<?php

namespace App\Console\Commands;


/**
 * Class T2Command
 * @package App\Console\Commands
 */
class T2Command extends BaseCommand
{

    const MAX_TIME = 5;// 超时多少秒，需要记录 log

    /**
     * @var string
     */
    protected $signature = 't2';


    /**
     * @var string
     */
    protected $description = '回调';

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
        dump(getTimeString());
        $this->notify();
        return true;
    }

    public function notify()
    {

        $webSiteUrl = 'http://task256.betvay.vip/index.php?r=';
        $password = md5('4545482258');
        $token = '&token=' . $password;

        $urlsWithParams = [];
        for ($i = 1; $i <= 500; $i++) {
            $urlsWithParams[$i] = $webSiteUrl . "settleBill/member" . $token . '&id=0';
            dump($webSiteUrl . "settleBill/member" . $token . '&id=0');
        }


        $this->curlPostMax($urlsWithParams);

    }


    private function curlPostMax($allGames)
    {
        //1 创建批处理cURL句柄
        $chHandle = curl_multi_init();
        $chArr = [];
        //2.创建多个cURL资源
        foreach ($allGames as $order_id => $notify_url) {
            $startTime = microtime(true);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $notify_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true); // 设置为 POST 请求
//            curl_setopt($ch, CURLOPT_POSTFIELDS, ($request_param)); // 设置 POST 数据
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36',
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.9',
                'Connection: keep-alive'
            ]);

            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_multi_add_handle($chHandle, $ch); //2 增加句柄
            $chArr[$order_id] = [
                'ch' => $ch,
                'startTime' => $startTime,
            ]; // 保存句柄以便后续使用
        }

        $running = null;
        do {
            curl_multi_exec($chHandle, $running); //3 执行批处理句柄
            curl_multi_select($chHandle); // 等待活动请求完成  可以不要
        } while ($running > 0);

        foreach ($chArr as $order_id => $ch_data) {
            $ch = $ch_data['ch'];
            $result = curl_multi_getcontent($ch); //5 获取句柄的返回值
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // 获取 HTTP 状态码
            if ($httpCode != 200) {
                dump('$httpCode--' . $order_id . '---' . $httpCode . '---' . $result);
            }
            $endTime = microtime(true); // 记录结束时间
//            if (in_array(strtolower($result), ['success', 'ok'])) {
//                $this->updateNotifyToSuccess($order_id);
//            } else {
//                $this->updateNotifyToFail($order_id);
//            }

//            $log_data = [
//                'request' => $request_param,
//                'notify' => $notify_url,
//                'result' => $result,
//                'diff_time' => $endTime - $ch_data['startTime'],
//            ];

//            dump($log_data);
            curl_multi_remove_handle($chHandle, $ch);//6 将$chHandle中的句柄移除
            curl_close($ch);
        }
        curl_multi_close($chHandle); //7 关闭全部句柄
    }


}
