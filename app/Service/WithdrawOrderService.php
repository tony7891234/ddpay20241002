<?php

namespace App\Service;

use App\Payment\BasePayment;
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
     * @param int $upstream_id
     * @return bool
     */
    public function notify($upstream_id = BasePayment::BANK_FIT)
    {

        $request = \Request::all();
//        logToMe('notify', ['$request' => $request, '$upstream_id' => $upstream_id]);
        $service = new HandelPayment();
        $service = $service->setUpstreamId($upstream_id)->getUpstreamHandelClass();

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
