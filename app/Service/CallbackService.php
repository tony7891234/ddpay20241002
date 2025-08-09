<?php

namespace App\Service;


use App\Models\HttpLogModel;
use App\Models\MerchantModel;
use App\Models\MoneyLog;
use App\Models\RechargeOrder;
use App\Payment\MicrocashifPayment;
use App\Payment\VitalcredPayment;
use Illuminate\Support\Facades\DB;

/**
 * Class FitService
 * @package App\Service
 */
class CallbackService extends BaseService
{


    /**
     * 入款回掉
     * @return bool
     */
    public function deposit()
    {
        $payment_name = $_GET['code'];
        // 获取对应的支付
        $pay_class = $this->getPaymentByPayName($payment_name);
        if (!$pay_class) {
            $callbackData = $_POST;
            if (!$callbackData) {
                $callbackData = file_get_contents('php://input');
                $callbackData = json_decode($callbackData, true);
            }
            TgBotMessage($payment_name . '对应的通道不存在，请联系技术');
            logToResponse($callbackData, $payment_name . '_in_back_bank_open');
            echo 'success';
            exit;
        }

        // 获取回掉数据
        $callbackData = $pay_class->getNotifyData();

        // 获取三方订单id
        $sf_id = $pay_class->getSfId();

        $tag_id = $pay_class->getTagId();

        // 订单信息
        $order_info = $pay_class->getOrderInfo();
        // 获取我方订单id
        $orderID = $order_info['orderid'];
        // 3. bank_open 是否匹配
        if ($order_info['bank_open'] != $pay_class->getBankOpen()) {
            TgBotMessage($payment_name . "_in_back_bank_open 收到非本银行回掉" . $sf_id . '  单号' . $order_info['orderid']);
            logToResponse($callbackData, $payment_name . '_in_back_bank_open');
            echo $pay_class->getResponseSuccess();
            exit;
        }

        // 4. 我方订单状态是否正确
        if ($order_info['status'] != 2) {
            logToResponse($callbackData, $payment_name . '_in_status_no_2_' . date('md'));
            echo $pay_class->getResponseSuccess();
            exit;
        }
        //  5.记录日志
        $this->redisCacheCallback($orderID, $callbackData);

        //  6 订单成功 的处理

        $response = $this->depositOrderSuccess($order_info, $tag_id);
        if (!$response) {
            echo $pay_class->getResponseSuccess();
            exit;
        }

        // 7. 回掉商户
        $api_service = new  NotifyService();
        $api_service->recharge_notify($orderID);

        // 8. 代理佣金 给代理加积分
        $this->rebateAgent($order_info);
        echo $pay_class->getResponseSuccess();
        exit;
    }


    /**
     * 出款回掉
     * @return bool
     */
    public function transfer()
    {

        return true;
    }

    /**
     * 回掉的 code  对应的支付类文件名字
     * @param $pay_name
     * @return MicrocashifPayment|VitalcredPayment|bool
     */
    private function getPaymentByPayName($pay_name)
    {
        $list_name = ['microcashif', 'vitalcred'];
        if (!in_array($pay_name, $list_name)) {
            $error = ($pay_name . ' not exists');
            logToResponse($error, 'getPaymentByPayName', true);
            die();
        }

        if ($pay_name == 'microcashif') {
            return new MicrocashifPayment();
        }

        if ($pay_name == 'vitalcred') {
            return new VitalcredPayment();
        }

        return false;
    }


    /**
     * 记录日志
     * @param $orderId
     * @param $value
     */
    private function redisCacheCallback($orderId, $value)
    {
        if (is_array($value)) {
            $value['time_me'] = date('Y-m-d H:i:s');// 3.15号添加记录时间
        }
        try {
            $res = HttpLogModel::where('order_id', '=', $orderId)->update(['updatetime' => time(), 'callback' => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

            if (!$res) {
                HttpLogModel::insert(['order_id' => $orderId, 'updatetime' => time(), 'callback' => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }

        } catch (\Exception $e) {

        }

    }


    /**
     * 3.入款成功
     * @param $order_data
     * @param $tag_id
     * @return bool
     */
    private function depositOrderSuccess($order_data, $tag_id = '')
    {

        try {
            DB::beginTransaction();//事务开始
            // 1.获取商户信息
            $merchant = MerchantModel::where('merchant_id', $order_data['merchantid'])->field('merchant_id,QRBX,balance')->first();
            if (!$merchant) {
                // 商户不存在
                Db::rollback(); // 回滚事务
                return false;
            }
            // 2.更新订单状态
            $status_update = RechargeOrder::where('order_id', $order_data['order_id'])
                ->update([
                    'status' => RechargeOrder::STATUS_SUCCESS,
                ]);
            if (!$status_update) {
                //  之前已经更新过了  重复更新
                logToResponse($order_data['order_id'], 'depositOrderSuccess_again');
                DB::rollback();//回滚
                return false;
            }

            $real_amount = $order_data['amount'] * (1 - $merchant['QRBX'] / 1000);
            // 2.2 更新订单其他字段
            // 2025。08  备注  没有用到   去掉的字段   realname  amount_real_pay
            RechargeOrder::where('order_id', $order_data['order_id'])
                ->update([
                    'update_time' => time(),
                    'remarks' => "正常回调",
                    'notify_status' => RechargeOrder::NOTIFY_STATUS_WAITING,
                    'df_fee' => $order_data['amount'] - $real_amount,
                    'yh_bq' => $tag_id,
                ]);

            // 3.3加余额
            MerchantModel::where('merchant_id', $order_data['merchantid'])
                ->increment('balance', $real_amount);

            // 4. 添加 moneylog
            $log = [
                'merchant_id' => $merchant['merchant_id'],
                'type' => 10,
                'bank_lx' => $order_data['bank_open'],
                'begin' => $merchant['balance'],
                'after' => $merchant['balance'] + $real_amount,
                'money' => $real_amount,
                'action' => '上游回调成功',
                'content' => $order_data['amount'] - $real_amount,
                'adduser' => $order_data['orderid'],
                'create_time' => time()
            ];

            MoneyLog::create($log);
            DB::commit();//提交
        } catch (\Exception $e) {
            DB::rollback();//回滚
            TgBotMessage("代收回调事务失败[回调成功,数据操作失败]：" . $order_data['orderid'] . $e->getMessage());
        }

        return true;
    }


    /**
     * 代理返利
     *
     * @param RechargeOrder $order 订单信息
     */
    private function rebateAgent($order)
    {

        $merchantInfo = MerchantModel::where('merchant_id', '=', $order['merchantid'])->first();
        if (!$merchantInfo) {
            // 商户不存在
            return false;
        }

        if (!$merchantInfo['jsr']) {
            // 无介绍人
            return false;
        }

        if ($order['inizt'] == 0) {
            if ($merchantInfo['yongjin'] <= 0) {
                // 佣金返利百分比为0
                return false;
            }
            $rate = $merchantInfo['yongjin'];
            $action = '充值订单返利';
        } else {
            if ($merchantInfo['df_yongjin'] <= 0) {
                // 佣金返利百分比为0
                return false;
            }
            $rate = $merchantInfo['df_yongjin'];
            $action = '代付订单返利';
        }


        /**
         * 代理信息
         */
        $agentInfo = DB::connection('home')->table('cd_agent')
            ->where('agent_id', '=', $merchantInfo['jsr'])
            ->first();

        if (!$agentInfo || $agentInfo['status'] == 0) {
            // 代理信息无
            return false;
        }

        //返利金额
        $change_money = $order['amount'] * $rate / 1000;

        if ($change_money <= 0) {
            // 金额错误
            return false;
        }

        // 1代理加钱
        DB::connection('home')->table('cd_agent')
            ->where('agent_id', '=', $agentInfo->agent_id)
            ->increment('balance', $change_money);

        // 2、记录日志
        DB::connection('home')->table('agent_moneylog')->insert([
            'agent_id' => $agentInfo->agent_id,
            'type' => 3,
            'action' => $action,
            'begin' => $agentInfo->balance,
            'after' => $agentInfo->balance + $change_money,
            'money' => $change_money,
            'content' => $order['order_id'],
            'remark' => '',
            'adduser' => $order['order_id'],
            'create_time' => time(),
        ]);

        // 修改佣金
        RechargeOrder::where('order_id', '=', $order['order_id'])->update(['agent_commission' => $change_money]);

        return true;
    }


}
