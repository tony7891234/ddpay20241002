<?php

namespace App\Models;

/**
 * 提款订单
 * Class WithdrawOrder
 * @package App\Models
 * @property int order_id
 * @property int merchant_id 商户id
 * @property int batch_no 批量单号
 * @property string pix_type pix类型
 * @property string pix_account pix账号
 *
 * @property double withdraw_amount 出款金额
 * @property string user_message 附言(给客户的)
 * @property string remark 备注(运营)
 * @property string error_message 错误信息
 * @property string request_bank 请求银行内容
 * @property string response_bank 银行返回
 * @property int created_at 添加时间
 * @property int updated_at 更新时间
 * @property int status 支付状态
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
    const STATUS_MERCHANT_REQUEST = 1;
    const STATUS_UPSTREAM_REQUEST = 2;
    const STATUS_NOTIFY_SUCCESS = 3;
    const STATUS_NOTIFY_FAIL = 4;
    const LIST_STATUS = [
        self::STATUS_MERCHANT_REQUEST => '待处理',
        self::STATUS_UPSTREAM_REQUEST => '已提交银行',
        self::STATUS_NOTIFY_SUCCESS => '请求银行成功', // 回调返回成功
        self::STATUS_NOTIFY_FAIL => '请求银行失败', // 回调返回失败失败
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
     * 状态对应的颜色
     * @return array
     */
    public static function getStatusDot()
    {
        return [
            self::STATUS_MERCHANT_REQUEST => 'yellow',
            self::STATUS_UPSTREAM_REQUEST => 'danger',
            self::STATUS_NOTIFY_SUCCESS => 'success',
            self::STATUS_NOTIFY_FAIL => 'fail'
        ];
    }


    /**
     * 自增ID
     * @return int
     */
    public function getId()
    {
        return $this->order_id;
    }

    /**
     * 是否处理成功
     * @return bool
     */
    public function isSuccess()
    {
        return $this->status == self::STATUS_NOTIFY_SUCCESS;
    }

    /**
     * 更新状态成为已经向上游支付
     * @return bool
     */
    public function updateStatusToUpstreamRequest()
    {
        return $this->update([
            'status' => self::STATUS_UPSTREAM_REQUEST,
            'updated_at' => getTimeString(),
        ]);
    }

    /**
     * 支付成功
     * @return bool
     */
    public function updateToSuccess()
    {
        return $this->update([
            'status' => self::STATUS_NOTIFY_SUCCESS,
            'updated_at' => getTimeString(),
        ]);
    }


}
