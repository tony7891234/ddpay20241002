<?php

namespace App\Console\Commands;

use App\Jobs\WithdrawToBankJob;
use App\Models\MoneyLog;
use App\Models\PixModel;
use App\Models\RechargeOrder;
use App\Models\WithdrawOrder;
use App\Payment\HandelPayment;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\DB;
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
        $this->pix_create_20250726();
        return true;
    }

    /**
     * 把之前的订单，如果存在   $remark_err_message 报错的 记录在 pix 数据中
     */
    private function pix_create_20250726()
    {
//        die(111);

        // select   count(*)   from  cd_order   where   realname =  '响应内容有误：Invalid Pix Entry';
        $remark_err_message = '失败原因:Chave Pix não encontrada';
//        $list = DB::connection('rds')->table('cd_order')
        $list = DB::connection('rds')->table('cd_order_250718')
            ->field('account,orderid,realname')
            ->where('realname', '=', $remark_err_message)
            ->limit(2)
            ->select();

//        var_dump($list);die;
        foreach ($list as $key => $item) {
            echo $key;
            echo PHP_EOL;
            $Account = $item->account;
            $orderid = $item->orderid;
//            $realname = $item['realname'];
            dd($Account, $orderid);
            // 插入
            try {
                PixModel::create([
                    'account' => $Account,
                    'status' => PixModel::STATUS_INVALID_PIX_WRONG,
                    'remark' => PixModel::LIST_STATUS[PixModel::STATUS_INVALID_PIX_WRONG],
                    'content' => '查询订单记录:单号:' . $orderid,
                    'add_time' => time(),
                ]);
            } catch (\Exception $exception) {
            }

        }

    }
//https://test107.hulinb.com/admin999/recharge_order?order_id=&merchantid=&orderid=&account=&status=1&inizt=&bank_open=22&amount%5Bstart%5D=&amount%5Bend%5D=&create_time%5Bstart%5D=2025-02-17%2011%3A00%3A00&create_time%5Bend%5D=2025-02-23%2010%3A59%3A59
    //  查询某个条件中 有没有 moneylog 记录的订单，有是正常订单
    private function t2()
    {
        die;
        $start_at = strtotime(date('2025-02-17 11:00:00'));
        $end_at = strtotime(date('2025-02-23 10:59:59'));
//
//        $list = RechargeOrder::select(['order_id', 'orderid', 'bank_open', 'yh_bq', 'sf_id', 'amount', 'merchantid', 'amount', 'completetime', 'create_time', 'inizt'])
//            ->where('create_time', '>=', $start_at)  // 22：56
//            ->where('create_time', '<=', $end_at)  // 22：56
//            ->where('inizt', '=', RechargeOrder::INIZT_RECHARGE)  // 22：56
//            ->where('status', '=', RechargeOrder::STATUS_SUCCESS)
//            ->where('bank_open', '=', 22)
//            ->count();  // 22：56
//        dd($list);

        // 设置浏览器文件下载的响应头
        $filename = '225.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        // 打开 PHP 输出流
//        $output = fopen('php://output', 'w');
        $output = fopen($filename, 'w'); // 本地导出到根目录
        // 写入 CSV 表头
        fputcsv($output, ['订单id', '标签', '金额', '完成时间']);  // 根据你的字段调整

        $list = RechargeOrder::select(['order_id', 'orderid', 'bank_open', 'sf_id', 'yh_bq', 'amount', 'merchantid', 'amount', 'completetime', 'create_time', 'inizt'])
            ->where('create_time', '>=', $start_at)  // 22：56
            ->where('create_time', '<=', $end_at)  // 22：56
            ->where('inizt', '=', RechargeOrder::INIZT_RECHARGE)
            ->where('status', '=', RechargeOrder::STATUS_SUCCESS)
            ->where('bank_open', '=', 22)
            ->orderBy('order_id')->chunk(10000, function ($data) use ($output) {
                dump(getTimeString());

                foreach ($data as $item) {
//                    $str = $item['orderid'] . '   ' . $item['amount'] . '  ' . $item['yh_bq'] . '  ' . formatTimeToString($item['completetime']);
//                    logToResponse($str, '17_23.txt');
                    fputcsv($output, [$item['orderid'], $item['yh_bq'], $item['amount'], formatTimeToString($item['completetime'])]);
                }
            });

        fclose($output);
        return response()->download($filename);
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

