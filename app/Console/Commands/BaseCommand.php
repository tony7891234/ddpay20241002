<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bx:base';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '单独运行无实际意义，只是封装一些方法';


    /**
     * @var int
     */
    protected $errorCode;

    /**
     * @var string
     */
    protected $errorMessage;

    /**
     * BaseCommand constructor.
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
        $this->info('Hello');
    }

}
