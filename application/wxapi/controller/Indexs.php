<?php

namespace app\wxapi\controller;

use think\Config;
use think\Controller;
use think\Request;
use app\wxapi\controller\Wechat;
use think\Db;
use think\Cache;

class Indexs extends Controller
{
    protected $appid = 'wx78c9900b8a13c6bd';
    protected $appSecret = 'a1391017fa573860e266fd801f2b0449';

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

        $w->getCode('http://' . $_SERVER['HTTP_HOST'] . '/wxapi/indexs/info/');
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
    public function info(Request $request)
    {
        $code = $request->param('code');
        $w = new Wechat($this->appid, $this->appSecret);
        $UserInfo = $w->getDetail($code);
//        dump($w->getWebJson($code));die;
        //获取用户信息，整合到数组$row中
        $row['nickname'] = $UserInfo->nickname;
        $row['PassengerGender'] = $UserInfo->sex;
        $row['bjnews_openid'] = $UserInfo->openid;
        $row['unionid'] = $UserInfo->unionid;
        $row['create_time'] = time();
        $city = $UserInfo->city;
//        return $row;
//创建用户
        $city_id = Db::name('cn_city')->where(['name' => $city."市"])->value('id');
        $row['city_id'] = $city_id;
        $row['portrait'] = $UserInfo->headimgurl;
        $file = fopen('./log.txt', 'a+');
        fwrite($file, "-------------------获取用户信息--------------------"."\r\n");

        //判断用户是否存在
        $users = Db::name('user')->where([ 'nickname' => $row['nickname']])->find() ;
        if(!empty($users)){ //查询到信息，就返回，查询不到就插入
            return view('',['user' =>$users ]);
        }else{
           $user_id =  Db::name('user')->insertGetId($row) ;
            $users = Db::name('user')->where(['id' =>$user_id ])->find() ;
            return view('',['user' =>$users ]);
        }
    }

    /**
     * 绑定手机号
     */
    public function phonenumber(){
        $user_id = input('user_id') ;

        if(request()->isPost()){        //绑定司机
            $rand_code = input('rand_code');
            $phone = input('phone');
            $user_ids = input('user_ids');

//            if($rand_code != Cache::get('reset_passwords')){
//                return ['code' => APICODE_ERROR,'msg' => '验证码错误'];
//            }else {
                $ini['id'] = intval($user_ids) ;
                $ini['is_bangding'] = 1 ;
                $ini['PassengerPhone'] = $phone ;
                $res = Db::name('user')->update($ini) ;

                if($res > 0){
                    $this->redirect('/wxapi/Indexs/indexs/user_id/'.intval($user_ids));
                    $message = "手机号绑定成功" ;
                    $openid = Db::name('user')->where(['id'=>intval($user_ids)])->value('bjnews_openid');
                    $this->newsService($openid,$message) ;
                }else{
                    return [
                        "code" =>APICODE_ERROR,
                        "msg" => "绑定失败",
                    ];
                }
            }
//        }
        return view('',['user_id'=>$user_id]) ;
    }
    public function newsService($bjnews_openid,$message){
//        $file = fopen('./log.txt', 'a+');
//        fwrite($file, "-------------------出租车抢单进来了--------------------"."\r\n");
        //获取用户的公众号openid
//        $bjnews_openid = Db::name('user')->where(['id'=>$user_id])->value('bjnews_openid') ;
        $w = new Wechat("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449");
        $res = $w->sendServiceText($bjnews_openid,$message);
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
    public function indexs(){
        $user_id = request()->param('user_id');

        $users = Db::name('user')->where([ 'id' => $user_id ])->find() ;

        return view('',['user' =>$users ]);
    }

    /**
     * 我的优惠券
     */
    public function coupon(){
        $user_id = input('user_id') ;
        $coupon = [] ;
        $count = 0 ;
        $user_coupon = db('user_coupon')->where('is_use','eq',0)->where(['user_id' =>$user_id ])->select();
        foreach ($user_coupon as $key => $value) {
            $flag = $this->activityTimeVerify($value['times']);
            if($flag == 1){
                $count = $count + 1 ;
                //有效期
                $tims = json_decode($value['times'],true) ;
                $endTime = "" ;
                $startTime = date('Y-m-d',$tims['startTime']) ;
                if(!empty($tims['endTime'])){
                    $endTime = date('Y-m-d',$tims['endTime']) ;
                    $validity = $startTime."-".$endTime ;
                }else{
                    $validity = $startTime."之后使用" ;
                }

                $coupon[]=[
                    'id'=>$value['id'],
                    'coupon_name'=>$value['coupon_name'],
                    'times'=>$value['times'],
                    'user_id'=>$value['user_id'],
                    'order_type'=>$value['order_type'],
                    'city_id'=>$value['city_id'],
                    'discount'=>$value['discount'],
                    'min_money'=>$value['min_money'],
                    'man_money'=>$value['man_money'],
                    'minus_money'=>$value['minus_money'],
                    'pay_money'=>$value['pay_money'],
                    'type'=>$value['type'],
                    'is_use'=>$value['is_use'],
                    'type'=>$value['type'],
                    'validity'=>$validity,
                ];
            }
        }

        return view('',['coupon'=>$coupon,'count'=>$count]) ;
    }

    //活动时间验证
    private function activityTimeVerify($times){
        $flag = 0 ;
        $time = time() ;
        $activity = json_decode($times,true) ;
        if(!empty($activity['startTime']) && !empty($activity['endTime'])){      //俩个都有值
            if($time > $activity['startTime'] && $time < $activity['endTime']){
                $flag = 1 ;
            }
        }
        if(!empty($activity['startTime']) && empty($activity['endTime'])){       //起始有值,终点为空
            if($time > $activity['startTime']){
                $flag = 1 ;
            }
        }
        if(empty($activity['startTime']) && !empty($activity['endTime'])){       //起点为空,终点有值
            if($time < $activity['endTime']){
                $flag = 1 ;
            }
        }
        return $flag ;
    }

    /**
     * 我的订单
     */
    public function trip(){
        $user_id = input('user_id') ;

        $orders = Db::name('order')->where(['user_id' =>$user_id ])->select() ;

        foreach ($orders as $key =>$value){
            $str = "" ;
            if($value['status'] == 1 ){
                $str = "叫车中" ;
            }else if($value['status'] == 2 ){
                $str = "待接驾" ;
            }else if($value['status'] == 3 ){
                $str = "待出行" ;
            }else if($value['status'] == 4 ){
                $str = "出行中" ;
            }else if($value['status'] == 5 ){
                $str = "已取消" ;
            }else if($value['status'] == 6 ){
                $str = "已付款" ;
            }else if($value['status'] == 7 ){
                $str = "待付款" ;
            }else if($value['status'] == 8 ){
                $str = "已评价" ;
            }else if($value['status'] == 9 ){
                $str = "已完成" ;
            }else if($value['status'] == 10 ){
                $str = "取消待付款" ;
            }else if($value['status'] == 11 ){
                $str = "待确认费用" ;
            }else if($value['status'] == 12 ){
                $str = "去接驾" ;
            }else if($value['status'] == 15 ){
                $str = "已退款" ;
            }else if($value['status'] == 16 ){
                $str = "16信息补充" ;
            }
            $orders[$key]['status_str'] = $str ;
            $orders[$key]['create_times'] = date('Y-m-d H:i:s',$value['create_time']) ;
        }

        return view('',['orders'=>$orders]) ;
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

    //取消订单显示页面
    public function canceltip(){
       return view('');
    }
}