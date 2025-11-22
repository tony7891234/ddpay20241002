<?php

namespace App\Admin\Controllers;

use App\Models\RechargeOrder;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;
use App\Admin\Extensions\Exports\LargeCsvExporter; // 引入刚才创建的类

class RechargeOrder3Controller extends AdminController
{
    /**
     * 列表
     * @return Grid
     */
    protected function grid()
    {
        $model = new RechargeOrder();
        $tableName = 'cd_order_250101';
        $model->setConnection('rds')->setTable($tableName);

        $grid = new Grid($model);

        // =================================================
        // 核心修改：使用自定义的大数据 CSV 导出类
        // =================================================
        $grid->export(new LargeCsvExporter());

        // 如果你想保留禁用其他按钮的逻辑，写在这里
        // $grid->disableBatchActions();
        // ... 其他禁用代码 ...

        // =================================================
        // 下面是您的筛选和列表逻辑 (保持不变)
        // =================================================

        // 默认筛选条件
        $grid->model()
            ->where('inizt', '=', RechargeOrder::INIZT_WITHDRAW)
            ->where('status', '=', RechargeOrder::STATUS_SUCCESS)
            ->orderBy('order_id', 'asc');

        // 如果有自定义的 GET 参数筛选逻辑，建议尽量使用 Dcat 的 filter() 方法，
        // 但如果您必须保留原来手动处理 $_GET 的逻辑，请确保它作用在 $grid->model() 上
        // (您原来的代码这部分逻辑是没问题的，导出类会自动继承这些筛选)

        if (isset($_GET['create_time']['start']) && ($_GET['create_time']['start'])) {
            $grid->model()->where('create_time', '>=', strtotime($_GET['create_time']['start']));
        }
        if (isset($_GET['create_time']['end']) && ($_GET['create_time']['end'])) {
            $grid->model()->where('create_time', '<=', strtotime($_GET['create_time']['end']));
        }

        // 列表显示的列
        $grid->column('order_id', 'ID');
        $grid->column('orderid', '系统订单号');
        $grid->column('amount', '金额');
        $grid->column('account', '收款账号');
        $grid->column('bankname', '开户行');
        $grid->column('create_time', '添加时间')->display(function ($input) {
            return formatTimeToString($input);
        });

        // 过滤器
        $grid->filter(function (Grid\Filter $filter) {
            $filter->disableIdFilter(); // 通常不需要默认的ID查询
            $filter->between('order_id');

            // 您的时间筛选逻辑
            $filter->whereBetween('create_time', function ($q) {
                $start = $this->input['start'] ?? null;
                $end = $this->input['end'] ?? null;
                if ($start) {
                    $q->where('create_time', '>=', strtotime($start));
                }
                if ($end) {
                    $q->where('create_time', '<=', strtotime($end) + 3600 * 24);
                }
            })->datetime();
        });

        return $grid;
    }
}
