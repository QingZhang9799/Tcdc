<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/7
 * Time: 13:29
 */

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
//        $file = fopen('./lease.txt', 'a+');
//        fwrite($file, "-------------------异步进来l :--------------------" . "\r\n");
//        fwrite($file, "-------------------异步:--------------------" . json_encode($params) . "\r\n");
        $order_id = input('order_id');
        $order = Db::name('order')->field('id,business_type_id,user_id,partnerCarTypeId,mtorderid,user_phone')->where(['id' => $order_id])->find();
        $conducteur_id = substr(input('drivers'), 1); //$result['partnerDriverId'] ;
//        fwrite($file, "-------------------司机id:--------------------" . $conducteur_id . "\r\n");
        //拦截用户

        //拦截司机
//        fwrite($file, "-------------------order[business_type_id]:--------------------" . $order['business_type_id']. "\r\n");
        $flags = $this->judgeCconducteur($conducteur_id,$order['business_type_id'],$order['user_id']) ;
//        fwrite($file, "-------------------flags:--------------------" . $flags. "\r\n");
        if($flags == 0){
            return [
                'code' => APICODE_ERROR,
                'message' => '司机车型不符',
            ];
        }

        $conducteur = Db::name('conducteur')->field('id,DriverName,DriverPhone,Gps_number,key,service,trace,terimnal,company_id')->where(['id' => $conducteur_id])->find();
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

        $block = $this->getLatlntByBlockIds(input('block_id'), $scope) ;

        $driverLocation = [
            'lat' => sprintf("%.6f", $block['lat']),//floatval($order['DepLatitude']),
            'lng' => sprintf("%.6f", $block['lng']),//floatval($order['DepLongitude']),
        ];
        //延迟5秒
//        sleep(8);
//        $reason = Db::name('order')->where(['id' => $order_id])->value('reason') ;
//        fwrite($file, "-------------------取消原因reason:--------------------" . $reason. "\r\n");     //司机电话
//        if(!empty($reason)){
//            return [
//                'result' => 0,
//                'message' => 'SUCCESS',
//            ];
//        }
        //更新订单信息
        $classification = "实时";
        $ini['id'] = $order_id;
        $ini['status'] = 2;
        $ini['mt_status'] = 30;
//        $ini['conducteur_id'] = $conducteur_id;
        $ini['conducteur_name'] = $conducteur['DriverName'];
        $ini['conducteur_phone'] = $conducteur['DriverPhone'];
        $ini['classification'] = $classification;
        $ini['gps_number'] = $conducteur['Gps_number'];
        //司机gps
        $ini['key'] = $conducteur['key'];
        $ini['service'] = $conducteur['service'];
        $ini['terimnal'] = $conducteur['terimnal'];
        $ini['trace'] = $conducteur['trace'];
        $ini['orders_from'] = sprintf("%.6f", $block['lng']).",".sprintf("%.6f", $block['lat']) ;
        $ini['async_block'] = input('block_id') ;
        Db::name('order')->update($ini);

//        fwrite($file, "-------------------虚拟号开始:--------------------" . date("Y-m-d H:i:s", time()) . "\r\n");     //司机电话
        $driverLastNames = mb_substr($conducteur['DriverName'], 0, 1, 'utf-8');
        if (!empty($order_id)) {

            $vritualController = new \app\api\controller\Vritualnumber();

//            fwrite($file, "-------------------虚拟号绑定结果:--------------------" . json_encode($result_vritual) . "\r\n");     //司机电话
//            fwrite($file, "-------------------conducteur_phone:--------------------" . $result_vritual['data']['conducteur_phone'] . "\r\n");     //司机电话
//            fwrite($file, "-------------------user_phone:--------------------" . $result_vritual['data']['user_phone'] . "\r\n");     //司机电话
//            fwrite($file, "-------------------虚拟号结束:--------------------" . date("Y-m-d H:i:s", time()) . "\r\n");     //司机电话
            //更新订单虚拟号
//            $virtual['id'] = $order_id ;

//            Db::name('order')->update($virtual);

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
            if (!empty($conducteur_id)) {
//                fwrite($file, "-------------------下单参数:--------------------" . json_encode($param, JSON_UNESCAPED_UNICODE) . "\r\n");
//                $datas = $this->request_post("https://qcs-openapi.apigw.test.meituan.com/api/open/callback/common/v1/pushOrderStatus", $param);   //"application/x-www-from-urlencoded"
                $datas = $this->request_post("https://qcs-openapi.meituan.com/api/open/callback/common/v1/pushOrderStatus", $param);   //"application/x-www-from-urlencoded"
//                fwrite($file, "-------------------数据1:--------------------" . json_encode($datas, JSON_UNESCAPED_UNICODE) . "\r\n");
                if ($datas['result'] == 0) {  //回调成功，以后在进行推送
                    //先给美团回调，在延时存司机，进行派单
                    sleep(11);
                    $reason = Db::name('order')->where(['id' => $order_id])->value('reason') ;
//                    fwrite($file, "-------------------取消原因reason:--------------------" . $reason. "\r\n");     //司机电话
                    if(!empty($reason)){
                        return [
                            'result' => 0,
                            'message' => 'SUCCESS',
                        ];
                    }
                    $result_vritual = $vritualController->getPhoneNumberByOrderId($order_id);
                    $inii['id'] = $order_id;

                    $inii['conducteur_virtual'] = $result_vritual['data']['conducteur_phone'];
                    $inii['user_virtual'] = $order['user_phone'] ; //$result_vritual['data']['user_phone'];

                    $inii['conducteur_id'] = $conducteur_id;
                    $inii['company_id'] = $conducteur['company_id'];
                    Db::name('order')->update($inii);
                    //调用激光
                    $this->appointment("美团单来了", $conducteur_id, $order_id, 10);
                }
            }

            return [
                'result' => 0,
                'message' => 'SUCCESS',
                'data' => $data,
            ];
        }
    }
        function appointmentByCompany($title, $companyId, $message, $type, $business_id, $business_type_id, $conducteur_id)
        {
            $url = 'https://api.jpush.cn/v3/push';
            $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
            $header = array(
                "Authorization:Basic $base64",
                "Content-Type:application/json"
            );
            $param = array("platform" => "all", "audience" => array("tag" => array("Company_$companyId")), "message" => array("msg_content" => $message . "," . $type . "," . $companyId . "," . $business_id . "," . $business_type_id . "," . $conducteur_id, "title" => $title));
            $params = json_encode($param);
            $res = $this->request_post($url, $params, $header);
            $res_arr = json_decode($res, true);
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

        private function querynearbydriver($params, $businessTypeId)
        {

            $params = $this->filterFilter($params);
            $required = ["channel"];
            if (!$this->checkRequire($required, $params)) {
                return [
                    "code" => APICODE_FORAMTERROR,
                    "msg" => "必填项不能为空，请检查输入"
                ];
            }

            $data = [];
            //根据下单经度和纬度查询附近车辆
            $r = (int)($params['distance'] / 500);
            $scope = db("city_scope")->where("city_id", 62)->find();
            $centerPoolID = $this->getBlockIdByLatlnt($params["latitude"], $params["longitude"], $scope);
//        $this->printLog("partner/postorder.php:319", json_encode($centerPoolID, JSON_UNESCAPED_UNICODE));
            $res = $this->getPoolIdAround($centerPoolID, $r, $scope["db"]);
//        $this->printLog("partner/postorder.php:321", json_encode($res, JSON_UNESCAPED_UNICODE));
//        $this->printLog("partner/postorder.php:322", json_encode($r, JSON_UNESCAPED_UNICODE));
            $latitude = "";
            $longitude = "";
            foreach ($res as $value) {
                $temp = $this->getLatlntByBlockId($value, $scope);
                $temp["drivers"] = $this->getPoolInfoByPoolID($value, $scope["db"]);
                $temp["direction"] = 0;
                $temp['partnerCarTypeId'] = 1;
                $temp['duration'] = 10000;
                $latitude = $temp['latitude'];
                $longitude = $temp['longitude'];
                array_push($data, $temp);
            }
//        $this->printLog("partner/postorder.php:335", json_encode($data, JSON_UNESCAPED_UNICODE));

//        //返回司机id
            foreach ($data as $unitData) {
                $driverInfos = $unitData["drivers"];
                foreach ($driverInfos as $driverId => $driverInfo) {
                    try {
                        $driverInfo = json_decode($driverInfo, true);
//                    $this->printLog("partner/postorder.php:383", json_encode($driverInfo["businessType"], JSON_UNESCAPED_UNICODE));
//                    $this->printLog("partner/postorder.php:384", json_encode($businessTypeId, JSON_UNESCAPED_UNICODE));
                        if ($driverInfo["businessType"] == $businessTypeId) {
                            $partnerDriverId = $driverInfo["userID"];
                            break;
                        }

                    } catch (\Exception $e) {

                    }

                }
            }
            $rusult['lat'] = $latitude;
            $rusult['lng'] = $longitude;
            $rusult['partnerDriverId'] = $partnerDriverId;
            return $rusult;
        }

        function getPoolIdAround($centerPoolID, $around, $DB)
        {

            $redis = new \Redis();

            $redis->connect('127.0.0.1', 6379);
            // 获取数据并输出
            $redis->select($DB);
            $poolID = explode(",", $centerPoolID);
            $resList = [];
//        if (count($redis->hGetAll($centerPoolID)) > 0) {
//            $temp = array_values($redis->hGetAll($centerPoolID));
//            foreach ($temp as $value) {
////                $value["poolID"] = $centerPoolID;
//                array_push($resList, $centerPoolID);
//            }
//        }
            if (count($poolID) == 2) {
                $first = (int)$poolID[0];
                $second = (int)$poolID[1];
                for ($j = (-$around); $j <= $around; $j++) {
                    for ($k = (-$around); $k <= $around; $k++) {
                        $drivers = $redis->hGetAll(implode(",", array($first + $j, $second + $k)));
//                    $this->printLog("partner/postorder.php:362", json_encode($drivers, JSON_UNESCAPED_UNICODE));
//                    $this->printLog("partner/postorder.php:363", json_encode(implode(",", array($first + $j, $second + $k)), JSON_UNESCAPED_UNICODE));
                        if ($drivers) {
                            $poolID = implode(",", array($first + $j, $second + $k));
                            array_push($resList, $poolID);
                        }
                    }
                }
            }
            return $resList;
        }

        private function getPoolInfoByPoolID($poolID, $db)
        {
            $redis = new \Redis();

            $redis->connect('127.0.0.1', 6379);
            // 获取数据并输出
            $redis->select($db);
            $drivers = $redis->hGetAll($poolID);
            return $drivers;
        }

        private function getLatlntByBlockId($blockId, $scope)
        {
            $blockX = explode(",", $blockId)[0];
            $blockY = explode(",", $blockId)[1];
            $lng = $blockX * $scope["scope"] / 111000 + $scope["scope_longitude"];
            $lat = $scope["scope_latitude"] - $blockY * $scope["scope"] / 111000;
            return array("latitude" => $lat, "longitude" => $lng);
        }

        private function getBlockIdByLatlnt($lat, $lnt, $scope)
        {
            $blockX = abs(floor((($lnt - $scope['scope_longitude']) * 111000) / $scope['scope']));
            $blockY = abs(floor((($lat - $scope['scope_latitude']) * 111000) / $scope['scope']));
            return "$blockX,$blockY";
        }

        private function printLog($title, $value)
        {
            $file = fopen('./log.txt', 'a+');
            fwrite($file, "-------------------$title--------------------" . $value . "\r\n");
            fclose($file);
        }
        //激光推送
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
        // 极光推送提交
        function request_post_s($url = "", $param = "", $header = "")
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

        //判断司机属性
        private function judgeCconducteur($conducteur_id,$business_type_id,$user_id){
            $flag = 0 ;
//            $file = fopen('./log.txt', 'a+');
            //车辆
            $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => $conducteur_id])->value('vehicle_id');
            $vehicle = Db::name('vehicle')->field('id,businesstype_id')->where(['id' => $vehicle_id])->find();
            $businesstype_id = $vehicle['businesstype_id'] ;

            if($businesstype_id == $business_type_id ){
                //相同司机，相同用户，为2，美团订单只有一个
                $order_s = Db::name('order')->field('id')->where(['user_id' =>$user_id,'status'=>2,'is_type' =>1  ])->find();
//                fwrite($file, "-------------------order_s:--------------------" . json_encode($order_s, JSON_UNESCAPED_UNICODE) . "\r\n");
                if(empty($order_s)){
                    $flag = 1 ;
                }
            }
            return $flag ;
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
}