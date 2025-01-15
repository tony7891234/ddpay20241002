<?php

namespace App\Console\Commands;

use App\Models\MerchantModel;
use App\Models\MoneyLog;
use App\Models\RechargeOrder;
use App\Models\WithdrawOrder;
use App\Payment\HandelPayment;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Storage;

/**
 * Class BackCommand
 * @package App\Console\Commands
 */
class BackCommand extends BaseCommand
{

    use RepositoryTrait;


    const MAX_NOTIFY_NUM = 2; // 最大回掉次数
    /**
     * @var string
     */
    protected $signature = 'backup';


    /**
     * @var string
     */
    protected $description = '备注的，用过的功能都挡在这里';

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
        $this->moneylog();
        return true;
    }

    private $withdrawOrder;


    // 1.11 号  查询某个条件中 有没有 moneylog 记录的订单，有是正常订单
    private function t2()
    {
        $start_at = strtotime(date('2025-01-09 18:54:00'));
        $end_at = strtotime(date('2025-01-09 18:59:00'));
        $list = RechargeOrder::select(['order_id', 'orderid', 'bank_open', 'sf_id', 'amount', 'merchantid', 'amount', 'completetime', 'create_time', 'inizt'])
            ->where('create_time', '>=', $start_at)  // 22：56
            ->where('create_time', '<=', $end_at)  // 22：56
            ->where('inizt', '=', RechargeOrder::INIZT_RECHARGE)  // 22：56
            ->where('status', '=', RechargeOrder::STATUS_SUCCESS)  // 22：56
            ->get();
        foreach ($list as $item) {
            $str = $item['orderid'] . '   ' . date('Y-m-d H:i:s', $item['create_time']) . '  ' . formatTimeToString($item['completetime']);
            $check = MoneyLog::where('adduser', '=', $item['orderid'])->first();
            if ($check) {
                logToResponse($str, '0111_yes.txt');
            } else {
                logToResponse($str, '0111_no.txt');
            }

        }
    }

    // 2.测试某一笔出款  拿到三方返回的数据
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


    /**
     * 1. 某种订单的遗漏，添加 moneylog 和余额
     */
    private function moneylog()
    {
        $arr = [];
        $true = $false = 0;

        $list = RechargeOrder::select(['order_id', 'orderid', 'bank_open', 'sf_id', 'amount', 'merchantid', 'create_time', 'inizt'])
            ->whereIn('sf_id', $arr)  // 22：56
            ->orderBy('order_id')
            ->get();

        foreach ($list as $key => $item) {
            $res = $this->addMoney($item);
            if ($res) {
                $true++;
                logToResponse($item['orderid'], '0109_no.txt');
            } else {
                logToResponse($item['orderid'], '0109_yes.txt');
                $false++;
            }
        }
        dump('true  笔数' . $true);
        dump('false 笔数' . $false);

    }

    private function addMoney($order_data)
    {
        $merchantid = $order_data['merchantid'];
        $recharge_amount = $order_data['amount'];
        $merchant = MerchantModel::where('merchant_id', $merchantid)->first();

        $orderid = $order_data['orderid'];
        $check = MoneyLog::where('adduser', '=', $orderid)->count();
        if ($check) {
            return false;
        }
        $real_amount = $recharge_amount * (1 - $merchant['QRBX'] / 1000);

        MerchantModel::where('merchant_id', $merchantid)
            ->increment('balance', $real_amount);

        $log = [
            'merchant_id' => $merchant['merchant_id'],
            'type' => 10,
            'bank_lx' => $order_data['bank_open'],
            'begin' => $merchant['balance'],
            'after' => $merchant['balance'] + $real_amount,
            'money' => $real_amount,
            'action' => '上游回调成功-1.9补单',
            'content' => $order_data['amount'] - $real_amount,
            'adduser' => $order_data['orderid'],
            'create_time' => time()
        ];
        MoneyLog::create($log);
        // log  记录
        logToResponse($orderid . '  ' . $recharge_amount . '  ' . $real_amount, '0109_log.txt');

        return true;
    }


    // 1.10 号   查询异常订单
    private function t1()
    {

        $end_at = strtotime(date('2025-01-09 19:30:00'));
        $start_at = $end_at - 3600 * 24 * 6;
        //  这些订单是  不应该成功  但是给成功了
        // 特点是   completetime 存在   status=''
        $list = RechargeOrder::select(['order_id', 'orderid', 'bank_open', 'sf_id', 'amount', 'merchantid', 'create_time', 'inizt'])
            ->where('sf_id', '=', '')  // 22：56
            ->where('completetime', '>', $start_at)  // 22：56
            ->where('completetime', '<', $end_at)  // 22：56
//            ->where('inizt', '=', RechargeOrder::INIZT_RECHARGE)  // 22：56
            ->where('status', '=', RechargeOrder::STATUS_SUCCESS)  // 22：56
            ->count();
//            ->update(['status' => RechargeOrder::STATUS_WAITING]);
        dump($list);

//        die;

        $list = RechargeOrder::select(['order_id', 'orderid', 'bank_open', 'sf_id', 'amount', 'merchantid', 'amount', 'completetime', 'create_time', 'inizt'])
            ->where('sf_id', '=', '')  // 22：56
            ->where('completetime', '>', $start_at)  // 22：56
            ->where('completetime', '<', $end_at)  // 22：56
//            ->where('inizt', '=', RechargeOrder::INIZT_RECHARGE)  // 22：56
            ->where('status', '=', RechargeOrder::STATUS_SUCCESS)  // 22：56
            ->get();


        foreach ($list as $item) {
            $orderid = $item['orderid'];
            $notify_num = $item['notify_num'];
            $notify_status = $item['notify_status'];
            $merchantid = $item['merchantid'];
            $amount = $item['amount'];

            $create_at = formatTimeToString($item['create_time']);
            $completetime = formatTimeToString($item['completetime']);
            $str = $orderid . '  ' . $merchantid . '  ' . $amount . '  ' . $create_at . '  ' . $completetime;
            if ($item['inizt'] == RechargeOrder::INIZT_RECHARGE) {
                logToResponse($str, $merchantid . '_5_recharge.txt');
            } else {
                logToResponse($str, $merchantid . '_4_withdraw.txt');

            }


        }
//            ->count();
//        dd($list);
//            ->orderBy('order_id')
//            ->get();

    }

}

