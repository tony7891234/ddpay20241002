<?php

namespace App\Service;


use App\Payment\FitbankPayment;

/**
 * 出款订单
 * Class WithdrawOrderService
 * @package App\Service
 */
class WithdrawOrderService extends BaseService
{

    /**
     * 出款订单回掉
     * @return bool
     */
    public function notify()
    {

        $request = \Request::all();
        logToMe('notify', $request);
        $payment = new FitbankPayment();

        $response = $payment->withdrawCallback($request);
        if (!$response) {
            $this->errorCode = $payment->getErrorCode();
            $this->errorMessage = $payment->getErrorMessage();
            return false;
        }

        return true;
    }

}
