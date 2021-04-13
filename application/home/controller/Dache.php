<?php

namespace app\home\controller;
use think\Controller;
use think\Request;
class Dache extends Controller
{
    //作废
    public function index()
    {
        $result = $this->check();
        return view('',['result'=>$result]);
    }
    //作废
    public function map()
    {
        $result = $this->check();

        return view('',['result'=>$result]);
    }
    //初始化token
    public function check()
    {

        $jssdk = new Jssdk("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449");
//        new \JSSDK("wxd1c5257e94cb6f59","954b79418a743883f6b8a31b659483f0",$url);
        $sdk = $jssdk->getSignPackage();
//        dump($sdk);die;
        $share['appId'] = $sdk['appId'];//公众号的唯一标识
        $share['timestamp'] = $sdk['timestamp'];//生成签名的时间戳
        $share['nonceStr'] = $sdk['nonceStr'];//生成签名的随机串
        $share['signature'] = $sdk['signature'];//签名
        $share['url'] = $sdk['url'];//分享地址
        $share['access_token'] =$sdk['access_token'];//access_token
        $share['jsapi_ticket'] =$sdk['jsapi_ticket'];//access_token
        return $share;

    }

}
