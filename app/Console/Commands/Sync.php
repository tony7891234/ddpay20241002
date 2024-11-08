<?php

namespace App\Console\Commands;

use App\Models\RechargeOrder;
use Illuminate\Support\Facades\DB;

/**
 * Class Sync
 * @package App\Console\Commands
 */
class Sync extends BaseCommand
{


    /**
     * @var string
     */
    protected $signature = 'sync';


    /**
     * @var string
     */
    protected $description = '同步数据(只更新昨天和今天的数据)';

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


        for ($i = 1; $i <= 100; $i++) {
            try {
                $this->daySyncChunk();
            } catch (\Exception $exception) {
                var_dump($i);
            }
            var_dump($i);
        }
        return true;
    }



//
//    /**
//     * 每天同步一次
//     */
    private function daySyncChunk()
    {
        die;
        // 1.获取订单字段
        $orderInfo = RechargeOrder::first();
        $model = new RechargeOrder();
        // 2.获取总表得最大 order_id
        $max = DB::connection('rds')->table('cd_order')->max('order_id');

        // 3. 删除本地之前得订单


        // 4.移动数据
        $yy = 0;
        $end_time = time() - 3600 * 24 * 3; // 保留3天
        RechargeOrder::select('*')
            ->where('order_id', '>', $max)
            ->where('create_time', '<', $end_time)
            ->orderBy('order_id')->chunk(1000, function ($list) use ($model, $orderInfo, $max, $yy) {
                $yy++;
                if ($yy > 10) {
                    dump(1111);
                    return true;
                }
                $list = $list->toArray();
                foreach ($list as &$item) {
                    foreach ($item as $key => $value) {
                        if ($value == '') {
                            $item[$key] = 0;
                        }
                    }
                }
                RechargeOrder::where('order_id', '<', $max)->limit(5000)->delete();
                $count = allUpdateOrAdd('rds', $model->getTable(), array_keys($orderInfo->getAttributes()), 'order_id', $list);
                dump($count);
                dump(getTimeString());
//            die;
            });
    }


}

