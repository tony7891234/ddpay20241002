<?php


namespace App\Admin\Controllers;

use App\Models\WithdrawOrder;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;

/**
 * 平台订单
 * Class WithdrawOrderController
 * @package App\Admin\Controllers
 */
class WithdrawOrderController extends AdminController
{

    /**
     * header 标题
     * @return string
     */
    public function title()
    {
        return '商户订单';
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
        $grid = new Grid(new WithdrawOrder);
        // 只导出 id, name和email 三列数据
        $titles = [
            'order_id' => 'ID',
            'created_at' => '添加时间',
        ];
        $grid->export()->titles($titles)->filename('出款订单');
        // 禁用批量操作
        $grid->disableBatchActions();
        $grid->disableRowSelector();

//        $grid->withBorder();
        // 显示详情按钮
//        $grid->showViewButton();
        // 禁用删除按钮
        $grid->disableDeleteButton();
        // 禁用编辑按钮
        $grid->disableEditButton();
        // 隐藏 创建按钮
        $grid->disableCreateButton();


        $grid->disableViewButton();
        //  搜索条件
        $grid->model()->orderBy('order_id', 'desc'); // 按照ID 倒序排序
        $grid->column('order_id', 'ID');


        $grid->column('order_id', 'ID');
        $grid->column('batch_no', '批量单号'); // 直接对此字段查询
        $grid->column('bank_order_id', '银行单号');
        $grid->column('pix_type', 'pix类型');
        $grid->column('status', '订单状态')->display(function ($input) {
            return isset(WithdrawOrder::LIST_STATUS[$input]) ? WithdrawOrder::LIST_STATUS[$input] : $input;
        })->dot(WithdrawOrder::getStatusDot());
        $grid->column('pix_account', 'pix账号');
        $grid->column('withdraw_amount', '出款金额');
        $grid->column('user_message', '附言(给客户的)');
        $grid->column('remark', '备注(运营)');
        $grid->column('error_message', '错误信息')->hide();


        $grid->column('created_at', '添加时间')->display(function ($input) {
            return formatTimeToString($input);
        });

        $grid->column('updated_at', '更新时间')->display(function ($input) {
            return formatTimeToString($input);
        });

//        $grid->column('request_bank', '请求银行内容')->display(function ($input) {
//            $input = json_decode($input, true);
//            if (empty($input)) {
//                return '';
//            }
//            return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
//        });
//
//        $grid->column('response_bank', '银行返回')->display(function ($input) {
//            $input = json_decode($input, true);
//            if (empty($input)) {
//                return '';
//            }
//            return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
//        });

        $grid->column('notify_info', '银行回掉数据')->display(function ($input) {
            $input = json_decode($input, true);
            if (empty($input)) {
                return '';
            }
            return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        })->hide();

        $grid->column('pix_info', 'pix_info')->display(function ($input) {
            $input = json_decode($input, true);
            if (empty($input)) {
                return '';
            }
            return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        })->hide();

        $grid->column('pix_out', 'pix_out')->display(function ($input) {
            $input = json_decode($input, true);
            if (empty($input)) {
                return '';
            }
            return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        })->hide();


        // 过滤器  查询字段
        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('order_id', 'ID')->width('350px');
            $filter->equal('batch_no', '批量单号')->width('350px');

            $filter->equal('status', '状态')->select(WithdrawOrder::LIST_STATUS);
            $filter->equal('pix_type', 'pix类型')->select(WithdrawOrder::LIST_PIX);
        });
        return $grid;
    }


}
