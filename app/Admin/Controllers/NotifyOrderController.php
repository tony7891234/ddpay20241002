<?php


namespace App\Admin\Controllers;

use App\Models\NotifyOrder;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;

/**
 * Class NotifyOrderController
 * @package App\Admin\Controllers
 */
class NotifyOrderController extends AdminController
{

    /**
     * header 标题
     * @return string
     */
    public function title()
    {
        return '回掉商户遗漏订单';
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
        $grid = new Grid(new NotifyOrder);
        // 只导出 id, name和email 三列数据
        $titles = [
            'id' => 'ID',
            'created_at' => '添加时间',
        ];
        $grid->export()->titles($titles)->filename('出款订单');
        // 禁用批量操作
        $grid->disableBatchActions();
        $grid->disableRowSelector();

        $grid->withBorder();
        // 显示详情按钮
        $grid->showViewButton();
        // 禁用删除按钮
        $grid->disableDeleteButton();
        // 禁用编辑按钮
        $grid->disableEditButton();
        // 隐藏 创建按钮
        $grid->disableCreateButton();
        //  搜索条件
        $grid->model()->orderBy('id', 'desc'); // 按照ID 倒序排序

        $grid->column('id', 'ID');
        $grid->column('order_id', '订单ID');
        $grid->column('orderid', '订单号');

        $grid->column('notify_status', '回掉状态')->display(function ($input) {
            return isset(NotifyOrder::LIST_NOTIFY_STATUS[$input]) ? NotifyOrder::LIST_NOTIFY_STATUS[$input] : $input;
        });

        $grid->column('type', '订单类型')->display(function ($input) {
            return isset(NotifyOrder::LIST_TYPE[$input]) ? NotifyOrder::LIST_TYPE[$input] : $input;
        });

        $grid->column('notify_num', '回掉次数');

        $grid->column('create_time', '添加时间')->display(function ($input) {
            return formatTimeToString($input);
        });

        $grid->column('notify_time', '下次回掉时间')->display(function ($input) {
            return formatTimeToString($input);
        });
        $grid->column('response', '商户返回内容');
        $grid->column('request', '回掉内容')->hide();
        $grid->column('notify_url', '回掉地址')->hide();


        // 过滤器  查询字段
        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('order_id', 'ID')->width('350px');
            $filter->equal('orderid', '订单号')->width('350px');
            $filter->equal('notify_status', '回掉状态')->select(NotifyOrder::LIST_NOTIFY_STATUS);
        });
        return $grid;
    }


}
