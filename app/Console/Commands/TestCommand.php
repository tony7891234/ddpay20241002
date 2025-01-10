<?php

namespace App\Console\Commands;

use App\Jobs\WithdrawToBankJob;
use App\Models\RechargeOrder;
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
        $this->t1();
        return true;
    }

    private $withdrawOrder;

    // 1.10 号   查询异常订单
    private function t1()
    {

        $end_at = strtotime(date('2025-01-09 19:30:00'));
        $start_at = $end_at - 3600 * 24 * 6;

        $end_at = time();
        //  这些订单是  不应该成功  但是给成功了
        // 特点是   completetime 存在   status=''
//        $list = RechargeOrder::select(['order_id', 'orderid', 'bank_open', 'sf_id', 'amount', 'merchantid', 'create_time', 'inizt'])
//            ->where('sf_id', '=', '')  // 22：56
//            ->where('completetime', '>', $start_at)  // 22：56
//            ->where('completetime', '<', $end_at)  // 22：56
//            ->where('inizt', '=', RechargeOrder::INIZT_RECHARGE)  // 22：56
//            ->where('status', '=', RechargeOrder::STATUS_SUCCESS)  // 22：56
////            ->count();
//            ->sum('amount');
//        dump($list);
//
//        die;

        $list = RechargeOrder::select(['order_id', 'orderid', 'bank_open', 'sf_id', 'amount', 'merchantid', 'amount', 'completetime', 'create_time', 'inizt'])
            ->where('sf_id', '=', '')  // 22：56
            ->where('completetime', '>', $start_at)  // 22：56
            ->where('completetime', '<', $end_at)  // 22：56
            ->where('inizt', '=', RechargeOrder::INIZT_RECHARGE)  // 22：56
            ->where('status', '=', RechargeOrder::STATUS_SUCCESS)  // 22：56
            ->get();


        $arr = [];
        foreach ($list as $item) {
            $orderid = $item['orderid'];
            $amount = $item['amount'];
            $notify_num = $item['notify_num'];
            $notify_status = $item['notify_status'];
            $merchantid = $item['merchantid'];
            $amount = $item['amount'];

            if (isset($arr[$merchantid])) {
                $arr[$merchantid]['amount'] = $arr[$merchantid]['amount'] + $amount;
                $arr[$merchantid]['num'] = $arr[$merchantid]['num'] + 1;
            } else {
                $arr[$merchantid]['amount'] = $amount;
                $arr[$merchantid]['num'] = 1;
            }

//            $create_at = formatTimeToString($item['create_time']);
//            $completetime = formatTimeToString($item['completetime']);
//            $str = $orderid . '  ' . $merchantid . '  ' . $amount . '  ' . $create_at . '  ' . $completetime;
//            if ($item['inizt'] == RechargeOrder::INIZT_RECHARGE) {
//                logToResponse($str, 'aaa_6_recharge.txt');
//            } else {
//                logToResponse($str, 'aaa_6_withdraw.txt');
//            }


        }
        foreach ($arr as $merchantid => $item) {
            $str = $merchantid . '  ' . $item['amount'] . '  ' . $item['num'];
            logToResponse($str, 'a22_0110.txt');
        }
        dump($arr);
//            ->count();
//        dd($list);
//            ->orderBy('order_id')
//            ->get();

    }

//    private function vol()
//    {
//        $this->withdrawOrder = $this->getWithdrawOrderRepository()->getByLocalId(115961);
//        // 3。执行
//        $service = new HandelPayment();
//        $service = $service->setUpstreamId($this->withdrawOrder->upstream_id)->getUpstreamHandelClass();
//
//        $response = $service->withdrawRequest($this->withdrawOrder);
//        if ($response) {
//            dump($response);
//        } else {
//            dump($service->getErrorMessage());
//        }
//    }


}

