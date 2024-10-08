<?php

namespace App\Console\Commands;

use App\Models\RechargeOrder;
use Illuminate\Support\Facades\DB;

/**
 * Class OrderCommand
 * @package App\Console\Commands
 */
class OrderCommand extends BaseCommand
{

    const LIMIT_CHUNK = 2000; // 每次执行条数
    const LIMIT_TIMES = 80; // 每次执行多少次   一定要在执行频率内执行完毕
    const LIMIT_DELETE = 3000; // 每次删除条数

    /**
     * @var string
     */
    protected $signature = 'sync_order';


    /**
     * @var string
     */
    protected $description = '订单同步';

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
        //  每分钟执行一次  或者5分钟执行一次
        $this->minSyncChunk();
    }


    private function minSyncChunk()
    {
        // 1.获取订单字段
        $orderInfo = RechargeOrder::first();
        $model = new RechargeOrder();
        // 2.获取总表得最大 order_id
        $max = DB::connection('rds')->table('cd_order')->max('order_id');
        // 3.移动数据
        $processedCount = 0;
        $end_time = time() - 3600 * 24 * 2; // 保留3天
        RechargeOrder::select('*')
            ->where('order_id', '>', $max)
            ->where('create_time', '<', $end_time)
            ->orderBy('order_id')
            ->chunk(self::LIMIT_CHUNK, function ($list) use ($model, $orderInfo, $max, &$processedCount) {
                $processedCount++;
                if ($processedCount > self::LIMIT_TIMES) {
                    return false;
                }
                $list = $list->toArray();
                foreach ($list as &$item) {
                    foreach ($item as $key => $value) {
                        if ($value == '') {
                            $item[$key] = 0;
                        }
                    }
                }
                RechargeOrder::where('order_id', '<', $max)->limit(self::LIMIT_DELETE)->delete();
                $count = allUpdateOrAdd('rds', $model->getTable(), array_keys($orderInfo->getAttributes()), 'order_id', $list);
                dump($count);
                dump(getTimeString());
            });
    }

    /************************************** 以上是没分钟执行一次，以下是之数据太多了，执行得 *******************************************/
//
//    /**
//     * run
//     */
//    public function handle()
//    {
//        /**
//         *   处理数据多了  用这个方案
//         * for ($i = 1; $i <= 100; $i++) {
//         * try {
//         * $this->daySyncChunk();
//         * } catch (\Exception $exception) {
//         * var_dump($i);
//         * }
//         * var_dump($i);
//         * }
//         **/
//    }
//
//
//
////
////    /**
////     * 每天同步一次
////     */
//    private function daySyncChunk()
//    {
//        // 1.获取订单字段
//        $orderInfo = RechargeOrder::first();
//        $model = new RechargeOrder();
//        // 2.获取总表得最大 order_id
//        $max = DB::connection('rds')->table('cd_order')->max('order_id');
//        // 4.移动数据
//        $yy = 0;
//        $end_time = time() - 3600 * 24 * 3; // 保留3天
//        RechargeOrder::select('*')
//            ->where('order_id', '>', $max)
//            ->where('create_time', '<', $end_time)
//            ->orderBy('order_id')->chunk(1000, function ($list) use ($model, $orderInfo, $max, $yy) {
//                $yy++;
//                if ($yy > 10) {
//                    dump(1111);
//                    return true;
//                }
//                $list = $list->toArray();
//                foreach ($list as &$item) {
//                    foreach ($item as $key => $value) {
//                        if ($value == '') {
//                            $item[$key] = 0;
//                        }
//                    }
//                }
//                RechargeOrder::where('order_id', '<', $max)->limit(5000)->delete();
//                $count = allUpdateOrAdd('rds', $model->getTable(), array_keys($orderInfo->getAttributes()), 'order_id', $list);
//                dump($count);
//                dump(getTimeString());
////            die;
//            });
//    }


}

