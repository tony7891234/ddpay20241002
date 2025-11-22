<?php

namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Database\Eloquent\Builder;

class LargeCsvExporter extends AbstractExporter
{
    protected $filename = '充值订单导出';

    public function export()
    {
        // 1. 清理缓冲区
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 2. 基础设置
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $filename = $this->filename . '_' . date('Ymd_His') . '.csv';

        header('Content-Encoding: UTF-8');
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $handle = fopen('php://output', 'w');
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // 3. 准备表头
        $titles = [];
        $columnNames = [];
        foreach ($this->grid->columns() as $column) {
            if ($column->getName() == '__actions__') continue;
            $titles[] = $column->getLabel();
            $columnNames[] = $column->getName();
        }
        fputcsv($handle, $titles);

        // ============================================================
        // 核心修复区域
        // ============================================================

        // 1. 强制让 Grid 处理搜索框的筛选条件
        $this->grid->processFilter(true);

        // 2. 获取 Eloquent Builder (带模型功能的查询器)
        /** @var Builder $query */
        $query = $this->grid->model()->eloquent();

        // 3. 【至关重要】处理“选中导出”
        // 如果你勾选了数据，URL里会有 _ids_，必须手动加 whereIn，否则查出来的可能是空的或者全部数据
        $ids = request('_ids_');
        if (!empty($ids)) {
            $ids = is_string($ids) ? explode(',', $ids) : $ids;
            $keyName = $query->getModel()->getKeyName(); // 获取主键名 (通常是 id)
            $query->whereIn($keyName, (array)$ids);
        }

        // 4. 默认倒序，保证最新数据在上面
        $query->latest($query->getModel()->getKeyName());

        // ============================================================

        // 5. 分块写入
        $query->chunk(2000, function ($rows) use ($handle, $columnNames) {
            foreach ($rows as $row) {
                $line = [];
                foreach ($columnNames as $name) {
                    // 使用 data_get 从模型中取值 (兼容关联模型)
                    $value = data_get($row, $name);

                    // 简单的格式化
                    if (str_contains($name, 'time') && is_numeric($value)) {
                        $value = date('Y-m-d H:i:s', $value);
                    }
                    // 防止科学计数法
                    if (is_numeric($value) && strlen((string)$value) > 10) {
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
