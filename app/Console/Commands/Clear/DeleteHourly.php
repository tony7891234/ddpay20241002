<?php

namespace App\Console\Commands\Clear;

use App\Console\Commands\BaseCommand;
use App\Models\NotifyOrder;

/**
 * Class DeleteHourly
 * @package App\Console\Commands\Clear
 */
class DeleteHourly extends BaseCommand
{

    /**
     * @var string
     */
    protected $signature = 'clear:delete_hourly';

    /**
     * @var string
     */
    protected $description = '每小时删除一次的数据';

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

        // 保留2天的数据
        $time = time() - 3600 * 24 * 3;
        NotifyOrder::where('create_time', '<', $time)->delete();

    }


}
