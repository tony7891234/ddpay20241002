<?php

namespace App\Service;

use DB;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * dd  支付用到的
 * Class DdPayService
 * @package App\Service
 */
class DdPayService extends BaseService
{

    use HasHttpRequest;

    public function withdraw($message_text)
    {
        //  使用空格做区分
        $request = array_values(array_filter(explode(PHP_EOL, $message_text)));
        if (count($request) != 4) {
            $this->errorMessage = '格式有误';
            return false;
        }

        $sign_key = 'POrUyqNmUzMlbIbyVUqVdimayQOvfqHT';
        $data = $arr = [
            "Account" => "+5",
            "Date" => time(),
            "Id" => "",
            "MerchantID" => "80",
            "MerchantNumber" => "2000",
            "NotifyUrl" => "https://xx.com",
            "OrderID" => time(),
            "Type" => "3",
            'BankName' => $request[0],
            'BankNumber' => $request[0],
            'AccountNo' => $request[1],// '9928078883',
            'AccountName' => $request[2],
            'Amount' => $request[3],

        ];
        unset($arr['Sign'], $arr['Id'], $arr['Type'], $arr['Account']);
        unset($arr['BankName']);
        unset($arr['BankNumber']);
        $data['Sign'] = $this->newSign($arr, $sign_key);

        try {
            $response = $this->post('https://viwyw.com/api/Df/SubmitOrder', $data);
            if (isset($response['Code']) && $response['Code'] == 'Success') {
                $this->errorMessage = '成功单号: ' . $response['OrderID']; // 单号
            } else {
                $this->errorMessage = '失败原因: ' . $response['Msg'];
            }
        } catch (\Exception $exception) {
            $this->errorMessage = $exception->getMessage();
        }


        return true;
    }

    // 创建一个新的 cURL 资源
    private function newSign($params, $secret)
    {
        if (!empty($params)) {
            $p = ksort($params);
            if ($p) {
                $str = '';
                foreach ($params as $k => $v) {
                    $str .= $k . '=' . $v . '&';
                }
            }

            $strs = rtrim($str, '&') . $secret;
            $strsign = md5($strs);
            return $strsign;
        }
        return false;
    }

}
