<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ZipStream\ZipStream;
use ZipStream\Option\Archive as ArchiveOptions;
use App\Models\RechargeOrder;

class ExportLargeCsvCommand extends Command
{
    /**
     * 命令行执行指令
     * 示例:  php artisan export:large-csv > export_log.txt 2>&1 &
     * 查看进度   tail -f export_log.txt
     */
    protected $signature = 'export:large-csv';

    protected $description = '导出海量订单数据到本地 ZIP 文件';

    public function handle()
    {
        // 0. 基础配置
        ini_set('memory_limit', '2048M'); // 适当增加内存
        set_time_limit(0); // 永不超时
        DB::connection('rds')->disableQueryLog(); // 关闭 SQL 日志

        // 定义导出参数
        $tableName = 'cd_order_250408';
        // 【修改】直接使用表名作为文件名
        $zipFilename = $tableName . '.zip';
        
        // 确保导出目录存在 (放到 public 目录下方便下载，生产环境注意安全)
        // 如果你希望私密一点，可以改为 storage_path('app/exports')
        $exportPath = public_path('exports'); 
        if (!is_dir($exportPath)) {
            mkdir($exportPath, 0755, true);
        }
        $fullPath = $exportPath . '/' . $zipFilename;

        $this->info("开始导出数据...");
        $this->info("目标表名: {$tableName}");
        $this->info("保存文件: {$fullPath}");
        $this->info("注意：2700w 数据可能需要运行较长时间，请耐心等待...");

        // 1. 配置 ZIP 输出到本地文件
        $opt = new ArchiveOptions();
        $opt->setSendHttpHeaders(false); // 关键：不发送 HTTP 头
        $opt->setEnableZip64(true); // 开启 Zip64 支持大文件
        $opt->setZeroHeader(true); 
        
        // 打开本地文件流
        $fileStream = fopen($fullPath, 'w');
        if ($fileStream === false) {
            $this->error("无法创建文件: {$fullPath}，请检查目录权限");
            return 1;
        }
        $opt->setOutputStream($fileStream);

        // 初始化 ZIP 对象
        $zip = new ZipStream($zipFilename, $opt);

        // 2. 定义变量
        // 【修改】100万行切分一个 CSV 文件
        $chunkSize = 1000000; 
        $headers = ['ID', '收款账号', '开户行'];
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);

        // 3. 初始化 CSV 临时流
        $tempStream = fopen('php://temp', 'w+');
        fwrite($tempStream, $bom);
        fputcsv($tempStream, $headers);

        // 4. 循环查询
        $lastId = null;
        $currentCount = 0;
        $fileIndex = 1;
        $totalExported = 0;

        // 进度条
        $bar = $this->output->createProgressBar();
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% %memory:6s%');
        
        $this->info("正在统计总行数...");
        $totalRows = DB::connection('rds')
            ->table($tableName)
            ->where('inizt', '=', RechargeOrder::INIZT_WITHDRAW)
            ->where('status', '=', RechargeOrder::STATUS_SUCCESS)
            ->count();
        
        $this->info("预计总行数: {$totalRows}");
        $bar->start($totalRows);

        do {
            // 构建查询
            $query = DB::connection('rds')
                ->table($tableName)
                ->select(['order_id', 'account', 'bankname'])
                // 导出全量
                ->where('inizt', '=', RechargeOrder::INIZT_WITHDRAW)
                ->where('status', '=', RechargeOrder::STATUS_SUCCESS);

            if ($lastId) {
                // ID 倒序游标
                $query->where('order_id', '<', $lastId);
            }

            // 每次取 5000 条
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
                    $record->account . "\t",
                    $record->bankname,
                ];

                fputcsv($tempStream, $row);
                $currentCount++;
                $totalExported++;
                $bar->advance();

                // 切分文件逻辑
                if ($currentCount >= $chunkSize) {
                    rewind($tempStream);
                    // 加入 ZIP
                    $zip->addFileFromStream("order_part_{$fileIndex}.csv", $tempStream);
                    
                    // 重置流
                    fclose($tempStream);
                    $tempStream = fopen('php://temp', 'w+');
                    fwrite($tempStream, $bom);
                    fputcsv($tempStream, $headers);

                    $currentCount = 0;
                    $fileIndex++;
                }
            }

            unset($records); // 释放内存

        } while (true);

        // 处理剩余数据
        if ($currentCount > 0) {
            rewind($tempStream);
            $zip->addFileFromStream("order_part_{$fileIndex}.csv", $tempStream);
        }
        fclose($tempStream);

        // 结束 ZIP 并关闭文件句柄
        $zip->finish();
        fclose($fileStream);
        
        $bar->finish();
        $this->newLine();
        $this->info("导出完成！");
        $this->info("文件已保存至: {$fullPath}");
        $this->info("下载链接: " . url("exports/{$zipFilename}"));

        return 0;
    }
}
