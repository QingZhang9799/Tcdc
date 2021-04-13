<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/7
 * Time: 13:29
 */

namespace app\partner\controller;
use app\backstage\controller\Gps;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Request;

class DriverLocation extends Base
{
    public function index()
    {
        $params = [
            "channel" => input('?channel') ? input('channel') : null,
            "timestamp" => input('?timestamp') ? input('timestamp') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "mtOrderId" => input('?mtOrderId') ? input('mtOrderId') : null,
            "partnerOrderId" => input('?partnerOrderId') ? input('partnerOrderId') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["channel"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $gps = new Gps();
        $orderId = $params["partnerOrderId"];
        //根据订单id,获取车辆id
        $conducteur_id = Db::name('order')->where(['id'=> $orderId])->value('conducteur_id') ;
        $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => $conducteur_id ])->value('vehicle_id');

        $driverInfos = db()->query("call getCarInfoByOrderId($vehicle_id)");

        $driverInfos = $driverInfos[0][0];
        $data = $gps->getCarsStatus($driverInfos["Gps_number"])["data"][0];

        if(!empty($data)){
            $ini['longitude'] = $data["lonc"];
            $ini['latitude'] = $data["latc"];
        }else{
            $orders = Db::name('order')->where(['id'=>$orderId])->find() ;
            //按照状态进行获取
//            if($orders['status'] == 2){  //证明已经接单了
//                if($orders['DepLatitude'] != 0){
//                    $ini['latitude'] = floatval($orders['DepLatitude']) ;
//                    $ini['longitude'] = floatval($orders['DepLongitude']) ;
//                }else{
//                    $ini['latitude'] = 45.69726 ;
//                    $ini['longitude'] = 126.585479 ;
//                }
//            }else{
                //除2状态就是行程中的订单-取块里面的数据
                $location = explode(',',$gps->getDriverPositionByDriverId($conducteur_id));
                if(!empty($location)){
                    if($location[0] != 0){
                        $ini['latitude'] = floatval($location[0]) ;
                        $ini['longitude'] = floatval($location[1]) ;
                    }else{
                        $ini['latitude'] = 45.69726 ;
                        $ini['longitude'] = 126.585479 ;
                    }
                }else{
                    $ini['latitude'] = 45.69726 ;
                    $ini['longitude'] = 126.585479 ;
                }
//            }
        }

        $ini['updateTime'] = time()*1000;

        $file = fopen('./location.txt', 'a+');
        fwrite($file, "-------------------司机位置--------------------" . "\r\n");
        fwrite($file, "-------------------订单id: --------------------" . input('partnerOrderId')."\r\n");
        fwrite($file, "-------------------ini:--------------------" . json_encode($ini) . "\r\n");
        fwrite($file, "-------------------data:--------------------" . json_encode($data) . "\r\n");
        fwrite($file, "-------------------location:--------------------" . json_encode($location) . "\r\n");

        return [
            'result' => 0,
            'message' => 'SUCCESS',
            'data' => $ini
        ];
    }
}