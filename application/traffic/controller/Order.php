<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 19-2-26
 * Time: 上午10:53
 */

namespace app\traffic\controller;

use app\api\model\Conducteur;
use app\api\model\Company;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Request;

class Order extends Base
{
    //我的行程
    public function MyJourney(){
        if (input('?conducteur_id')) {
//            $params = [
//                "id" => input('conducteur_id')
//            ];

            $pageSize = input('?pageSize') ? input('pageSize') : 10;
            $pageNum = input('?pageNum') ? input('pageNum') : 0;

            //通过司机获取车辆
            $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id'=>input('conducteur_id')])->value("vehicle_id") ;

            $data = db('journey')->alias('j')
                ->where(['j.vehicle_id'=>$vehicle_id])
                ->order('id desc')->page($pageNum, $pageSize)
                ->select() ;

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }
    }

    //我的钱包
    public function MyWallet(){
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id')
            ];
            $data = db('conducteur')->where($params)->find();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }
    }

    //流水统计
    public function FlowStatistics(){
        $type = input('type');
        $start_time = '';
        $end_time = '' ;
        if($type == 1 ){         //日
            $start_time = strtotime( date('Y-m-d 00:00:00', time()) );
            $end_time = strtotime ( date('Y-m-d 23:59:59', time()) );
        }else if($type == 2){    //月
            $start_time=mktime(0,0,0,date('m'),1,date('Y'));
            $end_time=mktime(23,59,59,date('m'),date('t'),date('Y'));
        }else if($type == 3){    //年
            $start_time = strtotime(date("Y",time())."-1"."-1"); //本年开始
            $end_time = strtotime(date("Y",time())."-12"."-31"); //本年结束
        }else if($type == 4){    //自定义
            $start_time = input('start_time') / 1000 ;
            $end_time =input('end_time') / 1000 ;
        }
//       echo $start_time."<br />" ;
//       echo $end_time ."<br />";
//       exit();

        $conducteur_id =input('conducteur_id');
        //总收入
        $board_money = Db::name('conducteur_board')->alias('c')
            ->where('c.create_time','egt',$start_time)
            ->where('c.create_time','elt',$end_time)
            ->where(['symbol'=>1])
            ->where(['conducteur_id'=>$conducteur_id])
            ->sum('money');

        if( empty($board_money) ){
            $board_money = 0 ;
        }

        $data['board_money'] =sprintf("%.2f", $board_money)  ;

        //总支出
        $expend_money = Db::name('conducteur_board')->alias('c')
            ->where('c.create_time','egt',$start_time)
            ->where('c.create_time','elt',$end_time)
            ->where(['symbol'=>2])
            ->where(['conducteur_id'=>$conducteur_id])
            ->sum('money');

        if(empty($expend_money)){
            $expend_money = 0 ;
        }

        $data['expend_money'] = sprintf("%.2f", $expend_money)  ;

        //分类名称
        $fare_money = $this->classifyMoney($start_time,$end_time,$conducteur_id,'接单');
        $award_money = $this->classifyMoney($start_time,$end_time,$conducteur_id,'奖励');
        $else_money = $this->classifyMoney($start_time,$end_time,$conducteur_id,'其他');

        if(empty($fare_money)){
            $fare_money = 0 ;
        }
        if(empty($award_money)){
            $award_money = 0 ;
        }
        if(empty($else_money)){
            $else_money = 0 ;
        }

        $data['classify'] = [
            [
                'classify_name'=>'车费',
                'money'=>sprintf("%.2f", $fare_money)
            ],
            [
                'classify_name'=>'奖励',
                'money'=>sprintf("%.2f", $award_money)
            ],
            [
                'classify_name'=>'其他',
                'money'=>sprintf("%.2f", $else_money)
            ]
        ];

        //订单数
        $order = Db::name('conducteur_board')->alias('c')
            ->field('count(order_id) as order_count')
            ->where('c.create_time','egt',$start_time)
            ->where('c.create_time','elt',$end_time)
            ->where(['conducteur_id'=>$conducteur_id])
            ->find();

        $data['order_count'] = $order['order_count'] ;

        //流水明细
        $data['board'] = Db::name('conducteur_board')->alias('c')
            ->where('c.create_time','egt',$start_time)
            ->where('c.create_time','elt',$end_time)
            ->where(['conducteur_id'=>$conducteur_id])
            ->order('c.id desc')
            ->select() ;

        return ['code'=>APICODE_SUCCESS,'msg'=>'成功','data'=>$data];
    }

    protected function classifyMoney($start_time,$end_time,$conducteur_id,$title){
        $title_money = Db::name('conducteur_board')->alias('c')
            ->where('c.create_time','egt',$start_time)
            ->where('c.create_time','elt',$end_time)
            ->where(['c.symbol'=>1])
            ->where(['c.conducteur_id'=>$conducteur_id])
            ->where(['c.title'=>$title])
            ->sum('money');

        return $title_money;
    }

    //申请提现
    public function applyBalanceWithdraw(){
        $params = [
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
            "money" => input('?money') ? input('money') : null,
            "type" => input('?type') ? input('type') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["conducteur_id", "money"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //根据司机获取公司参数
        $company_id = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->value('company_id') ;
        $company = Db::name('company')->where(['id' =>$company_id ])->find() ;
        $limitNumber = $company['limitNumber'] ;
        //看司机提现了几次
        $start_time = strtotime( date('Y-m-d H:i:s',mktime(0,0,0,date('m'),1,date('Y'))) );
        $end_time = strtotime( date('Y-m-d H:i:s',mktime(23,59,59,date('m'),date('t'),date('Y'))) );

        $conducteur_withdraw_count = Db::name('conducteur_withdraw')
            ->where(['conducteur_id'=>input('conducteur_id')])
            ->where(['state'=>1])
            ->where('create_time','gt',$start_time)
            ->where('create_time','lt',$end_time)
            ->count() ;

        if($conducteur_withdraw_count >=$limitNumber ){
            return [
                "code" => APICODE_ERROR,
                "msg" => "提现次数已超过",
            ];
        }

        Db::name('conducteur')->where(['id'=>input('conducteur_id')])->setDec('balance' , input('money') );         //扣除司机余额
        Db::name('conducteur')->where(['id'=>input('conducteur_id')])->setInc('freeze_balance',input('money'));

        //保存提现记录
        $ini['conducteur_id'] = input('conducteur_id') ;
        $ini['money'] = input('money') ;
        $ini['state'] = 0 ;
        $ini['title'] = "司机提现" ;
        $ini['create_time'] = time() ;
        $ini['day'] = date('Y-m-d' , time() )  ;

        $conducteur_withdraw = Db::name('conducteur_withdraw')->insert($ini);

        //司机余额变动表
        $inii['conducteur_id'] = input('conducteur_id');
        $inii['title'] = '提现';
        $inii['describe'] = '';
        $inii['order_id'] = 0;
        $inii['money'] = input('money');
        $inii['symbol'] = 2;
        $inii['create_time'] = time();

        Db::name('conducteur_board')->insert($inii);

        if($conducteur_withdraw){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'申请成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'申请失败'
            ];
        }
    }

    //司机余额
    public function CashBalance(){
        if (input('?conducteur_id')) {
            $params = [
                "conducteur_id" => input('conducteur_id')
            ];
            //根据司机获取公司参数
            $company_id = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->value('company_id') ;
            $freeze_balance = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->value('freeze_balance') ;
            $money = Db::name('conducteur_withdraw')->where(['conducteur_id'=>input('conducteur_id')])->where(['state'=>1])->value('sum(money)') ;

            $company = Db::name('company')->where(['id' =>$company_id ])->find() ;
            $minWithdraw = $company['minWithdraw'] ;
            //锁定天数
            $payment_days = $company['payment_days'] ;
            //可提现余额
            $order_money = Db::name('order')->where(['conducteur_id'=>input('conducteur_id')])->value('sum(money)') ;

            //锁定余额
            $conducteur_id = input('conducteur_id') ;
            //锁定余额
            $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id'=>$conducteur_id])->value('vehicle_id') ;
            $chauffeur_income_money =  Db::name('journey')->where(['vehicle_id'=>$vehicle_id])
                ->where('times','gt',time()-60*60*24*$payment_days)
                ->where('status','in','6,9')
                ->value('sum(chauffeur_income_money)') ;
            //锁定余额2
            $chauffeur_income_money2 =  Db::name('order')->where(['conducteur_id'=>$conducteur_id])
                ->where('create_time','gt',time()-60*60*24*$payment_days)
                ->where('status','in','6,9')
                ->value('sum(chauffeur_income_money)');

            if(empty($chauffeur_income_money2)){
                $chauffeur_income_money2 = 0 ;
            }
            if(empty($chauffeur_income_money)){
                $chauffeur_income_money = 0 ;
            }
            $chauffeur_income_money=$chauffeur_income_money+$chauffeur_income_money2;
            //可提现余额
            $chauffeur_income_moneys =  Db::name('journey')->where(['vehicle_id'=>$vehicle_id])
                ->where('times','lt',time()-60*60*24*$payment_days)
                ->where('status','in','6,9')
                ->value('sum(chauffeur_income_money)');
            //可提现余额2
            $chauffeur_income_moneys2 =  Db::name('order')->where(['conducteur_id'=>$conducteur_id])
                ->where('create_time','lt',time()-60*60*24*$payment_days)
                ->where('status','in','6,9')
                ->value('sum(chauffeur_income_money)');
            if(empty($chauffeur_income_moneys)){
                $chauffeur_income_moneys = 0 ;
            }
            if(empty($chauffeur_income_moneys2)){
                $chauffeur_income_moneys2 = 0 ;
            }
            $chauffeur_income_moneyss=$chauffeur_income_moneys+$chauffeur_income_moneys2;
            $data = [
                'withdraw_balance' =>sprintf("%.2f" , ($chauffeur_income_moneyss - $freeze_balance - $money) )   ,          //可提现余额
                'locking_balance' => $chauffeur_income_money ,          //锁定余额
                'freeze_balance' => $freeze_balance ,        //冻结余额
                'minWithdraw' => $minWithdraw ,              //单次提现金额最小值
                'money' => $money ,              //已经提现余额
                'chauffeur_income_moneys'=>$chauffeur_income_moneys,
                'chauffeur_income_moneys2'=>$chauffeur_income_moneys2,
            ] ;
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" =>$data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "企业ID不能为空"
            ];
        }
    }

    //未到账列表
    public function DeliveryAccount(){
        if (input('?conducteur_id')) {
            $params = [
                "conducteur_id" => input('conducteur_id')
            ];

            $company_id = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->value('company_id') ;
            $freeze_balance = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->value('freeze_balance') ;
            $money = Db::name('conducteur_withdraw')->where(['conducteur_id'=>input('conducteur_id')])->where(['state'=>1])->value('sum(money)') ;

            $company = Db::name('company')->where(['id' =>$company_id ])->find() ;

            $minWithdraw = $company['minWithdraw'] ;
            //锁定天数
            $payment_days = $company['payment_days'] ;

            $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id'=>input('conducteur_id')])->value('vehicle_id') ;

            $data =  Db::name('journey')->field('id,price,times,chauffeur_income_money')->where(['vehicle_id'=>$vehicle_id])
                ->where('times','gt',time()-60*60*24*$payment_days)
                ->where('status','in','6,9')
                ->where('chauffeur_income_money','gt',0)
                ->select();

            foreach ($data as $key=>$value){
                $data[$key]['price'] = sprintf("%.2f", ($value['price'])) ;
            }

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "企业ID不能为空"
            ];
        }
    }

    //余额提现
    public function balance_withdraw(){
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id')
            ];

            $balance = db('conducteur')->where($params)->value('balance');
            $data['balance'] =  $balance;

            //余额提现明细
            $data['conducteur_withdraw'] = Db::name('conducteur_withdraw')->where([ 'conducteur_id' => input('conducteur_id') ])->order('create_time desc')->select();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }
    }

    //司机数据
    public function ConducteurData(){
        if (input('?conducteur_id')) {
            //获取车辆
            $vehicle_id = Db::name("vehicle_binding")->where(['conducteur_id'=>input('conducteur_id')])->value('vehicle_id') ;
            $params = [
                "vehicle_id" => $vehicle_id
            ];
            //获取司机当天的订单数和金额
            $time = date('Y-m-d',time());

            $start_time = strtotime($time." 00:00:00") ;
            $end_time = strtotime($time." 23:59:59") ;

            //订单数
            $order_count = db('journey')->where($params)->where('status','in','6,9')->where('times','egt',$start_time)
           ->where('times','elt',$end_time)->count();

            $data['order_count']  = sprintf("%.2f", $order_count ) ;
            $paramss = [
                "conducteur_id" => input('conducteur_id')
            ];
            //金额
            $order_money = db('conducteur_board')->where($paramss)->where(['title'=>'接单'])->where('create_time','egt',$start_time)->where('create_time','elt',$end_time)->sum('money');
            if(empty($order_money)){
                $order_money = 0 ;
            }
            $data['order_money']  = $order_money ;

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }
    }

    //退票
    public function Refund(){
        require_once "extend/traffic_w_pay/lib/WxPay.Api.php";
        require_once "extend/traffic_w_pay/example/WxPay.Config.php";
        $input = new \WxPayRefund();
        $config = new \WxPayConfig();
        $refund = new  \WxPayApi();//\WxPayApi();

        $journey_order = Db::name('journey_order')->alias('j')
            ->field('j.*')
            ->where(['j.id' => (int)input('journey_order_id')])
            ->find();

        Db::name('journey_order')->where(['id'=>(int)$journey_order['id']])->update(['status'=>5]);

        Db::name('jorder_passenger')->where(['id'=>input('jorder_passenger_id')])->update(['is_accepted'=>5]);

        $flags = $this->Refunds(3,$journey_order['transaction_id'],$journey_order['id'],(int)input('jorder_passenger_id'),$input,$config,$refund);
        if($flags == 1){    //退款成功
            Db::name('jorder_passenger')->where(['id'=>(int)input('jorder_passenger_id')])->update(['is_accepted'=>5]);
        }

        //还原票数
        Db::name('journey')->where(['id'=>$journey_order['journey_id']])->setInc('residue_ticket',1) ;

        return [
            "code" => APICODE_SUCCESS,
            "msg" => "退票成功"
        ];
    }

    //退票
    private function Refunds($is_payment,$transaction_id,$order_id,$passenger_id,$input,$config,$refund){

        $flag = 0 ;
        if($is_payment == 1){                //微信
//            include_once  "extend/traffic_WeChat/WxPayApi.php" ;
//            include_once  "extend/traffic_WeChat/WxPayConfig.php" ;
//            include_once  "extend/traffic_WeChat/WxPayData.php" ;
//            include_once  "extend/traffic_WeChat/WxPayNotify.php" ;
//            include_once  "extend/traffic_WeChat/log.php" ;
//
//            $indent = Db::name('journey_order')->where('id',$order_id)->find();
//            if(empty($indent)){
//                $flag = 0 ;
//            }
//            //退票按比例退
//            $cancel_rules = Db::name('journey')->where(['id' => $indent['journey_id']])->value('cancel_rules');
//            $times = Db::name('journey')->where(['id' => $indent['journey_id']])->value('times');
//            $prices = Db::name('jorder_passenger')->where('id','in',$passenger_id)->sum('price');
//
//            $cancel = json_decode($cancel_rules);
//
//            $proportion = $this->check($cancel,$times) ;
//
//            $price = $prices - ($prices * ($proportion/100)) ;
//            //退款单号
//            $tk_code  = "TK" . date('YmdHis') . rand(0000, 999);
//
//            $input = new \WxPayRefund();
//            $input->SetTransaction_id($indent['transaction_id']);//微信订单号
//            $input->SetOut_refund_no($tk_code);//退款单号
//            $input->SetTotal_fee($indent['price']*100);//订单金额
//            $input->SetRefund_fee($price*100);//退款金额
//            $input->SetOp_user_id('1337863101');//商户号
//            $refund = new  \WxPayApi();//\WxPayApi();
//            $result = $refund->refund($input);
//            if(($result['return_code']=='SUCCESS') && ($result['result_code']=='SUCCESS')){
//                $flag = 1 ;
//            }else if(($result['return_code']=='FAIL') || ($result['result_code']=='FAIL')){
//                $flag = 0 ;
//            }else{
//                $flag = 0 ;
//            }
        }else if($is_payment == 2){         //支付宝
//            include_once 'extend/traffic_Alipay/config.php';
//            include_once 'extend/traffic_Alipay/pagepay/service/AlipayTradeService.php';
//            include_once 'extend/traffic_Alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php';
//            require_once 'extend/traffic_Alipay/pagepay/buildermodel/AlipayTradeRefundContentBuilder.php';
//
//            $param = Request::instance()->param();
//            $config = $GLOBALS['config'];
//            $indent = Db::name('journey_order')->where('id',$param['order_id'])->find();
//            if(empty($indent)){
//                $flag = 0 ;
//            }
//            //退票按比例退
//            $cancel_rules = Db::name('journey')->where(['id' => $indent['journey_id']])->value('cancel_rules');
//            $times = Db::name('journey')->where(['id' => $indent['journey_id']])->value('times');
//            $prices = Db::name('jorder_passenger')->where('id','in',$passenger_id)->sum('price');
//            $cancel = json_decode($cancel_rules);
//            $proportion = $this->check($cancel,$times) ;
//            $price = $prices - ($prices * ($proportion/100)) ;
//            //退款单号
//            $tk_code  = "TK" . date('YmdHis') . rand(0000, 999);
//            //支付宝交易号
//            $trade_no = trim($indent['transaction_id']);
//            $refund_amount = number_format($price,2);
//            $refund_reason = '正常退款';
//            //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
//            //构造参数
//            $RequestBuilder=new \AlipayTradeRefundContentBuilder();
//            $RequestBuilder->setTradeNo($trade_no);
////        $RequestBuilder->setRefundAmount($refund_amount);
//            $RequestBuilder->setRefundReason($refund_reason);
//            $RequestBuilder->setOutRequestNo($tk_code);
//            $RequestBuilder->setOutTradeNo($tk_code);
//            $RequestBuilder->setRefundAmount($refund_amount);
//            $aop = new \AlipayTradeService($config);
//            $response = $aop->Refund($RequestBuilder);
//            if($response->code==10000&&$response->msg=='Success'){
//                $flag = 1 ;
//            }else{
//                $flag = 0 ;
//            }
        }else if($is_payment == 3){         //小程序微信

            $file = fopen('./traffic.txt', 'a+');
            fwrite($file, "-------------------新方法进来了--------------------".$order_id."\r\n");     //司机电话
            //查找是否有订单
            $indent = Db::name('journey_order')->where('id',$order_id)->find();
            fwrite($file, "-------------------indent--------------------".json_encode($indent)."\r\n");     //司机电话
            if(empty($indent)){
                $flag = 0 ;
            }
            //查找是否有订单
            $indent = Db::name('journey_order')->where('id',$order_id)->find();
            if(empty($indent)){
                return ['code'=>APICODE_ERROR];exit;
            }
            //退票按比例退
            $cancel_rules = Db::name('journey')->where(['id' => $indent['journey_id']])->value('cancel_rules');
            $times = Db::name('journey')->where(['id' => $indent['journey_id']])->value('times');
            $status = Db::name('journey')->where(['id' => $indent['journey_id']])->value('status');
            $prices = Db::name('jorder_passenger')->where('id','in',$passenger_id)->sum('price');

            $cancel = json_decode($cancel_rules);

            $proportion = $this->check($cancel,$times,$status) ;

            $price = $prices - ($prices * ($proportion/100)) ;
            fwrite($file, "-------------------indent--------------------".($indent['price']*100)."\r\n");     //司机电话
            fwrite($file, "-------------------price--------------------".($price*100)."\r\n");     //司机电话
            //退款单号
            $tk_code  = "TK" . date('YmdHis') . rand(0000, 999);



            $input->SetTransaction_id($indent['transaction_id']);//微信订单号
            $input->SetOut_refund_no($tk_code);//退款单号
            $input->SetTotal_fee($indent['price']*100);//订单金额
            $input->SetRefund_fee($price*100);//退款金额
            $input->SetOp_user_id('1337863101');//商户号

            fwrite($file, "-------------------input--------------------".json_encode($input)."\r\n");     //司机电话
            $result = $refund->refund($config,$input);
            //halt($result);
            fwrite($file, "-------------result-----------------------".json_encode($result)."\r\n");
            if(($result['return_code']=='SUCCESS') && ($result['result_code']=='SUCCESS')){
                $flag =1 ;
            }else if(($result['return_code']=='FAIL') || ($result['result_code']=='FAIL')){
                $flag =0 ;
            }else{
                $flag = 0 ;
            }
            return $flag;
        }
        return $flag ;
    }

    //退票区间
    private function check($cancel,$times,$status){
        $proportion = 0 ;
        $time = time() ;
        $cancelRule=array();
        foreach ($cancel as $val){
            foreach ($val as $key=>$value){
                $cancelRule[$key]=$value;
            }
        }
        $calculate=$times-$time;
        if($calculate>24*60*60){
            $proportion=(int)$cancelRule["1"];
        }elseif ($calculate>2*60*60){
            $proportion=(int)$cancelRule["2"];
        }elseif($calculate>1*60*60){
            $proportion=(int)$cancelRule["3"];
        }elseif($calculate>0){
            $proportion=(int)$cancelRule["4"];
        }else{
            $proportion = (int)$cancelRule["5"] ;
        }
        if($status==2){
            $proportion = (int)$cancelRule["5"] ;
        }
        return $proportion ;
    }

    //取消行程
    public function CancelJourney(){
        if (input('?journey_id')) {
            $params = [
                "id" => input('journey_id'),
                "status" => 5,
            ];
            $res = db('journey')->update($params) ;

            if( $res > 0){
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "取消成功",
                ];
            }else{
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "取消失败",
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "行程ID不能为空"
            ];
        }
    }
}

