<?php

namespace App\Repository;

use App\Models\WithdrawOrder;
use Carbon\Carbon;

/**
 * Class WithdrawOrderRepository
 * @package App\Repository
 */
class WithdrawOrderRepository extends BaseRepository
{

    const CACHE_TIME = 3600; // 缓存一个小时
    const CACHE_UPSTREAM = 'MERCHANT_ORDER_REPOSITORY_UPSTREAM'; // 支付通道前缀

    /**
     * 客户充值金额
     * @var array
     */
    private $request_info;

    /**
     * MerchantOrderRepository constructor.
     * @param WithdrawOrder $model
     */
    public function __construct(WithdrawOrder $model)
    {
        $this->model = $model;
    }


    /**
     * 获取本地订单号对应的数据
     * @param $local_order_id
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null|MerchantOrder|bool
     */
    public function getByLocalOrderId($local_order_id)
    {
        $response = $this->model->where('local_order_id', '=', $local_order_id)->first();
        if (!$response) {
            $this->errorCode = -1;
            $this->errorMessage = '订单不存在';
            return false;
        }
        return $response;
    }


}
