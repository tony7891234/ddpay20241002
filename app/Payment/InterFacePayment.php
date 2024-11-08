<?php

namespace App\Payment;

use App\Models\WithdrawOrder;

/**
 * 银行的接口
 * Interface InterFacePayment
 * @package App\Payment
 */
interface InterFacePayment
{

    /**
     * 1.出款请求
     * @param $orderInfo WithdrawOrder
     * @return array|bool
     */
    public function withdrawRequest($orderInfo);


    /**
     * 2.出款回掉
     * @param $callbackData array
     * @return array|bool
     */
    public function withdrawCallback($callbackData);


}
