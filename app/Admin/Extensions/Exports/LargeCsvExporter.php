<?php

namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Collection;

class LargeCsvExporter extends AbstractExporter
{
    protected $filename = '充值订单导出';

    public function export()
    {
        // 1. 清理缓冲区（防报错核心）
        if (ob_get_length()) {
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

        // 4. 写入表头 (ID, 系统订单号, 金额...)
        fputcsv($handle, $this->titles());

        // 5. 数据处理
        // buildData 会自动应用你在 Controller 里写的 model()->where(...) 条件
        $this->buildData(function (Collection $rows) use ($handle) {
            foreach ($rows as $row) {
                // $row 是一个数组，包含了 select 出来的字段

                // 🚨 特殊处理：时间格式化
                // 对应你 Controller 里的 display(function... formatTimeToString)
                if (isset($row['create_time'])) {
                    // 假设 formatTimeToString 是全局函数，如果不是请替换为 date()
                    // 如果 formatTimeToString 不可用，可以用下面这行代替：
                    $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
                }

                // 🚨 特殊处理：防止长数字（如订单号、银行卡）在 Excel 变成科学计数法
                // 在数字前面加一个制表符 "\t"
                if (isset($row['orderid'])) {
                    $row['orderid'] = "\t" . $row['orderid'];
                }
                if (isset($row['account'])) {
                    $row['account'] = "\t" . $row['account'];
                }

                // 写入 CSV
                fputcsv($handle, $row);
            }
        });

        fclose($handle);
        exit;
    }
}
