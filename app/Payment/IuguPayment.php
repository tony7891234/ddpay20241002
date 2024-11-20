<?php

namespace App\Payment;

use App\Models\WithdrawOrder;
use Illuminate\Support\Facades\Storage;

/**
 * Class IuguPayment
 * @package App\Payment
 */
class IuguPayment extends BasePayment implements InterFacePayment
{


    /**
     * 1.出款请求
     * @param $orderInfo WithdrawOrder
     * @return array|bool
     */
    public function withdrawRequest($orderInfo)
    {

        if (!isset(self::LIST_API_PARAM['transferCode'][$orderInfo->pix_type])) {
            $this->errorCode = -1;
            $this->errorMessage = '不支持的付款方式:' . $orderInfo->pix_type;
            return false;
        }

        // 检查数据是否可以出款
        $validate = $this->validateWithdraw();
        if (!$validate) {
            return false;
        }

        // 如果是邮箱
        if (filter_var($orderInfo->pix_account, FILTER_VALIDATE_EMAIL)) {
            $orderInfo->pix_account = lcfirst($orderInfo->pix_account);
        }

        $requestParam = [
            // 这是附言:wq

            'api_token' => self::LIST_API_PARAM['token'],
            'transfer_type' => 'pix',
            'amount_cents' => bcmul($orderInfo->withdraw_amount, 100),
            'external_reference' => $this->sqlToBankOrder($orderInfo->getId()),
            'receiver' => [
                'pix' => [
                    'key' => $orderInfo->pix_account,
                    'type' => strtolower($orderInfo->pix_type),
                ],
            ],
            //description 转账附言，会显示到转账凭证上面
        ];
//        // 有留言，才写上
//        if (isset($orderInfo->user_message)) {
//            $requestParam['CustomerMessage'] = $orderInfo->user_message;
//        }

        $this->pix_out = $responseData = $this->curlRequest($requestParam, '/v1/transfer_requests');
//        logToMe('pix_out', $responseData);
//        dump($requestParam);
//        dump($responseData);
//        array:9 [
//        "transfer_request_id" => "FCD8E4D0C8824C6DB4B5EE2509E88D4C"
//  "created_at" => "2024-11-20T11:36:31-03:00"
//  "amount_cents" => 132
//  "transfer_type" => "pix"
//  "end_to_end_id" => "E15111975202411201436130f9501659"
//  "external_reference" => "dcat2021"
//  "receipt_url" => "https://comprovantes.iugu.com/fcd8e4d0-c882-4c6d-b4b5-ee2509e88d4c-d3dc88"
//  "status" => "pending"
//  "pix_bucket_size" => 53
//]
        if (!is_array($responseData) || !$responseData) {
            $this->errorCode = -21;
            $this->errorMessage = '响应内容有误：超时';
            return false;
        }
        //   exit;
        if (!isset($responseData['status']) || $responseData['status'] != 'pending') {
            $this->errorCode = 22;
            $this->errorMessage = 'Success 响应内容有误：' . $responseData['errors'];
            return false;
        }

        if (isset($responseData['end_to_end_id']) && $responseData['end_to_end_id'] != '') {
            $this->bank_order_id = $responseData['end_to_end_id'];
            return true;
        }

        $this->errorCode = -24;
        $this->errorMessage = 'DocumentNumber 不存在';
        return false;
    }

    /**
     * 2.出款回掉
     * @param $callbackData array
     * @return array|bool
     */
    public function withdrawCallback($callbackData)
    {

        // 订单id 和三方id
        if (!isset($callbackData['external_reference']) || !isset($callbackData['statement'])) {
            $this->errorCode = -102;
            $this->errorMessage = 'external_reference  or statement 参数不存在';
            return false;
        }
//        'orderid' => $callbackData['Identifier'],
//            'bank_order_id' => $callbackData['statement'],
        $orderInfo = $this->getWithdrawOrderRepository()->getById($callbackData['external_reference']);
        if (!$orderInfo) {
            $this->errorCode = -21;
            $this->errorMessage = '订单不存在' . $callbackData['external_reference'];
            return false;
        }
//        'sf_id' => $callbackData['statement'],

        // 更新回掉的数据
        $orderInfo->updateNotifyInfo($callbackData);

        if ($orderInfo->status != WithdrawOrder::STATUS_REQUEST_SUCCESS) {
            $this->errorCode = -22;
            $this->errorMessage = '订单状态不对' . $orderInfo->status;
            return false;
        }

        if ($callbackData['transfer_status'] == 'rejected') {
            $this->errorCode = -23;
            $this->errorMessage = '失败原因:' . $callbackData['ErrorDescription'] ?? '';
            $orderInfo->updateNotifyFail($this->errorMessage);
            return false;
        }

        if ($callbackData['transfer_status'] != 'done') {
            $this->errorCode = -24;
            $this->errorMessage = '支付失败';
            $orderInfo->updateNotifyFail($this->errorMessage);
            return false;
        }

        $orderInfo->updateToNotifySuccess();
        return true;
    }

    /************************************************* 下面是私钥方法 ****************************************************/


    private function get_request_time()
    {
        // Link de referência: https://dev.iugu.com/reference/autentica%C3%A7%C3%A3o#quinto-passo

        $datetime = new \DateTime('UTC');
        $datetime->setTimeZone(new \DateTimeZone('UTC'));
        $datetime_iso8601 = sprintf(
            '%s%s',
            $datetime->format('Y-m-d\TH:i:s'),
            $datetime->format('P')
        );
        return $datetime_iso8601;
    }

    private function get_private_key()
    {
        // 位置在 storage/app/pem/   其实是取值 filesystem.php 中的 local 配置
        $text_key = Storage::get('pem/iugu.pem');
        return openssl_pkey_get_private($text_key);
    }


    private function sign_body(
        $method,
        $endpoint,
        $request_time,
        $body,
        $private_key
    )
    {
        // Link de referência: https://dev.iugu.com/reference/autentica%C3%A7%C3%A3o#sexto-passo

        $pattern =
            $method .
            '|' .
            $endpoint .
            "\n" .
            self::LIST_API_PARAM['token'] .
            '|' .
            $request_time .
            "\n" .
            $body;
        $ret_sign = '';
        if (
        openssl_sign(
            $pattern,
            $ret_sign,
            $this->get_private_key(),
            OPENSSL_ALGO_SHA256
        )
        ) {
            $ret_sign = base64_encode($ret_sign);
        } else {
            die('error in openssl_sign');
        }
        return $ret_sign;
    }

    private function curlRequest($data, $endpoint, $method = 'POST')
    {
//        dump($data);
//        dump($endpoint);
        //var_dump($endpoint);die;
        $startTime = microtime(true);
        $request_time = $this->get_request_time();
        $body = json_encode($data);

        $header = [
            'Content-Type: application/json',
            'accept: application/json',
            'Request-Time: ' . $request_time,
        ];

        //出款才用签名，入款不用
        if ($endpoint == '/v1/transfer_requests') {
            $signature = $this->sign_body(
                $method,
                $endpoint,
                $request_time,
                $body,
                $this->get_private_key()
            );

            $header[] = 'Signature: signature=' . $signature;
        }

        $curl = curl_init();

        // #############################################################################
        //case of error: SSL certificate problem: unable to get local issuer certificate
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        // #############################################################################

        curl_setopt_array($curl, [
            CURLOPT_URL => self::LIST_API_PARAM['baseUrl'] . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $header,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return $err;
        } else {
            return json_decode($response, true);
        }
    }


    const LIST_CHANNEL = [
        'deposit_proxy_id' => 175,
        'transfer_proxy_id' => 176,
        'channel_code' => 'iugu',
        'bank_open' => 15,
    ];

    //1 cpf 2 email 3 phone(需加巴西区号 例如+55) 4 EVP 5 CNPJ
    const LIST_PIX_TYPE = [
        1 => 'cpf',
        2 => 'email',
        3 => 'phone',
        4 => 'evp',
        5 => 'cnpj', //不支持的付款方式
    ];


    const LIST_API_PARAM = [
        'baseUrl' => 'https://api.iugu.com',
        'token' => '4980C39384D48CD5D65217AE577FF6F53F83A3648E7F8131F8DD60BBD012920A',
        'pixKey' => '3a8ebab0-ba7c-42c1-9afa-913297e882a5',


        'partnerId' => 1532,
        'businessUnitId' => 1599,
        'taxNumber' => '45.707.166/0001-80',

        'taxNumberRaw' => '45707166000180',
        'bankInfo' => [
            "Bank" => "450",
            "BankBranch" => "0001",
            "BankAccount" => "5005600098",
            "BankAccountDigit" => "8",
        ],
        'address' => [
            'AddressLine' => 'Av. Cidade Jardim, 400',
            'ZipCode' => '01454901',
            'Neighborhood' => 'Jardim Paulistano',
            'CityCode' => '011',
            'CityName' => 'São PAULO',
            'AddressType' => 1,
            'Country' => 'Brasil',
            'Complement' => 'Apto 01',
            'Reference' => 'Próximo ao mercado',
        ],
        'transferCode' => [
            'CPF' => '0',
            'PHONE' => '3',
            'EMAIL' => '2',
            'EVP' => '4',
            'CNPJ' => '1',
        ],

        'transferCode1' => [
            '1' => 'cpf',
            '2' => 'phone',
            '3' => 'email',
            '4' => 'evp',
            '5' => 'cnpj',
        ],
    ];


}
