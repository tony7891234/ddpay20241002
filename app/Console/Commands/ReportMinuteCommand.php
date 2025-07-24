<?php

namespace App\Console\Commands;


use App\Models\RechargeOrder;
use DB;

/**
 * NotifyOrderCommand 失败3次的订单，直接去请求系统域名
 * Class NotifyToSiteCommand
 * @package App\Console\Commands
 */
class ReportMinuteCommand extends BaseCommand
{


    /**
     * @var string
     */
    protected $signature = 'report_minute';

    /**
     * @var string
     */
    protected $description = '分钟报表';


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
        $this->report();
        return true;
    }

    private function report()
    {
        $start_at = date('Y-m-d H:i:00', strtotime('-1 minute'));
        $end_at = $start_at + 60;
        $reportInfo = RechargeOrder::select(
            DB::raw('COUNT(id) as order_count'),
            DB::raw('SUM(amount) as sum_amount')
        )->where([
            ['create_time', '>=', $start_at],
            ['create_time', '<', $end_at],
        ])->find();
        dump($reportInfo);

        $reportInfo = RechargeOrder::select(
            DB::raw('COUNT(id) as order_count'),
            DB::raw('SUM(amount) as sum_amount')
        )->where([
            ['create_time', '>=', $start_at],
            ['create_time', '<', $end_at],
            ['status', '=', RechargeOrder::STATUS_SUCCESS],
        ])->find();
        dd($reportInfo);
    }


}

