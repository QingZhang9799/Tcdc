<?php
namespace app\traffic\controller;

use app\api\model\Conducteur;
use app\api\model\Company;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Request;

class Driver extends Base
{
    //获取最近一次出发的行程
    public function getNearByJourney()
    {
        $now = time();
        $params = [
//            "conducteur_id"=> input('?conducteur_id') ? input('conducteur_id') : null,
            "status" => input("?status") ? ["in", input("status")] : null,
//            "times" => ["gt", time() ],
        ];
        $params = $this->filterFilter($params);
        $required = ["status"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机不能为空，请检查输入"
            ];
        }
//        $res = db("jorder_passenger_aboutjourneyorder")->where($params)->order("times", "asc")->find();
//        if(!$res){
//            unset($params["times"]);
//            $res = db("jorder_passenger_aboutjourneyorder")->where($params)->order("times", "desc")->find();
//        }
//        if($res){
//            $res["tickets"]=db("jorder_passenger_aboutjourneyorder")->where(["journey_id"=>$res["id"]])->select();
//        }
        //获取司机的车辆
        $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id'=>input('?conducteur_id')])->value('vehicle_id') ;
        $res["tickets"] = Db::name('journey')->where($params)->find();

        return [
            'code' => $res?APICODE_SUCCESS:APICODE_EMPTYDATA,
            'msg' => '查询成功',
            'data' => $res,
        ];
    }
    //根据司机ID获取所有行程
    public function getJourneysByDriverID()
    {
        $params = [
            "conducteur_id"=> input('?conducteur_id') ? input('conducteur_id') : null,
            "status" => input("?status") ? input("status") :"1,2",
        ];
        $params = $this->filterFilter($params);
        $required = ["conducteur_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机不能为空，请检查输入"
            ];
        }

        //客运司机
        $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => input('conducteur_id')])->value('vehicle_id');

//        $res = db("journey_aboutdriver")->field('j.*,v.VehicleNo')->where($params)->order("times", "asc")->select();
        //不显示过期的行程,大于今天以后的行程
        $start_time = strtotime( date('Y-m-d' , strtotime("-1 day") )." 00:00:00" ) ;


        $journey = Db::name('journey')->alias('j')
            ->field('j.*,v.VehicleNo')
            ->join('mx_vehicle v','v.id = j.vehicle_id','left')
            ->where(['j.vehicle_id'=>$vehicle_id])
            ->where('j.status','in',$params["status"])
            ->where('j.times','gt',$start_time)
//            ->where('j.status','in',"1,2")
//            ->where('j.times','lt',$start_end)
            ->order("j.times", "asc")->select();

        $time = time() ;
        $data = [] ;
        foreach ($journey as $key=>$value){
            if($value['status'] == 1 || $value['status'] == 2){
//                if($time > intval($value['times'])){
////                    unset($journey[$key]);
//                }else{
                    $data[] = [
                        "id"=>$value['id'],
                        "type"=> $value['type'],
                        "city_id"=> $value['city_id'],
                        "particulars"=> $value['particulars'],
                        "status"=> $value['status'],
                        "spot"=> $value['spot'],
                        "times"=> $value['times'],
                        "vehicle_id"=> $value['vehicle_id'],
                        "total_ticket"=> $value['total_ticket'],
                        "residue_ticket"=> $value['residue_ticket'],
                        "origin"=> $value['origin'],
                        "destination"=> $value['destination'],
                        "price"=> $value['price'],
                        "origin_longitude"=> $value['origin_longitude'],
                        "origin_latitude"=> $value['origin_latitude'],
                        "destination_longitude"=> $value['destination_longitude'],
                        "destination_latitude"=> $value['destination_latitude'],
                        "cancel_rules"=> $value['cancel_rules'],
                        "start_time"=> $value['start_time'],
                        "chauffeur_income_money"=> $value['chauffeur_income_money'],
                        "parent_company_money"=> $value['parent_company_money'],
                        "superior_company_money"=> $value['superior_company_money'],
                        "filiale_company_money"=> $value['filiale_company_money'],
                        "discounts_money"=> $value['discounts_money'],
                        "discounts_details"=> $value['discounts_details'],
                        "filiale_company_settlement"=> $value['filiale_company_settlement'],
                        "VehicleNo"=> $value['VehicleNo'],
                        "Ridership"=> Db::name('journey_order')->alias('j')
                            ->field('p.*')
                            ->join('mx_jorder_passenger p','p.journey_order_id = j.id','left')
                            ->where(['j.journey_id' =>$value['id'] ])
                            ->where('j.status','in','2,3,5')
                            ->where('p.is_accepted','in','1,2')
                            ->where('j.pay_time','gt',0)
                            ->order('p.is_accepted asc')
                            ->count(),
                    ];
//                }
            }
        }

        return [
            'code' => $data?APICODE_SUCCESS:APICODE_EMPTYDATA,
            'msg' => '查询成功',
            'data' => $data,
        ];
    }
    //根据行程ID获取行程车票信息
    public function getJourneyDetailByJourneyID()
    {
        $params = [
            "journey_id"=> input('?journey_id') ? input('journey_id') : null,
            "is_accepted" => input("?is_accepted") ? ["in", input("is_accepted")] : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["journey_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "行程不能为空，请检查输入"
            ];
        }
        $required = ["conducteur_id"];

//        $res['jorder_passenger'] = db("jorder_passenger_aboutjourneyorder")->where($params)->order('is_accepted asc')->select();

        $journey = Db::name('journey')
            ->where('status','in','1,2')
            ->where(['id'=>input('journey_id')])
            ->find();
        $ini = [] ;
        //$index ==最终筛选数字 ,获取主路线
        if($journey){
            $ini['origin'] = $journey['origin'];
            $ini['destination'] = $journey['destination'];
            $ini['spot'] = $journey['spot'];
            $ini['times'] = $journey['times'];
            $ini['id'] = $journey['id'];
            $VehicleNo = Db::name('vehicle')->where(['id' => $journey['vehicle_id']])->value('VehicleNo');
            $ini['VehicleNo'] = $VehicleNo;

            $ini['origin_longitude'] = $journey['origin_longitude'];
            $ini['origin_latitude'] = $journey['origin_latitude'];
            $ini['destination_longitude'] = $journey['destination_longitude'];
            $ini['destination_latitude'] = $journey['destination_latitude'];
            $ini['status'] = $journey['status'];
        }else{
            $ini['origin'] ="";
            $ini['destination'] = "";
            $ini['spot'] = "";
            $ini['times'] = "";
            $ini['id'] = "";
            $ini['VehicleNo'] = "";
            $ini['origin_longitude'] = "";
            $ini['origin_latitude'] = "";
            $ini['destination_longitude'] = "";
            $ini['destination_latitude'] = "";
        }

        //乘客列表
        $passenger = [] ;
        $journey_order = Db::name('journey_order')->alias('j')
            ->field('j.*,p.id as p_id,p.name,p.number,p.is_accepted,p.journey_order_id,p.price,p.phone')
            ->join('mx_jorder_passenger p','p.journey_order_id = j.id','left')
            ->where(['j.journey_id' =>$journey['id'] ])
            ->where('j.status','in','2,3,5,8')
            ->where('p.is_accepted','in',input("is_accepted"))
            ->where('j.pay_time','gt',0)
            ->order('p.is_accepted asc')
            ->select();

        $ini['total_ticket'] = $journey['total_ticket'] ;//总票数
        $ini['Ridership'] = count($journey_order) ;

        foreach ($journey_order as $k=>$v){
            $passenger[] = [
                'id'=>$v['p_id'],
                'name'=>$v['name'],
                'number'=>$v['number'],
                'phone'=>$v['phone'],
                'is_accepted'=>$v['is_accepted'],
                'origin'=>$v['origin'],
                'origin_longitude'=>$v['origin_longitude'],
                'origin_latitude'=>$v['origin_latitude'],
                'destination_longitude'=>$v['destination_longitude'],
                'destination_latitude'=>$v['destination_latitude'],
                'journey_order_id'=>$v['id'],
            ];
        }
        $ini['jorder_passenger'] = $passenger ;


        return [
            'code' => $ini?APICODE_SUCCESS:APICODE_EMPTYDATA,
            'msg' => '查询成功',
            'data' => $ini,
        ];
    }
    //验票
    public function checkTicket(){
        $params = [
            "journey_id"=> input('?journey_id') ? input('journey_id') : null,
            "jopcode"=>input("?jopcode")?input("jopcode"):null,
        ];
        $params = $this->filterFilter($params);
        $required = ["journey_id","jopcode"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "参数缺失，请检查输入"
            ];
        }
        $res=db("jorder_passenger_aboutjourneyorder")->where($params)->find();
        if(!$res){
            return [
                "code" => APICODE_NOTFOUND,
                "msg" => "车票号不存在或非本次行程，请检查"
            ];
        }else{
            if($res["is_accepted"]!=0){
                return [
                    "code" => APICODE_EMPTYDATA,
                    "msg" => "该车票已完成验票，请检查"
                ];
            }else{
                db("jorder_passenger")->where(["id"=>$res["id"]])->setField(["is_accepted"=>1]);
                $jorder_passenger = Db::name('jorder_passenger')->where(['id'=>$res["id"]])->find();
                //判断一下，行程里面是不是全部验票
                $jorder_passengers = Db::name('jorder_passenger')->where(['journey_order_id'=>$jorder_passenger['journey_order_id']])
                    ->where('is_accepted','neq',"1")
                    ->select();
                if(empty($jorder_passengers)){
                    $inii['id'] = $jorder_passenger['journey_order_id'];
                    $inii['status'] = 8;
                    Db::name('journey_order')->update($inii);
                }
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "验票成功"
                ];
            }
        }
    }
    //司机首页
    public function PassengerHome()
    {
        $params = [
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["conducteur_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //客运司机
        $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => input('conducteur_id')])->value('vehicle_id');

        //查询今天行程
        $start_time = strtotime(date('Y-m-d', time()) . " 00:00:00");            //开始时间
        $end_time = strtotime(date('Y-m-d', time()) . " 23:59:59");              //结束时间

        $journey = Db::name('journey')->where('times', 'gt', $start_time)
            ->where('times', 'lt', $end_time)
            ->where(['vehicle_id'=>$vehicle_id])
            ->where('status','in','1,2')
            ->order('status desc')
            ->select();
//        halt($journey);
        $ini = [] ;
        $passenger = [] ;

        if(!empty($journey)){
            //筛选出最近车程
            $sum = 0;
            $index = 0;
            foreach ($journey as $key => $value) {
                $time = time();    //当前时间
                if($value['status'] == 2){    //为2的时候，直接就是第二个
                    $index = $key;
                    break;  //结束循环
                }else{    //状态为1的时候，匹配最近的
                    $tims = $value['times'];

                    $residue = abs($value['times'] - time());         //相差时间

                    if ($sum == 0) {
                        $sum = $residue;
                    }

                    if ($residue < $sum) {
                        $sum = $residue;
                        $index = $key;
                    }
                }
            }

            //$index ==最终筛选数字 ,获取主路线
            $ini['origin'] = $journey[$index]['origin'];
            $ini['destination'] = $journey[$index]['destination'];
            $ini['spot'] = $journey[$index]['spot'];
            $ini['times'] = $journey[$index]['times'];
            $ini['id'] = $journey[$index]['id'];
            $VehicleNo = Db::name('vehicle')->where(['id' => $journey[$index]['vehicle_id']])->value('VehicleNo');
            $ini['VehicleNo'] = $VehicleNo;

            $ini['origin_longitude'] = $journey[$index]['origin_longitude'];
            $ini['origin_latitude'] = $journey[$index]['origin_latitude'];
            $ini['destination_longitude'] = $journey[$index]['destination_longitude'];
            $ini['destination_latitude'] = $journey[$index]['destination_latitude'];
            $ini['status'] = $journey[$index]['status'];

            //乘客列表

            $journey_order = Db::name('journey_order')->alias('j')
                ->field('j.*,p.id as p_id,p.name,p.number,p.is_accepted,p.journey_order_id,p.price,p.phone')
                ->join('mx_jorder_passenger p','p.journey_order_id = j.id','left')
                ->where(['j.journey_id' =>$journey[$index]['id'] ])
                ->where('j.status','in',"2,3,5")
                ->where('j.pay_time','gt',0)
                ->where('p.is_accepted','in',"1,2")
                ->order('p.is_accepted asc')
                ->select();
//
            $ini['total_ticket'] = $journey[$index]['total_ticket'] ;//总票数
            $ini['Ridership'] = count($journey_order) ;

            foreach ($journey_order as $k=>$v){
                $passenger[] = [
                    'id'=>$v['p_id'],
                    'name'=>$v['name'],
                    'number'=>$v['number'],
                    'phone'=>$v['phone'],
                    'is_accepted'=>$v['is_accepted'],
                    'origin'=>$v['origin'],
                    'origin_longitude'=>$v['origin_longitude'],
                    'origin_latitude'=>$v['origin_latitude'],
                    'destination_longitude'=>$v['destination_longitude'],
                    'destination_latitude'=>$v['destination_latitude'],
                    'journey_order_id'=>$v['id'],
                ];
            }

            $ini['jorder_passenger'] = $passenger ;
        }else{
            $ini['origin'] = "";
            $ini['destination'] = "";
            $ini['spot'] = "";
            $ini['times'] = "";
            $ini['id'] = 0;
            $ini['VehicleNo'] = "";

            $ini['origin_longitude'] = "";
            $ini['origin_latitude'] = "";
            $ini['destination_longitude'] = "";
            $ini['destination_latitude'] = "";
            $ini['total_ticket'] = 0 ;
            $ini['Ridership'] = 0 ;
            $ini['jorder_passenger'] = $passenger ;
        }


        return [
            'code' => APICODE_SUCCESS,
            'msg' => '查询成功',
            'data' => $ini,
        ];
    }
    //补票-起点
    public function CompensationFareOrigin(){
        if (input('?journey_id')) {
            $params = [
                "id" => input('journey_id')
            ];
            //查询行程的里面所有起点
            $journey = db('journey')->where($params)->find();
            $particulars = $journey['particulars'];

            $part = json_decode($particulars,true);

            $ini =array() ;
            foreach ($part as $key=>$value){
                if($key == 0){
                    array_push($ini,$value['origin']);
                }else{
                    if(!in_array($value['origin'],$ini)){
                        array_push($ini,$value['origin']) ;
                    }
                }
            }
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data"=>$ini
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "行程ID不能为空"
            ];
        }
    }
    //补票-终点
    public function CompensationFareDestination(){
        $params = [
            "journey_id" => input('?journey_id') ? input('journey_id') : null,
            "origin" => input('?origin') ? input('origin') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["journey_id","origin"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //根据行程起点获取终点
        $journey = Db::name('journey')->where(['id' => input('journey_id')])->find();
        $particulars = json_decode($journey['particulars'],true);
        $ini = [] ;
        foreach ($particulars as $key=>$value){
            //判断一下，起点相等取，终点
            if(input('origin') == $value['origin']){
                $spot = explode(',',$journey['spot']);
                $str = "" ;
                foreach ($spot as $k=>$v){
                    $ss = explode('-',$v);
                    $str .= $ss[0]."," ;
                }
                $str = substr($str,0,-1);
                $ini[] = [
                    'destination'=>$value['destination'],
                    'price'=>$value['price'],
                    'spot'=>$str
                ];
            }
        }
        return [
            "code" => APICODE_SUCCESS,
            "msg" => "查询成功",
            "data"=>$ini
        ];
    }
    //补票-全票
    public function CompensationFareFullFare(){
        if (input('?journey_id')) {
            $params = [
                "j.id" => input('journey_id')
            ];
            $data = Db::name('journey')->alias('j')
                ->field('j.*,v.VehicleNo')
                ->join('mx_vehicle v','v.id = j.vehicle_id','left')
                ->where($params)->find();

            $spot = explode(',',$data['spot']);
            $str = "" ;
            foreach ($spot as $k=>$v){
                $ss = explode('-',$v);
                $str .= $ss[0]."," ;
            }
            $str = substr($str,0,-1);
            $data['spot'] = $str ;

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "行程ID不能为空"
            ];
        }
    }
    //开始行程
    public function BeganTravel(){
        if (input('?journey_id')) {
            $params = [
                "id" => input('journey_id'),
                "status" => 2,
            ];

            //判断一下，行程是否有行程中
            $vehicle_id = Db::name('journey')->where(['id'=>input('journey_id')])->value('vehicle_id') ;  //车辆id
            $journeys = Db::name('journey')->where(['vehicle_id'=>$vehicle_id,'status'=>2])->find() ;
            if(!empty($journeys)){
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "您有未结束的行程，请结束行程之后在试"
                ];
            }

            $journey = Db::name('journey')->update($params);

            //将所有已验票的变成行程中
            $journey_order = Db::name('journey_order')->alias('j')
                ->field('j.*,p.id as passenger_id,p.name,p.number,p.is_accepted')
                ->join('mx_jorder_passenger p','p.journey_order_id = j.id','left')
                ->where(['j.journey_id' => input('journey_id')])
                ->where('j.status','eq',2)
                ->select();

            foreach ($journey_order as $key=>$value){
                if($value['is_accepted'] == 1){     //已验票
                    Db::name('journey_order')->where(['id'=>$value['id']])->update(['status'=>3]);
                    Db::name('jorder_passenger')->where(['id'=>$value['passenger_id']])->update(['is_accepted'=>2]);
                }
            }
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "开始行程"
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "行程ID不能为空"
            ];
        }
    }
    //结束行程
    public function EndJourney(){
        if (input('?journey_id')) {
            $params = [
                "id" => input('journey_id'),
                "status" => 9
            ];

            $data = db('journey')->update($params);

            $vehicle_id = Db::name("journey")->where(['id'=>input('journey_id')])->value('vehicle_id') ;

            //将所有已验票的变成已完成
            $journey_order = Db::name('journey_order')->alias('j')
                ->field('j.*,p.id as passenger_id,p.name,p.number,p.is_accepted,p.price as p_money')
                ->join('mx_jorder_passenger p','p.journey_order_id = j.id','left')
                ->where(['j.journey_id' => input('journey_id')])
                ->select();

            require_once "extend/traffic_w_pay/lib/WxPay.Api.php";
            require_once "extend/traffic_w_pay/example/WxPay.Config.php";
            $input = new \WxPayRefund();
            $config = new \WxPayConfig();
            $refund = new  \WxPayApi();//\WxPayApi();
            $p_money = 0 ;
            foreach ($journey_order as $key=>$value){
                if($value['status'] == 2 || $value['status'] == 3){
                    Db::name('jorder_passenger')->where(['id'=>$value['passenger_id']])->update(['is_accepted'=>3]);
                    Db::name('journey_order')->where(['id'=>$value['id']])->update(['status'=>6]);
                }else{
                    Db::name('journey_order')->where(['id'=>$value['id']])->update(['status'=>5]);
                }

                //把已验票变成已完成,把未验票进行退款
                if($value['is_accepted'] == 0){  //进行退票
                    sleep(2);
                    $flags = $this->Refund($value['is_payment'],$value['transaction_id'],$value['id'],$value['passenger_id'],$input,$config,$refund);
                    if($flags == 1){    //退款成功
                        Db::name('jorder_passenger')->where(['id'=>$value['passenger_id']])->update(['is_accepted'=>5]);
                    }
                }else if($value['is_accepted'] == 2 || ( $value['is_accepted'] == 1 && $value['status'] == 2) ){
                    $p_money += $value['p_money'] ;
                }
            }
            $conducteur_id = Db::name("vehicle_binding")->where(['vehicle_id'=>$vehicle_id])->value("conducteur_id");
            $this->companyMoney($conducteur_id, $p_money , input('journey_id'), 0);           //给司机分钱

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "结束行程"
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "行程ID不能为空"
            ];
        }
    }
    //抽成
    function companyMoney($conducteur_id, $money, $order_id, $discount_money)
    {
//        $file = fopen('./traffic.txt', 'a+');
//        fwrite($file, "-------------------抽成进来了--------------------"."\r\n");
//        fwrite($file, "-------------------conducteur_id--------------------".$conducteur_id."\r\n");
//        fwrite($file, "-------------------money--------------------".$money."\r\n");
//        fwrite($file, "-------------------order_id--------------------".$order_id."\r\n");
//        fwrite($file, "-------------------discount_money--------------------".$discount_money."\r\n");

        $company_id = Db::name("conducteur")->where(['id' => $conducteur_id])->value('company_id');  //公司id
//        fwrite($file, "-------------------company_id--------------------".$company_id."\r\n");
        //在获取抽成规则
        $company_ratio = Db::name('company_ratio')->where(['company_id' => $company_id, 'business_id' => 12])
            ->where('businesstype_id', 'eq', 12)->find();

//        fwrite($file, "-------------------company_ratio--------------------".json_encode($company_ratio)."\r\n");

        //总公司抽成
        $parent_company = ($company_ratio['parent_company_ratio'] / 100) * $money;
        //上级分公司抽成
        $superior_company = ($company_ratio['filiale_company_ratio'] / 100) * $money;  //没有上级值 为 0
        //分公司结算金额
        $compamy_money = ($money - $discount_money) - (($company_ratio['parent_company_ratio'] / 100) * $money) - (($company_ratio['filiale_company_ratio'] / 100) * $money);
        //分公司利润
        $compamy_profit = ($company_ratio['company_ratio'] / 100) * $money;
        //司机
        $chauffeur_money = $money - (($company_ratio['parent_company_ratio'] / 100) * $money) - (($company_ratio['filiale_company_ratio'] / 100) * $money) - (($company_ratio['company_ratio'] / 100) * $money);

        $inii = [];
        $inii['id'] = $order_id ;
        $inii['parent_company_money'] = sprintf("%.2f", $parent_company);
        $inii['superior_company_money'] =sprintf("%.2f", $superior_company);
        $inii['filiale_company_money'] = sprintf("%.2f", $compamy_profit);
        $inii['chauffeur_income_money'] = sprintf("%.2f", $chauffeur_money);
        $inii['filiale_company_settlement'] =sprintf("%.2f", $compamy_money);

//        fwrite($file, "-------------------inii--------------------".json_encode($inii)."\r\n");
        $inii['total_price'] = $money ;
        Db::name('journey')->update($inii);

        //司机加余额
//        fwrite($file, "-------------------conducteur_id--------------------".$conducteur_id."\r\n");
//        fwrite($file, "-------------------chauffeur_money--------------------".$chauffeur_money."\r\n");
        Db::name('conducteur')->where(['id' => $conducteur_id])->setInc('balance', sprintf("%.2f", $chauffeur_money));

        $this->conducteurBoard($conducteur_id, $chauffeur_money, $order_id);
    }
    //司机流水
    function conducteurBoard($conducteur_id, $money, $order_id)
    {
        $file = fopen('./traffic.txt', 'a+');
        fwrite($file, "-------------------司机流水进来了--------------------"."\r\n");

        $inic['conducteur_id'] = (int)$conducteur_id;
        $inic['title'] = '接单';
        $inic['describe'] = "";
        $inic['order_id'] = (int)$order_id;
        $inic['money'] = sprintf("%.2f", $money);
        $inic['symbol'] = 1;
        $inic['create_time'] = time();
        fwrite($file, "-------------------inic--------------------".json_encode($inic)."\r\n");

        Db::name('conducteur_board')->insert($inic);
    }

    //退票
    private function Refund($is_payment,$transaction_id,$order_id,$passenger_id,$input,$config,$refund){

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
            fwrite($file, "-------------------新方法进来了--------------------"."\r\n");     //司机电话
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
    //版本更新
    public function VersionsRenewal(){
        $versions_record = Db::name('versions_record')->field('versionCode,versionName,file_size,link')->where(['type'=>2])->find();
        return [
            'code'=>APICODE_SUCCESS,
            'msg'=>'成功',
            'data'=>$versions_record,
        ];
    }

    //修改行程
    public function AmendJourneyTime(){
        $params = [
            "journey_id" => input('?journey_id') ? input('journey_id') : null,
            "time" => input('?time') ? input('time') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["journey_id","time"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //看一下，这个时间和本车辆其他行程是否有冲突
        $vehicle_id = Db::name('journey')->where(['id'=>input('journey_id')])->value('vehicle_id') ;

        $journey = Db::name('journey')->where(['vehicle_id'=>$vehicle_id,'times'=>input('time')])->find() ;
        if(!empty($journey)){
            return [
                "code" => APICODE_ERROR,
                "msg" => "与其他发车出发时间相同，请更换时间",
            ];
        }

        $ini['id'] = input('journey_id');
        $ini['times'] = input('time');
        $res = Db::name("journey")->update($ini) ;

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
}
