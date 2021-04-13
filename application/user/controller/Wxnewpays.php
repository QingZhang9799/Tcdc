<?php

namespace app\user\controller;

use think\Controller;
use mrmiao\encryption\RSACrypt;
use think\Db;
use think\Request;

class Wxnewpays extends Controller
{
    public function getSign($params) {
        ksort($params);        //将参数数组按照参数名ASCII码从小到大排序
        foreach ($params as $key => $item) {
            if (!empty($item)) {         //剔除参数值为空的参数
                $newArr[] = $key.'='.$item;     // 整合新的参数数组
            }
        }
        $stringA = implode("&", $newArr);         //使用 & 符号连接参数
//        $stringSignTemp = $stringA."&key=".'f56f580f9ba6e39ce2dc54e1835a22eb';        //拼接key
        $stringSignTemp = $stringA."&key=".'f56f580f9ba6e39ce2dc54e1835a22eb';        //拼接key
        // key是在商户平台API安全里自己设置的
        $stringSignTemp = MD5($stringSignTemp);       //将字符串进行MD5加密
        $sign = strtoupper($stringSignTemp);      //将所有字符转换为大写
        return $sign;
    }

    public function ToXml($data=array())
    {
        if(!is_array($data) || count($data) <= 0)
        {
            return '数组异常';
        }

        $xml = "<xml>";
        foreach ($data as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }


    /*
         * Effect    微信App支付XML转ARR
         * author    YangYunHao
         * email     1126420614@qq.com
         * time      2019-02-14 11:12:31
         * parameter request:请求参数
         * return    data:请求数据
         * */
    public function FromXml($xml)
    {
        if(!$xml){
            echo "xml数据异常！";
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }

    /*
     * Effect    curl 跨域请求
     * author    YangYunHao
     * email     1126420614@qq.com
     * time
     * parameter url: 请求地址,request: 请求参数,hearer:请求头,method:请求方式
     * Guardian
     * Guardian-email
     * Guardian-time
     * */
    public function curl($url = '',$request = [],$header = [],$method = 'POST'){
        $header[] = 'Accept-Encoding: gzip, deflate';//gzip解压内容
        $ch = curl_init();   //1.初始化
        curl_setopt($ch, CURLOPT_URL, $url); //2.请求地址
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);//3.请求方式
        //4.参数如下
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//https
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');//模拟浏览器
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');

        if ($method == "POST") {//5.post方式的时候添加数据
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);//6.执行

        if (curl_errno($ch)) {//7.如果出错
            return curl_error($ch);
        }
        curl_close($ch);//8.关闭
        return $tmpInfo;
    }
    public function payment($ini){
        $datas = $ini;
        $nonce_str = $this->createNumber(); // uuid 生成随机不重复字符串

        $data['appid']            = 'wx11bf63050c37fd9d'; //appid
        $data['mch_id']           = '1337863101'; //商户ID
        $data['nonce_str']        = $nonce_str; //随机字符串 这个随便一个字符串算法就可以，我是使用的UUID
        $data['body']             = '同城打车'; // 商品描述
        $data['attach']           = $datas['attach'];
        $data['out_trade_no']     = $datas['ordernum'];    //商户订单号,不能重复
        $data['total_fee']        = $datas['money'] * 100; //金额
        $data['spbill_create_ip'] = $_SERVER['SERVER_ADDR'];   //ip地址
        $data['notify_url']       = 'https://php.51jjcx.com/user/WxnewPays/notifyurl';
        $data['trade_type']       = 'APP';      //支付方式

        //将参与签名的数据保存到数组  注意：以上几个参数是追加到$data中的，$data中应该同时包含开发文档中要求必填的剔除sign以外的所有数据
        $data['sign'] = $this->getSign($data);        //获取签名
        $xml = $this->ToXml($data);            //数组转xml
        //curl 传递给微信方
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $data = $this->curl($url,$xml,[]); // 请求微信生成预支付订单
        //返回结果
        if($data){
            //返回成功,将xml数据转换为数组.
            $re = $this->FromXml($data);
            if($re['return_code'] != 'SUCCESS'){
                $msg = isset($re['return_msg'])?$re['return_msg']:'签名失败';
                return ['status'=>false,'msg'=>$msg];
            }
            else{
                //接收微信返回的数据,传给APP!
                $arr =array(
                    'prepayid'  =>$re['prepay_id'], // 用返回的数据
                    'appid'     => 'wx11bf63050c37fd9d',
                    'partnerid' => '1337863101', // 商户ID
                    'package'   => 'Sign=WXPay',
                    'noncestr'  => $nonce_str,
                    'timestamp' =>time(),
                );
                //第二次生成签名
                $sign = $this->getSign($arr);
                $arr['sign'] = $sign;
//                halt($arr);
                // return ['status'=>true,'msg'=>'生成预支付订单成功','data'=>json_encode($arr)];
//                return ['code'=>'200','message'=>'成功','data'=>$arr];

                return $arr;
            }
        } else {
            return ['status'=>'400','msg'=>'签名数据为空'];
        }
    }

    //微信支付回调地址
    public function notifyurl(){
        $file = fopen('./log.txt', 'a+');
        fwrite($file, "-----------------------". "\r\n");
        include_once  "extend/traffic_WeChat/WxPayApi.php" ;
        include_once  "extend/traffic_WeChat/WxPayConfig.php" ;
        include_once  "extend/traffic_WeChat/WxPayData.php" ;
        include_once  "extend/traffic_WeChat/WxPayNotify.php" ;
        include_once  "extend/traffic_WeChat/log.php" ;

        // 初始化日志
        $logHandler = new \CLogFileHandler("../logs/" . date('Y-m-d') . '.log');
        $log = \Log::Init($logHandler,15);
        $log->DEBUG("支付回调开始！");
        $Notify = new \WxPayNotify();

        $Notify->Handle(true);

        $log->DEBUG("支付回调结束！");

    }

    public function Refund()
    {
        $file = fopen('./log.txt', 'a+');
        fwrite($file, "退款进来了--------------------------------------"."\r\n");
        $param = Request::instance()->param();
        //查找是否有订单
        $indent = Db::name('journey_order')->where('id',$param['order_id'])->find();
        if(empty($indent)){
            return ['code'=>APICODE_ERROR];exit;
        }
        //退票按比例退
        $cancel_rules = Db::name('journey')->where(['id' => $indent['journey_id']])->value('cancel_rules');
        $times = Db::name('journey')->where(['id' => $indent['journey_id']])->value('times');
        fwrite($file, "--------------------jorder_passenger_id------------------".input('jorder_passenger_id')."\r\n");
        $prices = Db::name('jorder_passenger')->where('id','in',input('jorder_passenger_id'))->sum('price');

        $cancel = json_decode($cancel_rules);

        $proportion = $this->check($cancel,$times) ;
        fwrite($file, "--------------------prices------------------".$prices."\r\n");
        $price = $prices - ($prices * ($proportion/100)) ;
        //退款单号
        $tk_code  = "TK" . date('YmdHis') . rand(0000, 999);

        include_once  "extend/traffic_WeChat/WxPayApi.php" ;
        include_once  "extend/traffic_WeChat/WxPayConfig.php" ;
        include_once  "extend/traffic_WeChat/WxPayData.php" ;
        include_once  "extend/traffic_WeChat/WxPayNotify.php" ;
        include_once  "extend/traffic_WeChat/log.php" ;
        $input = new \WxPayRefund();
        $input->SetTransaction_id($indent['transaction_id']);//微信订单号
        $input->SetOut_refund_no($tk_code);//退款单号
        $input->SetTotal_fee($indent['price']*100);//订单金额
        $input->SetRefund_fee($price*100);//退款金额
        $input->SetOp_user_id('1337863101');//商户号
        $refund = new  \WxPayApi();//\WxPayApi();
        $result = $refund->refund($input);
        fwrite($file, "--------------------------------------".json_encode($result)."\r\n");
        if(($result['return_code']=='SUCCESS') && ($result['result_code']=='SUCCESS')){
            fwrite($file, "------------------成功了--------------------".json_encode($result)."\r\n");
            //成功之后，更改订单状态
            $jorder_passenger = explode(',' , input('jorder_passenger_id') ) ;
            foreach ($jorder_passenger as $key=>$value){
                $ini['id'] = $value;
                $ini['is_accepted'] = 5;
                Db::name('jorder_passenger')->update($ini);
            }
            //判断一下，子乘车人，还有没有了
            $jorder_passengers = Db::name('jorder_passenger')->where(['journey_order_id'=>$param['order_id']])->where('is_accepted','neq',"5")->select();
            if(empty($jorder_passengers)){          //全部为退票之后，子单变为取消
                $inii['id'] = $param['order_id'];
                $inii['status'] = 7;
                Db::name('journey_order')->update($inii);
            }
            return ['code'=>APICODE_SUCCESS,'msg'=>'退款成功'];
        }else if(($result['return_code']=='FAIL') || ($result['result_code']=='FAIL')){
            //原因
            $reason = (empty($result['err_code_des'])?$result['return_msg']:$result['err_code_des']);
            return ['code'=>APICODE_ERROR,'msg'=>$reason];
        }else{
            return ['code'=>APICODE_ERROR,'msg'=>'退款失败'];
        }
    }
    //退票区间
    private function check($cancel,$times){
        $proportion = 0 ;
        $time = time() ;
        foreach ($cancel as $key => $value){
            foreach ($value as $k => $v){
                if($k == 1){                //开车前24小时
                    $calculate =  $times  + 24 * 60 *60 ;
                    if($calculate > $time ){
                        $proportion = (int)$v ;
                    }
                }else if($k == 2){         //开车前2小时
                    $calculate =  $times  + 2 * 60 *60 ;
                    if($calculate > $time ){
                        $proportion = (int)$v ;
                    }
                }else if($k == 3){         //开车前1小时
                    $calculate =  $times  + 1 * 60 *60 ;
                    if($calculate > $time ){
                        $proportion = (int)$v ;
                    }
                }else if($k == 4){         //开车前1小时以内
                    $calculate =  $times  + 1 * 60 *60 ;
                    if( ($calculate > $time ) && ($time < $times) ){
                        $proportion = (int)$v ;
                    }
                }else if($k == 5){         //开车后
                    if($times > $time){
                        $proportion = (int)$v ;
                    }
                }
            }
        }
        return $proportion ;
    }

    /**
     * 生成随机字符串
     * @return string
     */

    protected function createNumber()
    {
        //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
        $order_id_main = date('YmdHis') . rand(10000000, 99999999);
        //订单号码主体长度
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for ($i = 0; $i < $order_id_len; $i++) {
            $order_id_sum += (int)(substr($order_id_main, $i, 1));
        }
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        $order_id = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
        return $order_id;
    }

    function file_get_contents_post($url, $post){

        $options = array(CURLOPT_RETURNTRANSFER =>true,CURLOPT_HEADER =>false,CURLOPT_POST =>true,CURLOPT_POSTFIELDS => $post,);

        $ch = curl_init($url);

        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;

    }

    /**
     * xml 数据格式转换
     * @param $arr
     * @return string
     */

    public  function arrayToXml($arr)

    {

        $xml = "<xml>";

        foreach ($arr as $key=>$val)

        {

            if (is_numeric($val)){

                $xml.="<".$key.">".$val."</".$key.">";

            }else{

                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";

            }

        }

        $xml.="</xml>";

        return $xml;

    }


}