<?php

namespace App\Payment;

use App\Models\WithdrawOrder;
use Illuminate\Support\Facades\Storage;

/**
 * Class VitalcredPayment
 * @package App\Payment
 */
class VitalcredPayment extends BasePayment
{

    /**
     * 1.出款请求
     * https://dev.iugu.com/reference/baas-pix-ted-out
     * @param $orderInfo WithdrawOrder
     * @return array|bool
     */
    public function withdrawRequest($orderInfo)
    {


    }


}
