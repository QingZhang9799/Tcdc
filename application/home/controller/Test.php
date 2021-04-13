<?php


namespace app\home\controller;

use think\Config;
use think\Controller;
use think\Request;
use app\home\controller\Wechat;
use think\Db;

class Test extends Controller
{
    public function index()
    {
        $w = new Wechat("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449");
        $res = $w->sendServiceText("om8QlxHFLDhGthT1MzSo__1QkZiY","成功111");
        echo  $res;
    }
    public function adkf()
    {
         $w = new Wechat("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449");
         $res = $w->addkf();
         echo $res;
    }
    public function sendapp(){
        $w = new Wechat("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449") ;
        $res = $w->sendApp("om8QlxHFLDhGthT1MzSo__1QkZiY","同城打车","wxfaa1ea1ef2c2be3f","/pages/index/index","2t7i64-V2fLKWB92jzaSbsAkaNCpKZSs1B-sLZII1HtN9s0DmAtcyLchs0jQQlil") ;
        echo $res ;
    }
    //测试图片
    public function thumb(){
        $w = new Wechat("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449") ;
        $thumb_id = $w->get_thumb_id() ;
        echo $thumb_id ;
    }

    //通过高德路线规划
    public function LinePlanning(){
        $data = $this->request_post("https://restapi.amap.com/v3/direction/driving?parameters", array("name" => "tcdc", "password" => "123456"));

    }

//    private function request_post($url = '', $post_data = array())
//    {
//        if (empty($url) || empty($post_data)) {
//            return false;
//        }
//        $o = "";
//        foreach ($post_data as $k => $v) {
//            $o .= "$k=" . urlencode($v) . "&";
//        }
//        $post_data = substr($o, 0, -1);
//        $postUrl = $url;
//        $curlPost = $post_data;
//        $ch = curl_init();//初始化curl
//        curl_setopt($ch, CURLOPT_URL, $postUrl);//抓取指定网页
//        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
//        curl_setopt($ch, CURLOPT_GET, 1);//post提交方式
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
//        $data = curl_exec($ch);//运行curl
//        curl_close($ch);
//        return $data;
//    }

    //测试类
    public function test(){
        $this->appointment("城际来了",1016,99999, 4);
    }
    function appointment($title, $uid, $message, $type)
    {
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("6052ba408cae4f0c51d5f695:4e44c5e79552ce7672cab6c2");
//        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
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

//    //插入用户数据
//    public function InsertUser()
//    {
//        $sheet1 = Db::name('sheet1')->limit(27000,40000)->select() ;
//        foreach ($sheet1 as $key=>$value){
//            //随机创建时间
//            $ini['nickname'] = $value['nick'] ;
//            $ini['PassengerPhone'] = $value['phone'] ;
//            $city_id = Db::name('cn_city')->where(['name'=>$value['city_name']])->value('id') ;
//            if(empty($city_id)){
//                $city_id = 62 ;
//            }
//            $ini['city_id'] = $city_id ;
//            $ini['create_time'] = strtotime($this->randomDate('2020-06-07 00:00:00','2020-10-06 23:59:59')) ;
//            Db::name('user')->insert($ini) ;
//        }
//    }
//    //更换数据
//    public function UpdateUser(){
//        $sheet1 = Db::name('sheet1')->limit(1,65535)->select() ;
//        foreach ($sheet1 as $key=>$value){
//            //随机创建时间
//            $ini['id'] = $value['id'] ;
//            $ini['nick'] = $value['name'].$value['nick']  ;
//            Db::name('sheet1')->update($ini) ;
//        }
//    }
    /**
     *   生成某个范围内的随机时间
     * @param <type> $begintime  起始时间 格式为 Y-m-d H:i:s
     * @param <type> $endtime    结束时间 格式为 Y-m-d H:i:s
     */
    function randomDate($begintime, $endtime="") {
        $begin = strtotime($begintime);
        $end = $endtime == "" ? mktime() : strtotime($endtime);
        $timestamp = rand($begin, $end);
        return date("Y-m-d H:i:s", $timestamp);
    }

    //测试秒数
    public function TestSeconds(){
//        $order = Db::name('order')->field('id,business_type_id,user_id,partnerCarTypeId,mtorderid,user_phone')->where(['id' => 742665])->find();
//        $conducteur = Db::name('conducteur')->field('id,DriverName,DriverPhone,key,service,trace,terimnal,company_id')->where(['id' => 273])->find();
//        $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => 273])->value('vehicle_id');
//        $vehicle = Db::name('vehicle')->field('id,PlateColor,VehicleNo,Model')->where(['id' => $vehicle_id])->find();
//        $orders = Db::name('order')->field('id')->where(['id' => 742665])->where('conducteur_id', 'neq', 0)->find();
        $order_m =  Db::name('order')->field('id')->where(['user_id' => 655565,'is_type' =>1,'status'=>12 ])->find();
    }

    //测试位置接口
//    public function  TestLocations(){
//        $file = fopen('./informs.txt', 'a+');
//        fwrite($file, "----------------------------进来了---------------------". "\r\n");
//        fwrite($file, "-----------longitude:----------------" .input('longitude')."---------------latitude-------------".input('latitude'). "\r\n");
//
//        return [
//            "code" => APICODE_SUCCESS,
//            "msg" => "成功",
//        ];
//    }
     // 测试token和-1情况
//    public function TestKoken(){
//        $file = fopen('./inform.txt', 'a+');
//        fwrite($file, "-----------进来了:----------------" . "\r\n");
//        fwrite($file, "-----------token:----------------" .input('token'). "\r\n");
//        fwrite($file, "-----------Longitude_1:----------------" .input('Longitude_1'). "\r\n");
//        fwrite($file, "-----------Longitude_5:----------------" .input('Longitude_5'). "\r\n");
//        fwrite($file, "-----------message:----------------" .input('message'). "\r\n");
//        return [
//            "code" => APICODE_SUCCESS,
//            "msg" => "成功",
//        ];
//    }
    //测试本地
    public function test1(){

        halt(111) ;
    }

    //测试秒数
//    public function SecondsTest(){
//        $params = [
//            "order_id" => input('?order_id') ? input('order_id') : null,
//            "order_line_id" => input('?order_line_id') ? input('order_line_id') : null,
//        ];
//
//        $order = Db::name('order')->alias('o')
//            ->field('o.id,l.origin,l.destination,l.price,o.seating_count,o.Ridership,o.DepLongitude,o.DepLatitude,c.DriverName,c.DriverPhone,c.grandet,v.VehicleNo,v.PlateColor,v.OwnerName,v.VehicleColor,v.Model,o.DepartTime,l.seat_details,v.seating as seatings,o.create_time,o.line_id')
//            ->join('mx_order_line l', 'l.order_id = o.id', 'inner')
//            ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
//            ->join('mx_vehicle_binding b', 'b.conducteur_id = c.id', 'inner')
//            ->join('mx_vehicle v', 'v.id = b.vehicle_id', 'left')
//            ->where(['l.id' => input('order_line_id')])
//            ->where(['o.id' => input('order_id')])
//            ->find();
//
//        //已占用座位
//        //获取子单子-> 座位情况
//        if ($order['seating_count'] == 4) {
//            $orders = Db::name('order')->distinct(true)->field('seat,Ridership')->where(['order_id' => input('order_id')])->where('status', 'in', '7,12,3,4')->select();
//        } else {
//            $orders = Db::name('order')->field('seat,Ridership')->where(['order_id' => input('order_id')])->where('status', 'in', '7,12,3,4')->select();
//        }
//
//        $seatOccputed = array();
//        $Ridership = 0;
//        //拼接座位情况，去除重复
//        foreach ($orders as $key => $value) {
//            $seatOccputed = array_merge($seatOccputed, explode(",", $value['seat']));
//            $Ridership += (int)$value['Ridership'];
//        }
//
//        $str = implode('', $seatOccputed); //合并数组
//        $remaining_seats = $str;
//        $order['remaining_seats'] = $remaining_seats;
//        $order['seatOccputed'] = $seatOccputed;
//        $order['Ridership'] = $Ridership;
//
//        //获取折扣
//        $discount = Db::name('line')->where(['id'=>$order['line_id']])->value('discount') ;
//        $order['l_discount'] = $discount ;
//        return ['code' => APICODE_SUCCESS, 'data' => $order];
//    }

    //测试ios访问
//    public function ios_test(){
//        $file = fopen('./ios.txt', 'a+');
//        fwrite($file, "-----------进来了:----------------" . "\r\n");
//    }

    //判定是否在圈内
    public function programming($longitude,$latitude){
        $company_scope = Db::name('company_scope')->where(['company_id'=>90])->find() ;
        $scope = explode('-', $company_scope['scope']) ;
        $ini = [] ;

        $minX = 0 ; //最小经度
        $maxX = 0 ; //最大经度
        $minY = 0 ; //最小维度
        $maxY = 0 ; //最大维度

        foreach ($scope as $key=>$value){
            $location = explode(',',$value) ;

            $ini[] = [
                'longitude'=>floatval($location[1]),
                'latitude'=>floatval($location[0])
            ] ;
            if($key == 0){
                $minX = floatval($location[1]) ;
                $maxX = floatval($location[1]) ;
                $maxY = floatval($location[0]) ;
                $minY = floatval($location[0]) ;
            }

            //匹配最大值
            if(floatval($location[1]) > $maxX){
                $maxX = floatval($location[1]);
            }
            if(floatval($location[0]) > $maxY){
                $maxY = floatval($location[0]);
            }

            //匹配最小值
            if(floatval($location[1]) < $minX){
                $minX = floatval($location[1]);
            }
            if(floatval($location[0]) < $minY){
                $minY = floatval($location[0]);
            }
        }
//        var_dump($ini) ;
//        halt('----maxX:---'.$maxX.'----minX:---'.$minX.'----maxY:---'.$maxY.'----minY:---'.$minY) ;
        if( $longitude < $minX || $longitude > $maxX || $latitude < $minY || $latitude > $maxY)
        {
            return false;
        }
    }

    public function fun($vert,$vertx,$verty,$testx,$testy)
    {
        $i = 0 ;
        $j = 0 ;
        $c = 0 ;

        for ($i = 0, $j = $vert-1; $i < $vert; $j = $i++)
        {
            if ( ( ($verty[$i]>$testy) != ($verty[$j]>$testy) ) && ($testx < ($vertx[$j]-$vertx[$i]) * ($testy-$verty[$i]) / ($verty[$j]-$verty[$i]) + $vertx[$i]) )
                $c = !$c;
        }
        return $c;
    }

    public function location($longitude,$latitude){
        //根据起点和终点连成一条直线



    }













}