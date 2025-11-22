<?php

// 命名空间已根据您的要求修改为 App\Admin\Extensions\Exports
namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

// 类名已根据您的要求修改为 LargeCsvExporter
class LargeCsvExporter extends AbstractExporter
{
    protected $fileName = 'large-data.csv';
    protected $headings = [];

    public function __construct($title = '')
    {
        if ($title) {
            $this->fileName = $title . '-' . date('YmdHis') . '.csv';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        $this->headings = $this->getHeadings();

        $response = new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');

            // 写入BOM头，解决Excel打开中文乱码问题
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // 写入CSV头部
            fputcsv($handle, $this->headings);

            // 使用 chunkById 分块查询数据，节省内存
            $this->chunk(function ($records) use ($handle) {
                $rows = $this->getRows($records);

                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $this->fileName . '"');

        Response::make($response)->send();

        exit;
    }

    protected function getHeadings(): array
    {
        $headings = [];
        foreach ($this->grid->visibleColumns() as $column) {
            $headings[] = $column->getLabel();
        }
        return $headings;
    }

    protected function getRows($records): array
    {
        $rows = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($this->grid->visibleColumns() as $column) {
                $row[] = $column->render($record);
            }
            $rows[] = $row;
        }
        return $rows;
    }
}
