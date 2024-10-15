<?php

namespace App\Console\Commands;

use App\Models\RechargeOrder;

/**
 * Class Sync
 * @package App\Console\Commands
 */
class CallbackCommand extends BaseCommand
{


    /**
     * @var string
     */
    protected $signature = 'callback';


    /**
     * @var string
     */
    protected $description = '并发回调';

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

        $this->t2();

        return true;
    }

    private function t2()
    {
        date_default_timezone_set('PRC');
        $start_at = 1729010400;//  20.00
        $end_at = 1729011300;//  20.16


        RechargeOrder::select(['order_id', 'orderid', 'create_time', 'inizt'])
            ->where('create_time', '>=', $start_at)  // 22：56
            ->where('create_time', '<=', $end_at) //  22：53
            ->where('merchantid', '=', 452) //  22：53
            ->where('inizt', '=', 1) //  22：53
            ->orderBy('order_id')->chunk(350, function ($list) {
                $list = $list->toArray();
                $response = [];
                foreach ($list as $item) {
                    $order_id = $item['order_id'];
                    if ($item['inizt'] == 0) {
                        //  收
                        $url = 'https://hulinb.com/api/order/notify?order_id_index=' . $order_id;
                    } else {
                        $url = 'https://hulinb.com/api/df/notify?order_id_index=' . $order_id;
                    }
                    $response[] = $url;
                    dump($order_id . '   ' . $item['orderid'] . '  ' . date('Y-m-d H:i:s', $item['create_time']));
                }
                dump($response);
                curlManyRequest($response);

            });
    }


}
