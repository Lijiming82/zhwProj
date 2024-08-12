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
        $openid = Request::header('x-wx-openid');
        $zhwDB = Db::connect('zhwProjDB');
        $ret = array('code'=>1,'errmsg'=>'');//默认是成功及空串，1-成功，-1测试，其他失败

        $res = $zhwDB->table('cusinfo')->where('openid',$openid)->update($updatedata);
        //$res = $zhwDB->table('cusinfo')->where('openid',$openid)->count();


        if($res ==1){//更新数据记录数 为1
            //更新成功
            $ret['code']=1;
            $ret['errmsg']='客户信息更新成功';
        }else{
            //更新失败
            $ret['code']=101;
            $ret['errmsg']='数据更新失败||'+$res;
        }

        $res_json = json_encode($ret,JSON_UNESCAPED_UNICODE);


        return $res_json; //json格式，其中code为返回码（1-成功，-1测试，其他失败）
    }
    
    public function wxminiappGetLayouts(){
        $openid = Request::header('x-wx-openid');
        $redata['code']=0;

        if(!is_null($openid)){
            $zhwDB = Db::connect('zhwProjDB');
            $redata['code']=1;
            $fileurlparam['env']='prod-9gg6q4yc21bb3fdd';
            $i=0;

            $j = $zhwDB->table('layouts')->where('sta','1')->count();

            $res = $zhwDB->table('layouts')->where('sta','1')->select();

            for($i=0;$i<$j;$i++){
                //
                $redata['layouts'][$res[$i]['room']][$res[$i]['type']]['fileid']=$res[$i]['fileid'];
                $fileurlparam['file_list'][$i]['fileid']=$res[$i]['fileid'];
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'http://api.weixin.qq.com/tcb/batchdownloadfile',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($fileurlparam),
                CURLOPT_HTTPHEADER => array(
                  'Content-Type: application/json'
                ),
              ));
              $response = curl_exec($curl);
              curl_close($curl);

              $urldata = json_decode($response);

              for($i=0;$i<$j;$i++){
                $redata['layouts'][$res[$i]['room']][$res[$i]['type']]['fileurl']=$urldata->file_list[$i]->download_url;//object内容访问使用 -> 数组用索引值或下标
              }
              
        }

        $redata_json  = json_encode($redata);
        return $redata_json;
        
    }

    private function mydir($degree){

        if($degree>=340||$degree<=20){
            return '1';//北，1
        }elseif($degree>20&&$degree<70){
            return '8';//东北 8
        }elseif($degree>=70&&$degree<=110){
            return '3';//东 3
        }elseif($degree>110&&$degree<160){
            return '4';//东南 4
        }elseif($degree>=160&&$degree<=200){
            return '9';//南 9
        }elseif($degree>200&&$degree<250){
            return '2';//西南 2
        }elseif($degree>=250&&$degree<=290){
            return '7';//西 7
        }elseif($degree>290&&$degree<340){
            return '6';//西北 6
        }

    }

    public function fengshuiA(){
        //紫白三元，财耀、魁星
        $openid = Request::header('x-wx-openid');
        $redata['code']=0;
        $inputdata = Request::post();

        
        if(!is_null($openid)){
            $zhwDB = Db::connect('zhwProjDB');
            $redata['code']=1;

            //
            $degree = $inputdata['degree'];
            $dir = $this->mydir($degree);

            $res = $zhwDB->table('zibai1')->where('dir',$dir)->find();

            $redata['career']=$res['career'];
            $redata['rich']=$res['rich'];

        }

        $redata_json  = json_encode($redata);
        return $redata_json;
    }

    public function fengshuiB(){
        //阳宅三要，生气、延年、天医、六煞、祸害、五鬼、绝命、伏位
        $openid = Request::header('x-wx-openid');
        $redata['code']=0;
        $inputdata = Request::post();

        
        if(!is_null($openid)){
            $zhwDB = Db::connect('zhwProjDB');
            $redata['code']=1;

            //
            $degree = $inputdata['degree'];
            $dir = $this->mydir($degree);

            $res = $zhwDB->table('yangzhai1')->where('xiang',$dir)->find();

            $redata['sheng']=$res['sheng'];
            $redata['yan']=$res['yan'];
            $redata['tian']=$res['tian'];
            $redata['liu']=$res['liu'];
            $redata['huo']=$res['huo'];
            $redata['wu']=$res['wu'];
            $redata['jue']=$res['jue'];
            $redata['fu']=$res['fu'];

        }

        $redata_json  = json_encode($redata);
        return $redata_json;
    }

}
