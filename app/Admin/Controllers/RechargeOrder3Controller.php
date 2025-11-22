<?php

namespace App\Admin\Controllers;

use App\Models\RechargeOrder;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use App\Admin\Extensions\Exports\LargeCsvExporter; // 引入刚才建的类

/**
 * Class RechargeOrder3Controller
 * @package App\Admin\Controllers
 */
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

        /**
         * @var $grid Grid
         */
        $grid = new Grid($model);

        // ======================================================
        // 【修改点】这里改成了使用自定义 CSV 导出类
        // 原来的 export()->titles()->rows() 写法处理不了100万条
        // ======================================================
        $grid->exporter(new LargeCsvExporter('充值订单数据'));
        // ======================================================
        // 【保留】以下是你原本的禁用按钮和多选逻辑，完全没动
        // ======================================================

        // 禁用批量操作 (多选框)
        $grid->disableBatchActions();
        $grid->disableRowSelector();

        // 禁用删除按钮
        $grid->disableDeleteButton();
        // 禁用编辑按钮
        $grid->disableEditButton();
        // 隐藏 创建按钮
        $grid->disableCreateButton();
        // 禁用 查看按钮
        $grid->disableViewButton();

        // ======================================================
        // 【保留】以下是你原本的筛选和查询逻辑
        // ======================================================

        if (isset($_GET['create_time']['start']) && ($_GET['create_time']['start'])) {
            $grid->model()
                ->where([
                    ['create_time', '>=', strtotime($_GET['create_time']['start'])],
                ]);
        }

        if (isset($_GET['create_time']['end']) && ($_GET['create_time']['end'])) {
            $grid->model()
                ->where([
                    ['create_time', '<=', strtotime($_GET['create_time']['end'])],
                ]);
        }

        //  搜索条件
        $grid->model()
            ->select([
                'order_id',     // 列表显示用到
                'orderid',      // 列表和导出用到
                'amount',       // 列表和导出用到
                'account',      // 列表和导出用到
                'bankname',     // 列表和导出用到
                'create_time'   // 列表和导出用到
            ])
            ->where('inizt', '=', RechargeOrder::INIZT_WITHDRAW)
            ->where('status', '=', RechargeOrder::STATUS_SUCCESS)
            ->orderBy('order_id', 'asc'); // 按照ID 倒序排序

        $grid->column('order_id', 'ID');
        $grid->column('orderid', '系统订单号'); // 直接对此字段查询
        $grid->column('amount', '金额');
        $grid->column('account', '收款账号');
        $grid->column('bankname', '开户行');
        $grid->column('create_time', '添加时间')->display(function ($input) {
            return formatTimeToString($input);
        });

        // 过滤器  查询字段
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

        return $grid;
    }
}
