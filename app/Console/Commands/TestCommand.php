<?php

namespace App\Console\Commands;

use App\Jobs\WithdrawToBankJob;
use App\Models\WithdrawOrder;
use App\Payment\HandelPayment;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Storage;

/**
 * 回掉异常订单
 * Class NotifyOrderCommand
 * @package App\Console\Commands
 */
class TestCommand extends BaseCommand
{

    use RepositoryTrait;


    const MAX_NOTIFY_NUM = 2; // 最大回掉次数
    /**
     * @var string
     */
    protected $signature = 'test';


    /**
     * @var string
     */
    protected $description = '2.回调异常订单';

    private $count_order = 0; // 总条数

    private $start_at = 0;
    private $curl_start = 0;
    private $sql_finished = 0;
    private $end_at = 0;

    /**
     * KG_Init constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * run
     */
    public function handle()
    {
        $this->vol();
        return true;
    }

    private $withdrawOrder;

    private function vol()
    {
        $this->withdrawOrder = $this->getWithdrawOrderRepository()->getByLocalId(115961);
        // 3。执行
        $service = new HandelPayment();
        $service = $service->setUpstreamId($this->withdrawOrder->upstream_id)->getUpstreamHandelClass();

        $response = $service->withdrawRequest($this->withdrawOrder);
        if ($response) {
            dump($response);
        } else {
            dump($service->getErrorMessage());
        }
    }


}

