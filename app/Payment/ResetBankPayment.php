<?php

namespace App\Payment;

use App\Models\WithdrawOrder;

/**
 * 11.8 号
 * 巴西新银行：Reset
 * Chave pix:
 * 49.440.778/0001-00
 * UUID
 * 67363858-a26a-43b7-94ab-23db6002aada
 * API Key
 * QfjxIcQ-iLdQ-Oupzxv-sLpn-NNDky2dD
 * API Secret
 * kns-rPPJX7qtS-aMY4-1V7WS-uVyHSuOoz2-arWe-CyGFpESp
 * API文档
 * https://api.pixease.reset-bank.com/api
 *
 * 说明说明
 * 有了uuid，就可以请求创建 qrcode
 * 金额应为整数，例如 100 表示 1.00 雷亚尔
 * 如何创建签名
 * https://api.pixease.reset-bank.com/how-to-sign
 *
 * 只有带有挂锁图标的API才需要 apikey
 * 所以
 * 存款，不需要 apikey，只需要 user_account_uuid
 * 提款，需要 apikey + secret 来请求
 *
 * PowerFull
 * Power@2024!
 * Class ResetBankPayment
 * @package App\Payment
 */
class ResetBankPayment extends BasePayment implements InterFacePayment
{


    /**
     * 1.出款请求
     * @param $orderInfo WithdrawOrder
     * @return array|bool
     */
    public function withdrawRequest($orderInfo)
    {

        // 检查数据是否可以出款
        $validate = $this->validateWithdraw();
        if (!$validate) {
            return false;
        }

        return true;
    }


    /**
     * 2.出款回掉
     * @param $callbackData array
     * @return array|bool
     */
    public function withdrawCallback($callbackData)
    {

//        $callbackData = json_decode($this->ParseNotifyData($request), true);

        if ($callbackData['Method'] != 'PixOut') {
            $this->errorCode = -101;
            $this->errorMessage = 'Method 有误';
            return false;
        }

        if (!isset($callbackData['Identifier']) || !isset($callbackData['DocumentNumber'])) {
            $this->errorCode = -102;
            $this->errorMessage = 'Identifier or DocumentNumber 参数不存在';
            return false;
        }
//        'orderid' => $callbackData['Identifier'],
//            'bank_order_id' => $callbackData['DocumentNumber'],
        $orderInfo = $this->getWithdrawOrderRepository()->getById($callbackData['Identifier']);
        if (!$orderInfo) {
            $this->errorCode = -21;
            $this->errorMessage = '订单不存在' . $callbackData['Identifier'];
            return false;
        }

        // 更新回掉的数据
        $orderInfo->updateNotifyInfo($callbackData);

        if ($orderInfo->status != WithdrawOrder::STATUS_REQUEST_SUCCESS) {
            $this->errorCode = -22;
            $this->errorMessage = '订单状态不对' . $orderInfo->status;
            return false;
        }

        if ($callbackData['Status'] == 'Cancel') {
            $this->errorCode = -23;
            $this->errorMessage = '失败原因:' . $callbackData['ErrorDescription'] ?? '';
            $orderInfo->updateNotifyFail($this->errorMessage);
            return false;
        }

        if ($callbackData['Status'] != 'Paid') {
            $this->errorCode = -24;
            $this->errorMessage = '支付失败';
            $orderInfo->updateNotifyFail($this->errorMessage);
            return false;
        }

        $orderInfo->updateToNotifySuccess();
        return true;


    }

    /************************************************* 下面是私钥方法 ****************************************************/


}
