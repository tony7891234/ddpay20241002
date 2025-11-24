<?php

namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Database\Eloquent\Builder;

class LargeCsvExporter extends AbstractExporter
{
    // 导出的文件名
    protected $filename = '充值订单导出';

    public function export()
    {
        // 1. 清除之前的输出缓冲区，防止文件损坏或乱码
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 2. 设置内存和超时，防止大数据量导致脚本挂掉
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $filename = $this->filename . '_' . date('Ymd_His') . '.csv';

        // 3. 设置 HTTP 头，告诉浏览器这是一个 CSV 文件下载
        header('Content-Encoding: UTF-8');
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        // 打开 PHP 输出流
        $handle = fopen('php://output', 'w');

        // 写入 BOM 头，防止 Excel 打开中文乱码
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // 4. 生成表头
        $titles = [];
        $columnNames = [];

        foreach ($this->grid->columns() as $column) {
            // 跳过“操作”列和“复选框”列
            if ($column->getName() == '__actions__' || $column->getName() == '__row_selector__') {
                continue;
            }
            $titles[] = $column->getLabel();
            $columnNames[] = $column->getName();
        }
        fputcsv($handle, $titles);

        // ============================================================
        // 【核心逻辑】构建查询条件
        // ============================================================

        // A. 这一步至关重要：应用搜索框的筛选条件
        // 如果没有这行，你按日期筛选后导出，依然会导出全部数据
        $this->grid->processFilter(true);

        // B. 获取带有模型功能的查询构建器 (Eloquent Builder)
        // 替换掉了之前的 getQueryBuilder()，确保软删除等逻辑正常
        /** @var Builder $query */
        $query = $this->grid->model()->eloquent();

        // C. 处理“选中导出”逻辑
        // Dcat 在勾选导出时，会通过 URL 参数 _ids_ 传递 ID
        $ids = request('_ids_');
        if (!empty($ids)) {
            // 兼容字符串 "1,2,3" 和数组格式
            $ids = is_string($ids) ? explode(',', $ids) : (array)$ids;

            // 强制只查询选中的 ID
            $keyName = $query->getModel()->getKeyName();
            $query->whereIn($keyName, $ids);
        }

        // D. 默认按主键倒序，保证最新数据在最上面
        $query->latest($query->getModel()->getKeyName());

        // ============================================================
        // 5. 分块读取并写入数据
        // ============================================================

        // 每次从数据库取 2000 条，避免一次性把内存撑爆
        $query->chunk(2000, function ($rows) use ($handle, $columnNames) {
            foreach ($rows as $row) {
                $line = [];

                foreach ($columnNames as $name) {
                    // data_get 支持 'user.name' 这种关联字段取值
                    $value = data_get($row, $name);

                    // --- 数据格式化 ---

                    // 1. 时间戳转日期字符串
                    if (str_contains($name, 'time') && is_numeric($value)) {
                        $value = date('Y-m-d H:i:s', $value);
                    }

                    // 2. 防止 Excel 科学计数法 (针对长数字字段)
                    // 如果是纯数字且长度超过10位，前面加个制表符
                    if (is_numeric($value) && strlen((string)$value) > 10) {
                        $value = "\t" . $value;
                    }

                    $line[] = $value;
                }
                fputcsv($handle, $line);
            }
        });

        // 关闭流并退出
        fclose($handle);
        exit;
    }
}
