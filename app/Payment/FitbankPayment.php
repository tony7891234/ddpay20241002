<?php

namespace App\Payment;

use App\Models\PixModel;
use App\Models\WithdrawOrder;
use App\Traits\RepositoryTrait;

/**
 * fit银行
 * Class FitbankPayment
 * @package App\Payment
 */
class FitbankPayment extends BasePayment
{
    use RepositoryTrait;


    const PREFIX_ORDER_ID = 'dcat'; // 订单号的前缀

    /**
     * @var string   pix  信息
     */
    public $pix_info = '';

    /**
     * @var string  出款信息
     */
    public $pix_out = '';

    /**
     * @var string 三方的订单id
     */
    public $bank_order_id;


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

        // 1.先查询库
        $has_sql = false; // 数据库是否存在   不要重复插入
        /**
         * @var $pix_info PixModel
         */
        $this->pix_info = $pix_info = PixModel::where('account', '=', $orderInfo->pix_account)->first();
        if ($pix_info) {
            $pix_status = $pix_info->status;
            if ($pix_status && $pix_status != PixModel::STATUS_SUCCESS) {
                $this->errorCode = -21;
                $this->errorMessage = "pix 查询错误:::" . $pix_info['remark'];
                return false;
            }
        }

        // 2.上面没有失败的，处理新的查询
        $this->pix_info = $pixInfo = $this->getPixInfo($orderInfo->pix_account);

        if ($pixInfo['status'] != 'success') {
            $this->errorCode = -31;
            $this->errorMessage = "pix 查询错误:" . $pixInfo['msg'];
            return false;
        }

        // 如果是邮箱
        if (filter_var($orderInfo->pix_account, FILTER_VALIDATE_EMAIL)) {
            $orderInfo->pix_account = lcfirst($orderInfo->pix_account);
        }

        if ($pixInfo['data']['PixKeyValue'] != $orderInfo->pix_account) {
            $this->errorCode = -32;
            $this->errorMessage = '出款账户不一致';
            return false;
        }


        $requestParam = [
            "Method" => "GeneratePixOut",
            "PartnerId" => self::LIST_API_PARAM['partnerId'],
            "BusinessUnitId" => self::LIST_API_PARAM['businessUnitId'],
            "TaxNumber" => self::LIST_API_PARAM['taxNumberRaw'],
            "Bank" => self::LIST_API_PARAM['bankInfo']['Bank'],
            "BankBranch" => self::LIST_API_PARAM['bankInfo']['BankBranch'],
            "BankAccount" => self::LIST_API_PARAM['bankInfo']['BankAccount'],
            "BankAccountDigit" => self::LIST_API_PARAM['bankInfo']['BankAccountDigit'],
            "ToName" => $pixInfo['data']['ReceiverName'],
            "ToTaxNumber" => $pixInfo['data']['ReceiverTaxNumber'],
            "PixKey" => $pixInfo['data']['PixKeyValue'],
            "PixKeyType" => $pixInfo['data']['PixKeyType'],
            "ToBank" => $pixInfo['data']['ReceiverBank'],
            "ToISPB" => $pixInfo['data']['ReceiverISPB'],
            "ToBankBranch" => $pixInfo['data']['ReceiverBankBranch'],
            "ToBankAccount" => $pixInfo['data']['ReceiverBankAccount'],
            "ToBankAccountDigit" => $pixInfo['data']['ReceiverBankAccountDigit'],
            "Value" => $orderInfo->withdraw_amount,
            "AccountType" => $pixInfo['data']['ReceiverAccountType'],
            "Identifier" => $this->sqlToFitOrder($orderInfo->getId()),
            "PaymentDate" => $this->brlTime(),
            "SearchProtocol" => $pixInfo['data']['SearchProtocol'],
        ];
//        // 有留言，才写上
        if (isset($orderInfo->user_message)) {
            $requestParam['CustomerMessage'] = $orderInfo->user_message;
        }

        $this->pix_out = $responseData = $this->postRequestJsonForAuth('GeneratePixOut', $requestParam);
        if (!is_array($responseData) || !$responseData) {
            $this->errorCode = -21;
            $this->errorMessage = '响应内容有误：超时';
            return false;
        }
        //   exit;
        if ($responseData['Success'] != 'true') {
            $this->errorCode = 22;
            $this->errorMessage = 'Success 响应内容有误：' . $responseData['Message'];
            return false;
        }

        if (!isset($responseData['DocumentNumber'])) {
            $this->errorCode = -24;
            $this->errorMessage = 'DocumentNumber 不存在';
            return false;
        }
        $this->bank_order_id = $responseData['DocumentNumber'];
        return true;
    }


    /**
     * 2.出款回掉
     * @param $callbackData array
     * @return array|bool
     */
    public function withdrawCallback($callbackData)
    {

//        $callbackData = json_decode($this->ParseNotifyData($request), true);

        if ($callbackData['Method'] != 'PixOut') {
            $this->errorCode = -101;
            $this->errorMessage = 'Method 有误';
            return false;
        }

        if (!isset($callbackData['Identifier']) || !isset($callbackData['DocumentNumber'])) {
            $this->errorCode = -102;
            $this->errorMessage = 'Identifier or DocumentNumber 参数不存在';
            return false;
        }
//        'orderid' => $callbackData['Identifier'],
//            'bank_order_id' => $callbackData['DocumentNumber'],
        $orderInfo = $this->getWithdrawOrderRepository()->getById($callbackData['Identifier']);
        if (!$orderInfo) {
            $this->errorCode = -21;
            $this->errorMessage = '订单不存在' . $callbackData['Identifier'];
            return false;
        }

        // 更新回掉的数据
        $orderInfo->updateNotifyInfo($callbackData);

        if ($orderInfo->status != WithdrawOrder::STATUS_REQUEST_SUCCESS) {
            $this->errorCode = -22;
            $this->errorMessage = '订单状态不对' . $orderInfo->status;
            return false;
        }

        if ($callbackData['Status'] == 'Cancel') {
            $this->errorCode = -23;
            $this->errorMessage = '失败原因:' . $callbackData['ErrorDescription'] ?? '';
            $orderInfo->updateNotifyFail($this->errorMessage);
            return false;
        }

        if ($callbackData['Status'] != 'Paid') {
            $this->errorCode = -24;
            $this->errorMessage = '支付失败';
            $orderInfo->updateNotifyFail($this->errorMessage);
            return false;
        }

        $orderInfo->updateToNotifySuccess();
        return true;


    }

    /************************************************* 下面是私钥方法 ****************************************************/

    /**
     * 请求 fit 的订单号
     * @param $order_id
     * @return string
     */
    private function sqlToFitOrder($order_id)
    {
        return self::PREFIX_ORDER_ID . $order_id;
    }

    private function getPixInfo($pixKey)
    {
        $requestParam = [
            'Method' => 'GetInfosPixKey',
            'PartnerId' => self::LIST_API_PARAM['partnerId'],
            'BusinessUnitId' => self::LIST_API_PARAM['businessUnitId'],
            'TaxNumber' => self::LIST_API_PARAM['taxNumberRaw'],
            'PixKey' => $pixKey,
            'PixKeyType' => 0,
        ];

        $responseData = $this->postRequestJsonForAuth('GetInfosPixKey', $requestParam);

        //if not array or empty or not a array
        if (!is_array($responseData) || empty($responseData)) {
            return [
                'status' => 'error',
                'data' => [],
                'msg' => 'Failed to retrieve PixKey information: Invalid response data',
            ];
        }

        if (!isset($responseData['Infos'])) {
            return [
                'status' => 'error',
                'data' => [],
                'msg' => 'Failed PixKey information:' . $responseData['Message'],
            ];
        }

        if ($responseData['Success'] === 'true') {
            $infos = $responseData['Infos'];
            $infos['SearchProtocol'] = $responseData['SearchProtocol'];
            return [
                'status' => 'success',
                'data' => $infos,
                'msg' => 'Retrieved PixKey information successfully',
            ];
        } else {
            return [
                'status' => 'error',
                'data' => [],
                'msg' => 'Failed to retrieve PixKey information: ' . $responseData['Message'],
            ];
        }
    }


    private function postRequestJsonForAuth($path, $data)
    {
        $url = self::LIST_API_PARAM['baseUrl'] . $path;


        $startTime = microtime(true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type:application/json',
            'authorization:Basic ' . base64_encode(self::LIST_API_PARAM['username'] . ':' . self::LIST_API_PARAM['password']),
        ]);
        $result = curl_exec($ch);

        $endTime = microtime(true);
        if ($result === false) {
            $curlInfo = curl_getinfo($ch);
//            $this->redisCacheLogs('response', $curlInfo);
//            $this->redisCacheLogs('time_cost', [
//                'start_time' => $startTime,
//                'end_time' => $endTime,
//            ]);

            return false;
        }
        curl_close($ch);
        $resultFinal = json_decode($result, true);
        $endTime = microtime(true);

        return $resultFinal;
    }


    private function ParseNotifyData($data)
    {
        $temp = stripslashes($data);
        $temp = str_replace('"{"', '{"', $temp);
        $temp = str_replace('}"', '}', $temp);
        return $temp;
    }


    private function brlTime()
    {
        $beijingTime = date('Y/m/d  H:i:s', time());
        $dateTime = new \DateTime($beijingTime, new \DateTimeZone('Asia/Shanghai'));
        $dateTime->setTimezone(new \DateTimeZone('America/Sao_Paulo'));
        $brazilTime = $dateTime->format('Y/m/d');
        return $brazilTime;
    }


    const LIST_CHANNEL = [
        'deposit_proxy_id' => 76,
        'transfer_proxy_id' => 77,
        'channel_code' => 'fitbank',
        'bank_open' => 10,
    ];

    const LIST_PIX_TYPE = [
        1 => 'CPF',
        2 => 'EMAIL',
        3 => 'PHONE',
        4 => 'EVP',
        5 => 'CNPJ',
    ];

    const LIST_API_PARAM = [
        'baseUrl' => 'https://apiv2.payments.fitbank.com.br/main/execute/',
        // 新的
        'username' => 'b58a5700-68dd-4ca3-8820-b0800d8fed87',
        'password' => '0b724ca3-c523-4805-882b-366af67e0d7a',


        'partnerId' => 1532,
        'businessUnitId' => 1599,
        'taxNumber' => '45.707.166/0001-80',
        'pixKey' => '0dc290b3-44b5-4e2c-921c-92216f01e9b7',
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
    ];

}
