<?php

namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Collection;

class LargeCsvExporter extends AbstractExporter
{
    protected $filename;

    public function __construct($filename = '导出数据')
    {
        $this->filename = $filename;
    }

    public function export()
    {
        // 文件名带上时间戳
        $filename = $this->filename . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Encoding'    => 'UTF-8',
            'Content-Type'        => 'text/csv;charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $response = response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // 写入 BOM 头，防止 Excel 打开中文乱码
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // ------------------------------------------------------------
            // 1. 获取表头 (使用最稳妥的遍历方式，避开 titles() 报错)
            // ------------------------------------------------------------
            $titles = [];
            // 获取 Grid 中定义的所有列对象
            $columns = $this->grid()->columns();

            foreach ($columns as $column) {
                $name = $column->getName(); // 字段名 (如 id, amount)
                $label = $column->getLabel(); // 显示名 (如 ID, 金额)

                // 排除不需要导出的系统列
                if (in_array($name, ['__row_selector__', 'actions', 'action'])) {
                    continue;
                }

                // 建立 字段名 => 显示名 的映射
                $titles[$name] = $label;
            }

            // 写入第一行：表头
            fputcsv($handle, $titles);

            // ------------------------------------------------------------
            // 2. 构建查询并分块写入数据
            // ------------------------------------------------------------
            $query = $this->buildQuery();

            // 每次处理 1000 条，防止内存溢出
            $query->chunk(1000, function (Collection $records) use ($handle, $titles) {
                foreach ($records as $record) {
                    $row = [];

                    // 严格按照表头的顺序填充数据
                    foreach ($titles as $columnName => $label) {
                        // data_get 支持数组和对象，也支持 'user.name' 这种关联写法
                        $value = data_get($record, $columnName);

                        // --- 这里可以做一些通用的格式化 ---

                        // 例如：如果是 create_time 且是数字，转成日期字符串
                        if (($columnName == 'create_time' || $columnName == 'created_at') && is_numeric($value)) {
                            $value = date('Y-m-d H:i:s', $value);
                        }

                        $row[] = $value;
                    }
                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, 200, $headers);

        return $response->send();
    }

    /**
     * 构建数据库查询
     */
    protected function buildQuery()
    {
        // 1. 获取当前 Grid 的基础查询（带筛选条件）
        $query = $this->grid()->model()->getQueryBuilder();

        // 2. 检查是否有勾选特定的行
        $ids = $this->grid()->model()->getIds();

        // 注意：Dcat 中如果没有勾选，getIds() 返回空数组，此时导出全部
        if (!empty($ids)) {
            $keyName = $this->grid()->model()->getKeyName();
            $query->whereIn($keyName, $ids);
        }

        // 3. 加上排序，防止分块读取时数据乱序
        $query->orderBy($this->grid()->model()->getKeyName(), 'desc');

        return $query;
    }
}
