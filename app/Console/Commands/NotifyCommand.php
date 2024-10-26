<?php

namespace App\Console\Commands;

use App\Models\MerchantModel;
use App\Models\NotifyOrder;
use App\Models\RechargeOrder;
use App\Traits\RepositoryTrait;

/**
 * Class Sync
 * @package App\Console\Commands
 */
class NotifyCommand extends BaseCommand
{

    use RepositoryTrait;

    const MAX_TIME = 5;// 超时多少秒，需要记录 log
    const FILE_NAME_LONG_TIME = 'long_'; // 超时5S没信息的
    const FILE_NAME_RESPONSE_NULL = 'nothing_'; // 什么否没有返回的

    /**
     * @var string
     */
    protected $signature = 'notify {action?}';


    /**
     * @var string
     */
    protected $description = '回调';

    private $count_order = 0; // 总条数

    private $start_at = 0;
    private $curl_start = 0;
    private $sql_finished = 0;
    private $end_at = 0;
    private $remark = '正常订单';

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
        $action = $this->argument('action');

        if ($action == 'notify') {
            while (true) {
                $this->notify();
                sleep(1);
            }
        } elseif ($action == 'left') {
            // 十分钟执行一次补发遗漏的
            $this->forLeftOrder();
        } else {
            echo 'nothing';
        }
        return true;
    }

    /**
     *  补发遗漏订单
     */
    public function forLeftOrder()
    {
        $this->remark = '遗漏订单';
        $this->start_at = time();

        // 10.17号，改成只处理一个小时之内的数据，不然可能需要的时间长
        $create_time = time() - 3600 * 5;
        $start = time() - 3600 * 24;
        $this->count_order = RechargeOrder::where('create_time', '>', $start)
            ->where('create_time', '<', $create_time)
            ->where('notify_status', RechargeOrder::NOTIFY_STATUS_WAITING)
            ->where('status', '<', 2)
            ->where('notify_num', '=', 0)
            ->count();
        $this->end_at = time();
        if ($this->count_order == 0) {
            $tgMessage = '没有遗漏数据';
            $this->getTelegramRepository()->replayMessage(config('telegram.group.callback_count'), $tgMessage);
            return true;
        }

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
            ->where('create_time', '>', $start) // 5小时之前的数据
            ->where('create_time', '<', $create_time)
            ->where('notify_status', RechargeOrder::NOTIFY_STATUS_WAITING)
            ->where('status', '<', 2)
            ->where('notify_num', '=', 0)
            ->orderBy('create_time', 'asc')
            ->limit(300)
            ->get();

        //  处理数据
        $this->forDataDetail($list);

    }


    /**
     * 正常回掉
     */
    private function notify()
    {
        $this->remark = '正常订单';

        $this->start_at = time();

        // 10.17号，改成只处理一个小时之内的数据，不然可能需要的时间长
        $create_time = time() - 3600 * 5;
        $this->count_order = RechargeOrder::where('create_time', '>', $create_time)
            ->where('notify_status', RechargeOrder::NOTIFY_STATUS_WAITING)
            ->where('status', '<', 2)
            ->where('notify_num', '=', 0)
            ->count();
        $this->end_at = time();

        dump((getTimeString()) . '  ' . $this->count_order . '  diff:' . ($this->end_at - $this->start_at));
//        if ($this->count_order < 30) { // 小于50就等待下一组
//            sleep(1); // 没有数据，休息1S
//        }


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
            ->where('create_time', '>', $create_time)
            ->where('notify_status', RechargeOrder::NOTIFY_STATUS_WAITING)
            ->where('status', '<', 2)
            ->where('notify_num', '=', 0)
            ->orderBy('create_time', 'asc')
            ->limit(1000)
            ->get();
        //  处理数据
        $this->forDataDetail($list);
    }

    /**
     * @var $list RechargeOrder[]
     */
    private function forDataDetail($list)
    {
        $this->sql_finished = time(); // sql 结束时间
        // 商户ID列表
        $merchant_list = MerchantModel::pluck('secret', 'merchant_id');
        $urlsWithParams = [];
//        $id_arr = [];
//        $this->count_order = 0;
        foreach ($list as $k => $orderInfo) {
//            $this->count_order++;
//            dump($orderInfo->orderid);
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
                'orderid' => $orderInfo->orderid,
                'request_param' => $data,
                'notify_url' => $notify_url,
                'inizt' => $orderInfo->inizt,
            ];
//            $id_arr[] = $orderInfo->getId();
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
            $inizt = $params['inizt'];
            $orderid = $params['orderid'];
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
                'inizt' => $inizt,
                'orderid' => $orderid,
                'startTime' => $startTime,
            ]; // 保存句柄以便后续使用
        }

        $running = null;
        do {
            curl_multi_exec($chHandle, $running); //3 执行批处理句柄
            curl_multi_select($chHandle); // 等待活动请求完成  可以不要
        } while ($running > 0);

        $response_success = $response_error = $response_null = $response_http_no_200 = 0;
        foreach ($chArr as $order_id => $ch_data) {
            $ch = $ch_data['ch'];
            $request_param = $ch_data['request_param'];
            $notify_url = $ch_data['notify_url'];
            $inizt = $ch_data['inizt'];
            $orderid = $ch_data['orderid'];
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // 获取 HTTP 状态码
            if ($httpCode != 200) {
                $response_http_no_200++;
                $this->updateNotifyNum($order_id, $orderid, $request_param, $notify_url, $inizt);
                $file = ('no_200_' . date('Ymd') . '.txt');
                $log_data = '---' . $order_id . '--' . $httpCode;
                logToPublicLog($log_data, $file); // 记录文件
                continue; // 这次请求没成功，不做处理
            }
            $result = curl_multi_getcontent($ch); //5 获取句柄的返回值
            $endTime = microtime(true); // 记录结束时间
            $result = strtolower($result);
            if (in_array($result, ['success', 'ok'])) {
                $response_success++;
                $this->updateNotifyToSuccess($order_id);
            } else {
                if ($result) {
                    $response_error++;
                    $this->updateNotifyToFail($order_id, $orderid, $request_param, $notify_url, $inizt);
                } else {
                    $response_null++;
                    $file = (self::FILE_NAME_RESPONSE_NULL . date('Ymd') . '.txt');
                    //  什么都没返回
                    logToPublicLog($order_id . '--', $file); // 记录文件
                }
            }

            $log_data = [
                'httpCode' => $httpCode,
                'diff_time' => $endTime - $ch_data['startTime'],
                'result' => $result,
                'request' => $request_param,
                'notify' => $notify_url,
            ];
            // 超时回调记录
            if ($log_data['diff_time'] > self::MAX_TIME) {
                $file = (self::FILE_NAME_LONG_TIME . date('Ymd') . '.txt');
                logToPublicLog($log_data, $file); // 记录文件
            }

            // https://test107.hulinb.com/logs_me/2024-10/df_notify20241012.txt
            if ($inizt == RechargeOrder::INIZT_RECHARGE) {
                $file = ('ds_notify' . date('Ymd') . '.txt');
            } else {
                $file = ('df_notify' . date('Ymd') . '.txt');
            }
//            dump($log_data);
            logToPublicLog($log_data, $file); // 记录文件
            curl_multi_remove_handle($chHandle, $ch);//6 将$chHandle中的句柄移除
            curl_close($ch);
        }
        curl_multi_close($chHandle); //7 关闭全部句柄


        $current_time = getTimeString();
        $startTimeTmp = date('H:i:s', $this->start_at);
        $curl_start = date('H:i:s', $this->curl_start);
        $sql_finished = date('H:i:s', $this->sql_finished);
        $endTimeTmp = date('H:i:s', time());
        $diff_time = (time() - $this->start_at);
        $remark = $this->remark;
        $tgMessage = <<<MG
类型：{$remark}\r\n
执行时间：{$current_time}\r\n
总单数：{$this->count_order} \r\n
成功条数：{$response_success} \r\n
失败条数：{$response_error} \r\n
空值条数：{$response_null} \r\n
HTTP非200条数：{$response_http_no_200} \r\n
执行时间：{$diff_time} \r\n
执行开始时间：{$startTimeTmp} \r\n
sql结束时间：{$sql_finished} \r\n
curl开始时间：{$curl_start} \r\n
执行结束时间: {$endTimeTmp}
\r\n
MG;
        $this->getTelegramRepository()->replayMessage(config('telegram.group.callback_count'), $tgMessage);


    }


    /**
     * 更新成 回调失败状态  返回的不是 success/ok
     * @param $order_id
     * @param $orderid
     * @param $request_param
     * @param $notify_url
     * @param $inizt
     * @return bool
     */
    private function updateNotifyToFail($order_id, $orderid, $request_param, $notify_url, $inizt)
    {

        RechargeOrder::where('order_id', '=', $order_id)->update([
            'notify_status' => RechargeOrder::NOTIFY_STATUS_FAIL,
            'update_time' => time(),
            'completetime' => time(),
            'notify_num' => \DB::raw('notify_num + 1'),
        ]);


        NotifyOrder::create([
            'order_id' => $order_id,
            'orderid' => $orderid,
            'create_time' => time(),
            'notify_time' => time() + 2,
            'notify_num' => 0,
            'notify_status' => NotifyOrder::NOTIFY_STATUS_ERROR,
            'type' => $inizt == RechargeOrder::INIZT_RECHARGE ? NotifyOrder::TYPE_RECHARGE : NotifyOrder::TYPE_WITHDRAW,
            'request' => base64_encode(json_encode($request_param)),
            'notify_url' => $notify_url,
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
     *   更新次数  400的
     * @param int $order_id
     * @param string $orderid
     * @param array $request_param
     * @param string $notify_url
     * @param int $inizt
     * @return bool
     */
    private function updateNotifyNum($order_id, $orderid, $request_param, $notify_url, $inizt)
    {
        RechargeOrder::where('order_id', '=', $order_id)->update([
            'update_time' => time(),
            'completetime' => time(),
            'notify_num' => \DB::raw('notify_num + 1'),
        ]);

        NotifyOrder::create([
            'order_id' => $order_id,
            'orderid' => $orderid,
            'create_time' => time(),
            'notify_time' => time() + 2,
            'notify_num' => 0,
            'notify_status' => NotifyOrder::NOTIFY_STATUS_400,
            'type' => $inizt == RechargeOrder::INIZT_RECHARGE ? NotifyOrder::TYPE_RECHARGE : NotifyOrder::TYPE_WITHDRAW,
            'request' => base64_encode(json_encode($request_param)),
            'notify_url' => $notify_url,
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

    /**
     * @param $params
     * @param $secret
     * @return bool|string
     */
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

}

