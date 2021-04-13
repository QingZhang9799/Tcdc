<?php
namespace app\passengertraffic\Controller;

use think\Controller;
use think\Db;
use think\Loader;
use think\Request;
use think\Log;
use mrmiao\encryption\RSACrypt;

class Userpay{
    //支付
    public function zfbpayment($ini,$crypt){

        $datas = $ini ;
        include_once  "extend/traffic_Alipay/wappay/service/AlipayTradeService.php" ;
        include_once  "extend/traffic_Alipay/aop/AopClient.php";
        include_once  "extend/traffic_Alipay/config.php";
        $title = $datas['title'];
        $out_trade_no = $datas['order_code'];
        $total_amount = $datas['money'];
        $passback_params = $datas['passback_params'];

//        $aop = new \AopClient();

        $aop = new \AopClient();
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = "2021001193654498";
        $aop->rsaPrivateKey = 'MIIEpQIBAAKCAQEAphYpDkz/SSynwNG6XUL1YlzaaSeyOCcB7c/H8CpeNqGLiyptdlI88QS9etpJ0ci8DBXD4lzvo+TtMl1gSNY3186uvXeGvflgFFtX6ajwIvjgOjxpPrpTlKRse3RdhLH/U0nS/H0DYEEtqrgz6/6PCVYNiAbAer/saTMh9CiYK0aa4F32wq+aLbbii14Ncrea03u6Et9sLgnisQM8q1pOWTRE9dwWnnvvd5907Hg14/3fohF0z8mm9T7BWay6++gdFNi4cmE2pUAICNBz5RP4qJINcrzzmCyluz1bZBEUStaKPqbDG7dTHueFqL9FswC6hHE3pjwBjsUPmY6hzL2lHwIDAQABAoIBAQCNwVxJWG6LhhGoAVmPQBcwXRANsFPsmV6MG0wLMB45gqgXn57N3mMlU2Zl9OoMo8fciLcn/SqMOFg7JHeJs0z2ZPG/xMS8YJwgw9XFGOvc7Y50Jhut7lpoA+6TcD5hg4rpC5mI5yp6fSb9DztBsYNj9I6YCys9mZGuOHZCbmNyivA8w0OLxguJT0YEhTPkUKwbNwbQOKtIzjhfJURuMD69/OpGkUc9rL0dd/VNR7K2Xr58JEtjq/7wT6SOh6oHneZvSnTMiot1Ofx3k9YnZ1sAGpZO24nzo6a0BmOVFmCbiBtUECr4j/JtoGV0POO9pWrzQmFKbhL8lmWL3UQcwSABAoGBANiHtJ4+R74ghoOPdN0dp0mt9xQ3f5Bla9WLeYQiWDKJi4dhvEjUa91PNpqDBxjw6n5sygzlWjOrDGaoapo+mBat13xkTt+XJUVXh6Z8zYFrviQBt/a/wC/n/Z4x5KAD7bDrd6qy1Xt3fj/7zkCo7GEQyQOVs+grE+6BPfjP069/AoGBAMRchsgLrzjVHwT4ozgM/Oj2yQg9gBlJhp0MgVtNTqT4+pHrbj1RcMD54zO5a0FHWlBjsRxPPAiK3Lrrox0nmetlBgx/uOTSnqI++fKg0UrUA9iOX/fL6bQbGPemxyfkmYNf323l+fT0pvzwvRDH1z04SFQ5wta0WDj03E2g6tphAoGBAMFPXkwMVCaEiTK5B19E0w3vdu+goI08TqpGK8VwmAb+TwgdlGf85ROeXaRSKCr3IpKd80DSHdaU9axM3Wc5TLSqnP/b2aK6ILcobt2O/DV4CDfDJQbwp9bdKcpqxq6o8zKI9bv6jqb8xkS/PKLzbJ03zA4cP5Kdqty6m6YffOBnAoGACPbwcFGYPk/8io2PZg+xvDEIHIgyQPVKYAEiJrjwzjdPuTm2XrZJH4ZJCSN98gz/4ouqmlBDvWAZk68OU1ZrgIOsMwXhuxCijWWyo5ET/QaQ5mIZn4Z/tOlHyoaisP+OwqCt4qaNMtG4jfOvrgRxnynio3W/n228WV1UcXbXQgECgYEAs4Bg5iJSt1fu/88fzChq5gWmDbZlauANO/m5whW9U9hqzZRZzU39594yFBEhCCvJWU3upOMLAz/b12QkQ7yUXX97xFLAlK5c9nzpOdiilGiNCfPuh2uVLoLqJLhD1gG2XPhuDUPbKijwGKAtijop7S3+Ri4W4beGG2Ir6jM5Yxo=';
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAilI4TYGzgX3L3YVhqTznDIrMaism/BokwAdgrzJtTyNqnnMkfcdMWEyUadSNImzKfkHuq7vZ1F3SsZufmFehuxe2moTH+O9F04pwtbC/Rs/Rd+e6MPgHhqaR09BFmzFRHO28aj7D6ry/uopBashGSrMS8eL+eRGBvYLM4sL7vhBIiLpGWBrf7miEk6YNI02WCrkD5ctPnB6kgTSwbd+y+kyX3bnUD+qKvbuX5IODpWLrYgDUNul8IkuUqe4dGklc5Q0ajJtBgoRT/JnRr5KvF68YLGyySKafnMgvzmwt0VM8H5BpkirQDhiBatRtwAc90cZTqYQUby2L8nfOMZE4dQIDAQAB';
        $request = new \AlipayTradeAppPayRequest();
        $bizcontent = "{\"body\":\"$title\","
            . "\"subject\": \"同城打车\","
            . "\"out_trade_no\": \"$out_trade_no\","
            . "\"timeout_express\": \"15m\","
            . "\"total_amount\": \"$total_amount\","
            . "\"passback_params\": \"$passback_params\","
            . "\"product_code\":\"QUICK_MSECURITY_PAY\""
            . "}";
        //TODO 上线时需要修改为正式地址
        $request->setNotifyUrl("https://php.51jjcx.com/passengertraffic/userpay/alipay");
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
        return $response;
//        return $crypt->response(['code'=>200,'msg'=>'成功','data'=>$response]);
    }

    public function alipay(Request $request)
    {
        $file = fopen('./log.txt', 'a+');
        fwrite($file, "---------------------进来了---------------"."\r\n");
        include_once  "extend/Alipay/wappay/service/AlipayTradeService.php" ;
        include_once  "extend/Alipay/config.php";
        /* *
         * 功能：支付宝服务器异步通知页面
         * 版本：2.0
         * 修改日期：2016-11-01
         * 说明：
         * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
         *************************页面功能说明*************************
         * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
         * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
         * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
         */
        global $config;

        $data = request()->param();

        $alipaySevice = new \AlipayTradeService($config);
//        $alipaySevice->writeLog("支付宝回调开始：======================");
        $result = $alipaySevice->check($data);
        $datas = [];
        $insert = [];
        $alipaySevice->writeLog(var_export($result, true));
        /* 实际验证过程建议商户添加以下校验。
        1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
        4、验证app_id是否为该商户本身。
        */
            //支付宝交易号
            $order = $data['out_trade_no'];
            //第三方渠道号
            $trade_no = $data['trade_no'];
        //交易状态
            $trade_status = $data['trade_status'];
            //交易金额
            $status = $data['body'];
            if ($trade_status == 'TRADE_FINISHED') {
                // todo 交易成功，且可对该交易做操作
            } else if ($trade_status == 'TRADE_SUCCESS') {
                if($data['passback_params'] == "1"){
                    $orderinfo = Db::name('journey_order')->where('order_code', $order)->find();
                    fwrite($file, '-------------成功：---------------' .$orderinfo['id']. '\r\n');
                    // 启动事务
                    Db::startTrans();
                    try {
                        // 处理支付日志

                        $inii['id'] = $orderinfo['id'] ;
                        $inii['status'] = 2 ;
                        $inii['pay_time'] = time() ;
                        $inii['transaction_id'] = $trade_no;
                        $res = Db::name('journey_order')->update($inii);

                        //处理分钱
                        Db::commit();
                    } catch (\Exception $e) {
                        fwrite($file, '-------------报错：---------------' . $e->getMessage() . '\r\n');
                        // 回滚事务
                        Db::rollback();
                    }
                }
            }
        $results = [
            'return_code' => 'SUCCESS',
            'return_msg' => 'ok',
        ];
        $xml = $this->MapConvertXML($results);
        return $xml;
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
    protected function make_order()
    {
        return 'YP' . date('His') . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
    }
    /**
     * name 支付宝订单web退款
     * param string $param['indent_id'] 订单id
     * param string $param['transaction_number'] 订单交易号
     * param string $param['total'] 订单交易金额
     * @return array
     */
    public function refund(){
        include_once 'extend/traffic_Alipay/config.php';
        include_once 'extend/traffic_Alipay/pagepay/service/AlipayTradeService.php';
        include_once 'extend/traffic_Alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php';
        require_once 'extend/traffic_Alipay/pagepay/buildermodel/AlipayTradeRefundContentBuilder.php';

        $param = Request::instance()->param();
        $config = $GLOBALS['config'];
        $indent = Db::name('journey_order')->where('id',$param['order_id'])->find();
        if(empty($indent)){
            return ['code'=>APICODE_ERROR];exit;
        }
        //退票按比例退
        $cancel_rules = Db::name('journey')->where(['id' => $indent['journey_id']])->value('cancel_rules');
        $times = Db::name('journey')->where(['id' => $indent['journey_id']])->value('times');
        $prices = Db::name('jorder_passenger')->where('id','in',input('jorder_passenger_id'))->sum('price');

        $cancel = json_decode($cancel_rules);

        $proportion = $this->check($cancel,$times) ;
        $price = $prices - ($prices * ($proportion/100)) ;
        //退款单号
        $tk_code  = "TK" . date('YmdHis') . rand(0000, 999);
        $file = fopen('./log.txt', 'a+');
        fwrite($file, "退款进来了--------------------------------------"."\r\n");
        fwrite($file, "------------------price:--------------------".number_format($price,2)."\r\n");
        //支付宝交易号
        $trade_no = trim($indent['transaction_id']);
        $refund_amount = number_format($price,2);
        $refund_reason = '正常退款';
        //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
        //构造参数
        $RequestBuilder=new \AlipayTradeRefundContentBuilder();
        $RequestBuilder->setTradeNo($trade_no);
//        $RequestBuilder->setRefundAmount($refund_amount);
        $RequestBuilder->setRefundReason($refund_reason);
        $RequestBuilder->setOutRequestNo($tk_code);
        $RequestBuilder->setOutTradeNo($tk_code);
        $RequestBuilder->setRefundAmount($refund_amount);

        $aop = new \AlipayTradeService($config);
        fwrite($file, "-------------------RequestBuilder:-------------------".json_encode($RequestBuilder)."\r\n");
        $response = $aop->Refund($RequestBuilder);

        fwrite($file, "-------------------response:-------------------".json_encode($response)."\r\n");
        if($response->code==10000&&$response->msg=='Success'){
            //成功之后，更改订单状态
            $jorder_passenger = explode(',' , input('jorder_passenger_id') ) ;
            fwrite($file, "-------------------jorder_passenger:-------------------".json_encode($jorder_passenger)."\r\n");
            foreach ($jorder_passenger as $key=>$value){
                $ini['id'] = $value;
                $ini['is_accepted'] = 5;
                Db::name('jorder_passenger')->update($ini);
            }
            //判断一下，子乘车人，还有没有了
            $jorder_passengers = Db::name('jorder_passenger')->where(['journey_order_id'=>$param['order_id']])->where('is_accepted','neq',"5")->select();
            fwrite($file, "-------------------jorder_passengers:-------------------".json_encode($jorder_passengers)."\r\n");
            if(empty($jorder_passengers)){          //全部为退票之后，子单变为取消
                $inii['id'] = $param['order_id'];
                $inii['status'] = 7;
                Db::name('journey_order')->update($inii);
            }
            return ['code'=>APICODE_SUCCESS,'msg'=>'退款成功'];
        }else{
            return ['code'=>400,'message'=>$response->sub_msg];
            exit;
        }
    }
    //退票区间
    private function check($cancel,$times){
        $proportion = 0 ;
        $time = time() ;
        foreach ($cancel as $key => $value){
            foreach ($value as $k => $v){
                if($k == 1){                //开车前24小时
                    $calculate =  $times  + 24 * 60 *60 ;
                    if($calculate > $time ){
                        $proportion = (int)$v ;
                    }
                }else if($k == 2){         //开车前2小时
                    $calculate =  $times  + 2 * 60 *60 ;
                    if($calculate > $time ){
                        $proportion = (int)$v ;
                    }
                }else if($k == 3){         //开车前1小时
                    $calculate =  $times  + 1 * 60 *60 ;
                    if($calculate > $time ){
                        $proportion = (int)$v ;
                    }
                }else if($k == 4){         //开车前1小时以内
                    $calculate =  $times  + 1 * 60 *60 ;
                    if( ($calculate > $time ) && ($time < $times) ){
                        $proportion = (int)$v ;
                    }
                }else if($k == 5){         //开车后
                    if($times > $time){
                        $proportion = (int)$v ;
                    }
                }
            }
        }
        return $proportion ;
    }
}