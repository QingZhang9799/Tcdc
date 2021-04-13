<?php

/**

 * Created by PhpStorm.

 * User: Administrator

 * Date: 19-2-26

 * Time: 上午10:53

 */

namespace app\backstage\controller;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;

class Invoice extends Base
{
    //发票列表
    public function InvoiceList(){
        return self::pageReturnStrot(db('invoice'),"",'id desc');
    }

    //发票-发货
    public function InvoiceSendout(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "expressNum" => input('?expressNum') ? input('expressNum') : null,
            "expressName" => input('?expressName') ? input('expressName') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id","expressNum","expressName"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $res = Db::name('invoice')->update($params);

        if($res > 0){
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "更新成功",
            ];
        }else{
            return [
                "code" => APICODE_ERROR,
                "msg" => "更新失败",
            ];
        }
    }

    //发票-开票
    public function MakeOutInvoice(){
        $params = [
            "invoice_id" => input('?invoice_id') ? input('invoice_id') : null,
            "invoiceCode" => input('?invoiceCode') ? input('invoiceCode') : null,
            "invoiceNumber" => input('?invoiceNumber') ? input('invoiceNumber') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["invoice_id","invoiceCode","invoiceNumber"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $ini['id'] = input('invoice_id') ;
        $ini['invoiceCode'] = input('invoiceCode') ;
        $ini['invoiceNumber'] = input('invoiceNumber') ;
        $ini['status'] = 1 ;
        $invoice = Db::name('invoice')->update($ini);

        if($invoice > 0){
            return [
                "code" =>APICODE_SUCCESS,
                "msg" => "开票成功",
            ];
        }else{
            return [
                "code" =>APICODE_ERROR,
                "msg" => "开票失败",
            ];
        }
    }

    //发票-作废
    public function InvoiceCancellation(){
        $params = [
            "invoice_id" => input('?invoice_id') ? input('invoice_id') : null,
            "invoiceCode" => input('?invoiceCode') ? input('invoiceCode') : null,
            "invoiceNumber" => input('?invoiceNumber') ? input('invoiceNumber') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["invoice_id","invoiceCode","invoiceNumber"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //获取作废之前的原始发票代码和发票号码
        $invoice = Db::name('invoice')->where(['id' => input('invoice_id') ])->find();

        $ini['id'] = input('invoice_id') ;
        $ini['originInvoiceCode'] = $invoice['invoiceCode'] ;
        $ini['originInvoiceNumber'] = $invoice['invoiceNumber'] ;
        $ini['invoiceCode'] = input('invoiceCode') ;
        $ini['invoiceNumber'] = input('invoiceNumber') ;
        $ini['status'] = 2 ;
        $invoice = Db::name('invoice')->update($ini) ;

        if($invoice > 0){
            return [
                "code" =>APICODE_SUCCESS,
                "msg" => "作废成功",
            ];
        }else{
            return [
                "code" =>APICODE_ERROR,
                "msg" => "作废失败",
            ];
        }
    }

    //发票-详情
    public function InvoiceDetails(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('invoice')->where($params)->find();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "发票ID不能为空"
            ];
        }
    }
}