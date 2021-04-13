<?php

namespace app\partner\controller;

use app\backstage\controller\Gps;
use function GuzzleHttp\Psr7\str;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Request;

class Dispatch extends Base
{
    public function index()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "drivers" => input('?drivers') ? input('drivers') : null,
            "block_id" => input('?block_id') ? input('block_id') : null,
        ];
        $conducteur_id = substr(input('drivers'), 1);                           //司机id
        $order_id = input('order_id');
        $order = Db::name('order')->field('id,business_type_id,user_id,partnerCarTypeId,mtorderid,user_phone')->where(['id' => $order_id])->find();
        if(empty($conducteur_id)){
            return [
                'code' => APICODE_ERROR,
                'message' => '无司机',
            ];
        }
        //司机多实时
        $orders = Db::name('order')->field('id')->where(['conducteur_id'=>$conducteur_id,'status'=>2])->find();
        if(!empty($orders)){
            return [
                'code' => APICODE_ERROR,
                'message' => '司机存在实时单',
            ];
        }

        $conducteur = Db::name('conducteur')->field('id,DriverName,DriverPhone,key,service,trace,terimnal,company_id')->where(['id' => $conducteur_id])->find();
        //车辆
        $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => $conducteur_id])->value('vehicle_id');
        $vehicle = Db::name('vehicle')->field('id,PlateColor,VehicleNo,Model')->where(['id' => $vehicle_id])->find();

        $data = [];
        $bill = [
            'totalPrice' => 0,
            'driveDistance' => 0,
            'driveTime' => 0,
            'initPrice' => 0,
            'driveDistancePrice' => 0,
            'driveTimePrice' => 0,
        ];
        $carInfo = [
            'carColor' => $vehicle['PlateColor'],
            'carNumber' => $vehicle['VehicleNo'],
            'brandName' => $vehicle['Model'],
        ];
        $scope['scope_longitude'] = 125.687894 ;
        $scope['scope_latitude'] = 46.095985 ;
        $scope['scope'] = 500 ;

//        $block = $this->getLatlntByBlockIds(input('block_id'), $scope) ;
//        $driverLocation = [
//            'lat' => sprintf("%.6f", $block['lat']),//floatval($order['DepLatitude']),
//            'lng' => sprintf("%.6f", $block['lng']),//floatval($order['DepLongitude']),
//        ];
        $gps = new Gps();
        $location = explode(',',$gps->getDriverPositionByDriverId($conducteur_id));
        if(!empty($location)){
            $lat = floatval($location[0]) ;
            $lng = floatval($location[1]) ;
        }else{
            $lat = 45.69726 ;
            $lng = 126.585479 ;
        }

        $driverLocation = [
            'lat' => sprintf("%.6f", $lat),//floatval($order['DepLatitude']),
            'lng' => sprintf("%.6f", $lng),//floatval($order['DepLongitude']),
        ];
        $driverLastNames = mb_substr($conducteur['DriverName'], 0, 1, 'utf-8');
        $driverInfo = [
            'driverLastName' => $driverLastNames,
            'driverMobile' => $conducteur['DriverPhone'],
            'driverName' => $conducteur['DriverName'],
            'driverVirtualMobile' => $conducteur['DriverPhone'],//$result_vritual['data']['conducteur_phone'],
            'partnerDriverId' => "$conducteur_id",
        ];

        $product = [
            'partnerCarTypeId' => intval($order['partnerCarTypeId']),
        ];

        $param['channel'] = strval("tcdc_car");
        $param['timestamp'] = strval(time() * 1000);

        $param['eventCode'] = "0";
        $param['bill'] = json_encode($bill, JSON_UNESCAPED_UNICODE);
        $param['carInfo'] = json_encode($carInfo, JSON_UNESCAPED_UNICODE);

//        $param['chargeInfo'] = json_encode($chargeInfo, JSON_UNESCAPED_UNICODE);
        $param['driverInfo'] = json_encode($driverInfo, JSON_UNESCAPED_UNICODE);
        $param['eventTime'] = strval(time() * 1000);
        $param['mtOrderId'] = strval($order['mtorderid']);
        $param['partnerOrderId'] = strval($order_id);
        $param['product'] = json_encode($product, JSON_UNESCAPED_UNICODE);
        $param['driverLocation'] = json_encode($driverLocation, JSON_UNESCAPED_UNICODE);
        $param['status'] = "CONFIRM" ;
//            $sign = $this->getSign($param, "4wpitbq9JyLEZXj3InLbTw==");
        $sign = $this->getSign($param, "IQBs6DADXQrBawyQyVZaQA==");
        $param['sign'] = $sign;//"4wpitbq9JyLEZXj3InLbTw==" ;

        unset($params['sign_key']);

        $datas = $this->request_post("https://qcs-openapi.meituan.com/api/open/callback/common/v1/pushOrderStatus", $param);

        if ($datas['result'] == 0) {  //回调成功，以后在进行推送
            //先给美团回调，在延时存司机，进行派单
            sleep(15);
            $reason = Db::name('order')->where(['id' => $order_id])->value('reason') ;
            if(!empty($reason)){
                return [
                    'result' => 0,
                    'message' => 'SUCCESS',
                ];
            }

            $vritualController = new \app\api\controller\Vritualnumber();
            $result_vritual = $vritualController->getPhoneNumberByOrderId($order_id);
            $inii['id'] = $order_id;

            $inii['conducteur_virtual'] = $result_vritual['data']['conducteur_phone'];
            $inii['user_virtual'] = $order['user_phone'] ; //$result_vritual['data']['user_phone'];

            $inii['budget_conducteur_id'] = $conducteur_id;
            $inii['company_id'] = $conducteur['company_id'];
//            $inii['orders_from'] = sprintf("%.6f", $block['lng']).",".sprintf("%.6f", $block['lat']) ;
            $inii['orders_from'] = sprintf("%.6f", $lat).",".sprintf("%.6f", $lng) ;

            //更新订单信息
            $classification = "实时";
            $inii['id'] = $order_id;

            $inii['conducteur_id'] = $conducteur_id;
            $inii['conducteur_name'] = $conducteur['DriverName'];
            $inii['conducteur_phone'] = $conducteur['DriverPhone'];
            $inii['gps_number'] = $conducteur['Gps_number'];
            //司机gps
            $inii['key'] = $conducteur['key'];
            $inii['service'] = $conducteur['service'];
            $inii['terimnal'] = $conducteur['terimnal'];
            $inii['trace'] = $conducteur['trace'];
            $inii['async_block'] = input('block_id') ;
            $inii['status'] = 2;
            $inii['mt_status'] = 30;

//        $ini['conducteur_id'] = $conducteur_id;
            Db::name('order')->update($inii);

            //调用激光
            $this->appointment("美团单来了", $order['budget_conducteur_id'], (int)input('partnerOrderId'), 10);
        }
    }

    function appointment($title, $uid, $message, $type)
    {
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
        $header = array(
            "Authorization:Basic $base64",
            "Content-Type:application/json"
        );
        $param = array("platform" => "all", "audience" => array("tag" => array("D_$uid")), "message" => array("msg_content" => $message . "," . $type, "title" => $title));
        $params = json_encode($param);
        $res = $this->request_post($url, $params, $header);
        $res_arr = json_decode($res, true);
    }

    private function getLatlntByBlockIds($blockId, $scope)
    {
        $blockX = explode(",", $blockId)[0];
        $blockY = explode(",", $blockId)[1];
        $lng = $blockX * $scope["scope"] / 111000 + $scope["scope_longitude"];
        $lat = $scope["scope_latitude"] - $blockY * $scope["scope"] / 111000;

        $result['lng'] = $lng ;
        $result['lat'] = $lat ;
        return $result ;
    }
    function request_post($url = "", $param = "", $header = "")
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init(); // 初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); // 抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); // 设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); // post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch); // 运行curl

        curl_close($ch);
        return $data;
    }
}