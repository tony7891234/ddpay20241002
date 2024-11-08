<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * cd_notify_order 得到的数据库
 * @property string orderid
 * @property string table_name
 * Class MapNotifyOrder
 * @package App\Models
 */
class MapNotifyOrder extends NotifyOrder
{

    protected $connection = 'rds';
    protected $table = 'cd_notify_order';
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = ['id'];
    protected $hidden = [];


    // 设置动态表名
//    public function getTable()
//    {
//        // 获取当前日期，并格式化为 Ymd
//        $date = Carbon::now()->format('md');
//        return 'cd_notify_order_' . $date; // 生成动态表名
//    }


    // 定义动态表名
    protected static $dynamicTable;

    // 设置动态表名
    public static function setDynamicTable($date)
    {
        self::$dynamicTable = 'order_' . $date; // 生成动态表名
    }

    /**
     * 重写 getTable 方法
     * @return string
     */
    public function getTable()
    {
        if (self::$dynamicTable) {
            return self::$dynamicTable;
        } else {
            $date = Carbon::now()->format('md');
            return 'cd_notify_order_' . $date; // 生成动态表名
        }
    }

    // 重写 create 方法
    public static function create(array $attributes = [])
    {
        if (is_null(self::$dynamicTable)) {
            throw new \Exception("Dynamic table name not set.");
        }

        return parent::create($attributes);
    }


}
