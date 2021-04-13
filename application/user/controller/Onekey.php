<?php

namespace app\user\controller;

use app\api\model\Conducteur;
use app\api\model\Company;
use app\backstage\controller\Gps;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Config;

class Onekey extends Base
{
    //获取车型列表
    public function QuerybusinessList(){
        if (input('?business_id')) {
            $params = [
                "id" => input('business_id')
            ];

            $id = Db::name('company')->where(['city_id'=>input('city_id')])->value('id') ;
            $rates = [] ;
            if(input('business_id') != 7){
                $rates = Db::name('company_rates')->alias('c')
                    ->field('t.id,t.title,t.img')
                    ->join('mx_business_type t','t.id =c.businesstype_id','left')
                    ->where(['c.company_id'=>$id,'c.business_id'=>input('business_id')])
                    ->select() ;
            }
            if(input('business_id') == 7){
                $img = Db::name('business')->where(['id'=>input('business_id')])->value('img') ;
                $rates[] = [
                    'id'=>5,
                    'title'=>"出租车",
                    'img'=>$img,
                ];
            }
            if(input('business_id') == 10) {
                $img = Db::name('business')->where(['id' => input('business_id')])->value('img');
                $rates[] = [
                    'id' => 6,
                    'title' => "代驾",
                    'img' => $img,
                ];
            }
            return [
                "code" => $rates ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $rates
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "业务ID不能为空"
            ];
        }
    }

    //起点判断是否为城外
    public function OriginJudgeCountry(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null ,
            "lng" => input('?lng') ? input('lng') : null ,
            "lat" => input('?lat') ? input('lat') : null ,
            "business_id" => input('?business_id') ? input('business_id')  : null ,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "origin", "business_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $company = Db::name('company')->where(['city_id'=>input('city_id')])->find();
        $flag =  0 ;
        $is_scope = 0 ;
        if($company['is_scope'] == 1){          //是否范围
            $flag = 1 ;
            $company_scope = Db::name('company_scope')->where(['company_id'=>$company['id'],'business_id'=>input('business_id')])->select() ;
            //用户起点
            $orgin_location = [
                'lng'=>input('lng'),
                'lat'=>input('lat'),
            ];
            $is_scope = $this->calculatescope($company_scope,$orgin_location);
        }

        return [
            'code'=>APICODE_SUCCESS,
            'msg'=>'成功',
            'flag'=>$flag,
            'is_scope'=>$is_scope,
        ];
    }

    //判断起点和终点是否城内或者城外或者超出城外
    private function calculatescope($company_scopes,$orgin_locations){
        $flags = 0 ;
        $company_muang = explode('-',$company_scopes[0]['scope']) ;           //城内

        $company_town = explode('-',$company_scopes[1]['scope']) ;           //城外

        $muang = [] ;
        $town = [] ;

        //城内
        foreach ($company_muang as $key=>$value){
            $s = explode(',',$value) ;
            $muang[] = [
                'lng'=>floatval($s[0]),
                'lat'=>floatval($s[1]),
            ];
        }
        //城外
        foreach ($company_town as $k=>$v){
            $w = explode(',',$v) ;
            $town[] = [
                'lng'=>floatval($w[0]),
                'lat'=>floatval($w[1]),
            ];
        }

        $cn =  $this->isPointInPolygon($muang,$orgin_locations);
        $cw = $this->isPointInPolygon($town,$orgin_locations) ;

        //不在城内,在城外
        if($cn == false&&$cw){
            $flags = 1 ;
        }
    }

    // 判断点 是否在多边形 内
    private function isPointInPolygon($polygon,$lnglat)
    {
        $count = count($polygon);
        $px = $lnglat['lat'];
        $py = $lnglat['lng'];
        $flag = FALSE;
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i, $i++) {
            $sy = $polygon[$i]['lng'];
            $sx = $polygon[$i]['lat'];
            $ty = $polygon[$j]['lng'];
            $tx = $polygon[$j]['lat'];
            if ($px == $sx && $py == $sy || $px == $tx && $py == $ty)
                return TRUE;
            if ($sy < $py && $ty >= $py || $sy >= $py && $ty < $py) {
                $x = $sx + ($py - $sy) * ($tx- $sx) / ($ty-$sy); if ($x == $px) return TRUE; if ($x > $px)
                    $flag = !$flag;
            }
        }
        return $flag;
    }
}