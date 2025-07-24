<?php

namespace App\Console\Commands;


use App\Models\RechargeOrder;
use App\Models\ReportMinute;
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
        $start_at = strtotime(date('Y-m-d H:i:00', strtotime('-1 minute')));
        dump(date('H:i:s', $start_at));
        $end_at = $start_at + 60;
        $arr = [
            'request_count' => 0,
            'request_amount' => 0,
            'finished_count' => 0,
            'finished_amount' => 0,
            'start_at' => $start_at,
            'end_at' => $end_at,
            'created_at' => time(),
        ];
        $requestOrder = RechargeOrder::select(
            DB::raw('COUNT(order_id) as order_count'),
            DB::raw('SUM(amount) as sum_amount')
        )->where([
            ['create_time', '>=', $start_at],
            ['create_time', '<', $end_at],
        ])->first();
        if ($requestOrder) {
            $arr['request_count'] = $requestOrder->order_count;
            $arr['request_amount'] = $requestOrder->sum_amount;
        }
        dump($requestOrder);

        $finishedOrder = RechargeOrder::select(
            DB::raw('COUNT(order_id) as order_count'),
            DB::raw('SUM(amount) as sum_amount')
        )->where([
            ['create_time', '>=', $start_at],
            ['create_time', '<', $end_at],
            ['status', '=', RechargeOrder::STATUS_SUCCESS],
        ])->first();
        if ($finishedOrder) {
            $arr['finished_count'] = $finishedOrder->order_count;
            $arr['finished_amount'] = $finishedOrder->sum_amount;
        }
        if ($arr['request_count'] == 0 && $arr['finished_count'] == 0) {
            echo '没单子';
        } else {
            ReportMinute::create($arr);
        }
    }


}

