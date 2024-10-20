<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;

class JobController extends Controller
{

    public function index(Content $content)
    {
        return $content
            ->header('报表')
            ->description('合并表头功能示例')
//            ->body(function ($row) {
//                $row->column(4, new TotalUsers());
//                $row->column(4, new NewUsers());
//                $row->column(4, new NewDevices());
//            })
            ->body($this->grid());
    }

    protected function grid()
    {
        return new Grid(new Job(), function (Grid $grid) {
//            $grid->showColumnSelector();
//            $grid->combine('avgCost', ['avgMonthCost', 'avgQuarterCost', 'avgYearCost'])->help('提示信息演示');
//            $grid->combine('avgVist', ['avgMonthVist', 'avgQuarterVist', 'avgYearVist']);
//            $grid->tableCollapse(false);


            $grid->column('id', 'ID')->sortable();
            $grid->column('queue', '队列类型');
            $grid->column('attempts', '尝试运行次数');
//            $grid->column('created_at', '添加时间')->display(function ($input) {
//                return Carbon::parse($input)->format('d H:i:s');
//            });
//            $grid->column('available_at', '允许时间')->display(function ($input) {
//                return Carbon::parse($input)->format('d H:i:s');
//            });
//            $grid->payload('队列内容')->display(function ($input) {
//                if (empty($input)) {
//                    return '';
//                }
//                return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
//            });

            $grid->disableActions();
            $grid->disableBatchDelete();
            $grid->disableCreateButton();
            $grid->disableCreateButton();
            $grid->rowSelector()
                ->style('success')
                ->click();
//            $grid->tools($this->buildPreviewButton());
            $grid->filter(function (Grid\Filter $filter) {
//                $filter->scope(1, admin_trans_field('month'))->where('date', 2019, '<=');
//                $filter->scope(2, admin_trans_label('quarter'))->where('date', 2019, '<=');
//                $filter->scope(3, admin_trans_label('year'))->where('date', 2019, '<=');
                $filter->equal('queue');
                $filter->equal('attempts');
            });
        });
    }
}
