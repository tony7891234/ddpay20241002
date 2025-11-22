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

        // 4. 准备表头和对应的字段名
        $titles = [];
        $columnNames = [];

        foreach ($this->grid->columns() as $column) {
            $titles[] = $column->getLabel(); // 表头显示名称 (例如 "订单号")
            $columnNames[] = $column->getName(); // 数据库字段名称 (例如 "order_id")
        }

        // 写入表头
        fputcsv($handle, $titles);

        // 5. 【关键修复】强制构建 Grid，确保搜索条件生效
        $this->grid->build();

        // 6. 【关键修复】获取底层 Eloquent 查询构建器
        // 这样可以避开 Dcat 的所有中间层，直接查库
        $query = $this->grid->model()->getQueryBuilder();

        // 7. 分块处理数据
        $query->chunk(2000, function ($rows) use ($handle, $columnNames) {
            foreach ($rows as $row) {
                $line = [];

                // 按照表头的顺序，一个一个把数据填进去
                foreach ($columnNames as $name) {
                    // 使用 data_get 支持关联模型 (例如 user.name)
                    $value = data_get($row, $name);

                    // --- 数据清洗区域 ---

                    // 时间处理
                    if ($name == 'create_time' && is_numeric($value)) {
                        $value = date('Y-m-d H:i:s', $value);
                    }

                    // 订单号/账号防止科学计数法
                    if (in_array($name, ['orderid', 'account'])) {
                        $value = "\t" . $value;
                    }

                    $line[] = $value;
                }

                fputcsv($handle, $line);
            }
        });

        fclose($handle);
        exit;
    }
}
