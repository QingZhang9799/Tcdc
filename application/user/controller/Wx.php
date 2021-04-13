<?php
/**微信分账 */
namespace app\user\controller;

use think\Controller;
use think\Debug;
use think\Db;

require_once  "ProfitSharing.php";
require_once  "ProfitSharingCurl.php";

//返回参数参考地址：https://pay.weixin.qq.com/wiki/doc/api/allocation_sl.php?chapter=25_1&index=1
class Wx extends Controller
{
    private $wxConfig = null;
    private $sign = null;
    private $curl = null;

    public function __construct()
    {
        $this->wxConfig = $this->wxConfig();
        $this->sign = new \ProfitSharing();
        $this->curl = new \ProfitSharingCurl();
    }


    /**
     * @function 发起请求所必须的配置参数
     * @return mixed
     */
    private function wxConfig()
    {

        $wxConfig['app_id'] = 'wx23d17a2d492c5d42';          //服务商公众号AppID
        $wxConfig['mch_id'] = '1516479151';                   //服务商商户号
        $wxConfig['sub_app_id'] = 'wx23d17a2d492c5d42';     //子服务商公众号AppID
        $wxConfig['sub_mch_id'] = '1337863101';              //子服务商商户号
        $wxConfig['md5_key'] = '2279ea9fcb9b92c0fb9018a2bf3bff6a'; //md5 秘钥
        $wxConfig['app_cert_pem'] = '/var/www/php.tcdc.dzsev.cn/extend/WeChat/cert/apiclient_cert.pem';//证书路径
        $wxConfig['app_key_pem'] = '/var/www/php.tcdc.dzsev.cn/extend/WeChat/cert/apiclient_key.pem';//证书路径
        return $wxConfig;
    }


    /**
     * @function 请求多次分账接口
     * @param $orders array 待分账订单
     * @param $accounts array 分账接收方
     * @return array
     * @throws Exception
     */
    public function multiProfitSharing($orders,$accounts)
    {
        if(empty($orders)){
            throw new \Exception('没有待分帐订单');
        }
        if(empty($accounts)){
            throw new \Exception('接收分账账户为空');
        }

        //1.设置分账账号
        $receivers = array();
        foreach ($accounts as $account)
        {
            $tmp = array(
                'type'=>$account['type'],
                'account'=>$account['account'],
                'amount'=>intval($account['amount']),
                'description'=>$account['desc'],
            );
            $receivers[] = $tmp;
        }
        $receivers = json_encode($receivers,JSON_UNESCAPED_UNICODE);

        $totalCount = count($orders);
        $successCount = 0;
        $failCount = 0;
        $now = time();
        foreach ($orders as $order)
        {
            //2.生成签名
            $postArr = array(
                'appid'=>$this->wxConfig['app_id'],
                'mch_id'=>$this->wxConfig['mch_id'],
                'sub_mch_id'=>$this->wxConfig['sub_mch_id'],
                'sub_appid'=>$this->wxConfig['sub_app_id'],
                'nonce_str'=>md5(time() . rand(1000, 9999)),
                'transaction_id'=>$order['trans_id'],
                'out_order_no'=>$order['order_no'].$order['ticket_no'],
                'receivers'=>$receivers,
            );

            $sign = $this->sign->getSign($postArr, 'HMAC-SHA256',$this->wxConfig['md5_key']);
            $postArr['sign'] = $sign;


            //3.发送请求
            $url = 'https://api.mch.weixin.qq.com/secapi/pay/multiprofitsharing';
            $postXML = $this->toXml($postArr);
//            Ilog::DEBUG("multiProfitSharing.postXML: " . $postXML);

            $opts = array(
                CURLOPT_HEADER    => 0,
                CURLOPT_SSL_VERIFYHOST    => false,
                CURLOPT_SSLCERTTYPE   => 'PEM', //默认支持的证书的类型，可以注释
                CURLOPT_SSLCERT   => $this->wxConfig['app_cert_pem'],
                CURLOPT_SSLKEY    => $this->wxConfig['app_key_pem'],
            );
//            Ilog::DEBUG("multiProfitSharing.opts: " . json_encode($opts));

            $curl_res = $this->curl->setOption($opts)->post($url,$postXML);
//            Ilog::DEBUG("multiProfitSharing.curl_res: " . $curl_res);

            $ret = $this->toArray($curl_res);
            if($ret['return_code']=='SUCCESS' and $ret['result_code']=='SUCCESS')
            {
                //更新分账订单状态
                $params = array();
                $params['order_no'] =  $order['order_no'];
                $params['trans_id'] =  $order['trans_id'];
                $params['ticket_no'] =  $order['ticket_no'];

                $data = array();
                $data['profitsharing'] = $receivers;
                $data['state'] = 2;
//                pdo_update('ticket_orders_profitsharing',$data,$params);//根据自己的业务更改
//                $this->PdoUpdateTicket('ticket_orders_profitsharing',$data,$params);
                $successCount++;

            }else{
                $failCount++;
            }
            usleep(500000);//微信会报频率过高，所以停一下
        }
        return array('processTime'=>date('Y-m-d H:i:s',$now),'totalCount'=>$totalCount,'successCount'=>$successCount,'failCount'=>$failCount);
    }

    /**
     * @function 请求单次分账接口
     * @param $profitSharingOrders array 待分账订单
     * @param $profitSharingAccounts array 分账接收方
     * @return array
     * @throws Exception
     */
    public function profitSharing($profitSharingOrders,$profitSharingAccounts)
    {
        $this->printLog("分账进来了","");
        if(empty($profitSharingOrders)){
            throw new \Exception('没有待分帐订单');
        }
        if(empty($profitSharingAccounts)){
            throw new \Exception('接收分账账户为空');
        }
        $this->printLog("分账profitSharingAccounts:",json_encode($profitSharingAccounts));
        //1.设置分账账号
        $receivers = array();
//        foreach ($profitSharingAccounts as $profitSharingAccount)
//        {
            $tmp = array(
                'type'=>$profitSharingAccounts['type'],
                'account'=>"om4FRwbGK0eQQgltVLFUQQxU-ito",
                'amount'=>intval($profitSharingAccounts['amount']),
                'description'=>$profitSharingAccounts['desc'],
            );
            $receivers[] = $tmp;
//        }
        $receivers = json_encode($receivers,JSON_UNESCAPED_UNICODE);
        $this->printLog("分账receivers",json_encode($receivers));

        $totalCount = count($profitSharingOrders);
        $successCount = 0;
        $failCount = 0;
        $now = time();
        $this->printLog("分账profitSharingOrders:",json_encode($profitSharingOrders));
//        foreach ($profitSharingOrders as $profitSharingOrder)
//        {
            $this->printLog("分账trans_id : ",$profitSharingOrders['trans_id']);
            $this->printLog("分账ticket_no : ",$profitSharingOrders['ticket_no']);
            //2.生成签名
            $postArr = array(
                'appid'=>$this->wxConfig['app_id'],
                'mch_id'=>$this->wxConfig['mch_id'],
                'sub_mch_id'=>$this->wxConfig['sub_mch_id'],
                'sub_appid'=>$this->wxConfig['sub_app_id'],
                'nonce_str'=>md5(time() . rand(1000, 9999)),
                'transaction_id'=>$profitSharingOrders['trans_id'],//修改成自己参数【微信支付订单号】
                'out_order_no'=>$profitSharingOrders['ticket_no'],//商户分账单号  //$profitSharingOrder['order_no'].

                'receivers'=>$receivers,
            );
            $this->printLog("分账postArr",json_encode($postArr));

            $sign = $this->sign->getSign($postArr, 'HMAC-SHA256',$this->wxConfig['md5_key']);
            $postArr['sign'] = $sign;

            //3.发送请求
            $url = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharing';

            $postXML = $this->toXml($postArr);
//            Ilog::DEBUG("profitSharing.postXML: " . $postXML);
            $this->printLog("分账postXML:",json_encode($postXML));
            $opts = array(
                CURLOPT_HEADER    => 0,
                CURLOPT_SSL_VERIFYHOST    => false,
                CURLOPT_SSLCERTTYPE   => 'PEM', //默认支持的证书的类型，可以注释
                CURLOPT_SSLCERT   => $this->wxConfig['app_cert_pem'],
                CURLOPT_SSLKEY    => $this->wxConfig['app_key_pem'],
            );
//            Ilog::DEBUG("profitSharing.opts: " . json_encode($opts));

            $curl_res = $this->curl->setOption($opts)->post($url,$postXML);
//            Ilog::DEBUG("profitSharing.curl_res: " . $curl_res);

            $ret = $this->toArray($curl_res);
            $this->printLog("分账ret : ",json_encode($ret));

            if($ret['return_code']=='SUCCESS' and $ret['result_code']=='SUCCESS')
            {
                //更新分账订单状态
                $params = array();
                $params['order_no'] =  $profitSharingOrders['order_no'];
                $params['trans_id'] =  $profitSharingOrders['trans_id'];
                $params['ticket_no'] =  $profitSharingOrders['ticket_no'];

                $data = array();
//                $data['profitsharing'] = $receivers;
//                $data['state'] = 2;
//                pdo_update('ticket_orders_profitsharing',$data,$params);//需要更改自己的业务
                $this->PdoUpdateTicket('ticket_orders_profitsharing',$data,$params);
                $successCount++;
            }else{
                $failCount++;
            }

//        }
        return array('processTime'=>date('Y-m-d H:i:s',$now),'totalCount'=>$totalCount,'successCount'=>$successCount,'failCount'=>$failCount);
    }
    private function PdoUpdateTicket($describe,$data,$params){
        $file = fopen('./log.txt', 'a+');
        //改变订单分账的状态
        $order_no = $params['order_no'] ;
        $order =  Db::name('order')->where(['OrderId' => $order_no ])->find() ;

        $ini['id'] = $order['id'] ;
        $ini['is_routing'] = 1 ;
        fwrite($file, "-------------------分账---------------------" . "\r\n");
        Db::name('order')->update($ini) ;
    }
    private function printLog($title, $value)
    {
        $file = fopen('./lease.txt', 'a+');
        fwrite($file, "-------------------$title--------------------" . $value . "\r\n");
        fclose($file);
    }

    /**
     * @function 查询分账结果
     * @param $trans_id string 微信支付单号
     * @param $out_order_no string 分账单号
     * @return array|false
     * @throws Exception
     */
    public function query($trans_id,$out_order_no)
    {
        //1.生成签名
        $postArr = array(
            'mch_id'=>$this->wxConfig['mch_id'],
            'sub_mch_id'=>$this->wxConfig['sub_mch_id'],
            'transaction_id'=>$trans_id,
            'out_order_no'=>$out_order_no,
            'nonce_str'=>md5(time() . rand(1000, 9999)),
        );

        $sign = $this->sign->getSign($postArr, 'HMAC-SHA256',$this->wxConfig['md5_key']);
        $postArr['sign'] = $sign;

        //2.发送请求
        $url = 'https://api.mch.weixin.qq.com/pay/profitsharingquery';
        $postXML = $this->toXml($postArr);
//        Ilog::DEBUG("query.postXML: " . $postXML);

        $curl_res = $this->curl->post($url,$postXML);
//        Ilog::DEBUG("query.curl_res: " . $curl_res);

        $ret = $this->toArray($curl_res);
        return $ret;
    }


    /**
     * @function 添加分账接收方
     * @param $profitSharingAccount array 分账接收方
     * @return array|false
     * @throws Exception
     */
    public function addReceiver()
    {
        //1.接收分账账户
        $receiver = array(
            'type'=>'MERCHANT_ID',
            'account'=>'1337863101',
            'name'=>'同城打车',
            'relation_type'=>'SUPPLIER',
        );
        $receiver = json_encode($receiver,JSON_UNESCAPED_UNICODE);

        //2.生成签名
        $postArr = array(
            'appid'=>$this->wxConfig['app_id'],
            'mch_id'=>$this->wxConfig['mch_id'],
            'sub_mch_id'=>$this->wxConfig['mch_id'],
            'nonce_str'=>md5(time() . rand(1000, 9999)),
            'receiver'=>$receiver
        );

        $sign = $this->sign->getSign($postArr, 'HMAC-SHA256',$this->wxConfig['md5_key']);
        $postArr['sign'] = $sign;


        //3.发送请求
        $url = 'https://api.mch.weixin.qq.com/pay/profitsharingaddreceiver';
        $postXML = $this->toXml($postArr);
//        Ilog::DEBUG("addReceiver.postXML: " . $postXML);

        $curl_res = $this->curl->post($url,$postXML);
//        Ilog::DEBUG("addReceiver.curl_res: " . $curl_res);

        $ret = $this->toArray($curl_res);
        return $ret;
    }


    /**
     * @function 删除分账接收方
     * @param $profitSharingAccount array 分账接收方
     * @return array|false
     * @throws Exception
     */
    public function removeReceiver($profitSharingAccount)
    {
        //1.接收分账账户
        $receiver = array(
            'type'=>$profitSharingAccount['type'],
            'account'=>$profitSharingAccount['account'],
            'name'=>$profitSharingAccount['name'],
        );
        $receiver = json_encode($receiver,JSON_UNESCAPED_UNICODE);

        //2.生成签名
        $postArr = array(
            'appid'=>$this->wxConfig['app_id'],
            'mch_id'=>$this->wxConfig['mch_id'],
            'sub_mch_id'=>$this->wxConfig['sub_mch_id'],
            'sub_appid'=>$this->wxConfig['sub_app_id'],
            'nonce_str'=>md5(time() . rand(1000, 9999)),
            'receiver'=>$receiver
        );

        $sign = $this->sign->getSign($postArr, 'HMAC-SHA256',$this->wxConfig['md5_key']);
        $postArr['sign'] = $sign;


        //3.发送请求
        $url = 'https://api.mch.weixin.qq.com/pay/profitsharingremovereceiver';
        $postXML = $this->toXml($postArr);
//        Ilog::DEBUG("removeReceiver.postXML: " . $postXML);

        $curl_res = $this->curl->post($url,$postXML);
//        Ilog::DEBUG("removeReceiver.curl_res: " . $curl_res);

        $ret = $this->toArray($curl_res);
        return $ret;
    }


    /**
     * @function 完结分账
     * @param $profitOrder array 分账订单
     * @param $description string 完结分账描述
     * @return array|false
     * @throws Exception
     */
    public function finish($profitOrder,$description='分账完结')
    {
        $ret = array();
        if(!empty($profitOrder))
        {
            //1.签名
            $postArr = array(
                'mch_id'=>$this->wxConfig['mch_id'],
                'sub_mch_id'=>$this->wxConfig['sub_mch_id'],
                'appid'=>$this->wxConfig['app_id'],
                'nonce_str'=>md5(time() . rand(1000, 9999)),
                'transaction_id'=>$profitOrder['trans_id'],
                'out_order_no'=>'finish'.'_'.$profitOrder['order_no'],
                'description'=>$description,
            );

            $sign = $this->sign->getSign($postArr, 'HMAC-SHA256',$this->wxConfig['md5_key']);
            $postArr['sign'] = $sign;

            //2.请求
            $url = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharingfinish';
            $postXML = $this->toXml($postArr);

//            Ilog::DEBUG("finish.postXML: " . $postXML);

            $opts = array(
                CURLOPT_HEADER    => 0,
                CURLOPT_SSL_VERIFYHOST    => false,
                CURLOPT_SSLCERTTYPE   => 'PEM', //默认支持的证书的类型，可以注释
                CURLOPT_SSLCERT   => $this->wxConfig['app_cert_pem'],
                CURLOPT_SSLKEY    => $this->wxConfig['app_key_pem'],
            );
//            Ilog::DEBUG("finish.opts: " . json_encode($opts));

            $curl_res = $this->curl->setOption($opts)->post($url,$postXML);
//            Ilog::DEBUG("finish.curl_res: " . $curl_res);

            $ret = $this->toArray($curl_res);
        }

        return $ret;
    }


    /**
     * @function 分账回退
     * @param $profitOrder array 分账订单
     * @return array
     * @throws Exception
     */
    public function profitSharingReturn($profitOrder)
    {
        $ret = array();
        if(!empty($profitOrder) and $profitOrder['channel']==1)
        {
            $accounts = json_decode($profitOrder['profitsharing'],true);
            foreach ($accounts as $account)
            {
                //1.签名
                $postArr = array(
                    'appid'=>$this->wxConfig['app_id'],
                    'mch_id'=>$this->wxConfig['mch_id'],
                    'sub_mch_id'=>$this->wxConfig['sub_mch_id'],
                    'sub_appid'=>$this->wxConfig['sub_app_id'],
                    'nonce_str'=>md5(time() . rand(1000, 9999)),
                    'out_order_no'=>$profitOrder['order_no'].$profitOrder['ticket_no'],
                    'out_return_no'=>'return_'.$profitOrder['order_no'].$profitOrder['ticket_no'].'_'.$account['account'],
                    'return_account_type'=>'MERCHANT_ID',
                    'return_account'=>$account['account'],
                    'return_amount'=>$account['amount'],
                    'description'=>'用户退款',
                    'sign_type'=>'HMAC-SHA256',
                );

                $sign = $this->sign->getSign($postArr, 'HMAC-SHA256',$this->wxConfig['md5_key']);
                $postArr['sign'] = $sign;


                //2.请求
                $url = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharingreturn';
                $postXML = $this->toXml($postArr);
//                Ilog::DEBUG("profitSharingReturn.postXML: " . $postXML);

                $opts = array(
                    CURLOPT_HEADER    => 0,
                    CURLOPT_SSL_VERIFYHOST    => false,
                    CURLOPT_SSLCERTTYPE   => 'PEM', //默认支持的证书的类型，可以注释
                    CURLOPT_SSLCERT   => $this->wxConfig['app_cert_pem'],
                    CURLOPT_SSLKEY    => $this->wxConfig['app_key_pem'],
                );
//                Ilog::DEBUG("profitSharingReturn.opts: " . json_encode($opts));

                $curl_res = $this->curl->setOption($opts)->post($url,$postXML);
//                Ilog::DEBUG("profitSharingReturn.curl_res: " . $curl_res);

                $ret[] = $this->toArray($curl_res);
            }

        }
        return $ret;
    }


    /**
     * @function 回退结果查询
     * @param $order_no string 本地订单号
     * @param $ticket_no string 本地票号
     * @return array|false
     * @throws \Exception
     */
    public function returnQuery($order_no,$ticket_no)
    {
        $ret = array();

//        $profitOrder = pdo_fetch("SELECT * FROM zc_ticket_orders_profitsharing WHERE order_no='{$order_no}' AND ticket_no='{$ticket_no}'");
        $profitOrder = $this->RetchTicketOrders() ;
        //$profitOrder 返回的结果根据自己的业务进行查询 查询有没有本地订单号，票号
        if($profitOrder['channel']==1 and $profitOrder['state']==2)
        {
            $accounts = json_decode($profitOrder['profitsharing'],true);
            foreach ($accounts as $account)
            {
                //1.签名
                $postArr = array(
                    'appid'=>$this->wxConfig['app_id'],
                    'mch_id'=>$this->wxConfig['mch_id'],
                    'sub_mch_id'=>$this->wxConfig['sub_mch_id'],
                    'nonce_str'=>md5(time() . rand(1000, 9999)),
                    'out_order_no'=>$profitOrder['order_no'].$profitOrder['ticket_no'],
                    'out_return_no'=>'return_'.$profitOrder['order_no'].$profitOrder['ticket_no'].'_'.$account['account'],
                    'sign_type'=>'HMAC-SHA256',
                );

                $sign = $this->sign->getSign($postArr, 'HMAC-SHA256',$this->wxConfig['md5_key']);
                $postArr['sign'] = $sign;

                //2.请求
                $url = 'https://api.mch.weixin.qq.com/pay/profitsharingreturnquery';
                $postXML = $this->toXml($postArr);
//                Ilog::DEBUG("returnQuery.postXML: " . $postXML);

                $curl_res = $this->curl->post($url,$postXML);
//                Ilog::DEBUG("returnQuery.curl_res: " . $curl_res);

                $ret[] = $this->toArray($curl_res);
            }

        }
        return $ret;
    }

    private function RetchTicketOrders(){

    }


    /**
     * @function 将array转为xml
     * @param array $values
     * @return string|bool
     * @author xiewg
     **/
    public function toXml($values)
    {
        if (!is_array($values) || count($values) <= 0) {
            return false;
        }

        $xml = "<xml>";
        foreach ($values as $key => $val) {
            if (is_numeric($val)) {
                $xml.="<".$key.">".$val."</".$key.">";
            } else {
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * @function 将xml转为array
     * @param string $xml
     * @return array|false
     * @author xiewg
     */
    public function toArray($xml)
    {
        if (!$xml) {
            return false;
        }

        // 检查xml是否合法
        $xml_parser = xml_parser_create();
        if (!xml_parse($xml_parser, $xml, true)) {
            xml_parser_free($xml_parser);
            return false;
        }

        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);

        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        return $data;
    }
}