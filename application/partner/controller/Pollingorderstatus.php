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

class Pollingorderstatus extends Base
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
//        $file = fopen('./log.txt', 'a+');
        $params = $this->filterFilter($params);
        $required = ["channel"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $data = [] ;
//        fwrite($file, "-----------------------输入参数---------------" . json_encode($params) . "\r\n");
        $orders = Db::name('order')->where(['id' =>input('partnerOrderId') ])->find() ;
        $conducteur = [] ;
        $conducteur_id = 0 ;
        $vehicle_id = 0 ;
        $vehicle = [] ;
        if(!empty($orders['conducteur_id'])){
            $conducteur = Db::name('conducteur')->where(['id'=>$orders['conducteur_id']])->find() ;
            $conducteur_id = $orders['conducteur_id'] ;
            $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' =>$orders['conducteur_id'] ])->value('vehicle_id') ;
            $vehicle = Db::name('vehicle')->where(['id' =>$vehicle_id ])->find() ;
        }

        if($orders['status'] == 7){         //待付款
            $rate = json_decode($orders['rates'], true);
            $total_price = json_decode($orders['total_price'], true);
//            var_dump($total_price);
//            var_dump($rate);
//            exit();
//            fwrite($file, "-----------------------rate1111111111111---------------" . json_encode($rate) . "\r\n");
            $bill = [
                'totalPrice' => ($total_price["money"] * 100) - ($orders['highwayPrice']*100) - ($orders['tollPrice']*100) - ($orders['surcharge'] *100)- ($orders['parkPrice']*100) ,//行程基础类费用总额，单位为分。完成履约行为后，除去高速费、停车费、感谢费、其他费用四项附加类费用项外订单产生的行程费用总和
                'driveDistance' => (int)($total_price["Mileage"] * 1000),//	行驶里程,单位m
                'driveTime' => (int)($total_price["total_time"] * 60 * 1000),//行驶时长,单位ms
                'initPrice' => (int)($rate["startMoney"]["money"]*100),//起步价。订单开始履约需要收取的费用，包含一定的里程和时长。注：当时长费和里程费不满起步价，但需要按照起步价金额收取的时候，里程费和时长费不需要传递，只传起步价金额。

//                'normalTimePrice' => 1,//正常时长费。非高峰及夜间时段行驶时间产生的费用总额
//                'normalDistancePrice' => 2,//正常里程费。非高峰及夜间时段行驶里程产生的费用总额
                'longDistancePrice' => (int)($rate["Kilometre"]["Longfee"]*100),//远程费。超过一定里程之后收取的远途行驶里程费用总和
                'longDistance' => (int)($rate["Kilometre"]["LongKilometers"]*1000),//	远程里程。单位m
//                'nightPrice' => 1,//夜间里程费。夜间时段行驶里程产生的费用总额
//                'nightDistance' => 1,//	夜间里程。单位m
//                'highwayPrice' => (int)($orders['highwayPrice']*100),//高速费。订单履约过程产生的高速类费用，此费用为代收代付类费用不能开票，不能抽佣，不能使用红包抵扣
//                'tollPrice' => (int)($orders['tollPrice']*100),//通行费。订单履约过程产生的过路过桥类费用，此费用为代收代付类费用不能开票，不能抽佣，不能使用红包抵扣
//                'parkPrice' => (int)($orders['parkPrice']*100),//停车费。订单履约过程产生的停车类费用，此费用为代收代付类费用不能开票，不能抽佣，不能使用红包抵扣
                'otherPrice' => (int)($orders['surcharge'] *100),//其他费。订单履约过程中产生的其他代收代付类费用，如：清洁费等费用项，此费用为代收代付类费用不能开票，不能抽佣，不能使用红包抵扣
            ];
//            fwrite($file, "------------------------total_price---------------" . json_encode($total_price) . "\r\n");
            $rate["Kilometre"][0]["LongKilometers"] == "0.00" ?: $bill["longDistance"] = (int)($rate["Kilometre"][0]["LongKilometers"] * 1000);
            $rate["Kilometre"][0]["Longfee"] == "0.00" ?: $bill["longDistancePrice"] = (int)($rate["Kilometre"][0]["Longfee"] * 100);
//            if ($total_price["money"] != $rate["startMoney"]["money"]) {
            $driveDistancePrice = 0 ;
            if(!empty($rate["Mileage"])){
                foreach ($rate["Mileage"] as $key=>$value){
                    $driveDistancePrice += $value['money'] ;
                }
            }
            $bill['driveDistancePrice'] = (int)($driveDistancePrice*100) ;//$rate["Mileage"][0]["money"]*100;//	里程费。行驶里程产生的费用总额，里程费=正常里程费+夜间里程费
            $driveTimePrice = 0 ;
            if(!empty($rate["tokinaga"])){
                foreach ($rate["tokinaga"] as $k=>$v){
                    $driveTimePrice += $v['money'] ;
                }
            }
            $bill['driveTimePrice'] = (int)($driveTimePrice*100) ;//$rate["tokinaga"][0]["money"]*100;//时长费。行驶时间产生的费用总额
//            }
//            fwrite($file, "-----------------------bill---------------" . json_encode($bill) . "\r\n");
        }else{
            $bill = [
                'totalPrice'=>0,
                'driveDistance'=>0,
                'driveTime'=>0,
                'initPrice'=>0,
                'driveDistancePrice'=>0,
                'driveTimePrice'=>0,
            ] ;
        }
        $carInfo = [] ;
        if(!empty($vehicle)){
            $carInfo = [
                'carColor' => $vehicle['PlateColor'],
                'carNumber' => $vehicle['VehicleNo'],
                'brandName' => $vehicle['Model'],
            ];
        }
        $customerServiceInfo = [
            'cancelReason'=>"1",
            'opName'=>"1",
        ];
        $chargeInfo = [
            'offlinePayAmount'=>10
        ];
        $continuousAssign =[
            'preDestLng'=>"1",
            'preDestLat'=>"1",
            'preOrderId'=>"1",
            'preRemainDistance'=>1,
            'preRemainSecond'=>1,
            'wholePickupDistance'=>1,
            'wholePickupSecond'=>1,
        ];
        $driverLastNames = mb_substr($conducteur['DriverName'], 0, 1, 'utf-8');
        $driverInfo = [] ;
        if(!empty($conducteur)){
            $driverInfo = [
                'driverLastName' => $driverLastNames,
                'driverMobile' => $conducteur['DriverPhone'],
                'driverName' => $conducteur['DriverName'],
                'driverVirtualMobile' => $orders['conducteur_virtual'],
                'partnerDriverId' => "$conducteur_id",
            ];
        }

        $product = [
            'partnerCarTypeId'=>intval($orders['partnerCarTypeId']),
//            'outCarTypeId'=>"1"
        ];

        $gps = new Gps();
        $data_gps = [] ;
        if($vehicle_id > 0){
            $driverInfos = db()->query("call getCarInfoByOrderId($vehicle_id)");

            $driverInfos = $driverInfos[0][0];
            $data_gps = $gps->getCarsStatus($driverInfos["Gps_number"])["data"][0];

            $ini['longitude'] = $data_gps["lonc"];
            $ini['latitude'] = $data_gps["latc"];
        }

        $lat = 0 ;
        $lng = 0 ;
        if(!empty($data_gps)){
            $lat = $data_gps["latc"] ;
            $lng = $data_gps["lonc"] ;
        }else{
            //按照状态进行获取
//            if($orders['status'] == 2){  //证明已经接单了
//                $block = explode(',',$orders['orders_from'])  ;
//                $lng = floatval(sprintf("%.6f", $block[0]));//floatval($orders['DepLongitude']) ;
//                $lat = floatval(sprintf("%.6f", $block[1]));//floatval($orders['DepLatitude']) ;
//            }else{
                //除2状态就是行程中的订单-取块里面的数据
                $location = explode(',',$gps->getDriverPositionByDriverId($conducteur_id));
                if(!empty($location)){
                    $lat = floatval($location[0]) ;
                    $lng = floatval($location[1]) ;
                }else{
                    $lat = 45.69726 ;
                    $lng = 126.585479 ;
                }
//            }
        }

        $driverLocation = [
            'lat'=>$lat,
            'lng'=>$lng,
        ];

//        $candidateConfirmList = [
//            'driverInfo'=>$driverInfo,
//            'carInfo'=>$carInfo,
//            'product'=>$product,
//            'driverLocation'=>$driverLocation,
//        ] ;

        $driverFingerprint = [
            'wifimac'=>"c4:b3:01:cb:f6:cf",
            'dm'=>"false",
        ];

        $param['channel'] = strval(input('channel')) ;
        $param['timestamp'] = strval(time()*1000);

        $param['bill'] =$bill ;
        if(!empty($vehicle)){
            $param['carInfo'] = $carInfo;
        }
//        $param['chargeInfo'] = $chargeInfo;
        if(!empty($conducteur)){
            $param['driverInfo'] = $driverInfo;
        }

        $param['eventTime'] = strval(time()*1000) ;
        $param['mtOrderId'] = strval(input('mtOrderId')) ;
        $param['partnerOrderId'] = strval(input('partnerOrderId')  );
        $param['product'] =$product;
        $param['driverLocation'] = $driverLocation;

        $status = "" ;

        if($orders['mt_status'] == 10){
            $status = "SUBMIT" ;
        }else if($orders['mt_status'] == 30){
            $status = "CONFIRM" ;
        }else if($orders['mt_status'] == 50){
            $status = "SET_OUT" ;
        }else if($orders['mt_status'] == 60){
            $status = "ARRIVE" ;
        }else if($orders['mt_status'] == 70){
            $status = "DRIVING" ;
        }else if($orders['mt_status'] == 80){
            $status = "DELIVERED" ;
        }else if($orders['mt_status'] == 90){
            $status = "WAIT_PAY" ;
        }else if($orders['mt_status'] == 100){
            $status = "FINISH" ;
        }else if($orders['mt_status'] == 101){
            $status = "CANCEL_BY_USER" ;
            $param['customerServiceInfo'] = $customerServiceInfo;
        }else if($orders['mt_status'] == 102){
            $status = "CANCEL_BY_DRIVER" ;
            $param['customerServiceInfo'] = $customerServiceInfo;
        }else if($orders['mt_status'] == 103){
            $status = "CANCEL_BY_CS" ;
            $param['customerServiceInfo'] = $customerServiceInfo;
        }

        $param['status'] = $status ;
//        $param['candidateConfirmList'] = $candidateConfirmList ;

        $sign = $this->getSign($param,"IQBs6DADXQrBawyQyVZaQA==") ;
        $param['sign'] = $sign;//"4wpitbq9JyLEZXj3InLbTw==" ;

        unset($params['sign_key']) ;
//        fwrite($file, "-------------------轮回参数:--------------------".json_encode($param)."\r\n");
        return [
            'result' => 0,
            'message' => 'SUCCESS',
            'data' => $param
        ];
    }
    function request_post($url = "", $param = "", $header = "") {
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
        curl_setopt($ch, CURLOPT_POSTFIELDS,  $curlPost);
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