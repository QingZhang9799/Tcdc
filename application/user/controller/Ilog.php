<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 20-10-11
 * Time: 下午7:56
 */
namespace app\test\controller;


use think\Controller;

class Ilog extends Controller
{
    public static function Debug($msg)
    {
        //初始化日志
        $logHandler= new \CLogFileHandler("../logs/".date('Y-m-d').'.log');
        $log = \Log::Init($logHandler, 15);
        //根据订单号去进行订单回调处理
        $log->DEBUG($msg);
    }
}