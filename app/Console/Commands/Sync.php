<?php

namespace App\Console\Commands;

use App\Models\RechargeOrder;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * Class Sync
 * @package App\Console\Commands
 */
class Sync extends BaseCommand
{

    use HasHttpRequest;

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
        $this->daySync();
//        if ($type == 'user') {
//            $this->syncUser();
//        } elseif ($type == 'order') {
//            $this->syncOrder();
//        } else {

//        }
        return true;
    }


    /**
     * 每天同步一次
     */
    private function daySync()
    {
        $user = RechargeOrder::first();
        $model = new RechargeOrder();
        $list = RechargeOrder::limit(1)->get()->toArray();

        dd($list);
        $count = allUpdateOrAdd('rds', $model->getTable(), array_keys($user->getAttributes()), 'order_id');

        dd($count);
    }


//
//    /**
//     * 每天同步一次
//     */
//    private function daySync1()
//    {
//        RechargeOrder::select(['id', 'username', 'user_type', 'topAgentId'])->orderByDesc('id')->chunk(3000, function ($userList) {
//            $createData = $cheatData = [];
//            /**
//             * @var $userList User[]
//             */
//            foreach ($userList as $item) {
//                $createData[] = [
//                    'id' => $item->getId(),
//                    'username' => $item->username,
//                    'user_type' => $item->user_type,
//                ];
//
//                // 外挂多一个顶级ID   方便统计代理日报表
//                $cheatData[] = [
//                    'id' => $item->getId(),
//                    'username' => $item->username,
//                    'user_type' => $item->user_type,
//                    'topAgentId' => $item->topAgentId,
//                ];
//            }
//            allUpdateOrAdd('tencent', 'user', ['id', 'username', 'user_type'], 'id', $createData);
////            $count = allUpdateOrAdd($model->getConnectionName(), $model->getTable(), array_keys($user->getAttributes()), 'id', $arr);
//
//        });
//    }


}
