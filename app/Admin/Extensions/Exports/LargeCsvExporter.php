<?php

namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Collection;

class LargeCsvExporter extends AbstractExporter
{
    // 定义文件名
    protected $filename;

    public function __construct($filename = '导出数据')
    {
        $this->filename = $filename;
    }

    /**
     * 核心导出逻辑
     */
    public function export()
    {
        // 1. 设置文件名和响应头
        $filename = $this->filename . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Encoding'    => 'UTF-8',
            'Content-Type'        => 'text/csv;charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        // 2. 准备输出流
        $response = response()->stream(function () {
            $handle = fopen('php://output', 'w');

            // 添加 BOM 头，防止 Excel 打开中文乱码
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // 3. 获取 Grid 定义的列名（表头）
            // $this->titles() 是 Dcat 自动根据 Grid 里的 column 生成的
            $titles = $this->titles();
            fputcsv($handle, $titles);

            // 4. 获取查询构建器 (Query Builder)
            // 这里非常关键：它会自动带上你在 Controller 里写的 where 条件和 filter 筛选条件
            $query = $this->buildQuery();

            // 5. 分块读取数据 (每次读 1000 条，防止内存爆掉)
            $query->chunk(1000, function (Collection $records) use ($handle, $titles) {
                // 获取原始数据数组
                // 注意：这里拿到的是数据库原始字段，需要映射到 Grid 的列
                foreach ($records as $record) {
                    $row = [];

                    // 根据表头的 key (column name) 来提取数据
                    foreach ($titles as $columnName => $titleLabel) {
                        // 获取对应字段的值
                        // 如果你有关联模型（比如 user.name），这里需要自行处理逻辑
                        // 这里假设都是单表字段
                        $value = data_get($record, $columnName);

                        // 特殊处理：比如时间戳转字符串
                        if ($columnName == 'create_time' && is_numeric($value)) {
                            $value = date('Y-m-d H:i:s', $value);
                        }

                        // 特殊处理：状态映射 (示例)
                        // if ($columnName == 'status') {
                        //     $value = $value == 1 ? '成功' : '失败';
                        // }

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
     * 构建查询语句
     * 自动判断是“导出选中”还是“导出全部”
     */
    protected function buildQuery()
    {
        // 获取 Grid 的数据模型构建器
        // 这一步会继承 Controller 中 grid() 里写的所有 where 条件
        $query = $this->grid()->model()->getQueryBuilder();

        // 获取用户勾选的 ID 列表 (如果你开启了 rowSelector)
        $ids = $this->grid()->model()->getIds();

        if (!empty($ids)) {
            // 如果用户勾选了特定的行，只导出这些 ID
            // 获取主键名称，通常是 id 或 order_id
            $keyName = $this->grid()->model()->getKeyName();
            $query->whereIn($keyName, $ids);
        }

        // 这里的 orderBy 最好指定一下，防止分块数据乱序
        $query->orderBy($this->grid()->model()->getKeyName(), 'desc');

        return $query;
    }
}
