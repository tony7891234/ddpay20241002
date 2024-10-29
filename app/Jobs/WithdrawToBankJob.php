<?php

namespace App\Jobs;

use App\Models\WithdrawOrder;
use App\Payment\FitbankPayment;
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
        $service = new FitbankPayment();
        $response = $service->withdrawRequest($this->withdrawOrder);
        if (!$response) {
            $this->withdrawOrder->updateToRequestFail($service->pix_info, $service->pix_out, $service->getErrorMessage());
        } else {
            $this->withdrawOrder->updateToRequestSuccess($service->pix_info, $service->pix_out, $service->bank_order_id);
        }
    }


}
