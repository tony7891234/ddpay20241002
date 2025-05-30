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

    // action  方法
    const LIST_ACTION = [
        '银行回调成功' => '银行回调成功',
        '补发回调成功' => '补发回调成功',
        '手动回调成功' => '手动回调成功',
        '代付提交成功' => '代付提交成功',
        '银行付款成功' => '银行付款成功',
        '代付付款失败' => '代付付款失败',
        '手动代付成功' => '手动代付成功',
        '手动代付失败' => '手动代付失败',
        '管理员手动加款' => '管理员手动加款',
        '管理员手动扣款' => '管理员手动扣款',
        '下发提交打款' => '下发提交打款',
        '卡商-订单支付成功' => '卡商-订单支付成功',
        '下发打款失败' => '下发打款失败',
        '下发打款成功' => '下发打款成功',
        '上游回调成功' => '上游回调成功',
        '上游付款失败' => '上游付款失败',
        '预付扣款' => '预付扣款',
        '预付加款' => '预付加款',
        '冻结解除' => '冻结解除',
        '冻结扣款' => '冻结扣款',
    ];

}
