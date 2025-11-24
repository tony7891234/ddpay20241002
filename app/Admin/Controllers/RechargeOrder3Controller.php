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
        $model = new \App\Models\RechargeOrder();
        $tableName = 'cd_order_250101';
        $model->setConnection('rds')->setTable($tableName);

        $tableName = 'cd_order';
        $model->setConnection('home')->setTable($tableName);


        return Grid::make($model, function (Grid $grid) {

            // ======================================================
            // 【1. 修正导出配置】
            // ======================================================
            // 只要这一行就够了！不要用 $grid->tools(...)
            // 这行代码会自动替换系统默认的导出功能
            $grid->export(new LargeCsvExporter());

            // ======================================================
            // 【2. 关键设置】
            // ======================================================
            $grid->disableBatchActions();

            // !!! 注意 !!!
            // 如果你想支持“勾选导出”，下面这行必须注释掉或删掉！
            // 否则左边的复选框没了，用户没法选。
            // $grid->disableRowSelector();

            $grid->disableDeleteButton();
            $grid->disableEditButton();
            $grid->disableCreateButton();
            $grid->disableViewButton();

            // ======================================================
            // 【3. 数据查询逻辑】
            // ======================================================

            // 这里的固定条件（状态、初始化）会一直生效，导出时也会生效
            $grid->model()
                ->select(['order_id', 'orderid', 'amount', 'account', 'bankname', 'create_time'])
                ->where('inizt', '=', \App\Models\RechargeOrder::INIZT_WITHDRAW)
                ->where('status', '=', \App\Models\RechargeOrder::STATUS_SUCCESS)
                ->orderBy('order_id', 'asc');

            // ======================================================
            // 【4. 你的手动筛选逻辑】
            // ======================================================
            // 建议：尽量把逻辑写在下面的 $grid->filter 里，
            // 但保留这里的逻辑也可以，导出器里的 processFilter 会尝试兼容它
            if (isset($_GET['create_time']['start']) && ($_GET['create_time']['start'])) {
                $grid->model()->where('create_time', '>=', strtotime($_GET['create_time']['start']));
            }
            if (isset($_GET['create_time']['end']) && ($_GET['create_time']['end'])) {
                $grid->model()->where('create_time', '<=', strtotime($_GET['create_time']['end']));
            }

            // ======================================================
            // 【5. 列定义】
            // ======================================================
            $grid->column('order_id', 'ID');
            $grid->column('orderid', '系统订单号');
            $grid->column('amount', '金额');
            $grid->column('account', '收款账号');
            $grid->column('bankname', '开户行');
            $grid->column('create_time', '添加时间')->display(function ($input) {
                return formatTimeToString($input);
            });

            // ======================================================
            // 【6. 过滤器】
            // ======================================================
            $grid->filter(function (Grid\Filter $filter) {
                // 这里的设置非常重要，导出器会读取这里来判断用户搜了什么
                $filter->disableIdFilter(); // 关掉默认的主键搜索，如果你不需要

                $filter->equal('order_id', 'ID'); // 比如按ID搜

                $filter->whereBetween('create_time', function ($q) {
                    $start = $this->input['start'] ?? null;
                    $end = $this->input['end'] ?? null;
                    if ($start) {
                        $q->where('create_time', '>=', strtotime($start));
                    }
                    if ($end) {
                        // 结束时间通常要加一天或者到当天23:59:59
                        $q->where('create_time', '<=', strtotime($end) + 86399);
                    }
                })->datetime();
            });
        });
    }

}
