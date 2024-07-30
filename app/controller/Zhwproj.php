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
                'birthday'=>'1900-01-01 00:00',
                'type'=>'G',//G:general
                'firstVisit'=>$mytime,
                'lastVisit'=>$mytime,
                'remark'=>'',
                'unionid'=>$unionid,
                'birthplace'=>'北京市,北京市',
                'birthlongitude'=>'',
                'gender'=>'0' //0-男，1-女
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

    public function wxminiappSaveUsrInfo(){
        //post 返回json
        $updatedata = Request::post();
        $openid = Request::param('x-wx-openid');
        $zhwDB = Db::connect('zhwProjDB');
        $ret = array('code'=>0,'errmsg'=>'');

        $res = $zhwDB->table('cusinfo')->where('openid',$openid)->update($updatedata);

        if($res ==1){//更新数据记录数 为1
            //更新成功
            $ret['code']=-1;
            $ret['errmsg']='客户信息更新成功';
        }else{
            //更新失败
            $ret['code']=101;
            $ret['errmsg']='数据更新失败||'+$res;
        }

        $res_json = json_encode($ret,JSON_UNESCAPED_UNICODE);

        return $res_json; //json格式，其中code为返回码（0-成功，-1测试，其他失败）
    }
    

}
