<?php

return [
    'host' => env('SWOOLE_HOST', '0.0.0.0'),
    'port' => env('SWOOLE_PORT', 9501),
    'options' => [
        'worker_num' => env('SWOOLE_WORKER_NUM', 4),
        'task_worker_num' => env('SWOOLE_TASK_WORKER_NUM', 8),
        'daemonize' => env('SWOOLE_DAEMONIZE', false),
    ]
]; 