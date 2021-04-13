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

class Queryinvoice extends Base
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
//        fwrite($file, "-------------------查询发票--------------------------------" . "\r\n");
//        fwrite($file, "------------------requestId:---------------------------" .input('requestId'). "\r\n");
        //查询发票状态
        $invoice = Db::name('invoice')->where(['code' =>input('requestId') ])->find() ;
        $data = array();
        if($invoice['status'] == 4){
            array_push($data,[
                'invoiceId'=>$invoice['id'],
                'status'=>$invoice['status'],
            ] );
        }else if($invoice['status'] == 2){
            array_push($data, [
                'invoiceId'=>$invoice['id'],
                'invoiceCode'=>$invoice['invoiceCode'],
                'invoiceNumber'=>$invoice['invoiceNumber'],
                'status'=>$invoice['status'],
                'originInvoiceCode'=>$invoice['originInvoiceCode'],
                'originInvoiceNumber'=>$invoice['originInvoiceNumber'],
            ] );
        }else if($invoice['status'] == 6){
            array_push($data,[
                'invoiceId'=>$invoice['id'],
                'invoiceCode'=>$invoice['invoiceCode'],
                'invoiceNumber'=>$invoice['invoiceNumber'],
                'status'=>$invoice['status'],
            ]);
        }else if($invoice['status'] == 1){
            array_push($data,[
                'invoiceId'=>$invoice['id'],
                'invoiceCode'=>$invoice['invoiceCode'],
                'invoiceNumber'=>$invoice['invoiceNumber'],
                'status'=>$invoice['status'],

                'invoiceFile'=>$invoice['invoiceFile']
            ]);
        }
//        fwrite($file, "------------------data:---------------------------" .json_encode($data). "\r\n");

        return [
            'result' => 0,
            'message' => 'SUCCESS',
            'data'=>$data
        ];
    }
}