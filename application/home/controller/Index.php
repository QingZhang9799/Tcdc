<?php

namespace app\home\controller;

use think\Config;
use think\Controller;
use think\Request;
use app\wxapi\controller\Wechat;
class Index extends Controller
{


     public function index()
    {
        $w = new Wechat("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449");
//        echo $w->getAccessToken();die;

        $w->getCode('http://'.$_SERVER['HTTP_HOST'].'/home/index/getUserInfo/');
//        $w->getOpenCode('http://'.$_SERVER['SERVER_NAME'].'/wxapi/index/getUserInfo/');
    }



    /**
     * 获取用户信息
     */
    public function getUserInfo(Request $request)
    {
        $code = $request->param('code');
        $w = new Wechat($this->appid,$this->appSecret);
        $UserInfo = $w->getDetail($code);
//        dump($w->getWebJson($code));die;
        //获取用户信息，整合到数组$row中
        $row['nickname'] = $UserInfo->nickname;
        $row['sex'] =$UserInfo->sex;
        $row['country'] =$UserInfo->country;
        $row['city'] =$UserInfo->city;
        $row['province'] =$UserInfo->province;
        $row['headimgurl'] =$UserInfo->headimgurl;
        $row['openid'] =$UserInfo->openid;
//        return $row;
        dump($row);
    }

}
