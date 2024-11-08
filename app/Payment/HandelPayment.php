<?php

namespace App\Payment;

use App\Traits\RepositoryTrait;

/**
 * 付款银行的 handel  类  通过这个，来操作 FitbankPayment 银行，这样可以随时切换银行
 * Class HandelPayment
 * @package App\Payment
 */
class HandelPayment extends BasePayment
{
    use RepositoryTrait;


    /**
     * 获取某个支付的类
     * @return FitbankPayment
     */
    public function getUpstreamHandelClass()
    {
        return new FitbankPayment();
    }


}
