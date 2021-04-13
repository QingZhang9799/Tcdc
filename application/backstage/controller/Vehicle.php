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



class Vehicle extends Base
{
    //车辆列表
    public function vehicle_list(){
        $params = [
//            "city_id" => input('?city_id') ? input('city_id') : null,
            "v.type" => input('?type') ? input('type') : null,
            "v.inspection_expirant_time" => input('?inspection_expirant_time') ? input('inspection_expirant_time') : null,
//            "bd_chauffeur_count" => input('?bd_chauffeur_count') ? ['gt',input('bd_chauffeur_count')] : null,
            "v.status" => input('?status') ? input('status') : null,
            "v.anciennete" => input('?anciennete') ? input('anciennete') : null,
        ];
        if(empty($params['v.inspection_expirant_time']) || $params['v.inspection_expirant_time'] == "null"){
            unset($params['v.inspection_expirant_time']);
        }
        if(empty($params['v.type']) || $params['v.type'] == "null"){
            unset($params['v.type']);
        }
        if(empty(input('city_id')) || input('city_id') == "null"){
            unset($params['v.city_id']);
        }else{
            $params['v.city_id'] = ['eq' , input('city_id') ] ;
        }
        if(empty($params['v.bd_chauffeur_count']) || $params['v.bd_chauffeur_count'] == "null" || $params['v.bd_chauffeur_count'] == "0"){
            unset($params['v.bd_chauffeur_count']);
        }else{
            $params['v.bd_chauffeur_count'] = ['gt',input('bd_chauffeur_count')];
        }
        if(empty(input('OwnerName')) ){
            unset($params['v.status']);
        }else{
            $params['v.OwnerName'] = ['like','%'.input('OwnerName').'%'] ;
        }
        if(!empty(input('passenger'))){
            $params['v.passenger'] = ['eq', input('passenger') ] ;
        }
        if(empty($params['v.status']) || $params['v.status'] == "null"){
            unset($params['v.status']);
        }
        if(empty($params['v.anciennete'])  || $params['v.anciennete'] == "null"){
            unset($params['v.anciennete']);
        }
        if(empty(input('number')) ){
            unset($params['v.VehicleNo']);
        }else{
            $params['v.VehicleNo'] = ['like','%'.input('number').'%'] ;
        }
        if(empty(input('type_service'))){
            unset($params['v.type_service']);
        }else{
            $params['v.business_id'] = ['in',input('type_service')] ;
        }
        if(empty(input('business_id')) || input('business_id') == "null" ){

        }else{
            $params['v.business_id'] = ['eq',input('business_id')] ;
        }
        if(empty(input('company_id')) || input('company_id') == "null" ){

        }else{
            $params['v.company_id'] = ['eq',input('company_id')] ;
        }
        $where = [] ; $where1 = [] ;
        if(empty(input('create_time')) || input('create_time') == "null" ){

        }else{
            $times = explode(',',input('create_time'))  ;
            $start_time = strtotime( $times[0] .' 00:00:00' ) ;
            $end_time = strtotime( $times[1] .' 23:59:59' ) ;
            $where['v.create_time'] = ['gt',$start_time] ;
            $where1['v.create_time'] = ['lt',$end_time] ;
        }
//        halt($params);
//        return self::pageReturnStrot(db('vehicle'), $params,'id desc');
        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;

        $sum = db('vehicle')->alias('v')->where($where)->where($where1)->where(self::filterFilter($params))->count();

        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" =>  db('vehicle')->alias('v')
                       ->field('v.*,b.title as b_title')
                       ->join('mx_business_type b','v.businesstype_id = b.id','left')
                       ->where($where)->where($where1)
                       ->where(self::filterFilter($params))->order('id desc')->page($pageNum, $pageSize)
                       ->select()
        ];
    }

    //添加车辆
    public function add_vehicle(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "type" => input('?type') ? input('type') : null,
            "Model" => input('?Model') ? input('Model') : null,
            "anciennete" => input('?anciennete') ? input('anciennete') : null,
            "number" => input('?number') ? input('number') : null,
            "chars_chown" => input('?chars_chown') ? input('chars_chown') : null,
            "activites" => input('?activites') ? input('activites') : null,
            "bd_chauffeur_count" => input('?bd_chauffeur_count') ? input('bd_chauffeur_count') : null,
            "inspection_expirant_time" => input('?inspection_expirant_time') ? input('inspection_expirant_time') : null,
            "status" => input('?status') ? input('status') : null,
            "cocontractant_id" => input('?cocontractant_id') ? input('cocontractant_id') : null,
            "create_time" => input('?create_time') ? input('create_time') : time(),
            "company_id" => input('?company_id') ? input('company_id') : null,
            "OwnerName" => input('?OwnerName') ? input('OwnerName') : null,
            "VehicleNo" => input('?VehicleNo') ? input('VehicleNo') : null,
            "business_id" => input('?business_id') ? input('business_id') : null,
            "businesstype_id" => input('?businesstype_id') ? input('businesstype_id') : null,
            "seating" => input('?seating') ? input('seating') : null,
            "Gps_number" => input('?Gps_number') ? input('Gps_number') : null,
            "Driving_permit_front" => input('?Driving_permit_front') ? input('Driving_permit_front') : null,
            "Driving_permit_side" => input('?Driving_permit_side') ? input('Driving_permit_side') : null,
            "vehicle_photos" => input('?vehicle_photos') ? input('vehicle_photos') : null,
            "online_operation_certificate" => input('?online_operation_certificate') ? input('online_operation_certificate') : null,
            "passenger" => input('?passenger') ? input('passenger') : null,
            "PlateColor" => input('?PlateColor') ? input('PlateColor') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "Model"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $vehicle = db('vehicle')->insert($params);

        if($vehicle){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'创建成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'创建失败'
            ];
        }
    }

    //按id查询车辆
    public function query_vehicle(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('vehicle')->where($params)->find();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "车辆ID不能为空"
            ];
        }
    }

    //修改车辆
    public function update_vehicle(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "type" => input('?type') ? input('type') : null,
            "marque" => input('?marque') ? input('marque') : null,
            "anciennete" => input('?anciennete') ? input('anciennete') : null,
            "number" => input('?number') ? input('number') : null,
            "chars_chown" => input('?chars_chown') ? input('chars_chown') : null,
            "activites" => input('?activites') ? input('activites') : null,
            "bd_chauffeur_count" => input('?bd_chauffeur_count') ? input('bd_chauffeur_count') : null,
            "inspection_expirant_time" => input('?inspection_expirant_time') ? input('inspection_expirant_time') : null,
            "status" => input('?status') ? input('status') : null,
            "cocontractant_id" => input('?cocontractant_id') ? input('cocontractant_id') : null,
            "create_time" => input('?create_time') ? input('create_time') : null,
            "seating" => input('?seating') ? input('seating') : null,
            "OwnerName" => input('?OwnerName') ? input('OwnerName') : null,
            "business_id" => input('?business_id') ? input('business_id') : null,
            "businesstype_id" => input('?businesstype_id') ? input('businesstype_id') : null,
            "Gps_number" => input('?Gps_number') ? input('Gps_number') : null,
            "Driving_permit_front" => input('?Driving_permit_front') ? input('Driving_permit_front') : null,
            "Driving_permit_side" => input('?Driving_permit_side') ? input('Driving_permit_side') : null,
            "vehicle_photos" => input('?vehicle_photos') ? input('vehicle_photos') : null,
            "online_operation_certificate" => input('?online_operation_certificate') ? input('online_operation_certificate') : null,
            "company_id" => input('?company_id') ? input('company_id') : null,
            "VehicleNo" => input('?VehicleNo') ? input('VehicleNo') : null,
            "PlateColor" => input('?PlateColor') ? input('PlateColor') : null,
            "Model" => input('?Model') ? input('Model') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $id = $params["id"];
        unset($params["id"]);
        $res = db('vehicle')->where("id", $id)->update($params);
        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];

    }

    //车辆禁封
    public function banned_vehicle(){
        $status  = request()->param('status');
        $vehicle_id  = request()->param('vehicle_id');
        $times  = request()->param('times');
        $cause   = request()->param('cause');

        $ini['id'] = $vehicle_id ;
        $ini['status'] = $status ;

        $vehicle = Db::name('vehicle')->update($ini);

        if($vehicle){
            //保存原因
            $inii['vehicle_id'] = $vehicle_id ;
            $inii['cause'] = $cause ;
            $inii['times'] = $times ;
            $inii['type'] = 1 ;

            $vehicle_banned = Db::name('vehicle_banned')->insert($inii);

            if($vehicle_banned){
                return ['code'=>APICODE_SUCCESS,'msg'=>'封禁成功'];
            }else{
                return ['code'=>APICODE_DATABASEERROR,'msg'=>'数据库错误'];
            }
        }else{
            return ['code'=>APICODE_DATABASEERROR,'msg'=>'数据库错误'];
        }

    }

    //车辆解封
    public function deblocking_vehicle(){

        $status  = request()->param('status');
        $vehicle_id  = request()->param('vehicle_id');
        $times  = request()->param('times');
        $cause   = request()->param('cause');

        $ini['id'] = $vehicle_id ;
        $ini['status'] = $status ;

        $vehicle = Db::name('vehicle')->update($ini);

        if($vehicle){
            //保存原因
            $inii['vehicle_id'] = $vehicle_id ;
            $inii['cause'] = $cause ;
            $inii['times'] = $times ;
            $inii['type'] = 2 ;

            $vehicle_banned = Db::name('vehicle_banned')->insert($inii);

            if($vehicle_banned){
                return ['code'=>APICODE_SUCCESS,'msg'=>'解封成功'];
            }else{
                return ['code'=>APICODE_DATABASEERROR,'msg'=>'数据库错误'];
            }
        }else{
            return ['code'=>APICODE_DATABASEERROR,'msg'=>'数据库错误'];
        }
    }

    //车辆首页
    public function vehicle_home(){
        $where = [] ; $where1 = [] ; $where2 = [] ; $where3 = [] ; $where4 =[] ;
        if(input('company_id') != 0){           //分公司首页
            $where['v.city_id'] = ['eq',input('city_id')];
            $where1['c.city_id'] = ['eq',input('city_id')];
            $where2['company_id'] = ['eq',input('company_id')];
            $where3['o.city_id'] = ['eq',input('city_id')];
            $where4['city_id'] = ['eq',input('city_id')];
        }
        //全国车辆总数
        $vehicle_count = Db::name('vehicle')->where($where4)->count();
        $data['vehicle_count'] = $vehicle_count;

        //今日活跃车辆数
        $todayStart= strtotime( date('Y-m-d 00:00:00', time()) );
        $todayEnd= strtotime ( date('Y-m-d 23:59:59', time()) );

        $jr_vehicle_count = Db::name('vehicle')->alias('v')
            ->join('mx_vehicle_binding b','b.vehicle_id = v.id','left')
            ->join('mx_conducteur c','c.id = b.conducteur_id')
            ->join('mx_order o','o.conducteur_id = c.id')
            ->where('o.create_time','egt',$todayStart)
            ->where('o.create_time','elt',$todayEnd)
            ->where($where)
            ->count();

        $data['jr_vehicle_count'] = $jr_vehicle_count ;

        //新增注册车辆数
        $register_count = Db::name('vehicle')->alias('v')
            ->where('v.create_time','egt',$todayStart)
            ->where('v.create_time','elt',$todayEnd)
            ->where($where)
            ->count();

        $data['register_count'] = $register_count ;

        //日期为null   本周
        $times = aweek("",1);
        $beginThisweek = strtotime($times[0]);
        $endThisweek = strtotime($times[1]);

        $register_time = input('register_time');
        $active_time = input('active_time');

        //车辆注册趋势
        $Interval_o = 0 ; //注册的间隔天数
        $Interval_h = 0 ; //活跃的间隔天数

        if($register_time == "null" || $register_time == "null " ){
            $start_o = $beginThisweek ;
            $end_o = $endThisweek ;

            $Interval_o = diffBetweenTwoDays((int)$start_o,(int)$end_o);
        }else{
            $order_times = explode(',',$register_time);
            $start_o = strtotime( $order_times[0]);
            $end_o = strtotime( $order_times[1] );

            $Interval_o = diffBetweenTwoDays((int)$start_o,(int)$end_o);
        }

        if($active_time == "null"|| $active_time == "null " ){
            $start = $beginThisweek ;
            $end = $endThisweek ;

            $Interval_h = diffBetweenTwoDays((int)$start,(int)$end);
        }else{
            $order_times = explode(',',$active_time);
            $start = strtotime($order_times[0]);
            $end = strtotime($order_times[1]);

            $Interval_h = diffBetweenTwoDays((int)$start,(int)$end);
        }

        $city_name = Db::name('vehicle')->alias('v')
            ->distinct(true)
            ->field('c.name as c_name')
            ->join('mx_cn_city c','c.id = v.city_id','inner')
            ->where($where)
            ->limit(3)
            ->select();

        $order = [] ;
        for ($y = 0; $y <= $Interval_o; $y++) {     //行

            $op_o = date('Y-m-d',$start_o);

            $day_start_o = strtotime($op_o.' 00:00:00') ;  //当天开始时间
            $day_end_o = strtotime($op_o.' 23:59:59') ;    //当天结束时间
            $days =  $this->days(date('m', $start_o) );
            $ini = date("Y-m-d",strtotime("+".$y." day",strtotime($op_o)));
            $times = date('m',strtotime($ini)). '-' .date('d',strtotime($ini)) ;
//            if((((int)date('d', $start_o)) + $y) <= $days) {
                $order[] = [
                    'times' => date('m',strtotime($ini)). '/' .date('d',strtotime($ini)),
                    $city_name[0]['c_name'] => $this->VehicleRegister($where4,$times,$city_name[0]['c_name']),
                    $city_name[1]['c_name'] => $this->VehicleRegister($where4,$times,$city_name[1]['c_name']),
                    $city_name[2]['c_name'] => $this->VehicleRegister($where4,$times,$city_name[2]['c_name']),
                ];
//            }
        }

        $data['vehicle_register']['city_name'] = $city_name;
        $data['vehicle_register']['order'] = $order;

        //车辆活跃趋势
        $city_names = Db::name('vehicle')->alias('v')
            ->distinct(true)
            ->field('c.name as c_name')
            ->join('mx_cn_city c','c.id = v.city_id','inner')
            ->where($where)
            ->limit(3)
            ->select();

        $orders= [] ;
        for ($y = 0; $y <= $Interval_h; $y++) {     //行

            $op_o = date('Y-m-d',$start);

            $day_start_o = strtotime($op_o.' 00:00:00') ;  //当天开始时间
            $day_end_o = strtotime($op_o.' 23:59:59') ;    //当天结束时间
            $ini = date("Y-m-d",strtotime("+".$y." day",strtotime($op_o)));
            $times = date('m',strtotime($ini)). '-' .date('d',strtotime($ini)) ;
            $days =  $this->days(date('m', $start) );
//            if((((int)date('d', $start)) + $y) <= $days) {
                $orders[] = [
                    'times' => date('m',strtotime($ini)). '/' .date('d',strtotime($ini)),
                    $city_names[0]['c_name'] => $this->VehicleActivity($where3, $times,$city_names[0]['c_name']),
                    $city_names[1]['c_name'] => $this->VehicleActivity($where3, $times,$city_names[1]['c_name']),
                    $city_names[2]['c_name'] => $this->VehicleActivity($where3, $times,$city_names[2]['c_name']),
                ];
//            }
        }

        $data['vehicle_active']['city_name'] = $city_names;
        $data['vehicle_active']['order'] = $orders;

        return ['code'=>APICODE_SUCCESS,'data'=>$data];
    }

    //车辆注册
    private function VehicleRegister($where4 , $times,$city){
        $start_time = strtotime(date('Y',time())."-".$times." 00:00:00");
        $end_time = strtotime(date('Y',time())."-".$times." 23:59:59") ;

        $city_id = Db::name('cn_city')->where(['name'=>$city])->value('id');

        $vehicle_count = Db::name('vehicle')->where($where4)->where('create_time','gt',$start_time)
                                                    ->where('create_time','lt',$end_time)
                                                    ->where(['city_id'=>$city_id])
                                                    ->count();
        return $vehicle_count ;
    }

    //车辆活动
    private function VehicleActivity($where3,$times,$city){
        $start_time = strtotime(date('Y',time())."-".$times." 00:00:00");
        $end_time = strtotime(date('Y',time())."-".$times." 23:59:59") ;

        $city_id = Db::name('cn_city')->where(['name'=>$city])->value('id');


        $vehicle_count = Db::name('vehicle')->alias('v')->join('mx_order o','o.city_id = v.city_id')
                                    ->where($where3)
                                    ->where('o.create_time','gt',$start_time)
                                    ->where('o.create_time','lt',$end_time)
                                    ->where(['v.city_id'=>$city_id])
                                    ->count();
        return $vehicle_count ;
    }
    //天数
    private function days($months){
        $month = (int)$months;
        $day = 0 ;
        if($month == 1){
            $day = 31;
        }else if($month == 2){
            $day = 28;
        }else if($month == 3){
            $day = 31;
        }else if($month == 4){
            $day = 30;
        }else if($month == 5){
            $day = 31;
        }else if($month == 6){
            $day = 30;
        }else if($month == 7){
            $day = 31;
        }else if($month == 8){
            $day = 31;
        }else if($month == 9){
            $day = 30;
        }else if($month == 10){
            $day = 31;
        }else if($month == 11){
            $day = 30;
        }else if($month == 12){
            $day = 31;
        }
        return $day;
    }

    //绑定驾驶员
    public function bindingDriver(){

        $params = [
            "vehicle_id" => input('?vehicle_id') ? input('vehicle_id') : null,
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["vehicle_id", "conducteur_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $vehicle_id = input('vehicle_id');
        $conducteur = explode(',',input('conducteur_id'));
        $ini = [] ;
        foreach ($conducteur as $key=>$value){
            $ini[] = [
                'vehicle_id'=>$vehicle_id,
                'conducteur_id'=>$value,
            ];
        }
        //添加之前，删除掉之前数据
        $binding = Db::name('vehicle_binding')->where(['vehicle_id'=>$vehicle_id])->select();
        foreach ($binding as $k=>$v){
            Db::name('vehicle_binding')->where(['id'=>$v['id']])->delete();
        }
        $vehicle_binding = db('vehicle_binding')->insertAll($ini);
        if($vehicle_binding){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'绑定成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'绑定失败'
            ];
        }
    }

}