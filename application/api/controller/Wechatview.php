<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 19-2-26
 * Time: 上午10:53
 */

namespace app\api\controller;

use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Controller;
use think\Db;
use think\Exception;
use think\view;

class Wechatview extends Controller
{
    //扫码返回
    public function Ewm()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx23d17a2d492c5d42&secret=d43efd31543f69aee8c11977948515b8";
        $access_token = json_decode($this->request_post($url), true)["access_token"];
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$access_token&type=jsapi";
        $data = array();
        $data["jsapi_ticket"] = json_decode($this->request_post($url), true)["ticket"];
        $data["noncestr"] = $this->createNumber();
        $data["timestamp"] = time();
        $data["url"] = 'https://php.51jjcx.com' . $_SERVER["REQUEST_URI"];
        $string = $this->ToUrlParams($data);
        $data["signature"] = sha1($string);
        $data["string"] = $string;
        $data["appid"] = "wx23d17a2d492c5d42";
        $data["param"]='';
        $input = input('');
        if (sizeof($input) > 0) {
            $data["param"] = "?";
            foreach ($input as $key => $value) {
                $data["param"] = $data["param"] . $key . "=" . $value;
                $data["param"] = $data["param"] . "&";
            }
            $data["param"] = substr($data["param"], 0, strlen($data["param"]) - 1);
        }
        $this->assign("data", $data);
        echo $this->fetch();
    }

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

    private function request_post($url = '', $post_data = array())
    {
        if (empty($url)) {
            return false;
        }
        $o = "";
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&";
        }
        $post_data = substr($o, 0, -1);
        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
    }

    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v) {
            if ($k != "sign") {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
}
