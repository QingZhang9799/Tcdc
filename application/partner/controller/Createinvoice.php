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

class Createinvoice extends Base
{
    public function index()
    {
        $params = [
            "channel" => input('?channel') ? input('channel') : null,
            "timestamp" => input('?timestamp') ? input('timestamp') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "title" => input('?title') ? input('title') : null,
            "invoiceItem" => input('?invoiceItem') ? input('invoiceItem') : null,
            "buyerType" => input('?buyerType') ? input('buyerType') : null,
            "taxNumber" => input('?taxNumber') ? input('taxNumber') : null,
            "receiveOrders" => input('?receiveOrders') ? input('receiveOrders') : null,
            "invoiceType" => input('?invoiceType') ? input('invoiceType') : null,
            "amount" => input('?amount') ? input('amount') : null,
            "receiveName" => input('?receiveName') ? input('receiveName') : null,
            "receiveMobile" => input('?receiveMobile') ? input('receiveMobile') : null,
            "receiveEmail" => input('?receiveEmail') ? input('receiveEmail') : null,
            "postProvince" => input('?postProvince') ? input('postProvince') : null,
            "postCity" => input('?postCity') ? input('postCity') : null,
            "postDistrict" => input('?postDistrict') ? input('postDistrict') : null,
            "postRegion" => input('?postRegion') ? input('postRegion') : null,
            "postStreet" => input('?postStreet') ? input('postStreet') : null,
            "registerAddress" => input('?registerAddress') ? input('registerAddress') : null,
            "registerPhone" => input('?registerPhone') ? input('registerPhone') : null,
            "openBank" => input('?openBank') ? input('openBank') : null,
            "bankAccount" => input('?bankAccount') ? input('bankAccount') : null,
            "remark" => input('?remark') ? input('remark') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["channel"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $code = "FP". date('YmdHis') . rand(0000, 999);
        $data = [] ;

         //将纸质发票，存到数据库里
        if( input('invoiceType') == 1 ){        //纸质
            $params['create_time'] = time();

            unset($params['channel']);
            unset($params['timestamp']);
            unset($params['sign']);

            $params['code'] = $code ;

            $invoice_id = Db::name('invoice')->insertGetId($params);

            $code = Db::name('invoice')->where(['id' => $invoice_id ])->value('code');

            $data['requestId'] = $code ;

            return [
                'result' => 0,
                'message' => 'SUCCESS',
                'data' => $data
            ];
        }else if(input('invoiceType') == 2){    //电子
            $params['create_time'] = time();
            //订单里抓取用户号
            $receiveOrders = explode(',' , input('receiveOrders') ) ;
            $user_phone = Db::name('order')->where(['id'=>$receiveOrders[0]])->value('user_phone') ;

            unset($params['channel']);
            unset($params['timestamp']);
            unset($params['sign']);

            $params['code'] = $code ;
            $params['status'] = 4 ;
            $params['phone'] = $user_phone ;
            $invoice_id = Db::name('invoice')->insertGetId($params);
            //电子发票
            $param = [] ;
            $items = [] ;               //商品

//            $items['name'] = "*运输服务*客运服务费" ;   //测试商品",商品名称必须不能超过75个字符
//            $items['model'] = "无" ;                     //"红色",型号
//            $items['unit'] = "次" ;                      //"个",单位
//            $items['number'] = "1" ;                    //数量，可以传小数位
//            $items['price'] = input('amount') / 100 ;   //含税单价 必须


            $type = "" ;
            if(input('buyerType') == 1){
                $type = "个人" ;
            }else if(input('buyerType') == 2){
                $type = "企业" ;
            }


            $param['appKey'] = "4Xaja10riTeS2X1E" ;
            $param['appSecret'] = "0ra41can52o3i71u" ;
//            $param['accessToken'] = "" ;
            $param['taxNumber'] = "91230184MA18W4FFXA" ;
            $param['outOrderNo'] = $invoice_id ;
            $param['category'] = "增值税普通发票" ;
            $param['property'] = "电子" ;
            $param['type'] = $type ;
            $param['purchaserName'] = input('title') ;
            $param['purchaserTaxpayerNumber'] = input('taxNumber') ;
//            $param['purchaserAddress'] = input('registerAddress') ;
//            $param['purchaserPhone'] = "" ;
//            $param['purchaserBank'] = strval(input('openBank')) ;
//            $param['purchaserBankAccount'] =strval(input('bankAccount'))  ;


            $param['mobile'] = $user_phone;     // input('registerPhone') ;
            $param['email'] = "" ;
            $param['username'] =$user_phone;    // input('registerPhone') ;
            $param['remark'] = '' ;//input('remark') ;
            $param['items'] = array(array("name" => "客运服务费", "model" => "无", "unit" => "次","number"=>"1","price"=>input('amount') / 100,"no"=>3010101020203,"taxRate"=>0.01));

            $param['callbackUrl'] = "https://php.51jjcx.com/partner/Invoicecallback" ;
//            $param['taxPlate'] = "" ;
            $headers = array(
                "Content-type: application/json;charset='utf-8'",
                "Accept: application/json",
                "Cache-Control: no-cache",
                "Pragma: no-cache"
            );
//            $file = fopen('./log.txt', 'a+');
//            fwrite($file, "------------------param:---------------------" . json_encode($param) . "\r\n");
            $datas =  $this->request_post("https://fapiao-api.easyapi.com/invoice/make",json_encode($param),$headers);
//            fwrite($file, "------------------发票:---------------------" . json_encode($datas) . "\r\n");
            $data = [] ;
            $data['requestId'] = $code;
            return [
                'result' => 0,
                'message' => 'SUCCESS',
                'data' => $data,
            ];
        }
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