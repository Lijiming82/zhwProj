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

use function PHPSTORM_META\elementType;

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
        var_dump($res);

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
        //给出方向所对应的数字
        //向

        if($degree>=337.5||$degree<=22.5){
            return '1';//北，1
        }elseif($degree>22.5&&$degree<67.5){
            return '8';//东北 8
        }elseif($degree>=67.5&&$degree<=112.5){
            return '3';//东 3
        }elseif($degree>112.5&&$degree<157.5){
            return '4';//东南 4
        }elseif($degree>=157.5&&$degree<=202.5){
            return '9';//南 9
        }elseif($degree>202.5&&$degree<247.5){
            return '2';//西南 2
        }elseif($degree>=247.5&&$degree<=292.5){
            return '7';//西 7
        }elseif($degree>292.5&&$degree<337.5){
            return '6';//西北 6
        }

    }

    private function mydir2($degree){

        //输入角度（朝向的角度），给出排列数字BCDEFGHI

        if($degree>=337.5||$degree<=22.5){
            $redata=array('B'=>1,'C'=>8,'D'=>3,'E'=>4,'F'=>9,'G'=>2,'H'=>7,'I'=>6);
            return $redata;//北，1
        }elseif($degree>22.5&&$degree<67.5){
            $redata=array('B'=>8,'C'=>3,'D'=>4,'E'=>9,'F'=>2,'G'=>7,'H'=>6,'I'=>1);
            return $redata;//东北 8
        }elseif($degree>=67.5&&$degree<=112.5){
            $redata=array('B'=>3,'C'=>4,'D'=>9,'E'=>2,'F'=>7,'G'=>6,'H'=>1,'I'=>8);
            return $redata;//东 3
        }elseif($degree>112.5&&$degree<157.5){
            $redata=array('B'=>4,'C'=>9,'D'=>2,'E'=>7,'F'=>6,'G'=>1,'H'=>8,'I'=>3);
            return $redata;//东南 4
        }elseif($degree>=157.5&&$degree<=202.5){
            $redata=array('B'=>9,'C'=>2,'D'=>7,'E'=>6,'F'=>1,'G'=>8,'H'=>3,'I'=>4);
            return $redata;//南 9
        }elseif($degree>202.5&&$degree<247.5){
            $redata=array('B'=>2,'C'=>7,'D'=>6,'E'=>1,'F'=>8,'G'=>3,'H'=>4,'I'=>9);
            return $redata;//西南 2
        }elseif($degree>=247.5&&$degree<=292.5){
            $redata=array('B'=>7,'C'=>6,'D'=>1,'E'=>8,'F'=>3,'G'=>4,'H'=>9,'I'=>2);
            return $redata;//西 7
        }elseif($degree>292.5&&$degree<337.5){
            $redata=array('B'=>6,'C'=>1,'D'=>8,'E'=>3,'F'=>4,'G'=>9,'H'=>2,'I'=>7);
            return $redata;//西北 6
        }
    }
    private function shan24($degree){
        //根据角度，给出24山，天3 地1 人2，阴-1 阳1 标识
        if($degree>=337.5&&$degree<352.5){
            //
            $retdata=[1,'壬',1,1,'丙'];
            return $retdata;
        }elseif($degree>=352.5||$degree<7.5){
            //
            $retdata=[1,'子',3,-1,'午'];
            return $retdata;
        }elseif($degree>=7.5&&$degree<22.5){
            //
            $retdata=[1,'癸',2,-1,'丁'];
            return $retdata;
        }elseif($degree>=22.5&&$degree<37.5){
            //
            $retdata=[8,'丑',1,-1,'未'];
            return $retdata;
        }elseif($degree>=37.5&&$degree<52.5){
            //
            $retdata=[8,'艮',3,1,'坤'];
            return $retdata;
        }elseif($degree>=52.5&&$degree<67.5){
            //
            $retdata=[8,'寅',2,1,'申'];
            return $retdata;
        }elseif($degree>=67.5&&$degree<82.5){
            //
            $retdata=[3,'甲',1,1,'庚'];
            return $retdata;
        }elseif($degree>=82.5&&$degree<97.5){
            //
            $retdata=[3,'卯',3,-1,'酉'];
            return $retdata;
        }elseif($degree>=97.5&&$degree<112.5){
            //
            $retdata=[3,'乙',2,-1,'辛'];
            return $retdata;
        }elseif($degree>=112.5&&$degree<127.5){
            //
            $retdata=[4,'辰',1,-1,'戌'];
            return $retdata;
        }elseif($degree>=127.5&&$degree<142.5){
            //
            $retdata=[4,'巽',3,1,'乾'];
            return $retdata;
        }elseif($degree>=142.5&&$degree<157.5){
            //
            $retdata=[4,'巳',2,1,'亥'];
            return $retdata;
        }elseif($degree>=157.5&&$degree<172.5){
            //
            $retdata=[9,'丙',1,1,'壬'];
            return $retdata;
        }elseif($degree>=172.5&&$degree<187.5){
            //
            $retdata=[9,'午',3,-1,'子'];
            return $retdata;
        }elseif($degree>=187.5&&$degree<202.5){
            //
            $retdata=[9,'丁',2,-1,'癸'];
            return $retdata;
        }elseif($degree>=202.5&&$degree<217.5){
            //
            $retdata=[2,'未',1,-1,'丑'];
            return $retdata;
        }elseif($degree>=217.5&&$degree<232.5){
            //
            $retdata=[2,'坤',3,1,'艮'];
            return $retdata;
        }elseif($degree>=232.5&&$degree<247.5){
            //
            $retdata=[2,'申',2,1,'寅'];
            return $retdata;
        }elseif($degree>=247.5&&$degree<262.5){
            //
            $retdata=[7,'庚',1,1,'甲'];
            return $retdata;
        }elseif($degree>=262.5&&$degree<277.5){
            //
            $retdata=[7,'酉',3,-1,'卯'];
            return $retdata;
        }elseif($degree>=277.5&&$degree<292.5){
            //
            $retdata=[7,'辛',2,-1,'乙'];
            return $retdata;
        }elseif($degree>=292.5&&$degree<307.5){
            //
            $retdata=[6,'戌',1,-1,'辰'];
            return $retdata;
        }elseif($degree>=307.5&&$degree<322.5){
            //
            $retdata=[6,'乾',3,1,'巽'];
            return $retdata;
        }else{
            //322.5-337.5
            $retdata=[6,'亥',2,1,'巳'];
            return $retdata;
        }
    }

    private function pan1($zhong,$basePan){
        //顺飞排盘

        $retdata['Z']=$zhong;

        $a = ($basePan['B']+$zhong-5) % 9;
        $retdata['B']= $a>0?$a:$a+9;

        $a = ($basePan['C']+$zhong-5) % 9;
        $retdata['C']= $a>0?$a:$a+9;

        $a = ($basePan['D']+$zhong-5) % 9;
        $retdata['D']= $a>0?$a:$a+9;

        $a = ($basePan['E']+$zhong-5) % 9;
        $retdata['E']= $a>0?$a:$a+9;

        $a = ($basePan['F']+$zhong-5) % 9;
        $retdata['F']= $a>0?$a:$a+9;

        $a = ($basePan['G']+$zhong-5) % 9;
        $retdata['G']= $a>0?$a:$a+9;

        $a = ($basePan['H']+$zhong-5) % 9;
        $retdata['H']= $a>0?$a:$a+9;

        $a = ($basePan['I']+$zhong-5) % 9;
        $retdata['I']= $a>0?$a:$a+9;

        return $retdata;
    }

    private function pan2($zhong,$basePan){
        //逆飞排盘

        $retdata['Z']=$zhong;

        $a = ($basePan['B']+$zhong-5) % 9;
        $retdata['F']= $a>0?$a:$a+9;

        $a = ($basePan['C']+$zhong-5) % 9;
        $retdata['G']= $a>0?$a:$a+9;

        $a = ($basePan['D']+$zhong-5) % 9;
        $retdata['H']= $a>0?$a:$a+9;

        $a = ($basePan['E']+$zhong-5) % 9;
        $retdata['I']= $a>0?$a:$a+9;

        $a = ($basePan['F']+$zhong-5) % 9;
        $retdata['B']= $a>0?$a:$a+9;

        $a = ($basePan['G']+$zhong-5) % 9;
        $retdata['C']= $a>0?$a:$a+9;

        $a = ($basePan['H']+$zhong-5) % 9;
        $retdata['D']= $a>0?$a:$a+9;

        $a = ($basePan['I']+$zhong-5) % 9;
        $retdata['E']= $a>0?$a:$a+9;

        return $retdata;
    }

    public function wxminiappFengshuiA(){
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

    public function wxminiappFengshuiB(){
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

    private function inArrayCheck($val,$array,$key){
        //
        foreach($array as $item){
            if($item[$key]==strval($val))
                return true;
        }
        return false;
    }

    public function wxminiappFengshuiC(){
        //飞星断略，按照年运、向、山，分别飞星（含顺、逆飞），输出每个位置的数字
        $openid = Request::header('x-wx-openid');
        $redata['code']=0;
        $inputdata = Request::post();
        $zhwDB = Db::connect('zhwProjDB');
        
        if(!is_null($openid)){
            $redata['code']=1;

            //
            $degree = $inputdata['degree'];
            $a = $inputdata['timeIndex'];

            if($a==2){
                $yfortune=8;
            }elseif($a==3){
                $yfortune=9;
            }elseif($a==1){
                $yfortune=7;
            }elseif($a==0){
                $yfortune=6;
            }

            $basePan = $this->mydir2($degree);//经过角度偏转的元旦盘
            //var_dump($basePan);
            $sylong= $this->shan24($degree);//获取24山和三元龙信息，$sylong[0],$sylong[2]
            //var_dump($sylong);
            $yearPan = $this->pan1($yfortune,$basePan);//年运顺飞排盘
            //var_dump($yearPan);
            $xiang = $yearPan['B'];
            $shan = $yearPan['F'];

            if($xiang!=5){
                //
                $res = $zhwDB->table('shan24')->where('xiang',$xiang)->where('sanyuan',$sylong[2])->find();
                $tag1 = $res['yinyang'];
            }else{
                $tag1=$sylong[3];
            }

            if($shan!=5){
                //
                $res = $zhwDB->table('shan24')->where('xiang',$shan)->where('sanyuan',$sylong[2])->find();
                $tag2 = $res['yinyang'];
            }else{
                $tag2=$sylong[3];
            }

            if($tag1==1){
                //顺飞
                $xiangPan=$this->pan1($xiang,$basePan);
            }else{
                //逆飞
                $xiangPan=$this->pan2($xiang,$basePan);
            }

            if($tag2==1){
                //顺飞
                $shanPan=$this->pan1($shan,$basePan);
            }else{
                //逆飞
                $shanPan=$this->pan2($shan,$basePan);
            }

            $redata['year']=$yearPan;
            $redata['xiang']=$xiangPan;
            $redata['shan']=$shanPan;
        }

        //飞星断吉凶，针对9运，9 1 2当令，查找每个方位子块的吉凶断语
        $pwr = 9;
        $pwr1 = 1;
        $pwr2 = 2;//严格9 1 宽泛9 1 2
        $myindx = ['Z','B','C','D','E','F','G','H','I'];
        $duanyu = [];

        $seqSpecial = $zhwDB->table('special')->field('cmb')->select()->toArray();

        foreach ($myindx as $key => $value) {

            $myseq = [$redata['shan'][$value],$redata['xiang'][$value],$redata['year'][$value]];
            $myseq2 = 0;
            sort($myseq);

            foreach($myseq as $idv){
                $myseq2= $myseq2*10+$idv;
            }
        
            if($this->inArrayCheck($myseq2,$seqSpecial,'cmb')){
                //存在匹配的情况
                $res1= $zhwDB->table('special')->where('cmb',$myseq2)->find();
                $duanyu[$value]['special']['jx']=$res1['jx'];
                $duanyu[$value]['special']['yw']=$res1['yw'];
                $duanyu[$value]['special']['dy']=$res1['dy'];
            }

            if($redata['xiang'][$value]==$pwr){
                //当 3
                $res = $zhwDB->table('dangling')->where('xiang',$redata['xiang'][$value])->where('shan',$redata['shan'][$value])->find();
                $duanyu[$value][0]['pwr']=3;
                $duanyu[$value][0]['jx']=$res['jx'];
                $duanyu[$value][0]['yw']=$res['yw'];
                $duanyu[$value][0]['lq']=$res['lq'];
                $duanyu[$value][0]['a']=$res['a'];
                $duanyu[$value][0]['b']=$res['b'];
                $duanyu[$value][0]['c']=$res['c'];
                $duanyu[$value][0]['zs']=$res['zs'];
                $duanyu[$value][0]['tag']=$res['tag'];
            }elseif($redata['xiang'][$value]==$pwr1){
                //当 2
                $res = $zhwDB->table('dangling')->where('xiang',$redata['xiang'][$value])->where('shan',$redata['shan'][$value])->find();
                $duanyu[$value][0]['pwr']=2;
                $duanyu[$value][0]['jx']=$res['jx'];
                $duanyu[$value][0]['yw']=$res['yw'];
                $duanyu[$value][0]['lq']=$res['lq'];
                $duanyu[$value][0]['a']=$res['a'];
                $duanyu[$value][0]['b']=$res['b'];
                $duanyu[$value][0]['c']=$res['c'];
                $duanyu[$value][0]['zs']=$res['zs'];
                $duanyu[$value][0]['tag']=$res['tag'];
            }elseif($redata['xiang'][$value]==$pwr2){
                //当 1 失 3
                $res = $zhwDB->table('dangling')->where('xiang',$redata['xiang'][$value])->where('shan',$redata['shan'][$value])->find();
                $duanyu[$value][0]['pwr']=1;
                $duanyu[$value][0]['jx']=$res['jx'];
                $duanyu[$value][0]['yw']=$res['yw'];
                $duanyu[$value][0]['lq']=$res['lq'];
                $duanyu[$value][0]['a']=$res['a'];
                $duanyu[$value][0]['b']=$res['b'];
                $duanyu[$value][0]['c']=$res['c'];
                $duanyu[$value][0]['zs']=$res['zs'];
                $duanyu[$value][0]['tag']=$res['tag'];

                $res = $zhwDB->table('shiling')->where('xiang',$redata['xiang'][$value])->where('shan',$redata['shan'][$value])->find();
                $duanyu[$value][1]['pwr']=3;
                $duanyu[$value][1]['jx']=$res['jx'];
                $duanyu[$value][1]['yw']=$res['yw'];
                $duanyu[$value][1]['lq']=$res['lq'];
                $duanyu[$value][1]['a']=$res['a'];
                $duanyu[$value][1]['b']=$res['b'];
                $duanyu[$value][1]['c']=$res['c'];
                $duanyu[$value][1]['zs']=$res['zs'];
                $duanyu[$value][1]['tag']=$res['tag'];
            }else{
                //失 3
                $res = $zhwDB->table('shiling')->where('xiang',$redata['xiang'][$value])->where('shan',$redata['shan'][$value])->find();
                $duanyu[$value][0]['pwr']=3;
                $duanyu[$value][0]['jx']=$res['jx'];
                $duanyu[$value][0]['yw']=$res['yw'];
                $duanyu[$value][0]['lq']=$res['lq'];
                $duanyu[$value][0]['a']=$res['a'];
                $duanyu[$value][0]['b']=$res['b'];
                $duanyu[$value][0]['c']=$res['c'];
                $duanyu[$value][0]['zs']=$res['zs'];
                $duanyu[$value][0]['tag']=$res['tag'];
            }
        }
        $redata['duanyu']=$duanyu;

        $redata_json  = json_encode($redata);
        return $redata_json;

    }

    public function wxminiappTranslogs(){
        //登记交易日志
        $openid = Request::header('x-wx-openid');
        $redata['code']=0;
        $inputdata = Request::post();
        $zhwDB = Db::connect('zhwProjDB');
        $logdata= [];

        if(!is_null($openid)){
            $redata['code']=1;
            $logdata['openid'] = $openid;
            $logdata['timestamp'] = date('Ymd H:i:s');
            $logdata['type']=$inputdata['type'];
            $logdata['lvl']=$inputdata['lvl'];
            $logdata['gtag']=$inputdata['glvltag'];
            $logdata['degree']=$inputdata['degree'];
            $logdata['timeindex']=$inputdata['timeIndex'];
            $logdata['layout']=$inputdata['layout'];
            $logdata['layoutind']=$inputdata['layoutind'];

            $res = $zhwDB->table('log')->insert($logdata);

        }

        $redata_json  = json_encode($redata);
        return $redata_json; 
    }

    public function wxminiappGetlogs(){
        //获取前10条记录
        $openid = Request::header('x-wx-openid');
        $zhwDB = Db::connect('zhwProjDB');
        $logdata= [];

        if(!is_null($openid)){

            $res = $zhwDB->table('log')->field('timestamp,layout,layoutind,lvl,gtag,degree,timeindex')->where('openid',$openid)->order('timestamp','desc')->limit(10)->select();
        
            $logdata = $res;
            $redata_json = json_encode($logdata);
            return $redata_json;
        }else{return json_encode(['code'=>0]);}
        
    }
}
