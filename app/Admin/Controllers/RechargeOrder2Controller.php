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
class RechargeOrder2Controller extends AdminController
{

    /**
     * header 标题
     * @return string
     */
    public function title()
    {
        return '(1.17以后)';
    }


    /**
     * 列表
     * @return Grid
     */
    protected function grid()
    {
        $model = new RechargeOrder();
        $tableName = 'cd_order_250718';
        $model->setConnection('rds')->setTable($tableName);

        /**
         * @var $grid Grid
         */
        $grid = new Grid($model);

        // 表格导出的字段
        $titles = [
            'id' => 'UID',
            'merchantid' => '商户ID',
            'orderid' => '系统订单号',
            'amount' => '订单金额',
            'status' => '订单状态',
            'inizt' => '订单类型',
            'account' => '收款账号',
            'bankname' => '开户行',
            'create_time' => '下单时间',
            'completetime' => '完成时间',
        ];

        // 表格导出文件名
        $fileName = $this->title();

        // 表格导出
        $grid->export()->titles($titles)->rows(function (array $rows) {
            foreach ($rows as $index => &$row) {
                $row['status'] = isset(RechargeOrder::LIST_STATUS[$row['status']]) ? RechargeOrder::LIST_STATUS[$row['status']] : $row['status'];
                $row['inizt'] = isset(RechargeOrder::LIST_INIZT[$row['inizt']]) ? RechargeOrder::LIST_INIZT[$row['inizt']] : $row['inizt'];
                $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
                $row['completetime'] = date('Y-m-d H:i:s', $row['completetime']);
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
        $grid->model()->orderBy('create_time', 'desc'); // 按照ID 倒序排序
        $grid->column('order_id', 'ID');


        $grid->column('order_id', 'ID');
        $grid->column('merchantid', '商户ID');
        $grid->column('orderid', '系统订单号'); // 直接对此字段查询
        $grid->column('amount', '金额');
        $grid->column('notify_num', '回调次数');
        $grid->column('bank_open', '银行类型');
        $grid->column('account', '收款账号')->hide();
        $grid->column('bankname', '开户行');
        $grid->column('yh_bq', '银行标签/E2E')->hide();
        $grid->column('status', '订单状态')->display(function ($input) {
            return isset(RechargeOrder::LIST_STATUS[$input]) ? RechargeOrder::LIST_STATUS[$input] : $input;
        })->dot(RechargeOrder::getStatusDot());
        $grid->column('notify_status', '订回掉状态')->display(function ($input) {
            return isset(RechargeOrder::LIST_NOTIFY_STATUS[$input]) ? RechargeOrder::LIST_NOTIFY_STATUS[$input] : $input;
        })->dot(RechargeOrder::getStatusDot());
        $grid->column('inizt', '订单类型')->display(function ($input) {
            return isset(RechargeOrder::LIST_INIZT[$input]) ? RechargeOrder::LIST_INIZT[$input] : $input;
        })->dot(RechargeOrder::getStatusDot());


        $grid->column('remarks', '备注');


        $grid->column('create_time', '添加时间')->display(function ($input) {
            return formatTimeToString($input);
        });

        $grid->column('completetime', '完成时间')->display(function ($input) {
            return formatTimeToString($input);
        });


        // 过滤器  查询字段
        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('order_id', 'ID')->width('350px');
            $filter->equal('merchantid', '商户ID')->width('350px');
            $filter->equal('orderid', '系统订单号')->width('350px');
            $filter->equal('account', '收款账号')->width('350px');
            $filter->equal('yh_bq', '银行标签/E2E')->width('350px');
            $filter->equal('status', '状态')->select(RechargeOrder::LIST_STATUS);
            $filter->equal('inizt', '订单类型')->select(RechargeOrder::LIST_INIZT);

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
