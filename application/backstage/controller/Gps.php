<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 19-2-26
 * Time: 上午10:53
 */

namespace app\backstage\controller;

use think\Controller;
use think\Db;
use think\Cache;

class Gps extends Controller
{
    //添加管理员
    public function getAdminToken()
    {
        $token = Cache::get('gps_token');
        if (!$token) {
            $data = $this->request_post("http://www.gpsnow.net/user/login.do", array("name" => "tcdc", "password" => "123456"));
            $data = json_decode($data, true);
            $token = $data["data"]["token"];
            Cache::set('gps_token', $token, 3600);
        }
        return $token;
    }

    public function getUserInfo()
    {
        $data = $this->request_post("http://www.gpsnow.net/structure/getChildStruc.do", array("token" => $this->getAdminToken()));
        $data = json_decode($data, true);
        $data = $data["data"][0];
        $res = $data;
        if ($res["hasChild"]) {
            $res["children"] = $this->getChildUserInfo($res["userId"]);
        }
        return ['code' => APICODE_SUCCESS, 'data' => $res];
    }

    private function getChildUserInfo($userId)
    {
        $data = $this->request_post("http://www.gpsnow.net/structure/getChildStruc.do", array("token" => $this->getAdminToken(), "targetUserId" => $userId));
        $data = json_decode($data, true);
        $data = $data["data"];
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]["hasChild"]) {
                $data[$i]["children"] = $this->getChildUserInfo($data[$i]["userId"]);
            }
        }
        return $data;
    }

    public function getUserInfoAndCarInfo()
    {
        $data = $this->request_post("http://www.gpsnow.net/structure/getChildStruc.do", array("token" => $this->getAdminToken()));
        $data = json_decode($data, true);
        $data = $data["data"][0];
        $res = $data;
        $temp = $this->request_post("http://www.gpsnow.net/structure/getCarGroupAndStatus.do", array("token" => $this->getAdminToken(), "mapType" => 2));
        $temp=json_decode($temp, true);
        $res["cars"] =$data["data"][0]["cars"];
        if ($res["hasChild"]) {
            $res["children"] = $this->getChildUserInfoAndCarInfo($res["userId"]);
        }
        return ['code' => APICODE_SUCCESS, 'data' => $res];
    }

    private function getChildUserInfoAndCarInfo($userId)
    {
        $data = $this->request_post("http://www.gpsnow.net/structure/getChildStruc.do", array("token" => $this->getAdminToken(), "targetUserId" => $userId));
        $data = json_decode($data, true);
        $data = $data["data"];
        for ($i = 0; $i < count($data); $i++) {
            $temp = $this->request_post("http://www.gpsnow.net/structure/getCarGroupAndStatus.do", array("token" => $this->getAdminToken(), "targetUserId" => $data[$i]["userId"], "mapType" => 2));
            $temp=json_decode($temp, true);
            $data[$i]["cars"] = $temp["data"][0]["cars"];
            if ($data[$i]["hasChild"]) {
                $data[$i]["children"] = $this->getChildUserInfoAndCarInfo($data[$i]["userId"]);
            }
        }
        return $data;
    }

    public function getCarsStatus($carIds)
    {
        $data = $this->request_post("http://www.gpsnow.net/carStatus/getByCarIds.do", array("token" => $this->getAdminToken(), "carIds" => $carIds, "mapType" => 2));
        $data = json_decode($data, true);
        $data = $data["data"];
        return ['code' => APICODE_SUCCESS, 'data' => $data];
    }

    public function queryHistory($carId, $startTime, $endTime)
    {
        $startTime = mb_substr($startTime, 0, 10); //strtotime("2019/01/29 01:00:02");
        $endTime = mb_substr($endTime, 0, 10);; //strtotime("2019/01/29 23:00:02");
        $startTime = gmdate("Y-m-d H:i:s", $startTime);
        $endTime = gmdate("Y-m-d H:i:s", $endTime);
        $token = $this->getAdminToken();
        $data = $this->request_post("http://www.gpsnow.net/position/queryHistory.do", array("carId" => $carId, "mapType" => "2", "startTime" => $startTime, "endTime" => $endTime, "token" => $token, "filter" => true));
        $data = json_decode($data, true);
        $res = $data["data"];
        return $res;
    }

    private function request_post($url = '', $post_data = array())
    {
        if (empty($url) || empty($post_data)) {
            return false;
        }
        $o = "";
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&";
        }
        $post_data = substr($o, 0, -1);
        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36');

        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
    }

    public function getFilialeAndCarInfo()
    {
        $parentId = input('parentId');      //上级
        $userId = input('userId');      //本级
        $datas = [];
        $data = $this->request_post("http://www.gpsnow.net/structure/getChildStruc.do", array("token" => $this->getAdminToken(), "targetUserId" => $parentId));
        $data = json_decode($data, true);
        $data = $data["data"];

        for ($i = 0; $i < count($data); $i++) {

            if ($data[$i]["userId"] == $userId) {
                $datas["hasChild"] = $data[$i]["hasChild"];
                $datas["name"] = $data[$i]["name"];
                $datas["parentId"] = $data[$i]["parentId"];
                $datas["totalNum"] = $data[$i]["totalNum"];
                $datas["underNum"] = $data[$i]["underNum"];
                $datas["userId"] = $data[$i]["userId"];
                $datas["userType"] = $data[$i]["userType"];

                $temp = $this->request_post("http://www.gpsnow.net/structure/getCarGroupAndStatus.do", array("token" => $this->getAdminToken(), "targetUserId" => $userId, "mapType" => 2));
                $temp =json_decode($temp, true);
                $datas["cars"] = $temp["data"][0]["cars"];
                if ($datas[$i]["hasChild"]) {
                    $datas[$i]["children"] = $this->getChildUserInfoAndCarInfo($userId);
                }
            }
        }
        return $datas;
    }

    public function getDriverInfo()
    {
        $city_id = input("city_id");
        if (!$city_id) {
            return array();
        }
        $scope = db("city_scope")->where("city_id", $city_id)->find();
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        // 获取数据并输出
        $redis->select(1);
        $arList = $redis->keys("*");
        $data = array();
        //获取该城市下，所有的车辆及订单情况
        $driverInfos = db()->query("call getCarInfoByCityId($city_id)");
        $driverInfos = $driverInfos[0];
        $carPositions = array();
        if (!empty($driverInfos)) {
            $gpsNumbers = array();
            foreach ($driverInfos as $val) {
                if ($val["Gps_number"]) {
                    $gpsNumbers[$val["Gps_number"]] = array();
                }
            }
            $car_statuss = $this->getCarsStatus(implode(",", array_keys($gpsNumbers)));
            foreach ($car_statuss["data"] as $val) {
                $carPositions[$val["carId"]] = $val;
            }
            foreach ($driverInfos as $val) {
                $car_status = array();
                if ($val["Gps_number"]) {
                    $car_status = $carPositions[$val["Gps_number"]];
                }
                if ($val["hasOrder"]) {
                    $data[$val["id"]] = array("status" => "ordering", "driverPosition" => null, "carPosition" => $car_status["lonc"] . "," . $car_status["latc"], "gpsInfo" => $car_status, "distance" => null, "driverInfo" => $val);
                } else {
                    $data[$val["id"]] = array("status" => "offline", "driverPosition" => null, "carPosition" => $car_status["lonc"] . "," . $car_status["latc"], "gpsInfo" => $car_status, "distance" => null, "driverInfo" => $val);
                }
            }
        }
        //获取听单中车辆
        foreach ($arList as $val) {
            if ((!strpos($val, "DB"))&&(strpos($val, "Position")===false)) {
                $driver_id = str_replace("D", "", $val);
                $driver_db = $redis->get($val . "DB");
                if ($driver_db == $scope["db"]) {
                    $carInfo = db()->query("call getCarInfoByDriverId($driver_id)");
                    $carInfo = $carInfo[0][0];
                    if ($carInfo) {
                        $gpsNumber = $carInfo["Gps_number"];
                        $car_status = $carPositions[$gpsNumber];
                        $blockId = $redis->get($val);
                        $driverPosition = $this->getLatlntByBlockId($blockId, $scope);
                        $lng1 = explode(",", $driverPosition)[0];
                        $lng2 = $car_status["lonc"];
                        $lat1 = explode(",", $driverPosition)[1];
                        $lat2 = $car_status["latc"];
                        $data[$driver_id] = array("status" => "listening", "blockId" => $blockId, "driverPosition" => $driverPosition, "carPosition" => $car_status["lonc"] . "," . $car_status["latc"], "gpsInfo" => $car_status, "distance" => $this->getDistance($lng1, $lat1, $lng2, $lat2), "driverInfo" => $carInfo);
                    }
                }
            }
        }

        $res = array();
        foreach ($data as $key => $val) {
            $val["id"] = $key;
            array_push($res, $val);
        }
        return $res;
    }

    public function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        $EARTH_RADIUS = 6378.137;
        $radLat1 = $this->rad($lat1);
        $radLat2 = $this->rad($lat2);
        $a = $radLat1 - $radLat2;
        $b = $this->rad($lng1) - $this->rad($lng2);
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s = $s * $EARTH_RADIUS;
        $s = round($s, 6);
        $s = $s * 1000;
        return $s;
    }

    public function rad($d)
    {
        return $d * 3.1415926535898 / 180.0;
    }

    private function getLatlntByBlockId($blockId, $scope)
    {
        $blockX = explode(",", $blockId)[0];
        $blockY = explode(",", $blockId)[1];
        $lng = $blockX * $scope["scope"] / 111000 + $scope["scope_longitude"];
        $lat = $scope["scope_latitude"] - $blockY * $scope["scope"] / 111000;
        return $lng . "," . $lat;
    }

    public function getDriverPositionByDriverId($driverId){
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->select(1);
//        $driverInfo=$redis->keys("Position_D".$driverId);
        $driverInfo=$redis->get("Position_D".$driverId);
        $driverPosition=false;
        if(sizeof($driverInfo)>0){
//            $driverPosition=$driverInfo[0];
            $driverPosition=$driverInfo;
        }
        return $driverPosition;

    }
}