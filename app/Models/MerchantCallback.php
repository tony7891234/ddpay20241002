<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantCallback extends Model
{
    protected $fillable = [
        'merchant_id', 'callback_url', 'callback_data', 
        'retry_times', 'max_retry_times', 'next_retry_time',
        'status', 'response'
    ];

    protected $casts = [
        'callback_data' => 'array',
        'next_retry_time' => 'datetime',
        'response' => 'array'
    ];

    const STATUS_PENDING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;
} 