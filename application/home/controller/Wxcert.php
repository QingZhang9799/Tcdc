<?php

namespace app\home\controller;

use think\Controller;

class Wxcert extends Controller
{

    /**
     * 获取平台证书内容
     */
    public function get_Certificates()
    {
        $merchant_id = 1516479151;//商户号
        $serial_no = "7DB0A35AF645E0BB0E8E21673A55CBFC5105F9C6";//API证书序列号
        $sign = $this->get_Sign("https://api.mch.weixin.qq.com/v3/certificates", "GET", "", $this->get_Privatekey(), $merchant_id, $serial_no);//$http_method要大写

        $header[] = 'User-Agent:https://zh.wikipedia.org/wiki/User_agent';
        $header[] = 'Accept:application/json';
        $header[] = 'Authorization:WECHATPAY2-SHA256-RSA2048 '.$sign;

        $back = $this->http_Request("https://api.mch.weixin.qq.com/v3/certificates", $header);
        halt($back) ;
        dump(json_decode($back, true));
    }

    /**
     * 获取sign
     * @param $url
     * @param $http_method [POST GET 必读大写]
     * @param $body [请求报文主体（必须进行json编码）]
     * @param $mch_private_key [商户私钥]
     * @param $merchant_id [商户号]
     * @param $serial_no [证书编号]
     * @return string
     */
    private function get_Sign($url, $http_method, $body, $mch_private_key, $merchant_id, $serial_no)
    {
        $timestamp = time();//时间戳
        $nonce = $timestamp . rand(10000, 99999);//随机字符串
        $url_parts = parse_url($url);
        $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
        $message =
            $http_method . "\n" .
            $canonical_url . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $body . "\n";
        openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');
        $sign = base64_encode($raw_sign);
        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $merchant_id, $nonce, $timestamp, $serial_no, $sign);
        return $token;
    }

    /**
     * 获取商户私钥
     * @return false|resource
     */
    public function get_Privatekey()
    {
        $private_key_file = (dirname(__FILE__) . '/key/private_key.pem');//私钥文件路径 如linux服务器秘钥地址地址：/www/wwwroot/test/key/private_key.pem"
        $mch_private_key = openssl_get_privatekey(file_get_contents($private_key_file));//获取私钥
        return $mch_private_key;
    }

    /**
     * 数据请求
     * @param $url
     * @param array $header 获取头部
     * @param string $post_data POST数据，不填写默认以GET方式请求
     * @return bool|string
     */
    public function http_Request($url, $header = array(), $post_data = "")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 2);

        if ($post_data != "") {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); //设置post提交数据
        }
        //判断当前是不是有post数据的发

        $output = curl_exec($ch);
        halt($output) ;
        if ($output === FALSE) {
            $output = "curl 错误信息: " . curl_error($ch);
        }
        curl_close($ch);
        return $output;

    }
}
?>