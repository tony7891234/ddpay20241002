<?php

namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Facades\DB;

class LargeCsvExporter extends AbstractExporter
{
    protected $filename;

    public function __construct($filename = '代付')
    {
        $this->filename = $filename;
    }

    public function export()
    {
        // 0. 极限配置
        set_time_limit(0);
        ini_set('memory_limit', '1024M'); // 1000w数据建议给 1G 内存

        // 1. 清理缓冲区
        while (ob_get_level()) {
            ob_end_clean();
        }

        $table_name = 'cd_order_250101';

        // 2. 设置 HTTP 头
        $filename = $this->filename . '_' . $table_name . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        // 禁用 Nginx 缓冲 (非常重要，防止 Nginx 等待 PHP 执行完才吐数据)
        header('X-Accel-Buffering: no');
        header('Pragma: no-cache');
        header('Expires: 0');

        // 3. 打开输出流
        $handle = fopen('php://output', 'w');
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

        // 4. 写入表头
        $headers = ['ID', '系统订单号', '金额', '收款账号', '开户行', '添加时间'];
        fputcsv($handle, $headers);

        // 5. 【核心优化】使用 ID 游标循环，而不是普通的 chunk
        // 初始 lastId 设为极大值
        $lastId = null;
        $limit = 5000; // 每次取 5000 条

        do {
            // 构建查询
            $query = DB::connection('rds')
                ->table($table_name)
                ->select(['order_id', 'orderid', 'amount', 'account', 'bankname', 'create_time'])
                ->where('inizt', '=', \App\Models\RechargeOrder::INIZT_WITHDRAW)
                ->where('status', '=', \App\Models\RechargeOrder::STATUS_SUCCESS);

            // 如果有 lastId，说明不是第一页，利用主键索引快速定位
            if ($lastId) {
                $query->where('order_id', '<', $lastId);
            }

            // 必须按 ID 倒序，配合上面的 where < lastId
            $records = $query->orderBy('order_id', 'desc')
                ->limit($limit)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            // 遍历写入
            foreach ($records as $record) {
                // 记录当前的 ID，用于下一次循环
                $lastId = $record->order_id;

                // 时间格式化
                $createTime = $record->create_time;
                if (function_exists('formatTimeToString')) {
                    $createTime = formatTimeToString($createTime);
                } elseif (is_numeric($createTime)) {
                    $createTime = date('Y-m-d H:i:s', $createTime);
                }

                $row = [
                    $record->order_id,
                    $record->orderid . "\t",
                    $record->amount,
                    $record->account . "\t",
                    $record->bankname,
                    $createTime,
                ];

                fputcsv($handle, $row);
            }

            // 释放内存
            unset($records);

            // 实时输出给浏览器，防止超时
            flush();

        } while (true);

        fclose($handle);
        exit;
    }
}
