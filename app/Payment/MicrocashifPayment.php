<?php

namespace App\Payment;

use App\Models\RechargeOrder as OrderModel;

/**
 * Class MicrocashifPayment
 * @package App\Payment
 */
class MicrocashifPayment extends BaseCallbackPayment
{

    //  银行 ip 白名单
    const LIST_IP = [
        '18.229.29.178', // 银行给的
        '164.152.44.251', // 实际看到的
        '43.203.176.78',
    ];
    const RESPONSE_SUCCESS = 'success';//  回掉返回银行的值
    const PAYMENT_NAME = 'microcashif';

    const BANK_OPEN = 21;

    /**
     * @return bool
     */
    public function depositCallback()
    {

        $payment_name = self::PAYMENT_NAME;
        $callbackData = $_POST;
        if (!$callbackData) {
            $callbackData = file_get_contents('php://input');
            $callbackData = json_decode($callbackData, true);
        }
//        logToResponse($callbackData, self::PAYMENT_NAME . '_notify_');

        $ip = real_ip();
        if (!in_array($ip, self::LIST_IP)) {
            logToResponse($callbackData, $payment_name . '_in_black_ip_' . date('md'));
            TgBotMessage($payment_name . ' 代收回掉ip未加白 ' . $ip);
            echo self::RESPONSE_SUCCESS;
            exit;
        }

        if (!isset($callbackData['txId']) || $callbackData['txId'] == '') {
            logToResponse($callbackData, $payment_name . '_in_sf_id_no_' . date('md'));
            echo self::RESPONSE_SUCCESS;
            exit;
        }
        $sf_id = $callbackData['txId'];

        $redis = getLocalRedis();
        $cache_key = 'deposit_callback_lock:' . $sf_id;
        if (!$redis->setnx($cache_key, $sf_id)) {
            logToResponse($callbackData, $payment_name . '_in_cache_' . date('md'));
            echo self::RESPONSE_SUCCESS;
            exit;
        }
        $redis->expire($cache_key, 15); // 设置 15 秒过期

        $this->notify_data = $callbackData; // 回掉数据赋值

        $orderID = $this->parseOrderNumber($callbackData['tid']);
        $this->tag_id = isset($callbackData['endToEndId']) ? $callbackData['endToEndId'] : '';
        /**
         * @var $orderModel OrderModel
         */
        $this->order_info = $orderModel = OrderModel::where('orderid', '=', $orderID)->first();
        // 1. 订单是否存在
        if (!$orderModel) {
            logToResponse($callbackData, $payment_name . '_in_order_not_exists_' . date('md'));
            TgBotMessage($payment_name . '入款单号:' . $orderID . ' 我方需要手动处理');
            echo self::RESPONSE_SUCCESS;
            exit;
        }

        // 2.三方单号是否匹配
        if ($orderModel->sf_id != $sf_id) {
            logToResponse($callbackData, $payment_name . '_in_sf_id_exists_' . date('md'));
            TgBotMessage($payment_name . '入款单号:' . $orderID . ' 我方需要手动处理,回掉的三方单号' . $sf_id . '与我方记录的单号' . $orderModel->sf_id . '不一致。请告知技术处理');
            echo self::RESPONSE_SUCCESS;
            exit;
        }

        return true;
    }

}
