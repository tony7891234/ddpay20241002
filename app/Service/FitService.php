<?php

namespace App\Service;


/**
 * Class FitService
 * @package App\Service
 */
class FitService extends BaseService
{


    public function balance($check_time)
    {
        $requestParam = [
            'Method' => 'GetAccountEntryPaged',
            'PartnerId' => self::ARR_API_PARAM['partnerId'],
            'BusinessUnitId' => self::ARR_API_PARAM['businessUnitId'],
            'TaxNumber' => self::ARR_API_PARAM['taxNumberRaw'],
            "DateTime" => $check_time,
            'OnlyBalance' => 'true',
        ];

        $mainResponseData = [];
        $responseData = $this->postRequestJsonForAuth('GetAccountEntryPaged', $requestParam);
        dump($responseData);
        if ($responseData['Success'] === 'true') {
            $pixBalanceMap = [
                'Bank' => self::ARR_API_PARAM['bankInfo']['Bank'],
                'BankBranch' => self::ARR_API_PARAM['bankInfo']['BankBranch'],
                'BankAccount' => self::ARR_API_PARAM['taxNumberRaw'],
                'BankAccountDigit' => 1,
            ];
            $requestParam = array_merge($requestParam, $pixBalanceMap);
            $mainResponseData = $this->postRequestJsonForAuth('GetAccountEntryPaged', $requestParam);
            if ($mainResponseData['Success'] === 'true') {
                return [
                    'status' => 'success',
                    'code' => 0,
                    'mainBalance' => [
                        'balance' => $mainResponseData['Balance'],
                        // 'block'=> $mainResponseData['BlockedBalance']
                    ],
                    'feeBalance' => [
                        'balance' => $responseData['Balance'],
                        // 'block'=> $responseData['BlockedBalance']
                    ],
                ];
            }
        }
        return [
            'balance' => $mainResponseData['Balance'], // 余额
            'fee' => $responseData['Balance'], // 手续费
        ];

    }

    protected function postRequestJsonForAuth($path, $data)
    {
        $url = self::ARR_API_PARAM['baseUrl'] . $path;

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
            'authorization:Basic ' . base64_encode(self::ARR_API_PARAM['username'] . ':' . self::ARR_API_PARAM['password']),
        ]);
        $result = curl_exec($ch);

        $endTime = microtime(true);
        if ($result === false) {
            $curlInfo = curl_getinfo($ch);

            return false;
        }
        curl_close($ch);
        $resultFinal = json_decode($result, true);
        $endTime = microtime(true);

        return $resultFinal;
    }


    const   ARR_API_PARAM = [
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
