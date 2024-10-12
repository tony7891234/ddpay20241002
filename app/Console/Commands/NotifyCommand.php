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

        $this->notify();

        return true;
    }

    public function notify()
    {

//        $merchant_list = Mer
        /**
         * @var $list RechargeOrder[]
         */
        $list = RechargeOrder::where('status', '<', 2)
            ->where('order_id', '>=', 117624290)
            ->where('order_id', '<=', 117624312)
//            ->where('notify_status', 0)
//            ->where('notify_num', '<', 2)
            ->limit(100)
            ->get();
        if ($list->isEmpty()) {
            sleep(1); // 没有数据，休息1S
            dump(111);
            return true;
        }

        // 商户ID列表
        $merchant_list = MerchantModel::pluck('secret', 'merchant_id');
        dump($merchant_list);

        $urlsWithParams = [];
        foreach ($list as $k => $orderInfo) {
            //  检查回调状态
            if ($orderInfo->status > 1) {
                continue;
            }
            // 检查回调地址
            if (filter_var($orderInfo->notifyurl, FILTER_VALIDATE_URL) == false) {
                continue;
            }
            if (!isset($merchant_list[$orderInfo->merchantid])) {
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
            $data['Remarks'] = $orderInfo->remarks;
            $notify_url = $orderInfo->notifyurl;
            // 添加并发回调数据
            $urlsWithParams[] = [
                $notify_url => [
                    'request_param' => $data,
                    'order_id' => $orderInfo->order_id,
                ],
            ];
        }
        if ($urlsWithParams) {
            dump($urlsWithParams);
            $this->curlPostMax($urlsWithParams);
        }

    }

    /**
     * 更新成 回调失败状态
     * @param $orderInfo RechargeOrder
     * @return bool
     */
    private function updateNotifyToFail($orderInfo)
    {
        $orderInfo->notify_status = RechargeOrder::NOTIFY_STATUS_FAIL;
        $orderInfo->update_time = time();
        $orderInfo->completetime = time();
        $orderInfo->notify_num += 1;
        $orderInfo->save();
        return true;
    }

    /**
     * 更新成 回调失败状态
     * @param $orderInfo RechargeOrder
     * @return bool
     */
    private function updateNotifyToSuccess($orderInfo)
    {
        $orderInfo->notify_status = RechargeOrder::NOTIFY_STATUS_SUCCESS;
        $orderInfo->update_time = time();
        $orderInfo->completetime = time();
        $orderInfo->notify_num += 1;
        $orderInfo->save();
        return true;
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
        foreach ($allGames as $request_url => $params) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $request_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true); // 设置为 POST 请求
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params['request_param'])); // 设置 POST 数据
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
            $curlHandles[$request_url] = [
                'ch' => $ch,
                'order_id' => $params['order_id'],
            ]; // 保存句柄以便后续使用
        }

        $running = null;
        do {
            curl_multi_exec($chHandle, $running); //3 执行批处理句柄
            curl_multi_select($chHandle); // 等待活动请求完成  可以不要
        } while ($running > 0);

        foreach ($chArr as $request_url => $data) {
            $ch = $data['ch'];
            $order_id = $data['order_id']; // 订单ID
            $result = curl_multi_getcontent($ch); //5 获取句柄的返回值
            dump('$result' . $result);
            dump('$order_id' . $order_id);
            curl_multi_remove_handle($chHandle, $ch);//6 将$chHandle中的句柄移除
            curl_close($ch);
        }
        curl_multi_close($chHandle); //7 关闭全部句柄
    }


    /**
     * 并发之后的处理
     * @param $order_id int
     * @return bool
     */
    private function afterCurl($order_id)
    {
        $orderInfo = [];
        if (empty($notify_url)) {
            $orderInfo->notify_status = 2; // 可能是失败的
        } else {
            if (filter_var($notify_url, FILTER_VALIDATE_URL) == false) {
                $orderInfo->notify_status = 2;
                $orderInfo->update_time = time();
                $orderInfo->completetime = time();
                $orderInfo->notify_num += 1;
                $orderInfo->save();
                return false;
            }

//
//                $orderInfo->update_time = time();
//                $orderInfo->save();

//                if ($result == 'success' || strtolower($result) == 'success' || strtolower($result) == 'ok') {
//
//                    $orderInfo->notify_status = 1;
//                    $orderInfo->update_time = time();
//                    $orderInfo->completetime = time();
//                    $orderInfo->notify_num += 1;
//                    $orderInfo->save();
//                } else {
//                    $orderInfo->update_time = time();
//                    $orderInfo->completetime = time();
//                    $orderInfo->notify_num += 1;
//                    $orderInfo->save();
//                    $tgMessage = <<<MG
//  ⚠代付通知下游⚠️\r\n
//
//原  因：通知下游失败\r\n
//订单 号 : {$orderInfo['orderid']} \r\n
//订单状态：已完成\r\n
//通知地址：{$notify_url} \r\n
//响应结果：{$result} \r\n
//请求数据: {$data}
//
//\r\n
//MG;
//
//                    TgBotMessage($tgMessage);
//                }
        }

    }


}
