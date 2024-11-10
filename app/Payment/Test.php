<?php

namespace app\api\payment;

use app\admin\model\Order as OrderModel;
use app\admin\model\Pix as PixModel;
use think\facade\Cache;
use app\api\model\Request as RequestModel;


// Chave pix:
// 49.440.778/0001-00
// UUID
// 67363858-a26a-43b7-94ab-23db6002aada
// API Key
// QfjxIcQ-iLdQ-Oupzxv-sLpn-NNDky2dD
// API Secret
// kns-rPPJX7qtS-aMY4-1V7WS-uVyHSuOoz2-arWe-CyGFpESp
// API文档
// https://api.pixease.reset-bank.com/api

// 说明说明
// 有了uuid，就可以请求创建 qrcode
// 金额应为整数，例如 100 表示 1.00 雷亚尔
// 如何创建签名
// https://api.pixease.reset-bank.com/how-to-sign

// 只有带有挂锁图标的API才需要 apikey
// 所以
// 存款，不需要 apikey，只需要 user_account_uuid
// 提款，需要 apikey + secret 来请求

// FIT代收 proxy_id = 76
// FIT代付 proxy_id = 77

// 商户类型 ： bank_lx=10

// cd_order.bank_open=10
class Reset extends paymentHandle implements paymentInterface
{
    var $channelInfo = [
        'deposit_proxy_id' => 171,
        'transfer_proxy_id' => 172,
        'channel_code' => 'reset',
        'bank_open' => 13,
    ];

    public $access_token;

    var $transferTypeMap = [
        1 => 'CPF',
        2 => 'EMAIL',
        3 => 'PHONE',
        4 => 'EVP',
        5 => 'CNPJ',
    ];

    var $apiParam = [
        'baseUrl' => 'https://api.pixease.reset-bank.com/',//主域名

        // 新的
        'username' => 'QfjxIcQ-iLdQ-Oupzxv-sLpn-NNDky2dD', //API Key
        'password' => 'kns-rPPJX7qtS-aMY4-1V7WS-uVyHSuOoz2-arWe-CyGFpESp', //API Secret

        'uuid' => '67363858-a26a-43b7-94ab-23db6002aada',

        'partnerId' => 1532,
        'businessUnitId' => 1599,
        'taxNumber' => '49.440.778/0001-00',
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

    public function deposit($orderInfo)
    {

        // $requestParam = [
        //     'Method' => "GenerateDynamicPixQRCode",
        //     'Address' => $this->apiParam['address'],
        //     'PartnerId' => $this->apiParam['partnerId'],
        //     'BusinessUnitId' => $this->apiParam['businessUnitId'],
        //     'PixKey' => $this->apiParam['pixKey'],
        //     'TaxNumber' => $this->apiParam['taxNumberRaw'],
        //     'PixKeyType' => 1,
        //     'Bank' => $this->apiParam['bankInfo']['Bank'],
        //     'BankBranch' => $this->apiParam['bankInfo']['BankBranch'],
        //     'BankAccount' => $this->apiParam['bankInfo']['BankAccount'],
        //     'BankAccountDigit' => $this->apiParam['bankInfo']['BankAccountDigit'],
        //     'ChangeType' => 0,
        //     'PrincipalValue' => $orderInfo['request']['Amount'],
        //     'ExpirationDate' => $this->brlTimePixIn(),
        //     'Identifier' => $this->generatOrderNumber([
        //         'merchant_id' => $orderInfo['request']['MerchantID'],
        //         'order_number' => $orderInfo['request']['OrderID'],
        //     ]),
        // ];

        // if ($orderInfo['request']['Amount'] > 5000) {
        //     $requestParam['PayerTaxNumber'] = "37119774808";
        // }
        //{
//   "amount": 100,
//   "customer_uuid": "string",
//   "external_id": "string",
//   "additional_info": "string",
//   "expiration": 30
// }'
        $requestParam = [
            'amount' => $orderInfo['request']['Amount']  * 100,
            // 'customer_uuid' => $this->apiParam['uuid'],
            'external_id' => $this->generatOrderNumber([
                'merchant_id' => $orderInfo['request']['MerchantID'],
                'order_number' => $orderInfo['request']['OrderID'],
            ]),
            // 'additional_info' => '',
            'expiration' => 60 * 24,//1天
        ];

        $responseData = $this->postRequestJsonForAuth('deposit/create/'.$this->apiParam['uuid'], $requestParam);

        if ($responseData['status'] != 'WAITING_PAYMENT') {
            return [
                'code' => 500,
                'msg' => '响应内容有误：' . $responseData['message'],
                'channel_info' => $this->channelInfo,
            ];
        }
        if ($responseData['qr_code_text'] == '') {
            return [
                'code' => 500,
                'msg' => '响应内容有误：' . $responseData['message'],
                'channel_info' => $this->channelInfo,
            ];
        }

        return [
            'code' => 0,
            'msg' => '下单成功',
            'deposit_url' => $responseData['qr_code_text'],
            'top_order_id' => $responseData['uuid'],
            'tag_id' => $responseData['txid'] ?? $responseData['uuid'],
            'channel_info' => $this->channelInfo,
            'return_type' => self::RETURN_TYPE_FOR_QR,
        ];
    }

    public function transfer($orderInfo)
    {

        if (!isset($this->apiParam['transferCode'][$this->transferTypeMap[$orderInfo['Type']]])) {
            return [
                'code' => 500,
                'msg' => '不支持的付款方式:' . $orderInfo['Type'],
                'channel_info' => $this->channelInfo,
            ];
        }

        // 先查询库
        $has_sql = false; // 数据库是否存在   不要重复插入
        $pix_info = PixModel::where('account', '=', $orderInfo['Account'])->find();
        // var_dump($pix_info);die;
        if ($pix_info) {
            $has_sql = true;
            $pix_status = $pix_info['status'];
            if ($pix_status && $pix_status != PixModel::STATUS_SUCCESS) {
                return [
                    'code' => 500,
                    'msg' => "pix 查询错误:::" . $pix_info['remark'],
                    'channel_info' => $this->channelInfo,
                ];
            }
        }

        // 上面没有失败的，处理新的查询
        $pixInfo = $this->getPixInfo($orderInfo['Account']);

        if ($pixInfo['status'] != 'success') {
            if (!$has_sql) {
                // 插入
                try {
                    // 这条是央行屏蔽
                    $res_msg = $pixInfo['msg'];
                    $str = 'Failed PixKey information:EXC0027 - Não foi possível validar a chave Pix enviada';
                    $str2 = 'Failed PixKey information:EXC0025 - Ocorreu um erro ao realizar a consulta da Chave PIX. Tente novamente mais tarde.';
                    $str3 = 'Failed PixKey information:Houve um erro no sistema. Tente novamente mais tarde';
                    $str4 = 'Failed to retrieve PixKey information: Invalid response data';
                    if (in_array($res_msg, [$str3, $str, $str2, $str4])) {
                        return [
                            'code' => 500,
                            'msg' => "pix 查询错误:" . $pixInfo['msg'],
                            'channel_info' => $this->channelInfo,
                        ];
                    }
                    // 这条是PIX查询错误
                    //  $str = 'Failed PixKey information:EXC0027 - Chave PIX inexistente.';
                    PixModel::create([
                        'account' => $orderInfo['Account'],
                        'status' => PixModel::STATUS_BLOCK,
                        'remark' => $pixInfo['msg'],
                        'content' => json_encode($pixInfo),
                        'add_time' => time(),
                    ]);
                } catch (\Exception $exception) {

                }
            }
            return [
                'code' => 500,
                'msg' => "pix 查询错误:" . $pixInfo['msg'],
                'channel_info' => $this->channelInfo,
            ];
        }

        // 如果是邮箱
        if (filter_var($orderInfo['Account'], FILTER_VALIDATE_EMAIL)) {
            $orderInfo['Account'] = lcfirst($orderInfo['Account']);
        }


        if ($pixInfo['data']['key'] != $orderInfo['Account']) {
//                if (str_replace('+55', '', $pixInfo['data']['PixKeyValue']) != $orderInfo['Account']) {
            if (!$has_sql) {
                // 插入
                try {
                    PixModel::create([
                        'account' => $orderInfo['Account'],
                        'status' => PixModel::STATUS_WRONG_ACCOUNT,
                        'remark' => '出款账户不一致' . $pixInfo['msg'],
                        'content' => json_encode($pixInfo),
                        'add_time' => time(),
                    ]);
                } catch (\Exception $exception) {
                }
            }
            return [
                'code' => 500,
                'msg' => "出款账户不一致",
                'channel_info' => $this->channelInfo,
            ];
//                }
        }



        $requestParam = [
            "amount" => $orderInfo['Amount'] * 100,
            "pixkey" => $pixInfo['data']['key'],
            'bank_data' => [
                'name' => $pixInfo['data']['owner']['name'],
                'cpf_cnpj' => $pixInfo['data']['owner']['cpf_cnpj'],
                'ispb' => $pixInfo[''],
                'branch' => $pixInfo['data']['account']['branch'],
                'account' => $pixInfo['data']['account'][''],

            ],

            'external_id' => $this->generatOrderNumber([
                'merchant_id' => $orderInfo['MerchantID'],
                'order_number' => $orderInfo['OrderID'],
            ]),
        ];

        $responseData = $this->postRequestJsonForAuth('withdrawal/create/'.$this->apiParam['uuid'], $requestParam);

        if (!is_array($responseData) || !$responseData) {
            return [
                'code' => 400,
                'top_order_id' => 'reset_timeout_' . time(),
                'msg' => '响应内容有误：超时',
                'channel_info' => $this->channelInfo,
            ];
        }

        if (!isset($responseData['status'])) {
            return [
                'code' => 500,
                'msg' => '响应内容有误：' . $responseData['message'],
                'channel_info' => $this->channelInfo,
            ];
        }

        if($responseData['statusCode'] == 400){
            return [
                'code' => 500,
                'msg' => '响应内容有误：' . $responseData['message'],
                'channel_info' => $this->channelInfo,
            ];
        }


        if ($responseData['code'] != '') {
            return [
                'top_order_id' => $responseData['code'],
                'msg' => '请求成功',
                'code' => 0,
                'channel_info' => $this->channelInfo,
                'raw' => $responseData,
            ];
        }

        return [
            'top_order_id' => 'no_tx_id_'. time(),
            'msg' => '请求成功',
            'code' => 400,
            'channel_info' => $this->channelInfo,
            'raw' => $responseData,
        ];



    }


    public function orderQuery()
    {

    }

    //callbac
    public function depositCallback()
    {

        TgBotMessage('reset deposit notify: post from-data'. json_encode($this->getPostFormData(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        TgBotMessage('reset deposit notify: json'. $this->getPostRawData());

        // die;

        if (empty($this->getPostRawData()) || $this->getPostRawData() == '') {
            echo '{ "Success":true, "Message": "Operação realizada com sucesso" }';
            exit;
            return [
                'code' => 500,
                'msg' => "no callback data",
                'status' => -1,
            ];
        }

        $callbackData = json_decode($this->getPostRawData(), true);
        $callbackData = $callbackData['event'];

        if ($callbackData['method'] != 'PIX') {
            echo '{ "Success":true, "Message": "Operação realizada com sucesso" }';
            exit;
            return [];
        }


        if (!isset($callbackData['externalid'])) {
            echo '{ "Success":true, "Message": "Operação realizada com sucesso" }';
            exit;
            return [
                'code' => 500,
                'msg' => "Identifier or DocumentNumber not found",
            ];
        }
        // TgBotMessage('新订单解析：'.$callbackData['QRCodeInfos']['Identifier']."\r\n".$this->parseOrderNumber($callbackData['QRCodeInfos']['Identifier']));
        $this->redisCacheCallback($this->parseOrderNumber($callbackData['externalid']), $callbackData);
        // $lockData = [
        //     'dis_order_no' => $callbackData['externalid']['DocumentNumber'],
        //     'order_no' => $this->parseOrderNumber($callbackData['externalid']),
        // ];
        // if (isset($lockData['dis_order_no']) && $lockData['dis_order_no'] != '') {
        //     $redisInstance = new \think\cache\driver\Redis(config('cache.stores.redis'));
        //     if (!$redisInstance->setnx('deposit_callback_lock:' . $lockData['dis_order_no'], $lockData['dis_order_no'],
        //         15)) {
        //         // TgBotMessage('fitbank:代收回调锁定:'.$lockData['dis_order_no']);
        //         echo '{ "Success": true, "Message": "Operação realizada com sucesso" }';
        //         exit;
        //         return [
        //             'code' => 500,
        //             'msg' => 'callback locked',
        //         ];
        //     }
        // }

        // if (isset($callbackData['ReceiptUrl'])) {
        //     // $this->redisCacheSet('receipt:' . $this->parseOrderNumber($callbackData['QRCodeInfos']['Identifier']),
        //     //     $callbackData['ReceiptUrl'],
        //     //     60 * 60 * 24 * 7);

        //     try {
        //         RequestModel::where('order_id', '=', $this->parseOrderNumber($callbackData['QRCodeInfos']['Identifier']))->update(['receipt'=> $callbackData['ReceiptUrl']]);
        //     } catch (\Exception $e) {

        //     }
        // }

        // if (isset($callbackData['DocumentNumber'])) {
        // $this->redisCacheSet('documentNumber:' . $this->parseOrderNumber($callbackData['QRCodeInfos']['Identifier']),
        //     $callbackData['DocumentNumber'], 60 * 60 * 24 * 15);
        // }


        $orderModel = OrderModel::where([
            'orderid' => $this->parseOrderNumber($callbackData['externalid']),
            'sf_id' => $callbackData['txid'],
        ])->find();

        if (!$orderModel) {
            echo '{ "Success": true, "Message": "Operação realizada com sucesso" }';
            exit;
            return [
                'code' => 500,
                'msg' => 'order not exist',
            ];
        }

        if ($orderModel['status'] != 2) {
            echo '{ "Success": true, "Message": "Operação realizada com sucesso" }';
            exit;
            return [
                'code' => 500,
                'msg' => 'Paid',
            ];
        }

        if (strtolower($callbackData['status']) == 'paid') {
            return [
                'code' => 0,
                'order' => $orderModel,
                'tag_id' => $callbackData['pix_end2end_id'] ?? '',
                'DocumentNumber' => $callbackData['code'] ?? '',
            ];
        }


    }

    public function transferCallback()
    {
        // TgBotMessage('reset transferCallback notify: post from-data'. json_encode($this->getPostFormData()));
        TgBotMessage('reset transferCallback notify: json'. $this->getPostRawData());



        //{
        //     "event": {
        //         "uuid": "7df8ed95-0668-47aa-b0c8-29fb0db990cf",
        //         "useraccountuuid": "67363858-a26a-43b7-94ab-23db6002aada",
        //         "amount": "200",
        //         "status": "NOFUND",
        //         "code": "7df8ed95-0668-47aa-b0c8-29fb0db990cf",
        //         "method": "PIXOUT",
        //         "txid": "",
        //         "type": "BANKDATA",
        //         "pixkey": "",
        //         "bankdata": {
        //             "name": null,
        //             "cpfcnpj": null,
        //             "ispb": null,
        //             "branch": null,
        //             "account": null
        //         },
        //         "externalid": "80FY1731069566",
        //         "payeeuuid": "",
        //         "pixend2endid": "",
        //         "createdat": "2024-11-08T12:39:28.712Z",
        //         "updatedat": "2024-11-08T12:39:30.818Z",
        //         "voucherurl": "https://pay.reset-bank.com/withdrawal/7df8ed95-0668-47aa-b0c8-29fb0db990cf"
        //     }
        // }

        // die;
        if (empty($this->getPostRawData()) || $this->getPostRawData() == '') {
            echo '{ "Success":true, "Message": "Operação realizada com sucesso" }';
            exit;
            return [
                'code' => 500,
                'msg' => "no callback data",
                'status' => -1,
            ];
        }

        $callbackData = json_decode($this->getPostRawData(), true);
        $callbackData = $callbackData['event'];


        if ($callbackData['method'] != 'PIXOUT') {
            echo '{ "Success":true, "Message": "Operação realizada com sucesso" }';
            exit;
            return [];
        }

        if (!isset($callbackData['externalid']) || !isset($callbackData['code'])) {
            echo '{ "Success": true, "Message": "Operação realizada com sucesso" }';
            exit;
            return [
                'code' => 500,
                'msg' => "Identifier or DocumentNumber not found",
            ];
        }

        // 检查是不是批量的订单
        // if (substr($callbackData['externalid'], 0, 4) == 'dcat') {
        //     post('https://test107.hulinb.com/api/withdraw/notify', $callbackData);
        //     echo '{ "Success": true, "Message": "Operação realizada com sucesso" }';
        //     exit;
        // }
        if (isset($callbackData['voucherurl']) && $callbackData['voucherurl'] != '') {
            // $this->redisCacheSet('receipt:' . $callbackData['Identifier'], $callbackData['ReceiptUrl'],
            //     60 * 60 * 24 * 5);

            try {
                $res = RequestModel::where('order_id', '=', $this->parseOrderNumber($callbackData['externalid']))->update(['receipt'=> $callbackData['voucherurl']]);

                if(!$res){
                    RequestModel::insert(['createtime'=>time(),'order_id'=> $this->parseOrderNumber($callbackData['externalid']),'receipt'=>$callbackData['voucherurl']]);
                }

            } catch (\Exception $e) {

            }
        }
        $this->redisCacheCallback($this->parseOrderNumber($callbackData['externalid']), $callbackData);
        // $this->redisCacheSet('callback_time:' . $callbackData['Identifier'], date('Y-m-d H:i:s', time()));
        $orderModel = OrderModel::where([
            'orderid' => $this->parseOrderNumber($callbackData['externalid']),
            'sf_id' => $callbackData['code'],
        ])->find();

        if (!$orderModel) {
            echo '{ "Success":true, "Message": "Operação realizada com sucesso" }';
            exit;
            return [
                'code' => 500,
                'msg' => 'order not exist',
                'status' => -1,
            ];
        }
        if ($orderModel['status'] == 1) {
            echo '{ "Success": true, "Message": "Operação realizada com sucesso" }';
            exit;

        }

        if ($orderModel['status'] != 2) {
            echo '{ "Success": true, "Message": "Operação realizada com sucesso" }';
            exit;
            return [
                'code' => 500,
                'msg' => 'success',
                'status' => -1,
            ];
        }
        //Withdrawal Status

        // WAITING_PAYMENT
        // PAID
        // SENDING
        // SENT
        // APROVED
        // NO_FUND 这个失败
        // WRONG_PIXKEY
        // REJECTED
        // FAIL
        // NEW 这个创建的时候 有的
        $status = $callbackData['status'];

        if (in_array($status, ['NO_FUND', 'WRONG_PIXKEY', 'REJECTED', 'FAIL'])) {
            return [
                'code' => 0,
                'order' => $orderModel,
                'status' => 0,
                'msg' => '失败原因:' . $callbackData['status'] ?? '',
                'channel_info' => $this->channelInfo,
            ];
        }

        if ($status == 'PAID' ) {
            return [
                'code' => 0,
                'order' => $orderModel,
                'status' => 1,
                'channel_info' => $this->channelInfo,
            ];
        }


    }

    protected function getPixInfo($pixKey)
    {

        $redisInstance = new \think\cache\driver\Redis(config('cache.stores.redis'));


        $this->access_token = $redisInstance->get('reset_access_token');

        $responseData = $this->postRequestJsonForAuth('withdrawal/check-key/'. $pixKey, [], false, false);

        //if not array or empty or not a array
        if (!is_array($responseData) || empty($responseData)) {
            return [
                'status' => 'error',
                'data' => [],
                'msg' => 'Failed to retrieve PixKey information: Invalid response data',
            ];
        }

        if (!isset($responseData['owner'])) {
            return [
                'status' => 'error',
                'data' => [],
                'msg' => 'Failed PixKey information:' . $responseData['message'],
            ];
        }

        return [
            'status' => 'success',
            'data' => $responseData,
            'msg' => 'Retrieved PixKey information successfully',
        ];

    }

    public function balance()
    {
        // https://api.pixease.reset-bank.com/statement/balance/{user_account_uuid}
        $redisInstance = new \think\cache\driver\Redis(config('cache.stores.redis'));
        $this->access_token = $redisInstance->get('reset_access_token');

        $responseData = $this->postRequestJsonForAuth('statement/balance/'.$this->apiParam['uuid'], [], false, false);


        if ($responseData && is_array($responseData)) {


            if (isset($responseData['balance'])) {
                return [
                    'status' => 'success',
                    'code' => 0,
                    'mainBalance' => [
                        'balance' => $responseData['balance'] / 100,
                        // 'block'=> $mainResponseData['BlockedBalance']
                    ],
                    'feeBalance' => [
                        'balance' => 0,
                        // 'block'=> $responseData['BlockedBalance']
                    ],
                ];
            }

            return [
                'code' => 500,
                'status' => 'error',
                'msg' => $responseData['message'],
            ];


        } else {
            return [
                'code' => 500,
                'status' => 'error',
                'msg' => $responseData['message'],
            ];
        }
    }

    public function orderQuerys($orderInfo)
    {

        switch ($orderInfo['init']) {
            case 0:
                return $this->queryDepositOrder($orderInfo);
                break;
            case 1:
                return $this->queryTransferOrder($orderInfo);
                break;
        }
    }

    protected function queryDepositOrder($orderInfo)
    {


        if ($orderInfo['status'] == 2) {
            return $this->queryDepositOrderForQR($orderInfo);
        }

        $documentNumber = $this->redisCacheGet('documentNumber:' . $orderInfo['orderid']);

        if ($documentNumber == '') {
            return [
                'code' => 500,
                'status' => 'error',
                'msg' => 'Order not found' . json_encode($orderInfo),
            ];
        }
        $request = [
            "Method" => "GetPixInById",
            "PartnerId" => $this->apiParam['partnerId'],
            "BusinessUnitId" => $this->apiParam['businessUnitId'],
            "DocumentNumber" => $documentNumber,
            "TaxNumber" => $this->apiParam['taxNumberRaw'],
            "Bank" => $this->apiParam['bankInfo']['Bank'],
            "BankBranch" => $this->apiParam['bankInfo']['BankBranch'],
            "BankAccount" => $this->apiParam['bankInfo']['BankAccount'],
            "BankAccountDigit" => $this->apiParam['bankInfo']['BankAccountDigit'],
        ];


        $responseData = $this->postRequestJsonForAuth('GetPixInById', $request);


        if (!is_array($responseData) || empty($responseData)) {
            return [
                'code' => 500,
                'status' => 'error',
                'msg' => 'Invalid response data',
            ];
        }

        if ($responseData['Success'] !== 'true') {
            return [
                'code' => 500,
                'status' => 'error',
                'msg' => $responseData['Message'],
            ];
        }

        if (count($responseData['Infos']) == 0) {
            return [
                'code' => 500,
                'status' => 'error',
                'msg' => 'Order not found',
            ];
        }

        return [
            'code' => 0,
            'status' => 'success',
            'data' => [
                'amount' => $responseData['Infos']['TotalValue'],
                'order_status' => $responseData['Infos']['Status'],
                'ReceiptUrl' => $responseData['Infos']['ReceiptValue'],
                'order_info_raw' => $responseData['Infos'],
            ],
        ];

    }

    protected function queryTransferOrder($orderInfo)
    {

        $requestParam = [
            'Method' => 'GetPixOutById',
            'PartnerId' => $this->apiParam['partnerId'],
            'BusinessUnitId' => $this->apiParam['businessUnitId'],
            'DocumentNumber' => $orderInfo['top_order_id'],
            'TaxNumber' => $this->apiParam['taxNumber'],
            'Bank' => $this->apiParam['bankInfo']['Bank'],
            'BankBranch' => $this->apiParam['bankInfo']['BankBranch'],
            'BankAccount' => $this->apiParam['bankInfo']['BankAccount'],
            'BankAccountDigit' => $this->apiParam['bankInfo']['BankAccountDigit'],
        ];


        $responseData = $this->postRequestJsonForAuth('GetPixOutById', $requestParam);

        if (!is_array($responseData) || empty($responseData)) {
            return [
                'code' => 500,
                'status' => 'error',
                'msg' => 'Invalid response data',
            ];
        }

        if ($responseData['Success'] !== 'true') {
            return [
                'code' => 500,
                'status' => 'error',
                'msg' => $responseData['Message'],
            ];
        }

        if (count($responseData['Infos']) == 0) {
            return [
                'code' => 500,
                'status' => 'error',
                'msg' => 'Order not found',
            ];
        }

        return [
            'code' => 0,
            'status' => 'success',
            'data' => [
                'orderid' => $responseData['Infos']['Identifier'],
                'top_order_id' => $responseData['Infos']['DocumentNumber'],
                'amount' => $responseData['Infos']['TotalValue'],
                'order_status' => $responseData['Infos']['Status'],
                'ReceiptUrl' => $responseData['Infos']['ReceiptValue'],
                'order_info_raw' => $responseData['Infos'],
            ],
        ];


    }

    protected function queryDepositOrderForQR($orderInfo)
    {

        $request = [
            "Method" => "GetPixQRCodeById",
            "PartnerId" => $this->apiParam['partnerId'],
            "BusinessUnitId" => $this->apiParam['businessUnitId'],
            "DocumentNumber" => $orderInfo['top_order_id'],
            "TaxNumber" => $this->apiParam['taxNumberRaw'],
        ];


        $responseData = $this->postRequestJsonForAuth('GetPixQRCodeById', $request);

        if (!is_array($responseData) || empty($responseData)) {
            return [
                'code' => 500,
                'status' => 'error',
                'msg' => 'Invalid response data',
            ];
        }

        if ($responseData['Success'] !== 'true') {
            return [
                'code' => 500,
                'status' => 'error',
                'msg' => $responseData['Message'] ?? 'no have',
            ];
        }

        if (count($responseData['GetPixQRCodeByIdInfo']) == 0) {
            return [
                'code' => 500,
                'status' => 'error',
                'msg' => 'Order not found',
            ];
        }

        return [
            'code' => 0,
            'status' => 'success',
            'data' => [
                'order_status' => $responseData['GetPixQRCodeByIdInfo']['Status'],
            ],
        ];

    }


    protected function postRequestJsonForAuth($path, $data, $is_log = true, $is_post = true)
    {
        $url = $this->apiParam['baseUrl'] . $path;

        //var_dump($path);
        // var_dump($this->access_token);

        $startTime = microtime(true);


        $apiKey = 'QfjxIcQ-iLdQ-Oupzxv-sLpn-NNDky2dD';
        $apiSecret = 'kns-rPPJX7qtS-aMY4-1V7WS-uVyHSuOoz2-arWe-CyGFpESp';
        //$path = '/deposit/create';
        $method = 'POST';
        $expires = time() + (10 * 60); // 10 minutes from now

        $body = []; // an empty array for body data
        $sign_data = json_encode($data); // JSON-encode the body data

        $dataToSign = $method . $path . $expires . $sign_data;
        $signature = hash_hmac('sha256', $dataToSign, $apiSecret);


        $ch = curl_init();
        //var_dump($is_post);
        curl_setopt($ch, CURLOPT_URL, $url);
        if($is_post){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }else{
            curl_setopt($ch, CURLOPT_POST, false);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $header = [
            'Content-Type:application/json',
            'accept: application/json',
            'api-key:'.$this->apiParam['username'],
            'api-signature:'.$signature,
            'api-expires:' . $expires,
        ];

        // if($path != 'withdrawal/create/67363858-a26a-43b7-94ab-23db6002aada'){
        $header[] =  'Authorization: Bearer ' . $this->access_token;
        //}

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $result = curl_exec($ch);

        $endTime = microtime(true);
        if ($result === false) {
            $curlInfo = curl_getinfo($ch);

            if($is_log){

                try {
                    if(self::$orderID){
                        RequestModel::insert([
                            'order_id' => self::$orderID,
                            'request' => json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'merchant' => json_encode(input('post.'), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                            'response' => json_encode($curlInfo, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'time_cost' => json_encode([
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                            ]),
                            'createtime' => time(),
                        ]);
                    }


                } catch (\Exception $e ) {

                }

            }


            return false;
        }
        curl_close($ch);
        $resultFinal = json_decode($result, true);
        $endTime = microtime(true);

        if($is_log){


            try {
                if(self::$orderID){

                    unset($resultFinal['UserAccount']['User']);

                    RequestModel::insert([
                        'order_id' => self::$orderID,
                        'request' => json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'merchant' => json_encode(input('post.'), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
                        'response' => json_encode($resultFinal, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'time_cost' => json_encode([
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                        ]),
                        'createtime' => time(),
                    ]);
                }


            } catch (\Exception $e ) {

            }

        }


        return $resultFinal;
    }




    function generatOrderNumber($orderInfo)
    {
        $prefix = $orderInfo['merchant_id'];
        $orderID = $orderInfo['order_number'];
        return $prefix . '__FY__' . $orderID;
    }

    // 解析订单号
    function parseOrderNumber($orderNumber)
    {
        // 1___FY__123456
        $parts = explode('__FY__', $orderNumber);
        if (count($parts) != 2) {
            return $orderNumber;
        }
        if ($parts[0] == '' || $parts[1] == '') {
            return $orderNumber;
        }
        return $parts[1];
    }

    function queryPix($pix)
    {
        $pixInfo = $this->getPixInfo($pix);
        if ($pixInfo['status'] != 'success') {
            return [
                'code' => 500,
                'msg' => "pix 查询错误:" . $pixInfo['msg'],
                'channel_info' => $this->channelInfo,
            ];
        }
        return [
            'code' => 0,
            'data' => $pixInfo['data'] ?? [],
        ];
    }




}
