<?php

namespace App\Services;

use App\Models\MerchantCallbackStat;
use Carbon\Carbon;

class CallbackStatService
{
    public function updateStats($merchantId, $success, $responseTime, $isRetry = false)
    {
        $date = Carbon::today();
        
        $stats = MerchantCallbackStat::firstOrNew([
            'merchant_id' => $merchantId,
            'date' => $date
        ]);
        
        if (!$stats->exists) {
            $stats->fill([
                'total_count' => 0,
                'success_count' => 0,
                'fail_count' => 0,
                'retry_count' => 0,
                'avg_response_time' => 0
            ]);
        }
        
        $stats->total_count++;
        if ($success) {
            $stats->success_count++;
        } else {
            $stats->fail_count++;
        }
        
        if ($isRetry) {
            $stats->retry_count++;
        }
        
        // 更新平均响应时间
        $stats->avg_response_time = (
            ($stats->avg_response_time * ($stats->total_count - 1)) + $responseTime
        ) / $stats->total_count;
        
        $stats->save();
    }
    
    public function getStatsByDateRange($merchantId, $startDate, $endDate)
    {
        return MerchantCallbackStat::where('merchant_id', $merchantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }
} 