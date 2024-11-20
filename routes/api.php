<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/test/', 'TestController@test');

Route::get('/getChatList/', 'TelegramController@getChatList');
// 创建一个 web hook
Route::get('/createWebHook/', 'TelegramController@createWebHook');
// 发送信息
//    Route::get('/sendMessage/', 'TelegramController@sendMessage');
// 删除对话   https://xx/api/createWebHook
Route::get('/removeWebHook/', 'TelegramController@removeWebHook');

//接收发送给我的信息
Route::post('/telegram/webhook/', 'TelegramController@ListenWebHook');

Route::get('/telegram/urtNotice/{order_no}', 'TelegramController@urtNotice');
Route::get('/telegram/vim/{order_no}', 'TelegramController@vim'); // 越南短信


// 业务相关

// 出款订单回掉
Route::post('/withdraw/notify/{upstream_id}', 'WithdrawOrderController@notify');


Route::post('/back1', 'TestController@back1');
Route::post('/back2', 'TestController@back2');



