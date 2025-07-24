<?php


namespace App\Admin\Controllers;

use App\Models\ReportMinute;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;

/**
 * Class ReportMinuteController
 * @package App\Admin\Controllers
 */
class ReportMinuteController extends AdminController
{

    /**
     * header 标题
     * @return string
     */
    public function title()
    {
        return '分钟报表';
    }


    /**
     * 列表
     * @return Grid
     */
    protected function grid()
    {

        $model = new ReportMinute();
        $tableName = 'cd_report_minute';
        $model->setConnection('rds')->setTable($tableName);

        /**
         * @var $grid Grid
         */
        $grid = new Grid($model);

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

        if (isset($_GET['start_at']['start']) && ($_GET['start_at']['start'])) {
            $grid->model()
                ->where([
                    ['start_at', '>=', strtotime($_GET['start_at']['start'])],
                ]);
        }

        if (isset($_GET['start_at']['end']) && ($_GET['start_at']['end'])) {
            $grid->model()
                ->where([
                    ['start_at', '<=', strtotime($_GET['start_at']['end'])],
                ]);
        }


        //  搜索条件
        $grid->model()->orderBy('id', 'desc'); // 按照ID 倒序排序
        $grid->column('id', 'ID');

        $grid->column('start_at', '统计开始时间')->display(function ($input) {
            return formatTimeToString($input);
        });

        $grid->column('end_at', '统计结束时间')->display(function ($input) {
            return formatTimeToString($input);
        });

        $grid->column('request_count', '请求比数');
        $grid->column('request_amount', '请求金额');
        $grid->column('finished_count', '完成比数');
        $grid->column('finished_amount', '完成金额');

        $grid->column('created_at', '统计时间')->display(function ($input) {
            return formatTimeToString($input);
        });


        // 过滤器  查询字段
        $grid->filter(function (Grid\Filter $filter) {

            $filter->whereBetween('start_at', function ($q) {
                $start = $this->input['start'] ?? strtotime('today');
                $end = $this->input['end'] ?? strtotime('today');
                if ($start !== null) {
                    $q->where('start_at', '>=', strtotime($start));
                }
                if ($end !== null) {
                    $q->where('start_at', '<=', strtotime($end) + 3600);
                }
            })->datetime();
        });
        return $grid;
    }


}
