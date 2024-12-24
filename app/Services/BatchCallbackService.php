<?php

namespace App\Services;

use App\Models\MerchantCallback;
use Illuminate\Support\Collection;

class BatchCallbackService
{
    protected $swooleServer;
    
    public function __construct(SwooleServer $swooleServer)
    {
        $this->swooleServer = $swooleServer;
    }
    
    public function processBatchCallbacks(array $callbacks)
    {
        $collection = collect($callbacks)->chunk(100);
        
        $collection->each(function ($chunk) {
            $this->processChunk($chunk);
        });
    }
    
    protected function processChunk(Collection $chunk)
    {
        $chunk->each(function ($callback) {
            $this->swooleServer->getServer()->task($callback);
        });
    }
    
    public function retryFailedCallbacks($merchantId = null, $limit = 100)
    {
        $query = MerchantCallback::where('status', MerchantCallback::STATUS_FAILED)
            ->where('retry_times', '<', 'max_retry_times');
            
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }
        
        $callbacks = $query->limit($limit)->get();
        
        $callbacks->each(function ($callback) {
            $this->swooleServer->getServer()->task([
                'merchant_id' => $callback->merchant_id,
                'callback_url' => $callback->callback_url,
                'data' => $callback->callback_data,
                'retry_times' => $callback->retry_times,
                'max_retry_times' => $callback->max_retry_times,
                'callback_id' => $callback->id
            ]);
        });
        
        return $callbacks->count();
    }
} 