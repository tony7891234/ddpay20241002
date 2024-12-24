<?php

namespace App\Services;

use App\Models\MerchantCallback;
use Swoole\Http\Server;
use Swoole\Server\Task;
use Carbon\Carbon;
use App\Models\MerchantConfig;
use App\Services\SignatureService;
use Illuminate\Support\Facades\Mail;
use App\Services\CallbackStatService;

class SwooleServer
{
    protected $server;
    protected $host;
    protected $port;
    protected $signatureService;
    protected $statService;
    
    public function __construct($host = '0.0.0.0', $port = 9501)
    {
        $this->host = $host;
        $this->port = $port;
        $this->server = new Server($host, $port);
        
        $this->server->set([
            'worker_num' => 4,        // 工作进程数
            'task_worker_num' => 8,   // 任务进程数
            'daemonize' => false,     // 是否守护进程化
            'max_request' => 1000,    // 每个工作进程最大处理请求数
            'task_enable_coroutine' => true, // 开启协程支持
        ]);
        
        $this->initCallbacks();
        $this->signatureService = new SignatureService();
        $this->statService = new CallbackStatService();
    }
    
    protected function initCallbacks()
    {
        $this->server->on('Start', [$this, 'onStart']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('Task', [$this, 'onTask']);
        $this->server->on('Finish', [$this, 'onFinish']);
    }
    
    public function onStart($server)
    {
        echo "Swoole Server is started at {$this->host}:{$this->port}\n";
    }
    
    public function onWorkerStart($server, $workerId)
    {
        // 在 Worker 进程启动时加载 Laravel 应用
        require_once base_path('bootstrap/app.php');
    }
    
    public function onTask($server, Task $task)
    {
        $data = $task->data;
        
        try {
            // 这里处理商户回调逻辑
            $result = $this->processCallback($data);
            $task->finish($result);
        } catch (\Exception $e) {
            \Log::error('Callback task error: ' . $e->getMessage());
            $task->finish(['error' => $e->getMessage()]);
        }
    }
    
    public function onFinish($server, $taskId, $data)
    {
        echo "Task {$taskId} finished\n";
    }
    
    protected function processCallback($data)
    {
        $startTime = microtime(true);
        
        try {
            // 解析回调数据
            $merchantId = $data['merchant_id'] ?? null;
            $callbackUrl = $data['callback_url'] ?? null;
            $callbackData = $data['data'] ?? [];
            $retryTimes = $data['retry_times'] ?? 0;
            $maxRetryTimes = $data['max_retry_times'] ?? 3;
            $callbackId = $data['callback_id'] ?? null;
            
            if (!$callbackUrl) {
                throw new \Exception('回调地址不能为空');
            }

            // 获取或创建回调记录
            $callback = $this->getOrCreateCallback($callbackId, $merchantId, $callbackUrl, $callbackData, $maxRetryTimes);
            
            // 获取商户配置
            $merchantConfig = MerchantConfig::where('merchant_id', $merchantId)->first();
            if (!$merchantConfig) {
                throw new \Exception('商户配置不存在');
            }
            
            // 生成签名
            $sign = $this->signatureService->generateSign($callbackData, $merchantConfig->sign_key);
            
            // 发送回调请求
            $response = curlPost($callbackUrl, $callbackData, true, [
                'X-Merchant-ID: ' . $merchantId,
                'X-Timestamp: ' . time(),
                'X-Nonce: ' . uniqid(),
                'X-Retry-Times: ' . $retryTimes,
                'X-Sign: ' . $sign
            ]);
            
            // 处理回调响应
            $success = isset($response['code']) && $response['code'] == 200;
            
            if ($success) {
                // 更新统计
                $this->statService->updateStats(
                    $merchantId,
                    true,
                    (microtime(true) - $startTime) * 1000,
                    $retryTimes > 0
                );
                
                // 回调成功
                $callback->update([
                    'status' => MerchantCallback::STATUS_SUCCESS,
                    'response' => $response
                ]);
                
                \Log::info('Callback success', [
                    'callback_id' => $callback->id,
                    'merchant_id' => $merchantId,
                    'response' => $response
                ]);
                
                return [
                    'status' => 'success',
                    'callback_id' => $callback->id,
                    'response' => $response
                ];
            }
            
            // 更新统计
            $this->statService->updateStats(
                $merchantId,
                false,
                (microtime(true) - $startTime) * 1000,
                $retryTimes > 0
            );
            
            // 回调失败，判断是否需要重试
            if ($callback->retry_times >= $callback->max_retry_times) {
                // 超过最大重试次数
                $callback->update([
                    'status' => MerchantCallback::STATUS_FAILED,
                    'response' => $response
                ]);
                
                throw new \Exception('超过最大重试次数');
            }
            
            // 计算下次重试时间（递增延迟）
            $nextRetryDelay = min(pow(2, $callback->retry_times) * 60, 3600); // 最大延迟1小时
            $nextRetryTime = Carbon::now()->addSeconds($nextRetryDelay);
            
            // 更新重试信息
            $callback->update([
                'retry_times' => $callback->retry_times + 1,
                'next_retry_time' => $nextRetryTime,
                'response' => $response
            ]);
            
            // 投递重试任务
            $this->server->task([
                'merchant_id' => $merchantId,
                'callback_url' => $callbackUrl,
                'data' => $callbackData,
                'retry_times' => $callback->retry_times,
                'max_retry_times' => $callback->max_retry_times,
                'callback_id' => $callback->id
            ], -1, $nextRetryDelay);
            
            return [
                'status' => 'retry',
                'callback_id' => $callback->id,
                'next_retry_time' => $nextRetryTime,
                'response' => $response
            ];
            
        } catch (\Exception $e) {
            // 更新统计
            $this->statService->updateStats(
                $merchantId ?? 'unknown',
                false,
                (microtime(true) - $startTime) * 1000,
                $retryTimes > 0
            );
            
            \Log::error('Callback processing error', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);
            
            if (isset($callback)) {
                $callback->update([
                    'status' => MerchantCallback::STATUS_FAILED,
                    'response' => ['error' => $e->getMessage()]
                ]);
            }
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    protected function getOrCreateCallback($callbackId, $merchantId, $callbackUrl, $callbackData, $maxRetryTimes)
    {
        if ($callbackId) {
            return MerchantCallback::findOrFail($callbackId);
        }
        
        return MerchantCallback::create([
            'merchant_id' => $merchantId,
            'callback_url' => $callbackUrl,
            'callback_data' => $callbackData,
            'max_retry_times' => $maxRetryTimes,
            'status' => MerchantCallback::STATUS_PENDING
        ]);
    }
    
    public function start()
    {
        $this->server->start();
    }
    
    protected function sendFailureAlert($merchantConfig, $callback)
    {
        if (!$merchantConfig->alert_email) {
            return;
        }
        
        try {
            Mail::send('emails.callback_failure', [
                'merchant_id' => $merchantConfig->merchant_id,
                'callback_url' => $callback->callback_url,
                'retry_times' => $callback->retry_times,
                'callback_data' => $callback->callback_data,
                'last_response' => $callback->response
            ], function($message) use ($merchantConfig) {
                $message->to($merchantConfig->alert_email)
                        ->subject('回调失败告警');
            });
        } catch (\Exception $e) {
            \Log::error('Send alert email failed: ' . $e->getMessage());
        }
    }
    
    protected function sendExceptionAlert($merchantConfig, $callback, $errorMessage)
    {
        if (!$merchantConfig->alert_email) {
            return;
        }
        
        try {
            Mail::send('emails.callback_exception', [
                'merchant_id' => $merchantConfig->merchant_id,
                'callback_url' => $callback->callback_url,
                'error_message' => $errorMessage,
                'callback_data' => $callback->callback_data
            ], function($message) use ($merchantConfig) {
                $message->to($merchantConfig->alert_email)
                        ->subject('回调异常告警');
            });
        } catch (\Exception $e) {
            \Log::error('Send alert email failed: ' . $e->getMessage());
        }
    }
} 