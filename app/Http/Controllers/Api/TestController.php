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


    public function test()
    {

        $res = 11;
        dd($res);
    }


}
