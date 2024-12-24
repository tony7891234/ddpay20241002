<?php

namespace App\Console\Commands;

use App\Services\SwooleServer;
use Illuminate\Console\Command;

class StartSwooleServer extends Command
{
    protected $signature = 'swoole:start {--host=0.0.0.0} {--port=9501}';
    protected $description = 'Start Swoole Server for callbacks';
    
    public function handle()
    {
        $host = $this->option('host');
        $port = $this->option('port');
        
        $server = new SwooleServer($host, $port);
        $server->start();
    }
} 