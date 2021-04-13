<?php
/**
 * Created by PhpStorm.
 * User: Cathy
 * Date: 2019/3/8
 * Time: 15:13
 */

namespace app\passengertraffic\controller;

use think\Controller;
use think\Loader;
use think\Request;
use think\Db;
use app\user\controller\Marketing;

class Wxpay extends Base
{
    public function __construct(Request $request = null)
    {
        require_once "extend/traffic_w_pay/lib/WxPay.Api.php";
        require_once "extend/traffic_w_pay/example/WxPay.JsApiPay.php";
        require_once "extend/traffic_w_pay/example/WxPay.Config.php";
        require_once 'extend/traffic_w_pay/example/log.php';

        parent::__construct($request);
    }

    //车票预订
    public function TicketReservation()
    {
        $datas = input('');

        $params = [
            "journey_id" => input('?journey_id') ? input('journey_id') : null,
            "board" => input('?board') ? input('board') : null,
            "user_id" => input('?user_id') ? input('user_id') : null,
            "phone" => input('?phone') ? input('phone')  : null,
            "money" => input('?money') ? input('money')  : null,
            "city_id" => input('?city_id') ? input('city_id')  : null,
//            "point" => input('?point') ? input('point')  : null,
            "Vehicle_description" => input('?Vehicle_description') ? input('Vehicle_description')  : null,
            "origin" => input('?origin') ? input('origin')  : null,
            "destination" => input('?destination') ? input('destination')  : null,
            "origin_longitude" => input('?origin_longitude') ? input('origin_longitude')  : null,
            "origin_latitude" => input('?origin_latitude') ? input('origin_latitude')  : null,
            "destination_longitude" => input('?destination_longitude') ? input('destination_longitude')  : null,
            "destination_latitude" => input('?destination_latitude') ? input('destination_latitude')  : null,
            "monovalent" => input('?monovalent') ? input('monovalent')  : null,
            "times" => input('?times') ? input('times')  : null,
        ];

        $order_money = input('money');    //订单金额
        $order_code = "HY" .input('city_id') . '0' . date('YmdHis') . rand(0000, 999);

        //乘车人拦截
//        $flah = $this->intercept(json_decode($datas['passenger'],true),input('journey_id'));
//        if($flah == 1){
//            return [
//                'code' => APICODE_ERROR,
//                'msg' => '乘车人已存在，请重新预订'
//            ];
//        }

        //获取司机的城市id
        $city_id = Db::name('journey')->where(['id'=>input('journey_id')])->value('city_id') ;

        //途经点
        $point = '' ;

        if(!empty($datas['point'])){
            foreach (json_decode($datas['point']) as $key=>$value){
                $point .= $value .',' ;
            }
        }
        $point_items = substr($point , 0 , -1 ) ;

        $ini['user_id'] = input('user_id');
        $ini['journey_id'] = input('journey_id');
        $ini['status'] = 1;                                 //待付款
        $ini['origin'] = input('origin');
        $ini['destination'] = input('destination');
        $ini['price'] = input('money');
        $ini['point'] = $point_items ;
        $ini['Vehicle_description'] = input('Vehicle_description');
        $ini['city_id'] = $city_id;
        $ini['phone'] = input('phone');
        $ini['order_code'] = $order_code;
        $ini['board'] = input('board');
        $ini['debarkation'] = input('debarkation');
        $ini['create_time'] = time() ;
        $ini['times'] = input('times') ;

        //经度和纬度
        $ini['origin_longitude'] = input('origin_longitude');
        $ini['origin_latitude'] = input('origin_latitude');
        $ini['destination_longitude'] = input('destination_longitude');
        $ini['destination_latitude'] = input('destination_latitude');

        $ini['is_payment'] = 1 ;
        $journey_order_id = Db::name('journey_order')->insertGetId($ini) ;

        //乘车人
        $inii= [] ;
        if(!empty($datas['passenger'])){
            foreach (json_decode($datas['passenger'],true) as $k=>$v){
                $inii[]= [
                    'user_id'=> input('user_id'),
                    'name'=>$v['name'],
                    'number'=>$v['number'],
                    'phone'=>$v['phone'],
                    'is_accepted'=>1,
                    'journey_order_id'=>$journey_order_id,
                    'price'=>input('monovalent')
                ];
            }
            Db::name('jorder_passenger')->insertAll($inii);
            //按照乘车人个数减少票数
            $count = count($inii);
            Db::name('journey')->where(['id' => input('journey_id') ])->setDec('residue_ticket' , $count );
            //增加子订单票数
            Db::name('journey_order')->where(['id' => $journey_order_id ])->setInc('ticket_count' , $count );
        }

            //支付
            $logHandler = new \CLogFileHandler("./logs/" . date('Y-m-d') . '.log');
            $log = \Log::Init($logHandler, 15);
            //①、获取用户openid
            $code = input('code');//登录凭证码
            $appid = 'wx617ad132dec1f4d7';
            $appsecret = '2ab953b2b23ac71f209bf7329aff9524';

            try {
                $tools = new \JsApiPay();
                $requst = Request::instance()->param();
                $openId = $this->sendCode($appid, $appsecret, $code);
                $price = intval($order_money * 100);

                $order_codes = Db::name('journey_order')->where(['id'=>$journey_order_id])->value('order_code');

                //②、统一下单
                $input = new \WxPayUnifiedOrder();
                $input->SetBody("支付金额" . $order_money . "元");
                $input->SetAttach("1");
                $input->SetOut_trade_no($order_codes);
                $input->SetTotal_fee($price);//实际支付金额
                $input->SetTime_start(date("YmdHis"));
                $input->SetTime_expire(date("YmdHis", time() + 600));
                $input->SetNotify_url("https://php.51jjcx.com/passengertraffic/Wxpay/notify");
                $input->SetTrade_type("JSAPI");
                $input->SetOpenid($openId);
                $config = new \WxPayConfig();
                $order = \WxPayApi::unifiedOrder($config, $input);

                $jsApiParameters = $tools->GetJsApiParameters($order);
                return json_decode($jsApiParameters);
            } catch (\Exception $e) {
                \Log::ERROR(json_encode($e));
                return ['code' => $e->getCode(), 'message' => $e->getMessage()];
            }
    }

    private function intercept($passenger,$journey_id){
          //获取其他行程订单
         $journey_order = Db::name('journey_order')->alias('j')
                                                             ->field('p.*')
                                                             ->join('mx_jorder_passenger p','p.journey_order_id = j.id','left')
                                                             ->where(['j.journey_id' =>$journey_id ])
                                                             ->select();
         $flag = 0 ;
         foreach ($journey_order as $key=>$value){
            foreach ($passenger as $k=>$v){
                if($value['name'] == $v['name']){
                    $flag = 1 ;
                }
            }
         }
         return $flag ;
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
        return $arr['openid'];
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

    //充值回调
    public function notify()
    {
        $testxml = file_get_contents("php://input");
        $jsonxml = json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA));

        $result = json_decode($jsonxml, true); //转成数组，

        $file = fopen('./view.txt', 'a+');
        fwrite($file, "支付信息：" . json_encode($result) . "\r\n");

        if ($result) {
            //如果成功返回了
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                // 这里写回调更新支付状态以及你的业务逻辑

                // 告知微信回调成功
                Db::startTrans();
                try {
//                    \Log::DEBUG('我的业务逻辑');
                    $out_trade_no = $result['out_trade_no']; //订单号
                    fwrite($file, '-------------WZ微信订单号1：' . $out_trade_no . '---------------' . '\r\n');
                    $acount = $result['total_fee'];  //订单总价格
                    fwrite($file, '-------------WZ微信订单总价格：' . $acount . '---------------' . '\r\n');
                    $attach = $result['attach']; //交易类型：1：充值；2：支付
                    fwrite($file, '-------------WZ微信订单类型：' . $attach . '---------------' . '\r\n');

                    if ($attach == "1") {//支付
                        $orderinfo = Db::name('journey_order')->where('order_code', $out_trade_no)->find();
                        fwrite($file, '-------------成功：---------------' .$orderinfo['id']. '\r\n');
                        // 启动事务
                        Db::startTrans();
                        try {
                            // 处理支付日志

                            $inii['id'] = $orderinfo['id'] ;
                            $inii['status'] = 2 ;
                            $inii['pay_time'] = time() ;
                            $inii['transaction_id'] = $result['transaction_id'];
                            $inii['is_payment'] = 3 ;
                            $res = Db::name('journey_order')->update($inii);

                            //发送激光推送给司机
                            //获取司机id
                            fwrite($file, '-------------发送1：---------------' .'\r\n');
                            $vehicle_id = Db::name('journey')->where(['id'=>$orderinfo['journey_id']])->value('vehicle_id') ;
                            $conducteur_id = Db::name('vehicle_binding')->where(['vehicle_id'=>$vehicle_id])->value('conducteur_id') ;
                            fwrite($file, '-------------发送2：---------------'.$orderinfo['id'].'\r\n');
                            $this->appointment("城际来了", $conducteur_id,$orderinfo['id'], 4);
                            fwrite($file, '-------------发送3：---------------'.$conducteur_id.'\r\n');
                            //处理分钱
                            Db::commit();
                        } catch (\Exception $e) {
                            fwrite($file, '-------------报错：---------------' . $e->getMessage() . '\r\n');
                            // 回滚事务
                            Db::rollback();
                        }
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                }
                $results = [
                    'return_code' => 'SUCCESS',
                    'return_msg' => 'ok',
                ];
                $xml = $this->MapConvertXML($results);
                fwrite($file, '-------------返回：---------------' . $xml . '\r\n');
                return $xml;
            }
        }
    }

    /**
     * map 转 xml
     * @param $map
     * @return string
     */
    private function MapConvertXML($map)
    {
        if (!is_array($map) || count($map) <= 0) {
            throw new RuntimeException('数据异常!');
        }
        $XML = '<xml>';
        foreach ($map as $key => $val) {
            if (is_numeric($val)) {
                $XML .= '<' . $key . '>' . $val . '</' . $key . '>';
            } else {
                $XML .= '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
            }
        }
        $XML .= '</xml>';
        echo $XML;
        exit;
    }

    //退票
    public function Refund(){
        require_once "extend/traffic_w_pay/lib/WxPay.Api.php";
        require_once "extend/traffic_w_pay/example/WxPay.Config.php";

        $file = fopen('./traffic.txt', 'a+');
        fwrite($file, "退款进来了--------------------------------------"."\r\n");

        $param = Request::instance()->param();
        //查找是否有订单
        $indent = Db::name('journey_order')->where('id',$param['order_id'])->find();
        if(empty($indent)){
            return ['code'=>APICODE_ERROR];exit;
        }
        //退票按比例退
        $cancel_rules = Db::name('journey')->where(['id' => $indent['journey_id']])->value('cancel_rules');
        $times = Db::name('journey')->where(['id' => $indent['journey_id']])->value('times');
        $status = Db::name('journey')->where(['id' => $indent['journey_id']])->value('status');
        $prices = Db::name('jorder_passenger')->where('id','in',input('jorder_passenger_id'))->sum('price');

        $cancel = json_decode($cancel_rules);

        $proportion = $this->check($cancel,$times,$status) ;

        $price = sprintf("%.2f", round($prices - ($prices * ($proportion/100)),2) ) ;
        //退款单号
        $tk_code  = "TK" . date('YmdHis') . rand(0000, 999);

        $input = new \WxPayRefund();
        $input->SetTransaction_id($indent['transaction_id']);//微信订单号
        $input->SetOut_refund_no($tk_code);//退款单号
        $input->SetTotal_fee($indent['price']*100);//订单金额
        $input->SetRefund_fee($price*100);//退款金额
        $input->SetOp_user_id('1337863101');//商户号
        $config = new \WxPayConfig();
        $refund = new  \WxPayApi();//\WxPayApi();
        fwrite($file, "-------------------input--------------------".json_encode($input)."\r\n");     //司机电话

        $result = $refund->refund($config,$input);
        //halt($result);
        fwrite($file, "-------------result-----------------------".$result['return_code']."\r\n");
        if(($result['return_code']=='SUCCESS') && ($result['result_code']=='SUCCESS')){
            fwrite($file, "-------------json_encode-----------------------".json_encode($result)."\r\n");
            //成功之后，更改订单状态
            $jorder_passenger = explode(',' , input('jorder_passenger_id') ) ;
            foreach ($jorder_passenger as $key=>$value){
                $ini['id'] = $value;
                $ini['is_accepted'] = 5;
                Db::name('jorder_passenger')->update($ini);
            }
            //判断一下，子乘车人，还有没有了
            $jorder_passengers = Db::name('jorder_passenger')->where(['journey_order_id'=>$param['order_id']])->where('is_accepted','neq',"5")->select();
            if(empty($jorder_passengers)){          //全部为退票之后，子单变为取消
                $inii['id'] = $param['order_id'];
                $inii['status'] = 7;
                Db::name('journey_order')->update($inii);
            }
            //退款之后，把占用的票，还原回去
            Db::name('journey')->where(['id'=>$indent['journey_id'] ])->setInc('residue_ticket',$indent['ticket_count']) ;
            return ['code'=>APICODE_SUCCESS,'msg'=>'退款成功'];
        }else if(($result['return_code']=='FAIL') || ($result['result_code']=='FAIL')){
            //原因
            $reason = (empty($result['err_code_des'])?$result['return_msg']:$result['err_code_des']);
            return ['code'=>APICODE_ERROR,'msg'=>$reason];
        }else{
            return ['code'=>APICODE_ERROR,'msg'=>'退款失败'];
        }
    }

    //退票方法
    public function RefundList($order_id,$passenger_id){


    }

    //退票区间
    private function check($cancel,$times,$status){
        $proportion = 0 ;
        $time = time() ;
        $cancelRule=array();
        foreach ($cancel as $val){
            foreach ($val as $key=>$value){
                $cancelRule[$key]=$value;
            }
        }
        $calculate=$times-$time;
        if($calculate>24*60*60){
            $proportion=(int)$cancelRule["1"];
        }elseif ($calculate>2*60*60){
            $proportion=(int)$cancelRule["2"];
        }elseif($calculate>1*60*60){
            $proportion=(int)$cancelRule["3"];
        }elseif($calculate>0){
            $proportion=(int)$cancelRule["4"];
        }else{
            $proportion = (int)$cancelRule["5"] ;
        }
        if($status==2){
            $proportion = (int)$cancelRule["5"] ;
        }
        return $proportion ;
    }

    //待支付
    public function NoPayment()
    {
        $datas = input('');

        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "user_id" => input('?user_id') ? input('user_id') : null,
        ];
        $journey_order = Db::name('journey_order')->where(['id'=>input('order_id')])->find();
        $order_money = $journey_order['price'];    //订单金额
        //支付
//        $logHandler = new \CLogFileHandler("./logs/" . date('Y-m-d') . '.log');
//        $log = \Log::Init($logHandler, 15);
        //①、获取用户openid
        $code = input('code');//登录凭证码
        $appid = 'wx617ad132dec1f4d7';
        $appsecret = '2ab953b2b23ac71f209bf7329aff9524';

        try {
            $tools = new \JsApiPay();
            $requst = Request::instance()->param();
            $openId = $this->sendCode($appid, $appsecret, $code);
            $price = intval($order_money * 100);

            $order_codes = "HY" .input('city_id') . '0' . date('YmdHis') . rand(0000, 999);
            $ini['id'] = input('order_id') ;
            $ini['order_code'] = $order_codes ;
            Db::name('journey_order')->update($ini) ;
//            $order_codes = Db::name('journey_order')->where(['id'=>input('order_id')])->value('order_code');

            //②、统一下单
            $input = new \WxPayUnifiedOrder();
            $input->SetBody("支付金额" . $order_money . "元");
            $input->SetAttach("1");
            $input->SetOut_trade_no($order_codes);
            $input->SetTotal_fee($price);//实际支付金额
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", time() + 600));
            $input->SetNotify_url("https://php.51jjcx.com/passengertraffic/Wxpay/notify");
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($openId);
            $config = new \WxPayConfig();
            $order = \WxPayApi::unifiedOrder($config, $input);

            $jsApiParameters = $tools->GetJsApiParameters($order);
            return json_decode($jsApiParameters);
        } catch (\Exception $e) {
            \Log::ERROR(json_encode($e));
            return ['code' => $e->getCode(), 'message' => $e->getMessage()];
        }
    }

    function appointment($title, $uid, $message, $type)
    {
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("6052ba408cae4f0c51d5f695:4e44c5e79552ce7672cab6c2");
//        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
        $header = array(
            "Authorization:Basic $base64",
            "Content-Type:application/json"
        );
        $param = array("platform" => "all", "audience" => array("tag" => array("D_$uid")), "message" => array("msg_content" => $message . "," . $type, "title" => $title));
        $params = json_encode($param);
        $res = $this->request_post($url, $params, $header);
        $res_arr = json_decode($res, true);
    }

// 极光推送提交
    function request_post($url = "", $param = "", $header = "")
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init(); // 初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); // 抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); // 设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); // post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch); // 运行curl

        curl_close($ch);
        return $data;
    }
}

