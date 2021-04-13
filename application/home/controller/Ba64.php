<?php

namespace app\home\controller;

use think\Controller;

class Ba64  extends Controller
{
    public function index()
    {
        ob_clean();
        $url = "https://upload.fapiaoer.cn/upload/fp_file/201907180000011132156341424300015d2fceeb399ec.pdf";
        $imgData = file_get_contents($url);//拿到远程图片
        $base64 = chunk_split(base64_encode($imgData));//转64文件流
       echo $base64;
    }
}