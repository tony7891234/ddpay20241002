<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;

/**
 * 回掉处理
 * Class CallbackController
 * @package App\Http\Controllers\Api
 */
class CallbackController extends ApiController
{

    /**
     * 入款回调入口
     */
    public function deposit()
    {

        echo json_encode(paymentGateway::depositCallback());
    }

    /**
     * 出款回调入口
     */
    public function transfer()
    {
        echo json_encode(paymentGateway::transferCallback());
    }


}
