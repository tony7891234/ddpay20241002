<?php

namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Collection;

class LargeCsvExporter extends AbstractExporter
{
    protected $filename = '充值订单导出.csv';

    public function export()
    {
        set_time_limit(0); // 防止超时
        ini_set('memory_limit', '512M');

        $headers = [
            'Content-Encoding'    => 'UTF-8',
            'Content-Type'        => 'text/csv;charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$this->filename}\"",
        ];

        foreach ($headers as $key => $value) {
            header("$key: $value");
        }

        $handle = fopen('php://output', 'w');
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM头，防乱码

        // 1. 设置表头
        fputcsv($handle, ['系统订单号', '订单金额', '收款账号', '开户行', '下单时间']);

        // 2. 只查询导出需要的字段 (性能优化)
        $query = $this->buildQuery()->select([
            'orderid', 'amount', 'account', 'bankname', 'create_time'
        ]);

        // 3. 核心优化：每次读 5000 条，写完释放内存
        $query->chunk(5000, function (Collection $chunk) use ($handle) {
            foreach ($chunk as $row) {
                fputcsv($handle, [
                    $row->orderid . "\t", // 加 \t 防止Excel把订单号变科学计数法
                    $row->amount,
                    $row->account . "\t",
                    $row->bankname,
                    date('Y-m-d H:i:s', $row->create_time) // 时间戳转格式
                ]);
            }
            if (ob_get_level() > 0) ob_flush();
            flush();
        });

        fclose($handle);
        exit;
    }
}
