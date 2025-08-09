<?php

namespace App\Service;

use App\Models\MerchantModel as MerchantModel;
use App\Models\RechargeOrder as OrderModel;

/**
 * 回掉商户的
 * Class NotifyService
 * @package App\Service
 */
class NotifyService extends BaseService
{

    const NOTIFY_TIME = 10; // 回掉秒数

    /**
     * 异步通知，  入款回掉
     * id 如果有ID 则查询该ID订单进行推送，如果无ID则查询所有符合条件的订单进行发送
     * 3的1～10次方 为基础数  18小时内  通知10次
     * @param int $order_id
     * @param bool|string
     */
    public function recharge_notify($order_id)
    {
        $fields = 'amount_real,amount,order_id,update_time,completetime,pay_name,notify_num,remarks,realname,merchantid,orderid,sysorderid,status,notify_status,notifyurl';

        /**
         * @var $orderInfo OrderModel
         */
        $orderInfo = OrderModel::select([$fields])->where('order_id', '=', $order_id)->first();

        // 1=成功   0=失败   其他的不处理
        if ($orderInfo['status'] > 1) {
            $this->errorMessage = "订单状态不正确";
            return false;
        }

        $merchant_secret = MerchantModel::where('merchant_id', '=', $orderInfo['merchantid'])->value('secret');

        $data = [
            'OrderID' => $orderInfo['orderid'],
            'SysOrderID' => $orderInfo['sysorderid'],
            'MerchantID' => $orderInfo['merchantid'],
            'CompleteTime' => date('YmdHis', $orderInfo['update_time']),
            'Amount' => $orderInfo['amount_real'],
            'Status' => $orderInfo['status']
        ];
        ksort($data);

        $data['Sign'] = strtoupper($this->newSign($data, $merchant_secret));
        $data['Remarks'] = $orderInfo['status'] == OrderModel::STATUS_SUCCESS ? $orderInfo['remarks'] : $orderInfo['realname'];
        $notify_url = $orderInfo['notifyurl'];

        if (empty($notify_url)) {
            $orderInfo->update_time = time();
            $orderInfo->completetime = time();
            $orderInfo->notify_num = 3;
            $orderInfo->pay_name = '没有回掉地址!';
            $orderInfo->notify_status = OrderModel::NOTIFY_STATUS_FAIL;
            $orderInfo->save();
            $this->errorMessage = '订单回调地址及商户默认回调地址均为空';
            return false;
        }

        if (filter_var($notify_url, FILTER_VALIDATE_URL) == false) {
            $orderInfo->update_time = time();
            $orderInfo->completetime = time();
            $orderInfo->notify_num = 3;
            $orderInfo->pay_name = '没有回掉地址!!';
            $orderInfo->notify_status = OrderModel::NOTIFY_STATUS_FAIL;
            $orderInfo->save();
            $this->errorMessage = '回掉地址不对' . $notify_url;
            return false;
        }

        if (in_array($data['MerchantID'], [278, 276, 358, 344, 424])) {
            $result = $this->curlPost292($notify_url, $data);
        } elseif ($data['MerchantID'] == 292) {
            // 原来用的post方法
            $result = $this->curlPost292($notify_url, $data);
        } elseif (in_array($data['MerchantID'], [315, 392])) {
            // 原来用的post方法
            $result = $this->curlPost315($notify_url, $data);
        } elseif (in_array($data['MerchantID'], [900])) {
            // 原来用的post方法
            $result = curlPostJson($notify_url, $data);
        } else {
            // 原来用的post方法
            $result = $this->curlPost315($notify_url, $data);
        }

        $orderInfo->update_time = time();
        $orderInfo->completetime = time();
        $orderInfo->notify_num += 1;
        if (in_array(strtolower($result), ['success', 'ok'])) {
            $orderInfo->pay_name = $result;
            $orderInfo->notify_status = OrderModel::NOTIFY_STATUS_SUCCESS;
            $orderInfo->save();
            return '操作成功';
        } else {
            $orderInfo->pay_name = strlen($result) > 200 ? substr($result, 200) : '返回:' . $result;
            $orderInfo->notify_status = OrderModel::NOTIFY_STATUS_FAIL;
            $orderInfo->save();
            // 3。16号 只记录失败的

            $notifyData = urldecode(http_build_query($data));

            logToResponse($result, 'ds_notify_error');

            $tgMessage = <<<MG
  ⚠代收回调️\r\n

原  因：通知下游失败\r\n
订单 号 : {$orderInfo['orderid']} \r\n
订单状态：已完成\r\n
通知地址：{$notify_url} \r\n
响应结果：{$result} \r\n
请求数据: {$notifyData}

\r\n
MG;

            TgToIndiaBaxi($tgMessage);
            if ($orderInfo->notify_num < 2) {
                return $this->recharge_notify($order_id);
            }
            return '远程无响应，请重试';
        }


    }

    public $errorMessage = '';

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
            $strsign = md5($strs);
            return $strsign;
        }
        return false;
    }

    private function curlPost315($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::NOTIFY_TIME);
        //设置超时时间
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private function curlPost292($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::NOTIFY_TIME);
        //设置超时时间
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }


}
