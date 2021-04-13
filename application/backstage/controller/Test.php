<?php
namespace app\backstage\controller;
use think\Controller;
use think\Loader ;

//支付控制器
class Test extends Controller
{
    public function index(){
        return $this->fetch();
    }

    //电脑支付宝接口
    public function index2(){
        //付款金额
        $total_amount = 1;//input('WIDtotal_amount');
        if($total_amount){
            $params = [
                //商户订单号，商户网站订单系统中唯一订单号，必填
                'out_trade_no'  =>  "QG11211",//input('WIDout_trade_no'),
                //订单名称，必填
                'trade_name'       =>  "企业充值",//input('WIDsubject'),
                //付款金额，必填
                'total_amount'  =>  $total_amount,
                //描述
                't_body'        =>  "",//input('WIDbody'),
            ];
//            include_once 'extend/Alipay/pagepay/pagepay.php';
            Loader::import('alipay.pagepay.pagepay',EXTEND_PATH);
//            import('alipay.pagepay.Pagepay',EXTEND_PATH);

//             \pagepay::pay($params);
        }
    }

    //异步通知地址
    public function notify_url(){
        /* *
         *************************页面功能说明*************************
         * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
         * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
         * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
         */
        import('alipay.pagepay.service.AlipayTradeService',EXTEND_PATH);

        //$arr = $this->request->param();使用这个无法接受数据
        $arr = $_POST;
        $alipaySevice = new \AlipayTradeService(config('alipay'));
        $alipaySevice->writeLog(var_export($arr, true));
        $result = $alipaySevice->check($arr);

        /* 实际验证过程建议商户添加以下校验。
        1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
        4、验证app_id是否为该商户本身。
        */
        if ($result) {//验证成功
            //商户订单号
            $out_trade_no = $arr['out_trade_no'];
            //支付宝交易号
            $trade_no = $arr['trade_no'];
            //交易状态
            $trade_status = $arr['trade_status'];

            //TRADE_FINISHED：交易完成，  TRADE_SUCCESS：支付成功
            if ($arr['trade_status'] == 'TRADE_FINISHED' || $arr['trade_status'] == 'TRADE_SUCCESS') {
                //做逻辑处理，例如：保存数据
                file_put_contents("../extend/sites.txt",implode("，",$arr).PHP_EOL,FILE_APPEND | LOCK_EX);
            }
            echo "success";
        } else {
            //验证失败
            echo "fail";
        }
    }

    //同步跳转，支付成功,由客户的浏览器触发的一个通知
    public function return_url(){
        /* *
         * 功能：支付宝页面跳转同步通知页面
         * 版本：2.0
         * 修改日期：2017-05-01
         * 说明：
         * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。

         *************************页面功能说明*************************
         * 该页面可在本机电脑测试
         * 可放入HTML等美化页面的代码、商户业务逻辑程序代码
         */
        import('alipay.pagepay.service.AlipayTradeService',EXTEND_PATH);

        //$arr = $this->request->param();
        $arr = $_GET;
        $alipaySevice = new \AlipayTradeService(config('alipay'));
        $result = $alipaySevice->check($arr);

        /* 实际验证过程建议商户添加以下校验。
        1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
        4、验证app_id是否为该商户本身。
        */
        if ($result) {//验证成功
            //做逻辑处理，例如：保存数据
            echo "<pre>";
            print_r($arr);
            //file_put_contents("../extend/sites.txt",implode("，",$arr).PHP_EOL,FILE_APPEND | LOCK_EX);
        } else {
            //验证失败
            echo "验证失败";
        }
    }
}