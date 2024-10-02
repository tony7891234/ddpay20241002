<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix' => config('admin.route.prefix'),
    'namespace' => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    //  5.测试使用
    $router->resource('test', 'TestController');
    //  入款订单
    $router->resource('recharge_order', 'RechargeOrderController');
    // 出款订单
    $router->resource('withdraw_order', 'WithdrawOrderController');

    $router->resource('job', 'JobController');

});
