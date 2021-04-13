<?php
/** 支付宝转账 */
namespace app\user\controller;

use think\Controller;

class Alifenzhang extends Controller
{
    //返回参数 参考网址：https://opendocs.alipay.com/apis/api_28/alipay.fund.trans.toaccount.transfer/
    public function index()
    {
        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = 'your app_id';
        $aop->rsaPrivateKey = '请填写开发者私钥去头去尾去回车，一行字符串';
        $aop->alipayrsaPublicKey='请填写支付宝公钥，一行字符串';
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='GBK';
        $aop->format='json';
        $request = new \AlipayFundTransToaccountTransferRequest ();
        $request->setBizContent("{" .
            "\"out_biz_no\":\"3142321423432\"," .
            "\"payee_type\":\"ALIPAY_LOGONID\"," .
            "\"payee_account\":\"abc@sina.com\"," .
            "\"amount\":\"12.23\"," .
            "\"payer_show_name\":\"上海交通卡退款\"," .
            "\"payee_real_name\":\"张三\"," .
            "\"remark\":\"转账备注\"" .
            "  }");
        $result = $aop->execute ( $request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            echo "成功";
        } else {
            echo "失败";
        }
    }
}