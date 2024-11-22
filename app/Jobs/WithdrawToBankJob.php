<?php

namespace App\Jobs;

use App\Models\WithdrawOrder;
use App\Payment\HandelPayment;
use Carbon\Carbon;

/**
 * 出款到银行
 * Class WithdrawToBankJob
 * @package App\Jobs
 */
class WithdrawToBankJob extends BaseJob
{


    const CACHE_KEY = 'cache_WithdrawToBankJob_';
    const CACHE_TIME = 120; //缓存120s

    /**
     * @var WithdrawOrder
     */
    private $withdrawOrder;

    public function __construct($withdrawOrder)
    {
        $this->withdrawOrder = $withdrawOrder;
    }

    public function handle()
    {
        // 1。检查状态
        if (!$this->withdrawOrder->isRequestStatus()) {
            logToMe('WithdrawToBankJob', [
                'msg' => '不是待处理订单',
                'status' => $this->withdrawOrder->status,
                'id' => $this->withdrawOrder->getId(),
            ]);
            return true;
        }

        // 2检查 token
        if (\Cache::get(self::CACHE_KEY)) {
            $this->withdrawOrder->updateToRequestFail('', '', '2分钟后执行', WithdrawOrder::STATUS_REQUEST_AGAIN_JOB);
            // 再次执行这个 job
            $this->jobAgain();
            return true;
        }

        // 3。执行
        $service = new HandelPayment();
        $service = $service->setUpstreamId($this->withdrawOrder->upstream_id)->getUpstreamHandelClass();

        $response = $service->withdrawRequest($this->withdrawOrder);
        if ($response) {
            $this->withdrawOrder->updateToRequestSuccess($service->pix_info, $service->pix_out, $service->bank_order_id);
            return true;
        }

        if ($service->getErrorCode() == HandelPayment::ERROR_CODE_AGAIN_JOB) {
            $this->withdrawOrder->updateToRequestFail($service->pix_info, $service->pix_out, $service->getErrorMessage(), WithdrawOrder::STATUS_REQUEST_AGAIN_JOB);
            // 再次执行这个 job
            $this->jobAgain();
            \Cache::put(self::CACHE_KEY, self::CACHE_TIME);
        } else {
            $this->withdrawOrder->updateToRequestFail($service->pix_info, $service->pix_out, $service->getErrorMessage());
        }
        return true;
    }

    /**
     *  再次执行 job
     */
    private function jobAgain()
    {
        WithdrawToBankJob::dispatch($this->withdrawOrder)->delay(Carbon::now()->addMinutes(2)); // 添加队列
    }


}
