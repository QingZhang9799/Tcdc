<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 20-5-15
 * Time: 下午6:28
 */
namespace app\passengertraffic\controller;

use think\Controller;
use think\Db;
use app\user\controller\Marketing;

class Versions extends Base
{
    public function version()
    {
        //版本
        return ['code'=>200,'msg'=>'成功','version'=>202011060000];
    }
}