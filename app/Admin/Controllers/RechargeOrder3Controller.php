<?php

namespace App\Admin\Controllers;

use App\Models\RechargeOrder;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use App\Admin\Extensions\Exports\LargeCsvExporter;

// 引入刚才建的类

/**
 * Class RechargeOrder3Controller
 * @package App\Admin\Controllers
 */
class RechargeOrder3Controller extends AdminController
{
    protected function grid()
    {
        // 1. 准备 Model 对象
        $model = new \App\Models\RechargeOrder(); // 请确保命名空间正确
        $tableName = 'cd_order_250101';
        $model->setConnection('rds')->setTable($tableName);

        // 2. 使用 Grid::make() 工厂模式
        return Grid::make($model, function (Grid $grid) {

            // ======================================================
            // 【核心部分】
            // ======================================================

            // 1. 指定我们的自定义导出器
            $grid->exporter(new \App\Admin\Extensions\Exports\LargeCsvExporter('充值订单数据'));

            // 2. 【修正后的强制显示按钮代码】
            $grid->tools(function (\Dcat\Admin\Grid\Tools $tools) use ($grid) {
                $tools->append(new LargeCsvExporter($grid));
            });

            // ======================================================
            // 您原本的其他设置，保持不变
            // ======================================================
            $grid->disableBatchActions();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->disableEditButton();
            $grid->disableCreateButton();
            $grid->disableViewButton();

            // 您原本的查询逻辑
            if (isset($_GET['create_time']['start']) && ($_GET['create_time']['start'])) {
                $grid->model()->where('create_time', '>=', strtotime($_GET['create_time']['start']));
            }
            if (isset($_GET['create_time']['end']) && ($_GET['create_time']['end'])) {
                $grid->model()->where('create_time', '<=', strtotime($_GET['create_time']['end']));
            }

            $grid->model()
                ->select(['order_id', 'orderid', 'amount', 'account', 'bankname', 'create_time'])
                ->where('inizt', '=', \App\Models\RechargeOrder::INIZT_WITHDRAW)
                ->where('status', '=', \App\Models\RechargeOrder::STATUS_SUCCESS)
                ->orderBy('order_id', 'asc');

            // 您原本的列定义
            $grid->column('order_id', 'ID');
            $grid->column('orderid', '系统订单号');
            $grid->column('amount', '金额');
            $grid->column('account', '收款账号');
            $grid->column('bankname', '开户行');
            $grid->column('create_time', '添加时间')->display(function ($input) {
                return formatTimeToString($input);
            });

            // 您原本的过滤器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->between('order_id');
                $filter->whereBetween('create_time', function ($q) {
                    $start = $this->input['start'] ?? strtotime('today');
                    $end = $this->input['end'] ?? strtotime('today');
                    if ($start !== null) {
                        $q->where('create_time', '>=', strtotime($start));
                    }
                    if ($end !== null) {
                        $q->where('create_time', '<=', strtotime($end) + 3600 * 24);
                    }
                })->datetime();
            });
        });
    }


}
