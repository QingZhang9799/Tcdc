<?php

namespace app\user\controller;

use think\Controller;
use think\Db;

class Fzhang extends Controller
{
    private $sep_url;                           // 单次分账请求URL
    private $mch_id;                            // 商户号
    private $appid;                             // 公众号appid
    private $mch_secrect;                       // 此处是商户key！！！

    function __construct()
    {
        $this->sep_url = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharing';
        $this->mch_id = '1516479151';//config('wechat.pay_config.mch_id');
        $this->appid = 'wx23d17a2d492c5d42' ;//config('wechat.pay_config.app_id');
        $this->mch_secrect = '2279ea9fcb9b92c0fb9018a2bf3bff6a'; //config('wechat.pay_config.key');
    }

    /**
     * Notes: 请求单次分账
     * @param $transaction_id   微信支付交易单号
     * @param $out_order_no     商户系统内部的分账单号，在商户系统内部唯一（单次分账、多次分账、完结分账应使用不同的商户分账单号），同一分账单号多次请求等同一次。只能是数字、大小写字母_-|*@
     */
    function requestsingleaccountsplitting($transaction_id, $out_order_no,$profitSharingAccounts)
    {
        $file = fopen('./lease.txt', 'a+');
        fwrite($file, "-------------------分账进来了--------------------"."\r\n");
//        $receivers = $this->receivers($out_order_no);
//        fwrite($file, "-------------------receivers:--------------------".json_encode($receivers)."\r\n") ;
//        if ($receivers['code'] == 0) return ['code' => '分账失败！'];
        $receivers = array();
//        foreach ($profitSharingAccounts as $profitSharingAccount)
//        {
        $tmp = array(
            'type'=>$profitSharingAccounts['type'],
            'account'=>"om4FRwbGK0eQQgltVLFUQQxU-ito",
            'amount'=>(intval($profitSharingAccounts['amount']) - intval($profitSharingAccounts['amount'])* 0.01)*100 ,
            'description'=>$profitSharingAccounts['desc'],
        );
        $receivers[] = $tmp;
//        }
        $receivers = json_encode($receivers,JSON_UNESCAPED_UNICODE);
        $tmp_splitting_data = [
            'appid' => $this->appid,
            'mch_id' => $this->mch_id,
            'nonce_str' => $this->get_nonce_str(),
            'sign_type' => 'HMAC-SHA256',
            'transaction_id' => $transaction_id,
            'out_order_no' => $out_order_no,
            'receivers' => $receivers
        ];

        fwrite($file, "-------------------tmp_splitting_data:--------------------".json_encode($tmp_splitting_data)."\r\n") ;
        $tmp_splitting_data['sign'] = $this->make_sign($tmp_splitting_data, $this->mch_secrect);
        $xml = $this->array_to_xml($tmp_splitting_data);
        fwrite($file, "-------------------xml:--------------------".json_encode($xml)."\r\n") ;
        $do_arr = $this->curl_post_ssl($this->sep_url, $xml);
        fwrite($file, "-------------------do_arr:--------------------".json_encode($do_arr)."\r\n") ;
        $result = $this->xml_to_array($do_arr);
        fwrite($file, "-------------------result:--------------------".json_encode($result)."\r\n") ;
        return $result;
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
     * Notes: 获取分账详细列表信息
     * User: googol
     * @param $out_order_no     商户内部的分账单号
     */
//    private function receivers($out_order_no)
//    {
//        $out_order = Db::name('order')
//            ->where(['OrderId' => $out_order_no])
//            ->field('money')
//            ->find();
//        if (!empty($out_order)) {
//            $receivers_arr = [];
//            $receivers_arr['type'] = 'PERSONAL_OPENID';
//            $receivers_arr['account'] = "o2ULYvuhOrGly0_IRx5ZlkWeTgZ8";
//            $receivers_arr['amount'] = $out_order['money'];
//            $receivers_arr['description'] = 'payment';
//            return ['code' => 1, 'res' => json_encode($receivers_arr)];
//        }
//        return ['code' => 0];
//    }

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
        $xml = '<?xml version="1.0" encoding="UTF-8"?><xml>';
        foreach ($arr as $key => $val) {
            $xml.="<".$key.">$val</".$key.">";
        }
        $xml.="</xml>";
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
    function curl_post_ssl($url, $vars, $second = 30, $aHeader = array())
    {
        $isdir = "/var/www/php.tcdc.dzsev.cn/extend/WeChat/cert/";//证书位置

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);//设置执行最长秒数
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');//证书类型
        curl_setopt($ch, CURLOPT_SSLCERT, $isdir . 'apiclient_cert.pem');//证书位置
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');//CURLOPT_SSLKEY中规定的私钥的加密类型
        curl_setopt($ch, CURLOPT_SSLKEY, $isdir . 'apiclient_key.pem');//证书位置
//        curl_setopt($ch, CURLOPT_CAINFO, 'PEM');
//        curl_setopt($ch, CURLOPT_CAINFO, $isdir . 'rootca.pem');
        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);//设置头部
        }
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);//全部数据使用HTTP协议中的"POST"操作来发送

        $data = curl_exec($ch);//执行回话
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }

}