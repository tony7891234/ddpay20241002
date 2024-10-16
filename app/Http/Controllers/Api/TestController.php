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


    public function test()
    {
        date_default_timezone_set('PRC');

        $start_at = '2024-10-16 00:48:50';
        $end_at = '2024-10-16 00:50:56';
        $merchant_id = 1;
        $start_at = strtotime(date($start_at));
        $end_at = strtotime(date($end_at));
        $count = RechargeOrder::where('create_time', '>=', $start_at)  // 22：56
        ->where('create_time', '<=', $end_at)
        ->count();
        $sql = \Debugbar::getData()['queries'];
        dump($sql);
        var_dump($start_at);
        var_dump($end_at);
        dd($count);
    }

}
