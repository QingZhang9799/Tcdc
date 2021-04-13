<?php

namespace app\wxapi\controller;

use think\Config;
use think\Controller;
use think\Request;
use app\wxapi\controller\Wechat;
use think\Db;

class Index extends Controller
{
    protected $appid = 'wxfaa1ea1ef2c2be3f';
    protected $appSecret = 'f79d5094433eebc3ce633c503b691642';

//    public function __construct()
//    {
//        $this->appid = config('wx_config.appid');
//        $this->appSecret = config('wx_config.secret');
//    }

    /**
     *1 先运行此方法，在修改微信后台回调地址  例如 www.cherry-yang.cn
     */
    public function index()
    {

        $w = new Wechat($this->appid, $this->appSecret);
//        echo $w->getAccessToken();die;

        $w->getCode('http://' . $_SERVER['HTTP_HOST'] . '/wxapi/index/getUserInfo/');
//        $w->getOpenCode('http://'.$_SERVER['SERVER_NAME'].'/wxapi/index/getUserInfo/');
    }

    /**
     * 向小程序用户发送订单取消通知
     * @param $openid 用户的openid
     * @param $orderId 订单号 格式为	32位以内数字、字母或符号
     * @param $orderContent 订单内容 格式为 20个以内字符
     * @param $cancelReason 取消原因 20个以内字符
     * @param $tripInfo 行程信息 20个以内字符
     * @param $departTime 出发时间 格式为 2019年10月1日 15:01
     */
    public function sendOrderCancel($openid, $orderId, $orderContent, $cancelReason, $tripInfo, $departTime)
    {
        $w = new Wechat($this->appid, $this->appSecret);
        $accessToken = $w->getAccessToken();
        $data = array("touser" => $openid, "template_id" => "IaDSL5Oxwyrp4VcHMqXg3dmK-3FblIbAxUuX-nzXbyk", "data" => array("character_string1" => array("value" => $orderId), "thing2" => array("value" => $orderContent), "thing4" => array("value" => $cancelReason), "thing5" => array("value" => $tripInfo), "date3" => array("value" => $departTime)));
        list($returnCode, $returnContent) = $this->post("https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=$accessToken", json_encode($data));
    }
    /**
     * 向小程序用户发送订单待支付通知
     * @param $openid 用户的openid
     * @param $money 订单号 格式为	32位以内数字、字母或符号
     * @param $departTime 出发时间 格式为 2019年10月1日 15:01
     * @param $upLocation 出发时间 格式为 2019年10月1日 15:01
     * @param $downLocation 出发时间 格式为 2019年10月1日 15:01
     * @param $statusInfo 出发时间 格式为 2019年10月1日 15:01
     */
    public function sendOrderWaitPay($openid, $money, $departTime,$upLocation, $downLocation,$statusInfo,$page=null)
    {
        $w = new Wechat($this->appid, $this->appSecret);
        $accessToken = $w->getAccessToken();
        $data = array("touser" => $openid, "template_id" => "uHTp2DAY8LUDzFabpbBR93Dt4xsA6zNu7E4L1nJjsgU", "data" => array("amount1" => array("value" => $money), "date2" => array("value" => $departTime), "thing3" => array("value" => $upLocation), "thing4" => array("value" => $downLocation), "thing5" => array("value" => $statusInfo)));
        if($page!=null){
            $data['page']=$page;
        }
        list($returnCode, $returnContent) = $this->post("https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=$accessToken", json_encode($data));
        $this->printLog("微信订阅通知消息结果",json_encode(array($returnCode,$returnContent)));
    }
    /**
     * 向用户发送订单状态变更通知
     * @param $openid 用户的openID
     * @param $statusInfo 订单状态描述 格式为	32位以内数字、字母或符号
     * @param $orderType 订单类型 格式为	32位以内数字、字母或符号
     * @param $upLocation 出发地 格式为	32位以内数字、字母或符号
     * @param $downLocation 目的地 格式为	32位以内数字、字母或符号
     * @param $departTime 出发时间 格式为 2019年10月1日 15:01
     */
    public function sendOrderStatus($openid, $statusInfo, $orderType, $upLocation, $downLocation, $departTime,$page=null)
    {
        $w = new Wechat($this->appid, $this->appSecret);
        $accessToken = $w->getAccessToken();
        $data = array("touser" => $openid, "template_id" => "x3CHIgb1kR9vEvDkmIRK5oML17elLkRVrCXUInZ9Ajc", "data" => array("thing1" => array("value" => $statusInfo), "thing5" => array("value" => $orderType), "thing3" => array("value" => $upLocation), "thing4" => array("value" => $downLocation), "date2" => array("value" => $departTime)));
        if($page!=null){
            $data['page']=$page;
        }
        list($returnCode, $returnContent) = $this->post("https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=$accessToken", json_encode($data));
        $this->printLog("微信订阅通知消息结果",json_encode(array($returnCode,$returnContent)));
    }

    /**
     * 向用户发送行程匹配成功通知
     * @param $openid 用户的openID
     * @param $driverName 司机姓名 10个以内纯汉字或20个以内纯字母或符号
     * @param $carNum 车牌号 8位以内，第一位与最后一位可为汉字，其余为字母或数字
     * @param $upLocation 上车地点  20个以内字符
     * @param $driverNumber 司机电话 17位以内，数字、符号
     * @param $note 备注 	20个以内字符
     */
    public function sendOrderStart($openid, $driverName, $carNum, $upLocation, $driverNumber, $note)
    {
        $w = new Wechat($this->appid, $this->appSecret);
        $accessToken = $w->getAccessToken();
        $data = array("touser" => $openid, "template_id" => "a2RqmKbG_yw8aQDt6ixH_zTBERTWSITFHvFZ99bVeKM", "data" => array("name2" => array("value" => $driverName), "car_number3" => array("value" => $carNum), "thing4" => array("value" => $upLocation), "phone_number6" => array("value" => $driverNumber), "thing10" => array("value" => $note)));
        list($returnCode, $returnContent) = $this->post("https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=$accessToken", json_encode($data));
        $this->printLog("微信订阅通知消息结果",json_encode(array($returnCode,$returnContent)));
    }

    function post($url, $jsonStr)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array($httpCode, $response);
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo(Request $request)
    {
        $code = $request->param('code');
        $w = new Wechat($this->appid, $this->appSecret);
        $UserInfo = $w->getDetail($code);
//        dump($w->getWebJson($code));die;
        //获取用户信息，整合到数组$row中
        $row['nickname'] = $UserInfo->nickname;
        $row['PassengerGender'] = $UserInfo->sex;
//        $row['country'] =$UserInfo->country;
        $city = $UserInfo->city;
//        $row['province'] =$UserInfo->province;
//        $row['headimgurl'] =$UserInfo->headimgurl;
        $row['openid'] = $UserInfo->openid;
//        return $row;
//        dump($row);
        //查询用户是否存在

        //创建用户
        $city_id = Db::name('cn_city')->where(['name' => $city])->value('id');
        $row['city_id'] = $city_id;
        $user = Db::name('user')->insert($row);
        if ($user) {
            return ['code' => 200, 'msg' => '登录成功', 'data' => $user];
        } else {
            return ['code' => 400, 'msg' => '登录失败'];
        }
    }

    public function get_user_list()
    {
        $user = new GetUserList();
        $data = $user->index();
        return view("", ['list' => $data, 'title' => '微信所有关注用户信息列表']);
    }

    //调起微信扫描 功能
    public function wx_scan()
    {
        return view("");
    }

    private function printLog($title, $value)
    {
        $file = fopen('./log.txt', 'a+');
        fwrite($file, "-------------------$title--------------------" . $value . "\r\n");
        fclose($file);
    }
}