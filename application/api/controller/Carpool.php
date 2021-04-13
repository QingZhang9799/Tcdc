<?php


namespace app\api\controller;

use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Exception;


class Carpool extends Base
{
    //线路列表
    public function getLineList()
    {
        $data = Db::name('line')->field('id,origin,destination,origin_longitude,origin_latitude,destination_longitude,destination_latitude,spot')
            ->where('state', 'eq', 0)
            ->where(['city_id' => input('city_id')])
            ->select();

        foreach ($data as $key => $value) {
            $data[$key]['price'] = Db::name('line_detail')->where(['line_id' => $value['id'], 'origin' => $value['origin'], 'destination' => $value['destination']])
                ->value('price');
        }
        return ['code' => APICODE_SUCCESS, 'data' => $data];
    }

    //设置路线
    public function SetRoute()
    {
        $data = input('');
        $params = [
            "origin" => input('?origin') ? input('origin') : '',
            "Destination" => input('?Destination') ? input('Destination') : '',
            "spot" => input('?spot') ? input('spot') : '',
            "line_id" => input('?line_id') ? input('line_id') : 0,
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : 0,
            "seating_count" => input('?seating_count') ? input('seating_count') : null,
            "origin_longitude" => input('?origin_longitude') ? input('origin_longitude') : null,
            "origin_latitude" => input('?origin_latitude') ? input('origin_latitude') : null,
            "destination_longitude" => input('?destination_longitude') ? input('destination_longitude') : null,
            "destination_latitude" => input('?destination_latitude') ? input('destination_latitude') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["origin", "Destination", "seating_count"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //创建顺风车订单
        $conducteur = Db::name('conducteur')->where(['id' => input('conducteur_id')])->find();
        //判断一下，司机是否绑定车辆
        $vehicle_binding = Db::name('vehicle_binding')->where(['conducteur_id' => input('conducteur_id')])->find();
        if (empty($vehicle_binding)) {
            return ['code' => APICODE_ERROR, 'msg' => '没有绑定车辆，不能发布行程。'];
        }

        $OrderId = 'SF' . $conducteur['city_id'] . '0' . date('Ymdhis') . rand(0000, 9999);

        $ini['city_id'] = $conducteur['city_id'];
        $ini['OrderId'] = $OrderId;
        $ini['origin'] = input('origin');
        $ini['Destination'] = input('Destination');
        $ini['create_time'] = time();
        $ini['status'] = 12;                               //待出行

        $ini['order_name'] = '预约' . '订单' . "(城际)";

        $ini['seating_A'] = input('seating_A');
        $ini['seating_B'] = input('seating_B');
        $ini['seating_C'] = input('seating_C');
        $ini['seating_D'] = input('seating_D');
        $ini['conducteur_id'] = input('conducteur_id');
        $ini['company_id'] = $conducteur['company_id'];
        $ini['classification'] = "顺风车";
        $ini['conducteur_phone'] = $conducteur['DriverPhone'];
        $ini['conducteur_name'] = $conducteur['DriverName'];
        $ini['seating_count'] = input('seating_count');
        $ini['DepartTime'] = strtotime(input('DepartTime'));
        //经纬度
        $ini['DepLongitude'] = input('origin_longitude');
        $ini['DepLatitude'] = input('origin_latitude');
        $ini['DestLongitude'] = input('destination_longitude');
        $ini['DestLatitude'] = input('destination_latitude');
        $ini['business_id'] = 4;
        $ini['spot'] = input('spot') ;
        $ini['line_id'] = input('line_id') ;

        //拦截顺风车
        $flag = $this->interceptExpress(input('line_id'), strtotime(input('DepartTime')), input('conducteur_id'));
        if ($flag == 1) {
            return ['code' => APICODE_ERROR, 'msg' => '该时段不能发布'];
        }

        $order_id = Db::name('order')->insertGetId($ini);

        $spot = input('spot');

        //订单线路
        $str = ['origin' => input('origin')];

        $line_detail = Db::name('line_detail')->where(['line_id' => input('line_id')])
            ->where('destination', 'in', $spot. "," .input('Destination'))
            ->where('origin', 'in', $spot . "," . input('origin'))
            ->select();

        $line_details = Db::name('line_detail')->where(['line_id' => input('line_id')])
            ->where('destination', 'in', $spot)
            ->where('origin', 'eq', input('origin'))
            ->select();

        $inii = [];

        //合并线路
        foreach ($line_detail as $key => $value) {
            $seats_details = $data['seats_details'];
            $seat_details = [];
            $seats_details_n = json_decode($seats_details);
            foreach ($seats_details_n as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $bl = 0;
                    if ($vv == 0) {
                        $bl = 0;
                    } else {
                        $bl = ($value['price'] * ($vv / 100));
                    }

                    $seat_details[] = [
                        $kk => sprintf("%.2f", $value['price'] + $bl)
                    ];
                }
            }

            $inii[] = [
                'order_id' => $order_id,
                'origin' => $value['origin'],
                'destination' => $value['destination'],
                'origin_longitude' => $value['origin_longitude'],
                'origin_latitude' => $value['origin_latitude'],
                'destination_longitude' => $value['destination_longitude'],
                'destination_latitude' => $value['destination_latitude'],
                'price' => sprintf("%.2f", $value['price']),
                'seat_details' => json_encode($seat_details),
            ];
        }

        Db::name('order_line')->insertAll($inii);

        $ini_n = [];
        foreach ($line_details as $k => $v) {
            $seats_details = $data['seats_details'];
            $seat_details = [];
            $seats_details_n = json_decode($seats_details);
            foreach ($seats_details_n as $kk => $vv) {
                foreach ($vv as $kkk => $vvv) {
                    $bl = 0;
                    if ($vvv == 0) {
                        $bl = 0;
                    } else {
                        $bl = ($v['price'] * ($vvv / 100));
                    }

                    $seat_details[] = [
                        $kkk => $v['price'] + $bl
                    ];
                }
            }

            $ini_n[] = [
                'order_id' => $order_id,
                'origin' => $v['origin'],
                'destination' => $v['destination'],
                'origin_longitude' => $v['origin_longitude'],
                'origin_latitude' => $v['origin_latitude'],
                'destination_longitude' => $v['destination_longitude'],
                'destination_latitude' => $v['destination_latitude'],
                'price' => sprintf("%.2f", $v['price']),
                'seat_details' => json_encode($seat_details),
            ];
        }
        Db::name('order_line')->insertAll($ini_n);
        return ['code' => APICODE_SUCCESS, 'msg' => '创建成功'];
    }

    //查询顺风车订单
    public function expressTrain()
    {
        if (input('?conducteur_id')) {
            $params = [
                "conducteur_id" => input('conducteur_id')
            ];
//            $time = date('Y-m-d',time()) ;
//
//            $start_day =  strtotime($time." 00:00:00");
//            $end_day = strtotime($time." 23:59:59");
            //主订单
            $data = db('order')->where($params)->where(['classification' => '顺风车'])
                ->where('user_id', 'eq', 0)->where('status', 'in', '12,4')
                ->select();

            foreach ($data as $key => $value) {
                $Ridership = 0;    // 乘客数

                $orders = Db::name('order')->field('Ridership')->where('status', 'in', '7,12,3,4,9')->where(['order_id' => $value['id']])->select();
                foreach ($orders as $k => $v) {
                    $Ridership += $v['Ridership'];
                }
                $data[$key]['Ridership'] = $Ridership;
            }

            if (empty($data)) {
                $data = [];
            }

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

    //顺风车订单信息
    public function CarpoolInformation()
    {
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id')
            ];

            $data = db('order')->where($params)->find();

            //获取用户数据组
            $user_order = Db::name('order')->where(['order_id' => input('order_id')])->where('status', 'neq', 5)->select();
            foreach ($user_order as $key => $value) {
                $user_order[$key]['star'] = Db::name('user')->where(['id' => $value['user_id']])->value('star');
                $user_order[$key]['portrait'] = Db::name('user')->where(['id' => $value['user_id']])->value('portrait');
            }

            $data['user_order'] = $user_order;

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //开始行程
    public function BeganTravel()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id", "conducteur_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //查询之前司机的顺风车，是否已完成的状态
        $order = Db::name('order')->where('id', 'neq', input('order_id'))->where('user_id', 'eq', 0)->where(['conducteur_id' => input('conducteur_id')])->where(['classification' => '顺风车'])->where('status', 'not in', '5,12,9')->select();

        if (!empty($order)) {
            return [
                'code' => APICODE_NOPOWER,
                'msg' => '您有未结束顺风车订单，请结束才能开始新的行程'
            ];
        }

        $ini['id'] = input('order_id');
        $ini['status'] = 4;
        $res = Db::name('order')->update($ini);
        $params["arrive_time"] = time() * 1000;
        unset($params['conducteur_id']);
        db('order_history')->insert($params);
        //在把子单都变成3
        $orders = Db::name('order')->where(['order_id' => input('order_id')])->select();
        foreach ($orders as $key => $value) {
            if ($value['status'] == 12) {         //只有满足12，才把状态变成3
                $inii['id'] = $value['id'];
                $inii['status'] = 3;
                Db::name('order')->update($inii);
            }
        }
        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "开始成功",
        ];
    }

    //结束行程
    public function FinishTrip()
    {
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id'),
                "status" => 9
            ];
            $res = 0;
            if (input('force') == 0) {
                //判断所有用户是否都已结束
                $order = Db::name('order')->where('status', 'not in', "9,5")->where(['order_id' => input('order_id')])->select();
                if (!empty($order)) {
                    return [
                        'code' => APICODE_ERROR,
                        'msg' => '已有乘客未点击已完成'
                    ];
                }
                $res = Db::name('order')->update($params);
            } else if (input('force') == 1) {
                $params['force'] = input('force');
                $res = Db::name('order')->update($params);
                //下面所有子订单，全部结束
                $orders = Db::name('order')->where(['order_id' => input('order_id')])->select();
                foreach ($orders as $key => $value) {
                    if($value['status'] == 7 || $value['status'] == 5){
                        $ini['id'] = $value['id'];
                        $ini['status'] = 5;
                        Db::name('order')->update($ini);
                    }else{
                        $ini['id'] = $value['id'];
                        $ini['status'] = 9;
                        Db::name('order')->update($ini);
                    }
                }
            }

            if ($res > 0) {

                //结算司机钱
                $money = 0;
                $file = fopen('./log.txt', 'a+');
                //获取子订单的所有订单
                $order_child = Db::name('order')->where(['order_id' => input('order_id')])->where(['status' => 9])->select();
                fwrite($file, "-------------------order_child：--------------------" . json_encode($order_child) . "\r\n");
                foreach ($order_child as $k => $v) {
                    if ($v['pay_time'] > 0) {
                        $money += $v['money'];
                        $this->companyMoney($v['conducteur_id'], $v['money'], $v, $v['discounts_money']);                                 //给司机分钱
                    }
//                    $this->conducteurBoard($v['conducteur_id'],$v['money'],$v['id']);
                }

                fwrite($file, "-------------------money：--------------------" . $money . "\r\n");
                //更新主单金额
                $inii['id'] = input('order_id');
                $inii['money'] = $money;
                $inii['end_time'] = time();
                try {
                    $order = Db::name('order')->alias('o')
                        ->field('c.key,c.service,c.terimnal,c.trace,o.company_id,o.business_id,o.business_type_id,h.arrive_time,o.end_time,o.gps_number')
                        ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
                        ->join('mx_order_history h', 'h.order_id = o.id', 'left')
                        ->where(['o.id' => input('order_id')])
                        ->find();
                    $start_time = $order['arrive_time'];       // 订单开始时间
                    $end_time = $order['end_time'];            // 订单结束时间
                    if (strlen($start_time) == 10) {
                        $start_time = $start_time * 1000;
                    }
                    if (strlen($end_time) == 10) {
                        $end_time = $end_time * 1000;
                    }
                    if ($order["gps_number"]) {
                        $locus = $this->gjByGps($order["gps_number"], $start_time, $end_time);
                        $tracks = json_encode($locus['tracks']);
                        $inii['tracks'] = $tracks;
                    }
                } catch (Exception $e) {

                }

                Db::name('order')->update($inii);
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "结束成功",
                ];
            } else {
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "结束失败",
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    public function gjByGps($carId, $startTime, $endTime)
    {
        if (strlen($startTime) == 10) {
            $startTime = $startTime * 1000;
        }
        if (strlen($endTime) == 10) {
            $endTime = $endTime * 1000;
        }
//        $gps = new Gps();
        $data = $this->queryHistory($carId, $startTime, $endTime);
        $sumMile = 0;
        $sumTime = $endTime - $startTime;
        $startPoint = array();
        $endPoint = array();
        $points = [];
        if (count($data) > 0) {
            $sumMile = $data[count($data) - 1]["mileage"];
            foreach ($data as $index => $item) {
                if ($index == 0) {
                    $startPoint = array("location" => $item["lonc"] . "," . $item["latc"]);
                } else if ($index == count($data) - 1) {
                    $endPoint = array("location" => $item["lonc"] . "," . $item["latc"]);
                }
                array_push($points, array("location" => $item["lonc"] . "," . $item["latc"]));
            }
        }
        $res = array("tracks" => [array("distance" => $sumMile, "time" => $sumTime, "endPoint" => $endPoint, "startPoint" => $startPoint, "points" => $points)]);
        return $res;
    }

    public function getAdminToken()
    {
        $data = $this->request_post("http://www.gpsnow.net/user/login.do", array("name" => "tcdc", "password" => "123456"));
        $data = json_decode($data, true);
        $token = $data["data"]["token"];
        return $token;
    }

    public function getCarsStatus($carIds)
    {
        $data = $this->request_post("http://www.gpsnow.net/carStatus/getByCarIds.do", array("token" => $this->getAdminToken(), "carIds" => $carIds, "mapType" => 2));
        $data = json_decode($data, true);
        $data = $data["data"];
        return ['code' => APICODE_SUCCESS, 'data' => $data];
    }

    public function queryHistory($carId, $startTime, $endTime)
    {
        $startTime = mb_substr($startTime, 0, 10); //strtotime("2019/01/29 01:00:02");
        $endTime = mb_substr($endTime, 0, 10);; //strtotime("2019/01/29 23:00:02");
        $startTime = gmdate("Y-m-d H:i:s", $startTime);
        $endTime = gmdate("Y-m-d H:i:s", $endTime);
        $token = $this->getAdminToken();
        $data = $this->request_post("http://www.gpsnow.net/position/queryHistory.do", array("carId" => $carId, "mapType" => "2", "startTime" => $startTime, "endTime" => $endTime, "token" => $token, "filter" => true));
        $data = json_decode($data, true);
        $res = $data["data"];
        return $res;
    }

    private function request_post($url = '', $post_data = array())
    {
        if (empty($url) || empty($post_data)) {
            return false;
        }
        $o = "";
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&";
        }
        $post_data = substr($o, 0, -1);
        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
    }

    //司机取消
    public function conducteurCancel()
    {
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id'),
                "status" => 5,
            ];

            //判断一下，子订单是否都是已取消状态
            $orders = Db::name('order')->where(['order_id' => input('order_id')])->where('status', 'neq', 5)->select();
            if (!empty($orders)) {
                return ['code' => APICODE_ERROR, 'msg' => '用户有未取消的订单'];
            }
            $data = Db::name('order')->update($params);

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //司机取消用户订单（待支付订单）
    public function conducteurUserOrder()
    {
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id'),
                "status" => 5
            ];

            $status = Db::name('order')->where(['id' => input('order_id')])->value('status');
            if ($status == 9) {
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "订单已完成，不能取消"
                ];
            }

            //将用户的余额增加
            $order = Db::name('order')->where(['id' => input('order_id')])->find();

            //如果payer_fut为0
            if($order['payer_fut'] == 0 ){          //个人支付
                if ( ($order['status'] != 7 ) && ($order['status'] != 5 )) {  //不是待付款订单
                    $money = 0 ;
                    //判断一下，支付方式
                    if($order['third_party_type'] == 0){// 微信
                        $money = $order['actual_amount_money'] ;
                        //补一个微信充值订单
                        $ini_w['ordernum'] = $ordernum = 'CZ' . "00000" . date('Ymdhis');
                        $ini_w['money'] = $money;
                        $ini_w['user_id'] = $order['user_id'];
                        $ini_w['state'] = 1;
                        $ini_w['is_payment'] = 0;
                        $ini_w['create_time'] = time();
                        $ini_w['pay_time'] = time();
                        $ini_w['transaction_id'] = $order['transaction_id'];

//                        Db::name('recharge_order')->insert($ini_w);
                    }else if($order['third_party_type'] == 1){//支付宝
                        $money = $order['actual_amount_money'] ;
                        //补一个支付宝充值订单
                        $ini_z['ordernum'] = $ordernum = 'CZ' . "00000" . date('Ymdhis');
                        $ini_z['money'] = $money;
                        $ini_z['user_id'] = $order['user_id'];
                        $ini_z['state'] = 1;
                        $ini_z['is_payment'] = 1;
                        $ini_z['create_time'] = time();
                        $ini_z['pay_time'] = time();
                        $ini_z['transaction_id'] = $order['transaction_id'];

//                        Db::name('recharge_order')->insert($ini_z);
                    }else if($order['third_party_type'] == 2){//余额支付
                        $money = $order['balance_payment_money'] ;
                    }
                    Db::name('user')->where(['id' => $order['user_id']])->setInc('balance', $money);

                    //是否使用优惠券
                    if($order['discounts_manner'] == 1){
                        $inii['id'] = $order['discounts_details'] ;
                        $inii['is_use'] = 0 ;
                        Db::name('user_coupon')->update($inii);
                    }

                    $users = Db::name('user')->where(['id' => $order['user_id']])->find();
                    //保存余额变动记录
                    $ini['user_id'] = $order['user_id'];
                    $ini['money'] = $order['money'];
                    $ini['type'] = 5;
                    $ini['user_name'] = $users['PassengerName'];
                    $ini['phone'] = $users['PassengerPhone'];
                    $ini['create_time'] = time();
                    $ini['symbol'] = 1;
                    Db::name('user_balance')->insert($ini);
                }
            }else{                                   //企业支付
                if ( ($order['status'] != 7 ) && ($order['status'] != 5 )) {  //不是待付款订单
                    //直接给企业加余额
                    Db::name('enterprise')->where(['id'=>$order['enterprise_id']])->setInc('balance',$order['money']) ;
                }
            }

            $data = Db::name('order')->update($params);
            if($data > 0){
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "成功",
                ];
            }else {
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "失败",
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //抽成
    function companyMoney($conducteur_id, $money, $order, $discount_money)
    {

        $company_id = Db::name("conducteur")->where(['id' => $conducteur_id])->value('company_id');  //公司id

        //在获取抽成规则
        $company_ratio = Db::name('company_ratio')->where(['company_id' => $company_id, 'business_id' => $order['business_id']])
            ->where('businesstype_id', 'eq', $order['businesstype_id'])->find();

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
        $inii['id'] = $order['id'];
        $inii['parent_company_money'] = $parent_company;
        $inii['superior_company_money'] = $superior_company;
        $inii['filiale_company_money'] = $compamy_profit;
        $inii['chauffeur_income_money'] = $chauffeur_money;
        $inii['filiale_company_settlement'] = $compamy_money;

        Db::name('order')->update($inii);

        //司机加余额
        Db::name('conducteur')->where(['id' => $conducteur_id])->setInc('balance', $chauffeur_money);

        $this->conducteurBoard($conducteur_id, $chauffeur_money, $order['id']);
    }

    //拦截顺风车
   public function interceptExpress($line_id, $DepartTime, $conducteur_id)
    {
        $flag = 0;
        $date = date('Y-m-d', $DepartTime);
        //获取线路车程
        $start_time = Db::name('line')->where(['id' => $line_id])->value('start_time');
        //转换成秒
        $start = explode(':', $start_time);
        $hour = (int)$start[0] * 60 * 60;              //小时
        $minute = (int)$start[1] * 60;              //分钟
        $sum = $hour + $minute;                     //间隔时间

        //获取顺风车司机之前发布订单 - 还没有开始行程
        $order = Db::name('order')->where(['conducteur_id' => $conducteur_id])->where(['classification' => '顺风车'])->where('status', 'eq', 12)->where('user_id', 'eq', 0)->select();

        foreach ($order as $key => $value) {
            //匹配时间(天)
            $times = date('Y-m-d', $value['DepartTime']);
            if ($times == $date) {
                $departTime = $value['DepartTime'] + $sum;            //小时之后
                $departTime_front = $value['DepartTime'] - $sum;            //小时之前
                if ($departTime > $DepartTime ) {  //在创建时间之后
                    $flag = 1;
                }
                if ($departTime_front > $DepartTime) {
                    $flag = 1;
                }
            }
        }
        return $flag;
    }

    //司机流水
    function conducteurBoard($conducteur_id, $money, $order_id)
    {
        $inic['conducteur_id'] = $conducteur_id;
        $inic['title'] = '接单';
        $inic['describe'] = "";
        $inic['order_id'] = $order_id;
        $inic['money'] = $money;
        $inic['symbol'] = 1;
        $inic['create_time'] = time();

        Db::name('conducteur_board')->insert($inic);
    }

    //扫码-半票 (起点)
    public function EwmOrigin(){
        if (input('?order_id')) {
            $params = [
                "order_id" => input('order_id')
            ];
            $data = [] ;
            $order_line = Db::name('order_line')->distinct(true)->field('origin')->where($params)->select();
            foreach ($order_line as $key=>$value){
                $data[] = [
                    'origin' =>$value['origin']
                ];
            }
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //扫码-半票 (终点)
    public function EwmDestination(){
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "origin" => input('?origin') ? input('origin') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id", "origin"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $order_line = Db::name('order_line')->where(['order_id' => input('order_id') ])->where(['origin' =>input('origin') ])->select() ;
        $ini = [] ;
        foreach ($order_line as $key=>$value){
            $ini[] = [
                'destination'  =>  $value['destination'] ,
                'price'  =>  $value['price'] ,
                'order_id'  =>  input('order_id') ,
                'order_line_id'  =>  $value['id']
            ];
        }
        return [
            "code" => APICODE_SUCCESS,
            "msg" => "查询成功",
            "data" => $ini
        ];
    }

    //扫码-全票
    public function EwmFullFare(){
        if (input('?order_id')) {
            $params = [
                "o.id" => input('order_id')
            ];

            $data = db('order')->alias('o')
                                       ->field('o.*,v.VehicleNo')
                                       ->join('mx_conducteur c','c.id = o.conducteur_id','left')
                                       ->join('mx_vehicle v','v.id = c.vehicle_id','left')
                                       ->where($params)
                                       ->find();

            $order_line = Db::name('order_line')->where(['order_id' => $data['id'] ])
                                           ->where(['origin' => $data['origin'] ])
                                           ->where(['destination' => $data['Destination'] ])->find() ;

            $data['order_line_id'] = $order_line['id'] ;
            $data['money'] = $order_line['price'] ;

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }
}
