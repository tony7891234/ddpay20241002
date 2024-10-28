<?php

namespace App\Console\Commands;

use App\Models\MerchantModel;
use App\Models\RechargeOrder;
use App\Service\DdPayService;

/**
 * Class Sync
 * @package App\Console\Commands
 */
class TestCommand extends BaseCommand
{

    const MAX_TIME = 5;// 超时多少秒，需要记录 log
    const FILE_NAME_LONG_TIME = 'long_'; // 超时5S没信息的
    const FILE_NAME_RESPONSE_NULL = 'nothing_'; // 什么否没有返回的

    /**
     * @var string
     */
    protected $signature = 'test';


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
        $this->t2();

        return true;
    }


    private function t2()
    {
        $name = 'withdraw/1730138606.xlsx';
//        $name = '1730138606.xlsx';
//        $file = \Illuminate\Support\Facades\Storage::disk('withdraw');

//        dump($file);
        $file = \Illuminate\Support\Facades\Storage::disk('withdraw')->get($name);
        dump($file);
    }

    private function t1()
    {
        $str = 'BIDV
8883109665
NGUYEN TUAN ANH
20000';
        $service = new DdPayService();
        $service->withdraw(trim($str));
        $response_text = $service->getErrorMessage();
        dump($response_text);
    }

}

