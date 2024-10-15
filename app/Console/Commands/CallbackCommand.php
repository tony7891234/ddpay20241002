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
                $this->curlPostMax($response);

            });
    }


    private function curlPostMax($allGames)
    {

        //1 创建批处理cURL句柄
        $chHandle = curl_multi_init();
        $chArr = [];
        //2.创建多个cURL资源
        foreach ($allGames as $gameUrl) {
            $chArr[$gameUrl] = curl_init();
            curl_setopt($chArr[$gameUrl], CURLOPT_URL, $gameUrl);
            curl_setopt($chArr[$gameUrl], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($chArr[$gameUrl], CURLOPT_TIMEOUT, 10);
            curl_setopt($chArr[$gameUrl], CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($chArr[$gameUrl], CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_multi_add_handle($chHandle, $chArr[$gameUrl]); //2 增加句柄
        }

        $active = null;
        /**常量
         * CURLM_CALL_MULTI_PERFORM==-1
         * // CURLM_OK == 0
         **/

        do {
            $mrc = curl_multi_exec($chHandle, $active); //3 执行批处理句柄
        } while ($mrc == CURLM_CALL_MULTI_PERFORM); //4

//4 $active 为true，即$chHandle批处理之中还有$ch句柄正待处理，$mrc==CURLM_OK,即上一次$ch句柄的读取或写入已经执行完毕。
        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($chHandle) != CURLM_CALL_MULTI_PERFORM) {//$chHandle批处理中还有可执行的$ch句柄，curl_multi_select($chHandle) != -1程序退出阻塞状态。
                do {
                    $mrc = curl_multi_exec($chHandle, $active);//继续执行需要处理的$ch句柄。
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        foreach ($chArr as $k => $ch) {
            $result[$k] = curl_multi_getcontent($ch); //5 获取句柄的返回值,不需要
            curl_multi_remove_handle($chHandle, $ch);//6 将$chHandle中的句柄移除
//            dump($result[$k]);
        }
        curl_multi_close($chHandle); //7 关闭全部句柄
    }

}
