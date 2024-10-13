<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Overtrue\EasySms\Traits\HasHttpRequest;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * 首页
 * Class HomeController
 * @package App\Http\Controllers\Web
 */
class HomeController extends WebController
{
    use HasHttpRequest;

    /**
     * 首页 测试支付通道页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        dd('homr');
    }


}
