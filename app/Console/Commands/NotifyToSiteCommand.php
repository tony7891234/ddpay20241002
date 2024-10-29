<?php

namespace App\Console\Commands;

use App\Models\NotifyOrder;
use App\Traits\RepositoryTrait;

/**
 * NotifyOrderCommand 失败3次的订单，直接去请求系统域名
 * Class NotifyToSiteCommand
 * @package App\Console\Commands
 */
class NotifyToSiteCommand extends BaseCommand
{

    use RepositoryTrait;

    private $count_order = 0; // 总条数

    /**
     * @var string
     */
    protected $signature = 'notify_site';

    /**
     * @var string
     */
    protected $description = '3.异常处理失败，回掉到站点';


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
        dump('restart ' . (getTimeString()) . '  ');
        while (true) {
            $this->notify();
            sleep(5);
        }

        return true;
    }

    public function notify()
    {
        $current_time = time() - 3600; // 获取一小时内的订单即可

        $this->count_order = NotifyOrder::where('notify_time', '>', $current_time)
            ->where('notify_num', '=', NotifyOrderCommand::MAX_NOTIFY_NUM)
            ->count();
        if ($this->count_order == 0) {
            return true;
        }
        /**
         * @var $list NotifyOrder[]
         */
        $list = NotifyOrder::select([
            'order_id',
        ])->where('notify_time', '>', $current_time)
            ->where('notify_num', '=', NotifyOrderCommand::MAX_NOTIFY_NUM)
            ->orderBy('notify_time', 'asc')
            ->limit(500)
            ->get();

        // 商户ID列表
        $urlsWithParams = [];
        $recharge_num = $withdraw_num = 0;

        foreach ($list as $k => $notifyInfo) {
            if (in_array(strtolower($notifyInfo->response), ['success', 'ok'])) {
                $this->updateNotifyToFail($notifyInfo->order_id);
                continue;
            }
            $order_id = $notifyInfo->order_id;
            if ($notifyInfo->type == NotifyOrder::TYPE_RECHARGE) {
                $recharge_num++;
                //  收
                $notify_url = 'https://hulinb.com/api/order/notify?order_id_index=' . $order_id;
            } else {
                $withdraw_num++;
                $notify_url = 'https://hulinb.com/api/df/notify?order_id_index=' . $order_id;
            }
            $this->updateNotifyToFail($order_id);

            $urlsWithParams[] = $notify_url;
        }

        if ($urlsWithParams) {
            curlManyRequest($urlsWithParams);
        }

        $current_time = getTimeString();
        $tgMessage = <<<MG
回掉平台：\r\n
执行时间：{$current_time}\r\n
总单数：{$this->count_order} \r\n
入款笔数：{$recharge_num} \r\n
出款笔数：{$withdraw_num} \r\n
\r\n
MG;
        $this->getTelegramRepository()->replayMessage(config('telegram.group.notify_order'), $tgMessage);

    }

    /**
     * 更新成 回调失败状态
     * @param int $order_id
     * @return bool
     */
    private function updateNotifyToFail($order_id)
    {
        NotifyOrder::where('order_id', '=', $order_id)->update([
            'notify_num' => \DB::raw('notify_num + 1'),
            'notify_status' => NotifyOrder::NOTIFY_STATUS_SITE,
        ]);
        return true;
    }

}

