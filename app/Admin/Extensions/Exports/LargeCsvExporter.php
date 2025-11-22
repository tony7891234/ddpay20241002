<?php

namespace App\Admin\Extensions\Exports;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Dcat\Admin\Grid\Exporters\AbstractExporter;

class LargeCsvExporter extends AbstractExporter
{
    public function export()
    {
        // 获取当前筛选后的查询对象
        /** @var Builder $query */
        $query = $this->getQuery();

        // 这里可以直接获取表名和连接
        $tableName = 'cd_order_250101';
        $query->getModel()->setTable($tableName);
        $query->getModel()->setConnection('rds');

        $filename = 'recharge_orders_' . date('Ymd_His') . '.csv';

        // 分批查询参数
        $batchSize = 5000; // 每批处理5000条，建议不要太大
        $lastId = 0;

        // 定义需要导出的字段
        $columns = [
            'order_id',
            'orderid',
            'amount',
            'account',
            'bankname',
            'create_time',
        ];

        return new StreamedResponse(function () use ($query, $columns, $batchSize, &$lastId) {
            set_time_limit(0);
            ini_set('memory_limit', '256M');

            // 输出 UTF-8 BOM
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');

            // 写入表头
            $header = ['ID', '系统订单号', '金额', '收款账号', '开户行', '添加时间'];
            fputcsv($handle, $header);

            do {
                // 构建本批次的查询
                $batchQuery = clone $query;
                if ($lastId > 0) {
                    $batchQuery->where('order_id', '>', $lastId);
                }
                $rows = $batchQuery->orderBy('order_id', 'asc')->limit($batchSize)->get($columns);

                if ($rows->isEmpty()) {
                    break;
                }

                foreach ($rows as $row) {
                    $lastId = $row->order_id;
                    fputcsv($handle, [
                        $row->order_id,
                        $row->orderid,
                        $row->amount,
                        $row->account,
                        $row->bankname,
                        date('Y-m-d H:i:s', $row->create_time),
                    ]);
                }

                // 强制刷新缓冲区
                ob_flush();
                flush();
            } while (count($rows) == $batchSize);

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
