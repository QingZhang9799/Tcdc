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



class System extends Base
{
    //添加服务协议
    public function addAgreement(){
        $params = [
            "title" => input('?title') ? input('title') : null,
            "content" => input('?content') ? input('content') : null,
            "agreement_classify_id" => input('?agreement_classify_id') ? input('agreement_classify_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["title","content"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $agreement = db('agreement')->insert($params);

        if($agreement){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'添加成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'添加失败'
            ];
        }
    }
    //服务协议列表
    public function AgreementList(){
        return self::pageReturn(db('agreement'), "");
    }
    //根据id获取服务协议详情
    public function getAgreement(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('agreement')->where($params)->find();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "协议ID不能为空"
            ];
        }
    }
    //添加公司业务
    public function AddCompanyBusiness(){
        $params = [
            "business_name" => input('?business_name') ? input('business_name') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["business_name"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $business = db('business')->insert($params);

        if($business){
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
    //添加业务车型
    public function AddBusinessType(){
        $params = [
            "title" => input('?title') ? input('title') : null,
            "business_id" => input('?business_id') ? input('business_id') : null,
            "img" => input('?img') ? input('img') : null,
        ];
        $params = $this->filterFilter($params);

        $required = ["title","business_id","img"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $business = db('business_type')->insert($params);

        if($business){
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
    //根据ID获取公司业务
    public function getCompanyBusiness(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];

            $data = db('business')->where($params)->find();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "业务ID不能为空"
            ];
        }
    }
    //根据id获取车型
    public function getBusinessType(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];

            $data = db('business_type')->where($params)->find();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "车型ID不能为空"
            ];
        }
    }
    //报警信息列表
    public function callthePolice(){
        return self::pageReturn(db('alarm'), '');
    }
    //版本记录列表
    public function VersionsRecordList(){
        return self::pageReturn(db('versions_record'), '');
    }
    //线路列表
    public function LineList(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "state" => input('?state') ? input('state') : null,
        ];
//        return self::pageReturnStrot(db('line'), $params,'id desc');
        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;
        $sortBy=input('?orderBy') ? input('orderBy') : 'id desc';

        $data = Db::name('line')->where(self::filterFilter($params))->order($sortBy)->page($pageNum, $pageSize)->select();
        foreach ($data as $key=>$value){
            $price = Db::name('line_detail')->where(['line_id'=>$value['id'],"origin"=>$value['origin'],"destination"=>$value['destination']])->value('price');
            $data[$key]['price'] = $price ;
        }

        $sum = Db::name('line')->where(self::filterFilter($params))->count();

        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" => $data
        ];
    }
    //添加线路
    public function AddLine(){
        $data = input('');

        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "origin" => input('?origin') ? input('origin') : null,
            "destination" => input('?destination') ? input('destination')  : null,
            "origin_longitude" => input('?origin_longitude') ? input('origin_longitude')  : null,
            "origin_latitude" => input('?origin_latitude') ? input('origin_latitude')  : null,
            "destination_longitude" => input('?destination_longitude') ? input('destination_longitude')  : null,
            "destination_latitude" => input('?destination_latitude') ? input('destination_latitude')  : null,
            "start_time" => input('?start_time') ? input('start_time')  : null,
            "end_time" => input('?end_time') ? input('end_time')  : null,
            "is_display" => input('?is_display') ? input('is_display')  : null,
            "discount" => input('?discount') ? input('discount')  : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "origin", "destination"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $params['create_time'] = time();

        $spot = $data['spot'] ;
        $display = $data['display'] ;

//        $str = "" ;
//        foreach ($spot as $key=>$value){
//            $str .= $value.',';
//        }
//        $str =  substr($str,0,-1);
        $params['spot'] = $spot;
        $params['display'] = $display;

        $params['state'] = 0 ;
        $line_id = db('line')->insertGetId($params);

        if($line_id > 0){
            //保存线路明细
            $detail = $data['detail'];
            foreach ($detail as $k=>$v){
                $ini[] = [
                    'origin'=>$v['origin'],
                    'destination'=>$v['destination'],
                    'origin_longitude'=>$v['origin_longitude'],
                    'origin_latitude'=>$v['origin_latitude'],
                    'destination_longitude'=>$v['destination_longitude'],
                    'destination_latitude'=>$v['destination_latitude'],
                    'price'=>$v['price'],
                    'line_id'=>$line_id
                ];
            }

            Db::name('line_detail')->insertAll($ini);

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
    //根据id获取线路详情
    public function getLineDaties(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('line')->where($params)->find();

            //线路明细
            $line_detail =  Db::name('line_detail')->where(['line_id'=>input('id')])->select();

            $data['line_detail'] =  $line_detail ;
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "线路ID不能为空"
            ];
        }
    }
    //更改线路状态
    public function DelLine(){
        if (input('?id')) {
            $params = [
                "id" => input('id'),
                'state'=>input('state')
            ];

            $line = Db::name('line')->update($params);

            if($line){
                return [
                    'code'=>APICODE_SUCCESS,
                    'msg'=>'更改成功'
                ];
            }else{
                return [
                    'code'=>APICODE_ERROR,
                    'msg'=>'更改失败'
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "线路ID不能为空"
            ];
        }
    }
    //更新线路
    public function UpdateLine(){
        $data = input('');
        $params = [
            "id" => input('?id') ? input('id') : 0,
            "city_id" => input('?city_id') ? input('city_id') : 0,
            "origin" => input('?origin') ? input('origin') : '',
            "destination" => input('?destination') ? input('destination')  : '',
            "spot" => input('?spot') ? input('spot')  : '',
            "origin_longitude" => input('?origin_longitude') ? input('origin_longitude') : '',
            "origin_latitude" => input('?origin_latitude') ? input('origin_latitude')  : '',
            "destination_longitude" => input('?destination_longitude') ? input('destination_longitude') : '',
            "destination_latitude" => input('?destination_latitude') ? input('destination_latitude')  : '',
            "start_time" => input('?start_time') ? input('start_time')  : null,
            "end_time" => input('?end_time') ? input('end_time')  : null,
            "is_display" => input('?is_display') ? input('is_display')  : null,
            "discount" => input('?discount') ? input('discount')  : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "origin", "destination"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $spot = $data['spot'] ;
        $display = $data['display'] ;

//        $str = "" ;
//        foreach ($spot as $key=>$value){
//            $str .= $value.',';
//        }
//        $str =  substr($str,0,-1);
        $params['spot'] = $spot;
        $params['display'] = $display;

        $line = Db::name('line')->update( $params );

        if($line > 0){
            //删除所有明细
            $line_detail = Db::name('line_detail')->where(['line_id'=>input('id')])->select();
            foreach ($line_detail as $key=>$value){
                    Db::name('line_detail')->where(['id'=>$value['id']])->delete();
            }

            $detail = $data['detail'];
            foreach ($detail as $k=>$v){
                $ini[] = [
                    'origin'=>$v['origin'],
                    'destination'=>$v['destination'],
                    'origin_longitude'=>$v['origin_longitude'],
                    'origin_latitude'=>$v['origin_latitude'],
                    'destination_longitude'=>$v['destination_longitude'],
                    'destination_latitude'=>$v['destination_latitude'],
                    'price'=>$v['price'],
                    'line_id'=>input('id')
                ];
            }
            Db::name('line_detail')->insertAll($ini);

            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'更新成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'更新失败'
            ];
        }
    }
    //添加协议分类
    public function agreementClassify(){
        $params = [
            "title" => input('?title') ? input('title') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["title"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $agreement_classify = Db::name('agreement_classify')->insert($params);

        if($agreement_classify){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'添加成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'添加失败'
            ];
        }
    }
    //根据id获取协议分类
    public function getAgreementClassify(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];

            $data = db('agreement_classify')->where($params)->find();

            $data['agreement'] = Db::name('agreement')->where(['agreement_classify_id'=>input('id')])->select();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "协议ID不能为空"
            ];
        }
    }
    //更新协议分类
    public function UpdateAgreementClassify(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "title" => input('?title') ? input('title') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id","title"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $res = Db::name('agreement_classify')->update($params);

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }
    //服务协议分类列表
    public function AgreementClassifyList(){
        return self::pageReturn(db('agreement_classify'), "");
    }
    //更新服务协议
    public function UpdateServeProtocol(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "content" => input('?content') ? input('content') : null,
            "title" => input('?title') ? input('title') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id","content"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $agreement = Db::name('agreement')->update( $params );

        return [
            "code" => $agreement > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }
    //删除协议
    public function DelAgreement(){
        if (input('?agreement_id')) {
            $params = [
                "id" => input('agreement_id')
            ];

            $data = Db::name('agreement')->where($params)->delete();
            if($data > 0){
                return [
                    "code" =>APICODE_SUCCESS,
                    "msg" => "删除成功",
                ];
            }else{
                return [
                    'code'=>APICODE_ERROR,
                    'msg'=>'删除失败'
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "协议ID不能为空"
            ];
        }
    }
    //车主报名列表
    public function CarownerApply(){
        return self::pageReturnStrot(db('carowner_apply'), "",'id desc');
    }
    //路线申报列表
    public function RouteDeclaration(){
        return self::pageReturnStrot(db('route_declaration'), "",'id desc');
    }
    //路线列表
    public function RouteList(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
        ];
        return self::pageReturnStrot(db('route'), $params,'id desc');
    }
    //添加路线
    public function AddRoute(){
        $data = input('');

        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "origin" => input('?origin') ? input('origin') : null,
            "destination" => input('?destination') ? input('destination')  : null,
            "origin_longitude" => input('?origin_longitude') ? input('origin_longitude')  : null,
            "origin_latitude" => input('?origin_latitude') ? input('origin_latitude')  : null,
            "destination_longitude" => input('?destination_longitude') ? input('destination_longitude')  : null,
            "destination_latitude" => input('?destination_latitude') ? input('destination_latitude')  : null,
            "start_time" => input('?start_time') ? input('start_time')  : null,
            "end_time" => input('?end_time') ? input('end_time')  : null,
            "is_display" => input('?is_display') ? input('is_display')  : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "origin", "destination"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $params['create_time'] = time();

        $spot = $data['spot'] ;
        $display = $data['display'] ;

//        $str = "" ;
//        foreach ($spot as $key=>$value){
//            $str .= $value.',';
//        }
//        $str =  substr($str,0,-1);
        $params['spot'] = $spot;
        $params['display'] = $display;

        $params['state'] = 0 ;
        $line_id = db('route')->insertGetId($params);

        if($line_id > 0){
            //保存线路明细
            $detail = $data['detail'];
            foreach ($detail as $k=>$v){
                $ini[] = [
                    'origin'=>$v['origin'],
                    'destination'=>$v['destination'],
                    'origin_longitude'=>$v['origin_longitude'],
                    'origin_latitude'=>$v['origin_latitude'],
                    'destination_longitude'=>$v['destination_longitude'],
                    'destination_latitude'=>$v['destination_latitude'],
                    'price'=>$v['price'],
                    'route_id'=>$line_id
                ];
            }

            Db::name('route_detail')->insertAll($ini);

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
    //根据id获取路线详情
    public function getRouteDaties(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('route')->where($params)->find();

            //线路明细
            $line_detail =  Db::name('route_detail')->where(['route_id'=>input('id')])->select();

            $data['line_detail'] =  $line_detail ;
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "线路ID不能为空"
            ];
        }
    }
    //更改路线状态
    public function DelRoute(){
        if (input('?id')) {
            $params = [
                "id" => input('id'),
                'state'=>input('state')
            ];

            $line = Db::name('route')->update($params);

            if($line){
                return [
                    'code'=>APICODE_SUCCESS,
                    'msg'=>'更改成功'
                ];
            }else{
                return [
                    'code'=>APICODE_ERROR,
                    'msg'=>'更改失败'
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "线路ID不能为空"
            ];
        }
    }
    //更新路线
    public function UpdateRoute(){
        $data = input('');
        $params = [
            "id" => input('?id') ? input('id') : 0,
            "city_id" => input('?city_id') ? input('city_id') : 0,
            "origin" => input('?origin') ? input('origin') : '',
            "destination" => input('?destination') ? input('destination')  : '',
            "spot" => input('?spot') ? input('spot')  : '',
            "origin_longitude" => input('?origin_longitude') ? input('origin_longitude') : '',
            "origin_latitude" => input('?origin_latitude') ? input('origin_latitude')  : '',
            "destination_longitude" => input('?destination_longitude') ? input('destination_longitude') : '',
            "destination_latitude" => input('?destination_latitude') ? input('destination_latitude')  : '',
            "start_time" => input('?start_time') ? input('start_time')  : null,
            "end_time" => input('?end_time') ? input('end_time')  : null,
            "is_display" => input('?is_display') ? input('is_display')  : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "origin", "destination"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $spot = $data['spot'] ;
        $display = $data['display'] ;

//        $str = "" ;
//        foreach ($spot as $key=>$value){
//            $str .= $value.',';
//        }
//        $str =  substr($str,0,-1);
        $params['spot'] = $spot;
        $params['display'] = $display;

        $line = Db::name('route')->update( $params );

        if($line > 0){
            //删除所有明细
            $line_detail = Db::name('route_detail')->where(['route_id'=>input('id')])->select();
            foreach ($line_detail as $key=>$value){
                Db::name('route_detail')->where(['id'=>$value['id']])->delete();
            }

            $detail = $data['detail'];
            foreach ($detail as $k=>$v){
                $ini[] = [
                    'origin'=>$v['origin'],
                    'destination'=>$v['destination'],
                    'origin_longitude'=>$v['origin_longitude'],
                    'origin_latitude'=>$v['origin_latitude'],
                    'destination_longitude'=>$v['destination_longitude'],
                    'destination_latitude'=>$v['destination_latitude'],
                    'price'=>$v['price'],
                    'route_id'=>input('id')
                ];
            }
            Db::name('route_detail')->insertAll($ini);

            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'更新成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'更新失败'
            ];
        }
    }
    //行程列表
    public function JourneyList(){
        $params = [] ;
        if (empty(input('city_id')) || input('city_id') == "null") {
            unset($params['city_id']);
        }else{
            $params['j.city_id'] = ['eq',(int)input('city_id') ] ;
        }
        if (empty(input('vehicle_id')) || input('vehicle_id') == "null") {
            unset($params['vehicle_id']);
        }else{
            $params['j.vehicle_id'] = ['eq',(int)input('vehicle_id') ] ;
        }

        if (empty(input('expected_time')) || input('expected_time') == "null") {
            unset($params['expected_time']);
        }else{
            $params['j.times'] = ['eq',strtotime(input('expected_time')) ] ;
        }

        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;
//        $params = "" ;

        $data = db('journey')->alias('j')
                                     ->field('j.*,v.VehicleNo')
                                     ->join('mx_vehicle v','v.id = j.vehicle_id','left')
                                     ->where(self::filterFilter($params))->order('j.id desc')->page($pageNum, $pageSize)
                                     ->select();

        $sum = db('journey')->alias('j')->where(self::filterFilter($params))->count();

        return [
            "code" => APICODE_SUCCESS,
            "sum" => $sum,
            "data" => $data
        ];
    }
    //添加班次
    public function AddPassengerFlights(){
        $datas = input('');

        $params = [
            "is_temporary" => input('?is_temporary') ? input('is_temporary') : null,
            "vehicle_id" => input('?vehicle_id') ? input('vehicle_id') :  null,
            "depart_time" => input('?depart_time') ? input('depart_time')  :  null,
            "expected_time" => input('?expected_time') ? input('expected_time')  :  null,
            "depart_frequency" => input('?depart_frequency') ? input('depart_frequency') :  null,
            "presell_day" => input('?presell_day') ? input('presell_day') :  null,
            "city_id" => input('?city_id') ? input('city_id') :  null,
        ];

        $params = $this->filterFilter($params);
        $required = ["is_temporary", "vehicle_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $params['create_time'] = time();

        if( !empty($datas['year']) ){ //年
            $str = "" ;
            foreach ($datas['year'] as $key=>$value){
                $str .= $value.",";
            }
            $str = substr($str,0,-1);
            $params['dates'] = $str;
        }else if(!empty( input('month') )){    //月
            $params['dates'] = input('month');
        }else{
            $params['dates'] = input('week');
        }
        //取消原因
        $ini = [] ;
        if(!empty($datas['cancelRule'])){
            foreach ($datas['cancelRule'] as $key=>$value){
                $ini[$key]=$value;
            }
            $params['cancel_rules'] = json_encode($ini);
        }
        $particulars = [] ;
        if(!empty( input('route_id') )){
            $route = Db::name('route')->where(['id' =>input('route_id') ])->find() ;
            $route_detail = Db::name('route_detail')->where(['route_id' =>input('route_id') ])->select();
            $price = 0 ;
            foreach ($route_detail as $key => $value ){
                $particulars[] = [
                    "origin" =>$value['origin'],
                    "destination" =>$value['destination'],
                    "price" =>$value['price'],
                    "origin_longitude" =>$value['origin_longitude'],
                    "origin_latitude" =>$value['origin_latitude'],
                    "destination_longitude" =>$value['destination_longitude'],
                    "destination_latitude" =>$value['destination_latitude'],
                ] ;
                if(($value['origin'] ==$route['origin']) && ($value['destination'] == $route['destination'])){
                    $price = $value['price'] ;
                }
            }
            $params['spot'] = $route['spot'];
            //路线
            $params['origin'] = $route['origin'];
            $params['destination'] = $route['destination'];
            $params['price'] = $price ;
            $params['origin_longitude'] = $route['origin_longitude'];
            $params['origin_latitude'] = $route['origin_latitude'];
            $params['destination_longitude'] = $route['destination_longitude'];
            $params['destination_latitude'] = $route['destination_latitude'];
            $params['start_time'] = $route['start_time'];
        }
        //按照车的座位数，定票数
        $seating = Db::name('vehicle')->where(['id' =>input('vehicle_id') ])->value('seating');
        $params['total_ticket'] = $seating ;

        $params['particulars'] = json_encode($particulars);

        $passenger_flights = db('passenger_flights')->insert($params);

        if($passenger_flights){
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
    //班次列表
    public function PassengerFlightsList(){

        $params = [
            "p.city_id" => input('?city_id') ? ['eq', input('city_id')] : null,
            "v.VehicleNo" => input('?number') ? ['like',"%" .input('number')."%"] : null,
        ] ;

        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;

        $data = db('passenger_flights')->alias('p')->join('mx_vehicle v','v.id = p.vehicle_id','left')
            ->join('mx_cn_city c','c.id = p.city_id','left')
            ->field('p.*,v.VehicleNo,c.name as c_name')->where(self::filterFilter($params))->page($pageNum, $pageSize)
            ->order('p.id desc')
            ->select();

        $sum = db('passenger_flights')->alias('p')->join('mx_vehicle v','v.id = p.vehicle_id','left')
            ->field('p.*,v.VehicleNo')->where(self::filterFilter($params))->count();

        return [
            "code" => APICODE_SUCCESS,
            "sum" => $sum,
            "data" => $data
        ];
//        return self::pageReturn(db('passenger_flights')->alias('p'), "");
    }
    //班次预览
    public function PassengerFlightsPreview(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "times" => input('?times') ? input('times') : null,
        ];
        $data = [] ;
         $passenger_flights = Db::name('passenger_flights')->alias('p')
                                                                     ->field('p.*,v.VehicleNo')
                                                                     ->join('mx_vehicle v','v.id = p.vehicle_id','left')
                                                                     ->where(['p.city_id' => input('city_id')])
                                                                     ->where(['p.state' => 1])
                                                                     ->select();
         //按照发车频率展示
        $times = strtotime(input('times'));
         foreach ($passenger_flights as $key=>$value){
            if( $value['depart_frequency'] == 2 ){           //每周
                $week = date("w" , $times ) ;
                if($week != $value){         //星期在里面
                    unset($passenger_flights[$key]);
                }
            }
            if( $value['depart_frequency'] == 3 ){           //每月
                $day = date('d',$times);
                $dates_month = explode(",",$value['dates']);
                foreach ($dates_month as $k=>$v){
                    if((int)$day != (int)$v){
                        unset($passenger_flights[$key]);
                    }
                }
            }
            if( $value['depart_frequency'] == 4  ){          //每年
                $year = date('m-d',$times);
                $dates_year = explode(",",$value['dates']);
                foreach ($dates_year as $kk=>$vv){
                    if($year != $vv){
                        unset($passenger_flights[$key]);
                    }
                }
            }
         }
         foreach ($passenger_flights as $k=>$v){
            $data[] = [
                "id"=>$v['id'],
                "vehicle_id"=>$v['vehicle_id'],
                "depart_time"=>$v['depart_time'],
                "expected_time"=>$v['expected_time'],
                "depart_frequency"=>$v['depart_frequency'],
                "dates"=>$v['dates'],
                "presell_day"=>$v['presell_day'],
                "is_temporary"=>$v['is_temporary'],
                "Scheduling_time"=>$v['Scheduling_time'],
                "create_time"=>$v['create_time'],
                "city_id"=>$v['city_id'],
                "state"=>$v['state'],
                "origin"=>$v['origin'],
                "destination"=>$v['destination'],
                "price"=>$v['price'],
                "origin_longitude"=>$v['origin_longitude'],
                "origin_latitude"=>$v['origin_latitude'],
                "destination_longitude"=>$v['destination_longitude'],
                "destination_latitude"=>$v['destination_latitude'],
                "total_ticket"=>$v['total_ticket'],
                "spot"=>$v['spot'],
                "particulars"=>$v['particulars'],
                "cancel_rules"=>$v['cancel_rules'],
                "VehicleNo"=>$v['VehicleNo']
            ];
         }

        return [
            'code'=>APICODE_SUCCESS,
            'msg'=>'查询成功',
            'data'=>$data
        ];
    }
    //班次停设/启动
    public function activate(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "state" => input('?state') ? input('state') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id", "state"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $passenger_flights = Db::name('passenger_flights')->update($params);

        return [
            "code" => $passenger_flights > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新状态成功",
        ];
    }
    //根据id获取班次详情
    public function GetPassengerFlightsDetails(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('passenger_flights')->where($params)->find();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "班次ID不能为空"
            ];
        }
    }
    //更新班次
    public function UpdatePassengerFlights(){
        $datas = input('');
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "id" => input('?id') ? input('id') : null,
//            "route_id" => input('?route_id') ? input('route_id')  : null,
            "vehicle_id" => input('?vehicle_id') ? input('vehicle_id')  : null,
            "depart_time" => input('?depart_time') ? input('depart_time') : null,
            "expected_time" => input('?expected_time') ? input('expected_time') : null,
            "depart_frequency" => input('?depart_frequency') ? input('depart_frequency') : null,
            "dates" => input('?dates') ? input('dates') : null,
            "presell_day" => input('?presell_day') ? input('presell_day') : null,
            "is_temporary" => input('?is_temporary') ? input('is_temporary') : null,
            "Scheduling_time" => input('?Scheduling_time') ? input('Scheduling_time') : null,
        ];
        if( !empty($datas['year']) ){
            $year = json_decode($datas['year'],true);
            $str = "" ;
            foreach ($year as $key=>$value){
                $str = $value.",";
            }
            $str = substr($str,0,-1);
            $params['dates'] = $str;
        }
        $passenger_flights = Db::name('passenger_flights')->update($params);

        return [
            "code" => $passenger_flights > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新状态成功",
        ];
    }
    //增加行程
    public function AddJourney(){
        $datas = input('');

        $params = [
            "type" => input('?type') ? input('type') : null,
            "city_id" => input('?city_id') ? input('city_id') :null,
            "times" => input('?times') ? input('times')  : null,
            "vehicle_id" => input('?vehicle_id') ? input('vehicle_id')  : null,
        ];
        $particulars =  [] ;// array('origin'=>null,'destination'=>null,'price'=>null) ;
        $prices = 0 ;
        //根据路线
        if(!empty( input('route_id') )){
            $route = Db::name('route')->where(['id' =>(int)input('route_id') ])->find() ;
            $route_detail = Db::name('route_detail')->where(['route_id' =>(int)input('route_id') ])->select();
            foreach ($route_detail as $key => $value ){
//                $particulars['origin']= $value['origin'];
//                $particulars['destination'] = $value['destination'] ;
//                $particulars['price'] = $value['price'] ;
                $particulars[] = [
                    "origin" =>$value['origin'],
                    "destination" =>$value['destination'],
                    "price" =>$value['price'],
                    "origin_longitude" =>$value['origin_longitude'],
                    "origin_latitude" =>$value['origin_latitude'],
                    "destination_longitude" =>$value['destination_longitude'],
                    "destination_latitude" =>$value['destination_latitude'],
                ] ;
                if($value['origin']==$route['origin'] && $value['destination']==$route['destination']){
                    $prices = floatval($value['price']) ;
                }
            }

            $params['spot'] = $route['spot'];
            //路线
            $params['origin'] = $route['origin'];
            $params['destination'] = $route['destination'];

            $params['origin_longitude'] = $route['origin_longitude'];
            $params['origin_latitude'] = $route['origin_latitude'];
            $params['destination_longitude'] = $route['destination_longitude'];
            $params['destination_latitude'] = $route['destination_latitude'];
            $params['start_time'] = $route['start_time'];
        }
        //取消原因
        $ini = [] ;
        if(!empty($datas['cancelRule'])){
            foreach ($datas['cancelRule'] as $key=>$value){
                $ini[$key]=$value;
            }
            $params['cancel_rules'] = json_encode($ini);
        }
        $params['particulars'] = json_encode($particulars);
        //按照车的座位数，定票数
        $seating = Db::name('vehicle')->where(['id' =>input('vehicle_id') ])->value('seating');
        $params['total_ticket'] = $seating ;
        $params['residue_ticket'] = $seating ;
        $params['price'] = $prices ;

        $journey = Db::name('journey')->insert($params) ;
        if($journey ){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'创建成功',
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'创建失败'
            ];
        }
    }
    //根据id获取行程详情
    public function GetJourneyDetails(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('journey')->where($params)->find();
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

    //修改行程
    public function UpdateJourney(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "times" => input('?times') ? input('times')  : null,
            "vehicle_id" => input('?vehicle_id') ? input('vehicle_id')  : null,
        ];

        $journey = Db::name('journey')->update($params);

        return [
            "code" => $journey > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }

    //取消行程
    public function CancelJourney(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "status" => input('?status') ? input('status') : null,
        ];

        //查询自订单存在1,2.3
//        $journey_order = Db::name('journey_order')->where(['journey_id'=>input('id')])->where('status','in','1,2,3')->select();
//        if(!empty($journey_order)){
//            return [
//                "code" => APICODE_ERROR,
//                "msg" => "有没结束的订单,不能取消行程",
//            ];
//        }

        $journey = Db::name('journey_order')->update($params);

        //取消行程订单，恢复票
        $journey_order = Db::name('journey_order')->where(['id'=>input('id')])->find() ;

        Db::name('journey')->where(['id'=>$journey_order['journey_id']])->setInc('residue_ticket',$journey_order['ticket_count']) ;

        if($journey > 0){
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
    }

    //取消行程
    public function CancelJourneys(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "status" => input('?status') ? input('status') : null,
        ];

        $journey = Db::name('journey')->update($params);

        if($journey > 0){
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
    }

    //行程订单列表
    public function JourneyOrderList(){
        $datas = input('') ;
        $params = [
            "j.city_id" => input('?city_id') ? ['eq',input('city_id')] : null,
            "j.price" => input('?price') ? ['egt',input('price')] : null,
        ];

        $where = []  ; $where1 = [] ;
        if(!empty(input('pay_time')) && input('pay_time') != "null" ){
            $pay_time = explode("," , input('pay_time') ) ;
            $start_time = strtotime($pay_time[0]." 00:00:00") ;
            $end_time = strtotime($pay_time[1]." 23:59:59") ;

            $where['j.pay_time'] = ['gt', $start_time ] ;
            $where1['j.pay_time'] = ['lt', $end_time ] ;
        }
        $where3 = []  ; $where4 = [] ;
        if(!empty(input('create_time')) && input('create_time') != "null" ){
            $create_time = explode("," , input('create_time') )  ;
            $start_time = strtotime($create_time[0]." 00:00:00") ;
            $end_time = strtotime($create_time[1]." 23:59:59") ;

            $where3['j.create_time'] = ['gt', $start_time ] ;
            $where4['j.create_time'] = ['lt', $end_time ] ;
        }
        $where5 = []  ; $where6 = [] ;
        if(!empty(input('conducteur_name')) && input('conducteur_name') != "null" ){
            $where5['c.DriverName'] = ['like', "%".input('conducteur_name')."%" ] ;
        }
        if(!empty(input('conducteur_name')) && input('conducteur_name') != "null" ){
            $where6['c.DriverPhone'] = ['like', "%".input('conducteur_phone')."%" ] ;
        }
        $str = "" ; $where2 = [] ;
        $status = $datas['status'] ;
        if(!empty($status)){
            foreach ($status as $key=>$value){
                $str .= $value."," ;
            }
            $str = substr($str,0,-1);

            $where2['j.status'] = ['in',$str] ;
        }

        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;
        $sortBy = input('?orderBy') ? input('orderBy') : "id desc";

        $data = Db::name('journey_order')->alias('j')
                ->field('j.*,c.id as conducteur_id,c.DriverName,c.DriverPhone,u.nickname,u.PassengerPhone,jo.times,jo.origin,jo.destination')
                ->join('mx_journey jo','jo.id = j.journey_id','left')
                ->join('mx_vehicle_binding v','v.vehicle_id = jo.vehicle_id','left')
                ->join('mx_conducteur c','c.id = v.conducteur_id','left')
                ->join('mx_user u','u.id = j.user_id','left')
                ->where(self::filterFilter($params))
                ->where($where)->where($where1)->where($where3)->where($where4)->where($where5)->where($where6)->where($where2)->order($sortBy)->page($pageNum, $pageSize)
                ->select();

        foreach ($data as $key=>$value){
            //获取乘车人列表
            $data[$key]['jorder_passenger'] = Db::name('jorder_passenger')->where(['journey_order_id'=>$value['id']])->select() ;
        }

        $sum = Db::name('journey_order')->alias('j')
            ->field('j.*,c.id as conducteur_id,c.DriverName,c.DriverPhone')
            ->join('mx_journey jo','jo.id = j.journey_id','left')
            ->join('mx_vehicle_binding v','v.vehicle_id = jo.vehicle_id','left')
            ->join('mx_conducteur c','c.id = v.conducteur_id','left')
            ->where(self::filterFilter($params))->where($where)->where($where1)->where($where2)
            ->where($where3)->where($where4)->where($where5)->where($where6)->count();

        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" => $data
        ];
    }

    //包车列表
    public function CharteredList()
    {
        $params = [
            "phone" => input('?phone') ? ['like','%'.input('phone').'%'] : null,
            "origin" => input('?origin') ? ['like','%'.input('origin').'%'] : null,
            "destination" => input('?destination') ? ['like','%'.input('destination').'%'] : null,
            "Departure_time" => input('?Departure_time') ? ['eq',input('Departure_time')] : null,
        ];
        return self::pageReturnStrot(db('chartered'), $params,'id desc');
    }

    //常见问题列表
    public function QuestionsList(){
        $questions = Db::name('questions')->select() ;
        return [
            "code" => APICODE_SUCCESS,
            "msg" => "查询成功",
            "data" => $questions
        ];
    }

    //客运车辆列表
    public function PassengerTrafficList(){
//        if (input('?time')) {
            $params = [
                "j.times" => ['neq',input('time')]
            ];
            $data = db('vehicle')->alias('v')
                    ->field("v.*")
//                    ->join('journey j','j.vehicle_id = v.id','left')
//                    ->where($params)
                    ->where('v.passenger','eq',1)
                    ->select();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
//        } else {
//            return [
//                "code" => APICODE_FORAMTERROR,
//                "msg" => "发车时间不能为空"
//            ];
//        }
    }

    //客运首页
    public function  PassengerTrafficHome(){

        $where1 = [];
        $where2 = [];
        $where3 = [];
        $where4 = [];
        $where5 = [];
        $wheres = [];
        $whered = [];
        if (!empty(input('company_id'))) {
            $where4['u.city_id'] = ['eq', input('city_id')];
            $where5['r.city_id'] = ['eq', input('city_id')];
        }
        //如果俩个时间都为空，表示是当天
        $start_time = strtotime(date("Y-m-d"), time());
        $end_time = strtotime($start_time." 23:59:59");

        if (!empty(input('start_time'))) {
            $start_time = strtotime(input('start_time'));
        }
        if (!empty(input('end_time'))) {
            $end_time = strtotime(input('end_time')." 23:59:59");
        }

        $where = ["status" => ["in", "6,9,12,3,4"],"pay_time" => ['egt', $start_time]];
        $wheres['r.pay_time'] = ['egt', $start_time];
        $where1['pay_time'] = ['elt', $end_time];
        $whered['r.pay_time'] = ['elt', $end_time];
        //入账金额
        $order_money = Db::name('journey_order')->where($where)->where($where1)->where($where2)->value('sum(price)');                            //订单入账

        if (empty($order_money)) {
            $order_money = 0;
        }

        //endregion
        //入账总金额
        $sum_money = sprintf("%.2f", ($order_money + 0 + 0));

        //订单入账-支付宝和微信
        $order_wechat_money = Db::name('journey_order')->where($where)->where($where1)->where($where2)->value('sum(price)');       //订单微信
//        halt($order_alipay_money);

        if (empty($order_money)) {
            $order_money = 0;
        }
        if (empty($order_wechat_money)) {
            $order_wechat_money = 0;
        }
        if (empty($order_alipay_money)) {
            $order_alipay_money = 0;
        }
        if (empty($recharge_wechat_money)) {
            $recharge_wechat_money = 0;
        }
        if (empty($recharge_alipay_money)) {
            $recharge_alipay_money = 0;
        }
        if (empty($recharge_enterprise_wechat_money)) {
            $recharge_enterprise_wechat_money = 0;
        }
        if (empty($recharge_enterprise_alipay_money)) {
            $recharge_enterprise_alipay_money = 0;
        }

        $data['recorded'] = [
            'sum_money' => $sum_money,
            'order_money' => $order_money,
            'recharge_money' => sprintf("%.2f", 0 + 0),
            'order' => [
                'order_wechat_money' => $order_wechat_money,
                'order_alipay_money' => $order_alipay_money,
            ],
            'recharge' => [
                'recharge_wechat_money' => sprintf("%.2f", (sprintf("%.2f", $recharge_wechat_money) + sprintf("%.2f", $recharge_enterprise_wechat_money))),
                'recharge_alipay_money' => sprintf("%.2f", (sprintf("%.2f", $recharge_alipay_money) + sprintf("%.2f", $recharge_enterprise_alipay_money))),
            ]
        ];

        //流水
        $flow_order_money = Db::name('journey_order')->where($where)->where($where1)->where($where2)->value('sum(price)');                                           //流水订单入账

        $flow_sum_money = sprintf("%.2f", ($flow_order_money + 0));                                                                     //流水入账总金额

        //流水订单-支付宝和微信
        $flow_order_wechat_money = Db::name('journey_order')->where($where)->where($where1)->where($where2)->value('sum(price)');       //订单微信

        if (empty($flow_order_money)) {
            $order_money = 0;
        }
        if (empty($flow_order_wechat_money)) {
            $flow_order_wechat_money = 0;
        }
        if (empty($flow_order_alipay_money)) {
            $flow_order_alipay_money = 0;
        }
        if (empty($flow_recharge_wechat_money)) {
            $flow_recharge_wechat_money = 0;
        }
        if (empty($flow_recharge_alipay_money)) {
            $flow_recharge_alipay_money = 0;
        }
        $data['flow'] = [
            'flow_sum_money' => $flow_sum_money,
            'flow_order_money' => $flow_order_money,
            'recharge_money' => sprintf("%.2f", 0),
            'flow_order' => [
                'flow_order_wechat_money' => $flow_order_wechat_money,
                'flow_order_alipay_money' => $flow_order_alipay_money,
                'flow_order_balance_money' => 0,
                'flow_order_discounts_money' => 0,
            ],
            'flow_recharge' => [
                'flow_recharge_wechat_money' => $flow_recharge_wechat_money,
                'flow_recharge_alipay_money' => $flow_recharge_alipay_money,
            ]
        ];

        $company = Db::name('company')->alias('c')
            ->field('c.id,c.CompanyName')
            ->where($where3)
            ->where(['id'=>236])
            ->select();

        foreach ($company as $key => $value) {
            $money = Db::name('journey_order')->where('status', 'in', '6,8,9,12,3,4')->where($where)->where($where1)->sum('price');
            if (empty($money)) {
                $money = 0;
            }

            $company[$key]['money'] = $money  ;                                  //订单流水总额

            $company[$key]['discounts_money'] = 0;           //订单优惠总额
            $wechat_third_party_money = Db::name('journey_order')->where('status', 'in', '6,8,9,12,3,4')->where($where)->where($where1)->sum('price');
            if (empty($wechat_third_party_money)) {
                $wechat_third_party_money = 0;
            }
            $company[$key]['wechat_third_party_money'] = $wechat_third_party_money;           //微信支付总额
            $alipay_third_party_money = Db::name('journey_order')->where('status', 'in', '6,8,9,12,3,4')->where($where)->where($where1)->sum('price');
            if (empty($alipay_third_party_money)) {
                $alipay_third_party_money = 0;
            }
            $company[$key]['alipay_third_party_money'] = 0;           //支付宝支付总额
            $third_party_money = Db::name('journey_order')->where('status', 'in', '6,8,9,12,3,4')->where($where)->where($where1)->sum('price');
            if (empty($third_party_money)) {
                $third_party_money = 0;
            }
            $company[$key]['third_party_money'] = $third_party_money;                          //第三方支付总额
            $balance_payment_money = Db::name('journey_order')->where('status', 'in', '6,8,9')->where($where)->where($where1)->sum('price');
            if (empty($balance_payment_money)) {
                $balance_payment_money = 0;
            }
            $company[$key]['balance_payment_money'] = 0;                          //订单余额支付总额
            $parent_company_money = Db::name('journey_order')->where('status', 'in', '6,8,9')->where($where)->where($where1)->sum('price');
            if (empty($parent_company_money)) {
                $parent_company_money = 0;
            }

            $company[$key]['parent_company_money'] = 0;                          //总公司抽成金额
//            $superior_company_money = Db::name('journey_order')->where('status', 'in', '6,8,9')->where($where)->where($where1)->sum('superior_company_money');
            if (empty($superior_company_money)) {
                $superior_company_money = 0;
            }
            $company[$key]['superior_company_money'] = 0;                          //上级分公司抽成金额
//            $filiale_company_money = Db::name('journey_order')->where('status', 'in', '6,8,9')->where($where)->where($where1)->sum('filiale_company_money');
            if (empty($filiale_company_money)) {
                $filiale_company_money = 0;
            }
            $company[$key]['filiale_company_money'] = 0;                          //分公司抽成金额
//            $filiale_company_settlement = Db::name('journey_order')->where('status', 'in', '6,8,9')->where($where)->where($where1)->sum('filiale_company_settlement');
            if (empty($filiale_company_settlement)) {
                $filiale_company_settlement = 0;
            }
            $company[$key]['filiale_company_settlement'] = 0;                          //分公司结算金额
        }

        $data['company'] = $company;

        return ['code' => APICODE_SUCCESS, 'data' => $data, 'start_time' => input('start_time'), 'end_time' => input('end_time')];
    }

    //退票
    public function  UserRefunds(){

        require_once "extend/traffic_w_pay/lib/WxPay.Api.php";
        require_once "extend/traffic_w_pay/example/WxPay.Config.php";
        $input = new \WxPayRefund();
        $config = new \WxPayConfig();
        $refund = new  \WxPayApi();//\WxPayApi();

        $journey_order = Db::name('journey_order')->alias('j')
            ->field('j.*')
            ->where(['j.id' => input('journey_order_id')])
            ->find();

       Db::name('journey_order')->where(['id'=>$journey_order['id']])->update(['status'=>5]);

       $jorder_passenger = Db::name('jorder_passenger')->where(['journey_order_id'=>input('journey_order_id')])->select();

//       foreach ($jorder_passenger as $key=>$value){
           Db::name('jorder_passenger')->where(['id'=>input('jorder_passenger_id')])->update(['is_accepted'=>5]);

           $flags = $this->Refund(3,$journey_order['transaction_id'],$journey_order['id'],input('jorder_passenger_id'),$input,$config,$refund);
           if($flags == 1){    //退款成功
               Db::name('jorder_passenger')->where(['id'=>input('jorder_passenger_id')])->update(['is_accepted'=>5]);
           }
//       }

        //还原票数
        Db::name('journey')->where(['id'=>$journey_order['journey_id']])->setInc('residue_ticket',$journey_order['ticket_count']) ;

        return [
            "code" => APICODE_SUCCESS,
            "msg" => "退票成功"
        ];
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

    //小程序更新版本
    public function AppletUpdate(){
        if (input('?versionCode')) {
            $params = [
                "versionCode" => input('versionCode'),
                "versionName"=>input('versionName'),
                "id"=>4
            ];
            $res = db('versions_record')->update($params) ;
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
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "版本号不能为空"
            ];
        }
    }

    //查看版本号
    public function QueryVersionNumber(){

        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('versions_record')->where($params)->find();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "ID不能为空"
            ];
        }
    }
}