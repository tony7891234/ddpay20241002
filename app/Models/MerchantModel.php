<?php

namespace App\Models;

/**
 * 商户
 * @property  int merchant_id  商户ID
 * @property string secret  密钥
 * Class MerchantModel
 * @package App\Models
 */
class MerchantModel extends BaseModel
{

    protected $connection = 'home';
    protected $table = 'cd_merchant';
    protected $primaryKey = 'merchant_id';
    protected $fillable = [];
    protected $guarded = ['merchant_id'];
    protected $hidden = [];


    /**
     * 自增ID
     * @return int
     */
    public function getId()
    {
        return $this->merchant_id;
    }


}
