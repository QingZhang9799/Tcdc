<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 20-5-15
 * Time: 下午6:28
 */

namespace app\applet\controller;

use think\Controller;
use think\Db;
use app\user\controller\Marketing;

class Xcx extends Controller
{
    public function onLogin()
    {
        // 前台参数
        $encryptedData = urldecode(input('encryptedData'));  //加密后的用户信息
        $code = input('code');//登录凭证码
        $iv = input('iv');//偏移向量，在解密是要用到的
        $city_id = input("city_id");
        // 小程序 appid 和 appsecret
        $appid = 'wxfaa1ea1ef2c2be3f';
        $appsecret = 'f79d5094433eebc3ce633c503b691642';

        // step1
        // 通过 code 用 curl 向腾讯服务器发送请求获取 session_key
        $res = $this->sendCode($appid, $appsecret, $code);
        $session_key = $res['session_key'];

        // step2
        // 用过 session_key 用 sdk 获得用户信息
        $save = [];

        // 相关参数为空判断
        if (empty($session_key) || empty($encryptedData) || empty($iv)) {
            $msg = "信息不全";
            return ['msg' => $msg, 'code' => 203, 'data' => $save];
        }

        //进行解密
        $userinfo = $this->getUserInfo($encryptedData, $iv, $session_key, $appid);

        // 解密成功判断
        if (isset($userinfo['code']) && 10001 == $userinfo['code']) {
            $msg = "请重试"; // 用户不应看到程序细节
            return ['msg' => $msg, 'code' => 203, 'data' => $save];
        }
//        session('myinfo', $userinfo);
//        $save['openid'] = &$userinfo['openId'];
        $user_portrait = Db::name('user_allocation')->where(['id' => 1])->value('user_portrait');
        $save['portrait'] = $user_portrait;
        $save['nickname'] = "同城" . rand(0000, 9999);
        $save['create_time'] = time();
        $save['openid'] = $res["openid"];
        $save['unionid'] = $res["unionid"];
        $save['star'] = 5;
        $save['city_id'] = $city_id;
        $save['PassengerPhone'] = $userinfo['phoneNumber'];

        $user = Db::name('user')->where(['PassengerPhone' => $save['PassengerPhone']])->find();
        if (empty($user)) {
            //若openid存在，则更新该用户的openid的unionid
            $user = Db::name('user')->where(['openid' => $save['openid']])->find();
            if (empty($user)) {
                //不存在插入用户
                $user_id = Db::name('user')->insertGetId($save);
                $user = Db::name('user')->where(['id' => $user_id])->find();
                $m = new Marketing();
                $flag = 1;
                $active = $m->judgeActivity($user_id, $city_id, 1, '');
                $active_active = 0;
                if ($active) {
                    $active_active = 1;
                    $user["active_active"] = $active_active;
                } else {
                    $user["active_active"] = $active_active;
                }
            }else{
                Db::name('user')->where(['openid' => $save['openid']])->setField("unionid",$save['unionid']);
            }
        }
        $msg = "获取成功";

        //返回用户信息
        return ['msg' => $msg, 'code' => 200, 'data' => $user, 'flag' => $flag];
    }

    //获取微信用户信息
    private function sendCode($appid, $appsecret, $code)
    {
        // 拼接请求地址
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='
            . $appid . '&secret=' . $appsecret . '&js_code='
            . $code . '&grant_type=authorization_code';

        $arr = $this->vegt($url);
        $arr = json_decode($arr, true);
        return $arr;
    }

    // curl 封装
    private function vegt($url)
    {
        $info = curl_init();
        curl_setopt($info, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($info, CURLOPT_HEADER, 0);
        curl_setopt($info, CURLOPT_NOBODY, 0);
        curl_setopt($info, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($info, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($info, CURLOPT_URL, $url);
        $output = curl_exec($info);
        curl_close($info);
        return $output;
    }

    //信息解密
    private function getUserInfo($encryptedData, $iv, $session_key, $APPID)
    {
        //进行解密
        $pc = new WxBizDataCrypt($APPID, $session_key);

        $decodeData = "";
        $errCode = $pc->decryptData($encryptedData, $iv, $decodeData);
        //判断解密是否成功
        if ($errCode != 0) {
            return [
                'code' => 10001,
                'message' => 'encryptedData 解密失败',
            ];
        }
        //返回解密数据
        return json_decode($decodeData, true);
    }


}