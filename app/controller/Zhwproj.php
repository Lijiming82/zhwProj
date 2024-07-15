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
    public function index()
    {
        return View::fetch();
    }

    public function adminLogin()
    {
        $admin = trim($_POST['admin']);
        $pass = trim($_POST['pass']);
        if($admin=='admin' and $pass=='lijiming'){
            return View::fetch();
        }else{
            return '用户密码校验错';
        }
    }

}
