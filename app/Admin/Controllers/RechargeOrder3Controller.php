<?php


namespace App\Admin\Controllers;

use App\Models\RechargeOrder;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;

/**
 * Class RechargeOrder1Controller
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


        // 表格导出的字段
        $titles = [
            'orderid' => '系统订单号',
            'amount' => '订单金额',
            'status' => '订单状态',
            'account' => '收款账号',
            'bankname' => '开户行',
            'create_time' => '下单时间',
        ];
        // 表格导出文件名
        $fileName = $this->title();

        // 表格导出
        $grid->export()->titles($titles)->rows(function (array $rows) {
            foreach ($rows as $index => &$row) {
                $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
            }
            return $rows;
        })->filename($fileName)->xlsx();

        // 禁用批量操作
        $grid->disableBatchActions();
        $grid->disableRowSelector();

        // 禁用删除按钮
        $grid->disableDeleteButton();
        // 禁用编辑按钮
        $grid->disableEditButton();
        // 隐藏 创建按钮
        $grid->disableCreateButton();


        $grid->disableViewButton();

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
                'status',       // 导出用到
                'account',      // 列表和导出用到
                'bankname',     // 列表和导出用到
                'bank_open',    // 列表显示用到
                'create_time'   // 列表和导出用到
            ])
            ->where('inizt', '=', RechargeOrder::INIZT_WITHDRAW)
            ->where('status', '=', RechargeOrder::STATUS_SUCCESS)
            ->orderBy('order_id', 'desc'); // 按照ID 倒序排序
        $grid->column('order_id', 'ID');
        $grid->column('orderid', '系统订单号'); // 直接对此字段查询
        $grid->column('amount', '金额');
        $grid->column('bank_open', '银行类型');
        $grid->column('account', '收款账号');
        $grid->column('bankname', '开户行');
        $grid->column('create_time', '添加时间')->display(function ($input) {
            return formatTimeToString($input);
        });


        // 过滤器  查询字段
        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('order_id', 'ID')->width('350px');
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
