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

class Invoicecallback extends Base
{
    public function index()
    {
       //回调信息
        $data = input('') ;
//        $file = fopen('./log.txt', 'a+');
//        fwrite($file, "------------------回调信息:---------------------" . json_encode($data) . "\r\n");
        //判断一下，是开票还是冲红的

        if($data['response']['color'] == "blue"){                   //发票
            $outOrderNo = $data['response']['outOrderNo'] ;
            $color = $data['response']['color'] ;
            $code = $data['response']['code'] ;         //发票代码
            $number = $data['response']['number'] ;     //发票号码
            //更新发票状态
            $ini['id'] = $outOrderNo;
            $ini['invoiceCode'] = $code ;       //发票代码
            $ini['invoiceNumber'] =$number ;    //发票号码
            $ini['status'] = 1 ;                //已开票

            //文件转base64位
            ob_clean();
            $url = $data['response']['electronicInvoiceUrl'] ;//"https://upload.fapiaoer.cn/upload/fp_file/201907180000011132156341424300015d2fceeb399ec.pdf";
            $imgData = file_get_contents($url);//拿到远程图片
            $base64 = chunk_split(base64_encode($imgData));//转64文件流

            $ini['invoiceFile'] = $base64 ;
            Db::name('invoice')->update($ini) ;
        }else if($data['response']['color'] == "red"){          //冲红
            $outOrderNo = $data['response']['outOrderNo'] ;
            $code = $data['response']['code'] ;         //发票代码
            $number = $data['response']['number'] ;     //发票号码
            //先获取原来的发票代码和号码
            $invoice = Db::name('invoice')->where(['id'=>$outOrderNo])->find() ;
            $inii['id'] =  $outOrderNo;
            $inii['originInvoiceCode'] = $invoice['invoiceCode'] ;
            $inii['originInvoiceNumber'] = $invoice['invoiceNumber'] ;
            $inii['invoiceCode'] = $code ;
            $inii['invoiceNumber'] = $number ;
            $inii['status'] = 2 ;                //已作废

            Db::name('invoice')->update($inii) ;
        }
    }
}