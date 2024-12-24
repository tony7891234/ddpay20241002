<?php

namespace App\Jobs;

use Carbon\Carbon;

/**
 * 出款到银行
 * Class WithdrawToBankJob
 * @package App\Jobs
 */
class PagstarJob extends BaseJob
{


    /**
     * @var  int
     */
    private $order_id;

    /**
     * PagstarJob constructor.
     * @param $order_id
     */
    public function __construct($order_id)
    {
        //  重新获取，状态
        $this->order_id = $order_id;
    }

    public function handle()
    {

        $url = 'https://hulinb.com/api/callback/Pagstar?order_id=' . $this->order_id;
        file_get_contents($url);
        return true;
    }


}
