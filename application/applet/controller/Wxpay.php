<?php
/**
 * Created by PhpStorm.
 * User: Cathy
 * Date: 2019/3/8
 * Time: 15:13
 */

namespace app\applet\controller;

use think\Controller;
use think\Loader;
use think\Request;
use think\Db;
use app\user\controller\Marketing;

class Wxpay extends Base
{
    public function __construct(Request $request = null)
    {
        require_once "extend/w_pay/lib/WxPay.Api.php";
        require_once "extend/w_pay/example/WxPay.JsApiPay.php";
        require_once "extend/w_pay/example/WxPay.Config.php";
        require_once 'extend/w_pay/example/log.php';

        parent::__construct($request);
    }

    //(实时单/预约单)实际支付
    public function actualPayment()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "is_balance" => input('?is_balance') ? input('is_balance') : null,
            "is_coupon" => input('?is_coupon') ? input('is_coupon') : null,
            "is_redpacket" => input('?is_redpacket') ? input('is_redpacket') : null,
            "is_enterprise" => input('?is_enterprise') ? input('is_enterprise') : null,
            "code" => input('?code') ? input('code') : null,
        ];
        $file = fopen('./log.txt', 'a+');
        $order = Db::name('order')->where(['id' => input('order_id')])->find();    //获取订单信息
        if($order['status'] == 6){
            return ["code" => APICODE_ERROR, "msg" => "该订单已经支付过了" ];
        }
        $balance = Db::name('user')->where(['id' => $order['user_id']])->value('balance');//获取用户余额
        $enterprise_money = Db::name('user')->where(['id' => $order['user_id']])->value('enterprise_money'); //获取企业用户余额
        $distribution_id = Db::name('user')->where(['id' => $order['user_id']])->value('distribution_id');   //获取分销司机id
        $order_money = $order['money'];    //订单金额
        $fare = $order['fare'];            //车费
        $ini['third_party_money'] = 0;                                 //第三方支付金额
        $ini['third_party_type'] = 2;                                  //第三方支付类型 0微信 1支付宝 2其他支付
        //扣除优惠券
        $calculate_coupon_money = 0;        //参与计算优惠券价格
        $discounts_manner = 0;              //优惠方式
        $discounts_details = 0;            //优惠详情

        //使用企业支付
        if (input('is_enterprise') == 1) {
            //先获取企业余额
            $enterprise_id = Db::name('user')->where(['id' =>$order['user_id'] ])->value('enterprise_id') ;
            $enterprise_balance = Db::name('enterprise')->where(['id' => $enterprise_id ])->value('balance') ;
            $result = $this->enterpriseuserconsumption($enterprise_id,$order['user_id'],$order_money);
            if($result['flag'] == 0){
                return ["code" => APICODE_ERROR, "msg" => $result['message']];
            }
            $residue = $enterprise_balance - $order_money ;//$discounts_gross_money - $balance;    //实付金额
            if ($residue >= 0) {
                //将企业余额减少
                Db::name('enterprise')->where(['id' =>$enterprise_id ])->setDec('balance' , $order_money );
                //消费总额增加
                Db::name('enterprise')->where(['id' =>$enterprise_id ])->setInc('gross_amount' , $order_money );

                $ini['balance_payment_money'] = $order_money;                         //余额支付金额
                $ini['actual_amount_money'] = $order_money;                            //实际支付金额
                $ini['discounts_money'] = 0;                         //优惠金额
                $ini['id'] = input('order_id');
                $ini['pay_time'] = time();
                $ini['payer_fut'] = 2 ;
                if ($order['classification'] == '顺风车') {
                    //判断一下，座位是否被占用(4座)
                    if (!empty($order['seat'])) {
                        $orders = Db::name('order')->where(['order_id' => $order['order_id']])->where(['status' => 12])->where('seat', 'in', $order['seat'])->select();     //所有付款的子订单
                        if (!empty($orders)) {
                            return ["code" => APICODE_ERROR, "msg" => "座位已经占用"];
                        }
                    }
                    //判断一下，主单状态
                    $order_id = Db::name('order')->where(['id' => input('order_id')])->value('order_id');
                    $status = Db::name('order')->where(['id' => $order_id])->value('status');
                    if ($status == 12) {
                        $ini['status'] = 12;
                    } else if ($status == 4) {
                        $ini['status'] = 3;
                    }
                    $seating_count = Db::name('order')->where(['id' => $order_id])->value('seating_count');
                    //判断一下，主单还有剩余座位
                    $Ridership = Db::name('order')->where(['order_id' => $order_id])->where(['status' => 12])->value('sum(Ridership)');
                    $residue = $seating_count - $Ridership;
                    if ($residue <= 0) {
                        return ["code" => APICODE_ERROR, "msg" => "座位已占用，请重新下单"];
                    }
                    $this->appointment("顺风车来了", $order['conducteur_id'], $order['id'], 4);
                }else{
                    $ini['status'] = 6;

                    $this->consumptionrecord($enterprise_id,$order['user_id'],$order_money) ;
                    $this->companyMoney($order['conducteur_id'], $order_money, $order, 0);                                 //给司机分钱
                    $this->distributionChauffeur($distribution_id, $order['city_id'], 0);                                       //给分销司机分钱
                }

                Db::name('order')->update($ini);
                return ["code" => APICODE_SUCCESS, "msg" => "支付成功"];
            } else {
                return ["code" => APICODE_ERROR, "msg" => "企业余额不足"];
            }
        }

        if (input('is_coupon') == 1) {       //使用优惠券
            $user_coupon = Db::name('user_coupon')->where(['id' => input('user_coupon_id')])->find();
            //计算优惠券价格
            if ($user_coupon['type'] == 1) {                    //无限制折扣
                $calculate_coupon_money = (10 - $user_coupon['discount']) / 10 * $fare;
            } else if ($user_coupon['type'] == 2) {              //有限制折扣
                if ($fare >= $user_coupon['pay_money']) {
                    $calculate_coupon_money = (10 - $user_coupon['discount']) / 10 * $fare;
                }
            } else if ($user_coupon['type'] == 3) {              //无限制满减
                $calculate_coupon_money = $user_coupon['minus_money'];
            } else if ($user_coupon['type'] == 4) {
                if ($fare >= $user_coupon['pay_money']) { //有限制满减
                    $calculate_coupon_money = $user_coupon['minus_money'];
                }
            } else if ($user_coupon['type'] == 5) {              //N元打车
                $calculate_coupon_money = $fare-$user_coupon['pay_money'];
            }
            $discounts_manner = 1;
            $discounts_details = $user_coupon['id'];
            //将优惠券变成已使用
            $inicoupon['id'] = $user_coupon['id'];
            $inicoupon['is_use'] = 1;
            $inicoupon['create_time'] = time() ;
            Db::name('user_coupon')->update($inicoupon);
        }
        fwrite($file, '------------优惠价格：' . $calculate_coupon_money . '---------------' . '\r\n');
        //扣除红包
        $redpacket_money = 0;           //参与计算红包金额
        if (input('is_redpacket') == 1) {
            $user_redpacket = Db::name('user_redpacket')->where(['id' => input('user_redpacket_id')])->find();
            $user_money = $user_redpacket['money'];                            //红包金额
            $user_redpacket_money = $user_redpacket['ratio'] * $fare;   //按订单金额可以抵扣的钱

            if ($user_money > $user_redpacket_money) {
                $redpacket_money = $user_redpacket_money;
            } else {
                $redpacket_money = $user_money;
            }
            $discounts_manner = 2;
            $iniiredpacket['id'] = $user_redpacket['id'];
            $iniiredpacket['money'] = $redpacket_money;
            Db::name('user_redpacket')->update($iniiredpacket);
        }
        //抽成
        if($calculate_coupon_money >= $fare){  //优惠金额大于订单金额，只优惠订单金额
            $calculate_coupon_money = $fare ;
        }
        $discounts_gross_money = floatval($calculate_coupon_money) + floatval($redpacket_money);                    //总优惠金额 （优惠和红包只有一种）
        fwrite($file, '------------总优惠价格：' . $discounts_gross_money . '---------------' . '\r\n');
        //扣除余额
        $calculate_money = 0;             //参与计算余额
        if (input('is_balance') == 1) {    //使用余额
            $residue_money = $order_money - $discounts_gross_money;                       //剩余金额(去掉优惠总金额)
            if ($residue_money > $balance) {        //余额小于订单金额
                $calculate_money = $balance;
                $ini['balance_payment_money'] = $balance;                               //余额支付金额
                $ini['actual_amount_money'] = $residue_money;                            //实际支付金额
                $ini['discounts_money'] = $discounts_gross_money;                         //优惠金额
                $ini['id'] = input('order_id');
                $ini['status'] = 7;                                                       //待支付
                $ini['discounts_manner'] = $discounts_manner;                            //优惠方式
                $ini['discounts_details'] = $discounts_details;                            //优惠详情
                $ordersum = substr($order['OrderId'], 0, 5) . date('YmdHis') . rand(0000, 999);
                $ini['OrderId'] = $ordersum;                            //优惠详情
                Db::name('order')->update($ini);
                $user = Db::name('user')->where(['id' => $order['user_id']])->setDec('balance', $balance);   //更改用户余额
                $users = Db::name('user')->where(['id' => $order['user_id']])->find();
                $this->BmikeceChange($users, 3, $balance, 2);
                $moneys = $residue_money - $calculate_money;
                fwrite($file, '------------第三方支付金额：' . $moneys . '---------------' . '\r\n');
//                $this->payorder($moneys,input('code'),3,$order['OrderId']);
                //支付
                $logHandler = new \CLogFileHandler("./logs/" . date('Y-m-d') . '.log');
                $log = \Log::Init($logHandler, 15);
                //①、获取用户openid
                $code = input('code');//登录凭证码
                $appid = 'wxfaa1ea1ef2c2be3f';
                $appsecret = 'f79d5094433eebc3ce633c503b691642';

                $tools = new \JsApiPay();
                $requst = Request::instance()->param();
                $openId = $this->sendCode($appid, $appsecret, $code);;
                $price = intval(strval($moneys * 100));
                fwrite($file, '------------微信支付调用接口价格：' . $price . '---------------' . '\r\n');
//                    //②、统一下单
                $input = new \WxPayUnifiedOrder();
                $input->SetBody("支付金额" . $moneys . "元");
                //根据订单来判断选项
                if ($order['classification'] == '实时' || $order['classification'] == '预约'|| $order['classification'] == '代驾') {
                    $input->SetAttach("3".",".$order['id']);
                } else if ($order['classification'] == '顺风车') {
                    $input->SetAttach("4".",".$order['id']);
                }

                $input->SetOut_trade_no($ordersum);
                $input->SetTotal_fee($price);//实际支付金额
                $input->SetTime_start(date("YmdHis"));
                $input->SetTime_expire(date("YmdHis", time() + 600));
                $input->SetNotify_url("https://php.51jjcx.com/applet/Wxpay/notify");
                $input->SetTrade_type("JSAPI");
                $input->SetOpenid($openId);
                $config = new \WxPayConfig();
                $order = \WxPayApi::unifiedOrder($config, $input);
//
                $jsApiParameters = $tools->GetJsApiParameters($order);
                return json_decode($jsApiParameters);
//                return ["code" => APICODE_ERROR,"msg" => "待支付",'money'=>$moneys];
            } else {                             //余额大于订单金额
                $calculate_money = $balance - $residue_money;
                $ini['balance_payment_money'] = $residue_money;                         //余额支付金额
                $ini['actual_amount_money'] = $residue_money;                            //实际支付金额
                $ini['discounts_money'] = $discounts_gross_money;                         //优惠金额
                $ini['discounts_manner'] = $discounts_manner;                            //优惠方式
                $ini['discounts_details'] = $discounts_details;                          //优惠详情
                $ini['id'] = input('order_id');
                //根据订单来判断选项
                if ($order['classification'] == '实时' || $order['classification'] == '预约'|| $order['classification'] == '代驾' || $order['classification'] == '公务车') {
                    $ini['status'] = 6;

                    $this->companyMoney($order['conducteur_id'], $order['fare'], $order, $discounts_gross_money);                            //给司机分钱
                    $this->distributionChauffeur($distribution_id, $order['city_id'], $residue_money);                                       //给分销司机分钱
                    $this->companyBoard($order);                                                                                            //公司流水
                } else if ($order['classification'] == '顺风车') {
                    //判断一下，座位是否被占用
                    if(!empty($order['seat'])){
                        $orders = Db::name('order')->where(['order_id' => $order['order_id'] ])->where(['status' => 12 ])->where('seat','in',$order['seat'])->select();     //所有付款的子订单
                        if( !empty($orders) ){
                            return ["code" => APICODE_ERROR, "msg" => "座位已经占用"];
                        }
                    }
                    //判断一下，主单状态
                    $order_id = Db::name('order')->where(['id' => input('order_id') ])->value('order_id') ;
                    $status = Db::name('order')->where(['id' => $order_id ])->value('status')  ;
                    if($status == 12 ){
                        $ini['status'] = 12;
                    }else if($status == 4 ){
                        $ini['status'] = 3;
                    }
                    $seating_count = Db::name('order')->where(['id' => $order_id ])->value('seating_count') ;
                    //判断一下，主单还有剩余座位
                    $Ridership = Db::name('order')->where(['order_id'=>$order_id])->where(['status' => 12 ])->value('sum(Ridership)');
                    $residue = $seating_count - $Ridership ;
                    if($residue <= 0 ){
                        return ["code" => APICODE_ERROR, "msg" => "座位已占用，请重新下单"];
                    }
                    $this->appointment("顺风车来了", $order['conducteur_id'], $order['id'], 4);
                }
                $ini['pay_time'] = time();
                Db::name('order')->update($ini);
                Db::name('user')->where(['id' => $order['user_id']])->setDec('balance', $residue_money);   //更改用户余额
                $user = Db::name('user')->where(['id' => $order['user_id']])->find();
                $this->BmikeceChange($user, 3, $residue_money, 2);
                return ["code" => APICODE_SUCCESS, "msg" => "支付成功"];
            }
        }
        else {
            fwrite($file, '------------订单价格 ：' . $order_money . '---------------' . '\r\n');
            fwrite($file, '------------优惠价格 ：' . $discounts_gross_money . '---------------' . '\r\n');
            $residue_money = round(($order_money - $discounts_gross_money),2);                       //剩余金额(去掉优惠总金额)

            fwrite($file, '------------付款价格 ：' . $residue_money . '---------------' . '\r\n');
            //支付
            $logHandler = new \CLogFileHandler("./logs/" . date('Y-m-d') . '.log');
            $log = \Log::Init($logHandler, 15);
            //①、获取用户openid
            $code = input('code');//登录凭证码
            $appid = 'wxfaa1ea1ef2c2be3f';
            $appsecret = 'f79d5094433eebc3ce633c503b691642';

            try {
                $tools = new \JsApiPay();
                $requst = Request::instance()->param();
                $openId = $this->sendCode($appid, $appsecret, $code);
                fwrite($file, '------------residue_money ：' . $residue_money . '---------------' . '\r\n');
                $price = round($residue_money * 100);
                fwrite($file, '------------price ：' . $price . '---------------' . '\r\n');
                //②、统一下单
                $input = new \WxPayUnifiedOrder();
                $input->SetBody("支付金额" . $residue_money . "元");

                //根据订单来判断选项
                if ($order['classification'] == '实时' || $order['classification'] == '预约'|| $order['classification'] == '出租车' || $order['classification'] == '代驾' ) {
                    $input->SetAttach("3".",".$order['id']);
                } else if ($order['classification'] == '顺风车') {
                    $input->SetAttach("4".",".$order['id']);
                }
                $ordersum = substr($order['OrderId'], 0, 5) .$order['id'] .date('YmdHis') . rand(0000, 999);
                //更新订单号
                $os['id'] = $order['id'];
                $os['OrderId'] = $ordersum;
//                $os['actual_amount_money'] = $residue_money;                            //实际支付金额
                $os['discounts_money'] = $discounts_gross_money;                         //优惠金额
                $os['discounts_manner'] = $discounts_manner;                            //优惠方式
                $os['discounts_details'] = $discounts_details;                          //优惠详情
                Db::name('order')->update($os);
                $input->SetOut_trade_no($ordersum);
                $input->SetTotal_fee($price);//实际支付金额
                $input->SetTime_start(date("YmdHis"));
                $input->SetTime_expire(date("YmdHis", time() + 600));
                $input->SetNotify_url("https://php.51jjcx.com/applet/Wxpay/notify");
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

    }
    public function cancelPayment(){
        $order = Db::name('order')->where(['id' => input('order_id')])->find();    //获取订单信息
        if (input('is_coupon') == 1) {       //使用优惠券
            $user_coupon = Db::name('user_coupon')->where(['id' => input('user_coupon_id')])->find();
            //将优惠券变成未使用
            $inicoupon['id'] = $user_coupon['id'];
            $inicoupon['is_use'] = 0;
            Db::name('user_coupon')->update($inicoupon);
        }
        if(input('is_balance') == 1){
            $user = Db::name('user')->where(['id' => $order['user_id']])->setInc('balance', $order["balance_payment_money"]);   //更改用户余额
            //删除一条记录
            $user_balance_id = Db::name('user_balance')->where(['user_id'=>$order['user_id']])->where(['money'=>$order["balance_payment_money"]])
                                                                ->where(['type'=>3])->where(['symbol'=>2])->value('id') ;

            Db::name('user_balance')->where(['id'=>$user_balance_id])->delete();
        }
        return ['code' => APICODE_SUCCESS, 'msg' => "取消成功"];
    }
    public function index()
    {
        /**
         *
         * example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
         * 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
         * 请勿直接直接使用样例对外提供服务
         *
         **/
        require_once "extend/w_pay/lib/WxPay.Api.php";
        require_once "extend/w_pay/example/WxPay.JsApiPay.php";
        require_once "extend/w_pay/example/WxPay.Config.php";
        require_once 'extend/w_pay/example/log.php';


        //初始化日志
        $logHandler = new \CLogFileHandler("./logs/" . date('Y-m-d') . '.log');
        $log = \Log::Init($logHandler, 15);
        //①、获取用户openid
        $code = input('code');//登录凭证码
        $appid = 'wxfaa1ea1ef2c2be3f';
        $appsecret = 'f79d5094433eebc3ce633c503b691642';
        try {
            $tools = new \JsApiPay();
            $requst = Request::instance()->param();
            //创建充值订单
            $ordernum = 'CZ' . "00000" . date('Ymdhis');
            $ini['ordernum'] = $ordernum;
            $ini['money'] = input('money');
            $ini['user_id'] = input('user_id');
            $ini['state'] = 0;
            $ini['create_time'] = time();
            //获取用户的城市id
            $city_id = Db::name('user')->where(['id'=>input('user_id')])->value('city_id') ;
            $ini['city_id'] = $city_id ;

            $recharge_order = Db::name('recharge_order')->insertGetId($ini);

            $openId = $this->sendCode($appid, $appsecret, $code);;
//            $openId = 'oIXgZ4-VsxoVzicUt9qtqnd5AMvc';
            $price = intval(strval($requst['money'] * 100));
            //②、统一下单
            $input = new \WxPayUnifiedOrder();
            $input->SetBody("余额充值" . $requst['money'] . "元");
            $input->SetAttach($requst['attach'].",".$recharge_order);
//            $input->SetOut_trade_no("sdkphp".date("YmdHis"));$parameters
            $input->SetOut_trade_no($ordernum);
            //$input->SetTotal_fee(1000);
            $input->SetTotal_fee($price);//实际支付金额
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", time() + 600));
//            $input->SetGoods_tag("test");
//            $input->SetNotify_url("http://paysdk.weixin.qq.com/notify.php");
            $input->SetNotify_url("https://php.51jjcx.com/applet/Wxpay/notify");
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($openId);
            $config = new \WxPayConfig();
            $order = \WxPayApi::unifiedOrder($config, $input);
//            echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
            $jsApiParameters = $tools->GetJsApiParameters($order);
            $res=json_decode($jsApiParameters);
            $data=array("orderId"=>$recharge_order,"res"=>$res);
            return $data;
            //获取共享收货地址js函数参数
            //$editAddress = $tools->GetEditAddressParameters();
        } catch (\Exception $e) {
            \Log::ERROR(json_encode($e));
            return ['code' => $e->getCode(), 'message' => $e->getMessage()];
        }
    }

    public function EnterpriseIndex()
    {
        /**
         *
         * example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
         * 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
         * 请勿直接直接使用样例对外提供服务
         *
         **/
        require_once "extend/w_pay/lib/WxPay.Api.php";
        require_once "extend/w_pay/example/WxPay.JsApiPay.php";
        require_once "extend/w_pay/example/WxPay.Config.php";
        require_once 'extend/w_pay/example/log.php';


        //初始化日志
        $logHandler = new \CLogFileHandler("./logs/" . date('Y-m-d') . '.log');
        $log = \Log::Init($logHandler, 15);
        //①、获取用户openid
        $code = input('code');//登录凭证码
        $appid = 'wxfaa1ea1ef2c2be3f';
        $appsecret = 'f79d5094433eebc3ce633c503b691642';
        try {
            $tools = new \JsApiPay();
            $requst = Request::instance()->param();
            //创建充值订单
            $ordernum = 'CZ' . "00000" . date('Ymdhis');
            $ini['ordernum'] = $ordernum;
            $ini['money'] = input('money');
            $ini['enterprise_id'] = input('enterprise_id');
            $ini['state'] = 0;
            $ini['create_time'] = time();
            $city_id = Db::name('enterprise')->where(['id'=>input('enterprise_id')])->value('city_id') ;
            $ini['city_id'] = $city_id ;
            $recharge_order = Db::name('enterprise_order')->insertGetId($ini);

            $openId = $this->sendCode($appid, $appsecret, $code);;
//            $openId = 'oIXgZ4-VsxoVzicUt9qtqnd5AMvc';
            $price = intval(strval($requst['money'] * 100));
            //②、统一下单
            $input = new \WxPayUnifiedOrder();
            $input->SetBody("余额充值" . $requst['money'] . "元");
            $input->SetAttach($requst['attach'].",".$recharge_order);
//            $input->SetOut_trade_no("sdkphp".date("YmdHis"));$parameters
            $input->SetOut_trade_no($ordernum);
            //$input->SetTotal_fee(1000);
            $input->SetTotal_fee($price);//实际支付金额
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", time() + 600));
//            $input->SetGoods_tag("test");
//            $input->SetNotify_url("http://paysdk.weixin.qq.com/notify.php");
            $input->SetNotify_url("https://php.51jjcx.com/applet/Wxpay/notify");
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($openId);
            $config = new \WxPayConfig();
            $order = \WxPayApi::unifiedOrder($config, $input);
//            echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
            $jsApiParameters = $tools->GetJsApiParameters($order);
            $res=json_decode($jsApiParameters);
            $data=array("orderId"=>$recharge_order,"res"=>$res);
            return $data;
            //获取共享收货地址js函数参数
            //$editAddress = $tools->GetEditAddressParameters();
        } catch (\Exception $e) {
            \Log::ERROR(json_encode($e));
            return ['code' => $e->getCode(), 'message' => $e->getMessage()];
        }
    }

    public function cancalIndex()
    {
        $orderId = input("?orderId") ? input("orderId") : null;
        if ($orderId) {
            Db::name('recharge_order')->where(["id" => $orderId])->update(["state" => -1]);
            return ['code' => APICODE_SUCCESS, 'msg' => "取消成功"];
        } else {
            return ['code' => APICODE_FORAMTERROR, 'msg' => "订单ID不能为空"];
        }
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

        $file = fopen('./log.txt', 'a+');
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
                    $attach = explode(",",$result['attach']); //交易类型：1：充值；2：支付
                    fwrite($file, '-------------WZ微信订单类型：' . $attach . '---------------' . '\r\n');

                    if ($attach[0] == "1") {//支付
                        $orderinfo = Db::name('order')->where('id', $attach[1])->find();
                        // 启动事务
                        Db::startTrans();
                        try {
                            // 处理支付日志
                            fwrite($file, '-------------成功：---------------' . '\r\n');
//                                Log::DEBUG('-------------成功-------------------');
                            $datas['status'] = 6;;
                            $datas['pay_time'] = time();
                            $datas['transaction_id'] = $result['transaction_id'];
                            $datas['actual_amount_money'] = sprintf("%.2f", ($acount /100)) ;
                            $res = Db::name('order')->where('ordernum', $out_trade_no)->update($datas);

                            //处理分钱
                            $this->companyMoney($orderinfo['conducteur_id'], $orderinfo['money'], $orderinfo, $orderinfo['discount_money']);
                            $this->conducteurBoard($orderinfo['conducteur_id'], $orderinfo['money'], $orderinfo['id']);
                            // 提交事务
                            Db::commit();
                        } catch (\Exception $e) {
                            fwrite($file, '-------------报错：---------------' . $e->getMessage() . '\r\n');
//                                Log::DEBUG('-------------报错-------------------'.$e->getMessage());
                            // 回滚事务
                            Db::rollback();
//                                throw new Exception($e->getMessage());
                        }

                    } else if ($attach[0] == "2") {//充值
                        fwrite($file, '----------------------------------------------------------------------充值：----------------------------------' . '\r\n');
                        $orderinfo = Db::name('recharge_order')->where('id', $attach[1])->find();

                        $insert['id'] = $orderinfo['id'];
                        $insert['state'] = 1;
                        $insert['pay_time'] = time();
                        $insert['transaction_id'] = $result['transaction_id'];
                        $insert['is_payment'] = 0 ;
                        Db::name('recharge_order')->update($insert);
                        fwrite($file, '-------------更新状态：---------------' . '\r\n');
//                        //营销
                        $m = new Marketing();
                        $city_id = Db::name('user')->where(['id' => $orderinfo['user_id']])->value('city_id');
                        $extra["recharge_money"] = $orderinfo['money'];
                        $m->judgeActivity($orderinfo['user_id'], $city_id, 4, $extra);
                        fwrite($file, '-------------money：---------------' . $orderinfo['money'] . '\r\n');
                        Db::name('user')->where(['id' => $orderinfo['user_id']])->setInc('balance', $orderinfo['money']);
                        $user = Db::name('user')->where(['id' => $orderinfo['user_id']])->find();
                        $this->BmikeceChange($user, 1, $orderinfo['money'], 1);
                    } else if ($attach[0] == "3") {  //支付订单
                        $orderinfo = Db::name('order')->where('id', $attach[1])->find();
                        fwrite($file, '-------------money：---------------' . $out_trade_no . '\r\n');
                        $datas['status'] = 6;                                                              //已付款
                        $datas['third_party_type'] = 0;                                                  //第三方支付方式
                        $datas['third_party_money'] = sprintf("%.2f", ($acount / 100));                  //第三方支付金额
                        $datas['id'] = $orderinfo['id'];
                        $datas['pay_time'] = time();
                        $datas['transaction_id'] = $result['transaction_id'];
                        $datas['actual_amount_money'] = sprintf("%.2f", ($acount /100))  ;               //实付金额
                        fwrite($file, '-------------order_id：---------------' . $orderinfo['id'] . '\r\n');
                        $res = Db::name('order')->update($datas);

                        $this->companyMoney($orderinfo['conducteur_id'], $orderinfo['fare'], $orderinfo, $orderinfo['discount_money']);               //给司机分钱
                        $user = Db::name('user')->where(['id' => $orderinfo['user_id']])->find();
                        $distribution_id = Db::name('user')->where(['id' => $orderinfo['user_id']])->value('distribution_id');            //获取分销司机id
                        $this->distributionChauffeur($distribution_id, $orderinfo['city_id'], $orderinfo['fare']);                                         //给分销司机分钱
                        $this->companyBoard($orderinfo);                                                                                                   //公司流水
                    } else if ($attach[0] == "4") {       //顺风车订单
                        $orderinfo = Db::name('order')->where('id', $attach[1])->find();
                        fwrite($file, '-------------money：---------------' . $out_trade_no . '\r\n');

                        //判断一下，主单状态
                        $order_id = Db::name('order')->where(['id' => $orderinfo['id'] ])->value('order_id') ;
                        $status = Db::name('order')->where(['id' => $order_id ])->value('status')  ;
                        if($status == 12 ){
                            $datas['status'] = 12;                                      //去接驾
                        }else if($status == 4 ){
                            $datas['status'] = 3;                                      //待出行
                        }else if($status == 9){
                            $datas['status'] = 12;
                        }

                        $datas['third_party_type'] = 0;                         //第三方支付方式
                        $datas['third_party_money'] = $acount/100;                  //第三方支付金额
                        $datas['id'] = $orderinfo['id'];
                        $datas['pay_time'] = time();
                        $datas['actual_amount_money'] = $acount/100;              //实付金额
                        $datas['transaction_id'] = $result['transaction_id'];
                        fwrite($file, '-------------order_id：---------------' . $orderinfo['id'] . '\r\n');
                        $res = Db::name('order')->update($datas);
                        $user = Db::name('user')->where(['id' => $orderinfo['user_id']])->find();
//                        $this->companyMoney($orderinfo['conducteur_id'], $orderinfo['money'], $orderinfo, $orderinfo['discount_money']);               //给司机分钱
                        $this->appointment("顺风车来了", $orderinfo['conducteur_id'], $orderinfo['id'], 4);
                    }else if ($attach[0] == "5") {//企业充值
                        fwrite($file, '-------------企业充值：---------------' . '\r\n');
                        $orderinfo = Db::name('enterprise_order')->where('id', $attach[1])->find();

                        $insert['id'] = $orderinfo['id'];
                        $insert['state'] = 1;
                        $insert['pay_time'] = time();
                        $insert['transaction_id'] = $result['transaction_id'];
                        $insert['is_payment'] = 0 ;
                        Db::name('enterprise_order')->update($insert);
                        fwrite($file, '-------------更新状态：---------------' . '\r\n');
                        Db::name('enterprise')->where(['id' => $orderinfo['enterprise_id']])->setInc('balance', $orderinfo['money']);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
//                    \Log::DEBUG(json_encode(['getMessage' => $e->getMessage(),'getFile' => $e->getFile()]));
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

    //测试
    public function test()
    {
//        $conducteur_id = 186 ;
//        $money = 10 ;
//        $order = [
//            'id'=>2172,
//            'business_id'=>2,
//            'businesstype_id'=>3,
//        ];
//        $discount_money = 0 ;
//        $this->companyMoney($conducteur_id, $money, $order, $discount_money) ;
    }

    //公司流水
    public function companyBoard($order)
    {
        $company = [];
        //总公司
        $company[] = [
            'order_id' => $order['id'],
            'company_id' => 0,
            'money' => $order['parent_company_money'],
        ];
        //分公司
        $company[] = [
            'order_id' => $order['id'],
            'company_id' => $order['company_id'],
            'money' => $order['filiale_company_money'],
        ];
        //上级公司
        $superior_company = Db::name('company')->where(['id' => $order['company_id']])->value('superior_company');
        if ($superior_company > 0) {                              //上级公司id
            $company[] = [
                'order_id' => $order['id'],
                'company_id' => $superior_company,
                'money' => $order['superior_company_money'],
            ];
        }
        Db::name('company_board')->insertAll($company);
    }

    //抽成
    function companyMoney($conducteur_id, $money, $order, $discount_money)
    {

        $company_id = Db::name("conducteur")->where(['id' => $conducteur_id])->value('company_id');  //公司id

        //在获取抽成规则
        $company_ratio = Db::name('company_ratio')->where(['company_id' => $company_id, 'business_id' => $order['business_id'], 'businesstype_id' => $order['business_type_id']])->find();

        //总公司抽成
        $parent_company = ($company_ratio['parent_company_ratio'] / 100) * $money;
        //上级分公司抽成
        $superior_company = ($company_ratio['filiale_company_ratio'] / 100) * $money;  //没有上级值 为 0
        //分公司结算金额
        $compamy_money = $money - (($company_ratio['parent_company_ratio'] / 100) * $money) - (($company_ratio['company_ratio'] / 100) * $money) - $discount_money + $order['surcharge'];
        //分公司利润
        $compamy_profit = ($company_ratio['company_ratio'] / 100) * $money;
        //司机
        $chauffeur_money = $money - (($company_ratio['parent_company_ratio'] / 100) * $money) - (($company_ratio['filiale_company_ratio'] / 100) * $money) - (($company_ratio['company_ratio'] / 100) * $money);

        //司机增加附加费
        $chauffeur_money = $chauffeur_money + $order['surcharge'];

        $inii = [];
        $inii['id'] = $order['id'];
        $inii['parent_company_money'] = $parent_company;
        $inii['superior_company_money'] = $superior_company;
        $inii['filiale_company_money'] = $compamy_profit;
        $inii['chauffeur_income_money'] = $chauffeur_money;
        $inii['filiale_company_settlement'] = $compamy_money;

        Db::name('order')->update($inii);

        //司机加余额
        Db::name('conducteur')->where(['id' => $conducteur_id])->setInc('balance', $chauffeur_money);

        $this->conducteurBoard($conducteur_id, $chauffeur_money, $order['id']);
    }

    //分销给司机分钱
    function distributionChauffeur($id, $city_id, $actually_money)
    {
        $distribution_id = $id;             //分销司机id
        $conducteur = Db::name('conducteur')->where(['id' => $distribution_id])->find();
        //司机状态为正常或者临时禁封，分销状态为正常，才可以正常分钱
        if (($conducteur['status'] == 1 || $conducteur['status'] == 3) && ($conducteur['distribution_state'] == 1)) {
            //判断订单的城市和司机城市在不在同一个地方
            if ($city_id == $conducteur['city_id']) {
                //获取分销规则
                $company_distribution = Db::name('company_distribution')->where(['company_id' => $conducteur['company_id']])->find();

                if ($company_distribution['type'] == 1) {                   //比例
                    $money = $actually_money * ($company_distribution['ratio'] / 100);
                    Db::name('conducteur')->where(['id' => $distribution_id])->setInc('distribution_money', $money);
                } else if ($company_distribution['type'] == 2) {             //区间
                    //获取区间信息
                    $company_distribution_detail = Db::name('company_distribution_detail')->where(['company_distribution_id' => $company_distribution['id']])->select();
                    //获取当前我有多少人
                    $people_count = Db::name('conducteur_distribution_balance')->where(['conducteur_id' => $distribution_id])->count();  //人数
                    foreach ($company_distribution_detail as $key => $value) {
                        if (($people_count >= $value['range_one']) || ($people_count <= $value['range_two'])) {
                            $money = $actually_money * ($value['ratio'] / 100);
                            Db::name('conducteur')->where(['id' => $distribution_id])->setInc('distribution_money', $money);
                        }
                    }
                }
            }
        }
    }

    //用户余额变动
    function BmikeceChange($user, $type, $money, $symbol)
    {
        $ini['user_id'] = $user['id'];
        $ini['type'] = $type;
        $ini['money'] = $money;
        $ini['user_name'] = $user['PassengerName'];
        $ini['phone'] = $user['PassengerPhone'];
        $ini['create_time'] = time();
        $ini['symbol'] = $symbol;

        Db::name('user_balance')->insert($ini);
    }

    //司机流水
    function conducteurBoard($conducteur_id, $money, $order_id)
    {
        $inic['conducteur_id'] = $conducteur_id;
        $inic['title'] = '接单';
        $inic['describe'] = "";
        $inic['order_id'] = $order_id;
        $inic['money'] = $money;
        $inic['symbol'] = 1;
        $inic['create_time'] = time();

        Db::name('conducteur_board')->insert($inic);
    }

    function appointment($title, $uid, $message, $type)
    {
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
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
    //消费记录
    private function consumptionrecord($enterprise_id,$user_id,$money){
        $consumerdetails['enterprise_id'] = $enterprise_id ;
        $consumerdetails['user_id'] = $user_id ;
        $nickname = Db::name('user')->where(['id' => $user_id])->value('nickname') ;
        $consumerdetails['times'] = time() ;
        $consumerdetails['title'] = $nickname."支付订单" ;
        $consumerdetails['money'] = $money ;

        Db::name('enterprise_consumerdetails')->insert($consumerdetails);
    }

    public function EnterpriseRecharge()
    {
        /**
         *
         * example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
         * 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
         * 请勿直接直接使用样例对外提供服务
         *
         **/
        require_once "extend/w_pay/lib/WxPay.Api.php";
        require_once "extend/w_pay/example/WxPay.JsApiPay.php";
        require_once "extend/w_pay/example/WxPay.Config.php";
        require_once 'extend/w_pay/example/log.php';


        //初始化日志
        $logHandler = new \CLogFileHandler("./logs/" . date('Y-m-d') . '.log');
        $log = \Log::Init($logHandler, 15);
        //①、获取用户openid
        $code = input('code');//登录凭证码
        $appid = 'wxfaa1ea1ef2c2be3f';
        $appsecret = 'f79d5094433eebc3ce633c503b691642';
        try {
            $tools = new \JsApiPay();
            $requst = Request::instance()->param();
            //创建充值订单
            $ordernum = 'CZ' . "00000" . date('Ymdhis');
            $ini['ordernum'] = $ordernum;
            $ini['money'] = input('money');
            $ini['enterprise_id'] = input('enterprise_id');
            $ini['state'] = 0;
            $ini['create_time'] = time();
            $recharge_order = Db::name('enterprise_order')->insertGetId($ini);

            $openId = $this->sendCode($appid, $appsecret, $code);;
//            $openId = 'oIXgZ4-VsxoVzicUt9qtqnd5AMvc';
            $price = intval(strval($requst['money'] * 100));
            //②、统一下单
            $input = new \WxPayUnifiedOrder();
            $input->SetBody("余额充值" . $requst['money'] . "元");
            $input->SetAttach("5");
//            $input->SetOut_trade_no("sdkphp".date("YmdHis"));$parameters
            $input->SetOut_trade_no($ordernum);
            //$input->SetTotal_fee(1000);
            $input->SetTotal_fee($price);//实际支付金额
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", time() + 600));
//            $input->SetGoods_tag("test");
//            $input->SetNotify_url("http://paysdk.weixin.qq.com/notify.php");
            $input->SetNotify_url("https://php.51jjcx.com/applet/Wxpay/notify");
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($openId);
            $config = new \WxPayConfig();
            $order = \WxPayApi::unifiedOrder($config, $input);
//            echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
            $jsApiParameters = $tools->GetJsApiParameters($order);
            $res=json_decode($jsApiParameters);
            $data=array("orderId"=>$recharge_order,"res"=>$res);
            return $data;
            //获取共享收货地址js函数参数
            //$editAddress = $tools->GetEditAddressParameters();
        } catch (\Exception $e) {
            \Log::ERROR(json_encode($e));
            return ['code' => $e->getCode(), 'message' => $e->getMessage()];
        }
    }
    //企业用户消费规则
    private function enterpriseuserconsumption($enterprise_id,$user_id,$money){
        $flag = 0 ;
        $message = "" ;
        //获取用户的消费规则
        $user_rule = Db::name('user_rule')->where(['enterprise_id' =>$enterprise_id,'user_id'=>$user_id ])->find();
        $rule_money = $user_rule['money'] ;     //限制额度
        //根据类型
        if($user_rule['type'] == 1){                    //无限制模式
            $flag = 1 ;
        }else if($user_rule['type'] == 2){              //周期模式
            if($user_rule['type_item'] == 1){           //自然月
                //获取当月的时间
                $start_month= strtotime( date('Y-m-d H:i:s',mktime(0,0,0,date('m'),1,date('Y'))) );
                $end_month= strtotime( date('Y-m-d H:i:s',mktime(23,59,59,date('m'),date('t'),date('Y'))) );
                //查询已经消费的当月额度
                $moneys = Db::name('enterprise_consumerdetails')->where(['enterprise_id' =>$enterprise_id ,'user_id'=>$user_id ])
                    ->where('times','gt',$start_month)
                    ->where('times','lt',$end_month)
                    ->value('sum(money)') ;

                //消费限制的金额，是否超过了限制
                $sum_money = $moneys + $money ;
                if($rule_money >= $sum_money){
                    $flag = 1;
                }else{
                    $message = "消费金额超过了限制额度" ;
                }
            }else if($user_rule['type_item'] == 2){     //自然天
                //获取当天时间
                $start_time = strtotime( date("Y-m-d",time())." 00:00:00" ) ;
                $end_time = strtotime( date("Y-m-d",time())." 24:00:00") ;

                //查询已经消费的当月额度
                $moneys = Db::name('enterprise_consumerdetails')->where(['enterprise_id' =>$enterprise_id ,'user_id'=>$user_id ])
                    ->where('times','gt',$start_time)
                    ->where('times','lt',$end_time)
                    ->value('sum(money)') ;

                //消费限制的金额，是否超过了限制
                $sum_money = $moneys + $money ;
                if($rule_money >= $sum_money){
                    $flag = 1;
                }else{
                    $message = "消费金额超过了限制额度" ;
                }
            }else if($user_rule['type_item'] == 3){     //自然周
                //获取自然周时间
                $week_start= strtotime( date('Y-m-d H:i:s',mktime(0,0,0,date('m'),date('d')-date('w')+1,date('Y'))) );
                $week_end= strtotime( date('Y-m-d H:i:s',mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'))) );
                //查询已经消费的当月额度
                $moneys = Db::name('enterprise_consumerdetails')->where(['enterprise_id' =>$enterprise_id ,'user_id'=>$user_id ])
                    ->where('times','gt',$week_start)
                    ->where('times','lt',$week_end)
                    ->value('sum(money)') ;

                //消费限制的金额，是否超过了限制
                $sum_money = $moneys + $money ;
                if($rule_money >= $sum_money){
                    $flag = 1;
                }else{
                    $message = "消费金额超过了限制额度" ;
                }
            }else if($user_rule['type_item'] == 4){     //自然年
                //获取本年的时间
                $start_time= strtotime( date('Y-m-d H:i:s',strtotime(date("Y",time())."-1"."-1")) );
                $end_time=strtotime( date('Y-m-d H:i:s',strtotime(date("Y",time())."-12"."-31 23:59:59")) );
                //查询已经消费的当月额度
                $moneys = Db::name('enterprise_consumerdetails')->where(['enterprise_id' =>$enterprise_id ,'user_id'=>$user_id ])
                    ->where('times','gt',$start_time)
                    ->where('times','lt',$end_time)
                    ->value('sum(money)') ;

                //消费限制的金额，是否超过了限制
                $sum_money = $moneys + $money ;
                if($rule_money >= $sum_money){
                    $flag = 1;
                }else{
                    $message = "消费金额超过了限制额度" ;
                }
            }else if($user_rule['type_item'] == 5){     //自然日
                $start_time = strtotime( $user_rule['start_time'] ) ;
                $end_time = strtotime( $user_rule['end_time'] ) ;
                $time = time() ;
                if( ($time > $start_time ) && ( $end_time < $time) ){
                    //已经消费的金额
                    $moneys = Db::name('enterprise_consumerdetails')->where(['enterprise_id' =>$enterprise_id ,'user_id'=>$user_id ])->value('sum(money)') ;
                    //消费的金额，是否超过了限制金额
                    $sum_money = $moneys + $money ;

                    if($rule_money >= $sum_money){
                        $flag = 1;
                    }else{
                        $message = "消费金额超过了限制额度" ;
                    }
                }else{
                    $message = "限制额度不在消费周期" ;
                }
            }
        }else if($user_rule['type'] == 3){              //固定模式
            //获取某日区间，在不在这里面
            $time = time() ;
            $start_time = strtotime( $user_rule['start_time'] ) ;
            $end_time = strtotime( $user_rule['end_time'] ) ;

            if( ($time > $start_time ) && ( $end_time < $time) ){
                //已经消费的金额
                $moneys = Db::name('enterprise_consumerdetails')->where(['enterprise_id' =>$enterprise_id ,'user_id'=>$user_id ])->value('sum(money)') ;
                //消费的金额，是否超过了限制金额
                $sum_money = $moneys + $money ;

                if($rule_money >= $sum_money){
                    $flag = 1;
                }else{
                    $message = "消费金额超过了限制额度" ;
                }
            }else{
                $message = "限制额度不在消费周期" ;
            }
        }
        $result['flag'] = $flag ;
        $result['message'] = $message ;
        return $result ;
    }
}

