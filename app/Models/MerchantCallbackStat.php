<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantCallbackStat extends Model
{
    protected $fillable = [
        'merchant_id', 'date', 'total_count', 'success_count',
        'fail_count', 'retry_count', 'avg_response_time'
    ];

    protected $casts = [
        'date' => 'date',
        'avg_response_time' => 'float'
    ];
} 