<?php

namespace App\Repository;

use App\Models\WithdrawOrder;
use App\Payment\FitbankPayment;
use Carbon\Carbon;

/**
 * Class WithdrawOrderRepository
 * @package App\Repository
 */
class WithdrawOrderRepository extends BaseRepository
{


    /**
     * @param $bank_order_id
     * @return WithdrawOrder
     */
    public function getByBankOrderId($bank_order_id)
    {

        return WithdrawOrder::where('bank_order_id', '=', $bank_order_id)->first();
    }

    /**
     * @param $order_id
     * @return WithdrawOrder
     */
    public function getByBankId($order_id)
    {
        // fit订单号转换成 sql 的订单id
        $prefix = FitbankPayment::PREFIX_ORDER_ID;
        $order_id = substr($order_id, strlen($prefix));
        return WithdrawOrder::where('order_id', '=', $order_id)->first();
    }

    /**
     * 银行的订单号
     * @param $order_id
     * @return WithdrawOrder
     */
    public function getBySfId($order_id)
    {
        // fit订单号转换成 sql 的订单id
        return WithdrawOrder::where('sf_id', '=', $order_id)->first();
    }


    /**
     * @param $order_id
     * @return WithdrawOrder
     */
    public function getByLocalId($order_id)
    {
        return WithdrawOrder::where('order_id', '=', $order_id)->first();
    }


}
