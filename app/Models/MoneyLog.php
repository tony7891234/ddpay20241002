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
class MoneyLog extends BaseModel
{

    protected $connection = 'home';
    protected $table = 'cd_moneylog';
    protected $primaryKey = 'moneylog_id';
    protected $fillable = [];
    protected $guarded = ['moneylog_id'];
    protected $hidden = [];


    //  数据格式
    protected $casts = [
        'payload' => 'json',
    ];


    /**
     * 自增ID
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    const PAY_LIST_ACTION = [
        1 => 'StarkBank',
        2 => 'AnSpace',
        3 => 'Epay',
        4 => 'Toppay',
        5 => 'TGG',
        7 => 'MAY',
        8 => 'E二类',
        9 => 'ENV',
        10 => 'FIT',
        11 => 'Celcoin',
        12 => 'Pagnovo',
    ];

}
