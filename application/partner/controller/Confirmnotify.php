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

class Confirmnotify extends Base
{
    //通知司机接单
    public function index()
    {
        $params = [
            "channel" => input('?channel') ? input('channel') : null,
            "timestamp" => input('?timestamp') ? input('timestamp') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "mtOrderId" => input('?mtOrderId') ? input('mtOrderId') : null,
            "partnerOrderId" => input('?partnerOrderId') ? input('partnerOrderId') : null,
        ];
        $file = fopen('./notification.txt', 'a+');
        fwrite($file, "-------------------通知司机:--------------------" . "\r\n");

        $order = Db::name('order')->field('id,business_type_id,user_id,partnerCarTypeId,mtorderid,user_phone,budget_conducteur_id')->where(['id' => input('partnerOrderId')])->find();
        $conducteur = Db::name('conducteur')->where(['id'=>$order['budget_conducteur_id']])->find();

        $inii['conducteur_id'] = $order['budget_conducteur_id'];
        $inii['conducteur_name'] = $conducteur['DriverName'];
        $inii['conducteur_phone'] = $conducteur['DriverPhone'];
        $inii['gps_number'] = $conducteur['Gps_number'];
        //司机gps
        $inii['key'] = $conducteur['key'];
        $inii['service'] = $conducteur['service'];
        $inii['terimnal'] = $conducteur['terimnal'];
        $inii['trace'] = $conducteur['trace'];
        $inii['async_block'] = input('block_id') ;
        $inii['status'] = 2;
        $inii['mt_status'] = 30;
        Db::name('order')->update($inii);
        //调用激光
        $this->appointment("美团单来了", $order['budget_conducteur_id'], (int)input('partnerOrderId'), 10);

        return [
            'result' => 0,
            'message' => 'SUCCESS',
        ];
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