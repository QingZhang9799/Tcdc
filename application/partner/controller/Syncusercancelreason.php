<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/7
 * Time: 13:29
 */

namespace app\partner\controller;
use app\backstage\controller\Gps;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Request;

class Syncusercancelreason extends Base
{
    public function index()
    {
        $params = [
            "channel" => input('?channel') ? input('channel') : null,
            "timestamp" => input('?timestamp') ? input('timestamp') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "mtOrderId" => input('?mtOrderId') ? input('mtOrderId') : null,
            "partnerOrderId" => input('?partnerOrderId') ? input('partnerOrderId') : null,
            "cancelCode" => input('?cancelCode') ? input('cancelCode') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["channel"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $data = [];
        $orders = Db::name('order')->where(['id' =>(int)input('partnerOrderId') ])->find();

        //将订单变成取消
        $ini['id'] = $orders['id'] ;
        $ini['cancelCode'] = input('cancelCode') ;
        $order_o = Db::name('order')->update($ini);
//        $file = fopen('./log.txt', 'a+');
//        fwrite($file, "-------------------Syncusercancelreason--------------------" . "\r\n");
        if($order_o > 0){
            return [
                'result' => 0,
                'message' => 'SUCCESS',
            ];
        }else{
            return [
                'result' => 0,
                'message' => 'SUCCESS',
            ];
        }
    }

    function appointment($title, $uid, $message,$type)
    {
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
        $header = array(
            "Authorization:Basic $base64",
            "Content-Type:application/json"
        );
        $param=array("platform"=>"all","audience"=>array("tag"=>array("D_$uid")),"message"=>array("msg_content"=>$message.",".$type,"title"=>$title));
        $params=json_encode($param);
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