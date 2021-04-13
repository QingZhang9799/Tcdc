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

class Invoicereddashed extends Base
{
    public function index()
    {
        //回调信息
        $data = input('') ;
//        $file = fopen('./log.txt', 'a+');
//        fwrite($file, "------------------回调信息:---------------------" . json_encode($data) . "\r\n");

        $outOrderNo = $data['response']['outOrderNo'] ;
        $color = $data['response']['color'] ;
        $code = $data['response']['code'] ;         //作废发票代码
        $number = $data['response']['number'] ;     //作废发票号码

        $invoice = Db::name('invoice')->where(['id' =>$outOrderNo ])->find();

        //更新发票状态
        $ini['id'] = $outOrderNo;
        $ini['invoiceCode'] = $code ;       //已开票或者已作废对应的发票代码
        $ini['invoiceNumber'] =$number ;    //已开票或者已作废对应的发票号码
        $ini['originInvoiceCode'] =  $invoice['invoiceCode'] ;       //作废发票对应的原始发票代码
        $ini['originInvoiceNumber'] = $invoice['invoiceNumber'] ;    //作废发票对应的原始发票号码
        $ini['status'] = 2 ;                //已作废

        Db::name('invoice')->update($ini) ;
    }
}