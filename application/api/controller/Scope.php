<?php

namespace app\api\controller;

class Scope
{
    /**
     * @desc 根据地址获取经纬度
     * $addr 地址
     * $city 地址所在城市
     * $ak 百度api密钥
     */
    function GetLN($addr = "",$city = "",$ak = "")
    {
        $addr = urlencode($addr);
        $api_url = "http://api.map.baidu.com/geocoder/v2/?address=$addr&city=$city&output=json&ak=$ak";
        $json = file_get_contents($api_url);
        $json_arr = json_decode($json);
        return $json_arr;
    }

    /**
     * @desc 根据两点间的经纬度计算距离
     * @param float $lat 纬度值
     * @param float $lng 经度值
     */
    function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6367000; //地球的近似半径，单位为米
        /*
        将这些角度转换为弧度,用公式来计算
        */
        $lat1 = ($lat1 * pi() ) / 180;
        $lng1 = ($lng1 * pi() ) / 180;

        $lat2 = ($lat2 * pi() ) / 180;
        $lng2 = ($lng2 * pi() ) / 180;
        /*
        使用公式计算距离
        */
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;
        return round($calculatedDistance);
    }

    // 判断点 是否在多边形 内
    function isPointInPolygon($polygon,$lnglat)
    {
        $count = count($polygon);
        $px = $lnglat['lat'];
        $py = $lnglat['lng'];
        $flag = FALSE;
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i, $i++) {
            $sy = $polygon[$i]['lng'];
            $sx = $polygon[$i]['lat'];
            $ty = $polygon[$j]['lng'];
            $tx = $polygon[$j]['lat'];
            if ($px == $sx && $py == $sy || $px == $tx && $py == $ty)
                return TRUE;
            if ($sy < $py && $ty >= $py || $sy >= $py && $ty < $py) {
                $x = $sx + ($py - $sy) * ($tx- $sx) / ($ty-$sy); if ($x == $px) return TRUE; if ($x > $px)
                $flag = !$flag;
            }
        }
        return $flag;
    }
}
