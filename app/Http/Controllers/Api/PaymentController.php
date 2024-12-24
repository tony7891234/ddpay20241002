<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Jobs\PagstarJob;
use Carbon\Carbon;

/**
 * 充值处理
 * Class PaymentController
 * @package App\Http\Controllers\Api
 */
class PaymentController extends ApiController
{

    /**
     * pag 银行
     * @param $order_id int
     * @return array
     */
    public function Pagstar($order_id)
    {
        PagstarJob::dispatch($order_id)->delay(Carbon::now()->addMinutes(1)); // 添加队列

        return $this->success();
    }

}
