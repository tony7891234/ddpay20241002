<?php

namespace App\Console\Commands;

use App\Models\RechargeOrder;
use Illuminate\Support\Facades\DB;

/**
 * Class Sync
 * @package App\Console\Commands
 */
class TestCommand extends BaseCommand
{


    /**
     * @var string
     */
    protected $signature = 'test';


    /**
     * @var string
     */
    protected $description = '同步数据(只更新昨天和今天的数据)';

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

        $type = $this->argument('type');
        $this->t2();

        return true;
    }

    private function t2()
    {
        dump(getTimeString());


    }


}
