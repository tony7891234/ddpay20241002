<?php

namespace App\Models;

/**
 * 通道模型
 * Class Job
 * @package App\Models
 * @property int order_id  三方单号
 * @property string orderid  三方单号
 * @property string sysorderid  系统订单号  doOrderSn('api_pay' . $merchant['merchant_id']),
 * @property int merchantid 商户号
 * @property int update_time 更新时间
 * @property float amount 订单金额
 * @property int status
 * @property string remarks
 * @property string notifyurl 回调地址
 * @property int notify_status 回调状态 0=未回调;1=已经回调
 * @property int notify_num 回调次数
 * @property int completetime 完成时间(下游接收时间)
 * @property int create_time
 * @property int inizt  1=代付;0=代收
 * @property string yh_bq  yh_bq-tag_id
 * @property int amount_real_pay DocumentNumber
 * @property string realname - transaction_id
 */
class RechargeOrder extends BaseModel
{

    protected $connection = 'home';
    protected $table = 'cd_order';
    protected $primaryKey = 'order_id';
    protected $fillable = [];
    protected $guarded = ['order_id'];
    protected $hidden = [];


    //  数据格式
    protected $casts = [
        'payload' => 'json',
    ];

    // 回调状态  notify_status 回调状态 0=未回调;1=已经回调
    const NOTIFY_STATUS_WAITING = 0;
    const NOTIFY_STATUS_SUCCESS = 1;
    const NOTIFY_STATUS_FAIL = 2;

    // inizt  1=代付;0=代收
    const INIZT_RECHARGE = 0;
    const INIZT_WITHDRAW = 1;
    const LIST_INIZT = [
        self::INIZT_RECHARGE => '代收',
        self::INIZT_WITHDRAW => '代付',
    ];


    const STATUS_SUCCESS = 1;
    const STATUS_WAITING = 2;
    const STATUS_FAIL = 0;
    const STATUS_FAIL_TATA = 3;
    const STATUS_FAIL_DD_PAY = 4;
    const STATUS_AUTO_DEVICE = 5;
    const LIST_STATUS = [
        self::STATUS_SUCCESS => '成功',
        self::STATUS_WAITING => '待付款',
        self::STATUS_FAIL => '失败',
        self::STATUS_FAIL_TATA => 'tata失败',
        self::STATUS_FAIL_DD_PAY => 'ddpay失败',
        self::STATUS_AUTO_DEVICE => '设备待出款',
    ];


    // 通道
    const LIST_BANK_OPEN = [
        3 => 'epay',
        4 => 'toppay',
        5 => 'tggpay',
        7 => 'mybank',
        8 => 'epay2',
        10 => 'fitbank',
        12 => 'Pagnovo',
        13 => 'reset',
        14 => 'tamborete',
        15 => 'iugu',
    ];

    /**
     * 自增ID
     * @return int
     */
    public function getId()
    {
        return $this->order_id;
    }


}
