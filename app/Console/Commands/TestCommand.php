<?php

namespace App\Console\Commands;

use App\Models\WithdrawOrder;
use App\Payment\IuguPayment;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Storage;

/**
 * 回掉异常订单
 * Class NotifyOrderCommand
 * @package App\Console\Commands
 */
class TestCommand extends BaseCommand
{

    use RepositoryTrait;


    const MAX_NOTIFY_NUM = 2; // 最大回掉次数
    /**
     * @var string
     */
    protected $signature = 'test';


    /**
     * @var string
     */
    protected $description = '2.回调异常订单';

    private $count_order = 0; // 总条数

    private $start_at = 0;
    private $curl_start = 0;
    private $sql_finished = 0;
    private $end_at = 0;

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
//        $pemContent = Storage::get('pem/iugu.pem');
//        $pemContent= openssl_pkey_get_private($pemContent);
//
//        dd($pemContent);
//        dump('restart ' . (getTimeString()) . '  ');

        $withdrawOrder = WithdrawOrder::where('order_id', '=', 3577)->first();
        dump($withdrawOrder);
        $service = new IuguPayment();
        $response = $service->withdrawRequest($withdrawOrder);
        if (!$response) {
            dd($service->getErrorMessage());
        } else {
            dd('success');
        }

        return true;
    }


}

