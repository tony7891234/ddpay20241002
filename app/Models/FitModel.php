<?php

namespace App\Models;

/**
 * 通道模型
 * Class FitModel
 * @package App\Models
 * @property int id
 * @property string EntryId
 * @property string create_date
 * @property string content
 * @property int create_at
 */
class FitModel extends BaseModel
{

    protected $connection = 'rds';
    protected $table = 'fit_data';
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
