<?php
error_reporting(E_ALL ^ E_DEPRECATED);
date_default_timezone_set('Asia/Shanghai');
$servername = "rm-hp322l094we3qq641xo.mysql.huhehaote.rds.aliyuncs.com";
$username = "myadmin3";
$password = "lingyun888";
$dbname = "tcdc";
$dir = "/root/TCDCIMSERVER_jar/driver_logs";
// 创建连接
$conn = mysqli_connect($servername, $username, $password, $dbname);
// 检测连接
if (!$conn) {
    die("连接失败: " . mysqli_error());
}
//执行
journey();
function journey()
{
    global $dir, $conn;
    //查询所有班次
    $sql = "select * from mx_passenger_flights WHERE state = 1";
    //执行SQL语句
    $query = $conn->query($sql);
    while ($row = $query->fetch_array()) {
        //当前时间+预售时间
        judgeIsActive($row);
    }
    $query->close();
    $conn->close();
}

//验证班次
function judgeIsActive($item)
{
    $times = strtotime("midnight", time());
    $presell_day = $item['presell_day'];
    $judgeDate = strtotime("+$presell_day days", $times);//当前日期加预售期
    $flag = 0;//判定是否进行实例化
    $depart_frequency = $item['depart_frequency'] ;
    switch ($item['depart_frequency']) {
        //判定该班次是天，日，月，年；
        case 1://日:也是加预售时间
            $flag = 1;
            break;
        case 2://周:判断now+预售期的周几属性是否在列表中；
            $dates = explode(",", $item['dates']);
            foreach ($dates as $key => $value) {
//              星期日:0,星期一:1,星期二:2,星期三:3,星期四:4,星期五:5,星期六:6
                $week = date("w", $judgeDate);
                echo "week : " . $week;
                if ($week == $value) {         //星期在里面
                    $flag = 1;
                }
            }
            break;
        case 3://月:判断now+预售期的日属性是否在列表中
            $day = date('d', $judgeDate);
            $dates_month = explode(",", $item['dates']);
            foreach ($dates_month as $k => $v) {
                if ((int)$day == (int)$v) {
                    $flag = 1;
                }
            }
            break;
        case 4://年:判断now+预售期的月和日属性是否在列表中
            $year = date('m-d', $judgeDate);
            $dates_year = explode(",", $item['dates']);
            foreach ($dates_year as $kk => $vv) {
                if ($year == $vv) {
                    $flag = 1;
                }
            }
            break;
    }
    if ($flag > 0) {
        createjourney($item,$presell_day,$depart_frequency);
    }
}

//创建行程
function createjourney($item,$presell_day,$depart_frequency)
{
    global $dir, $conn;
    echo "222" ;
//    var_dump($item['particulars']);
    $time_cs = strtotime("midnight", time());
    //取当天时间
    if($depart_frequency == 1){     //每天的时候，加上预售时间
        $times = strtotime(date("Y-m-d", strtotime("+$presell_day days", time())) . " " . $item['depart_time']);
    }else{                          //其他的时候，等于预售时间的时候，生成。
        $times = strtotime(date('Y-m-d', time()) . " " . $item['depart_time']);
    }

    $type = 1;
    $city_id = $item['city_id'];
    if(!empty($item['particulars'])){
        //JSON_UNESCAPED_UNICODE（中文不转为unicode ，对应的数字 256）
        //JSON_UNESCAPED_SLASHES （不转义反斜杠，对应的数字 64）
        //通常json_encode只能传入一个常量，如果同时使用2个常量怎么办？
        //JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES = 320
        $particulars = json_encode($item['particulars'],JSON_UNESCAPED_UNICODE) ;//;
    }else{
        $particulars = "" ;
    }

    $status = 1;
    $spot = $item['spot'];
    $vehicle_id = $item['vehicle_id'];
    $total_ticket = $item['total_ticket'];
    $residue_ticket = $item['total_ticket'];
    $origin = $item['origin'];
    $destination = $item['destination'];
    $price = $item['price'];
    $origin_longitude = $item['origin_longitude'];
    $origin_latitude = $item['origin_latitude'];
    $destination_longitude = $item['destination_longitude'];
    $destination_latitude = $item['destination_latitude'];
    $start_time = $item['start_time'];
    $cancel_rules = $item['cancel_rules'];


    $sql1 = "insert into mx_journey(type,city_id,particulars,status,spot,times,vehicle_id,total_ticket,residue_ticket,origin
,destination,price,origin_longitude,origin_latitude,destination_longitude,destination_latitude,start_time,cancel_rules) 
            values ('$type','$city_id',$particulars,'$status','$spot','$times','$vehicle_id','$total_ticket','$residue_ticket'
            ,'$origin','$destination','$price','$origin_longitude','$origin_latitude','$destination_longitude','$destination_latitude','$start_time','$cancel_rules')";

    $query = $conn->query($sql1);
    if($query){
        echo "成功录入数据";
    }else{
        echo "录入失败";
    }
}

?>