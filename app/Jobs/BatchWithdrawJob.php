<?php

namespace App\Jobs;

use App\Models\WithdrawOrder;
use Carbon\Carbon;
use App\Models\BatchWithdraw;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 添加 出款 的处理
 * Class BatchWithdrawJob
 * @package App\Jobs
 */
class BatchWithdrawJob extends BaseJob
{
    // $extension
    const LIST_ALLOWED_EXTENSION = ['txt', 'csv', 'xls', 'xlsx'];

    /**
     * 订单id
     * @var int
     */
    private $bach_id;

    /**
     * @var  BatchWithdraw
     */
    private $batch_withdraw_info;

    /**
     * @var WithdrawOrder
     */
    private $withdrawOrder;

    public function __construct($bach_id)
    {
        $this->bach_id = $bach_id;
    }

    public function handle()
    {
        $response = $this->deal();
        if (!$response) {
            logToMe('BatchWithdrawJob', ['code' => $this->errorCode, 'message' => $this->errorMessage]);
        }
    }

    //  格式     账号  金额  姓名 卡号  银行
    private function deal()
    {
        $this->batch_withdraw_info = BatchWithdraw::where('bach_id', '=', $this->bach_id)->first();
        if (!$this->batch_withdraw_info) {
            $this->errorCode = -1;
            $this->errorMessage = '数据不存在';
            return false;
        }
        //  如果不是空  表示又数据
        if ($list = $this->batch_withdraw_info->message) {
            $this->forWithdraw($list);
        }

//        $file = \Illuminate\Support\Facades\Storage::disk('admin')->get($this->batch_withdraw_info->file);
//        if ($file) {
//            $arr = [];
//            foreach (explode(PHP_EOL, $file) as $item) {
//                $item = rtrim($item, "\\r");
//                $item = str_replace("\\t", ' ', $item);
//                $item = array_filter(explode(' ', $item));
//                $content = implode(',', $item);
//                $arr[] = $content;
//            }
//            $this->forWithdraw(json_encode($arr));
//        }


        if ($this->batch_withdraw_info->file) {
            $extension = pathinfo($this->batch_withdraw_info->file, PATHINFO_EXTENSION);
            if (in_array($extension, self::LIST_ALLOWED_EXTENSION)) {
                // 获取上传的xls文件路径
                $filePath = \Illuminate\Support\Facades\Storage::disk('withdraw')->path($this->batch_withdraw_info->file);
                // 使用Excel门面读取xls文件的数据
                $file = Excel::toArray([], $filePath)[0];
                $arr = [];
                foreach ($file as $item) {
                    $arr[] = implode(',', $item);
                }
                $this->forWithdraw(json_encode($arr));
            } else {
                $file = \Illuminate\Support\Facades\Storage::disk('withdraw')->get($this->batch_withdraw_info->file);
                $file = explode(PHP_EOL, $file);
                $arr = [];
                foreach ($file as $item) {
                    $item = rtrim($item, "\\r");
                    $item = str_replace("\\t", ' ', $item);
                    $item = array_filter(explode(' ', $item));
                    $content = implode(',', $item);
                    $arr[] = $content;
                }

                $this->forWithdraw(json_encode($arr));
            }
        }

        $this->batch_withdraw_info->update([
            'response_success' => json_encode($this->response_success, JSON_UNESCAPED_UNICODE),
            'response_fail' => json_encode($this->response_fail, JSON_UNESCAPED_UNICODE),
        ]);
        return true;
    }

    private $response_success = [];
    private $response_fail = [];

    /**
     * @param $list string
     * @return bool
     */
    private function forWithdraw($list)
    {

        // 1. 检查phone 是否有数据
        foreach (json_decode($list, true) as $request) {
            $request = rtrim($request, "\r");
            $arr = array_values(array_filter(explode(",", $request)));
            if (count($arr) <= 2) {
                continue;
            }

            if (count($arr) != 4) {
                $response[] = $request . '格式有误';
//                $arr[] = '参数不是4位';
                $this->response_fail[] = implode(',', $arr);
                continue;
            }

            // pix类别  pix账号  金额   附言
            $pix_type = $arr[0];
            $pix_account = $arr[1];
            $withdraw_amount = $arr[2];
            $user_message = $arr[3];

            if (!in_array($pix_type, WithdrawOrder::LIST_PIX)) {
                $arr[] = 'pix类别不存在:' . $pix_type;
                $this->response_fail[] = implode(',', $arr);
                continue;
            }
            $this->response_success[] = implode(',', $arr);

            $this->addOrder($pix_type, $pix_account, $withdraw_amount, $user_message);

        }
        return true;
    }


    /**
     * @param $pix_type int
     * @param $pix_account string
     * @param $withdraw_amount float
     * @param $user_message string
     * @return bool
     */
    private function addOrder($pix_type, $pix_account, $withdraw_amount, $user_message)
    {
        $arr = [
            "merchant_id" => WithdrawOrder::MERCHANT_DEFAULT, // 商户号
            'batch_no' => $this->batch_withdraw_info->batch_no,
            'pix_type' => $pix_type,
            'pix_account' => $pix_account,
            'withdraw_amount' => $withdraw_amount,
            'user_message' => $user_message,
            'remark' => '批量出款',
            'error_message' => '',
            'request_bank' => '',
            'response_bank' => '',
            'status' => WithdrawOrder::STATUS_WAITING,
            "created_at" => time(),
        ];

        $this->withdrawOrder = WithdrawOrder::create($arr);
        if (!$this->withdrawOrder) {
            $this->errorCode = -598;
            $this->errorMessage = '订单创建失败';
            return false;
        }

        WithdrawToBankJob::dispatch($this->withdrawOrder); // 添加队列
        return true;
    }

}
