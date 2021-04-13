<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 19-2-26
 * Time: 上午10:53
 */

namespace app\backstage\controller;

use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;


class Order extends Base
{
    //订单列表
    public function order_list()
    {
        $params = [
            "o.city_id" => input('?city_id') ? input('city_id') : null,
//            "o.payer_time" => input('?payer_time') ? input('payer_time') : null,
            "o.payer_fut" => input('?payer_fut') ? input('payer_fut') : null,
//            "o.conducteur_name" => input('?conducteur_name') ? input('conducteur_name') : null,
            "o.conducteur_phone" => input('?conducteur_phone') ? input('conducteur_phone') : null,
//            "o.status" => input('?status') ?["in", input('status')] : null,
            "o.user_name" => input('?user_name') ? input('user_name') : null,
            "o.user_id" => input('?user_id') ? input('user_id') : null,
            "o.user_phone" => input('?user_phone') ? input('user_phone') : null,
            "o.third_party_type" => input('?third_party_type') ? input('third_party_type') : null,
        ];

        if (input('third_party_type') == null || input('third_party_type') == "null" || input('third_party_type') == "-1") {
            unset($params['o.third_party_type']);
        } else {
            $where2['o.third_party_type'] = ['eq', input('third_party_type')];
        }
        if ($params['o.city_id'] == null || $params['o.city_id'] == "null" || $params['o.city_id'] == 0) {
            unset($params['o.city_id']);
        }
        $where2 = [];
        $where3 = [];
        if (input('payer_time') == null || input('payer_time') == "null") {
            unset($params['o.payer_time']);
        } else {
            $payer_time =explode(",",input('payer_time')) ;
            $where2['o.pay_time'] = ['egt', strtotime($payer_time[0]. " 00:00:00")];
            $where3['o.pay_time'] = ['elt', strtotime($payer_time[1] . " 23:59:59")];
        }
        $where4 = [];
        $where5 = [];
        if (input('end_time') == null || input('end_time') == "null") {
            unset($params['o.end_time']);
        } else {
            $end_time = explode("," , input('end_time') ) ;
            $where4['o.end_time'] = ['gt', strtotime($end_time[0] . " 00:00:00")];
            $where5['o.end_time'] = ['lt', strtotime($end_time[1] . " 23:59:59")];
        }
        if (input('order_code') == null || input('order_code') == "null") {
            unset($params['o.OrderId']);
        } else {
            $params['o.OrderId'] = ['like', '%' . input('order_code') . '%'];
        }
        if (input('surcharge') == null || input('surcharge') == "null") {
            unset($params['o.surcharge']);
        } else {
            $params['o.surcharge'] = ['egt', input('surcharge') ];
        }
        if ($params['o.payer_fut'] == null || $params['o.payer_fut'] == "null" || $params['o.payer_fut'] == 0) {
            unset($params['o.payer_fut']);
        }
        if (input('conducteur_name') == null || input('conducteur_name') == "null") {
            unset($params['o.conducteur_name']);
        }else{
            $params['o.conducteur_name'] = ['like',"%".input('conducteur_name')."%"] ;
        }
        if ($params['o.conducteur_phone'] == null || $params['o.conducteur_phone'] == "null") {
            unset($params['o.conducteur_phone']);
        }
        if (!empty(input('name'))) {
            if (input('name') != "null") {
                $params['o.user_name'] = ['like', '%' . input('name') . '%'];
            }
        }
        if (input('status') == null || input('status') == "null" || input('status') == "" || input('status') == '' || input('status') == '0') {
            unset($params['o.status']);
        } else {
            $params['o.status'] = ["in", input('status')];
        }
        if ($params['o.user_name'] == null || $params['o.user_name'] == "null") {
            unset($params['o.user_name']);
        }
        if ($params['o.user_phone'] == null || $params['o.user_phone'] == "null") {
            unset($params['o.user_phone']);
        }
        if (input('OrderId') == null || input('OrderId') == "null") {
            unset($params['o.OrderId']);
        } else {
            $params['o.OrderId'] = ['like', '%' . input('OrderId') . '%'];
        }
        $where = [];
        $where1 = [];
        if (input('create_time') == null || input('create_time') == "null") {
            unset($params['o.create_time']);
        } else {
            $create_time = explode("," , input('create_time') ) ;
            $where['o.create_time'] = ['gt', strtotime($create_time[0] . " 00:00:00")];
            $where1['o.create_time'] = ['lt', strtotime($create_time[1] . " 23:59:59")];
        }
        $where6 = [] ;
        if(input('type_service') == null || input('type_service') == "null"){
            unset($params['type_service']) ;
        }else{
            $where6['o.business_id'] = ['in' , input('type_service') ] ;
        }
        $where7= [] ;
        if(input('origin') == null || input('origin') == "null"){
            unset($params['origin']) ;
        }else{
            $params['o.origin'] = ['like', '%' . input('origin') . '%'];
        }
        if(input('Destination') == null || input('Destination') == "null"){
            unset($params['Destination']) ;
        }else{
            $params['o.Destination'] = ['like', '%' . input('Destination') . '%'];
        }
        if(input('classification') == null || input('classification') == "null"|| input('classification') == "不限"){
            unset($params['classification']) ;
        }else{
            $params['o.classification'] = ['eq',input('classification')];
        }
        if(input('businesstype_id') == null || input('businesstype_id') == "null"){
            unset($params['businesstype_id']) ;
        }else{
            $params['o.business_type_id'] = ['eq',input('businesstype_id')];
        }
        if(input('mtorderid') == null || input('mtorderid') == "null"){
            unset($params['mtorderid']) ;
        }else{
            $params['o.mtorderid'] = ['like', '%' . input('mtorderid') . '%'];
        }
        if(input('id') == null || input('id') == "null"){
            unset($params['id']) ;
        }else{
            $params['o.id'] = ['eq',intval(input('id'))];
        }
        if(input('company_id') == null || input('company_id') == "null"|| input('company_id') == 0){
            unset($params['company_id']) ;
        }else{
            $params['o.company_id'] = ['eq',input('company_id')];
        }
        if(input('reason') == null || input('id') == "null"){
            unset($params['reason']) ;
        }else{
            $params['o.reason'] = ['in',input('reason')];
        }
        if(input('is_cancel') == null || input('is_cancel') == "null"){
            unset($params['reason']) ;
        }else{
            $params['o.is_cancel'] = ['eq',input('is_cancel')];
        }
        $where8 = [] ;
        if( input('is_sendOrder') == null || input('is_sendOrder') == "-1" ){
            unset($params['is_sendOrder']) ;
        }else if(input('is_sendOrder') == "1"){  //已派
            $where8['conducteur_phone'] = ['gt' , 0 ] ;
        }
//        halt($params);
        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;

        $sum = db('order')->alias('o')
            ->field('o.id,o.OrderId,o.DepartTime,o.Destination,o.city_id,o.create_time,o.classification,o.payer_time,o.payer_fut
            ,o.conducteur_phone,o.conducteur_name,o.conducteur_id,o.status,o.user_id,o.user_phone,o.user_name,o.origin,o.money
            ,o.pay_time,o.business_type_id,o.business_id,o.discounts_money,o.actual_amount_money,o.balance_payment_money,o.third_party_money
            ,o.third_party_type,o.parent_company_money,o.end_time,o.discounts_manner,o.estimated_mileage,o.reason
            ,o.total_price,o.rates,o.mtorderid,b.business_name,v.Model,y.CompanyName,o.is_type,o.gps_number')
            ->where($where)->where($where1)->where($where2)->where($where3)
            ->where($where4)->where($where5)->where($where6)->where($where7)->where($where8)
            ->where(self::filterFilter($params))->count();

        $order = db('order')->alias('o')
            ->field('o.id,o.OrderId,o.DepartTime,o.Destination,o.city_id,o.create_time,o.classification,o.payer_time,o.payer_fut
            ,o.conducteur_phone,o.conducteur_name,o.conducteur_id,o.status,o.user_id,o.user_phone,o.user_name,o.origin,o.money
            ,o.pay_time,o.business_type_id,o.business_id,o.discounts_money,o.actual_amount_money,o.balance_payment_money,o.third_party_money
            ,o.third_party_type,o.parent_company_money,o.end_time,o.discounts_manner,o.estimated_mileage,o.total_price,o.rates,b.business_name
            ,o.is_cancel,v.Model,y.CompanyName,o.tracks,o.DepLatitude,o.DepLongitude,o.origin,o.DestLatitude,o.DestLongitude,o.Destination,o.order_id
            ,o.fare,o.surcharge,o.superior_company_money,o.filiale_company_money,o.chauffeur_income_money,o.mtorderid,t.title as t_title,o.reason,o.is_type,o.gps_number')
            ->join('mx_business b', 'b.id = o.business_id', 'left')
            ->join('mx_business_type t', 'T.id = o.business_type_id', 'left')
            ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
            ->join('mx_vehicle_binding vb', 'vb.conducteur_id = c.id', 'left')
            ->join('mx_vehicle v', 'v.id = vb.vehicle_id', 'left')
            ->join('mx_company y', 'y.id = o.city_id', 'left')
            ->where(self::filterFilter($params))
            ->where($where)
            ->where($where1)
            ->where($where2)
            ->where($where3)
            ->where($where4)
            ->where($where5)
            ->where($where6)
            ->where($where7)
            ->where($where8)
            ->order('id desc')
            ->page($pageNum, $pageSize)
            ->select();

        foreach ($order as $key => $value) {
            $total_price = json_decode($value['total_price'], true);
            $order[$key]['Mileage'] = $total_price['Mileage'];
            if(empty($value['gps_number'])){
                $order[$key]['points'] = $this->points($value['id']) ;
            }else{
                $order[$key]['points'] = null ;
            }
        }

        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" => $order
        ];
    }

    public function points($order_id){
        $order = Db::name('order')->alias('o')
            ->field('c.key,c.service,c.terimnal,c.trace,o.company_id,o.business_id,o.business_type_id,h.arrive_time,o.end_time,o.gps_number,o.tracks')
            ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
            ->join('mx_order_history h', 'h.order_id = o.id', 'left')
            ->where(['o.id' => $order_id])
            ->find();
        $start_time = $order['arrive_time'];       // 订单开始时间
        $end_time = $order['end_time'];            // 订单结束时间
        if (strlen($start_time) == 10) {
            $start_time = $start_time * 1000;
        }
        if (strlen($end_time) == 10) {
            $end_time = $end_time * 1000;
        }
        $points = [] ;
        $tracks_o = json_decode( $order['tracks'] , true )  ;
        $tracks_oo = $tracks_o['data']['tracks'] ;
        $pointsss = $tracks_oo[0]['points'] ;

        $page = 1 ;
        for ($page=1;$page<5;$page++){
            $locus = $this->gj($order['key'], $order['service'], $order['terimnal'], $order['trace'], (int)$start_time , $end_time , $page);
            $tracks = $locus['data']['tracks'] ;
            $pointss = $tracks[0]['points'] ;
//           halt($pointss) ;
            foreach ($pointss as $key=>$value){
                $points[]= [
                    'location'=>$value['location']
                ];
            }
        }
        return $points;
    }

    //测试订单
    public function test(){
      $order =  Db::name('order')->where(['id' => 783393])->find() ;
        $order = Db::name('order')->alias('o')
            ->field('c.key,c.service,c.terimnal,c.trace,o.company_id,o.business_id,o.business_type_id,h.arrive_time,o.end_time,o.gps_number,o.tracks')
            ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
            ->join('mx_order_history h', 'h.order_id = o.id', 'left')
            ->where(['o.id' => 783393])
            ->find();
        $start_time = $order['arrive_time'];       // 订单开始时间
        $end_time = $order['end_time'];            // 订单结束时间
        if (strlen($start_time) == 10) {
            $start_time = $start_time * 1000;
        }
        if (strlen($end_time) == 10) {
            $end_time = $end_time * 1000;
        }
        $points = [] ;
        $tracks_o = json_decode( $order['tracks'] , true )  ;
        $tracks_oo = $tracks_o['data']['tracks'] ;
        $pointsss = $tracks_oo[0]['points'] ;

        $page = 1 ;
        for ($page=1;$page<5;$page++){
           $locus = $this->gj($order['key'], $order['service'], $order['terimnal'], $order['trace'], (int)$start_time , $end_time , $page);
           $tracks = $locus['data']['tracks'] ;
           $pointss = $tracks[0]['points'] ;
//           halt($pointss) ;
           foreach ($pointss as $key=>$value){
               $points[]= [
                   'location'=>$value['location']
               ];
           }
        }
        halt($points) ;
    }

    //查询轨迹
    public function gj($key, $sid, $tid, $trid, $start_time, $end_time,$page)
    {
        if (strlen($start_time) == 10) {
            $start_time = $start_time * 1000;
        }
        if (strlen($end_time) == 10) {
            $end_time = $end_time * 1000;
        }
        $url = "https://tsapi.amap.com/v1/track/terminal/trsearch";
        $url .= "?key=" . $key;
        $url .= "&sid=" . $sid;
        $url .= "&tid=" . $tid;
        $url .= "&trid=" . $trid;
        $url .= "&starttime=" . $start_time;
        $url .= "&endtime=" . $end_time;
//        $url .= "&correction=denoise%3d1%2cmapmatch%3d1%2cattribute%3d1%2cthreshold%3d0%2cmode%3ddriving";
        $url .= "&recoup=1";
        $url .= "&gap=50";
        $url .= "&ispoints=1";
        $url .= "&page=".$page;
        $url .= "&pagesize=999";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($data);
        $arr = $this->object_array($result);
        return $arr;
    }

    //对象转数组
    public function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }

    //分页
    public function processing_data($process){
        $tracks = json_decode($process ,true)  ;
    }

    //根据订单id获取订单详情
    public function getOrderDetails()
    {

        if (input('?id')) {
            $params = [
                "o.id" => input('id')
            ];
            $data = db('order o') ->field('o.*,h.arrive_time')
                ->join("mx_order_history h", "h.order_id=o.id", "left")->where($params)->find();

            $data['user'] = Db::name('user')->where(['id' => $data['user_id']])->find();

            $data['company'] = Db::name('company')->alias('c')->join('mx_manager m', 'm.id = c.super_id', 'left')
                ->join('mx_company y', 'y.id = c.superior_company', 'left')
                ->field('c.*,y.CompanyName as y_CompanyName,m.username as m_username')
                ->where(['c.id' => $data['company_id']])->find();

            $subSql = db("order")->field("conducteur_id,count(*) as count,sum(money) as sumMoney")->group("conducteur_id")->buildSql();//完成订单数及总额

            $data['conducteur'] = Db::name('conducteur')->alias('c')
                ->field("c.*,v.VehicleNo,o.*")
                ->join("vehicle_binding vb", "c.id=vb.conducteur_id", "left")
                ->join("vehicle v", "vb.vehicle_id=v.id", "left")
                ->join([$subSql => "o"], "o.conducteur_id=c.id", "left")
                ->where(['c.id' => $data['conducteur_id']])
                ->find();

            $data['vehicles'] = Db::name('vehicle')->alias('v')
                ->join('mx_vehicle_binding b', 'b.vehicle_id = v.id', 'left')
                ->where(['b.conducteur_id' => $data['conducteur_id']])
                ->find();

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

    //订单首页
    public function order_home()
    {
        $where = [];
        $where1 = [];
        $where2 = [];
        $where3 = [];
        $where4 = [];

        if (input('company_id') != 0) {           //分公司首页
            $where['u.city_id'] = ['eq', input('city_id')];
            $where1['c.city_id'] = ['eq', input('city_id')];
            $where2['company_id'] = ['eq', input('company_id')];
            $where3['o.city_id'] = ['eq', input('city_id')];
            $where4['city_id'] = ['eq', input('city_id')];
        }
        //今日订单总量
        $todayStart = strtotime(date('Y-m-d 00:00:00', time()));
        $todayEnd = strtotime(date('Y-m-d 23:59:59', time()));

        $order_count = Db::name('order')->alias('o')
            ->where('o.create_time', 'egt', $todayStart)
            ->where('o.create_time', 'elt', $todayEnd)
//            ->where('status', 'in', '6,9')
            ->where($where3)
            ->count();

        $data['order_count'] = $order_count;

        //今日订单总流水
        $order_money = Db::name('order')->alias('o')
            ->where('o.create_time', 'egt', $todayStart)
            ->where('o.create_time', 'elt', $todayEnd)
//            ->where('status', 'in', '6,9')
            ->where($where3)
            ->sum('money');

        $data['order_money'] = $order_money;

        //订单类别占比
        $order = Db::name('order')->alias('o')
            ->field('b.business_name,count(*) as count')
            ->join('mx_business b', 'b.id = o.business_id', 'left')
            ->where('status', 'in', '6,9')
            ->where($where3)
            ->group('o.business_id')
            ->select();
        $data['order'] = $order;

        //各城市订单成交走势图
        $order_time = input('order_time');
        $flow_time = input('flow_time');

        $Interval_o = 0; //订单的间隔天数
        $Interval_u = 0; //订单流水的间隔天数

        //日期为null   本周
        $times = aweek("", 1);
        $beginThisweek = strtotime($times[0]);
        $endThisweek = strtotime($times[1]);

        if ($order_time == "null" ||$order_time == "null " ) {
            $start_o = $beginThisweek;
            $end_o = $endThisweek;

            $Interval_o = diffBetweenTwoDays((int)$start_o, (int)$end_o);
        } else {
            $order_times = explode(',', $order_time);
            $start_o = strtotime($order_times[0]);
            $end_o = strtotime($order_times[1]);

            $Interval_o = diffBetweenTwoDays((int)$start_o, (int)$end_o);
        }

        if ($flow_time == "null"||$flow_time == "null ") {
            $start = $beginThisweek;
            $end = $endThisweek;

            $Interval_u = diffBetweenTwoDays((int)$start, (int)$end);
        } else {
            $order_times = explode(',', $flow_time);
            $start = strtotime($order_times[0]);
            $end = strtotime($order_times[1]);

            $Interval_u = diffBetweenTwoDays((int)$start, (int)$end);
        }

        //各城市订单走势图
        $city_name = [] ;
        if(input('city_id') == 0){ //总公司查看订单前三名的城市
            $city_name = Db::name('order')->alias('o')
                ->distinct(true)
                ->field('c.name as c_name,count(o.id) as count')
                ->join('mx_cn_city c', 'c.id = o.city_id', 'inner')
                ->where($where3)
                ->group('o.city_id')
                ->order('count desc')
                ->limit(3)
                ->select();
        }else{           //按照分公司
            $city_name = Db::name('order')->alias('o')
                ->distinct(true)
                ->field('c.name as c_name,count(o.id) as count')
                ->join('mx_cn_city c', 'c.id = o.city_id', 'inner')
                ->where($where3)
                ->group('o.city_id')
                ->limit(3)
                ->select();
        }

        $order = [];
        for ($y = 0; $y <= $Interval_o; $y++) {     //行

            $op_o = date('Y-m-d', $start_o);

            $day_start_o = strtotime($op_o . ' 00:00:00');  //当天开始时间
            $day_end_o = strtotime($op_o . ' 23:59:59');    //当天结束时间
            $ini = date("Y-m-d", strtotime("+" . $y . " day", strtotime($op_o)));
            $times = date('m', strtotime($ini)) . '-' . date('d', strtotime($ini));

            $days = $this->days(date('m', $start_o));
//            if((((int)date('d', $start_o)) + $y) <= $days) {
            $order[] = [
                'times' => date('m', strtotime($ini)) . '/' . date('d', strtotime($ini)),
                $city_name[0]['c_name'] => $this->OrderMoney($where4, $times, $city_name[0]['c_name']),
                $city_name[1]['c_name'] => $this->OrderMoney($where4, $times, $city_name[1]['c_name']),
                $city_name[2]['c_name'] => $this->OrderMoney($where4, $times, $city_name[2]['c_name']),
            ];
//            }
        }

        $data['cityOrdersTieData']['city_name'] = $city_name;
        $data['cityOrdersTieData']['order'] = $order;

        //各城市订单流水走势图
        $city_names = [] ;
        if(input('city_id') == 0){ //总公司查看订单前三名的城市
            $city_names = Db::name('order')->alias('o')
                ->distinct(true)
                ->field('c.name as c_name,count(o.id) as count')
                ->join('mx_cn_city c', 'c.id = o.city_id', 'inner')
                ->where($where3)
                ->group('o.city_id')
                ->order('count desc')
                ->limit(3)
                ->select();
        }else{           //按照分公司
            $city_names = Db::name('order')->alias('o')
                ->distinct(true)
                ->field('c.name as c_name,sum(money) as count')
                ->join('mx_cn_city c', 'c.id = o.city_id', 'inner')
                ->where($where3)
                ->group('o.city_id')
                ->limit(3)
                ->select();
        }

        $orders = [];
        for ($y = 0; $y <= $Interval_u; $y++) {     //行

            $op_o = date('Y-m-d', $start);

            $day_start_o = strtotime($op_o . ' 00:00:00');  //当天开始时间
            $day_end_o = strtotime($op_o . ' 23:59:59');    //当天结束时间
            $ini = date("Y-m-d", strtotime("+" . $y . " day", strtotime($op_o)));
            $times = date('m', strtotime($ini)) . '-' . date('d', strtotime($ini));

            $days = $this->days(date('m', $start));

//            if((((int)date('d', $start)) + $y) <= $days){
            $orders[] = [
                'times' => date('m', strtotime($ini)) . '/' . date('d', strtotime($ini)),
                $city_names[0]['c_name'] => $this->OrderMoney($where4, $times, $city_names[0]['c_name']),
                $city_names[1]['c_name'] => $this->OrderMoney($where4, $times, $city_names[1]['c_name']),
                $city_names[2]['c_name'] => $this->OrderMoney($where4, $times, $city_names[2]['c_name']),
            ];
//            }
        }

        $data['order_water']['city_name'] = $city_names;
        $data['order_water']['order'] = $orders;

        //全国订单分布地图
        $data['order_distribution'] = Db::name('order')
            ->alias('o')
            ->join('mx_cn_city c', 'c.id = o.city_id', 'left')
            ->join('mx_cn_prov p', 'c.pcode = p.code')
            ->field('p.name as p_name,count(o.id) as u_count')
//            ->where('status', 'in', '6,9')
            ->where($where3)
            ->group('p.id')->select();

        return ['code' => APICODE_SUCCESS, 'data' => $data];
    }

    //天数
    private function days($months)
    {
        $month = (int)$months;
        $day = 0;
        if ($month == 1) {
            $day = 31;
        } else if ($month == 2) {
            $day = 28;
        } else if ($month == 3) {
            $day = 31;
        } else if ($month == 4) {
            $day = 30;
        } else if ($month == 5) {
            $day = 31;
        } else if ($month == 6) {
            $day = 30;
        } else if ($month == 7) {
            $day = 31;
        } else if ($month == 8) {
            $day = 31;
        } else if ($month == 9) {
            $day = 30;
        } else if ($month == 10) {
            $day = 31;
        } else if ($month == 11) {
            $day = 30;
        } else if ($month == 12) {
            $day = 31;
        }
        return $day;
    }

    //返回订单总额
    private function OrderMoney($where4, $times, $city)
    {
        $start_time = strtotime(date('Y', time()) . "-" . $times . " 00:00:00");
        $end_time = strtotime(date('Y', time()) . "-" . $times . " 23:59:59");

        $city_id = Db::name('cn_city')->where(['name' => $city])->value('id');

        $order_money = Db::name('order')
            ->where('create_time', 'gt', $start_time)
            ->where('create_time', 'lt', $end_time)
            ->where(['city_id' => $city_id])
            ->sum('money');

        if (empty($order_money)) {
            $order_money = 0;
        }

        return $order_money;
    }

    //订单取消
    public function OrderCancel()
    {
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id'),
                "status" => 5,
                "is_cancel" => 2,
                "cancellation_reasons" => input('cancelCause'),
            ];
            $params['cancel_time'] = time() ;
            $res = Db::name('order')->update($params);
            if ($res > 0) {
                $file = fopen('./log.txt', 'a+');
                fwrite($file, "--------------------取消订单-------------------" . "\r\n");
                //取消成功之后,给订单回调
                $orders = Db::name('order')->where(['id' => input('order_id')])->find();
                if($orders['classification'] == "实时"){
                    //在进行推送
                    $this->appointment("取消订单",$orders['conducteur_id'],$orders['id'],3);
                }else if($orders['classification'] == "预约"){
                    //发推送
                    $this->appointment("取消订单",$orders['conducteur_id'],$orders['id'],3);
                }

                if (!empty($orders['mtorderid'])) {
                    //在进行推送
                    $this->appointment("取消订单",$orders['conducteur_id'],$orders['id'],12);
                    $param = [];
                    $tatol = [];
                    $rates = [];
                    $datas = $this->partner_post(input('order_id'), $param, 'CANCEL_BY_CS', $orders, "0", $orders['total_price'],$orders['rates'], 0);
                    fwrite($file, "--------------------datas-------------------" . $datas . "\r\n");
                }
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "取消成功",
                ];
            } else {
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
    function appointmentByCompany($title, $companyId, $message, $type, $business_id, $business_type_id, $conducteur_id,$laser)
    {
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
        $header = array(
            "Authorization:Basic $base64",
            "Content-Type:application/json"
        );
        $param = array("platform" => "all", "audience" => array("tag" => array("Company_$companyId")), "message" => array("msg_content" => $message . "," . $type . "," . $companyId . "," . $business_id . "," . $business_type_id . "," . $conducteur_id. "," . $laser, "title" => $title));
        $params = json_encode($param);
        $res = $this->request_post($url, $params, $header);
        $res_arr = json_decode($res, true);
    }

    private function partner_post($order_id, $param, $status, $orders, $eventCode, $total_price, $rates, $surcharge)
    {
        if ($status == 'WAIT_PAY') {
            $rate = json_decode($rates, true);
            $total_price = json_decode($total_price, true);
//            var_dump($total_price);
//            var_dump($rate);
//            exit();
            $bill = [
                'totalPrice' => (int)($total_price["money"] * 100) - (int)($orders['highwayPrice']*100) - (int)($orders['tollPrice']*100) - (int)($orders['surcharge'] *100)- (int)($orders['parkPrice']*100) ,//行程基础类费用总额，单位为分。完成履约行为后，除去高速费、停车费、感谢费、其他费用四项附加类费用项外订单产生的行程费用总和
                'driveDistance' => (int)($total_price["Mileage"] * 1000),//	行驶里程,单位m
                'driveTime' => (int)($total_price["total_time"] * 60 * 1000),//行驶时长,单位ms
                'initPrice' => (int)($rate["startMoney"]["money"]*100),//起步价。订单开始履约需要收取的费用，包含一定的里程和时长。注：当时长费和里程费不满起步价，但需要按照起步价金额收取的时候，里程费和时长费不需要传递，只传起步价金额。

//                'normalTimePrice' => 1,//正常时长费。非高峰及夜间时段行驶时间产生的费用总额
//                'normalDistancePrice' => 2,//正常里程费。非高峰及夜间时段行驶里程产生的费用总额
                'longDistancePrice' => (int)($rate["Kilometre"]["Longfee"]*100),//远程费。超过一定里程之后收取的远途行驶里程费用总和
                'longDistance' => (int)($rate["Kilometre"]["LongKilometers"]*1000),//	远程里程。单位m
//                'nightPrice' => 1,//夜间里程费。夜间时段行驶里程产生的费用总额
//                'nightDistance' => 1,//	夜间里程。单位m
//                'highwayPrice' => (int)($orders['highwayPrice']*100) ,//高速费。订单履约过程产生的高速类费用，此费用为代收代付类费用不能开票，不能抽佣，不能使用红包抵扣
//                'tollPrice' => (int)($orders['tollPrice']*100) ,//通行费。订单履约过程产生的过路过桥类费用，此费用为代收代付类费用不能开票，不能抽佣，不能使用红包抵扣
//                'parkPrice' => (int)($orders['parkPrice']*100) ,//停车费。订单履约过程产生的停车类费用，此费用为代收代付类费用不能开票，不能抽佣，不能使用红包抵扣
                'otherPrice' => (int)($orders['surcharge'] *100),//其他费。订单履约过程中产生的其他代收代付类费用，如：清洁费等费用项，此费用为代收代付类费用不能开票，不能抽佣，不能使用红包抵扣
//                'dynamicPrice' => 0,//动态调价费。根据供需关系对订单费用进行实时调价的金额。（此值可正可负，当为正值时美团会处展示成溢价费；当为负值时美团会展示为动态折扣）
//                'cancelPay' => 0,//应收取消费。取消状态下等于waitingPrice+cancelPrice
//                'suspectStatus' => 0,//费用是否可异议:0-否,1-是
//                'discountPrice' => 0,//服务商折扣金额。服务商给美团渠道提供的优惠金额，此字段需要传正值
//                'waitingPrice' => 0,//等待费。等待时间产生的费用总和
//                'waitingTime' => 12,//等待时长。单位ms
//                'cancelPrice' => 0,//	取消费
//                'eDispatchPrice' => 0,//电调费金额，单位：分。出租车场景下，国家允许收取的加价费用
//                'taxiMeterFee' =>0,//计价器费金额，单位：分。出租车场景下，打表计费产生的费用
            ];
            $rate["Kilometre"][0]["LongKilometers"] == "0.00" ?: $bill["longDistance"] = (int)($rate["Kilometre"][0]["LongKilometers"] * 1000);
            $rate["Kilometre"][0]["Longfee"] == "0.00" ?: $bill["longDistancePrice"] = (int)($rate["Kilometre"][0]["Longfee"] * 100);
//            if ($total_price["money"] != $rate["startMoney"]["money"]) {
            $bill['driveDistancePrice'] = (int)($rate["Mileage"][0]["money"]*100);//	里程费。行驶里程产生的费用总额，里程费=正常里程费+夜间里程费
            $bill['driveTimePrice'] = (int)($rate["tokinaga"][0]["money"]*100);//时长费。行驶时间产生的费用总额
//            }
        } else {
            $bill = [
                'totalPrice' => 0,
                'driveDistance' => 0,
                'driveTime' => 0,
                'initPrice' => 0,
                'driveDistancePrice' => 0,
                'driveTimePrice' => 0,
//                'normalTimePrice' => 0,
//                'normalDistancePrice' => 0,
//                'longDistancePrice' => 0,
//                'longDistance' => 0,
//                'nightPrice' => 0,
//                'nightDistance' => 0,
//                'highwayPrice' => 0,
//                'tollPrice' => 0,
//                'parkPrice' => 0,
//                'otherPrice' => 0,
//                'dynamicPrice' => 0,
//                'cancelPay' => 0,
//                'suspectStatus' => 0,
//                'discountPrice' => 0,
//                'waitingPrice' => 0,
//                'waitingTime' => 0,
//                'cancelPrice' => 0,
//                'eDispatchPrice' => 0,
//                'taxiMeterFee' => 0,
            ];
        }

        //车辆
        $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => $orders['conducteur_id']])->value('vehicle_id');
        $vehicle = Db::name('vehicle')->where(['id' => $vehicle_id])->find();

        $carInfo = [
//            'carColor'=>"黑色",
//            'carNumber'=>"黑A66789",
//            'brandName'=>"大众牌",
//            'carPic'=>"1",
//            'carId'=>"75",
            'carColor' => $vehicle['PlateColor'],
            'carNumber' => $vehicle['VehicleNo'],
            'brandName' => $vehicle['Model'],
//            'carPic' => "",
//            'carId' => "$vehicle_id",
        ];

        $customerServiceInfo = [
            'cancelReason' => $orders['cancellation_reasons'],
            'opName' => "admin",
        ];
        $chargeInfo = [
            'offlinePayAmount' => 0
        ];
        $continuousAssign = [
            'preDestLng' => "1",
            'preDestLat' => "1",
            'preOrderId' => "1",
            'preRemainDistance' => 1,
            'preRemainSecond' => 1,
            'wholePickupDistance' => 1,
            'wholePickupSecond' => 1,
        ];
        $conducteur = Db::name('conducteur')->where(['id' => $orders['conducteur_id']])->find();
        $conducteur_id = $orders['conducteur_id'];
        $driverInfo = [
//            'driverLastName'=>"王先生",
//            'driverMobile'=>"15776833552",
//            'driverName'=>"王先生",
//            'driverVirtualMobile'=>"15776833552",
//            'partnerDriverId'=>"273",
//            'driverRate'=>"4",
//            'driverPic'=>"1",
            'driverLastName' => mb_substr($conducteur['DriverName'], 0, 1, 'utf-8'),
            'driverMobile' => $conducteur['DriverPhone'],
            'driverName' => $conducteur['DriverName'],
            'driverVirtualMobile' => $orders['conducteur_virtual'],
            'partnerDriverId' => "$conducteur_id",
//            'driverRate' => "5",
//            'driverPic' => ""//$conducteur['grandet'],
        ];

        $product = [
            'partnerCarTypeId' => intval($orders['partnerCarTypeId']),
//            'outCarTypeId' => "1"
        ];

        $gps = new Gps();
        $driverInfos = db()->query("call getCarInfoByOrderId($vehicle_id)");

        $driverInfos = $driverInfos[0][0];
        $data_gps = $gps->getCarsStatus($driverInfos["Gps_number"])["data"][0];

        $ini['longitude'] = $data_gps["lonc"];
        $ini['latitude'] = $data_gps["latc"];

        $lat = 0 ;
        $lng = 0 ;
        if(!empty($data_gps)){
            $lat = $data_gps["latc"] ;
            $lng = $data_gps["lonc"] ;
        }else{
            //按照状态进行获取
            if($orders['status'] == 2){  //证明已经接单了
                $block = explode(',',$orders['orders_from'])  ;
                $lng = floatval(sprintf("%.6f", $block[0]));//floatval($orders['DepLongitude']) ;
                $lat = floatval(sprintf("%.6f", $block[1]));//floatval($orders['DepLatitude']) ;
            }else{
                //除2状态就是行程中的订单-取块里面的数据
                $location = explode(',',$gps->getDriverPositionByDriverId($conducteur_id));
                if(!empty($location)){
                    $lat = floatval($location[0]) ;
                    $lng = floatval($location[1]) ;
                }else{
                    $lat = 45.69726 ;
                    $lng = 126.585479 ;
                }
            }
        }

        $driverLocation = [
            'lat'=>$lat,
            'lng'=>$lng,
        ];

        $candidateConfirmList = [
            'driverInfo' => json_encode($driverInfo, JSON_UNESCAPED_UNICODE),
            'carInfo' => json_encode($carInfo, JSON_UNESCAPED_UNICODE),
            'product' => json_encode($product, JSON_UNESCAPED_UNICODE),
            'driverLocation' => json_encode($driverLocation, JSON_UNESCAPED_UNICODE),
        ];

        $driverFingerprint = [
            'wifimac' => "c4:b3:01:cb:f6:cf",
            'dm' => "false",
        ];

        $param['channel'] = strval("tcdc_car");
        $param['timestamp'] = strval(time() * 1000);

        $param['eventCode'] = $eventCode;
        $param['bill'] = json_encode($bill, JSON_UNESCAPED_UNICODE);
        $param['carInfo'] = json_encode($carInfo, JSON_UNESCAPED_UNICODE);
        $file = fopen('./log.txt', 'a+');
        if($status == "CANCEL_BY_CS"){
            $vritualController = new \app\api\controller\Vritualnumber() ;
            $result = $vritualController->releasePhoneNumberByOrderId((int)$order_id);
            fwrite($file, "-------------------虚拟号:--------------------" . json_encode($result, JSON_UNESCAPED_UNICODE) . "\r\n");
            $param['customerServiceInfo'] = json_encode($customerServiceInfo, JSON_UNESCAPED_UNICODE);
        }

//        $param['chargeInfo'] = [] ;//json_encode($chargeInfo, JSON_UNESCAPED_UNICODE);
        $param['driverInfo'] = json_encode($driverInfo, JSON_UNESCAPED_UNICODE);
        $param['eventTime'] = strval(time() * 1000);
        $param['mtOrderId'] = strval($orders['mtorderid']);
        $param['partnerOrderId'] = strval($order_id);
        $param['product'] = json_encode($product, JSON_UNESCAPED_UNICODE);
        $param['driverLocation'] = json_encode($driverLocation, JSON_UNESCAPED_UNICODE);
        $param['status'] = $status;
        if($status == "CANCEL_BY_CS"){
            $vritualController = new \app\api\controller\Vritualnumber() ;
            $result = $vritualController->releasePhoneNumberByOrderId((int)$order_id);
        }
//        $param['candidateConfirmList'] = json_encode($candidateConfirmList) ;
//        if ($eventCode == "0" || $eventCode == "1") {
//            $param['endTripLocation'] = json_encode(["lat" => 39.12504, "lng" => 117.200032], JSON_UNESCAPED_UNICODE);
//            $param['startTripLocation'] = json_encode(["lat" => 39.12504, "lng" => 117.200032], JSON_UNESCAPED_UNICODE);
//            $param['setoutLocation'] = json_encode(["lat" => 39.12504, "lng" => 117.200032], JSON_UNESCAPED_UNICODE);
//            $param['confirmLocation'] = json_encode(["lat" => 39.12504, "lng" => 117.200032], JSON_UNESCAPED_UNICODE);
//        }
//        $param['driverFingerprint'] = json_encode($driverFingerprint, JSON_UNESCAPED_UNICODE);
        $sign = $this->getSign($param, "IQBs6DADXQrBawyQyVZaQA==");
        $param['sign'] = $sign;//"4wpitbq9JyLEZXj3InLbTw==" ;

//        fwrite($file, "-------------------改价参数:--------------------" . json_encode($param, JSON_UNESCAPED_UNICODE) . "\r\n");
        $datas = $this->request_post("https://qcs-openapi.meituan.com/api/open/callback/common/v1/pushOrderStatus", $param);   //"application/x-www-from-urlencoded"
        fwrite($file, "-------------------数据1:--------------------" . json_encode($datas, JSON_UNESCAPED_UNICODE) . "\r\n");
    }
    function request_post($url = "", $param = "", $header = "") {
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
        curl_setopt($ch, CURLOPT_POSTFIELDS,  $curlPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch); // 运行curl

        curl_close($ch);
        return $data;
    }

    private function request_post_partner($url = "", $param = "", $header = "")
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

    //创建顺风车订单
    public function CreationCarpoolOrder()
    {
        $data = input('');
        $params = [
            "origin" => input('?origin') ? input('origin') : null,
            "Destination" => input('?Destination') ? input('Destination') : null,
            "spot" => input('?spot') ? input('spot') : null,
            "line_id" => input('?line_id') ? input('line_id') : null,
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
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

        $ini['order_name'] = '预约' . '订单' . "(顺风车)";

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
        $ini['spot'] = input('spot');
        $ini['line_id'] = input('line_id') ;

        //拦截顺风车
        $flag = $this->interceptExpress(input('line_id'), strtotime(input('DepartTime')), input('conducteur_id'));
        if ($flag == 1) {
            return ['code' => APICODE_ERROR, 'msg' => '该时段不能发布'];
        }
        $ini['DepartTime'] = input('DepartTime') ;
        //主订单
        $order_id = Db::name('order')->insertGetId($ini);

        $spot = input('spot');

        //订单线路
        $str = ['origin' => input('origin')];

        $line_detail = Db::name('line_detail')->where(['line_id' => input('line_id')])
            ->where('destination', 'in', input('Destination'))
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
                        $kk => $value['price'] + $bl
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

        //根据起点和终点
        $order_lines = Db::name('order_line')->where(['origin' =>input('origin'),'destination'=>input('Destination'),'order_id'=>$order_id ])->find();

        //根据用户创建从订单
        $users = $data['users'] ;
        $user_id = 0 ;
        if(!empty($users)){
            $file = fopen('./log.txt', 'a+');
            //判断用户是否存在
            foreach ($users as $kk=>$vv){
                $user_id =  Db::name('user')->where(['PassengerPhone' => $vv['phone']])->value('id');
                if(empty($user_id)){    //不存在
                    $user['nickname'] = "同城". rand(0000, 9999);
                    $user['PassengerPhone'] = $vv['phone'] ;
                    $user['create_time'] = time() ;
                    $user['city_id'] = input('city_id') ;

                    $user_id =  Db::name('user')->insertGetId($user) ;
                }
                //创建子订单
                $this->CreateChildOrder($order_id,$order_lines['id'],$user_id,input('conducteur_id'),$order_lines['price']) ;
            }
        }
        return ['code' => APICODE_SUCCESS, 'msg' => '创建成功'];
    }

    private function CreateChildOrder($order_id,$order_line_id,$user_id,$conducteur_id,$money){
        //用户订单
        $orders = Db::name('order')->where(['id' => $order_id])->find();
        $conducteur = Db::name('conducteur')->where(['id' => $orders['conducteur_id']])->find();
        //获取车辆
        $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => $orders['conducteur_id']])->value('vehicle_id');
        $Gps_number = Db::name('vehicle')->where(['id' => $vehicle_id])->value('Gps_number');
        $user = Db::name('user')->where(['id' => $user_id])->find();
        //线路订单
        $order_line = Db::name('order_line')->where(['id' => $order_line_id])->find();

        $OrderId = 'SF' . $orders['city_id'] . '0' . date('Ymdhis') . rand(0000, 9999);

        $params['OrderId'] = $OrderId;
        $params['order_id'] = $order_id;
        $params['order_line_id'] = $order_line_id;
        $params['user_id'] = $user_id;
        $params['conducteur_id'] = $conducteur_id;
        $params['company_id'] = $orders['company_id'];
        $params['city_id'] = $orders['city_id'];
        $params['conducteur_id'] = $orders['conducteur_id'];
        $params['DepartTime'] = $orders['DepartTime'];
        $params['classification'] = '顺风车';

        $params['conducteur_phone'] = $conducteur['DriverPhone'];
        $params['conducteur_name'] = $conducteur['DriverName'];

        $params['user_phone'] = $user['PassengerPhone'];
        $params['user_name'] = $user['nickname'];

        $params['origin'] = $order_line['origin'];
        $params['Destination'] = $order_line['destination'];
        //经度和纬度
        $params['DepLongitude'] = $order_line['origin_longitude'];
        $params['DepLatitude'] = $order_line['origin_latitude'];
        $params['DestLongitude'] = $order_line['destination_longitude'];
        $params['DestLatitude'] = $order_line['destination_latitude'];
        $params['status'] = 12;
        $params['pay_time'] = time() ;
        $params['order_name'] = "预约订单(城际)";
        $params['money'] = $money;
        $params['fare'] = $money;
        $params['create_time'] = time();
        //座位数和座位详情
        $params['Ridership'] = 1 ;
        $params['seat'] = "" ;
        $params['business_id'] = 4 ;
        $params['description'] = "" ;
        $params['gps_number'] = $Gps_number ;

        $order_id = Db::name('order')->insertGetId($params);
    }

    //拦截顺风车
    function interceptExpress($line_id, $DepartTime, $conducteur_id)
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
                if ($departTime > $DepartTime) {
                    $flag = 1;
                }
                if ($DepartTime < $departTime) {
                    $flag = 1;
                }
            }
        }
        return $flag;
    }

    //订单-改价
    public function OrderChangePrice(){
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "starting_fare" => input('?starting_fare') ? input('starting_fare') : null,
            "mileage_fee" => input('?mileage_fee') ? input('mileage_fee') : null,
            "how_fee" => input('?how_fee') ? input('how_fee') : null,
            "long_fee" => input('?long_fee') ? input('long_fee') : null,
            "total_price" => input('?total_price') ? input('total_price') : null,
            "rates" => input('?rates') ? input('rates') : null,
            "surcharge" => input('?surcharge') ? input('surcharge') : null,
            "highwayPrice" => input('?highwayPrice') ? input('highwayPrice') : null,
            "tollPrice" => input('?tollPrice') ? input('tollPrice') : null,
            "parkPrice" => input('?parkPrice') ? input('parkPrice') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $orders = Db::name('order')->where(['id'=>input('order_id')])->find() ;

        //更改订单金额
        $ini['id'] = input('order_id');
        $ini['money'] = input('total_price') + input('surcharge') + input('highwayPrice') + input('tollPrice') + input('parkPrice');
        $ini['rates'] = input('rates') ;
        $ini['surcharge'] = input('surcharge');                  //附加费
        $ini['highwayPrice'] = input('highwayPrice') ;          //高速费
        $ini['tollPrice'] = input('tollPrice') ;                 //通行费
        $ini['parkPrice'] = input('parkPrice') ;                 //停车费

        //total_price
        $total_prices = json_decode($orders['total_price'] , true) ;
        //将金额进行修改
        $total_prices['money'] = input('total_price') + input('surcharge') + input('highwayPrice') + input('tollPrice') + input('parkPrice') ;
        //获取距离
        $rates = json_decode( input('rates') , true ) ;
        $Mileage = 0 ;
        foreach ($rates['Mileage'] as $key=>$value){
            $Mileage += $value['mileage'] ;
        }
        $total_prices['Mileage'] = $Mileage ;

        $ini['total_price'] = json_encode($total_prices) ;
        $order = Db::name('order')->update($ini);

        if($order > 0){
            //美团回调
            $param = [];
            $orderss = Db::name('order')->where(['id'=>input('order_id')])->find() ;
            $total_price = $orderss['total_price'];
            $rates = $orderss['rates'];
            $this->partner_post(input('order_id'), $param, 'WAIT_PAY', $orderss, "1", $total_price, $rates,0);

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "改价成功",
            ];
        }else{
            return [
                "code" => APICODE_ERROR,
                "msg" => "改价失败",
            ];
        }
    }

    function appointment($title, $uid, $message,$type)
    {
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
        $header = array(
            "Authorization:Basic $base64",
            "Content-Type:application/json"
        );
        $param=array("platform"=>"all","audience"=>array("tag"=>array("D_$uid")),"message"=>array("msg_content"=>$message.",".$type,"title"=>$title));
        $params=json_encode($param);
        $res = $this->request_post($url, $params, $header);
        $res_arr = json_decode($res, true);
    }

    //热力图
    public function ThermodynamicChart(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
//            "times" => input('?times') ? input('times') : null,
//            "start_period" => input('?start_period') ? input('start_period') : null,
//            "end_period" => input('?end_period') ? input('end_period') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        //查询订单
        //开始时段
        $start_period = '' ;
        if(!empty(input('start_period'))){
            $start_period = ' '.input('start_period').':00' ;
        }else{
            $start_period = " 00:00:00";
        }
        //结束时段
        $end_period = '' ;
        if(!empty(input('end_period'))){
            $end_period = ' '.input('end_period').':00' ;
        }else{
            $end_period = " 23:59:59" ;
        }
        $start_time = strtotime(input('times').$start_period) ;
        $end_time = strtotime(input('times').$end_period) ;
        $where = [] ;
        if(input('city_id') != 'null' && !empty(input('city_id'))){
            $where['city_id'] = ['eq',input('city_id')] ;
        }
        $where1 = [] ;
        if(input('type_service') != 'null' && !empty(input('type_service')) ){
            $where1['business_id'] = ['eq',input('type_service')] ;
        }
        $where2 = [] ;
        if(input('classification') != 'null' && !empty(input('classification')) ){
            $where2['classification'] = ['eq',input('classification')] ;
        }
        $orders = Db::name('order')->field('id,DepLongitude,DepLatitude')
                                 ->where($where)
                                 ->where('create_time','gt',$start_time)
                                 ->where('create_time','lt',$end_time)
                                 ->where($where1)
                                 ->where($where2)
                                 ->select();

        //按照经纬度分区
        foreach ($orders as $key=>$value){
            $orders[$key]['lng'] = $value['DepLongitude'] ;
            $orders[$key]['lat'] = $value['DepLatitude'] ;
            $orders[$key]['count'] = 5 ;
        }

        return [
            "code" => APICODE_SUCCESS,
            "msg" => "查询成功",
            "data" => $orders,
        ];
    }

    //客运订单明细
    public function passengerTransportOrderlist(){
        $params = [
            "j.city_id" => input('?city_id') ? ['eq',input('city_id')] : null,
            "j.status" => input('?status') ? ['in',input('status')] : null,
        ];

//        $where = []  ; $where1 = [] ;
//        if(!empty(input('pay_time')) && input('pay_time') != "null" ){
//            $pay_time = explode("," , input('pay_time') ) ;
//            $start_time = strtotime($pay_time[0]." 00:00:00") ;
//            $end_time = strtotime($pay_time[1]." 23:59:59") ;
//
//            $where['j.pay_time'] = ['gt', $start_time ] ;
//            $where1['j.pay_time'] = ['lt', $end_time ] ;
//        }
        $where3 = []  ; $where4 = [] ;
        if(!empty(input('create_time')) && input('create_time') != "null" ){
            $create_time = explode("," , input('create_time') )  ;
            $start_time = strtotime($create_time[0]." 00:00:00") ;
            $end_time = strtotime($create_time[1]." 23:59:59") ;

            $where3['j.times'] = ['gt', $start_time ] ;
            $where4['j.times'] = ['lt', $end_time ] ;
        }

        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;

        $sortBy = input('?orderBy') ? input('orderBy') : "id desc";

        $data = db('journey')->alias('j')
                ->field('j.*,c.DriverName,c.DriverPhone')
                ->join('mx_vehicle_binding b','b.vehicle_id = j.vehicle_id','left')
                ->join('mx_conducteur c','c.id = b.conducteur_id','left')
                ->where(self::filterFilter($params))->where($where3)->where($where4)->order($sortBy)->page($pageNum, $pageSize)->select();

        $sum = db('journey')->alias('j')->where(self::filterFilter($params))->where($where3)->where($where4)->count();

        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" => $data
        ];
    }

    //符合的司机列表
    public function ConformConducteurList(){
        $params = [
            "business_id" => input('?business_id') ? input('business_id') : null,
            "business_type_id" => input('?business_type_id') ? input('business_type_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["business_id", "business_type_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $conducteur = Db::name('conducteur')->alias('c')
                                       ->field('c.id,c.DriverName,c.DriverPhone')
                                       ->join('mx_vehicle_binding b','b.conducteur_id = c.id','left')
                                       ->join('mx_vehicle v','v.id = b.vehicle_id','left')
                                       ->where(['v.business_id'=>input('business_id'),'v.businesstype_id' =>input('business_type_id') ])
                                       ->select() ;

        return [
            "code" => APICODE_SUCCESS,
            "msg"=>"查询成功",
            "data" => $conducteur
        ];
    }

    //订单-改派
    public function OrderReformed(){

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

        $conducteur = Db::name('conducteur')->where(['id' => input('conducteur_id') ])->find();

        $ini['id'] = input('order_id') ;
        $ini['conducteur_id'] = input('conducteur_id') ;
        $ini['conducteur_name'] = $conducteur['DriverName'];
        $ini['conducteur_phone'] = $conducteur['DriverPhone'];
        //高德
        $ini['key'] = $conducteur['key'];
        $ini['service'] = $conducteur['service'];
        $ini['terimnal'] = $conducteur['terimnal'];
        $ini['trace'] = $conducteur['trace'];

        $res =  Db::name('order')->update($ini) ;

        if($res > 0){
            return [
                "code" => APICODE_SUCCESS,
                "msg"=>"改派成功",
            ];
        }else{
            return [
                "code" => APICODE_ERROR,
                "msg"=>"改派失败",
            ];
        }
    }
}