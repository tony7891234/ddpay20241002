<?php

namespace App\Models;

/**
 * 商户
 * @param int merchant_id  商户ID
 * @param string secret  密钥
 * Class MerchantModel
 * @package App\Models
 */
class MerchantModel extends BaseModel
{

    protected $connection = 'mysql';
    protected $table = 'cd_order';
    protected $primaryKey = 'order_id';
    protected $fillable = [];
    protected $guarded = ['order_id'];
    protected $hidden = [];


    //  数据格式
    protected $casts = [
        'payload' => 'json',
    ];

    // 回调状态
    const NOTIFY_STATUS_WAITING = 0;
    const NOTIFY_STATUS_SUCCESS = 1;
    const NOTIFY_STATUS_FAIL = 2;


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

    /**
     * 自增ID
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


}
