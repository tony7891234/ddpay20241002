<?php

namespace App\Models;

/**
 *
 * 分钟报表
 * @property int id
 * @property int request_count 请求比数
 * @property int finished_count 完成比数
 * @property float request_amount 请求金额
 * @property float finished_amount 完成金额
 * @property int start_at 统计开始时间
 * @property int end_at 统计结束时间
 * @property int created_at 统计时间
 * @property int
 * Class ReportMinute
 * @package App\Models
 */
class ReportMinute extends BaseModel
{

    protected $connection = 'rds';
    protected $table = 'cd_report_minute';
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = ['id'];
    protected $hidden = [];


    /**
     * 自增ID
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


}
