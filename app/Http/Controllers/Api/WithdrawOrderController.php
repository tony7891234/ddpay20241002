<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Payment\BasePayment;
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
     * @param $upstream_id int
     * @param WithdrawOrderService $withdrawOrderService
     * @return array
     */
    public function notify($upstream_id, WithdrawOrderService $withdrawOrderService)
    {
        $response = $withdrawOrderService->notify($upstream_id);
        if ($response) {
            return $this->success('success');
        }
        return $this->error($withdrawOrderService->getErrorMessage(), $withdrawOrderService->getErrorCode());
    }


}
