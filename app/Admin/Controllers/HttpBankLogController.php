<?php


namespace App\Admin\Controllers;

use App\Models\HttpBankLog;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;

/**
 * Class HttpBankLogController
 * @package App\Admin\Controllers
 */
class HttpBankLogController extends AdminController
{

    /**
     * header 标题
     * @return string
     */
    public function title()
    {
        return '请求银行的日志';
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
        $grid = new Grid(new HttpBankLog);
        // 只导出 id, name和email 三列数据
        $titles = [
            'id' => 'ID',
            'order_id' => '订单ID',
        ];
        $grid->export()->titles($titles)->filename('出款订单');
        // 禁用批量操作
        $grid->disableRowSelector();
        $grid->disableDeleteButton();
        $grid->disableEditButton();
        $grid->disableViewButton();

//        $grid->withBorder();
        // 显示详情按钮
        $grid->showViewButton();
        // 禁用删除按钮
        $grid->disableDeleteButton();
        // 禁用编辑按钮
        $grid->disableEditButton();
        // 隐藏 创建按钮
        $grid->disableCreateButton();

        // 设置固定总条数
        $grid->model()->setTotal(10000); // 这里设置总条数为 100
        //  搜索条件
        $grid->model()->orderBy('id', 'desc'); // 按照ID 倒序排序

        $grid->column('id', 'ID');
        $grid->column('order_id', '订单ID');


//        $grid->column('time_cost', '请求的时间');
//        $grid->column('createtime', '添加时间');
//        $grid->column('receipt', '凭证链接');
//
//
//        $grid->column('merchant', '商户请求数据')->display(function ($input) {
//            $input = json_decode($input, true);
//            if (empty($input)) {
//                return '';
//            }
//            return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
//        })->hide();
//
//
//        $grid->column('request', '系统请求数据')->display(function ($input) {
//            $input = json_decode($input, true);
//            if (empty($input)) {
//                return '';
//            }
//            return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
//        })->hide();
//
//
//        $grid->column('request', '银行返回数据')->display(function ($input) {
//            $input = json_decode($input, true);
//            if (empty($input)) {
//                return '';
//            }
//            return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
//        })->hide();
//
//
//        $grid->column('callback', '银行回调数据')->display(function ($input) {
//            $input = json_decode($input, true);
//            if (empty($input)) {
//                return '';
//            }
//            return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
//        })->hide();


        // 过滤器  查询字段
        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('order_id', 'ID')->width('350px');
        });
        return $grid;
    }


}
