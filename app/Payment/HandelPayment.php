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
     * @var int
     */
    private $upstream_id = 1;

    /**
     * @param $upstream_id
     * @return $this
     */
    public function setUpstreamId($upstream_id)
    {
        $this->upstream_id = $upstream_id;
        return $this;
    }

    /**
     * 获取某个支付的类
     * @return FitbankPayment|IuguPayment|ResetPayment
     */
    public function getUpstreamHandelClass()
    {
        if ($this->upstream_id == BasePayment::BANK_FIT) {
            return new FitbankPayment();
        } elseif ($this->upstream_id == BasePayment::BANK_IUGU) {
            return new IuguPayment();
        } elseif ($this->upstream_id == BasePayment::BANK_RESET) {
            return new ResetPayment();
        } else {
            $arr = $this->upstream_id . '不存在';
            logToMe('HandelPayment', $arr);
            die;
        }
    }


}
