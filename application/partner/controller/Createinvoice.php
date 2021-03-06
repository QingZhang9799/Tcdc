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
                "msg" => "???????????????????????????????????????"
            ];
        }
        $code = "FP". date('YmdHis') . rand(0000, 999);
        $data = [] ;

         //????????????????????????????????????
        if( input('invoiceType') == 1 ){        //??????
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
        }else if(input('invoiceType') == 2){    //??????
            $params['create_time'] = time();
            //????????????????????????
            $receiveOrders = explode(',' , input('receiveOrders') ) ;
            $user_phone = Db::name('order')->where(['id'=>$receiveOrders[0]])->value('user_phone') ;

            unset($params['channel']);
            unset($params['timestamp']);
            unset($params['sign']);

            $params['code'] = $code ;
            $params['status'] = 4 ;
            $params['phone'] = $user_phone ;
            $invoice_id = Db::name('invoice')->insertGetId($params);
            //????????????
            $param = [] ;
            $items = [] ;               //??????

//            $items['name'] = "*????????????*???????????????" ;   //????????????",??????????????????????????????75?????????
//            $items['model'] = "???" ;                     //"??????",??????
//            $items['unit'] = "???" ;                      //"???",??????
//            $items['number'] = "1" ;                    //???????????????????????????
//            $items['price'] = input('amount') / 100 ;   //???????????? ??????


            $type = "" ;
            if(input('buyerType') == 1){
                $type = "??????" ;
            }else if(input('buyerType') == 2){
                $type = "??????" ;
            }


            $param['appKey'] = "4Xaja10riTeS2X1E" ;
            $param['appSecret'] = "0ra41can52o3i71u" ;
//            $param['accessToken'] = "" ;
            $param['taxNumber'] = "91230184MA18W4FFXA" ;
            $param['outOrderNo'] = $invoice_id ;
            $param['category'] = "?????????????????????" ;
            $param['property'] = "??????" ;
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
            $param['items'] = array(array("name" => "???????????????", "model" => "???", "unit" => "???","number"=>"1","price"=>input('amount') / 100,"no"=>3010101020203,"taxRate"=>0.01));

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
//            fwrite($file, "------------------??????:---------------------" . json_encode($datas) . "\r\n");
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
        $ch = curl_init(); // ?????????curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); // ??????????????????
        curl_setopt($ch, CURLOPT_HEADER, 0); // ??????header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // ?????????????????????????????????????????????
        curl_setopt($ch, CURLOPT_POST, 1); // post????????????
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // ?????? HTTP Header?????????????????????
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        // ??????????????????????????????
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch); // ??????curl

        curl_close($ch);
        return $data;
    }

}