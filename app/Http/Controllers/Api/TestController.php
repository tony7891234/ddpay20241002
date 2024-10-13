<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
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
        return $this->error('订单不存在');
    }

}
