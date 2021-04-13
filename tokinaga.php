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
duqu();
function duqu()
{
    //根据今天日期去查找文件夹
    global $dir, $conn;
    $today = date("Y_m_d");
    $dirpath = $dir . "/" . $today;
    //根据今天日期去查找文件夹
    if (is_dir($dirpath)) {
        if ($dh = opendir($dirpath)) {
            //读取包含D的文件 分析
            while (($file = readdir($dh)) !== false) {
                if (strpos($file, "D") !== false) {
                    //开始分析文件
                    $filepath = $dirpath . "/" . $file;
                    echo date("H:i:s");
                    if (file_exists($filepath)) {
                        $file_arr = file($filepath);
                        $adddata = array();
                        preg_match('/\d+/', $file, $conducteurid);
                        $adddata['conducteur_id'] = $conducteurid[0];
                        $adddata['day'] = $today;
                        for ($i = 0; $i < count($file_arr); $i++) {
                            //逐行读取文件内容
                            //逐行分析 上线下线凑一对 计算时间 录入数据库
                            $judge = false;
                            if ($i == 0 && strpos($file_arr[$i], "下线") != false) {
                                //前一天晚上没有下线，今天才下线
                                $time1 = array("00:00:00", 1, "上线");
                                $z_hour1 = substr($time1[0], 0, 2);
                                while (strpos($file_arr[$i + 1], "下线") == false && $i < count($file_arr) - 1) {
                                    $i++;
                                }
                                if (strpos($file_arr[$i + 1], "下线") != false&&$i < count($file_arr) - 1) {
                                    //到目前下线了
                                    $time2 = explode(',', $file_arr[$i + 1]);
                                    $z_hour2 = substr($time2[0], 0, 2);
                                } else {

                                    //到目前仍旧未下线
                                    $time2 = array(date("H:i:s"), 0, "下线");
                                    $z_hour2 = substr($time2[0], 0, 2);
                                }
                                $judge = true;
                            } else if (strpos($file_arr[$i], "上线") !== false) {
                                //该行为上线
                                $time1 = explode(',', $file_arr[$i]);
                                $z_hour1 = substr($time1[0], 0, 2);
                                while (strpos($file_arr[$i + 1], "下线") == false && $i < count($file_arr) - 1) {
                                    $i++;
                                }
                                if (strpos($file_arr[$i + 1], "下线") != false&&$i < count($file_arr) - 1) {
                                    //到目前下线了
                                    $time2 = explode(',', $file_arr[$i + 1]);
                                    $z_hour2 = substr($time2[0], 0, 2);
                                } else {
                                    //到目前仍旧未下线
                                    $time2 = array(date("H:i:s"), 0, "下线");
                                    $z_hour2 = substr($time2[0], 0, 2);
                                }
                                $judge = true;

                            }
                            if ($judge) {
                                $startHour = intval($z_hour1);
                                $startTime = explode(":", $time1[0]);
                                $endHour = intval($z_hour2);
                                $endTime = explode(":", $time2[0]);
                                if ($startHour != $endHour) {
                                    if (isset($adddata[time_quantum($startHour)])) {
                                        $adddata[time_quantum($startHour)] += ((strtotime(date("Y-m-d") . " " . " " . $startHour . ":" . "59" . ":" . "59") - strtotime(date("Y-m-d") . " " . $startTime[0] . ":" . $startTime[1] . ":" . $startTime[2])) / 60);
                                    } else {
                                        $adddata[time_quantum($startHour)] = (strtotime(date("Y-m-d") . " " . " " . $startHour . ":" . "59" . ":" . "59") - strtotime(date("Y-m-d") . " " . $startTime[0] . ":" . $startTime[1] . ":" . $startTime[2])) / 60;
                                    }
                                    $startHour++;
                                    while ($startHour < $endHour) {
                                        if (isset($adddata[time_quantum($startHour)])) {
                                            $adddata[time_quantum($startHour)] +=((strtotime(date("Y-m-d") . " " . $startHour . ":59:59") - strtotime(date("Y-m-d") . " " . " " . $startHour . ":" . "00" . ":" . "00")) / 60);
                                        } else {
                                            $adddata[time_quantum($startHour)] = (strtotime(date("Y-m-d") . " " . $startHour . ":59:59") - strtotime(date("Y-m-d") . " " . " " . $startHour . ":" . "00" . ":" . "00")) / 60;
                                        }
                                        $startHour++;
                                    }
                                    if (isset($adddata[time_quantum($startHour)])) {
                                        $adddata[time_quantum($startHour)] += ((strtotime(date("Y-m-d") . " " . $endTime[0] . ":" . $endTime[1] . ":" . $endTime[2]) - strtotime(date("Y-m-d") . " " . $startTime[0] . ":00:00")) / 60);
                                    } else {
                                        $adddata[time_quantum($startHour)] = (strtotime(date("Y-m-d") . " " . $endTime[0] . ":" . $endTime[1] . ":" . $endTime[2]) - strtotime(date("Y-m-d") . " " . $startTime[0] . ":00:00")) / 60;
                                    }
                                } else {
                                    if (isset($adddata[time_quantum($startHour)])) {
                                        $adddata[time_quantum($startHour)] +=((strtotime(date("Y-m-d") . " " . $endTime[0] . ":" . $endTime[1] . ":" . $endTime[2]) - strtotime(date("Y-m-d") . " " . $startTime[0] . ":" . $startTime[1] . ":" . $startTime[2])) / 60);
                                    } else {
                                        $adddata[time_quantum($startHour)] = (strtotime(date("Y-m-d") . " " . $endTime[0] . ":" . $endTime[1] . ":" . $endTime[2]) - strtotime(date("Y-m-d") . " " . $startTime[0] . ":" . $startTime[1] . ":" . $startTime[2])) / 60;
                                    }
                                }
                            }
                        }
                        $strk = array();
                        $strv = array();
                        foreach ($adddata as $k => $v) {
                            $strk[] = '`' . $k . '`';
                            //将字段作为一个数组；
                            $strv[] = '"' . $v . '"';
                            //将插入的值作为一个数组；
                        }
                        $strk = implode(',', $strk);
                        $strv = implode(",", $strv);
                        $addsql = "REPLACE INTO mx_conducteur_tokinaga ($strk) values ($strv)";
                        echo $addsql;
                        if (mysqli_query($conn, $addsql)) {
                            echo "新记录插入成功".PHP_EOL;
                        } else {
                            echo "Error: " . $addsql . "<br>" . mysqli_error($conn).PHP_EOL;
                        }
                        //删除此文件
                        //unlink($filepath);
                        //删除此文件
                    }
                    //开始分析文件
                }
            }
            //读取包含D的文件 分析
            closedir($dh);
        }
    }
}

function time_quantum($t)
{
    $t = intval($t);
    $numTable[0] = "one_hour";
    //时间 0点到1点的时长
    $numTable[1] = "two_hour";
    //时间 1点到2点的时长
    $numTable[2] = "three_hour";
    //时间 2点到3点的时长
    $numTable[3] = "four_hour";
    //时间 3点到4点的时长
    $numTable[4] = "five_hour";
    //时间 4点到5点的时长
    $numTable[5] = "six_hour";
    //时间 5点到6点的时长
    $numTable[6] = "seven_hour";
    //时间 6点到7点的时长
    $numTable[7] = "eight_hour";
    //时间 7点到8点的时长
    $numTable[8] = "nine_hour";
    //时间 9点到10点的时长 表里没有8-9的字段！
    $numTable[9] = "ten_hour";
    //时间 9点到10点的时长
    $numTable[10] = "eleven_hour";
    //时间 10点到11点的时长
    $numTable[11] = "twelve_hour";
    //时间 11点到12点的时长
    $numTable[12] = "thirteen_hour";
    //时间 12点到13点的时长
    $numTable[13] = "fourteen_hour";
    //时间 13点到14点的时长
    $numTable[14] = "fifteen_hour";
    //时间 14点到15点的时长
    $numTable[15] = "sixteen_hour";
    //时间 15点到16点的时长
    $numTable[16] = "seventeen_hour";
    //时间 16点到17点时长
    $numTable[17] = "eighteen_hour";
    //时间 17点到18点时长
    $numTable[18] = "nineteen_hour";
    //时间 18点到19点时长
    $numTable[19] = "twenty_hour";
    //时间 19点到20点时长
    $numTable[20] = "twentyone_hour";
    //时间 20点到21点的时长
    $numTable[21] = "twentytwo_hour";
    //时间 21点到22点的时长
    $numTable[22] = "twentythree_hour";
    //时间 22点到23点的时长
    $numTable[23] = "twentyfour_hour";
    //时间 23点到24点的时长
    return $numTable[$t];
}

?>