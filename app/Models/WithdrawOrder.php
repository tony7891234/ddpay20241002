<?php

namespace App\Models;

/**
 * 提款订单
 * Class WithdrawOrder
 * @package App\Models
 * @property int order_id
 * @property int merchant_id 商户id
 * @property int upstream_id  银行
 * @property int batch_no 批量单号
 * @property string pix_type pix类型
 * @property string pix_account pix账号
 *
 * @property double withdraw_amount 出款金额
 * @property string user_message 附言(给客户的)
 * @property string remark 备注(运营)
 * @property string error_message 错误信息
 * @property string request_bank 请求银行内容 废弃
 * @property string response_bank 银行返回  废弃
 * @property int created_at 添加时间
 * @property int updated_at 更新时间
 * @property int status 支付状态
 *
 * @property string bank_order_id 银行的id
 * @property string pix_info
 * @property string pix_out
 * @property string notify_info 银行回掉信息
 *
 */
class WithdrawOrder extends BaseModel
{
    protected $table = 'cd_withdraw_orders';
    protected $primaryKey = 'order_id';
    protected $fillable = [];
    protected $guarded = ['order_id'];
    protected $hidden = [];

    const MERCHANT_DEFAULT = 1001; // 默认的商户号码

    // status 代表的状态
    const STATUS_WAITING = 1;
    const STATUS_REQUEST_BANK = 2;
    const STATUS_REQUEST_SUCCESS = 3;
    const STATUS_REQUEST_FAIL = 4;
    const STATUS_NOTIFY_SUCCESS = 5;
    const STATUS_NOTIFY_FAIL = 6;
    const STATUS_REQUEST_AGAIN_JOB = 7;

    const LIST_STATUS = [
        self::STATUS_WAITING => '待处理',

        self::STATUS_REQUEST_BANK => '已提交银行',

        self::STATUS_REQUEST_SUCCESS => '请求银行成功',
        self::STATUS_REQUEST_FAIL => '请求银行失败',
        self::STATUS_REQUEST_AGAIN_JOB => '请求银行失败(待再次请求)',

        self::STATUS_NOTIFY_SUCCESS => '银行回掉成功',
        self::STATUS_NOTIFY_FAIL => '银行回掉失败',
    ];


    //  pix_type pix类型
    const PIX_PHONE = 'PHONE';
    const PIX_CPF = 'CPF';
    const PIX_EVP = 'EVP';
    const PIX_EMAIL = 'EMAIL';
    const PIX_CNPJ = 'CNPJ';
    const LIST_PIX = [
        self::PIX_PHONE => 'PHONE',
        self::PIX_CPF => 'CPF',
        self::PIX_EVP => 'EVP',
        self::PIX_EMAIL => 'EMAIL',
        self::PIX_CNPJ => 'CNPJ',
    ];


    /**
     * 自增ID
     * @return int
     */
    public function getId()
    {
        return $this->order_id;
    }


    /**
     * 更新银行回掉数据
     * @param string|array $notify_info
     * @return bool
     */
    public function updateNotifyInfo($notify_info)
    {
        return $this->update([
            'notify_info' => is_string($notify_info) ? $notify_info : json_encode($notify_info, JSON_UNESCAPED_UNICODE),
            'updated_at' => time(),
        ]);
    }


    /**
     * 2.请求银行
     * @param $error_message
     * @return int
     */
    public function updateToRequestBank($error_message = '')
    {
        return $this->update([
            'status' => self::STATUS_REQUEST_BANK,
            'error_message' => $error_message,
            'updated_at' => time(),
        ]);
    }


    /**
     * 3.请求银行成功
     * @param string $pix_info
     * @param string $pix_out
     * @param string $bank_order_id
     * @return bool
     */
    public function updateToRequestSuccess($pix_info, $pix_out, $bank_order_id)
    {
        return $this->update([
            'pix_info' => is_string($pix_info) ? $pix_info : json_encode($pix_info, JSON_UNESCAPED_UNICODE),
            'pix_out' => is_string($pix_out) ? $pix_out : json_encode($pix_out, JSON_UNESCAPED_UNICODE),
            'status' => self::STATUS_REQUEST_SUCCESS,
            'bank_order_id' => $bank_order_id,
            'updated_at' => time(),
        ]);
    }


    /**
     * 4.请求银行失败
     * @param string $pix_info
     * @param string $pix_out
     * @param string $error_message
     * @param int $status
     * @return int
     */
    public function updateToRequestFail($pix_info, $pix_out, $error_message = '', $status = self::STATUS_REQUEST_FAIL)
    {
        return $this->update([
            'status' => $status,
            'pix_info' => is_string($pix_info) ? $pix_info : json_encode($pix_info, JSON_UNESCAPED_UNICODE),
            'pix_out' => is_string($pix_out) ? $pix_out : json_encode($pix_out, JSON_UNESCAPED_UNICODE),
            'error_message' => $error_message,
            'updated_at' => time(),
        ]);
    }

    /**
     * 5.银行回掉成功
     * @return bool
     */
    public function updateToNotifySuccess()
    {
        return $this->update([
            'status' => self::STATUS_NOTIFY_SUCCESS,
            'updated_at' => time(),
        ]);
    }

    /**
     * 6.银行回掉失败
     * @param $error_message
     * @return int
     */
    public function updateNotifyFail($error_message = '')
    {
        return $this->update([
            'status' => self::STATUS_NOTIFY_FAIL,
            'error_message' => $error_message,
            'updated_at' => time(),
        ]);
    }


    /**
     * 检查是不是 可以请求银行的状态
     * @return bool
     */
    public function isRequestStatus()
    {
        // 待处理和请求银行失败
        return in_array($this->status, [self::STATUS_WAITING, self::STATUS_REQUEST_FAIL]);
    }

}
