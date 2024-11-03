<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        $schedule->command('notify left')->everyTenMinutes(); // 十分钟执行一次遗漏的订单

        $schedule->command('fit balance')->dailyAt('10:59'); // 查询余额
        $schedule->command('fit balance')->dailyAt('11:00'); // 查询余额

        // 每小时执行一次的升序
        $schedule->command('clear:delete_hourly')->hourly();

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
