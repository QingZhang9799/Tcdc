<?php

namespace app\voicebox\controller;
use app\backstage\controller\Gps;
use think\Cache;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Request;

class Voice extends Base
{
    //获取用户信息
    public function checkUserInfo(){
        $params = [
            "tel" => input('?tel') ? input('tel') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["tel"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $data = Db::name('user')->field('id,status')->where(['PassengerPhone'=>input('tel')])->find();

        return [
            'responseCode' => APICODE_SUCCESS,
            'message' => 'SUCCESS',
            'data'=>$data
        ];
    }

    //创建订单
    public function sVoiceInfo(){
        $params = [
            "tel" => input('?tel') ? input('tel') : null,
            "voice" => input('?voice') ? input('voice') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["tel","voice"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        //通过手机找用户
        $user = Db::name('user')->where(['PassengerPhone'=>input('tel')])->find() ;
        unset($params['tel']);
        $params['user_id'] = $user['id'] ;
        $params['user_phone'] = $user['PassengerPhone'] ;
        $params['user_name'] = $user['nickname'] ;

        $order = Db::name('order')->insertGetId($params) ;

        if($order){
            return [
                'responseCode' => APICODE_SUCCESS,
                'message' => 'SUCCESS',
                'order' => $order,
            ];
        }else{
            return [
                'responseCode' => 0,
                'message' => 'SUCCESS',
            ];
        }
    }

    //获取订单信息
    public function selectStatusInfo(){
        if (input('?orderId')) {
            $params = [
                "id" => input('orderId')
            ];
            $data = db('order')->where($params)->find();
            $ini = [] ;
            $Model = "" ;
            //车辆型号 - 匹配完车辆
            if( !empty($data['conducteur_id']) ){
                $conducteur_id = $data['conducteur_id'] ;
                $vehicle_id =  Db::name('vehicle_binding')->where(['conducteur_id'=>$conducteur_id])->value('vehicle_id') ;
                $VehicleNo = Db::name('vehicle')->where(['id'=>$vehicle_id])->value('VehicleNo') ;
            }
            $ini['id'] = input('orderId') ;
            $ini['conducteur_id'] = $data['conducteur_id'] ;
            $ini['VehicleNo'] = $VehicleNo ;
            return [
                "reponseCode" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $ini
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //取消订单
    public function cancel(){
        if (input('?orderId')) {
            $params = [
                "id" => input('orderId'),
                "status"=>5
            ];
            $res = db('order')->update($params) ;
            if($res > 0){
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
}