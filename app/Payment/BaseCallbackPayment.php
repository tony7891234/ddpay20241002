<?php

namespace App\Payment;

use App\Models\HttpLogModel;
use App\Models\MerchantModel;
use App\Models\MoneyLog;
use App\Models\RechargeOrder;
use App\Service\NotifyService;
use Illuminate\Support\Facades\DB;

/**
 * 2025.08
 * 回掉相关的基类
 * Class BaseCallbackPayment
 * @package App\Payment
 */
class BaseCallbackPayment extends BasePayment
{


    /**
     * @var array  回掉数据
     */
    protected $notify_data = [];

    public function getNotifyData()
    {
        return $this->notify_data;
    }

    /**
     * @var RechargeOrder  订单
     */
    protected $order_info = '';

    public function getOrderInfo()
    {
        return $this->order_info;
    }

    /**
     * @var string  三方ID
     */
    protected $sf_id = '';

    public function getSfId()
    {
        return $this->sf_id;
    }

    /**
     * @var string  order.yh_bq  的值
     */
    protected $tag_id = '';

    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * @return string 输出给银行的值
     */
    public function getResponseSuccess()
    {
        return static::RESPONSE_SUCCESS;
    }

    /**
     * @return string BANK_OPEN 的值
     */
    public function getBankOpen()
    {
        return static::BANK_OPEN;
    }

    /**
     * 解析订单号
     * @param $orderNumber
     * @return string
     */
    protected function parseOrderNumber($orderNumber)
    {
        // 1___FY__123456
        $parts = explode('__FY__', $orderNumber);
        //var_dump($parts);die;
        if (count($parts) != 2) {
            return $orderNumber;
        }
        if ($parts[1] == '') {
            return $orderNumber;
        }
        return $parts[1];
    }

    /**
     * 记录日志
     * @param $orderId
     * @param $value
     */
    protected function redisCacheCallback($orderId, $value)
    {
        if (is_array($value)) {
            $value['time_me'] = date('Y-m-d H:i:s');// 3.15号添加记录时间
        }
        try {
            $res = HttpLogModel::where('order_id', '=', $orderId)->update(['updatetime' => time(), 'callback' => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

            if (!$res) {
                HttpLogModel::insert(['order_id' => $orderId, 'updatetime' => time(), 'callback' => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }

        } catch (\Exception $e) {

        }

    }
}
