<?php

namespace App\Models;


/**
 * cd_notify_order 得到的数据库
 * Class MapNotifyOrder
 * @package App\Models
 */
class MapNotifyOrder extends BaseModel
{

    protected $connection = 'rds';
    protected $table = 'cd_notify_order';
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = ['id'];
    protected $hidden = [];

    // 回调状态  notify_status 回调状态 0=未回调;1=已经回调

    const NOTIFY_STATUS_SUCCESS = 1; // 这个值，和系统的值是不一样的
    const NOTIFY_STATUS_400 = 2; // 400 是这个
    const NOTIFY_STATUS_ERROR = 3; // 返回的不是 success/ok
    const NOTIFY_STATUS_FAIL = 4; //
    const NOTIFY_STATUS_SITE = 5; // 回掉到了站点
    const LIST_NOTIFY_STATUS = [
        self::NOTIFY_STATUS_SUCCESS => '回掉成功', // 这个值，和系统的值是不一样的
        self::NOTIFY_STATUS_400 => '回掉http400', // 400 是这个
        self::NOTIFY_STATUS_ERROR => '返回错误', // 返回的不是 success/ok
        self::NOTIFY_STATUS_FAIL => '返回不是success', //
        self::NOTIFY_STATUS_SITE => '站点回掉', // 回掉到了站点
    ];

    // type  1=充值；2=提款
    const TYPE_RECHARGE = 1;
    const TYPE_WITHDRAW = 2;
    const LIST_TYPE = [
        self::TYPE_RECHARGE => '充值订单',
        self::TYPE_WITHDRAW => '提款订单',
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
