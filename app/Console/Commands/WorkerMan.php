<?php

namespace App\Console\Commands;

use PHPSocketIO\SocketIO;
use Workerman\Lib\Timer;
use Workerman\Worker;

use Workerman\Connection\AsyncTcpConnection;


/**
 * Class WorkerMan
 * @package App\Console\Commands\Plan
 */
class WorkerMan extends BaseCommand
{

    /**
     *停止失败
     * https://www.bookstack.cn/read/workerman-manual/faq-stop-fail.md
     * The name and signature of the console command.
     * php artisan  workman  start
     * php artisan  workman  restart
     * php artisan  workman  status
     * php artisan  workman  stop
     * @var string
     */
    protected $signature = 'workman {action} {--d}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '开启worker man监听要处理的uid';

    // worker man 开启的进程数
    const WORKER_MAN_COUNT = 5;
    // @var int 每次要处理的uid数量

    const VIM_TG = -1002163291807; //越南tg

    const INDIA_TG = -1002172689012; // 印度tg

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return bool|string|void
     */
    public function handle()
    {
        echo getTimeString();// 记录重启时间

        global $argv;
        $action = $this->argument('action');
        $argv[0] = 'wk';
        $argv[1] = $action;
        $argv[2] = $this->option('d') ? '-d' : '';

        $worker = new Worker();
        // 服务名称.
        $worker->name = 'laravel timer';
        // 启动多少个进程数量，这里大家灵活配置，可以参考workerman的文档.

        $worker->count = self::WORKER_MAN_COUNT;
        // 当workerman的进程启动时的回调方法.
        $worker->onWorkerStart = function ($worker) {

            if ($worker->id == 1) {
                $this->smsReceive(); // 这个只处理一次即可
            }

            if ($worker->id == 2) {
                $this->smsReceiveVim(); // 这个只处理一次即可 越南短信
            }

        };


        Worker::runAll();
    }


    private function smsReceive()
    {
        /********************* ws start *********************/
        $connection = new AsyncTcpConnection('wss://stream.pushbullet.com/websocket/o.uMDYf8vJgLrGHzvEDwJSGE5xaTjCj1Y2');

        $connection->transport = 'ssl'; // 设置连接为 SSL

        $connection->onConnect = function ($connection) {
            echo "sms start   " . date('Y-m-d H:i:s') . "\n";
//                $connection->send('Hello, server!');
        };

        $connection->onMessage = function ($connection, $data) {
            $this->dealSmsData($data);
        };

        $connection->onClose = function ($connection) {
            // 如果手机关掉了，这个是会关闭的
            echo "sms end \n" . date('Y-m-d H:i:s') . "\n";
            return $this->smsReceive();
        };

        $connection->connect();
        /********************* ws end *********************/
    }

    /**
     * 处理短信数据
     * @param $response
     * @return array|bool
     */
    private function dealSmsData($response)
    {
        date_default_timezone_set('PRC'); // 设置北京时间
        $response = json_decode($response, true);
        if ($response['type'] != 'push') {
            $this->errorMessage = '不是推送';
            return false;
        }
        if (!isset($response['push']['type'])) {
            $this->errorMessage = '参数有误';
            return false;
        }

        if ($response['push']['type'] != 'sms_changed') {
            $this->errorMessage = '不是短信';
            return false;
        }

        if (!isset($response['push']['notifications'])) {
            $this->errorMessage = '通知消息有误';
            return false;
        }
//        logToMe('wss receive', ['notifications' => $response['push']['notifications']]);
        // 一般都是1条短信，但也有时候会出现两条
        foreach ($response['push']['notifications'] as $notification) {
            $this->singleSms($notification);
        }
        return [];
    }

    /**
     * 记录单条短信
     * @param $notification
     * @return bool
     */
    private function singleSms($notification)
    {
        if (isset($notification['body']) && isset($notification['timestamp']) && isset($notification['title'])) {
            logToResponse(date('Y-m-d H:i:s') . $notification['body'], date('Y-m-d') . '.txt');
//            logToMe('sms receive-detail', [
//                'body' => $notification['body'],
//                'timestamp' => $notification['timestamp'],
//                'title' => $notification['title'],
//            ]);
            /**
             * @var $repository \App\Repository\TelegramRepository
             */
            $repository = app('App\Repository\TelegramRepository');
            $response_text = $notification['body'];
            $repository->replayMessage(self::INDIA_TG, $response_text);
        }
        return true;
    }


    /******************** 越南短信 start ****************/

    private function smsReceiveVim()
    {
        /********************* ws start *********************/
        $connection = new AsyncTcpConnection('wss://stream.pushbullet.com/websocket/o.B25tZ7hN3ygDY4XDEeYu1CwvPqe0hPrZ');

        $connection->transport = 'ssl'; // 设置连接为 SSL

        $connection->onConnect = function ($connection) {
            echo "sms start   " . date('Y-m-d H:i:s') . "\n";
//                $connection->send('Hello, server!');
        };

        $connection->onMessage = function ($connection, $data) {
            $this->dealSmsDataVim($data);
        };

        $connection->onClose = function ($connection) {
            // 如果手机关掉了，这个是会关闭的
            echo "sms end \n" . date('Y-m-d H:i:s') . "\n";
            return $this->smsReceiveVim();
        };

        $connection->connect();
        /********************* ws end *********************/
    }

    /**
     * 处理短信数据
     * @param $response
     * @return array|bool
     */
    private function dealSmsDataVim($response)
    {
        date_default_timezone_set('PRC'); // 设置北京时间
        $response = json_decode($response, true);
        if ($response['type'] != 'push') {
            $this->errorMessage = '不是推送';
            return false;
        }
        if (!isset($response['push']['type'])) {
            $this->errorMessage = '参数有误';
            return false;
        }

        if ($response['push']['type'] != 'sms_changed') {
            $this->errorMessage = '不是短信';
            return false;
        }

        if (!isset($response['push']['notifications'])) {
            $this->errorMessage = '通知消息有误';
            return false;
        }
//        logToMe('wss receive', ['notifications' => $response['push']['notifications']]);
        // 一般都是1条短信，但也有时候会出现两条
        foreach ($response['push']['notifications'] as $notification) {
            $this->singleSmsVim($notification);
        }
        return [];
    }

    /**
     * 记录单条短信
     * @param $notification
     * @return bool
     */
    private function singleSmsVim($notification)
    {
        if (isset($notification['body']) && isset($notification['timestamp']) && isset($notification['title'])) {
            logToResponse(date('Y-m-d H:i:s') . $notification['body'], date('Y-m-d') . 'vim.txt');
//            logToMe('sms receive-detail', [
//                'body' => $notification['body'],
//                'timestamp' => $notification['timestamp'],
//                'title' => $notification['title'],
//            ]);
            /**
             * @var $repository \App\Repository\TelegramRepository
             */
            $repository = app('App\Repository\TelegramRepository');
            $response_text = $notification['body'];
            $repository->replayMessage(self::VIM_TG, $response_text);

            // 通知支付成功
//            $this->successToVim($response_text);
        }
        return true;
    }


    /**
     * 匹配数据，并且通知 支付平台
     * @param $entry string
     * @return bool
     */
    private function successToVim($entry)
    {
        // 匹配金额和编号
        if (
//            preg_match('/\(TPBank\):.*?PS:\+([\d.]+)VND.*?ND:\s*(\d{8})/s', $entry, $matches)
//            ||
//            preg_match('/\+?([\d,]+)VND.*?ND:\s*(\d{8})/s', $entry, $matches)// 这个必须放在第一位 9.1号添加
//            ||
//            preg_match('/\+([\d,]+)VND.*?(\b\d{8})\b/', $entry, $matches) // 1,2,3
//            ||
//            preg_match('/\+?([\d.]+)VND.*?ND:\s*(\d{8,})/s', $entry, $matches) // 4 测试
//            ||
//            preg_match('/PS:\+([\d,]+).*?ND:\s*QR\s*-\s*(\d{8})/s', $entry, $matches) // 5
//            ||
//            preg_match('/\+?([\d,]+)VND.*?ND:\s*(\d{8})/s', $entry, $matches)// 6

            // 以上是  9.2 之前的，下面是  9.2 之后的
            preg_match('/\+?([\d,]+)VND.*?ND:\s*(\d{8}\b)/s', $entry, $matches)// 这个必须放在第一位
            ||
            preg_match('/\+?([\d,]+)VND.*?_(\d{8}\b)$/', $entry, $matches)//
            ||
            preg_match('/\+([\d,]+)VND.*?(\b\d{8})\b/', $entry, $matches) // 1,2,3
            ||
            preg_match('/\+?([\d.]+)VND.*?ND:\s*(\d{8,}\b)/s', $entry, $matches) // 4 测试
            ||
            preg_match('/PS:\+([\d,]+).*?ND:\s*QR\s*-\s*(\d{8}\b)/s', $entry, $matches) // 5
        ) {
            $amount = isset($matches[1]) ? $matches[1] : '';
            $amount = str_replace([',', '.'], '', $amount);
            $id = isset($matches[2]) ? $matches[2] : '';

            logToResponse(date('Y-m-d H:i:s') . '  ' . $id . '  ' . $amount, date('Y-m-d') . 'vim_success.txt');

            curlPost('https://bugaq.com/api/VimNotify/receive_sms', [
                'amount_real_pay' => $amount,
                'order_id' => $id,
                'sign' => 'sdsasdsdfdsfdgrcfsvf'
            ]);
        } else {
            logToResponse(date('Y-m-d H:i:s') . $entry, date('Y-m-d') . 'vim_error.txt');
        }
        return true;
    }
    /******************** 越南短信 end ****************/


}
