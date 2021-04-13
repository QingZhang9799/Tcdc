<?php

namespace app\api\vikit;

class map
{
    public $url;
    // ip定位
    public function ip($ip)
    {
        // 请求的url
        $init = 'https://restapi.amap.com/v3/ip?';
        trace('[ 请求尝试 ] '.$init,'curl');

        // 请求参数
        $date = [
            'key' => config('vmap.key'),
            'output' => config('vmap.format'),
            'ip' => $ip,
        ];

        // 请求串
        $param = $this->param($date);
        $url = $init.$param;

        return $this->get($url);
    }

    // 经纬度转换
    public function convert($lnglat){
        $init = 'https://restapi.amap.com/v3/assistant/coordinate/convert?';
        trace('[ 请求尝试 ] '.$init,'curl');

        $date = [
            'key' => config('vmap.key'),
            'output' => config('vmap.format'),
            'locations' => $lnglat,
            'coordsys' => 'gps',
        ];

        $param = $this->param($date);
        $url = $init.$param;

        return $this->get($url);
    }

    // 逆地理编码
    public function ress($jwd)
    {
        $init = 'https://restapi.amap.com/v3/geocode/regeo?';
        trace('[ 请求尝试 ] '.$init,'curl');

        $date = [
            'key' => config('vmap.key'),
            'output' => config('vmap.format'),
            'location' => $jwd,
        ];

        // 请求串
        $param = $this->param($date);
        $url = $init.$param;
        
        return $this->get($url);
    }

    // 行政区查询
    public function chunk($citycode)
    {
        $init = 'https://restapi.amap.com/v3/config/district?';
        trace('[ 请求尝试 ] '.$init,'curl');

        $date = [
            'key' => config('vmap.key'),
            'output' => config('vmap.format'),
            'keywords' => $citycode,
            'subdistrict' => 0,
            'showbiz' => 'false',
            'extensions' => 'base'
        ];

        $param = $this->param($date);
        $url = $init.$param;

        return $this->get($url);
    }

    // 两点距离计算
    public function dis($start,$end){
        $a = explode(',',$start);
        $b = explode(',',$end);

        $dis = $this->get_s($a[0],$a[1],$b[0],$b[1]);
        return $dis;
    }

    //+=========================
    //| 系统函数
    //+=========================

    // 参数拼接
    private function param($date)
    {
        // 排序组装待签名串
        ksort($date);
        $init = null;
        foreach ($date as $k => $v) {
            $init[] = $k.'='.$v;
        }
        $param = implode('&', $init);

        return $param;
    }

    // 发送请求
    private function get($url)
    {
        $this->url = $url;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $re = curl_exec($ch);
        curl_close($ch);
        trace('[ 请求完毕 ] '.$re,'curl');
        return $re;
    }

    private function rad($d){
        return $d * M_PI / 180.0;
    }

    private function get_s($lng1,$lat1,$lng2,$lat2){
        // 地球半径
        $radius = 6378.137;

        // 经度检测
        if(abs($lng1) > 180 || abs($lng2) > 180){
            return ['code'=>'error','msg'=>'有一个经度超标了'];
        }

        // 纬度检测
        if(abs($lat1) > 90 || abs($lat2) > 90){
            return ['code'=>'error','msg'=>'有一个纬度超标了'];
        }

        $radlat1 = $this->rad($lat1);
        $radlat2 = $this->rad($lat2);
        $a =  $radlat1 - $radlat2;
        $b = $this->rad($lng1) - $this->rad($lng2);

        // 一堆计算
        /*
        Math.asin(Math.sqrt(Math.pow(Math.sin(a/2),2) +Math.cos(radLat1)*Math.cos(radLat2)*Math.pow(Math.sin(b/2),2)));
        */
        $s = 2 * asin(sqrt(pow(sin($a / 2),2) + cos($radlat1) * cos($radlat2) * pow(sin($b / 2),2)));
        $s *= $radius;
        $dis = round(round($s * 10000) / 10000,2);
        return $dis;
    }
}
