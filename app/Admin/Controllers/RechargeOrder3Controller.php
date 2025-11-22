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
        // 1. 先准备好你的 Model 对象，设置好连接和动态表名
        $model = new \App\Models\RechargeOrder(); // 请确保这里的命名空间正确
        $tableName = 'cd_order_250101';
        $model->setConnection('rds')->setTable($tableName);

        // 2. 使用 Grid::make() 工厂模式来构建表格
        return Grid::make($model, function (Grid $grid) {

            // 3. 把所有的配置都放到这个闭包函数里

            // ======================================================
            // 【核心修改】在这里使用我们的导出器
            // ======================================================
            $grid->exporter(new \App\Admin\Extensions\Exports\LargeCsvExporter('充值订单数据'));

            // ======================================================
            // 您原本的禁用按钮和多选逻辑，保持不变
            // ======================================================
            $grid->disableBatchActions();
            $grid->disableRowSelector();
            $grid->disableDeleteButton();
            $grid->disableEditButton();
            $grid->disableCreateButton();
            $grid->disableViewButton();

            // ======================================================
            // 您原本的筛选和查询逻辑，保持不变
            // ======================================================
            if (isset($_GET['create_time']['start']) && ($_GET['create_time']['start'])) {
                $grid->model()->where('create_time', '>=', strtotime($_GET['create_time']['start']));
            }
            if (isset($_GET['create_time']['end']) && ($_GET['create_time']['end'])) {
                $grid->model()->where('create_time', '<=', strtotime($_GET['create_time']['end']));
            }

            $grid->model()
                ->select([
                    'order_id',
                    'orderid',
                    'amount',
                    'account',
                    'bankname',
                    'create_time'
                ])
                ->where('inizt', '=', \App\Models\RechargeOrder::INIZT_WITHDRAW)
                ->where('status', '=', \App\Models\RechargeOrder::STATUS_SUCCESS)
                ->orderBy('order_id', 'asc');

            // ======================================================
            // 您原本的列定义，保持不变
            // ======================================================
            $grid->column('order_id', 'ID');
            $grid->column('orderid', '系统订单号');
            $grid->column('amount', '金额');
            $grid->column('account', '收款账号');
            $grid->column('bankname', '开户行');
            $grid->column('create_time', '添加时间')->display(function ($input) {
                // 请确保 formatTimeToString 是一个您可以全局调用的辅助函数
                return formatTimeToString($input);
            });

            // ======================================================
            // 您原本的过滤器，保持不变
            // ======================================================
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
