<?php


namespace App\Admin\Controllers;

use App\Models\Job;
use Carbon\Carbon;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;

/**
 * 队列
 * Class JobController
 * @package App\Admin\Controllers
 */
class JobController extends AdminController
{


    /**
     * header 标题
     * @return string
     */
    public function title()
    {
        return '队列';
    }

    /**
     * 列表
     * @return Grid
     */
    protected function grid()
    {
        /**
         * @var $grid Grid
         */
        $grid = new Grid(new Job());
        //  禁止操作
        $grid->disableActions();

        $grid->model()->orderBy('id', 'desc'); // 按照ID 倒序排序
        $grid->column('id', 'ID')->sortable();
        $grid->column('queue', '队列类型');
        $grid->column('attempts', '尝试运行次数');
        $grid->column('created_at', '添加时间')->display(function ($input) {
            return Carbon::parse($input)->format('d H:i:s');
        });
        $grid->column('available_at', '允许时间')->display(function ($input) {
            return Carbon::parse($input)->format('d H:i:s');
        });
        $grid->payload('队列内容')->display(function ($input) {
            if (empty($input)) {
                return '';
            }
            return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        });
        return $grid;
    }

    /**
     * @return Form
     */
    public function form()
    {
        // 要向使用删除功能，则必须添加这个处理方式
        return Form::make(new Job(), function (Form $form) {
        });
    }

}
