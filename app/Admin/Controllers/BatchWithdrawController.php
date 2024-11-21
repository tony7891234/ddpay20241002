<?php

namespace App\Admin\Controllers;

use App\Jobs\BatchWithdrawJob;
use App\Models\BatchWithdraw;
use App\Models\WithdrawOrder;
use App\Payment\BasePayment;
use App\Traits\PreviewCode;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;

use App\Models\BaseModel;
use Dcat\Admin\Form;
use Dcat\Admin\Layout\Content;

/**
 * 文件上传位置  storage/app/public/admin/files
 * Class BatchWithdrawController
 * @package App\Admin\Controllers
 */
class BatchWithdrawController extends AdminController
{

    use PreviewCode;

    /**
     * header 标题
     * @return string
     */
    public function title()
    {
        return '批量订单';
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
        $grid = new Grid(new BatchWithdraw);

        $grid->disableRowSelector();
        $grid->disableDeleteButton();
        $grid->disableEditButton();
        $grid->disableViewButton();

        $grid->model()->orderBy('bach_id', 'desc'); // 按照ID 倒序排序
        $grid->column('bach_id', 'ID');
        $grid->column('batch_no', '批次');
        $grid->column('upstream_id', '银行')->display(function ($input) {
            return isset(BasePayment::LIST_BANK[$input]) ? BasePayment::LIST_BANK[$input] : $input;
        });

        $grid->column('message', '内容')->display(function ($input) {
            $input = json_decode($input, true);
            if (empty($input)) {
                return '';
            }
            return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        });

        $grid->column('file', '文件路径');

        $grid->column('created_at', '添加时间')->display(function ($input) {
            return formatTimeToString($input);
        });

        $grid->column('updated_at', '更新时间')->display(function ($input) {
            return formatTimeToString($input);
        });

        $grid->column('response_success', '成功得')->display(function ($input) {
            $input = json_decode($input, true);
            if (empty($input)) {
                return '';
            }
            return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        })->hide();

        $grid->column('response_fail', '失败得')->display(function ($input) {
            $input = json_decode($input, true);
            if (empty($input)) {
                return '';
            }
            return '<pre class="dump">' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        })->hide();

        // 过滤器  查询字段
        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('batch_no', '批次');
            $filter->whereBetween('添加时间', function ($query) {
                $start = isset($this->input['start']) ? $this->input['start'] : null;
                $end = isset($this->input['end']) ? $this->input['end'] : null;
                if ($start) {
                    $query->where('created_at', '>=', strtotime($start));
                }
                if ($end) {
                    $query->where('created_at', '<=', strtotime($end));
                }
            })->datetime();
        });
        return $grid;
    }

    public function create(Content $content)
    {
        return $content
            ->title($this->title())
            ->description('内容和文件必须二选一;多个内容必须换行输入(顺序:1.pix类别;2.pix账号;3.金额;4.附言)')
            ->body($this->newline())
            ->body($this->form());
    }

    /**
     *  * @return Form
     * @property string message  内容
     * @property string file  文件路径
     * @property int created_at  添加时间
     * @property int send_at  发送时间
     *
     * @property int id
     */
    protected function form()
    {
        return Form::make(new BatchWithdraw(), function (Form $form) {
//            $form->disableListButton();
            $form->disableViewCheck();
            $form->disableCreatingCheck();
            $form->disableEditingCheck();
            $form->row(function ($form) {
                $form->width(12)->textarea('message', '内容')->required();
            });

            $form->row(function ($form) {
                $form->radio('upstream_id', '银行')->options(function () {
                    return BasePayment::LIST_BANK;
                })->required();
            });

            $form->row(function ($form) {
                $form->file('file', '文件路径')
                    ->accept(implode(',', BatchWithdrawJob::LIST_ALLOWED_EXTENSION)) // 上传文件的类型
                    ->autoUpload() // 自动上传
                    ->move(config('app.app_file_name'), function ($file) use ($form) {
                        $extension = $file->getClientOriginalExtension(); // 获取原文件的后缀名
                        return time() . '.' . $extension; // 构建新的文件名，保留原文件的后缀
                    }); //  修改文件路径，并且改名字
//                    ->move(config('xuanwu.phone_file_name'), time() . '.txt'); //  修改文件路径，并且改名字
            });

            $form->hidden('batch_no');
            $form->hidden('created_at');
            $form->submitted(function (Form $form) {
                $form->batch_no = makeOrder(BaseModel::PREFIX_BATCH_WITHDRAW);
                $form->merchant_id = WithdrawOrder::MERCHANT_DEFAULT;

                // 换行 PHP_EOL
                $arr = [];
                foreach (explode(PHP_EOL, $form->message) as $item) {
                    $item = rtrim($item, "\\r");
                    $item = str_replace("\\t", ' ', $item);
                    $item = array_filter(explode(' ', $item));
                    $content = implode(',', $item);
                    $arr[] = $content;
                }


                $form->message = json_encode($arr, JSON_UNESCAPED_UNICODE);

                $form->created_at = time();
            });

            $form->saved(function (Form $form) {
                BatchWithdrawJob::dispatch($form->getKey()); // 添加队列
            });

        });
    }


}
