<?php

namespace app\controller;

use Error;
use Exception;
use think\response\Html;
use think\response\Json;
use think\facade\Log;
use think\facade\View;


class Zhwproj
//
{
    /**
     * 主页静态页面
     * @return Html
     */
    public function index(): Html
    {
        return View::fetch();
    }

}
