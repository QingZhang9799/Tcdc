<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/7
 * Time: 13:29
 */

namespace app\partner\controller;
use think\cache\driver\Redis;

class Querynearbydriver extends Base
{
    public function index()
    {
        $params = [
            "channel" => input('?channel') ? input('channel') : null,
            "timestamp" => input('?timestamp') ? input('timestamp') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "longitude" => input('?longitude') ? input('longitude') : null,
            "latitude" => input('?latitude') ? input('latitude') : null,
            "distance" => input('?distance') ? input('distance') : null,
            "count" => input('?count') ? input('count') : null,
            "partnerCarTypeIds" => input('?partnerCarTypeIds') ? input('partnerCarTypeIds') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["channel"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
//        $file = fopen('./log.txt', 'a+');
//        fwrite($file, "-------------------附近司机:--------------------"."\r\n");
//        fwrite($file, "-------------------params:--------------------".json_encode($params)."\r\n");
        //获取附近司机
        $param_driver = [
            "channel" => input('channel'),
            "timestamp" => input('timestamp'),
            "sign" => input('sign'),
            "longitude" => input('longitude'),
            "latitude" => input('latitude'),
            "distance" => input('distance'),
            "count" => input('count'),
        ];
        $data = [];
        //根据下单经度和纬度查询附近车辆
//        $r = (int)($params['distance'] / 500);
//        $scope = db("city_scope")->where("city_id", 62)->find();
//        fwrite($file, "-------------------scope:--------------------".json_encode($scope)."\r\n");
//        $centerPoolID = $this->getBlockIdByLatlnt($params["latitude"], $params["longitude"],$scope);
//        fwrite($file, "-------------------$centerPoolID:--------------------".json_encode($centerPoolID)."\r\n");
//        $res = $this->getPoolIdAround($centerPoolID, $r, $scope["db"]);
//        fwrite($file, "-------------------res:--------------------".json_encode($res)."\r\n");
//
//        foreach ($res as $value) {
//            $temp = $this->getLatlntByBlockId($value,$scope);
//            $temp["direction"] = 500;
//            $temp['partnerCarTypeId'] = 1;
//            $temp['duration'] = 10000;
//            array_push($data, $temp);
//        }

        $business_type_id ="3" ;
        $data = $this->querynearbydriver($param_driver,array("$business_type_id"));
        return [
            'result' => 0,
            'message' => 'SUCCESS',
            'data' => $data
        ];
    }
    private function querynearbydriver($params,$businessTypeId)
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
        $ini = [] ;
        //根据下单经度和纬度查询附近车辆
        $r = (int)($params['distance'] / 500);
        $scope = db("city_scope")->where("city_id", 62)->find();
        $centerPoolID = $this->getBlockIdByLatlnt($params["latitude"], $params["longitude"], $scope);
//        $this->printLog("partner/postorder.php:319", json_encode($centerPoolID, JSON_UNESCAPED_UNICODE));
        $res = $this->getPoolIdAround($centerPoolID, $r, $scope["db"]);
//        $this->printLog("partner/postorder.php:321", json_encode($res, JSON_UNESCAPED_UNICODE));
//        $this->printLog("partner/postorder.php:322", json_encode($r, JSON_UNESCAPED_UNICODE));
        $latitude = "" ;
        $longitude = "" ;
        foreach ($res as $value) {
            $temp = $this->getLatlntByBlockId($value, $scope);
            $temp["drivers"] = $this->getPoolInfoByPoolID($value, $scope["db"]);
            $temp["direction"] = 500;
//            $temp['partnerCarTypeId'] = 1;
            $temp['duration'] = 1000;
            $latitude = $temp['latitude'] ;
            $longitude = $temp['longitude'] ;
            array_push($data, $temp);
        }
//        $this->printLog("partner/postorder.php:335", json_encode($data, JSON_UNESCAPED_UNICODE));

//        //返回司机id
        foreach ($data as $key=>$unitData) {
            $driverInfos = $unitData["drivers"];
            $i = 0 ; //计数器
            foreach ($driverInfos as $driverId=>$driverInfo){
                try {
                    $driverInfo=json_decode($driverInfo,true);
//                    $this->printLog("partner/postorder.php:383", json_encode($driverInfo["businessType"], JSON_UNESCAPED_UNICODE));
//                    $this->printLog("partner/postorder.php:384", json_encode($businessTypeId, JSON_UNESCAPED_UNICODE));

//                    if($driverInfo["businessType"]==$businessTypeId){
//                        $partnerDriverId=$driverInfo["userID"];
//                        break;
//                    }
                    $str1 = array("3");$str2 = array("18");$str3 = array("19");

                    if($driverInfo["businessType"] == $str1 || $driverInfo["businessType"] == $str2 || $driverInfo["businessType"] == $str3){
                        $businessType  = 0 ;
                        if($str1 == array("3")){
                            $businessType = 1401 ;
                        }else if($str1 == array("18")){
                            $businessType = 1402 ;
                        }else if($str1 == array("19")){
                            $businessType = 1403 ;
                        }
//                        $this->printLog("partner/postorder.php:134", $i);
                        if($i < 9){

                            $ini[] = [
                                'latitude' => $latitude,
                                'longitude' => $longitude,
                                'direction' => 0,
//                                'duration' => 1000,
//                                'distance'=>500,
                                'partnerCarTypeId' =>$businessType,
//                                'timestamp'=>time()*1000,
//                                'partnerDriverId'=>strval($driverInfo["userID"])
                            ];
                        }
                        $i = $i + 1 ;
                    }
                } catch (\Exception $e) {

                }

            }
        }
        return $ini;
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

//    function getPoolIdAround($centerPoolID, $around, $DB)
//    {
//
//        $redis = new \Redis();
//
//        $redis->connect('127.0.0.1', 6379);
//        // 获取数据并输出
//        $redis->select($DB);
//        $poolID = explode(",",$centerPoolID);
//        $resList = [];
//        if (count($redis->hGetAll($centerPoolID)) > 0) {
//            $temp = array_values($redis->hGetAll($centerPoolID));
//            foreach ($temp as $value) {
////                $value["poolID"] = $centerPoolID;
//                array_push($resList, $centerPoolID);
//            }
//        }
//        if (count($poolID) == 2) {
//            $first = (int)$poolID[0];
//            $second = (int)$poolID[1];
//            for ($i = 0; $i < $around; $i++) {
//                for ($j = (-$i); $j <= $i; $j++) {
//                    for ($k = (-$i); $k <= $i; $k++) {
//                        $drivers = $redis->hGetAll(array($first + $j, $second + $k) . implode(","));
//                        foreach ($drivers as $value) {
////                            $value["poolID"] = array($first + $j, $second + $k) . implode(",");
//                            $poolID = array($first + $j, $second + $k) . implode(",");
//                            array_push($resList, $poolID);
//                        }
//                    }
//                }
//            }
//        }
//        return $resList;
//    }
//
//    private function getLatlntByBlockId($blockId, $scope)
//    {
//        $blockX = explode(",", $blockId)[0];
//        $blockY = explode(",", $blockId)[1];
//        $lng = $blockX * $scope["scope"] / 111000 + $scope["scope_longitude"];
//        $lat = $scope["scope_latitude"] - $blockY * $scope["scope"] / 111000;
//        return array("latitude" => $lat, "longitude" => $lng);
//    }
//
//    private function getBlockIdByLatlnt($lat, $lnt, $scope)
//    {
//        $blockX = abs(floor((($lnt - $scope['scope_longitude']) * 111000) / $scope['scope']));
//        $blockY = abs(floor((($lat - $scope['scope_latitude']) * 111000) / $scope['scope']));
//        return "$blockX,$blockY";
//    }

}