<?php

namespace App\Console\Commands;

use App\Models\MerchantModel;
use App\Models\RechargeOrder;

/**
 * Class Sync
 * @package App\Console\Commands
 */
class NotifyCommand extends BaseCommand
{

    const MAX_TIME = 5;// 超时多少秒，需要记录 log

    /**
     * @var string
     */
    protected $signature = 'notify';


    /**
     * @var string
     */
    protected $description = '回调';

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
        dump(getTimeString());
        while (true) {
            $this->notify();
        }

        return true;
    }

    public function notify()
    {

        $count = RechargeOrder::where('notify_status', 0)
            ->where('status', '<', 2)
            ->where('notify_num', '=', 0)
            ->count();
        dump(getTimeString() . '  ' . $count);
        /**
         * @var $list RechargeOrder[]
         */
        /*********************** 以下是正式，上面是测试 ********************************/
        $list = RechargeOrder::select([
            'amount',
            'order_id',
            'sysorderid',
            'merchantid',
            'update_time',
            'completetime',
            'notify_num',
            'remarks',
            'realname',
            'orderid',
            'status',
            'notify_status',
            'notifyurl',
            'inizt'
        ])
            ->where('notify_status', 0)
            ->where('status', '<', 2)
            ->where('notify_num', '=', 0)
            ->orderBy('order_id', 'desc')
            ->limit(500)
            ->get();


        if ($list->isEmpty()) {
            sleep(1); // 没有数据，休息1S
            return true;
        }

        // 商户ID列表
        $merchant_list = MerchantModel::pluck('secret', 'merchant_id');
        $urlsWithParams = [];
        $id_arr = [];
        foreach ($list as $k => $orderInfo) {
            //  检查回调状态
            if ($orderInfo->status > 1) {
                $this->updateNotifyStatusToFail($orderInfo->getId());
                continue;
            }
            // 检查回调地址
            if (filter_var($orderInfo->notifyurl, FILTER_VALIDATE_URL) == false) {
                $this->updateNotifyStatusToFail($orderInfo->getId());
                continue;
            }
            if (!isset($merchant_list[$orderInfo->merchantid])) {
                $this->updateNotifyStatusToFail($orderInfo->getId());
                continue; // 商户ID不存在
            }
            $merchant_secret = $merchant_list[$orderInfo->merchantid];
            $data = [
                'OrderID' => $orderInfo->orderid,
                'SysOrderID' => $orderInfo->sysorderid,
                'MerchantID' => $orderInfo->merchantid,
                'CompleteTime' => date('YmdHis', $orderInfo->update_time),
                'Amount' => $orderInfo->amount,
                'Status' => $orderInfo->status
            ];
            ksort($data);

            $data['Sign'] = strtoupper($this->newSign($data, $merchant_secret));
            $data['Remarks'] = $orderInfo->status == RechargeOrder::STATUS_SUCCESS ? $orderInfo->remarks : $orderInfo->realname;
            $notify_url = $orderInfo->notifyurl;
            // 添加并发回调数据
            $urlsWithParams[$orderInfo->order_id] = [
                'request_param' => $data,
                'notify_url' => $notify_url,
                'inizt' => $orderInfo->inizt,
            ];
            $id_arr[] = $orderInfo->getId();
        }

        if ($urlsWithParams) {
            $this->curlPostMax($urlsWithParams);
        }

    }


    private function newSign($params, $secret)
    {
        if (!empty($params)) {
            $p = ksort($params);
            if ($p) {
                $str = '';
                foreach ($params as $k => $v) {
                    $str .= $k . '=' . $v . '&';
                }
            }
            $strs = rtrim($str, '&') . $secret;
            return md5($strs);
        }
        return false;
    }

    private function curlPostMax($allGames)
    {
        //1 创建批处理cURL句柄
        $chHandle = curl_multi_init();
        $chArr = [];
        //2.创建多个cURL资源
        foreach ($allGames as $order_id => $params) {
            $notify_url = $params['notify_url'];
            $request_param = $params['request_param'];
            $inizt = $params['inizt'];
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

            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_multi_add_handle($chHandle, $ch); //2 增加句柄
            $chArr[$order_id] = [
                'ch' => $ch,
                'request_param' => $request_param,
                'notify_url' => $notify_url,
                'inizt' => $inizt,
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
            $request_param = $ch_data['request_param'];
            $notify_url = $ch_data['notify_url'];
            $inizt = $ch_data['inizt'];
            $result = curl_multi_getcontent($ch); //5 获取句柄的返回值
            $endTime = microtime(true); // 记录结束时间
            if (in_array(strtolower($result), ['success', 'ok'])) {
                $this->updateNotifyToSuccess($order_id);
            } else {
                $this->updateNotifyToFail($order_id);
            }

            $log_data = [
                'request' => $request_param,
                'notify' => $notify_url,
                'result' => $result,
                'diff_time' => $endTime - $ch_data['startTime'],
            ];
            // 超时回调记录
            if ($log_data['diff_time'] > self::MAX_TIME) {
                $file = ('long_time' . date('Ymd') . '.txt');
                logToPublicLog($log_data, $file); // 记录文件
            }

            // https://test107.hulinb.com/logs_me/2024-10/df_notify20241012.txt
            if ($inizt == RechargeOrder::INIZT_RECHARGE) {
                $file = ('ds_notify' . date('Ymd') . '.txt');
            } else {
                $file = ('df_notify' . date('Ymd') . '.txt');
            }
            logToPublicLog($log_data, $file); // 记录文件
            curl_multi_remove_handle($chHandle, $ch);//6 将$chHandle中的句柄移除
            curl_close($ch);
        }
        curl_multi_close($chHandle); //7 关闭全部句柄
    }


    /**
     * 更新成 回调失败状态
     * @param int $order_id
     * @return bool
     */
    private function updateNotifyToFail($order_id)
    {
        RechargeOrder::where('order_id', '=', $order_id)->update([
            'notify_status' => RechargeOrder::NOTIFY_STATUS_FAIL,
            'update_time' => time(),
            'completetime' => time(),
            'notify_num' => \DB::raw('notify_num + 1'),
        ]);
        return true;
    }

    /**
     *  这个是没有地址  或者商户的情况，只改状态
     * @param int $order_id
     * @return bool
     */
    private function updateNotifyStatusToFail($order_id)
    {
        RechargeOrder::where('order_id', '=', $order_id)->update([
            'notify_status' => RechargeOrder::NOTIFY_STATUS_FAIL,
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
        return true;
    }

}