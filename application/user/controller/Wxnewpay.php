<?php

namespace app\user\controller;

use think\Controller;
use mrmiao\encryption\RSACrypt;
use think\Db;

class Wxnewpay extends Controller
{
    public function getSign($params) {
        ksort($params);        //将参数数组按照参数名ASCII码从小到大排序
        foreach ($params as $key => $item) {
            if (!empty($item)) {         //剔除参数值为空的参数
                $newArr[] = $key.'='.$item;     // 整合新的参数数组
            }
        }
        $stringA = implode("&", $newArr);         //使用 & 符号连接参数
//        $stringSignTemp = $stringA."&key=".'f56f580f9ba6e39ce2dc54e1835a22eb';        //拼接key
        $stringSignTemp = $stringA."&key=".'2279ea9fcb9b92c0fb9018a2bf3bff6a';        //拼接key
        // key是在商户平台API安全里自己设置的
        $stringSignTemp = MD5($stringSignTemp);       //将字符串进行MD5加密
        $sign = strtoupper($stringSignTemp);      //将所有字符转换为大写
        return $sign;
    }

    public function ToXml($data=array())
    {
        if(!is_array($data) || count($data) <= 0)
        {
            return '数组异常';
        }

        $xml = "<xml>";
        foreach ($data as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }


    /*
         * Effect    微信App支付XML转ARR
         * author    YangYunHao
         * email     1126420614@qq.com
         * time      2019-02-14 11:12:31
         * parameter request:请求参数
         * return    data:请求数据
         * */
    public function FromXml($xml)
    {
        if(!$xml){
            echo "xml数据异常！";
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }

    /*
     * Effect    curl 跨域请求
     * author    YangYunHao
     * email     1126420614@qq.com
     * time
     * parameter url: 请求地址,request: 请求参数,hearer:请求头,method:请求方式
     * Guardian
     * Guardian-email
     * Guardian-time
     * */
    public function curl($url = '',$request = [],$header = [],$method = 'POST'){
        $header[] = 'Accept-Encoding: gzip, deflate';//gzip解压内容
        $ch = curl_init();   //1.初始化
        curl_setopt($ch, CURLOPT_URL, $url); //2.请求地址
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);//3.请求方式
        //4.参数如下
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//https
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');//模拟浏览器
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');

        if ($method == "POST") {//5.post方式的时候添加数据
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);//6.执行

        if (curl_errno($ch)) {//7.如果出错
            return curl_error($ch);
        }
        curl_close($ch);//8.关闭
        return $tmpInfo;
    }

    //充值
    public function wxpaypost(RSACrypt $crypt){

        $datas = $crypt->request();

        $nonce_str = $this->createNumber(); // uuid 生成随机不重复字符串
        $order_id = Db::name('order')->where(['OrderId'=>$datas['ordernum']])->value('id') ;

        $data['appid']            = 'wxd70345ac6f6b0273'; //appid
        $data['mch_id']           = '1516479151'; //商户ID
        $data['nonce_str']        = $nonce_str; //随机字符串 这个随便一个字符串算法就可以，我是使用的UUID
        $data['body']             = '同城打车'; // 商品描述
        $data['attach']           = $datas['attach'].",".$order_id;
        $data['out_trade_no']     = $datas['ordernum'];    //商户订单号,不能重复
        $data['total_fee']        = $datas['money'] * 100; //金额
        $data['spbill_create_ip'] = $_SERVER['SERVER_ADDR'];   //ip地址
        $data['notify_url']       = 'https://php.51jjcx.com/user/Wxnewpay/notifyurl';
        $data['trade_type']       = 'APP';      //支付方式

        //将参与签名的数据保存到数组  注意：以上几个参数是追加到$data中的，$data中应该同时包含开发文档中要求必填的剔除sign以外的所有数据
        $data['sign'] = $this->getSign($data);        //获取签名
        $xml = $this->ToXml($data);            //数组转xml
        //curl 传递给微信方
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $data = $this->curl($url,$xml,[]); // 请求微信生成预支付订单
        //返回结果
        if($data){
            //返回成功,将xml数据转换为数组.
            $re = $this->FromXml($data);
            if($re['return_code'] != 'SUCCESS'){
                $msg = isset($re['return_msg'])?$re['return_msg']:'签名失败';
                return ['status'=>false,'msg'=>$msg];
            }
            else{
                //接收微信返回的数据,传给APP!
                $arr =array(
                    'prepayid'  =>$re['prepay_id'], // 用返回的数据
                    'appid'     => 'wxd70345ac6f6b0273',
                    'partnerid' => '1516479151', // 商户ID
                    'package'   => 'Sign=WXPay',
                    'noncestr'  => $nonce_str,
                    'timestamp' =>time(),
                );
                //第二次生成签名
                $sign = $this->getSign($arr);
                $arr['sign'] = $sign;
                // return ['status'=>true,'msg'=>'生成预支付订单成功','data'=>json_encode($arr)];
                return ['code'=>'200','message'=>'成功','data'=>$arr];
            }
        } else {
            return ['status'=>'400','msg'=>'签名数据为空'];
        }
    }

    //充值
    public function wxpayiospost(RSACrypt $crypt){

        $datas = $crypt->request();

        $nonce_str = $this->createNumber(); // uuid 生成随机不重复字符串
        //获取订单id
        $order_id = Db::name('order')->where(['OrderId'=>$datas['ordernum']])->value('id') ;

        $data['appid']            = 'wxd70345ac6f6b0273'; //appid
        $data['mch_id']           = '1516479151'; //商户ID
        $data['nonce_str']        = $nonce_str; //随机字符串 这个随便一个字符串算法就可以，我是使用的UUID
        $data['body']             = '同城打车'; // 商品描述
        $data['attach']           = $datas['attach'].",".$order_id;
        $data['out_trade_no']     = $datas['ordernum'];    //商户订单号,不能重复
        $data['total_fee']        = $datas['money'] * 100; //金额
        $data['spbill_create_ip'] = $_SERVER['SERVER_ADDR'];   //ip地址
        $data['notify_url']       = 'https://php.51jjcx.com/user/Wxnewpay/notifyurl';
        $data['trade_type']       = 'APP';      //支付方式

        //将参与签名的数据保存到数组  注意：以上几个参数是追加到$data中的，$data中应该同时包含开发文档中要求必填的剔除sign以外的所有数据
        $data['sign'] = $this->getSign($data);        //获取签名
        $xml = $this->ToXml($data);            //数组转xml
        //curl 传递给微信方
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $data = $this->curl($url,$xml,[]); // 请求微信生成预支付订单
        //返回结果
        if($data){
            //返回成功,将xml数据转换为数组.
            $re = $this->FromXml($data);
            if($re['return_code'] != 'SUCCESS'){
                $msg = isset($re['return_msg'])?$re['return_msg']:'签名失败';
                return ['status'=>false,'msg'=>$msg];
            }
            else{
                //接收微信返回的数据,传给APP!
                $arr =array(
                    'prepayid'  =>$re['prepay_id'], // 用返回的数据
                    'appid'     => 'wxd70345ac6f6b0273',
                    'partnerid' => '1516479151', // 商户ID
                    'package'   => 'Sign=WXPay',
                    'noncestr'  => $nonce_str,
                    'timestamp' =>time(),
                );
                //第二次生成签名
                $sign = $this->getSign($arr);
                $arr['sign'] = $sign;
                // return ['status'=>true,'msg'=>'生成预支付订单成功','data'=>json_encode($arr)];
                return ['code'=>'200','message'=>'成功','data'=>$arr];
            }
        } else {
            return ['status'=>'400','msg'=>'签名数据为空'];
        }
    }

    //企业充值
    public function wxpayenterprise(RSACrypt $crypt){

        $datas = $crypt->request();

        $nonce_str = $this->createNumber(); // uuid 生成随机不重复字符串

        $data['appid']            = 'wxd70345ac6f6b0273'; //appid
        $data['mch_id']           = '1516479151'; //商户ID
        $data['nonce_str']        = $nonce_str; //随机字符串 这个随便一个字符串算法就可以，我是使用的UUID
        $data['body']             = '同城打车'; // 商品描述
        $data['attach']           = $datas['attach'];
        $data['out_trade_no']     = $datas['ordernum'];    //商户订单号,不能重复
        $data['total_fee']        = $datas['money'] * 100; //金额
        $data['spbill_create_ip'] = $_SERVER['SERVER_ADDR'];   //ip地址
        $data['notify_url']       = 'https://php.51jjcx.com/user/Wxnewpay/notifyurl';
        $data['trade_type']       = 'APP';      //支付方式

        //将参与签名的数据保存到数组  注意：以上几个参数是追加到$data中的，$data中应该同时包含开发文档中要求必填的剔除sign以外的所有数据
        $data['sign'] = $this->getSign($data);        //获取签名
        $xml = $this->ToXml($data);            //数组转xml
        //curl 传递给微信方
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $data = $this->curl($url,$xml,[]); // 请求微信生成预支付订单
        //返回结果
        if($data){
            //返回成功,将xml数据转换为数组.
            $re = $this->FromXml($data);
            if($re['return_code'] != 'SUCCESS'){
                $msg = isset($re['return_msg'])?$re['return_msg']:'签名失败';
                return ['status'=>false,'msg'=>$msg];
            }
            else{
                //接收微信返回的数据,传给APP!
                $arr =array(
                    'prepayid'  =>$re['prepay_id'], // 用返回的数据
                    'appid'     => 'wxd70345ac6f6b0273',
                    'partnerid' => '1516479151', // 商户ID
                    'package'   => 'Sign=WXPay',
                    'noncestr'  => $nonce_str,
                    'timestamp' =>time(),
                );
                //第二次生成签名
                $sign = $this->getSign($arr);
                $arr['sign'] = $sign;
                // return ['status'=>true,'msg'=>'生成预支付订单成功','data'=>json_encode($arr)];
                return ['code'=>'200','message'=>'成功','data'=>$arr];
            }
        } else {
            return ['status'=>'400','msg'=>'签名数据为空'];
        }
    }

    //支付
    public function payment($ini){
        $datas = $ini;
        $nonce_str = $this->createNumber(); // uuid 生成随机不重复字符串

        $data['appid']            = 'wxd70345ac6f6b0273'; //appid
        $data['mch_id']           = '1516479151'; //商户ID
        $data['nonce_str']        = $nonce_str; //随机字符串 这个随便一个字符串算法就可以，我是使用的UUID
        $data['body']             = '同城打车'; // 商品描述
        $data['attach']           = $datas['attach'];
        $data['out_trade_no']     = $datas['ordernum'];    //商户订单号,不能重复
        $data['total_fee']        = $datas['money'] * 100; //金额
        $data['spbill_create_ip'] = $_SERVER['SERVER_ADDR'];   //ip地址
        $data['notify_url']       = 'https://php.51jjcx.com/user/WxnewPay/notifyurl';
        $data['trade_type']       = 'APP';      //支付方式
        if($datas['classification'] == '出租车'){
            $data['profit_sharing']  = 'Y';        //是否需要分账(出租车)
        }

        //将参与签名的数据保存到数组  注意：以上几个参数是追加到$data中的，$data中应该同时包含开发文档中要求必填的剔除sign以外的所有数据
        $data['sign'] = $this->getSign($data);        //获取签名
        $xml = $this->ToXml($data);            //数组转xml
        //curl 传递给微信方
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $data = $this->curl($url,$xml,[]); // 请求微信生成预支付订单
        //返回结果
        if($data){
            //返回成功,将xml数据转换为数组.
            $re = $this->FromXml($data);
            if($re['return_code'] != 'SUCCESS'){
                $msg = isset($re['return_msg'])?$re['return_msg']:'签名失败';
                return ['status'=>false,'msg'=>$msg];
            }
            else{
                //接收微信返回的数据,传给APP!
                $arr =array(
                    'prepayid'  =>$re['prepay_id'], // 用返回的数据
                    'appid'     => 'wxd70345ac6f6b0273',
                    'partnerid' => '1516479151', // 商户ID
                    'package'   => 'Sign=WXPay',
                    'noncestr'  => $nonce_str,
                    'timestamp' =>time(),
                );
                //第二次生成签名
                $sign = $this->getSign($arr);
                $arr['sign'] = $sign;
//                halt($arr);
                // return ['status'=>true,'msg'=>'生成预支付订单成功','data'=>json_encode($arr)];
//                return ['code'=>'200','message'=>'成功','data'=>$arr];

                return $arr;
            }
        } else {
            return ['status'=>'400','msg'=>'签名数据为空'];
        }
    }

    //微信支付回调地址
    public function notifyurl(){

        include_once  "extend/WeChat/WxPayApi.php" ;
        include_once  "extend/WeChat/WxPayConfig.php" ;
        include_once  "extend/WeChat/WxPayData.php" ;
        include_once  "extend/WeChat/WxPayNotify.php" ;
        include_once  "extend/WeChat/log.php" ;

        // 初始化日志
        $logHandler = new \CLogFileHandler("../logs/" . date('Y-m-d') . '.log');
        $log = \Log::Init($logHandler,15);
        $log->DEBUG("支付回调开始！");
        $Notify = new \WxPayNotify();

        $Notify->Handle(true);

        $log->DEBUG("支付回调结束！");

    }

    /**
     * 微信退款
     * @param $transaction_id
     * @param $total_fee
     * @param $refund_fee
     * @return \成功时返回，其他抛异常
     */
    public function wetuiorder($ordernum)
    {
        $orderinfo = Db::name('goodsorder')->where('ordernum',$ordernum)->find();
        $refund_amount = $orderinfo['totalprice'];
        include_once  "../extend/WeChat/WxPayApi.php" ;
        include_once  "../extend/WeChat/WxPayConfig.php" ;
        include_once  "../extend/WeChat/WxPayData.php" ;
        include_once  "../extend/WeChat/WxPayNotify.php" ;
        include_once  "../extend/WeChat/log.php" ;
        //初始化日志
        $logHandler = new \CLogFileHandler("./logs/" . date('Y-m-d') . '.log');
        $log = \Log::Init($logHandler,15);
        $log->DEBUG("微信退款！");

        $input = new \WxPayRefund();
        $log->DEBUG("开始退款了！");
        $input->SetOut_trade_no($ordernum);

        $input->SetTotal_fee($refund_amount*100);

        $input->SetRefund_fee($refund_amount*100);

        //$input->SetTotal_fee(5);

        //$input->SetRefund_fee(5);

        $input->SetOut_refund_no('1522837421' . date("YmdHis"));

        $input->SetOp_user_id('1522837421');//操作员帐号, 默认为商户号
        $log->DEBUG("微信退款到底了！");
        return \WxPayApi::refund($input);
    }

    /**

     * 生成随机字符串

     * @return string

     */

    protected function createNumber()
    {
        //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
        $order_id_main = date('YmdHis') . rand(10000000, 99999999);
        //订单号码主体长度
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for ($i = 0; $i < $order_id_len; $i++) {
            $order_id_sum += (int)(substr($order_id_main, $i, 1));
        }
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        $order_id = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
        return $order_id;
    }

    function file_get_contents_post($url, $post){

        $options = array(CURLOPT_RETURNTRANSFER =>true,CURLOPT_HEADER =>false,CURLOPT_POST =>true,CURLOPT_POSTFIELDS => $post,);

        $ch = curl_init($url);

        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;

    }

    /**

     * xml 数据格式转换

     * @param $arr

     * @return string

     */

    public  function arrayToXml($arr)

    {

        $xml = "<xml>";

        foreach ($arr as $key=>$val)

        {

            if (is_numeric($val)){

                $xml.="<".$key.">".$val."</".$key.">";

            }else{

                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";

            }

        }

        $xml.="</xml>";

        return $xml;

    }


}