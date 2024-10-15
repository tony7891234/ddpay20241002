<?php

namespace App\Repository;

use App\Models\User;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

/**
 * telegram  仓库
 * Class TelegramRepository
 * @package App\Repository
 */
class TelegramRepository extends BaseRepository
{


    /**
     * @var Api
     */
    private $telegram;


    /**
     * 锁定账号通知
     * @param $username
     */
    public function lockUser($username)
    {
        $response_text = $username;
        $userInfo = User::where('username', '=', $username)->first();
        if ($userInfo) {
            $response_text = $response_text . '<pre>代理层级 ' . $userInfo->agentPath . '</pre>';
        }
        $this->replayMessage(config('telegram.group.lock_user'), $response_text);
    }

    /**
     * 发送消息
     * @param $chat_id
     * @param $message
     * @return \Telegram\Bot\Objects\Message|bool
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function sendMessage($chat_id, $message)
    {
        try {
            return $this->getTelegram()->sendMessage([
                'chat_id' => $chat_id,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);
        } catch (\Exception $exception) {
            $this->errorCode = -1;
            $this->errorMessage = $exception->getMessage(); // 一般来说都是 chat_id 有误
            return false;
        }

    }

    /**
     * 回复某一条消息
     * @param $chant_id int
     * @param $text string
     * @return bool|\Telegram\Bot\Objects\Message
     */
    public function replayMessage($chant_id, $text)
    {
        try {
            return $this->getTelegram()->sendMessage([
                'chat_id' => $chant_id, //  message.chat.id   这个id必须是消息发布的群，不然不能实现回复
                'text' => $text, //  回复内容
                'parse_mode' => 'HTML',
            ]);
        } catch (\Exception $exception) {
            $this->errorCode = -1;
            $this->errorMessage = $exception->getMessage(); // 一般来说都是 chat_id 有误
            return false;
        }
    }

    /**
     * 发送图片
     * @param $chant_id int
     * @return bool|\Telegram\Bot\Objects\Message
     */
    public function sendPicture($chant_id)
    {
        try {
            return $this->getTelegram()->sendPhoto([
                'chat_id' => $chant_id, //  message.chat.id   这个id必须是消息发布的群，不然不能实现回复
                'photo' => InputFile::create('test/1.jpg'),
//                'photo2' => InputFile::create('test/2.jpg'),
//                'photo3' => InputFile::create('test/2.jpg'),
                'caption' => 'Some caption'
            ]);
        } catch (\Exception $exception) {
//            dd($exception->getMessage());
            $this->errorCode = -1;
            $this->errorMessage = $exception->getMessage(); // 一般来说都是 chat_id 有误
            return false;
        }
    }

    /**
     * 还是使用这种方案吧  因为不是每次使用这个类  都需要实例化
     * @return Api
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    private function getTelegram()
    {
        if ($this->telegram) {
            return $this->telegram;
        }

        $key = config('telegram.bots.mybot.token');
        return $this->telegram = new Api($key);
    }

}
