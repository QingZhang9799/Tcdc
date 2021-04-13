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

class Applycancel extends Base
{
    public function index(){
        $params = [
            "channel" => input('?channel') ? input('channel') : null,
            "timestamp" => input('?timestamp') ? input('timestamp') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "requestId" => input('?requestId') ? input('requestId') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["channel"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
//        $file = fopen('./log.txt', 'a+');
//        fwrite($file, "------------------申请作废进来了---------------------"  . "\r\n");
        //将发票改成作废的状态
        $invoice = Db::name('invoice')->where(['code' => input('requestId') ])->find() ;
//        fwrite($file, "------------------invoice---------------------"  .json_encode($invoice). "\r\n");
        $ini['id'] = $invoice['id'] ;
        $ini['status'] = 6 ;

        Db::name('invoice')->update($ini) ;
        //在开出一张作废发票
        $amount = Db::name('invoice')->where(['id' => $invoice['id'] ])->value('amount') ;

        //电子发票
        $param = [] ;
        $items = [] ;               //商品

        $type = "" ;
        if($invoice['buyerType'] == 1){
            $type = "个人" ;
        }else if($invoice['buyerType'] == 2){
            $type = "企业" ;
        }
        $invoice_id = $invoice['id'] ;
        $param['appKey'] = "4Xaja10riTeS2X1E" ;
        $param['appSecret'] = "0ra41can52o3i71u" ;
//            $param['accessToken'] = "" ;
        $param['taxNumber'] = "91230184MA18W4FFXA" ;
        $param['outOrderNos'] = array($invoice_id);//array($invoice['receiveOrders']);
        $param['reason'] = "" ;
        $param['items'] = array(array("name" => "客运服务费", "model" => "无", "unit" => "次","number"=>"1","price"=>$amount / 100,"no"=>3010101020203,"taxRate"=>0.01));

        $param['callbackUrl'] = "https://php.51jjcx.com/partner/Invoicereddashed" ;
//            $param['taxPlate'] = "" ;
        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Pragma: no-cache"
        );
//        fwrite($file, "------------------param:---------------------" . json_encode($param) . "\r\n");
        $datas =  $this->request_post("https://fapiao-api.easyapi.com/invoice/nullify",json_encode($param),$headers);
//        fwrite($file, "------------------发票:---------------------" . json_encode($datas) . "\r\n");

        return [
            'result' => 0,
            'message' => '申请作废成功',
        ];
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