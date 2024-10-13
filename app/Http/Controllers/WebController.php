<?php

namespace App\Http\Controllers;

use App\Traits\ResponseAble;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * web 控制器基类
 * Class WapController
 * @package App\Http\Controllers
 */
class WebController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ResponseAble;


    /**
     * WapController constructor.
     */
    public function __construct()
    {
    }

    /**
     * 失败页面
     * @param string $message
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function errorPage($message = 'error')
    {
        return view('web.layout.error', compact('message'));
    }

    /**
     * 成功页面  现在不知道怎么使用
     * @param $message
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function successPage($message)
    {
        return view('web.layout.success', compact('message'));
    }

}
