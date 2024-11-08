<?php

namespace App\Service;

use App\Payment\HandelPayment;

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
//        logToMe('notify', $request);
        $service = new HandelPayment();
        $service = $service->getUpstreamHandelClass();

        $response = $service->withdrawCallback($request);
        if (!$response) {
            $this->errorCode = $service->getErrorCode();
            $this->errorMessage = $service->getErrorMessage();
            return false;
        }

        // 回掉成功
        return true;
    }

}
