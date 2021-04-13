<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 19-2-26
 * Time: 上午10:53
 */

namespace app\backstage\controller;

use think\Controller;
use think\Cache;
use think\Db;
use think\Loader;
use QRcode;
use app\user\controller\Marketing;

class Index extends Controller
{
    //web页面
    public function index(){
        $id = input('id');  //司机id

        if(request()->isPost()){        //绑定司机
            $rand_code = input('rand_code');
            $phone = input('phone');

            if($rand_code != Cache::get('reset_passwords')){
                return ['code' => APICODE_ERROR,'msg' => '验证码错误'];
            }else {
                $users = Db::name('user')->where(['PassengerPhone' => $phone ])->find();
                if(!empty($users)){
                    //美团补录
                    $invitation_code = input('invitation_code');
                    if(!empty($invitation_code)){
                        //验证邀请码，是否跟司机相同
                       $invitation_codes = Db::name('conducteur')->where(['id'=>$id])->value('invitation_code') ;
                        if($invitation_codes == $invitation_code){
                            $inii['id'] = $users['id'] ;
                            $inii['invitation_id'] = $id ;
                            Db::name('user')->update($inii) ;
                            $this->redirect('/backstage/index/succ');
                        }else{
                            return ['code' => APICODE_ERROR,'msg' => '邀请码输入不正确'];
                        }
                    }else{
                        return ['code' => APICODE_ERROR,'msg' => '用户已存在'];
                    }
                }else{
                    $ini['PassengerPhone'] = $phone ;
                    $ini['user_pwd'] = encrypt_salt('123456') ;
                    $ini['create_time'] = time();

                    //默认头像和昵称
                    $user_portrait = Db::name('user_allocation')->where(['id'=>1])->value('user_portrait');
                    $ini['portrait'] = $user_portrait ;
                    $ini['nickname'] = "同城" . rand(0000, 9999);

                    $distribution_state = Db::name('conducteur')->where(['id'=>$id])->value('distribution_state');
                    $city_id = Db::name('conducteur')->where(['id'=>$id])->value('city_id');

                    if($distribution_state == 1){  //是分销司机
                           $ini['invitation_id'] =  $id;
                           $ini['distribution_id'] =  $id;
                    }else{                        //不是分销司机
                        $ini['invitation_id'] =  $id;
                    }
                    $ini['city_id'] = $city_id ;
                    $user_id = Db::name('user')->insertGetId($ini);

                    $m = new Marketing();
                    $m->judgeActivity($user_id,$city_id,1,'');

                   $this->redirect('/backstage/index/succ');
                }
            }
        }

        return view('index',['id'=>$id]);
    }

    public function sendphone(){

        $mobile = request()->param('phone');

        $rand_code = rand(100000, 999999);

        $acsResponse = sendSMSS($mobile, $rand_code,"SMS_194060321");

        $res = $acsResponse->Code == 'OK' ? true : false;
        if ($res){
            Cache::set('reset_passwords', (string)$rand_code,3600);
            //发送验证码
            return $rand_code;
        }else {
            return "";
        }

    }

    public function sq()
    {
        $urls = "http://www.qq.com";

        $url = $this->getQrcode($urls);
        dump($url);die;
        return view('',['url'=>$url]);
//        return ['code'=>APICODE_SUCCESS,'data'=>$url];
    }

    public function getQrcode($url){
        $filename = date('Ymdhis') . rand(10000, 99999);
        $errorCorrectionLevel = 'H';
        $matrixPointSize = '6';
        $date = date('Ymd');
        //文件存放的路径
        $pathname = ROOT_PATH.'public'.DS.'uploads'.DS.'image'.DS.'qrcode'.DS.$date;
        // 若目录不存在则创建之
        if (!is_dir($pathname)) {
            mkdir($pathname,0777,true);
        }
        //生成图片
        $file = $pathname.DS.$filename . ".png";
        //存数据库路径
        $sql_path = DS . 'uploads' . DS . 'image' . DS . 'qrcode'. DS . $date . DS . $filename. ".png";

//        Loader::import('phpqrcode.phpqrcode', EXTEND_PATH, ".php");
//        include_once  "extend/phpqrcode/phpqrcode.php" ;
//        Loader::import('phpqrcode.phpqrcode', EXTEND_PATH, ".php");
//        include_once EXTEND_PATH.'phpqrcode/phpqrcode.php';
        Vendor('phpqrcode.phpqrcode');
//        echo 2;
        $Qrcode = new \QRcode ();
//        echo 1;die;
//        dump($Qrcode);die;
        $Qrcode::png(iconv('UTF-8', 'GBK//IGNORE', $url), $file, $errorCorrectionLevel, $matrixPointSize, 2);
//        echo 3;
        //-----------------curl 方式提交----------------------------//
//        $post_url = "http://www.longnew.com/test/index/upload_to_oss";
//        $curl = curl_init();
//        $data = array('file' => new \CURLFile(realpath($sql_path)));
//        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
//        curl_setopt($curl, CURLOPT_URL, $post_url);
//        curl_setopt($curl, CURLOPT_POST, 1 );
//        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($curl, CURLOPT_USERAGENT,"TEST");
//        curl_exec($curl);
//        curl_error($curl);
        //---------------------------------------------//

        //如果二维码图片不存在,则抛出异常
        if (!file_exists($file))
            throw new \Exception('二维码生成失败!');
        dump($sql_path);
//        return $sql_path;
    }

    //反作弊协议
    public function fzbxy(){
        return view('fzbxy');
    }

    //驾驶员协议
    public function jsyxy(){
        return view('jsyxy');
    }
    //平台规则
    public function ptgz(){
        return view('ptgz');
    }
    //司机条款
    public function sjtk(){
        return view('sjtk');
    }
    //用户隐私条款
    public function yhys(){
        return view('yhys');
    }
    //成功页
    public function succ(){
        return view('succ');
    }
    //web页面
    public function user(){
        $id = input('id');  //用户id

        if(request()->isPost()){        //绑定用户
            $rand_code = input('rand_code');
            $phone = input('phone');

            if($rand_code != Cache::get('reset_password')){
                return ['code' => APICODE_ERROR,'msg' => '验证码错误'];
            }else {

                $ini['PassengerPhone'] = $phone ;
                $ini['user_pwd'] = encrypt_salt('123456') ;
                $ini['create_time'] = time();

                //默认头像和昵称
                $user_portrait = Db::name('user_allocation')->where(['id'=>1])->value('user_portrait');
                $ini['portrait'] = $user_portrait ;
                $ini['nickname'] = '用户'.substr(time(),0,4)  ;

                $distribution_state = Db::name('conducteur')->where(['id'=>$id])->value('distribution_state');

                $ini['invite_id'] =  $id;

                $user_id = Db::name('user')->insertGetId($ini);

                $this->redirect('/backstage/index/succ');
            }
        }
        return view('user',['id'=>$id]);
    }
    //注册协议
    public function zcxy(){
        return view('zcxy');
    }
    //车主报名注册协议
    public function wfxy(){
        return view('wfxy');
    }
    //同城打车顺风车服务协议
    public function zzbm(){
        return view('zzbm');
    }
    //企业注册服务协议
    public function qyzc(){
        return view('qyzc');
    }

    //凌云司机端协议
    public function lysi(){
        return view('lysi');
    }
}