<?php

namespace App\Http\Controllers;

use App\Traits\ResponseAble;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * API 基类
 * Class ApiController
 * @package App\Http\Controllers
 */
class ApiController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests,ResponseAble;
}
