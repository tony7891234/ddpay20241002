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
        $start_at = '2024-10-16 00:48:53';
        $end_at = '2024-10-16 00:48:56';
        $merchant_id = 1;
        $start_at = strtotime(date($start_at));
        $end_at = strtotime(date($end_at));
        $query = RechargeOrder::where('create_time', '>=', $start_at)  // 22：56
        ->where('create_time', '<=', $end_at);
        if ($merchant_id) {
            $query = $query->where('merchant_id', '=', $merchant_id);
        }
        $count = $query->count();
        dd($count);
    }

}
