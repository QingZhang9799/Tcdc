<?php
namespace app\home\controller;

use think\Controller ;
use think\Request ;
use think\Db ;

include_once ROOT_PATH.'/extend/Wexin_share/jssdk.php';
class Friends extends Controller
{
    public function index(Request $request){
        $url = "https://php.51jjcx.com/home/Friends/index" ;
        $jssdk = new \JSSDK("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449",$url);
        $sdk = $jssdk->getSignPackage();
//        dump($sdk);
        $share['appId'] = $sdk['appId'];//公众号的唯一标识
        $share['timestamp'] = $sdk['timestamp'];//生成签名的时间戳
        $share['nonceStr'] = $sdk['nonceStr'];//生成签名的随机串
        $share['signature'] = $sdk['signature'];//签名
        $share['url'] = "https://php.51jjcx.com/home/Friends/index";//分享地址
//        return json($share);
        $share['title'] = '同城打车哈尔滨招募司机';//公众号的唯一标识
        return view('',['share'=>$share]);
    }

    public function addDriver(){
        $parm = input('') ;
        //增加司机
        $ini['DriverName'] = $parm['username'] ;
        $ini['DriverPhone'] = $parm['phone'] ;
        $ini['model'] = $parm['model'] ;
        $ini['create_time'] = time() ;
        $ini['city_id'] = 62 ;
        $ini['company_id'] = 90 ;
        $ini['is_attestation'] = 0 ;

        $conducteur = Db::name('conducteur')->insert($ini) ;
        if($conducteur){
            return [
                "code" => APICODE_SUCCESS ,
                "msg" => "提交成功,等待公司联系你."
            ];
        }else{
            return [
                "code" => APICODE_SUCCESS ,
                "msg" => "提交失败"
            ];
        }
    }

    /**
     * 微信分享 功能封装
     */
    public function share(Request $request)
    {
        $url = $request->param('url');

        $jssdk = new \JSSDK("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449",$url);
        $sdk = $jssdk->getSignPackage();
//        dump($sdk);
        $share['appId'] = $sdk['appId'];//公众号的唯一标识
        $share['timestamp'] = $sdk['timestamp'];//生成签名的时间戳
        $share['nonceStr'] = $sdk['nonceStr'];//生成签名的随机串
        $share['signature'] = $sdk['signature'];//签名
        $share['url'] = $sdk['url'];//分享地址
        return json($share);
    }

    //热力图
    public function chart(){
        return view('');
    }
}
