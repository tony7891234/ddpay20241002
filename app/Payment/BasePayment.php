<?php

namespace App\Payment;


/**
 * 三方支付相关
 * Class BasePayment
 * @package App\Payment
 */
class BasePayment
{


    /**
     * 错误代码
     * @var int
     */
    protected $errorCode;

    /**
     * 错误信息
     * @var string
     */
    protected $errorMessage = '';


    /**
     * 返回错误代码
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * 返回错误信息
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }


}
