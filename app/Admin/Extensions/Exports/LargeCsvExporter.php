<?php

namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Collection;

class LargeCsvExporter extends AbstractExporter
{
    protected $filename = '充值订单导出';

    public function export()
    {
        // 1. 暴力清理缓冲区
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 2. 基础配置
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $filename = $this->filename . '_' . date('Ymd_His') . '.csv';

        // 3. 发送 Header
        header('Content-Encoding: UTF-8');
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $handle = fopen('php://output', 'w');
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM 头

        // 4. 获取表头 (手动获取，不调用报错的 titles 方法)
        $titles = [];
        foreach ($this->grid->columns() as $column) {
            $titles[] = $column->getLabel();
        }
        fputcsv($handle, $titles);

        // 5. 【核心修复】直接使用 Grid 模型的 chunk 方法
        // 避开 AbstractExporter::buildData 的参数类型限制
        // 每次处理 2000 条，防止内存溢出
        $this->grid->model()->chunk(function (Collection $rows) use ($handle) {
            foreach ($rows as $row) {
                // 确保转为数组，因为 chunk 取出来可能是对象
                $row = $row->toArray();

                // --- 数据清洗逻辑 ---

                // 时间格式化
                if (isset($row['create_time']) && is_numeric($row['create_time'])) {
                    $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
                }

                // 防止 Excel 科学计数法
                if (isset($row['orderid'])) {
                    $row['orderid'] = "\t" . $row['orderid'];
                }
                if (isset($row['account'])) {
                    $row['account'] = "\t" . $row['account'];
                }

                // 写入 CSV
                fputcsv($handle, $row);
            }
        }, 2000);

        fclose($handle);
        exit;
    }
}
