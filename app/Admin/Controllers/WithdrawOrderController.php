<?php


namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\OrderHandNotify;
use App\Admin\Actions\Grid\TestOrderHandNotify;
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
     *  数据详情  detail($id, Content $content)
     * @param $id
     * @return Show
     */
    public function detail($id)
    {
        // 详情不需要使用关联模型，因为只有一次查询，不会影响性能，直接查询即可
        return new Show($id, new WithdrawOrder(), function (Show $show) {
            $show->disableDeleteButton(); // 禁止删除
            $show->disableEditButton();//禁止编辑
            $show->order_id('ID');
            $show->field('merchant_id', '商户')->as(function ($merchant_id) {
                return Merchant::find($merchant_id)->username;
            });
            $show->upstream_id('通道')->as(function ($upstream_id) {
                return Upstream::find($upstream_id)->nick_name;
            });
            $show->field('agent_id', '商户代理')->as(function ($input) {
                $info = Merchant::find($input);
                return $info ? $info->username : $input;
            });
            $show->field('upstream_id', '通道')->as(function ($input) {
                $info = Upstream::find($input);
                return $info ? $info->nick_name : $input;
            });
            $show->field('local_order_id', '平台订单号');
            $show->field('merchant_order_id', '商户订单号'); // 直接对此字段查询
            $show->field('payment_money', '订单金额');
            $show->field('real_money', '到账金额');
            $show->field('agent_profit', '代理利润');
            $show->field('local_profit', '平台利润');
            $show->field('merchant_rate', '商户费率');
            $show->field('agent_rate', '商户代理费率');
            $show->field('pay_notify_url', '异步回调');
            $show->field('pay_return_url', '同步回调');
            $show->status('订单状态')->as(function ($status) {
                return WithdrawOrder::LIST_STATUS[$status];
            })->dot();
            $show->send('回调客户失败次数');
            $show->created_at('添加时间');
            $show->updated_at('更新时间');
        });
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
        $grid = new Grid(WithdrawOrder::with(['belongsToOneMerchant', 'belongsToOneUpstream']));
        // 只导出 id, name和email 三列数据
        $titles = [
            'local_order_id' => '系统订单号',
            'merchant_order_id' => '商户订单号',
            'payment_money' => '订单金额',
            'real_money' => '到账金额',
            'merchant_rate' => '商户费率',
            'agent_profit' => '代理利润',
            'agent_rate' => '代理费率',
            'local_profit' => '本平台利润',
            'status' => '订单状态(3:成功,其他值失败)',
            'pay_notify_url' => '同步商户url',
            'pay_return_url' => '异步回调url',
            'created_at' => '添加时间',
        ];
        $grid->export()->titles($titles)->filename('九鼎支付订单');
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
        $grid->quickSearch(['local_order_id', 'merchant_order_id'])->placeholder('平台订单、商户订单');
        $grid->model()->orderBy('order_id', 'desc'); // 按照ID 倒序排序
        $grid->column('order_id', 'ID');
        $grid->column('belongsToOneMerchant.username', '商户')->copyable();
        $grid->column('belongsToOneUpstream.nick_name', '通道')->copyable();
        $grid->column('notify', '回调')->display(function () {
            $tmp = OrderHandNotify::make();
            $tmp->setKey($this->local_order_id);

            $tmp2 = TestOrderHandNotify::make();
            $tmp2->setKey($this->local_order_id);
            return $tmp . '&nbsp;&nbsp;&nbsp;' . $tmp2;
        })->hide();

        $grid->column('local_order_id', '平台订单号')->copyable();
//        $grid->column('local_order_id', '平台订单号')->display(function ($local_order_id) {
//            $tmp = OrderHandNotify::make();
//            $tmp->setKey($local_order_id);
//
//            $tmp2 = TestOrderHandNotify::make();
//            $tmp2->setKey($local_order_id);
//            return $tmp . '&nbsp;&nbsp;&nbsp;' . $tmp2;
//        });
//

        $grid->column('merchant_order_id', '商户订单号')->copyable(); // 直接对此字段查询
        $grid->column('payment_money', '订单金额');
        $grid->column('real_money', '到账金额');
        $grid->column('agent_profit', '代理利润');
        $grid->column('local_profit', '平台利润');
        $grid->column('merchant_rate', '商户费率')->hide();
        $grid->column('agent_rate', '商户代理费率')->hide();

        $grid->column('pay_type', '支付类型')->display(function ($type) {
            return Upstream::LIST_TYPE[$type];
        })->dot(Upstream::getTypeDot());

        $grid->column('status', '订单状态')->display(function ($status) {
            return WithdrawOrder::LIST_STATUS[$status];
        })->dot(WithdrawOrder::getStatusDot());
        $grid->column('send', '回调次数')->hide();
        $grid->column('remark', '备注')->hide();
        $grid->created_at('添加时间')->sortable();
        $grid->updated_at('更新时间')->sortable();

        $grid->column('pay_notify_url', '异步回调')->hide();
        $grid->column('pay_return_url', '同步回调')->hide();

        // 过滤器  查询字段
        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('order_id', 'ID')->width('350px');
            $filter->equal('local_order_id', '平台订单号')->width('350px');
            $filter->equal('merchant_order_id', '商户订单号')->width('350px');
            $filter->equal('status', '状态')->select(WithdrawOrder::LIST_STATUS);

            $filter->equal('merchant_id', '商户名')->select(Merchant::pluck('username', 'id')->toArray());
            $filter->equal('upstream_id', '通道')->select(Upstream::pluck('nick_name', 'upstream_id')->toArray());
            $filter->between('created_at', '添加时间')->date();
        });
        return $grid;
    }


}
