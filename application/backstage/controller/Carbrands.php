<?php

namespace app\backstage\controller;
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
}