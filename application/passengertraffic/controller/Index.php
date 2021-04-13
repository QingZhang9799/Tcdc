<?php


namespace app\passengertraffic\controller;

use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Request;

class Index extends Base
{
    //推荐行程
    public function RecommendedLine()
    {
        $where = [] ;
        if(input('origin') == 'null'){

        }else{
            $where['origin'] = ['eq' ,input('origin') ] ;
        }
        $data = [] ;
        $time = time() ;
        //不显示过期的行程
        $journey = db('journey')->order('RAND()')->where('times','gt',$time)->where($where)->where('status', 'eq', 1)->limit(4)->select();

        $data = $journey ;

        return [
            "code" => APICODE_SUCCESS,
            "msg" => "查询成功",
            "data" => $data
        ];
    }

    //客运查询
    public function QueryPassengerTraffic()
    {
        $params = [
            "origin" => input('?origin') ? input('origin') : null,
            "destination" => input('?destination') ? input('destination') : null,
            "times" => input('?times') ? input('times') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["origin", 'destination', 'times'];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //搜索当天行程
        $start_time = strtotime(input('times'). " 00:00:00");
        $end_time = strtotime(input('times'). " 23:59:59");

        $journey = Db::name('journey')->alias('j')
            ->join('mx_vehicle v', 'v.id = j.vehicle_id', 'left')
            ->field('j.*,v.VehicleNo,v.seating,j.id as journey_id,v.id as v_id,v.Model')
            ->where('j.times', 'gt', $start_time)
            ->where('j.times', 'lt', $end_time)
            ->where('j.status','in',"1,2,9")
            ->order('j.times asc')
            ->select();

        $data = [];
        $time = time() ;
        foreach ($journey as $key => $value) {
            if($value['status'] == 1 || $value['status'] == 2 || $value['status'] == 9){
//                if($time > intval($value['times'])){
//                    unset($journey[$key]) ;
//                }else{
                    $particulars[$key] = json_decode($value['particulars'], true);
                    //返回司机信息
                    $conducteur_id = Db::name('vehicle_binding')->where(['vehicle_id'=>$value['v_id']])->value("conducteur_id") ;
                    $conducteur =Db::name("conducteur")->where(['id'=>$conducteur_id])->find() ;

                    $data = $this->getParticulars($particulars[$key],$data,$value,$conducteur);
//                }
            }
        }

        return [
            "code" => APICODE_SUCCESS,
            "msg" => "查询成功",
            "data" => $data
        ];
    }

    //整合
    public function getParticulars($particulars,$data,$value,$conducteur){
        foreach ($particulars as $k => $v) {
//                echo "-------".$v['destination'];
            //循环只取一个
            if (($v['origin'] == input('origin')) && ($v['destination'] == input('destination'))) {
                //在判断一下是否小于35分钟
                $times = $value['times'];            //发车时间
                $time = time();                        //当前时间
                $minute = 35 * 60;                     //35分钟
                $calculate_time = $times + $minute;
                $type = 0;
                if ($calculate_time > $time) {           //在发车前
                    $type = 0;
                } else {                                 //在发车后
                    $type = 1;
                }
                //计算到达时间
                $start_time = explode(':',$value['start_time']);
                $arrive = (int)$value['times'] +  ((int)$start_time[0] * 60 * 60) + (int)$start_time[1] *60;

                $data[] = [
                    "journey_id" => $value["journey_id"],
                    'origin' => input('origin'),
                    "spot" => $value["spot"],
                    "lineOrigin" => $v["origin"],
                    "lineDestination" => $v["destination"],
                    'destination' => input('destination'),
                    'price' => $v['price'],
                    'anticipate_geton_time' => $value['anticipate_geton_time'],
                    'residue_ticket' => $value['residue_ticket'],
                    'vehicle' => $value['car_type'] . " " . $value['seating'],
                    'origin_longitude' => $v['origin_longitude'],
                    'origin_latitude' => $v['origin_latitude'],
                    'destination_longitude' => $v['destination_longitude'],
                    'destination_latitude' => $v['destination_latitude'],
                    'total_ticket' => $value['total_ticket'],
                    'times' => $value['times'],
                    'arrive' => $arrive,
                    'grandet' => $conducteur['grandet'],
                    'score' => $conducteur['score'],
                    'Model' => $value['Model'],
                    'status' => $value['status'],
                    'conducteur'=>$conducteur,
                    'VehicleNo' => $value['VehicleNo'],
                ];
            }
        }
        return $data;
    }

    //我的车票
    public function MyTicket()
    {
        if (input('?user_id')) {
            $params = [
                "o.user_id" => input('user_id')
            ];
            $journey_order = db('journey_order')->alias('o')
                                                         ->field('o.*,v.VehicleNo,j.times,j.vehicle_id')
                                                         ->join('mx_journey j','o.journey_id = j.id','left')
                                                         ->join('mx_vehicle v','v.id = j.vehicle_id','left')
                                                         ->where($params)
                                                         ->order('o.id desc')
                                                         ->select();
            //乘车人
            foreach ($journey_order as $key => $value) {
                $jorder_passenger = Db::name('jorder_passenger')->where(['user_id' => input('user_id'), 'journey_order_id' => $value['id']])->select();
                $journey_order[$key]['jorder_passenger'] = $jorder_passenger ;
                //司机电话
                $conducteur_id = Db::name('vehicle_binding')->where(['vehicle_id'=>$value['vehicle_id']])->value('conducteur_id');
                $DriverPhone = Db::name('conducteur')->where(['id' => $conducteur_id])->value('DriverPhone');
                $journey_order[$key]['DriverPhone'] = $DriverPhone ;
                //座位数
                $total_ticket = Db::name('journey')->where(['id'=>$value['journey_id']])->value('total_ticket') ;
                $journey_order[$key]['total_ticket'] = $total_ticket ;
            }
            $data = $journey_order;
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //添加乘车人
    public function AddPassenger()
    {
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "name" => input('?name') ? input('name') : null,
//            "number" => input('?number') ? input('number') : null,
            "phone" => input('?phone') ? input('phone') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "name"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        //姓名不重复
        $name = Db::name('user_passenger')->where(['name' => input('name'), 'user_id' => input('user_id')])->select();
        if (!empty($name)) {
            return [
                'code' => APICODE_ERROR,
                'msg' => '姓名已重复'
            ];
        }
//        //身份证不重复
//        $number = Db::name('user_passenger')->where(['number' => input('number'), 'user_id' => input('user_id')])->select();
//        if (!empty($number)) {
//            return [
//                'code' => APICODE_ERROR,
//                'msg' => '身份证已重复'
//            ];
//        }
        $user_passenger = Db::name('user_passenger')->insert($params);

        if ($user_passenger) {
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '创建成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '创建失败'
            ];
        }
    }

    //编辑乘车人
    public function UpdatePassenger()
    {
        $params = [
            "id" => input('?id') ? input('id') : null,
            "user_id" => input('?user_id') ? input('user_id') : null,
            "name" => input('?name') ? input('name') : null,
            "number" => input('?number') ? input('number') : null,
            "phone" => input('?phone') ? input('phone') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "name", "number"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //姓名不重复
        $name = Db::name('user_passenger')->where(['name' => input('name'), 'user_id' => input('user_id')])->where('id', 'neq', input('id'))->select();
        if (!empty($name)) {
            return [
                'code' => APICODE_ERROR,
                'msg' => '姓名已重复'
            ];
        }
        //身份证不重复
        $number = Db::name('user_passenger')->where(['number' => input('number'), 'user_id' => input('user_id')])->where('id', 'neq', input('id'))->select();
        if (!empty($number)) {
            return [
                'code' => APICODE_ERROR,
                'msg' => '身份证已重复'
            ];
        }

        $res = Db::name('user_passenger')->update($params);

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }

    //根据id获取乘车人
    public function GetPassenger()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('user_passenger')->where($params)->find();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "乘车人ID不能为空"
            ];
        }
    }

    //删除乘车人
    public function DelPassenger()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];

            $user_passenger = db('user_passenger')->where(['id' => input('id')])->delete();

            if ($user_passenger > 0) {
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "删除成功",
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "乘车人ID不能为空"
            ];
        }
    }

    //乘车人列表
    public function PassengerList()
    {
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            $data = db('user_passenger')->where($params)->select();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //根据城市获取路线的起点
    public function accordingOriginList()
    {
        if (input('?city_id')) {
            $params = [
                "id" => input('city_id')
            ];

            $data = Db::name('route')->alias('r')
                ->distinct(true)
                ->field('r.origin')
//                ->field('d.origin as origin,d.origin_longitude,d.origin_latitude,l.display,l.is_display')
//                ->join('line_detail d', 'd.line_id = l.id', 'inner')
                ->where('r.state', 'eq', 0)
                ->where(['r.city_id' => input('city_id')])
                ->select();
            $ini = [];
//            halt($data);

            foreach ($data as $key => $value) {
                $lines = Db::name('route')->alias('r')->field('d.origin_longitude,d.origin_latitude,r.display,r.is_display')
                    ->join('route_detail d', 'd.route_id = r.id', 'inner')->where(['r.origin'=>$value['origin']])->find();
                $data[$key]['origin_longitude'] = $lines['origin_longitude'];
                $data[$key]['origin_latitude'] = $lines['origin_latitude'];
                $data[$key]['display'] = $lines['display'];
                if ($lines['is_display'] == 1) {
                    $display = explode(',', $lines['display']);
                    foreach ($display as $k => $v) {
                        $name = explode('-', $v);
                        foreach ($name as $kk => $vv) {
                            if ($kk == 0) {
                                $data[$key]['displays'][] = $name[$kk];
                            }
                        }
                    }
                } else {
                    $data[$key]['displays'] = [];
                }
            }

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "城市ID不能为空"
            ];
        }
    }

    //根据城市获取线路起点获取终点
    public function destinationDestination()
    {
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "origin" => input('?origin') ? input('origin') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "origin"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $data = Db::name('route')->alias('l')
            ->distinct(true)
            ->field('d.destination as destination')
            ->join('route_detail d', 'd.route_id = l.id', 'left')
            ->where('l.state', 'eq', 0)
            ->where('d.origin', 'eq', input('origin'))
            ->select();

        return [
            "code" => APICODE_SUCCESS,
            "msg" => "查询成功",
            "data" => $data
        ];
    }

    //车票信息
    public function TicketInformation()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('journey_order')->where($params)->find();
            $vehicle_id = Db::name('journey')->where(['id'=>$data['journey_id']])->value('vehicle_id');
            //乘车人
            $data['jorder_passenger'] = Db::name('jorder_passenger')->where(['journey_order_id' => input('id')])->select();


            $conducteur_id = Db::name('vehicle_binding')->where(['vehicle_id'=>$vehicle_id])->value('conducteur_id');
            $DriverPhone = Db::name('conducteur')->where(['id' => $conducteur_id])->value('DriverPhone');
            $data['DriverPhone'] = $DriverPhone ;

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //客运包车
    public function AppointmentChartered()
    {
        $params = [
            "user_name" => input('?user_name') ? input('user_name') : null,
            "origin" => input('?origin') ? input('origin') : null,
            "destination" => input('?destination') ? input('destination') : null,
            "Departure_time" => input('?Departure_time') ? input('Departure_time') : null,
            "Contact" => input('?Contact') ? input('Contact') : null,
            "seating" => input('?seating') ? input('seating') : null,
            "count" => input('?count') ? input('count') : null,
            "phone" => input('?phone') ? input('phone') : null,
            "user_id" => input('?user_id') ? input('user_id') : null,
            "money" => input('?money') ? input('money') : null,
            "is_kickback" => input('?is_kickback') ? input('is_kickback') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["origin", "destination"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $chartered = Db::name('chartered')->insert($params);

        if ($chartered) {
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '预约成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '预约失败'
            ];
        }
    }

    //包车-我的订单
    public function CharteredMyOrder()
    {
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            $data = db('chartered')->where($params)->select();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //获取行程订单
    public function GetJourney()
    {
        if (input('?id')) {
            $params = [
                "j.id" => input('id')
            ];
            $data = Db::name('journey')->alias('j')
                ->join('mx_vehicle v', 'v.id = j.vehicle_id', 'left')
                ->field('j.*,v.VehicleNo,v.seating')
                ->where($params)
                ->find();

            //判断起点和终点
            $particulars = json_decode($data['particulars'],true) ;
            foreach ($particulars as $key=>$value){
                if($value['origin'] == input('origin') && $value['destination'] == input('destination')){
                      $data['origin'] = input('origin');
                      $data['destination'] = input('destination');
                      $data['price'] = $value['price'];
                }
            }
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
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

    //取消订单
    public function CancelOrder(){
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id'),
                "status" => 5,
            ];
            $journey_order = Db::name('journey_order')->update($params) ;
            //取消之后，把占用的票，还原回去
            $journey_orders = Db::name("journey_order")->where(['id'=>input('order_id')])->find();
            Db::name('journey')->where(['id'=>$journey_orders['journey_id'] ])->setInc('residue_ticket',$journey_orders['ticket_count']) ;

            if($journey_order > 0){
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
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //退票价格
    public function RefundExplain(){
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id')
            ];
            $data = []  ;

            $indent = Db::name('journey_order')->where('id',input('order_id'))->find();
            //退票按比例退
            $cancel_rules = Db::name('journey')->where(['id' => $indent['journey_id']])->value('cancel_rules');

            $times = Db::name('journey')->where(['id' => $indent['journey_id']])->value('times');

            $status = Db::name('journey')->where(['id' => $indent['journey_id']])->value('status');  //行程状态

            $prices = Db::name('jorder_passenger')->where('id','in',input('jorder_passenger_id'))->sum('price');

            $cancel = json_decode($cancel_rules,true);

            $proportion = $this->check($cancel,$times,$status) ;

            $price = sprintf("%.2f", round($prices - ($prices * ($proportion/100)),2) ) ;

            //返回微信的还是支付宝的
            $is_payment = $indent['is_payment'] ;

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "price" => $price,
                "is_payment" => $is_payment,
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
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

    //车票预订
    public function TicketIosReservation()
    {
        $datas = input('');

        $params = [
            "journey_id" => input('?journey_id') ? input('journey_id') : null,
            "board" => input('?board') ? input('board') : null,
            "user_id" => input('?user_id') ? input('user_id') : null,
            "phone" => input('?phone') ? input('phone')  : null,
            "money" => input('?money') ? input('money')  : null,
            "city_id" => input('?city_id') ? input('city_id')  : null,
            "Vehicle_description" => input('?Vehicle_description') ? input('Vehicle_description')  : null,
            "origin" => input('?origin') ? input('origin')  : null,
            "destination" => input('?destination') ? input('destination')  : null,
            "origin_longitude" => input('?origin_longitude') ? input('origin_longitude')  : null,
            "origin_latitude" => input('?origin_latitude') ? input('origin_latitude')  : null,
            "destination_longitude" => input('?destination_longitude') ? input('destination_longitude')  : null,
            "destination_latitude" => input('?destination_latitude') ? input('destination_latitude')  : null,
        ];

        $order_money = input('money');    //订单金额
        $order_code = "HY" .input('city_id') . '0' . date('YmdHis') . rand(0000, 999);

        //途经点
        $point = '' ;

        if(!empty($datas['point'])){
            foreach (json_decode($datas['point']) as $key=>$value){
                $point .= $value .',' ;
            }
        }
        $point_items = substr($point , 0 , -1 ) ;

        $ini['user_id'] = input('user_id');
        $ini['journey_id'] = input('journey_id');
        $ini['status'] = 1;                                 //待付款
        $ini['origin'] = input('origin');
        $ini['destination'] = input('destination');
        $ini['price'] = input('money');
        $ini['point'] = $point_items ;
        $ini['Vehicle_description'] = input('Vehicle_description');
        $ini['city_id'] = input('city_id');
        $ini['phone'] = input('phone');
        $ini['order_code'] = $order_code;
        $ini['board'] = input('board');
        $ini['debarkation'] = input('debarkation');
        $ini['create_time'] = time() ;

        //经度和纬度
        $ini['origin_longitude'] = input('origin_longitude');
        $ini['origin_latitude'] = input('origin_latitude');
        $ini['destination_longitude'] = input('destination_longitude');
        $ini['destination_latitude'] = input('destination_latitude');
        $ini['is_payment'] = input('is_payment');
        $journey_order_id = Db::name('journey_order')->insertGetId($ini) ;

        //乘车人
        $inii= [] ;
        if(!empty($datas['passenger'])){
            foreach (json_decode($datas['passenger'],true) as $k=>$v){

                $inii[]= [
                    'user_id'=> input('user_id'),
                    'name'=>$v['name'],
                    'number'=>$v['number'],
                    'phone'=>$v['phone'],
                    'is_accepted'=>0,
                    'journey_order_id'=>$journey_order_id,
                    'price'=>input('monovalent')
                ];
            }
            Db::name('jorder_passenger')->insertAll($inii);
            //按照乘车人个数减少票数
            $count = count($inii);
            Db::name('journey')->where(['id' => input('journey_id') ])->setDec('residue_ticket' , $count );
        }

        //支付
        $is_payment = input('is_payment');      //支付方式
        $order_code = Db::name('journey_order')->where(['id' =>$journey_order_id ])->value('order_code');
        if($is_payment == 1){                   //微信支付
            $wxpay = new Wxnewpay();
            $pay = [
                'attach'=>1,
                'money'=>input('money'),
                'ordernum'=>$order_code,
            ];
            $arr =  $wxpay->payment($pay);
            return ['code'=>'200','message'=>'成功','data'=>$arr];
        }else if($is_payment == 2 ){            //支付宝支付
            $iospay = new Userpay();
            $ios = [
                'title'=>'支付宝支付',
                'order_code'=>$order_code,
                'money'=>input('money'),
                'passback_params'=>'1',
            ];
            $cart = new RSACrypt();
            $response =  $iospay->zfbpayment($ios,$cart);
            return $cart->response(['code'=>200,'msg'=>'成功','data'=>$response]) ;
        }
    }

    //按照待付款时间取消订单
    public function ObligationCancelorder(){
        $file = fopen('./traffic.txt', 'a+');
        fwrite($file, "-------------------取消订单时间--------------------"."\r\n");     //司机电话
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            $data = db('journey_order')->where(['status'=>1])->where($params)->select() ;

            $time = time() ;
            foreach ($data as $key=>$value){
                $create_time = $time - ($value['create_time'] + 60*5 );
                if($create_time > 0 ){      //超过了取消时长
                  $ini['id'] = $value['id'] ;
                  $ini['status'] = 5 ;
                  Db::name('journey_order')->update($ini) ;

                  //把车票还原回去
                    Db::name('journey')->where(['id'=>$value['journey_id'] ])->setInc('residue_ticket',$value['ticket_count']) ;
                }
            }

            return [
                "code" => APICODE_SUCCESS ,
                "msg" => "查询成功",
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //判断用户是否有待付款
    public function JudgeUserObligation(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            $data = db('journey_order')->where($params)->where(['status'=>1])->find();
            $flag = 0 ;
            if(!empty($data)){
                $flag = 1;
            }

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "flag" => $flag
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }
}