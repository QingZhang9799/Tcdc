<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
$domain="*";
if(array_key_exists("HTTP_REFERER",$_SERVER)){
    $domain=$_SERVER["HTTP_REFERER"] ;
    $domain=substr($domain,0,strlen($domain)-1);
}else if(array_key_exists("HTTP_ORIGIN",$_SERVER)){
    $domain=$_SERVER["HTTP_ORIGIN"] ;
}else if(array_key_exists("REMOTE_ADDR",$_SERVER)){
    $domain=$_SERVER["REMOTE_ADDR"] ;
}
header("Access-Control-Allow-Origin:$domain");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Methods:GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers:DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type, Accept-Language, Origin, Accept-Encoding");
// 定义应用目录
define('APP_PATH', __DIR__ . '/application/');
define('ALI_PATH', __DIR__ . '/extend/Alipay/aop/');

// 加载框架引导文件
require __DIR__ . '/thinkphp/start.php';

