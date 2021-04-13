<?php


namespace app\partner\controller;

use app\backstage\controller\Gps;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Request;

class Order extends Base
{
    public function callback(){
        $data = input('') ;
        $params = [
            "channel" => input('?channel') ? input('channel') : null,
            "timestamp" => input('?timestamp') ? input('timestamp') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "eventCode" => input('?eventCode') ? input('eventCode') : null,
            "bill" => input('?bill') ? input('bill') : null,
            "carInfo" => input('?carInfo') ? input('carInfo') : null,
            "driverInfo" => input('?driverInfo') ? input('driverInfo') : null,
            "eventTime" => input('?eventTime') ? input('eventTime') : null,
            "mtOrderId" => input('?mtOrderId') ? input('mtOrderId') : null,
            "partnerOrderId" => input('?partnerOrderId') ? input('partnerOrderId') : null,
            "product" => input('?product') ? input('product') : null,
            "status" => input('?status') ? input('status') : null,
        ];

        //更改订单信息
        $bill = json_decode( $data['bill'] )  ;
        $money = 0 ;
        foreach ($bill as $key => $value){
            $money = $value['totalPrice'] ;
        }
        $carInfo = json_decode($data['carInfo'])  ;
        $chargeInfo =  json_decode($data['chargeInfo'])  ;
        $driverInfo = json_decode($data['driverInfo'])  ;

        foreach ($driverInfo as $k=>$v){
            $ini['conducteur_id'] = $v['partnerDriverId'] ;
            $ini['conducteur_phone'] = $v['driverMobile'] ;
            $ini['conducteur_name'] = $v['driverName'] ;
        }

        //更新订单信息
        $order_id = Db::name('order')->where(['OrderId' =>input('mtOrderId') ])->value('order_id') ;

        $ini['id'] = $order_id;
        $ini['money'] = $money;
        $ini['mt_status'] = input('status');

        Db::name('order')->update($ini) ;

        return [
            'result' => 0,
            'message' => 'SUCCESS',
        ];
    }
}