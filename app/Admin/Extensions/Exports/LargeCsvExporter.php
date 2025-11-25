<?php

namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Facades\DB;
use ZipStream\ZipStream;
use ZipStream\Option\Archive as ArchiveOptions;

class LargeCsvExporter extends AbstractExporter
{
    protected $filename;

    public function __construct($filename = '订单导出')
    {
        $this->filename = $filename;
    }

    public function export()
    {
        // 0. 基础配置
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // 【关键】关闭 Laravel 的 SQL 日志，防止内存溢出
        DB::connection('rds')->disableQueryLog();

        // 1. 清理所有输出缓冲区，防止多余空格/报错信息混入 ZIP 导致文件损坏
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 2. 配置 ZIP 流式输出
        $opt = new ArchiveOptions();
        // 【修改】关闭库自动发送 Header，由下面手动发送，避免冲突
        $opt->setSendHttpHeaders(false);
        $opt->setEnableZip64(true);
        $opt->setContentType('application/octet-stream');

        // 设置直接输出到浏览器
        $opt->setOutputStream(fopen('php://output', 'w'));
        $table_name = 'cd_order_250315'; // 表名字
        $zipName = $table_name . '.zip';
        // 【手动发送 Header】确保 Nginx 不缓存，浏览器能识别下载
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"$zipName\"");
        header('Content-Transfer-Encoding: binary');
        header('X-Accel-Buffering: no'); // Nginx 专用：禁用缓冲
        header('Pragma: no-cache');
        header('Expires: 0');

        // 初始化 ZIP 对象
        $zip = new ZipStream($zipName, $opt);

        // 3. 定义参数

        $chunkSize = 500000; // 50万行切分一次
        $headers = ['ID', '收款账号', '开户行'];
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF); // UTF-8 BOM

        // 4. 初始化游标和计数器
        $lastId = null;
        $currentCount = 0;
        $fileIndex = 1;

        // 创建临时流用于写入 CSV
        $tempStream = fopen('php://temp', 'w+');
        fwrite($tempStream, $bom);
        fputcsv($tempStream, $headers);

        // 5. 循环查询 (ID 游标法)
        do {
            $query = DB::connection('rds')
                ->table($table_name)
                ->select(['order_id', 'account', 'bankname'])
                ->where('order_id', '<', 235812841)
                ->where('order_id', '>=', 225812841)
                ->where('inizt', '=', \App\Models\RechargeOrder::INIZT_WITHDRAW)
                ->where('status', '=', \App\Models\RechargeOrder::STATUS_SUCCESS);

            // 游标定位
            if ($lastId) {
                $query->where('order_id', '<', $lastId);
            }

            // 必须倒序，每次取 5000
            $records = $query->orderBy('order_id', 'desc')
                ->limit(5000)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            foreach ($records as $record) {
                $lastId = $record->order_id;

                $row = [
                    $record->order_id,
                    $record->account . "\t", // 防止科学计数法
                    $record->bankname,
                ];

                fputcsv($tempStream, $row);
                $currentCount++;

                // 6. 切片判断
                if ($currentCount >= $chunkSize) {
                    rewind($tempStream); // 指针回到流开头

                    // 将流加入 ZIP
                    $zip->addFileFromStream("order_part_{$fileIndex}.csv", $tempStream);

                    // 关闭旧流，开启新流
                    fclose($tempStream);
                    $tempStream = fopen('php://temp', 'w+');

                    // 新文件写入 BOM 和表头
                    fwrite($tempStream, $bom);
                    fputcsv($tempStream, $headers);

                    // 重置
                    $currentCount = 0;
                    $fileIndex++;

                    // 刷新输出，防止浏览器超时
                    flush();
                }
            }

            unset($records); // 释放内存

        } while (true);

        // 7. 处理剩余数据
        if ($currentCount > 0) {
            rewind($tempStream);
            $zip->addFileFromStream("order_part_{$fileIndex}.csv", $tempStream);
        }

        fclose($tempStream);

        // 8. 结束 ZIP
        $zip->finish();
        exit;
    }
}
