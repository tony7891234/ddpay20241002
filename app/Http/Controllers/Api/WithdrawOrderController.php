<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Service\WithdrawOrderService;

/**
 * 提款订单
 * Class WithdrawOrderController
 * @package App\Http\Controllers\Api
 */
class WithdrawOrderController extends ApiController
{

    /**
     * 出款回掉
     * @param WithdrawOrderService $withdrawOrderService
     * @return array
     */
    public function notify(WithdrawOrderService $withdrawOrderService)
    {
        $response = $withdrawOrderService->notify();
        if ($response) {
            return $this->success('success');
        }
        return $this->error($withdrawOrderService->getErrorMessage(), $withdrawOrderService->getErrorCode());
    }


}
