<?php

namespace App\Models;

/**
 * 通道模型
 * Class Job
 * @package App\Models
 * @property int id
 * @property string queue 队列类型
 * @property string payload 队列内容
 * @property int attempts 尝试运行次数
 * @property int reserved_at 保留时间 //不知道
 * @property int created_at 添加时间
 * @property int available_at 允许时间 // 不知道
 */
class RechargeOrder extends BaseModel
{

    protected $connection = 'mysql';
    protected $table = 'cd_order';
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = ['id'];
    protected $hidden = [];


    //  数据格式
    protected $casts = [
        'payload' => 'json',
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

    /**
     * 自增ID
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


}
