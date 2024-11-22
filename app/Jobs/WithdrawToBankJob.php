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


    const CACHE_KEY = 'cache_WithdrawToBankJob_3_';
    const CACHE_TIME = 60; // 单位是分钟

    /**
     * @var  WithdrawOrder
     */
    private $withdrawOrder;

    /**
     * WithdrawToBankJob constructor.
     * @param $withdrawOrder WithdrawOrder
     */
    public function __construct($withdrawOrder)
    {
        //  重新获取，状态
        $this->withdrawOrder = $this->getWithdrawOrderRepository()->getById($withdrawOrder->getId());
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
            dump('error--');

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
            dump($service->pix_info);
            dump('error');
            \Cache::put(self::CACHE_KEY, 1, self::CACHE_TIME);
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
        sleep(10);
    }


}
