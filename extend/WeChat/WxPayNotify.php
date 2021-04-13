<?php

use think\Db;
use app\user\controller\Marketing;
/**
 *
 * 回调基础类
 * @author widyhu
 *
 */
class WxPayNotify extends WxPayNotifyReply
{
    /**
     *
     * 回调入口
     * @param bool $needSign 是否需要签名输出
     */
    final public function Handle($needSign = true)
    {

        $msg = "OK";
        Log::DEBUG($msg);
        //当返回false的时候，表示notify中调用NotifyCallBack回调失败获取签名校验失败，此时直接回复失败
        $result = WxpayApi::notify(array($this, 'NotifyCallBack'), $msg);

        if ($result == false) {
            $this->SetReturn_code("FAIL");
            $this->SetReturn_msg($msg);
            $this->ReplyNotify(false);
            return;
        } else {
            //该分支在成功回调到NotifyCallBack方法，处理完成之后流程
            $this->SetReturn_code("SUCCESS");
            $this->SetReturn_msg("OK");
        }
        $this->ReplyNotify($needSign);
    }

    /**
     *
     * 回调方法入口，子类可重写该方法
     * 注意：
     * 1、微信回调超时时间为2s，建议用户使用异步处理流程，确认成功之后立刻回复微信服务器
     * 2、微信服务器在调用失败或者接到回包为非确认包的时候，会发起重试，需确保你的回调是可以重入
     * @param array $data 回调解释出的参数
     * @param string $msg 如果回调处理失败，可以将错误信息输出到该方法
     * @return true回调出来完成不需要继续回调，false回调处理未完成需要继续回调
     */
    //查询订单
//    public function Queryorder($transaction_id)
//    {
//        $input = new WxPayOrderQuery();
//        $input->SetTransaction_id($transaction_id);
//        $result = WxPayApi::orderQuery($input);
//        Log::DEBUG("query:" . json_encode($result));
//        if (array_key_exists("return_code", $result)
//            && array_key_exists("result_code", $result)
//            && $result["return_code"] == "SUCCESS"
//            && $result["result_code"] == "SUCCESS"
//        ) {
//            return true;
//        }
//        return false;
//    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {
        $datas = [];
        $notfiyOutput = array();
//            Log::DEBUG('caoxue');
        // $body = $data['attach'];
        //商户订单号
        $out_trade_no = $data['out_trade_no'];
        $file = fopen('./log.txt', 'a+');
        fwrite($file, "支付信息：" . json_encode($data) . "\r\n");
        //微信交易号
        //交易总额
         $total_fee = $data['total_fee'];
        // $data = explode(',', $body);
        if ($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
            $attach = explode( ',' , $data['attach'] )  ;
            fwrite($file, "---------------attach------------------".json_decode($attach). "\r\n");
            if ($attach[0] == "1") {
                $orderinfo = Db::name('order')->where( 'id' , $attach[1] )->find();
                $checkinfo = $orderinfo['status'];
                fwrite($file, "---------------进来了ios------------------".$out_trade_no  . "\r\n");
                fwrite($file, "---------------order_id------------------".$orderinfo['id']  . "\r\n");
                // 启动事务
//                Db::startTrans();
//                try {
                // 处理支付日志
                $acount = $data['total_fee'];
                $ini['id'] = $orderinfo['id'];
                $ini['status'] = 6;
                $ini['pay_time'] = time();
                $ini['transaction_id'] = $data['transaction_id'];
                $ini['third_party_money'] = $total_fee/100;                  //第三方支付金额
                $ini['actual_amount_money'] = $total_fee/100;              //实付金额
                $res = Db::name('order')->update($ini);
                fwrite($file, "---------------完事了ios------------------".$res  . "\r\n");
                $this->companyMoney($orderinfo['conducteur_id'], $orderinfo['money'], $orderinfo, $data['discount_money']);             //给司机分钱
                $distribution_id = Db::name('user')->where(['id' => $orderinfo['user_id']])->value('distribution_id');   //获取分销司机id
                if(empty($orderinfo['mtorderid'])){
                    $this->distributionChauffeur($distribution_id, $orderinfo['city_id'], $acount);                                           //给分销司机分钱
                }
                $this->companyBoard($orderinfo);                                                                                          //公司流水
                // 提交事务
//                    Db::commit();
//                } catch (\Exception $e) {
//                    // 回滚事务
//                    Db::rollback();
//                    throw new Exception($e->getMessage());
//                }
            } elseif ($attach[0] == "2") {
                fwrite($file, "-------------------充值---------------------" . "\r\n");
                $orderinfo = Db::name('recharge_order')->where('ordernum', $out_trade_no)->find();

                $insert['id'] = $orderinfo['id'];
                $insert['state'] = 1;
                $insert['create_time'] = time();
                $insert['pay_time'] = time();
                $insert['is_payment'] = 0;
                $insert['transaction_id'] = $data['transaction_id'];

                Db::name('recharge_order')->update($insert);
                Db::name('user')->where('id',$orderinfo['user_id'])->setInc('balance',$orderinfo['money']);

                $user = Db::name('user')->where(['id' => $orderinfo['user_id']])->find();
                $this->BmikeceChange($user, 1, $orderinfo['money'], 1);
//
                $m = new Marketing();
                $city_id = Db::name('user')->where(['id' => $orderinfo['user_id']])->value('city_id');
                $extra["recharge_money"] =sprintf("%.2f", ($data['total_fee'] /100))  ;
                $m->judgeActivity($orderinfo['user_id'], $city_id, 4, $extra);
            }elseif ($attach[0] == "3") {
                fwrite($file, "-------------------顺风车---------------------" . "\r\n");
                $orderinfo = Db::name('order')->where('OrderId', $out_trade_no)->find();
                fwrite($file, '-------------money：---------------' . $out_trade_no . '\r\n');

                //判断一下，主单状态
                $order_id = Db::name('order')->where(['id' => $orderinfo['id'] ])->value('order_id') ;
                $status = Db::name('order')->where(['id' => $order_id ])->value('status')  ;
                if($status == 12 ){
//                            $ini['status'] = 12;
                    $datas['status'] = 12;                                      //去接驾
                }else if($status == 4 ){
//                            $ini['status'] = 3;
                    $datas['status'] = 3;                                      //待出行
                }

                $datas['third_party_type'] = 0;                         //第三方支付方式
                $datas['third_party_money'] = $total_fee/100;                  //第三方支付金额
                $datas['id'] = $orderinfo['id'];
                $datas['pay_time'] = time();
                $datas['actual_amount_money'] = $total_fee/100;              //实付金额
                $datas['transaction_id'] = $data['transaction_id'];
                fwrite($file, '-------------order_id：---------------' . $orderinfo['id'] . '\r\n');
                $res = Db::name('order')->update($datas);
                $user = Db::name('user')->where(['id' => $orderinfo['user_id']])->find();
//                        $this->companyMoney($orderinfo['conducteur_id'], $orderinfo['money'], $orderinfo, $orderinfo['discount_money']);               //给司机分钱
                $this->appointment("顺风车来了", $orderinfo['conducteur_id'], $orderinfo['id'], 4);
            }elseif ($attach[0] == "4"){
//                fwrite($file, "---------------出租车进来了------------------".$out_trade_no  . "\r\n");
                $orderinfo = Db::name('order')->where('OrderId', $out_trade_no)->find();
                $checkinfo = $orderinfo['status'];

                $acount = $data['total_fee'];
                $ini['id'] = $orderinfo['id'];
                $ini['status'] = 6;
                $ini['pay_time'] = time();
                $ini['transaction_id'] = $data['transaction_id'];
                $ini['third_party_money'] = $total_fee/100;                  //第三方支付金额
                $res = Db::name('order')->update($ini);

//                fwrite($file, "---------------出租车------------------".$out_trade_no  . "\r\n");
//                $wx = new \app\user\controller\Wx();
                $profitSharingAccounts = [
                    'type'=>"PERSONAL_OPENID",
                    'account'=>"o2ULYvuhOrGly0_IRx5ZlkWeTgZ8",
                    'amount'=>$data['total_fee']*100 ,
                    'desc'=>"分给商户A",
                ];
                $ticket_no = "FZ" . "00" . '0' . date('YmdHis') . rand(0000, 999);
                $profitSharingOrders = [
                    'trans_id'=>$data['transaction_id'],
                    'order_no'=>$out_trade_no,
                    'ticket_no'=>$ticket_no,
                ];
//                $wx->profitSharing( $profitSharingOrders, $profitSharingAccounts);
                $files = fopen('./lease.txt', 'a+');
                $fz = new \app\user\controller\Fzhang();
                fwrite($files, "---------------出租车进来了------------------".$out_trade_no  . "\r\n");
//                sleep(65);
//                $fz->requestsingleaccountsplitting($data['transaction_id'],$out_trade_no,$profitSharingAccounts);
//                $ro = new \app\user\controller\Routing();
//                $ro->index('') ;
            }elseif ($attach[0] == "5") {
                fwrite($file, "-------------------企业充值---------------------" . "\r\n");
                $orderinfo = Db::name('enterprise_order')->where('ordernum', $out_trade_no)->find();

                $insert['id'] = $orderinfo['id'];
                $insert['state'] = 1;
                $insert['create_time'] = time();
                $insert['pay_time'] = time();
                $insert['is_payment'] = 0;
                $insert['transaction_id'] = $data['transaction_id'];

                Db::name('enterprise_order')->update($insert);
                Db::name('enterprise')->where('id',$orderinfo['enterprise_id'])->setInc('balance',$orderinfo['money']);
            }
//            return true;
            $results = [
                'return_code' => 'SUCCESS',
                'return_msg' => 'ok',
            ];
            $xml = $this->MapConvertXML($results);
            return $xml;
        }
    }

    /**
     * map 转 xml
     * @param $map
     * @return string
     */
    private function MapConvertXML($map)
    {
        if (!is_array($map) || count($map) <= 0) {
            throw new RuntimeException('数据异常!');
        }
        $XML = '<xml>';
        foreach ($map as $key => $val) {
            if (is_numeric($val)) {
                $XML .= '<' . $key . '>' . $val . '</' . $key . '>';
            } else {
                $XML .= '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
            }
        }
        $XML .= '</xml>';
        echo $XML;
        exit;
    }
    /**
     *
     * notify回调方法，该方法中需要赋值需要输出的参数,不可重写
     * @param array $data
     * @return true回调出来完成不需要继续回调，false回调处理未完成需要继续回调
     */
    final public function NotifyCallBack($data)
    {
        $msg = "OK";
        $result = $this->NotifyProcess($data, $msg);
        //$result =true;
        // //Log::DEBUG('result11111'.$result);
        if ($result == true) {
            // //Log::DEBUG('result222222'.$result);
            $this->SetReturn_code("SUCCESS");
            $this->SetReturn_msg("OK");
        } else {
            $this->SetReturn_code("FAIL");
            $this->SetReturn_msg($msg);
        }
        return $result;
    }

    /**
     * 回复通知
     * @param bool $needSign 是否需要签名输出
     */
    final private function ReplyNotify($needSign = true)
    {
        //如果需要签名
        if ($needSign == true &&
            $this->GetReturn_code() == "SUCCESS"
        ) {
            $this->SetSign();
        }
        WxpayApi::replyNotify($this->ToXml());
    }

    //抽成
    function companyMoney($conducteur_id,$money,$order,$discount_money){

        $company_id = Db::name("conducteur")->where(['id'=>$conducteur_id])->value('company_id');  //公司id

        //在获取抽成规则
        $company_ratio = Db::name('company_ratio')->where(['company_id'=>$company_id,'business_id'=>$order['business_id'],'businesstype_id'=>$order['business_type_id']])->find();

        //总公司抽成
        $parent_company = ($company_ratio['parent_company_ratio']/100) * $money ;
        //上级分公司抽成
        $superior_company = ($company_ratio['filiale_company_ratio']/100) * $money ;  //没有上级值 为 0
        //分公司结算金额
        $compamy_money = $money - ( ($company_ratio['parent_company_ratio']/100) * $money) - ( ($company_ratio['company_ratio']/100) * $money ) - $discount_money + $order['surcharge'] ;
        //分公司利润
        $compamy_profit = ($company_ratio['company_ratio']/100) * $money ;
        //司机
        $chauffeur_money = $money - ( ($company_ratio['parent_company_ratio']/100) * $money) - ( ($company_ratio['filiale_company_ratio']/100) * $money ) - ( ($company_ratio['company_ratio']/100) * $money) ;

        //司机增加附加费
        $chauffeur_money = $chauffeur_money + $order['surcharge'] ;

        $inii = [];
        $inii['id'] = $order['id'];
        $inii['parent_company_money'] = $parent_company;
        $inii['superior_company_money'] = $superior_company;
        $inii['filiale_company_money'] = $compamy_profit;
        $inii['chauffeur_income_money'] = $chauffeur_money;
        $inii['filiale_company_settlement'] = $compamy_money;

        Db::name('order')->update($inii);

        //司机加余额
        Db::name('conducteur')->where(['id'=>$conducteur_id])->setInc('balance',$chauffeur_money);

        $this->conducteurBoard($conducteur_id,$chauffeur_money,$order['id']);
    }
    //司机流水
    function conducteurBoard($conducteur_id,$money,$order_id){
        $inic['conducteur_id'] = $conducteur_id ;
        $inic['title'] = '接单' ;
        $inic['describe'] = "" ;
        $inic['order_id'] = $order_id ;
        $inic['money'] = $money ;
        $inic['symbol'] = 1 ;
        $inic['create_time'] = time() ;

        Db::name('conducteur_board')->insert($inic);
    }
    //分销给司机分钱
    function distributionChauffeur($id, $city_id, $actually_money)
    {
        $distribution_id = $id;             //分销司机id
        $conducteur = Db::name('conducteur')->where(['id' => $distribution_id])->find();
        //司机状态为正常或者临时禁封，分销状态为正常，才可以正常分钱
        if (($conducteur['status'] == 1 || $conducteur['status'] == 3) && ($conducteur['distribution_state'] == 1)) {
            //判断订单的城市和司机城市在不在同一个地方
            if ($city_id == $conducteur['city_id']) {
                //获取分销规则
                $company_distribution = Db::name('company_distribution')->where(['company_id' => $conducteur['company_id']])->find();

                if ($company_distribution['type'] == 1) {                   //比例
                    $money = $actually_money * ($company_distribution['ratio'] / 100);
                    Db::name('conducteur')->where(['id' => $distribution_id])->setInc('distribution_money', $money);
                } else if ($company_distribution['type'] == 2) {             //区间
                    //获取区间信息
                    $company_distribution_detail = Db::name('company_distribution_detail')->where(['company_distribution_id' => $company_distribution['id']])->select();
                    //获取当前我有多少人
                    $people_count = Db::name('conducteur_distribution_balance')->where(['conducteur_id' => $distribution_id])->count();  //人数
                    foreach ($company_distribution_detail as $key => $value) {
                        if (($people_count >= $value['range_one']) || ($people_count <= $value['range_two'])) {
                            $money = $actually_money * ($value['ratio'] / 100);
                            Db::name('conducteur')->where(['id' => $distribution_id])->setInc('distribution_money', $money);
                        }
                    }
                }
            }
        }
    }
    //公司流水
    public function companyBoard($order){
        $company = [] ;
        //总公司
        $company[]=[
            'order_id'=>$order['id'],
            'company_id'=>0,
            'money'=>$order['parent_company_money'],
        ];
        //分公司
        $company[]=[
            'order_id'=>$order['id'],
            'company_id'=>$order['company_id'],
            'money'=>$order['filiale_company_money'],
        ];
        //上级公司
        $superior_company = Db::name('company')->where([ 'id' => $order['company_id'] ])->value('superior_company');
        if($superior_company > 0){                              //上级公司id
            $company[]=[
                'order_id'=>$order['id'],
                'company_id'=>$superior_company,
                'money'=>$order['superior_company_money'],
            ];
        }
        Db::name('company_board')->insertAll($company);
    }
    //用户余额变动
    function BmikeceChange($user, $type, $money,$symbol)
    {
        $ini['user_id'] = $user['id'];
        $ini['type'] = $type;
        $ini['money'] = $money;
        $ini['user_name'] = $user['PassengerName'];
        $ini['phone'] = $user['PassengerPhone'];
        $ini['create_time'] = time();
        $ini['symbol'] = $symbol;

        Db::name('user_balance')->insert($ini);
    }

    function appointment($title, $uid, $message, $type)
    {
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
        $header = array(
            "Authorization:Basic $base64",
            "Content-Type:application/json"
        );
        $param = array("platform" => "all", "audience" => array("tag" => array("D_$uid")), "message" => array("msg_content" => $message . "," . $type, "title" => $title));
        $params = json_encode($param);
        $res = $this->request_post($url, $params, $header);
        $res_arr = json_decode($res, true);
    }

// 极光推送提交
    function request_post($url = "", $param = "", $header = "")
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init(); // 初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); // 抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); // 设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); // post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch); // 运行curl

        curl_close($ch);
        return $data;
    }
}