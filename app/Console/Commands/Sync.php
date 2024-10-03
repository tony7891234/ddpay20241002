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
    protected $signature = 'bx:sync {type?}';


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

        $type = $this->argument('type');
        $this->t2();
//        if ($type == 'user') {
//            $this->syncUser();
//        } elseif ($type == 'order') {
//            $this->syncOrder();
//        } else {

//        }
        return true;
    }

    private function t2()
    {
        //  SELECT COUNT(*) AS think_count FROM cd_order WHERE  inizt = '0'  AND completetime BETWEEN 1727712000 AND 1727971199;
        dump(getTimeString());
        $res = RechargeOrder::where('inizt', '=', 0)->where('completetime', '>=', 1727712000)->where('completetime', '<=', 1727971199)->count();
        dump($res);
        dump(getTimeString());

    }


    /**
     * 每天同步一次
     */
    private function daySync()
    {
        $user = RechargeOrder::first();
        $model = new RechargeOrder();
        $list = RechargeOrder::limit(1)->get()->toArray();
        foreach ($list as &$item) {
            foreach ($item as $key => $value) {
                if ($value == '') {
                    $item[$key] = 0;
                }
            }
        }

//        dd($list);
        $count = allUpdateOrAdd('rds', $model->getTable(), array_keys($user->getAttributes()), 'order_id', $list);

        dd($count);
    }


//
//    /**
//     * 每天同步一次
//     */
    private function daySync1()
    {
        $user = RechargeOrder::first();
        $model = new RechargeOrder();
        $max = DB::connection('rds')->table('cd_order')->max('order_id');
        RechargeOrder::select('*')->where('order_id', '>', $max)->orderBy('order_id')->chunk(4001, function ($list) use ($model, $user) {

            $list = $list->toArray();
            foreach ($list as &$item) {
                foreach ($item as $key => $value) {
                    if ($value == '') {
                        $item[$key] = 0;
                    }
                }
            }

            $count = allUpdateOrAdd('rds', $model->getTable(), array_keys($user->getAttributes()), 'order_id', $list);
            dump($count);
            dump(getTimeString());
        });
    }


}
