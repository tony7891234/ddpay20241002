<?php

namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Collection;

class LargeCsvExporter extends AbstractExporter
{
    protected $filename = '充值订单导出';

    public function export()
    {
        // 🚨 修复核心 1：暴力清除所有输出缓冲区
        // 这能防止 HTML 代码混入 CSV 文件
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 设置不超时和内存限制
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $filename = $this->filename . '_' . date('Ymd_His') . '.csv';

        // 发送 Header
        header('Content-Encoding: UTF-8');
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $handle = fopen('php://output', 'w');

        // 写入 BOM 头（防止 Excel 打开乱码）
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // 写入表头
        fputcsv($handle, $this->titles());

        // 处理数据
        $this->buildData(function (Collection $rows) use ($handle) {
            foreach ($rows as $row) {
                // 🚨 修复核心 2：使用原生 date() 函数
                // 防止 formatTimeToString 不存在导致报错，从而输出 HTML
                if (isset($row['create_time']) && is_numeric($row['create_time'])) {
                    $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
                }

                // 防止数字变成科学计数法
                if (isset($row['orderid'])) {
                    $row['orderid'] = "\t" . $row['orderid'];
                }
                if (isset($row['account'])) {
                    $row['account'] = "\t" . $row['account'];
                }

                fputcsv($handle, $row);
            }
        });

        fclose($handle);

        // 🚨 修复核心 3：强制终止脚本
        // 确保后面不会再有任何 HTML 输出
        exit;
    }
}
