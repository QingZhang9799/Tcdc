<?php
namespace app\user\Controller;

use think\Controller;
use think\Db;
use think\Loader;
use think\Request;
use think\Log;
use mrmiao\encryption\RSACrypt;

class Userpay{
    //充值
    public function zfbsqpost(RSACrypt $crypt){
        $datas = $crypt->request();
        include_once  "extend/Alipay/wappay/service/AlipayTradeService.php" ;
        include_once  "extend/Alipay/aop/AopClient.php";
        include_once  "extend/Alipay/config.php";
        $title = $datas['title'];
        $out_trade_no = $datas['order_code'];
        $total_amount = $datas['money'];
        $order_id = Db::name('recharge_order')->where(['ordernum'=>$datas['order_code']])->value('id') ;
        $passback_params = $datas['passback_params'].",".$order_id;

//        $aop = new \AopClient();
        $aop = new \AopClient();
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = "2021001181671546";
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
        $request->setNotifyUrl("https://php.51jjcx.com/user/userpay/alipay");
        $request->setBizContent($bizcontent);

        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);

        return $crypt->response(['code'=>200,'msg'=>'成功','data'=>$response]);
    }
    //企业充值
    public function zfbsqenterprise(RSACrypt $crypt){
        $datas = $crypt->request();
        include_once  "extend/Alipay/wappay/service/AlipayTradeService.php" ;
        include_once  "extend/Alipay/aop/AopClient.php";
        include_once  "extend/Alipay/config.php";
        $title = $datas['title'];
        $out_trade_no = $datas['order_code'];
        $total_amount = $datas['money'];
        $order_id = Db::name('recharge_order')->where(['ordernum'=>$datas['order_code']])->value('id') ;

        $passback_params = $datas['passback_params'].",".$order_id;

//        $aop = new \AopClient();
        $aop = new \AopClient();
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = "2021001181671546";
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
        $request->setNotifyUrl("https://php.51jjcx.com/user/userpay/alipay");
        $request->setBizContent($bizcontent);

        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);

        return $crypt->response(['code'=>200,'msg'=>'成功','data'=>$response]);
    }

    //支付
    public function zfbpayment($ini,$crypt){

        $datas = $ini ;
        include_once  "extend/Alipay/wappay/service/AlipayTradeService.php" ;
        include_once  "extend/Alipay/aop/AopClient.php";
        include_once  "extend/Alipay/config.php";
        $title = $datas['title'];
        $out_trade_no = $datas['order_code'];
        $total_amount = $datas['money'];
        $passback_params = $datas['passback_params'];

//        $aop = new \AopClient();

        $aop = new \AopClient();
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = "2021001181671546";
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
        $request->setNotifyUrl("https://php.51jjcx.com/user/userpay/alipay");
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


//        if ($result) { // 验证成功
//            $alipaySevice->writeLog("验证成功：" . $result);

//            $a = json_encode($data);

//            $alipaySevice->writeLog("json：". $a);
            //支付宝交易号
            $order = $data['out_trade_no'];
            //第三方渠道号
            $trade_no = $data['trade_no'];

        //交易状态
            $trade_status = $data['trade_status'];
            //交易金额
            // $total_amount = $arr['money'];

            $status = $data['body'];
            // $as = explode(",",$status);
            fwrite($file, "-------------------trade_status :--------------". $trade_status."\r\n");

            // $alipaySevice->writeLog('支付-类型：' . $as[0]);

            if ($trade_status == 'TRADE_FINISHED') {
                // todo 交易成功，且可对该交易做操作
            } else if ($trade_status == 'TRADE_SUCCESS') {
                $passback_params = explode(',',$data['passback_params'])  ;
                if($passback_params[0] == "1"){
                    $orderinfo = Db::name('order')->where('id',$passback_params[1])->find();
                    fwrite($file, "---------------------进来了---------------"."\r\n");
                    $checkinfo = $orderinfo['status'];

                    if($checkinfo ==7) {
//                        $alipaySevice->writeLog("订单状态是未支付可以修改：" . $checkinfo);

                        //Db::startTrans();   //  开启事务
                        try {
                            $acount = $data['total_amount'];
                            $datas['status'] = 6;
                            $datas['pay_time'] = time();
                            $datas['id'] = $orderinfo['id'] ;
                            $datas['actual_amount_money'] = $acount;
                            $datas['third_party_money'] = $acount;
                            $datas['third_party_type'] = 1  ;
                            $datas['transaction_id'] = $trade_no ;
                            $res = Db::name('order')->update($datas);

                            $this->companyMoney($orderinfo['conducteur_id'],$orderinfo['money'],$orderinfo,$data['discount_money']);             //给司机分钱
                            $distribution_id = Db::name('user')->where(['id'=>$orderinfo['user_id']])->value('distribution_id');   //获取分销司机id
                            if(empty($orderinfo['mtorderid'])){
                                $this->distributionChauffeur($distribution_id,$orderinfo['city_id'],$acount);                                           //给分销司机分钱
                            }
                            $this->companyBoard($orderinfo);

//                            $alipaySevice->writeLog("成功");
                            //Db::commit();
                            echo "success";
                        } catch (\Exception $e) {
                            $alipaySevice->writeLog("报错".$e->getMessage());
                            //Db::rollback();  // 回滚事务
                            echo "fail";
                        }
                    }elseif($checkinfo==8){
//                        Log::DEBUG('-------------订单状态已取消-------------------');
                        echo "fail";
                    }else{
                        $alipaySevice->writeLog("订单状态已经是已支付");
                        echo "success";
                    }
                }elseif($passback_params[0] == "2"){
                    $orderinfo = Db::name('recharge_order')->where('id',$passback_params[1])->find();;
                    if($orderinfo['state'] == 0){
                        $insert['state'] = '1';
                        $insert['id'] = $orderinfo['id'] ;
                        $insert['is_payment'] = 1 ;
                        $insert['pay_time'] = time() ;
                        $insert['transaction_id'] = $trade_no ;
                        Db::name('recharge_order')->where('id',$orderinfo['id'])->update($insert);

                        fwrite($file, "---------------价格----------".$orderinfo['money']."--------"  . "\r\n");

                        Db::name('user')->where('id',$orderinfo['user_id'])->setInc('balance',$orderinfo['money']);

                        $user = Db::name('user')->where(['id' => $orderinfo['user_id']])->find();
                        $this->BmikeceChange($user,1,$orderinfo['money'],1);

                        $m = new Marketing();
                        $city_id = Db::name('user')->where(['id'=>$orderinfo['user_id']])->value('city_id');
                        $extra["recharge_money"] = $orderinfo['fare'] ;
                        $m->judgeActivity($orderinfo['user_id'] , $city_id ,4 , $extra);
                    }
                }elseif($passback_params[0] == "3"){
                    fwrite($file, "-------------------顺风车---------------------" . "\r\n");
                    $orderinfo = Db::name('order')->where('id', $passback_params[1])->find();

                    //判断一下，主单状态
                    $order_id = Db::name('order')->where(['id' => $orderinfo['id'] ])->value('order_id') ;
                    $status = Db::name('order')->where(['id' => $order_id ])->value('status')  ;
                    if($status == 12 ){
//                            $ini['status'] = 12;
                        $datas['status'] = 12;                                      //去接驾
                    }else if($status == 4 ){
//                            $ini['status'] = 3;
                        $datas['status'] = 3;                                      //待出行
                    }

                    $datas['third_party_type'] = 1;                         //第三方支付方式
                    $datas['third_party_money'] = $data['total_amount'];  //第三方支付金额
                    $datas['id'] = $orderinfo['id'];
                    $datas['pay_time'] = time();
                    $datas['actual_amount_money'] = $data['total_amount']; //实付金额
                    $datas['transaction_id'] = $trade_no ;
                    fwrite($file, '-------------order_id：---------------' . $orderinfo['id'] . '\r\n');
                    $res = Db::name('order')->update($datas);
                    $user = Db::name('user')->where(['id' => $orderinfo['user_id']])->find();
//                        $this->companyMoney($orderinfo['conducteur_id'], $orderinfo['money'], $orderinfo, $orderinfo['discount_money']);               //给司机分钱
                    $this->appointment("顺风车来了", $orderinfo['conducteur_id'], $orderinfo['id'], 4);
                }elseif($passback_params[0] == "5"){
                    $orderinfo = Db::name('enterprise_order')->where('id',$passback_params[1])->find();;
                    if($orderinfo['state'] == 0){
                        $insert['state'] = '1';
                        $insert['id'] = $orderinfo['id'] ;
                        $insert['is_payment'] = 1 ;
                        $insert['pay_time'] = time() ;
                        $insert['transaction_id'] = $trade_no ;
                        Db::name('enterprise_order')->where('id',$orderinfo['id'])->update($insert);

                        fwrite($file, "---------------价格----------".$orderinfo['money']."--------"  . "\r\n");

                        Db::name('enterprise')->where('id',$orderinfo['enterprise_id'])->setInc('balance',$orderinfo['money']);

                    }
                }
            }
//            echo "success";
//            die ();
        $results = [
            'return_code' => 'SUCCESS',
            'return_msg' => 'ok',
        ];
        $xml = $this->MapConvertXML($results);
        fwrite($file, '-------------返回：---------------' . $xml . '\r\n');
        return $xml;
//        } else {
//            //验证失败
//            $alipaySevice->writeLog("fail");
//            echo "fail";    //请不要修改或删除
//        }
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
    //抽成
    function companyMoney($conducteur_id,$money,$order,$discount_money){

        $company_id = Db::name("conducteur")->where(['id'=>$conducteur_id])->value('company_id');  //公司id

        //在获取抽成规则
        $company_ratio = Db::name('company_ratio')->where(['company_id'=>$company_id,'business_id'=>$order['business_id'],'businesstype_id'=>$order['business_type_id']])->find();

        //总公司抽成
        $parent_company = ($company_ratio['parent_company_ratio']/100) * $money ;
        //上级分公司抽成
        $superior_company = ($company_ratio['filiale_company_ratio']/100) * $money ;  //没有上级值 为 0
        //分公司结算金额
        $compamy_money = $money - ( ($company_ratio['parent_company_ratio']/100) * $money) - ( ($company_ratio['company_ratio']/100) * $money ) - $discount_money + $order['surcharge'] ;
        //分公司利润
        $compamy_profit = ($company_ratio['company_ratio']/100) * $money ;
        //司机
        $chauffeur_money = $money - ( ($company_ratio['parent_company_ratio']/100) * $money) - ( ($company_ratio['filiale_company_ratio']/100) * $money ) - ( ($company_ratio['company_ratio']/100) * $money) ;

        //司机增加附加费
        $chauffeur_money = $chauffeur_money + $order['surcharge'] ;

        $inii = [];
        $inii['id'] = $order['id'];
        $inii['parent_company_money'] = $parent_company;
        $inii['superior_company_money'] = $superior_company;
        $inii['filiale_company_money'] = $compamy_profit;
        $inii['chauffeur_income_money'] = $chauffeur_money;
        $inii['filiale_company_settlement'] = $compamy_money;

        Db::name('order')->update($inii);

        //司机加余额
        Db::name('conducteur')->where(['id'=>$conducteur_id])->setInc('balance',$chauffeur_money);

        $this->conducteurBoard($conducteur_id,$chauffeur_money,$order['id']);
    }
    //司机流水
    function conducteurBoard($conducteur_id,$money,$order_id){
        $inic['conducteur_id'] = $conducteur_id ;
        $inic['title'] = '接单' ;
        $inic['describe'] = "" ;
        $inic['order_id'] = $order_id ;
        $inic['money'] = $money ;
        $inic['symbol'] = 1 ;
        $inic['create_time'] = time() ;

        Db::name('conducteur_board')->insert($inic);
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
    //公司流水
    public function companyBoard($order){
        $company = [] ;
        //总公司
        $company[]=[
            'order_id'=>$order['id'],
            'company_id'=>0,
            'money'=>$order['parent_company_money'],
        ];
        //分公司
        $company[]=[
            'order_id'=>$order['id'],
            'company_id'=>$order['company_id'],
            'money'=>$order['filiale_company_money'],
        ];
        //上级公司
        $superior_company = Db::name('company')->where([ 'id' => $order['company_id'] ])->value('superior_company');
        if($superior_company > 0){                              //上级公司id
            $company[]=[
                'order_id'=>$order['id'],
                'company_id'=>$superior_company,
                'money'=>$order['superior_company_money'],
            ];
        }
        Db::name('company_board')->insertAll($company);
    }
    //用户余额变动
    function BmikeceChange($user, $type, $money,$symbol)
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
}