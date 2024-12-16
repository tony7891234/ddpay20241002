<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\RechargeOrder;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * Class TestController
 * @package App\Http\Controllers\Api
 */
class TestController extends ApiController
{
    use HasHttpRequest;
    const INDIA_TG = -1002172689012; // 印度tg


    public function t3()
    {
        /**
         * @var $repository \App\Repository\TelegramRepository
         */
        $repository = app('App\Repository\TelegramRepository');
        $response_text = 111;
        $repository->replayMessage(self::INDIA_TG, $response_text);
    }
//
//    public function test()
//    {
//        date_default_timezone_set('PRC');
//
//        $start_at = '2024-10-16 00:48:50';
//        $end_at = '2024-10-16 00:50:56';
//        $merchant_id = 1;
//        $start_at = strtotime(date($start_at));
//        $end_at = strtotime(date($end_at));
//        $count = RechargeOrder::where('create_time', '>=', $start_at)  // 22：56
//        ->where('create_time', '<=', $end_at)
//        ->count();
//        $sql = \Debugbar::getData()['queries'];
//        dump($sql);
//        var_dump($start_at);
//        var_dump($end_at);
//        dd($count);
//    }

    public function back1()
    {
        $info = file_get_contents('php://input');

        $type = '---';
        if (json_decode($info)) {
            $type = 'file_get_content';
            $request = json_decode($info, 1);
        } else {
            $request = $_POST;
            $type = 'post';
        }


        $arr = [
            '$request' => $request,
            '$type' => $type,
        ];
        logToMe('back1', $arr);
    }

    public function back2()
    {
        $info = file_get_contents('php://input');

        $type = '---';
        if (json_decode($info)) {
            $type = 'file_get_content';
            $request = json_decode($info, 1);
        } else {
            $request = $_POST;
            $type = 'post';
        }


        $arr = [
            '$request' => $request,
            '$type' => $type,
        ];
        logToMe('back2', $arr);
    }

}
