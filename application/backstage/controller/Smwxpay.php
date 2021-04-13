<?php

namespace app\backstage\controller;

use think\Controller;
use think\Db;
use think\Request;

class Smwxpay extends Controller
{
    public function index(Request $request)
    {
        $data = $request->param();//接表单传递来的所有值

        ini_set('date.timezone', 'Asia/Shanghai');//设置时区
        include_once 'extend/Wxpay_saoma/wxpay/Autoloaders.php';//扫描自动加载核心文件
       //$attach = ['fee'=>0.01,'user_id'=>007,'goods_id'=>'0000001','order_id'=>'00002222333'];//微信回调后需要的参数
//        $attach = '0.01,007,0001,0002';
        $attach = '5';
//        $BackUrl = "https://php.51jjcx.com/backstage/Smwxpay/callback";//微信异步回调地址
        $BackUrl = "https://php.51jjcx.com/user/Wxnewpay/notifyurl";//微信异步回调地址
        $notify = new \NativePay();
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("同城打车微信扫描支付");
        $input->SetAttach($attach);//验签时候需要的返回参数，用于处理订单逻辑
        $order_code = "QY" . '0' . date('YmdHis') . rand(0000, 999);
        //创建一个充值订单
        $ini['ordernum'] = $order_code ;
        $ini['money'] = $data['money'] ;
        $ini['enterprise_id'] = $data['enterprise_id'] ;
        $ini['state'] = 0 ;
        $ini['is_payment'] = -1 ;
        $ini['create_time'] = time() ;

        $enterprise_order_id = Db::name('enterprise_order')->insertGetId($ini) ;

        $ordernum = Db::name('enterprise_order')->where(['id' => $enterprise_order_id ])->value('ordernum') ;

        $input->SetOut_trade_no($ordernum);//订单号
        $input->SetTotal_fee($data['money']*100);//支付金额
//        $input->SetTotal_fee(20);//支付金额
        $input->SetTime_start(date("YmdHis"));//支付开始时间
        $input->SetTime_expire(date("YmdHis", time() + 600));//支付结束时间
        $input->SetGoods_tag("test");//附加参数
        $input->SetNotify_url($BackUrl);//回调
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id("123456789");
        $result = $notify->GetPayUrl($input);

        $url2   = $result["code_url"];
        $url2   = base64_encode($url2);

//        $imgs = $this->qrcode($url2) ;

        return [
            "code" =>APICODE_SUCCESS,
            "url" => $url2,
        ];
//        return view('index',['url'=>$url2]) ;
    }

    /**
     * 二维码。
     * @param string $text
     * @return png
     */
    public function qrcode($text) {
        include_once 'extend/Wxpay_saoma/qrcode/qrcode.php';
        $text = base64_decode($text);
        return \QRcode::png($text);
        exit;
    }

     //微信同步地址，用于跳转网站首页等地址
    public function returned()
    {
        $this->redirect("index/index");
    }


    /**
     * 微信异步处理订单
     */
    public function callback() {
        ini_set('date.timezone', 'Asia/Shanghai');
        include_once 'extend/Wxpay_saoma/wxpay/Autoloaders.php';
        error_reporting(E_ERROR);
        $file = fopen('./log.txt', 'a+');
        //初始化日志
        $logHandler = new \CLogFileHandler("../logs/" . date('Y-m-d') . '.log');
//        \Log::Init($logHandler, 15);
//        \Log::DEBUG("------支付开始--------");

        //在PayNotifyCallBack中重写了NotifyProcess，会发起一个订单支付状态查询，其实也可以不去查询，
        //因为从php://input中已经可以获取到订单状态。file_get_contents("php://input")
        //$notify = new \WxPayNotify();
        $notify = new \PayNotifyCallBack();
        $notify->Handle(false);//不使用验签处理
        $result = $notify->GetValues();//获取传递的值
        fwrite($file, "-------------------result:--------------------".json_encode($result)."\r\n");     //司机电话
        if ($result['return_code'] == 'SUCCESS') {
            //订单支付完成，修改订单状态，发货。
            $order = json_encode($result);
//            \Log::DEBUG("------订单信息--".$a."------");
            fwrite($file, "-------------------order:--------------------".json_encode($order)."\r\n");     //司机电话
        }
//        \Log::DEBUG("------支付结束--------");
//        \Log::INFO(str_repeat("=", 20));
    }
}
