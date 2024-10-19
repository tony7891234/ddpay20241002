<?php

namespace App\Models;


/**
 * 回掉订单
 * @property  int id id
 * @property  int order_id 订单id
 * @property  int create_time 添加时间
 * @property  int notify_time 下次回掉时间
 * @property  int notify_num  回掉次数
 * @property  int notify_status  回掉状态
 * @property  int type  1=充值；2=提款
 * @property  string response  返回内容
 * @property  string request  回掉内容
 * @property  string notify_url  回掉地址
 * Class NotifyOrder
 * @package App\Models
 */
class NotifyOrder extends BaseModel
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
    const NOTIFY_STATUS_FAIL = 4;

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
