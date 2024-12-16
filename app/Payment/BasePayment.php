<?php

namespace App\Payment;


use App\Models\PixModel;
use App\Traits\RepositoryTrait;

/**
 * 三方支付相关
 * Class BasePayment
 * @package App\Payment
 */
class BasePayment
{

    use RepositoryTrait;

    const PREFIX_ORDER_ID = 'dcat'; // 订单号的前缀 回掉识别


    const ERROR_CODE_AGAIN_JOB = -9991; // 需要再次 job 的 error_code

    const BANK_FIT = 1;
    const BANK_IUGU = 2;
    const BANK_RESET = 3;
    const LIST_BANK = [
        self::BANK_FIT => 'FIT',
        self::BANK_IUGU => 'IUGU',
        self::BANK_RESET => 'RESET',
    ];


    /**
     * @var string   pix  信息
     */
    public $pix_info = '';

    /**
     * @var string  出款信息
     */
    public $pix_out = '';

    /**
     * @var string 三方的订单id
     */
    public $bank_order_id;

    /**
     * @var string 出款的 pix account 账号
     */
    protected $pix_account;


    /**
     * 请求 银行 的订单号
     * @param $order_id
     * @return string
     */
    protected function sqlToBankOrder($order_id)
    {
        return self::PREFIX_ORDER_ID . $order_id;
    }

    /**
     * 检查是否允许出款
     * @return bool
     */
    protected function validateWithdraw()
    {
        /**
         * @var $pix_info PixModel
         */
        $this->pix_info = $pix_info = PixModel::where('account', '=', $this->pix_account)->first();
        if ($pix_info) {
            $pix_status = $pix_info->status;
            if ($pix_status && $pix_status != PixModel::STATUS_SUCCESS) {
                $this->errorCode = -21;
                $this->errorMessage = "pix 查询错误:::" . $pix_info['remark'];
                return false;
            }
        }

        return true;
    }

    /***************************************** 上面是支付用到的，下面是常规的 *************************************************/

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
