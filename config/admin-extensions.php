<?php

return [
    'iframe-tabs' => [
        'enable' => true,
        'home_action' => 'App\Admin\Controllers\HomeController@home',
        'home_title' => 'Home',
        'home_icon' => 'fa-home',
        'use_icon' => true,
        'tabs_css' => 'vendor/laravel-admin-ext/iframe-tabs/dashboard.css',
        'layer_path' => 'vendor/laravel-admin-ext/iframe-tabs/layer/layer.js',
        'pass_urls' => [
            0 => '/auth/logout',
            1 => '/auth/lock',
        ],
        'force_login_in_top' => true,
        'tabs_left' => 42,
        'bind_urls' => 'popup',
        'bind_selecter' => 'a.grid-row-view,a.grid-row-edit,.column-__actions__ ul.dropdown-menu a,.box-header .pull-right .btn-success,.popup',
    ],
];
