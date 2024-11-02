<?php


namespace App\Admin\Controllers;

use App\Models\MoneyLog;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;

/**
 * Class MoneyLogController
 * @package App\Admin\Controllers
 */
class MoneyLog1Controller extends AdminController
{

    /**
     * header 标题
     * @return string
     */
    public function title()
    {
        return '10.28以后';
    }


    /**
     * 列表
     * @return Grid
     */
    protected function grid()
    {

        $model = new MoneyLog();
        $tableName = 'cd_moneylog_1028';
        $model->setConnection('rds')->setTable($tableName);
        /**
         * @var $grid Grid
         */
        $grid = new Grid($model);
        // 表格导出的字段
        $titles = [
            'moneylog_id' => '编号ID',
            'merchant_id' => '商户ID',
            'adduser' => '订单号',
            'action' => '方法',
            'begin' => '期初金额',
            'after' => '期末金额',
            'money' => '发生额',
            'content' => '手续费',
            'remark' => '备注',
            'create_time' => '下单时间',
        ];

        // 表格导出文件名
        $fileName = $this->title();

        // 表格导出
        $grid->export()->titles($titles)->rows(function (array $rows) {
            foreach ($rows as $index => &$row) {
                $row['bank_lx'] = isset(MoneyLog::PAY_LIST_ACTION[$row['bank_lx']]) ? MoneyLog::PAY_LIST_ACTION[$row['bank_lx']] : $row['bank_lx'];
                $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
            }
            return $rows;
        })->filename($fileName)->csv();

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
        $grid->column('moneylog_id', 'ID');


        $grid->column('moneylog_id', '编号ID');
        $grid->column('merchant_id', '商户ID');

        $grid->column('type', '变动类型');
        $grid->column('adduser', '订单号');
        $grid->column('action', '方法');
        $grid->column('begin', '期初金额');
        $grid->column('after', '期末金额');
        $grid->column('money', '发生额');
        $grid->column('content', '手续费');
        $grid->column('remark', '备注');


        $grid->column('bank_lx', '卡类型')->display(function ($input) {
            return isset(MoneyLog::PAY_LIST_ACTION[$input]) ? MoneyLog::PAY_LIST_ACTION[$input] : $input;
        })->dot(MoneyLog::getStatusDot());

        $grid->column('create_time', '添加时间')->display(function ($input) {
            return formatTimeToString($input);
        });


        // 过滤器  查询字段
        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('moneylog_id', 'ID')->width('350px');
            $filter->equal('merchant_id', '商户ID')->width('350px');
            $filter->equal('adduser', '订单号')->width('350px');

            $filter->equal('bank_lx', '卡类型')->select(MoneyLog::PAY_LIST_ACTION);

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
