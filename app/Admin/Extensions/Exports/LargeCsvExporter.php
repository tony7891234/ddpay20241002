<?php

namespace App\Admin\Extensions\Exports;

use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Illuminate\Support\Collection;

class LargeCsvExporter extends AbstractExporter
{
    public function __construct(string $fileName = null)
    {
        // 这里可以留空，或者调用父类构造函数
        parent::__construct($fileName);
    }

    /**
     * @param Collection $records
     *
     * @return mixed
     */
    public function export()
    {
        // 我们现在只做一件事：打印信息并停止程序
        // 如果您点击导出后，能看到这个白色背景的页面，就说明类加载成功了！
        dd('成功了！导出器类被正确加载和执行！');

        // 下面的真实导出逻辑我们先不执行
        // ...
    }
}
