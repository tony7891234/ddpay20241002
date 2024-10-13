<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Overtrue\EasySms\Traits\HasHttpRequest;
use Telegram\Bot\Api;

/**
 * 纸飞机
 *  -495238651   test  组
 *  523805746 xiaolong789
 *  -475445644  九鼎客服群
 * Class TelegramController
 * @package App\Http\Controllers\Web
 */
class TelegramController extends WebController
{
    use HasHttpRequest;
    const HOST_URL = 'https://admin';

    /**
     * @var string 订单号
     */
    private $order_id;

    /**
     * 使用这个之前 要先  removeWebHook()
     * 获取所有的聊天列表
     * @return array
     */
    public function getChatList()
    {
        $key = config('telegram.bot_token');
        return $this->get('https://api.telegram.org/bot' . $key . '/getUpdates');
    }

    /**
     * 开启机器人 web hook
     * @return \Telegram\Bot\TelegramResponse
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function createWebHook()
    {
        $key = config('telegram.bot_token');
        $telegram = new Api($key);
//        return $telegram->setWebhook(['url' => config('app.url') . '/telegram/webhook']);
        \Log::notice(self::HOST_URL . '/telegram/webhook');

        return $telegram->setWebhook(['url' => self::HOST_URL . '/telegram/webhook']);
    }

    /**
     * 关闭机器人 web hook
     * @return \Telegram\Bot\TelegramResponse
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function removeWebHook()
    {
        $key = config('telegram.bot_token');
        $telegram = new Api($key);
        return $telegram->removeWebhook();
    }


    /**
     * 接收发送给我的数据
     * @param TelegramService $telegramService
     */
    public function ListenWebHook(TelegramService $telegramService)
    {
        $response = $telegramService->listener();
        if (!$response) {
            \Log::notice($telegramService->getErrorMessage());
            //  不做记录了 不然日志太多了
            return false;
        }

        return true;

    }


}
