<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 20-12-19
 * Time: 下午9:36
 */

namespace app\user\controller;

use think\Controller;

class Routing extends Controller
{


    private $addsep_receiving_url;              // 添加分账接收方请求URL
    private $addsep_receiving_type;             // 分账接收方类型 此处是个人openid类型
    private $addsep_receiving_relation_type;    // 分账关系类型
    private $mch_id;                            // 商户号
    private $appid;                             // 公众号appid
    private $mch_secrect;                       // 此处是商户key！！！

    function __construct()
    {
        $this->addsep_receiving_url = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharing';
        $this->addsep_receiving_type = 'PERSONAL_OPENID';
        $this->addsep_receiving_relation_type = 'PARTNER';
        $this->mch_id = '1516479151' ;//config('wechat.pay_config.mch_id');
        $this->appid = 'wx23d17a2d492c5d42'; //config('wechat.pay_config.app_id');
        $this->mch_secrect ='2279ea9fcb9b92c0fb9018a2bf3bff6a' ; //config('wechat.pay_config.key');
    }

    /**
     * Notes: 添加微信分账接收方
     * Url: 调用该方法 传入openid
     */
    public function index($openid)
    {
        $tmp_receiving_data = [
            'mch_id' => $this->mch_id,
            'appid' => $this->appid,
            'nonce_str' => $this->get_nonce_str(),
            'sign_type' => 'HMAC-SHA256',
            'receiver' => $this->receiver($openid)
        ];

        $tmp_receiving_data['sign'] = $this->make_sign($tmp_receiving_data, $this->mch_secrect);
        $xml = $this->array_to_xml($tmp_receiving_data);
        $do_arr = $this->post_xml_curl($xml, $this->addsep_receiving_url);
        $result = $this->xml_to_array($do_arr);
        return $result;
    }

    /**
     * Notes: 接收方信息
     * @param $openid
     * @return false|string
     */
    private function receiver($openid)
    {
        $tmp_receiver_arr = [
            'type' => 'PERSONAL_OPENID',
            'account' => $openid,
            'relation_type' => $this->addsep_receiving_relation_type,
        ];

        return json_encode($tmp_receiver_arr);
    }

    /**
     * Notes: 获取随机数
     * @param int $length
     * @return string
     */
    private function get_nonce_str($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * Notes: 生成sign
     * @param $arr
     * @param $secret
     * @return string
     */
    private function make_sign($arr, $secret)
    {
        //签名步骤一：按字典序排序参数
        ksort($arr);
        $str = $this->to_url_params($arr);
        //签名步骤二：在str后加入KEY
        $str = $str . "&key=" . $secret;
        //签名步骤三：HMAC-SHA256 类型  加密的字符串 key是商户秘钥
        $str = hash_hmac('sha256', $str, $this->mch_secrect);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($str);
        return $result;
    }

    /**
     * Notes: 数组转字符串
     * @param $arr
     * @return string
     */
    private function to_url_params($arr)
    {
        $str = "";
        foreach ($arr as $k => $v) {
            if (!empty($v) && ($k != 'sign')) {
                $str .= "$k" . "=" . $v . "&";
            }
        }
        $str = rtrim($str, "&");
        return $str;
    }

    /**
     * Notes: 数组转XML
     * @param $arr
     * @return string
     */
    private function array_to_xml($arr){
        $xml = '';
        foreach($arr as $key => $val){

            $xml.="<".$key.">".$val.$key.">";
        }
        $xml.="";
        return $xml;
    }

    /**
     * Notes: XML转数组
     * @param $xml
     * @return mixed
     */
    private function xml_to_array($xml){
        libxml_disable_entity_loader(true);
        $arr= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $arr;
    }

    /**
     * Notes: POST 请求 此处不需要证书
     * @param $xml
     * @param $url
     * @param int $second
     * @return bool|string
     */
    private function post_xml_curl($xml, $url, $second = 30){
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //curl_close($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        }else{
            $error = curl_errno($ch);
            echo "curl出错,错误码:$error"."";
            echo "错误原因查询";
            curl_close($ch);
            return false;
        }
    }
}