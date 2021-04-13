<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/31
 * Time: 16:44
 */

namespace app\backstage\controller;

use think\Db;

class Distribution extends Base
{
    //司机列表
    public function chauffeurList(){
        $params = [
            "c.city_id" => input('?city_id') ? input('city_id') : null,
            "c.DriverName" => input('?DriverName') ?  ['like','%'.input('DriverName').'%'] : null,
            "c.DriverPhone" => input('?DriverPhone') ? ['like','%'.input('DriverPhone').'%'] : null,
            "c.number" => input('?number') ?  ['like','%'.input('number').'%'] : null,
            "c.distribution_state" => input('?distribution_state') ? input('distribution_state') : null,
            "c.company_id" => input('?company_id') ? input('company_id') : null,
        ];

        $db = db('conducteur c')->field('c.*') ;
        $stort = "c.id desc" ;

        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;
        $sortBy=input('?orderBy') ? input('orderBy') : $stort;

        $data = $db->where(self::filterFilter($params))->order($sortBy)->page($pageNum, $pageSize)
                ->select();
        foreach ($data as $key=>$value){
            $recommed_count = Db::name('conducteur_recommend')->where(['conducteur_id'=>$value['id']])->count();
            $data[$key]['re_count'] = $recommed_count ;
        }
        $sum = $db->where(self::filterFilter($params))->count();

        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" => $data
        ];
    }

    //已推荐列表
    public function recommendedList(){
        if (input('?conducteur_id')) {
            $params = [
                "distribution_id" => input('conducteur_id')
            ];
            $data = db('user')->field('id,PassengerName,PassengerPhone')->where($params)->select();
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

    //根据id获取司机详情
    public function getConducteurDetails(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
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

    //认证操作
    public function AuthenticationOperation(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "distribution_state" => input('?distribution_state') ? input('distribution_state') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id","distribution_state"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $conducteur = Db::name('conducteur')->update($params);

        return [
            "code" => $conducteur > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }

    //提现列表
    public function WithdrawList(){
        $params = [
            "company_id" => input('?company_id') ? input('company_id') : null,
            "DriverName" => input('?DriverName') ?  ['like','%'.input('DriverName').'%'] : null,
            "DriverPhone" => input('?DriverPhone') ? ['like','%'.input('DriverPhone').'%'] : null,
            "create_time" => input('?create_time') ?  ['gt',input('create_time')] : null,
            "state" => input('?state') ? input('state') : null,
        ];
        return self::pageReturn(db('conducteur_distribution_withdraw'), $params);
    }

    //提现处理
    public function WithdrawalProcessing(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "state" => input('?state') ? input('state') : null,
            "cause" => input('?cause') ? input('cause') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id","state"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $str = "" ;

        if(input('state') == 1){            //通过
            $str = "通过" ;
        }else if(input('state') == 3){     //拒绝
            $str = "拒绝" ;
        }

        $conducteur_distribution_withdraw = Db::name('conducteur_distribution_withdraw')->update($params);

        return [
            "code" => $conducteur_distribution_withdraw > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => $str."成功",
        ];
    }
}