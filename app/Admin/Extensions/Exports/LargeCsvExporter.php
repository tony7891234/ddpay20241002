<?php


namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Collection;

class LargeCsvExporter extends AbstractExporter
{
    // 定义导出的文件名
    protected $filename = '充值订单导出.csv';

    public function export()
    {
        // 1. 设置 PHP 配置，防止大文件超时
        set_time_limit(0);
        ini_set('memory_limit', '512M'); // 虽然是流式，给多点内存更保险

        $filename = $this->filename;

        // 2. 设置 HTTP 头，告诉浏览器这是一个 CSV 文件下载
        $headers = [
            'Content-Encoding' => 'UTF-8',
            'Content-Type' => 'text/csv;charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'public',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        foreach ($headers as $key => $value) {
            header("$key: $value");
        }

        // 3. 打开 PHP 输出流
        $handle = fopen('php://output', 'w');

        // 4. 写入 BOM 头，防止 Excel 打开中文乱码
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // 5. 写入表头 (CSV 第一行)
        $titles = [
            '系统订单号',
            '订单金额',
            '收款账号',
            '开户行',
            '下单时间',
        ];
        fputcsv($handle, $titles);

        // 6. 获取查询构建器 (复用 Grid 的筛选条件)
        // 这里只查询需要的字段，极大减少数据库压力
        $query = $this->buildQuery()->select([
            'orderid',
            'amount',
            'account',
            'bankname',
            'create_time'
        ]);

        // 7. 分块读取数据 (核心优化点：每次只处理 5000 条)
        $query->chunk(5000, function (Collection $chunk) use ($handle) {
            foreach ($chunk as $row) {
                // 处理每一行数据
                $data = [
                    $row->orderid . "\t", // 加上 \t 防止 Excel 把长数字变成科学计数法
                    $row->amount,
                    $row->account . "\t", // 同上，防止账号变成科学计数法
                    $row->bankname,
                    // 时间戳转日期
                    date('Y-m-d H:i:s', $row->create_time),
                ];

                // 写入 CSV
                fputcsv($handle, $data);
            }

            // 刷新输出缓冲区，把数据真正发给浏览器
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        });

        // 8. 关闭流并退出
        fclose($handle);
        exit;
    }
}
