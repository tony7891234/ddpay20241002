<?php

namespace App\Providers;

use App\Console\Commands\StartSwooleServer;
use Illuminate\Support\ServiceProvider;

class SwooleServiceProvider extends ServiceProvider
{
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                StartSwooleServer::class,
            ]);
        }
    }
} 