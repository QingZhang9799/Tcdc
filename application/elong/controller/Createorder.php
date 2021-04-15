<?php

namespace app\partner\controller;
use app\backstage\controller\Gps;
use think\Cache;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Request;

class Createorder extends Base
{
    public function index()
    {
        $params = [
            "tcOrderStatus" => input('?tcOrderStatus') ? input('tcOrderStatus') : null,
            "supplierCode" => input('?supplierCode') ? input('supplierCode') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "clientId" => input('?clientId') ? input('clientId') : null,

            "timestamp" => input('?timestamp') ? input('timestamp') : null,                       //请求时间，Unix Timestamp单位秒
            "order_id" => input('?order_id') ? input('order_id') : null,                          //同程订单ID
            "slon" => input('?slon') ? input('slon') : null,                                        //出发地经度
            "slat" => input('?slat') ? input('slat') : null,                                        //出发地纬度
            "sname" => input('?sname') ? input('sname') : null,                                    //出发地名称
            "saddress" => input('?saddress') ? input('saddress') : null,                          //出发地详细地址
            "dlon" => input('?dlon') ? input('dlon') : null,                                        //目的地经度
            "dlat" => input('?dlat') ? input('dlat') : null,                                        //目的地纬度
            "dname" => input('?dname') ? input('dname') : null,                                     //目的地名称
            "daddress" => input('?daddress') ? input('daddress') : null,                           //目的地详细地址
            "service_id" => input('?service_id') ? input('service_id') : null,                    //服务类型
            "estimate_id" => input('?estimate_id') ? input('estimate_id') : null,        //预估价格和一口价标识
            "passenger_mobile" => input('?passenger_mobile') ? input('passenger_mobile') : null, //乘客手机号，下单人就是乘坐人
            "passenger_name" => input('?passenger_name') ? input('passenger_name') : null,       //乘客姓名（50字符以内）
            "passenger_country_code" => input('?passenger_country_code') ? input('passenger_country_code') : null, //乘客手机号国 家代码，默认 为+86
            "ride_type" => input('?ride_type') ? input('ride_type') : null, //运力类型
            "city_code" => input('?city_code') ? input('city_code') : null, //城市code，默认为出发地所在城市
            "departure_time" => input('?departure_time') ? input('departure_time') : null, //出发时间，Unix timestamp，单位秒（S），默认为当前时
            "driver_message" => input('?driver_message') ? input('driver_message') : null, //给司机留言的内容，留言内容通过base64编码以后不超过92字符（约69字短信）
            "flight_no" => input('?flight_no') ? input('flight_no') : null, //航班号，接机单必填
            "flight_date" => input('?flight_date') ? input('flight_date') : null, //航班起飞日期，格式yyyy-MM-dd，接机单必填
        ];

        $params = $this->filterFilter($params);
        $required = ["tcOrderStatus"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //创建订单
        $ini['mtorderid'] = input('order_id') ;
        $ini['DepLongitude'] = input('slon') ;
        $ini['DepLatitude'] = input('slat') ;
        $ini[''] = input('sname') ;
        $ini[''] = input('slon') ;
        $ini[''] = input('slon') ;
        $ini[''] = input('slon') ;
        $ini[''] = input('slon') ;
        $ini[''] = input('slon') ;
        $ini[''] = input('slon') ;
        $ini[''] = input('slon') ;

        return [
            'result' => 0,
            'message' => 'SUCCESS',
        ];
    }

}