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

$sql="UPDATE mx_conducteur SET online_time = 0";
mysqli_query($conn, $sql);

?>