<?php

namespace App\Console\Commands;


use App\Service\FitService;
use App\Traits\RepositoryTrait;

/**
 * Class Sync
 * @package App\Console\Commands
 */
class FitCommand extends BaseCommand
{

    use RepositoryTrait;

    /**
     * @var string
     */
    protected $signature = 'fit {action?}';


    /**
     * @var string
     */
    protected $description = 'fit 查询余额';

    /**
     * KG_Init constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * run
     */
    public function handle()
    {

        $action = $this->argument('action');

        if ($action == 'balance') {
            $this->checkFitBalance();
        } else {
            dump(11);
        }


    }

    private function checkFitBalance()
    {
        $service = new FitService();
        $check_time = getTimeString();
        $response = $service->balance($check_time);
        if (isset($response['balance']) && isset($response['fee'])) {
            $response_text = $check_time . '   余额:' . $response['balance'] . '   手续费:' . $response['fee'];
        } else {
            $response_text = '查询失败';
        }

        $chat_id = config('telegram.group.fit_balance');
        $this->getTelegramRepository()->replayMessage($chat_id, $response_text);

    }

//    private function getData()
//    {
//        $page = 3000;
//        while (true) {
//            $page++;
//            $response = $this->balance($page);
////            echo ($response['Entry']);
//            if (isset($response['Success']) && $response['Success'] == true) {
//                if ($response['Entry']) {
//                    foreach (json_decode($response['Entry'], true) as $info) {
//                        $EntryId = $info['EntryId'];
//
////                        dd($info);
//                        $check = FitModel::where('EntryId', '=', $EntryId)->first();
//                        if ($check) {
//                            continue;
//                        }
//                        $timestamp = strtotime($info['EntryDate']);
//                        $arr = [
//                            'EntryId' => $info['EntryId'],
//                            'create_at' => $timestamp,
//                            'create_date' => date('Y-m-d H:i:s', $timestamp),
//                            'content' => json_encode($info),
//                        ];
//                        FitModel::create($arr);
//                    }
//                }
//            }
//
//            if ($page > 3300) {
//                break;
//            }
//        }
//
//    }

//    public function balance($page)
//    {
//        $requestParam = [
//            'Method' => 'GetAccountEntryPaged',
//            'PartnerId' => $this->apiParam['partnerId'],
//            'BusinessUnitId' => $this->apiParam['businessUnitId'],
//            'TaxNumber' => $this->apiParam['taxNumberRaw'],
//            'StartDate' => '2024/10/10',
//            'EndDate' => '2024/10/11',
//            'PageSize' => 200,
//            'PageIndex' => $page,
//            'OnlyBalance' => 'false',
//        ];
////       $requestParam = [
////            'Method' => 'GetAccountEntryPaged',
////            'PartnerId' => $this->apiParam['partnerId'],
////            'BusinessUnitId' => $this->apiParam['businessUnitId'],
////            'TaxNumber' => $this->apiParam['taxNumberRaw'],
////            'StartDate' => '2024/04/20',
////            'EndDate' => '2024/05/20',
////            'PageSize' => 0,
////            'PageIndex' => 10,
////            'OnlyBalance' => 'true',
////        ];
//
//        $responseData = $this->postRequestJsonForAuth('GetAccountEntryPaged', $requestParam);
//
//
//        if ($responseData['Success'] === 'true') {
//
//            $pixBalanceMap = [
//                'Bank' => $this->apiParam['bankInfo']['Bank'],
//                'BankBranch' => $this->apiParam['bankInfo']['BankBranch'],
//                'BankAccount' => $this->apiParam['taxNumberRaw'],
//                'BankAccountDigit' => 1,
//            ];
//            $requestParam = array_merge($requestParam, $pixBalanceMap);
//            $mainResponseData = $this->postRequestJsonForAuth('GetAccountEntryPaged', $requestParam);
//            return $mainResponseData;
//        } else {
//            return [
//                'code' => 500,
//                'status' => 'error',
//                'msg' => $responseData['Message'],
//            ];
//        }
//    }

//    protected function postRequestJsonForAuth($path, $data)
//    {
//        $url = $this->apiParam['baseUrl'] . $path;
//
//        $startTime = microtime(true);
//
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_POST, true);
//        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, [
//            'Content-Type:application/json',
//            'authorization:Basic ' . base64_encode($this->apiParam['username'] . ':' . $this->apiParam['password']),
//        ]);
//        $result = curl_exec($ch);
//
//        $endTime = microtime(true);
//        if ($result === false) {
//            $curlInfo = curl_getinfo($ch);
//
//            return false;
//        }
//        curl_close($ch);
//        $resultFinal = json_decode($result, true);
//        $endTime = microtime(true);
//
//        return $resultFinal;
//    }


    var $apiParam = [
        'baseUrl' => 'https://apiv2.payments.fitbank.com.br/main/execute/',

        // 旧的
        // 'username'       => 'fb66c3d2-a877-40df-ad2c-38863a21b425',
        // 'password'       => '5d2385b7-6140-4bfd-aa1c-3c2590601992',
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
