<?php

namespace app\applet\controller;

use think\Controller;
use think\Db;
use app\user\controller\Marketing;

class Versions extends Base
{
    public function version()
    {
        $versionCode = Db::name('versions_record')->where(['id'=>4])->value('versionCode') ;
        //版本
        return ['code'=>200,'msg'=>'成功','version'=>$versionCode];
    }
}