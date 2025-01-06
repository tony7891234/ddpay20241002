<?php

namespace App\Payment;

use App\Models\WithdrawOrder;
use Illuminate\Support\Facades\Storage;

/**
 * Class VolutiPayment
 * @package App\Payment
 */
class VolutiPayment extends BasePayment implements InterFacePayment
{

    const CACHE_KEY = 'VolutiPayment12_';
    const CACHE_TIME = 100; // 单位是秒  缓存token时间

    /**
     * 1.出款请求
     * https://dev.iugu.com/reference/baas-pix-ted-out
     * @param $orderInfo WithdrawOrder
     * @return array|bool
     */
    public function withdrawRequest($orderInfo)
    {

//        if (!isset(self::LIST_API_PARAM['transferCode'][$orderInfo->pix_type])) {
//            $this->errorCode = -1;
//            $this->errorMessage = '不支持的付款方式:' . $orderInfo->pix_type;
//            return false;
//        }

        // 检查数据是否可以出款
        $validate = $this->validateWithdraw();
        if (!$validate) {
            return false;
        }


        $requestParam = [
            'pixKey' => $orderInfo['Account'],//账号
            'expiration' => 10000,
            'payment' => [
                'currency' => 'BRL',
                'amount' => floatval($orderInfo['Amount']),
            ],
        ];
//        // 有留言，才写上
        if (isset($orderInfo->user_message)) {
            $requestParam['description'] = $orderInfo->user_message; // Description of the transfer that will appear on the receipt
        }

        $this->pix_out = $responseData = $this->postRequestJsonForAuth('pix/payments/dict', $requestParam);

        if (!is_array($responseData) || !$responseData) {
            $this->errorCode = -21;
            $this->errorMessage = '响应内容有误：超时';
            return false;
        }
        //   exit;
        if (!isset($responseData['status']) || $responseData['status'] != 'PENDING') {
            $this->errorCode = 22;
            $this->errorMessage = 'Success 响应内容有误：' . $responseData['message'];
            return false;
        }

        if ($responseData['status'] == 'PENDING' && isset($responseData['endToEndId']) && $responseData['endToEndId'] != '') {
            $this->bank_order_id = $responseData['endToEndId'];
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

        logToMe('vol', $callbackData);
        // 订单id 和三方id
        if (!isset($callbackData['endToEndId'])) {
            $this->errorCode = -102;
            $this->errorMessage = 'endToEndId 参数不存在';
            return false;
        }
        $sf_id = $callbackData['endToEndId'];
        $orderInfo = $this->getWithdrawOrderRepository()->getBySfId($sf_id);
        if (!$orderInfo) {
            $this->errorCode = -21;
            $this->errorMessage = '订单不存在' . $sf_id;
            return false;
        }

        // 更新回掉的数据
        $orderInfo->updateNotifyInfo($callbackData);

        if ($orderInfo->status != WithdrawOrder::STATUS_REQUEST_SUCCESS) {
            $this->errorCode = -22;
            $this->errorMessage = '订单状态不对' . $orderInfo->status;
            return false;
        }

        if (in_array($callbackData['status'], ['CANCELED', 'REFUNDED', 'REJECTED', 'PARTIALLY_REFUNDED'])) {
            if (isset($callbackData['message'])) {
                $msg = $callbackData['message'];
            } elseif (isset($callbackData['errorMessage'])) {
                $msg = $callbackData['errorMessage'];
            } else {
                $msg = 'no';
            }
            $this->errorCode = -23;
            $this->errorMessage = '失败原因:' . $msg;
            $orderInfo->updateNotifyFail($this->errorMessage);
            return false;
        }

        if ($callbackData['status'] != 'LIQUIDATED') {
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
     * 获取token
     */
    protected function getAccessToken()
    {

        $token = \Cache::get(self::CACHE_KEY);

        if ($token) {
            return $token;
        }

        $params = [
            'clientId' => self::LIST_API_PARAM['df_username'],
            'clientSecret' => self::LIST_API_PARAM['df_password'],
            'grantType' => 'client_credentials',
        ];

        $responseData = $this->postRequestJsonForAuth('oauth/token', $params, false);
        // var_dump($params);
        // var_dump($responseData);die;
        $token = isset($responseData['access_token']) ? $responseData['access_token'] : '';

        if (!$token) {
            $token = isset($responseData['accessToken']) ? $responseData['accessToken'] : '';
        }

        \Cache::put(self::CACHE_KEY, $token, self::CACHE_TIME);

        return $token;

    }

    protected function postRequestJsonForAuth($path, $data, $is_post = true)
    {

        $url = self::LIST_API_PARAM['dfBaseUrl'] . $path;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        if ($is_post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, arrayToJson($data));
        } else {
            curl_setopt($ch, CURLOPT_POST, false);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $header = [
            'Content-Type:application/json',
            'accept: application/json',
        ];

        // 位置在 storage/app/pem/   其实是取值 filesystem.php 中的 local 配置
        $txt_crt = Storage::get('voluti/VOLUTI267_67.crt');
        $txt_key = Storage::get('voluti/VOLUTI267_67.key');

        curl_setopt($ch, CURLOPT_SSLCERT, $txt_crt);
        curl_setopt($ch, CURLOPT_SSLKEY, $txt_key);
        $idempotency_key = microtime(true) . uniqid('', true);
        $header[] = "x-idempotency-key: " . $idempotency_key;

        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

        if ($path != 'oauth/token') {
            $header[] = 'Authorization: Bearer ' . $this->getAccessToken();
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $result = curl_exec($ch);


        $errorMessage = $httpCode = '';
        // 检查是否发生错误
        if (curl_errno($ch)) {
            // 获取错误信息
            $errorMessage = curl_error($ch);
        } else {
            // 获取 HTTP 状态码
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        $arr = [
            '$this->getAccessToken()' => $this->getAccessToken(),
            '$txt_crt' => $txt_crt,
            '$txt_key' => $txt_key,
            '$result' => $result,
            '$errorMessage' => $errorMessage,
            '$httpCode' => $httpCode,
        ];
        logToMe('request', $arr);


        curl_close($ch);
        $resultFinal = json_decode($result, true);

        return $resultFinal;
    }


    //1 cpf 2 email 3 phone(需加巴西区号 例如+55) 4 EVP 5 CNPJ
    const LIST_PIX_TYPE = [
        1 => 'cpf',
        2 => 'email',
        3 => 'phone',
        4 => 'evp',
        5 => 'cnpj', //不支持的付款方式
    ];


    const LIST_API_PARAM = [

        'baseUrl' => 'https://api.pix.voluti.com.br/',//代收域名
        'dfBaseUrl' => 'https://accounts.voluti.com.br/api/v2/',//代付域名


        // 代收-正式
        'ds_username' => '00011195458076990000121',
        'ds_password' => 'DRiMmU2ZTYtNzhmNi00OWYzLTk5MTYtO',
        'pix_key' => 'f60c0e6b-c415-4251-a424-0de0657b0229',//代收要使用的

        // 代付
        'df_username' => '13e31f4c-83d2-46b2-b706-959dde8d6965',
        'df_password' => 'MJOxi0lkliRF6rGvDLS5IMF164qgFfcg',

//
//        // 代收-正式
//        'username' => '5bme27d6olo71kusd47gp50dd1',
//        'password' => '79ooe45ha6ns7su4meb9rt127lqduim2gut3c5k45l24d3c48mt',
//
//        'account_id' => '000090728',

//        'token' => '4980C39384D48CD5D65217AE577FF6F53F83A3648E7F8131F8DD60BBD012920A',
        'transferCode' => [
            'CPF' => '1',
            'PHONE' => '3',
            'EMAIL' => '2',
            'EVP' => '4',
            'CNPJ' => '5',
        ],
    ];
    const ERROR_LIST = [
        'ACCOUNTING' => 'Não foi possível bloquear o valor do pix',
        'ERR' => 'Erro ao comunicar confirmacao de pagamento.',
        '001' => 'Bucket vazio',
        '02' => 'Não foi possível consultar essa chave pix: {rq.ChavePix}',
        '03' => 'Conta Inativa',
        '01' => 'Não foi possível iniciar esse qrcode',
    ];


}
