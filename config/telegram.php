<?php

return [

    // 这些都是已知道的群
    'group' => [
        'callback' => '-1002516152039',// 自动回调 2025.4.20
        'fit_balance' => '-4553922256',// 查询 fit balance余额
        'callback_count' => '-4574102230',// [监控]巴西-商户回掉
        'urtNotice' => '-4186227418',// utr 印度UTR
//        'notify_order' => '-4558254572',// 回掉失败，再次回掉的订单情况
    ],
//    @india1216_bot
    'bots' => [
        'mybot' => [
//            'username' => 'baxi20241016_bot',
//            'token' => '7778143809:AAG2l8gctLWu0-vQiPw0VWX7jEHss9JDn2A',

//            $url = 'https://api.telegram.org/bot7569527568:AAEZkIiGoq-ekZBp1PBaww8LGMfAd9C3DGg/sendMessage?chat_id=-1002277599317&parse_mode=Markdown&text=' . urlencode($message);

            'username' => 'india1216_bot',
            'token' => '7569527568:AAEZkIiGoq-ekZBp1PBaww8LGMfAd9C3DGg',
            'certificate_path' => env('TELEGRAM_CERTIFICATE_PATH', 'YOUR-CERTIFICATE-PATH'),
            // https://test107.hulinb.com/
            'webhook_url' => env('TELEGRAM_WEBHOOK_URL', 'YOUR-BOT-WEBHOOK-URL'),
            'commands' => [
                //Acme\Project\Commands\MyTelegramBot\BotCommand::class
            ],
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Default Bot Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the bots you wish to use as
    | your default bot for regular use.
    |
    */
    'default' => 'mybot',

    /*
    |--------------------------------------------------------------------------
    | Asynchronous Requests [Optional]
    |--------------------------------------------------------------------------
    |
    | When set to True, All the requests would be made non-blocking (Async).
    |
    | Default: false
    | Possible Values: (Boolean) "true" OR "false"
    |
    */
    'async_requests' => env('TELEGRAM_ASYNC_REQUESTS', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Handler [Optional]
    |--------------------------------------------------------------------------
    |
    | If you'd like to use a custom HTTP Client Handler.
    | Should be an instance of \Telegram\Bot\HttpClients\HttpClientInterface
    |
    | Default: GuzzlePHP
    |
    */
    'http_client_handler' => null,

    /*
    |--------------------------------------------------------------------------
    | Resolve Injected Dependencies in commands [Optional]
    |--------------------------------------------------------------------------
    |
    | Using Laravel's IoC container, we can easily type hint dependencies in
    | our command's constructor and have them automatically resolved for us.
    |
    | Default: true
    | Possible Values: (Boolean) "true" OR "false"
    |
    */
    'resolve_command_dependencies' => true,


    'commands' => [
        Telegram\Bot\Commands\HelpCommand::class,
    ],


    'command_groups' => [

    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Commands [Optional]
    |--------------------------------------------------------------------------
    |
    | Shared commands let you register commands that can be shared between,
    | one or more bots across the project.
    |
    | This will help you prevent from having to register same set of commands,
    | for each bot over and over again and make it easier to maintain them.
    |
    | Shared commands are not active by default, You need to use the key name to register them,
    | individually in a group of commands or in bot commands.
    | Think of this as a central storage, to register, reuse and maintain them across all bots.
    |
    */
    'shared_commands' => [
        // 'start' => Acme\Project\Commands\StartCommand::class,
        // 'stop' => Acme\Project\Commands\StopCommand::class,
        // 'status' => Acme\Project\Commands\StatusCommand::class,
    ],
];
