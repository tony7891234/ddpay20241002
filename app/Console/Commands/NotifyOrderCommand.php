<?php

namespace App\Console\Commands;

use App\Models\NotifyOrder;
use App\Models\RechargeOrder;
use App\Traits\RepositoryTrait;

/**
 * 回掉异常订单
 * Class NotifyOrderCommand
 * @package App\Console\Commands
 */
class NotifyOrderCommand extends BaseCommand
{

    use RepositoryTrait;


    const MAX_NOTIFY_NUM = 3; // 最大回掉次数
    /**
     * @var string
     */
    protected $signature = 'notify_order';


    /**
     * @var string
     */
    protected $description = '2.回调异常订单';

    private $count_order = 0; // 总条数

    private $start_at = 0;
    private $curl_start = 0;
    private $sql_finished = 0;
    private $end_at = 0;

    /**
     * KG_Init constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * run
     */
    public function handle()
    {
        dump('restart ' . (getTimeString()) . '  ');
        while (true) {
            $this->notify();

            sleep(1);
        }

        return true;
    }

    public function notify()
    {
        $this->start_at = time();
        $current_time = time();

        /**
         * @var $list NotifyOrder[]
         */
        $list = NotifyOrder::select([
            'order_id',
            'notify_url',
            'request',
            'notify_time',
            'notify_num',
        ])->where('notify_time', '>', $current_time)
            ->where('notify_num', '<', self::MAX_NOTIFY_NUM) // 3次回掉失败
            ->orderBy('notify_time', 'asc')
            ->limit(500)
            ->get();

        $this->sql_finished = time(); // sql 结束时间
        // 商户ID列表
        $urlsWithParams = [];
        foreach ($list as $k => $notifyInfo) {
            $data = base64_decode($notifyInfo->request);
            $notify_url = $notifyInfo->notify_url;
            // 添加并发回调数据
            $urlsWithParams[$notifyInfo->order_id] = [
                'request_param' => $data,
                'notify_url' => $notify_url,
            ];
        }

        if ($urlsWithParams) {
            $this->curlPostMax($urlsWithParams);
        }

    }


    /**
     * @param $allGames
     */
    private function curlPostMax($allGames)
    {
        $this->curl_start = time();
        //1 创建批处理cURL句柄
        $chHandle = curl_multi_init();
        $chArr = [];
        //2.创建多个cURL资源
        foreach ($allGames as $order_id => $params) {
            $notify_url = $params['notify_url'];
            $request_param = $params['request_param'];
            $startTime = microtime(true);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $notify_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true); // 设置为 POST 请求
            curl_setopt($ch, CURLOPT_POSTFIELDS, ($request_param)); // 设置 POST 数据
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36',
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.9',
                'Connection: keep-alive'
            ]);

            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_multi_add_handle($chHandle, $ch); //2 增加句柄
            $chArr[$order_id] = [
                'ch' => $ch,
                'request_param' => $request_param,
                'notify_url' => $notify_url,
                'startTime' => $startTime,
            ]; // 保存句柄以便后续使用
        }

        $running = null;
        do {
            curl_multi_exec($chHandle, $running); //3 执行批处理句柄
            curl_multi_select($chHandle); // 等待活动请求完成  可以不要
        } while ($running > 0);

        foreach ($chArr as $order_id => $ch_data) {
            $ch = $ch_data['ch'];
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // 获取 HTTP 状态码
            if ($httpCode != 200) {
                $this->updateNotifyNum($order_id);
                continue; // 这次请求没成功，不做处理

            }
            $response = curl_multi_getcontent($ch); //5 获取句柄的返回值
            $response = strtolower($response);
            if (in_array($response, ['success', 'ok'])) {
                $this->updateNotifyToSuccess($order_id);
            } else {
                $this->updateNotifyToFail($order_id, $response);
            }

            curl_multi_remove_handle($chHandle, $ch);//6 将$chHandle中的句柄移除
            curl_close($ch);
        }
        curl_multi_close($chHandle); //7 关闭全部句柄


    }


    /**
     * 更新成 回调失败状态
     * @param int $order_id
     * @param string $response
     * @return bool
     */
    private function updateNotifyToFail($order_id, $response)
    {

        RechargeOrder::where('order_id', '=', $order_id)->update([
            'notify_status' => RechargeOrder::NOTIFY_STATUS_FAIL,
            'update_time' => time(),
            'completetime' => time(),
            'notify_num' => \DB::raw('notify_num + 1'),
        ]);


        NotifyOrder::where('order_id', '=', $order_id)->update([
            'notify_num' => \DB::raw('notify_num + 1'),
            'notify_time' => time() + 10, //10秒后
            'response' => $response,
            'notify_status' => NotifyOrder::NOTIFY_STATUS_FAIL,
        ]);
        return true;
    }


    /**
     *   更新次数  400的
     * @param int $order_id
     * @return bool
     */
    private function updateNotifyNum($order_id)
    {
        RechargeOrder::where('order_id', '=', $order_id)->update([
            'update_time' => time(),
            'completetime' => time(),
            'notify_num' => \DB::raw('notify_num + 1'),
        ]);

        NotifyOrder::where('order_id', '=', $order_id)->update([
            'notify_num' => \DB::raw('notify_num + 1'),
            'notify_time' => time() + 10, //10秒后
        ]);
        return true;
    }

    /**
     * 更新成 回调失败状态
     * @param int $order_id
     * @return bool
     */
    private function updateNotifyToSuccess($order_id)
    {
        RechargeOrder::where('order_id', '=', $order_id)->update([
            'notify_status' => RechargeOrder::NOTIFY_STATUS_SUCCESS,
            'update_time' => time(),
            'completetime' => time(),
            'notify_num' => \DB::raw('notify_num + 1'),
        ]);

        NotifyOrder::where('order_id', '=', $order_id)->update([
            'notify_status' => NotifyOrder::NOTIFY_STATUS_SUCCESS,
            'notify_num' => \DB::raw('notify_num + 1'),
        ]);
        return true;
    }


}
