<?php

namespace App\Console\Commands;

use App\Services\BatchCallbackService;
use Illuminate\Console\Command;

class ProcessCallbacks extends Command
{
    protected $signature = 'callbacks:process 
        {--merchant=} 
        {--retry} 
        {--limit=100}';
        
    protected $description = '处理回调队列';
    
    protected $batchService;
    
    public function __construct(BatchCallbackService $batchService)
    {
        parent::__construct();
        $this->batchService = $batchService;
    }
    
    public function handle()
    {
        if ($this->option('retry')) {
            $count = $this->batchService->retryFailedCallbacks(
                $this->option('merchant'),
                $this->option('limit')
            );
            $this->info("重试了 {$count} 个失败的回调");
        }
    }
} 