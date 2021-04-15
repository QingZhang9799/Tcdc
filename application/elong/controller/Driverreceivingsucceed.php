<?php

namespace app\partner\controller;
use app\backstage\controller\Gps;
use think\Cache;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Request;

class Driverreceivingsucceed extends Base
{
    public function index()
    {
        $params = [
            "tcOrderStatus" => input('?tcOrderStatus') ? input('tcOrderStatus') : null,
            "supplierCode" => input('?supplierCode') ? input('supplierCode') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "clientId" => input('?clientId') ? input('clientId') : null,

            "driverName" => input('?driverName') ? input('driverName') : null,              //司机姓名
            "driverTel" => input('?driverTel') ? input('driverTel') : null,                 //司机电话
            "carNo" => input('?carNo') ? input('carNo') : null,                               //车牌号
            "carType" => input('?carType') ? input('carType') : null,                        //车辆名称
            "carColor" => input('?carColor') ? input('carColor') : null,                    //车辆颜色
            "orderId" => input('?orderId') ? input('orderId') : null,                        //供应商订单号
            "tcOrderNo" => input('?tcOrderNo') ? input('tcOrderNo') : null,                 //同程订单号
            "driverPhoneReal" => input('?driverPhoneReal') ? input('driverPhoneReal') : null,                 //司机真实手机号
            "driverLevel" => input('?driverLevel') ? input('driverLevel') : null,                 //司机星级（如：4.5   5.0）
            "driverLat" => input('?driverLat') ? input('driverLat') : null,                 //司机纬度
            "driverLng" => input('?driverLng') ? input('driverLng') : null,                 //司机经度
            "hasVirtual" => input('?hasVirtual') ? input('hasVirtual') : null,              //是否虚拟接单（1是  0否）
        ];

        $params = $this->filterFilter($params);
        $required = ["tcOrderStatus"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //根据接单失败，刷新数据库
        $ini['conducteur_name'] = input('driverName') ;
        $ini['conducteur_phone'] = input('driverTel') ;
        //根据车牌号获取车辆id
        $vehicle_id = Db::name('vehicle')->where(['Model'=>input('carNo')])->value('id') ;
        $ini['id'] = input('tcOrderNo') ;
        $ini['mtorderid'] = input('tcOrderNo') ;
        Db::name('order')->update($ini) ;

        return [
            'result' => 0,
            'message' => 'SUCCESS',
        ];
    }

}