<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantConfig extends Model
{
    protected $fillable = [
        'merchant_id', 'sign_key', 'callback_url', 'alert_email'
    ];

    public function callbacks()
    {
        return $this->hasMany(MerchantCallback::class, 'merchant_id', 'merchant_id');
    }
} 