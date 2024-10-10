<?php

namespace App\Http\Controllers;

use App\Models\FitModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class FitController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function data($page)
    {

        $response = FitModel::where('create_date', '>=', $page)->orderBy('create_date')->limit(100)->get();
        return $response;
    }
}
