<?php

namespace App\Service;


use App\Models\RechargeOrder;

/**
 *  为了避免出现通讯异常，所有处理直接使用 try catch
 * Class TelegramService
 * @package App\Service
 */
class TelegramService extends BaseService
{

    /**
     * 群消息内容
     * @var string
     */
    private $message_text;


    /**
     * 发消息的群ID 也是回复消息的ID
     * @var int
     */
    private $chat_id;

    /**
     * 监听商户的异常订单
     * 商户反馈订单后，如果我方系统是支付成功的状态，则直接返回给商户已补单
     * 但是这种订单一般是未成功，则放入缓存中，
     * 上游回调之后，我方查询缓存，如果这个订单在缓存中，则表示商户需要被通知
     * @return bool
     */
    public function listener()
    {
        $request = \Request::all();

        // 1.检查数据格式  是否有 chat_id 和 caption
        if (!isset($request['message']['chat']['id'])) {
            $this->errorCode = -11;
            $this->errorMessage = 'chat id 不存在';
            return false;
        }
        if (!isset($request['message']['text'])) {
            $this->errorCode = -12;
            $this->errorMessage = 'text不存在';
            return false; // 这个不需要输出  不需要处理
        }

        if ($request['message']['from']['is_bot'] == true) {
            return true;//机器人发送的消息  不需要处理
        }

        // 群ID 也是chat_id
        $this->chat_id = $request['message']['chat']['id'];
        $this->message_text = trim($request['message']['text']); // 群消息

        //  有中文  表示是聊天信息
        preg_match('/^(\p{Han})/u', $this->message_text, $result);
        if ($result) {
            $this->errorCode = -3;
            $this->errorMessage = '中文聊天，不需要处理';
            return false; // 这条消息不发送给飞机群 所以是 false
        }

        //  自动回调
        if ($this->chat_id == config('telegram.group.callback')) {
            $response_text = $this->callback();
            if ($response_text) {
                return $this->getTelegramRepository()->replayMessage($this->chat_id, $response_text);
            }
            return true;
        }
        //  查询 fit balance
        if ($this->chat_id == config('telegram.group.fit_balance')) {
            $response_text = $this->checkFitBalance();
            if ($response_text) {
                return $this->getTelegramRepository()->replayMessage($this->chat_id, $response_text);
            }
            return true;
        }


        return $this->getTelegramRepository()->replayMessage($this->chat_id, $this->chat_id);

        // 新添加群 打开这个
        logToMe('new_telegram   ' . $this->chat_id . '   ' . $this->errorMessage); // 新添加机器人的时候要打开 识别新的机器人

        return true;
    }

    private function checkFitBalance()
    {
        $service = new FitService();
        $check_time = trim($this->message_text);
        $response = $service->balance($check_time);
        if (isset($response['balance']) && isset($response['fee'])) {
            return $this->message_text . '   余额:' . $response['balance'] . '   手续费:' . $response['fee'];
        }
        return '查询失败';
    }


    private function callback()
    {
        //  使用空格做区分
        $arr = array_values(array_filter(explode(PHP_EOL, $this->message_text)));
        if (count($arr) < 2) {
            return '格式有误';
        }
//        return 'stop';
        
        $start_at = $arr[0];
        $end_at = $arr[1];
        $merchantid = isset($arr[2]) ? $arr[2] : 0; // 商户ID
        date_default_timezone_set('PRC');
        $start_at = strtotime(date($start_at));
        $end_at = strtotime(date($end_at));
        if ($end_at - $start_at > 300) {
            return '时间间隔最多只能是5分钟';
        }
        $query = RechargeOrder::select(['order_id', 'orderid', 'create_time', 'inizt'])
            ->where('create_time', '>=', $start_at)
            ->where('create_time', '<=', $end_at);
        if ($merchantid) {
            $query = $query->where('merchantid', '=', $merchantid);
        }
        $query->orderBy('order_id')->chunk(200, function ($list) {
            $list = $list->toArray();
            $response = [];
            foreach ($list as $item) {
                $order_id = $item['order_id'];
                if ($item['inizt'] == 0) {
                    //  收
                    $url = 'https://hulinb.com/api/order/notify?order_id_index=' . $order_id;
                } else {
                    $url = 'https://hulinb.com/api/df/notify?order_id_index=' . $order_id;
                }
                $response[] = $url;
            }
            curlManyRequest($response);
        });

        $query = RechargeOrder::where('create_time', '>=', $start_at)  // 22：56
        ->where('create_time', '<=', $end_at);
        if ($merchantid) {
            $query = $query->where('merchantid', '=', $merchantid);
        }
        $count = $query->count();
        return '执行完毕,总条数:' . $count . ' 开始时间' . formatTimeToString($start_at) . ' 结束时间:' . formatTimeToString($end_at) . ' 商户ID:' . $merchantid;
    }

}
