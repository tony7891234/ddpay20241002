<?php

namespace App\Models;

/**
 * @property int id
 * @property string order_id
 * @property int updatetime
 * @property string callback
 * @property string time_me
 * Class HttpLogModel
 * @package App\Models
 */
class HttpLogModel extends BaseModel
{

    protected $connection = 'rds';
    protected $table = 'cd_request_2507';
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
