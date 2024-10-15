<?php

namespace App\Console\Commands;

use App\Service\FitService;

/**
 * Class Sync
 * @package App\Console\Commands
 */
class T2Command extends BaseCommand
{


    /**
     * @var string
     */
    protected $signature = 't2';


    /**
     * @var string
     */
    protected $description = '并发回调';

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
        $service = new FitService();
        $check_time = 1;
        $response = $service->balance($check_time);
        dump($response);
    }
}
