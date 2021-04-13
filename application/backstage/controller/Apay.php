<?php
namespace app\backstage\controller;
use think\Controller;
use think\Db;
use think\Request;

class Apay extends Controller
{
    public function index(Request $request){
        header("Content-type:text/html;charset=utf-8");
        $data = $request->param();//接表单传递来的所有值

        $total_amount =  $data['money'] ;
        if($total_amount){
            //引入支付宝支付
            include_once 'extend/Alipay/config.php';
            include_once 'extend/Alipay/pagepay/service/AlipayTradeService.php';
            include_once 'extend/Alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php';

            $order_code = "QY" . '0' . date('YmdHis') . rand(0000, 999);
            //创建一个充值订单
            $ini['ordernum'] = $order_code ;
            $ini['money'] = $total_amount ;
            $ini['enterprise_id'] = $data['enterprise_id'] ;
            $ini['state'] = 0 ;
            $ini['is_payment'] = -1 ;
            $ini['create_time'] = time() ;

            $enterprise_order_id = Db::name('enterprise_order')->insertGetId($ini) ;

            $ordernum = Db::name('enterprise_order')->where(['id' => $enterprise_order_id ])->value('ordernum');

            //商户订单号，商户网站订单系统中唯一订单号，必填
            $out_trade_no = $order_code ;//input('post.out_trade_no');
 
            //订单名称，必填
            $subject = "企业充值" ;//input('post.goods_name');

            //商品描述，可空
            $body = "企业充值" ;//input('post.goods_body');
 
            //构造参数
            $payRequestBuilder = new \AlipayTradePagePayContentBuilder();
            $payRequestBuilder->setBody($body);
            $payRequestBuilder->setSubject($subject);
            $payRequestBuilder->setTotalAmount($total_amount);
            $payRequestBuilder->setOutTradeNo($out_trade_no);
 
            //电脑网站支付请求
            $config['return_url'] = "" ;
            $config['notify_url'] = "http://php.51jjcx.com/user/userpay/alipay" ;

            $aop = new \AlipayTradeService($config);
            $response = $aop->pagePay($payRequestBuilder,$config['return_url'],$config['notify_url']);

        }else{
            $out_trade_no = 'ALPAY'.date('YmdHis'); //订单号
            $goods_name = '在线支付'; //商品名称
            $goods_body = 'test'; //商品描述
 
            $this->assign('out_trade_no',$out_trade_no);
            $this->assign('goods_name',$goods_name);
            $this->assign('goods_body',$goods_body);
            return view();
        }
    }
}
?>