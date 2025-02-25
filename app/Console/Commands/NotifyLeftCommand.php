<?php
//
//namespace App\Console\Commands;
//
//use App\Models\NotifyOrder;
//use App\Traits\RepositoryTrait;
//
///**
// * Class NotifyLeftCommand
// * @package App\Console\Commands
// */
//class NotifyLeftCommand extends BaseCommand
//{
//
//    use RepositoryTrait;
//
//    private $count_order = 0; // 总条数
//
//    /**
//     * @var string
//     */
//    protected $signature = 'notify_left';
//
//    /**
//     * @var string
//     */
//    protected $description = '遗漏的回掉';
//
//
//    /**
//     * KG_Init constructor.
//     */
//    public function __construct()
//    {
//        parent::__construct();
//    }
//
//
//    /**
//     * run
//     */
//    public function handle()
//    {
//        dump('restart ' . (getTimeString()) . '  ');
//        while (true) {
//            $this->notify();
//            sleep(2);
//        }
//
//        return true;
//    }
//
//    public function notify()
//    {
//        $start_at = time() - 600; // 10 分钟到1 分钟的  分钟内的数据
//        $end_at = time() - 60; //
//
//        $this->count_order = NotifyOrder::where('update_time', '>', $start_at)
//            ->where('update_time', '<', $end_at)
//            ->where('notify_status', '=', 0) // 回掉次数失败的
//            ->count();
//        if ($this->count_order == 0) {
//            return true;
//        }
//        /**
//         * @var $list NotifyOrder[]
//         */
//        $list = NotifyOrder::select([
//            'order_id',
//        ])->where('update_time', '>', $start_at)
//            ->where('update_time', '<', $end_at)
//            ->where('notify_status', '=', 0) // 回掉次数失败的
//            ->orderBy('order_id', 'asc')
//            ->limit(200)
//            ->get();
//
//        // 商户ID列表
//        $urlsWithParams = [];
//        $recharge_num = $withdraw_num = 0;
//
//        foreach ($list as $k => $notifyInfo) {
//            if (in_array(strtolower($notifyInfo->response), ['success', 'ok'])) {
//                $this->updateNotifyToFail($notifyInfo->order_id);
//                continue;
//            }
//            $order_id = $notifyInfo->order_id;
//            if ($notifyInfo->type == NotifyOrder::TYPE_RECHARGE) {
//                $recharge_num++;
//                //  收
//                $notify_url = 'https://hulinb.com/api/order/notify?order_id_index=' . $order_id;
//            } else {
//                $withdraw_num++;
//                $notify_url = 'https://hulinb.com/api/df/notify?order_id_index=' . $order_id;
//            }
//            $this->updateNotifyToFail($order_id);
//
//            $urlsWithParams[] = $notify_url;
//        }
//
//        if ($urlsWithParams) {
//            curlManyRequest($urlsWithParams, 20); // 回掉时间延长下
//        }
//
//        $start_at = getTimeString();
//        $tgMessage = <<<MG
//回掉平台：\r\n
//执行时间：{$start_at}\r\n
//总单数：{$this->count_order} \r\n
//入款笔数：{$recharge_num} \r\n
//出款笔数：{$withdraw_num} \r\n
//\r\n
//MG;
//        $this->getTelegramRepository()->replayMessage(config('telegram.group.notify_order'), $tgMessage);
//
//    }
//
//    /**
//     * 更新成 回调失败状态
//     * @param int $order_id
//     * @return bool
//     */
//    private function updateNotifyToFail($order_id)
//    {
//        NotifyOrder::where('order_id', '=', $order_id)->update([
//            'notify_num' => \DB::raw('notify_num + 1'),
//            'notify_status' => NotifyOrder::NOTIFY_STATUS_SITE,
//        ]);
//        return true;
//    }
//
//}
//
