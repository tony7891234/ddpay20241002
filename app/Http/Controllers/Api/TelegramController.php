<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\WebController;
use App\Service\TelegramService;
use App\Traits\RepositoryTrait;
use Overtrue\EasySms\Traits\HasHttpRequest;
use Telegram\Bot\Api;

/**
 * 纸飞机
 *  523805746 xiaolong789
 * Class TelegramController
 * @package App\Http\Controllers\Web
 */
class TelegramController extends WebController
{

    use HasHttpRequest, RepositoryTrait;

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
        $key = config('telegram.bots.mybot.token');
        return $this->get('https://api.telegram.org/bot' . $key . '/getUpdates');
    }

    /** /api/createWebHook
     * 开启机器人 web hook
     * @return \Telegram\Bot\TelegramResponse
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function createWebHook()
    {
        $key = config('telegram.bots.mybot.token');
        $telegram = new Api($key);
        return $telegram->setWebhook(['url' => config('app.url').'/api/telegram/webhook']);
    }

    /**
     * 关闭机器人 web hook
     * @return \Telegram\Bot\TelegramResponse
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function removeWebHook()
    {
        $key = config('telegram.bots.mybot.token');
        $telegram = new Api($key);
        return $telegram->removeWebhook();
    }

    /**   https://api.cheat-kit03.win/api/telegram/webhook/
     * 接收发送给我的数据
     * @param TelegramService $telegramService
     */
    public function ListenWebHook(TelegramService $telegramService)
    {
        $response = $telegramService->listener();
        if (!$response) {
            if (date('H') > 11) {
                //  北京时间1.30以后 才可以记录数据  要不然出现问题还要早起
//                \Log::notice(time(), \Request::all());
            }
//            \Log::notice($telegramService->getErrorMessage());
            //  不做记录了 不然日志太多了
            return false;
        }

        return true;

    }




}
