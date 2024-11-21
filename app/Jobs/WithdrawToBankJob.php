<?php

namespace App\Jobs;

use App\Models\WithdrawOrder;
use App\Payment\HandelPayment;
use Carbon\Carbon;

/**
 * 出款到银行
 * Class WithdrawToBankJob
 * @package App\Jobs
 */
class WithdrawToBankJob extends BaseJob
{


    /**
     * @var WithdrawOrder
     */
    private $withdrawOrder;

    public function __construct($withdrawOrder)
    {
        $this->withdrawOrder = $withdrawOrder;
    }

    public function handle()
    {
        if (!$this->withdrawOrder->isStatusWaiting()) {
            logToMe('WithdrawToBankJob', [
                'msg' => '不是待处理订单',
                'status' => $this->withdrawOrder->status,
                'id' => $this->withdrawOrder->getId(),
            ]);
            return true;
        }
        $service = new HandelPayment();
        $service = $service->setUpstreamId($this->withdrawOrder->upstream_id)->getUpstreamHandelClass();

        $response = $service->withdrawRequest($this->withdrawOrder);
        if (!$response) {
            $this->withdrawOrder->updateToRequestFail($service->pix_info, $service->pix_out, $service->getErrorMessage());
        } else {
            $this->withdrawOrder->updateToRequestSuccess($service->pix_info, $service->pix_out, $service->bank_order_id);
        }
    }


}
