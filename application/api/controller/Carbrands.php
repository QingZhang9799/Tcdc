<?php

namespace app\api\controller;
use app\api\model\Conducteur;
use app\api\model\Company;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;

class Carbrands extends Base
{
    public function getBrands()
    {
        $data = db("car_brands")->select();
        return [
            "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "查询成功",
            "data" => $data
        ];
    }

    public function getCarFctByBrandId()
    {
        if (input('?id')) {
            $params = [
                "brandid" => input('id')
            ];
            $data = db("car_fct")->where($params)->select();
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

    public function getCarSeriesByFctId()
    {
        if (input('?id')) {
            $params = [
                "fctid" => input('id')
            ];
            $data = db("car_series")->where($params)->select();
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

    public function getCarsBySeriesId()
    {
        if (input('?id')) {
            $params = [
                "seriesid" => input('id')
            ];
            $data = db('cars')->where($params)->find();
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

    //获取车牌字母
    public function licenseLetter(){
        if (input('?id')) {
            $params = [
                "superior" => input('id')
            ];
            $data = db('platenumber')->where($params)->select();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "车牌简称ID不能为空"
            ];
        }
    }

    //获取车牌省简称
    public function ProvinceAbbreviation(){
        $data = db("platenumber")->where(['superior'=>0])->select();
        return [
            "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "查询成功",
            "data" => $data
        ];
    }

    //实时刷新订单
    public function RealtimeRefreshOrder(){
        if (input('?conducteur_id')) {
            $params = [
                "conducteur_id" => input('conducteur_id'),
                "status" => 2,
                "classification" => '实时',
            ];
            //刷新实时单
            $data = db('order')->field('id,origin,Destination,money,classification,DepLongitude,DepLatitude,DestLongitude,DestLatitude,conducteur_virtual,user_virtual,user_phone')
                ->where($params)
//                ->where('is_type','eq',0)
                ->find();

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

    //代驾-信息补充
    public function FurtherInformation(){
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null ,
            "vehicle_brand" => input('?vehicle_brand') ? input('vehicle_brand') : null ,
            "vehicle_number" => input('?vehicle_number') ? input('vehicle_number')  : null ,
            "sex" => input('?sex') ? input('sex')  : null ,
            "gears" => input('?gears') ? input('gears')  : null ,
            "is_mileage" => input('?is_mileage') ? input('is_mileage')  : null ,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id", "vehicle_brand", "vehicle_number"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $ini['id'] = input('order_id') ;
        $ini['status'] = 11 ;
        $ini['vehicle_brand'] = input('vehicle_brand') ;
        $ini['vehicle_number'] = input('vehicle_number') ;
        $supplement = [
            'sex'=>input('sex'),
            'gears'=>input('gears'),
            'is_mileage'=>input('is_mileage'),
        ];
        $ini['supplement'] = json_encode($supplement) ;
        $res = Db::name('order')->update($ini) ;

        if($res > 0){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'补充成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'补充失败'
            ];
        }
    }

}