<?php

namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Collection;

class LargeCsvExporter extends AbstractExporter
{
    protected $filename = '充值订单导出';

    public function export()
    {
        // 1. 暴力清理缓冲区（防止 HTML 混入）
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 2. 基础设置
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $filename = $this->filename . '_' . date('Ymd_His') . '.csv';

        // 3. Header 设置
        header('Content-Encoding: UTF-8');
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $handle = fopen('php://output', 'w');
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM 头

        // 4. 【核心修复】手动获取表头，不调用报错的 titles() 方法
        $titles = [];
        // $this->grid 是父类自带的属性，可以直接用
        foreach ($this->grid->columns() as $column) {
            $titles[] = $column->getLabel();
        }
        fputcsv($handle, $titles);

        // 5. 数据处理
        $this->buildData(function (Collection $rows) use ($handle) {
            foreach ($rows as $row) {
                // 时间格式化（防止 formatTimeToString 报错）
                if (isset($row['create_time']) && is_numeric($row['create_time'])) {
                    $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
                }

                // 防止长数字变成科学计数法
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
        exit;
    }
}
