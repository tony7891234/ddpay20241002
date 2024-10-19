<?php

namespace App\Console\Commands;

use App\Models\NotifyOrder;
use App\Traits\RepositoryTrait;

/**
 * NotifyOrderCommand 失败3次的订单，直接去请求系统域名
 * Class NotifyToSiteCommand
 * @package App\Console\Commands
 */
class NotifyToSiteCommand extends BaseCommand
{

    use RepositoryTrait;


    /**
     * @var string
     */
    protected $signature = 'notify_site';

    /**
     * @var string
     */
    protected $description = '3.异常处理失败，回掉到站点';


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
        dump('restart ' . (getTimeString()) . '  ');
        while (true) {
            $this->notify();
            sleep(5);
        }

        return true;
    }

    public function notify()
    {
        $current_time = time() - 3600; // 获取一小时内的订单即可
        /**
         * @var $list NotifyOrder[]
         */
        $list = NotifyOrder::select([
            'order_id',
        ])->where('notify_time', '>', $current_time)
            ->where('notify_num', '=', NotifyOrderCommand::MAX_NOTIFY_NUM + 1)
            ->orderBy('notify_time', 'asc')
            ->limit(500)
            ->get();

        $this->sql_finished = time(); // sql 结束时间
        // 商户ID列表
        $urlsWithParams = [];
        foreach ($list as $k => $notifyInfo) {
            $order_id = $notifyInfo->order_id;
            if ($notifyInfo->type == NotifyOrder::TYPE_RECHARGE) {
                //  收
                $notify_url = 'https://hulinb.com/api/order/notify?order_id_index=' . $order_id;
            } else {
                $notify_url = 'https://hulinb.com/api/df/notify?order_id_index=' . $order_id;
            }
            $urlsWithParams[] = $notify_url;
        }

        if ($urlsWithParams) {
            curlManyRequest($urlsWithParams);
        }

    }


}

