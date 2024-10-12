<?php

namespace App\Console\Commands;

use App\Models\RechargeOrder;
use Illuminate\Support\Facades\DB;

/**
 * Class Sync
 * @package App\Console\Commands
 */
class TestCommand extends BaseCommand
{


    /**
     * @var string
     */
    protected $signature = 'test';


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

        $this->t2();

        return true;
    }

    private function t2()
    {
        date_default_timezone_set('PRC');
//        RechargeOrder::where('create_time')->get();
//        $url = 'https://hulinb.com/api/df/notify?order_id_index=';
//        file_get_contents($url);

        $start_at = 1728659040;//'23:04~';
        $end_at = 1728659400;//'23:09';
        $i = 0;
        RechargeOrder::select(['order_id', 'orderid', 'create_time'])
            ->where('order_id', '<=', 116797272)  // 22：56
            ->where('order_id', '>=', 116789285) //  22：53

//                       ->where('order_id', '<=', 116828561)  // 10
//            ->where('order_id', '>=', 116815278) // 04
//
//
            ->orderBy('order_id')->chunk(100, function ($list) use ($i) {

                $i++;
                $list = $list->toArray();
                $response = [];
                foreach ($list as $item) {
//                    dd($item);
                    $order_id = $item['order_id'];
//                    $url = 'https://hulinb.com/api/df/notify?order_id=' . $order_id;
                    $url = 'https://hulinb.com/api/df/notify?order_id_index=' . $order_id;
                    $response[] = $url;
//                    $res = $this->curlGetRequest($url);
//                    var_dump($res);
//                    dump($res);
//                    dump($url);
                    dump($order_id . '   ' . $item['orderid'] . '  ' . date('Y-m-d H:i:s', $item['create_time']));
                }
                dump($response);
                $this->curlPostMax($response);

            });
    }
//
//    private function t2()
//    {
//        date_default_timezone_set('PRC');
////        RechargeOrder::where('create_time')->get();
////        $url = 'https://hulinb.com/api/df/notify?order_id_index=';
////        file_get_contents($url);
//
//        $start_at = 1728659040;//'23:04~';
//        $end_at = 1728659400;//'23:09';
//        $i = 0;
//        RechargeOrder::select(['order_id', 'orderid', 'create_time'])
////            ->where('order_id', '=', 116820708)
////            ->where('order_id', '>=', 116820709)
//            ->where('create_time', '>=', $start_at)
//            ->where('create_time', '<=', $end_at)
//            ->whereIn('status', [RechargeOrder::STATUS_SUCCESS, RechargeOrder::STATUS_FAIL])
//            ->orderBy('order_id')->chunk(1000, function ($list) use ($i) {
//
//                $i++;
//                $list = $list->toArray();
//                foreach ($list as $item) {
////                    dd($item);
//                    $order_id = $item['order_id'];
////                    $url = 'https://hulinb.com/api/df/notify?order_id=' . $order_id;
//                    $url = 'https://hulinb.com/api/df/notify?order_id_index=' . $order_id;
//                    $res = $this->curlGetRequest($url);
//                    var_dump($res);
//                    dump($res);
//                    dump($url);
//                    dump($order_id . '   ' . $item['orderid'] . '  ' . date('Y-m-d H:i:s', $item['create_time']));
////                    logToResponse($str, 'aa.txt');
//                }
////                dump($count);
////                dump(getTimeString());
////            die;
//            });
//    }


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
            curl_setopt($chArr[$gameUrl], CURLOPT_TIMEOUT, 1);
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
//    $result[$k] = curl_multi_getcontent($ch); //5 获取句柄的返回值,不需要
            curl_multi_remove_handle($chHandle, $ch);//6 将$chHandle中的句柄移除
        }
        curl_multi_close($chHandle); //7 关闭全部句柄
    }

    private function curlGetRequest($url, $headers = [])
    {
        // 初始化 cURL 会话
        $ch = curl_init();

        // 设置 cURL 选项
        curl_setopt($ch, CURLOPT_URL, $url); // 设置请求的 URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 将响应结果返回而不是直接输出
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // 设置请求头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36',
            'Accept: application/json, text/plain, */*',
            'Accept-Language: en-US,en;q=0.9',
            'Connection: keep-alive'
        ]);
        // 执行 cURL 请求
        $response = curl_exec($ch);

        // 检查是否有错误
        if (curl_errno($ch)) {
            echo 'cURL Error: ' . curl_error($ch);
        }

        // 关闭 cURL 会话
        curl_close($ch);

        return $response; // 返回响应结果
    }

}
