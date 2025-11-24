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
        // 0. 基础配置：无限时间，1G内存
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // 1. 清理所有输出缓冲区，防止多余空格导致文件损坏
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 2. 配置 ZIP 流式输出
        $opt = new ArchiveOptions();
        $opt->setSendHttpHeaders(true);
        $opt->setEnableZip64(true); // 开启 Zip64 支持大文件
        $opt->setContentType('application/octet-stream');

        // 设置直接输出到浏览器
        $opt->setOutputStream(fopen('php://output', 'w'));

        $zipName = $this->filename . '_' . date('Ymd_His') . '.zip';

        // 手动发送 Header，确保 Nginx 不缓存
        header("Content-Disposition: attachment; filename=\"$zipName\"");
        header('X-Accel-Buffering: no');
        header('Pragma: no-cache');

        // 初始化 ZIP 对象
        $zip = new ZipStream($zipName, $opt);

        // 3. 定义参数
        $table_name = 'cd_order_250101';
        $chunkSize = 500000; // 100万行切分一次
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
                // 时间处理
                $row = [
                    $record->order_id,
                    $record->account . "\t",
                    $record->bankname,
                ];

                fputcsv($tempStream, $row);
                $currentCount++;

                // 6. 切片判断：如果满了 100w 行
                if ($currentCount >= $chunkSize) {
                    // 指针回到流开头
                    rewind($tempStream);

                    // 将流加入 ZIP (文件名为 order_part_1.csv)
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

                    // 刷新输出，保持连接活跃
                    flush();
                }
            }

            unset($records); // 释放内存

        } while (true);

        // 7. 处理剩余数据 (最后一部分不足 100w 的)
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
