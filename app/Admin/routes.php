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


    $router->resource('job', 'JobController');

    // 出款订单
    $router->resource('withdraw_order', 'WithdrawOrderController');
    // 批量出款
    $router->resource('batch_withdraw', 'BatchWithdrawController');

    // 回掉商户遗漏订单
    $router->resource('notify_order', 'NotifyOrderController');


    //  出入款订单
    $router->resource('recharge_order', 'RechargeOrderController');
    //  往前第一个时间 1028
    $router->resource('recharge_order1', 'RechargeOrder1Controller');

    //  往前第一个时间 1010
    $router->resource('recharge_order2', 'RechargeOrder2Controller');

    //  往前第一个时间 cd_order
    $router->resource('recharge_order3', 'RechargeOrder3Controller');

    //  最新日志
    $router->resource('money_log', 'MoneyLogController');


});
