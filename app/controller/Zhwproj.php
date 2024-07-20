<?php

namespace app\controller;

use Error;
use Exception;
use think\response\Html;
use think\response\Json;
use think\facade\Log;
use think\facade\View;
use think\facade\Request;
use think\facade\Db;


class Zhwproj
//
{
    public function index()
    {
        return response( file_get_contents(dirname(dirname(__FILE__)).'/view/zhwproj/index.html'));
    }

    public function adminLogin()
    {
        $admin = trim($_POST['admin']);
        $pass = trim($_POST['pass']);
        if($admin=='admin' and $pass=='lijiming'){
            return response( file_get_contents(dirname(dirname(__FILE__)).'/view/zhwproj/admin_login.html'));
        }else{
            return '用户密码校验错';
        }
    }
    
    public function wxminiappLogin()
    //获取openid/unionid，记录客户访问时间，返回客户信息
    {
        $openid = Request::header('x-wx-openid');
        $unionid = Request::header('x-wx-unionid');
        $mytime = date('Ymd H:i:s');
        $zhwDB=Db::connect('zhwProjDB');
        $cusCount = $zhwDB->table('cusinfo')->where('openid',$openid)->count();
        
        if($cusCount==0)//新客户，插入新客户数据
        {
            $code = ['seq'=>-1];
            $cusdata=array(
                'openid'=>$openid,
                'avatar'=>'',
                'nickname'=>'',
                'birthday'=>'',
                'type'=>'G',//G:general
                'firstVisit'=>$mytime,
                'lastVisit'=>$mytime,
                'remark'=>'',
                'birthplace'=>'',
                'birthlongitude'=>'',
                'unionid'=>$unionid
            );
            $res = $zhwDB->table('cusinfo')->insert($cusdata);
            $returnData=$code+$cusdata;

        }else//老客户，获取客户信息，并更新客户访问时间
        {
            $cusdata = $zhwDB->table('cusinfo')->where('openid',$openid)->find();
            $res = $zhwDB->table('cusinfo')->where('openid',$openid)->update(['lastVisit'=>$mytime]);
            $returnData=$cusdata;
        }
        
        return json($returnData);
    }

}
