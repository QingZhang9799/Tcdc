<?php


namespace app\user\controller;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class Versions extends Controller
{
    //版本更新
    public function VersionsUpdate(){
        $versions_record = Db::name('versions_record')->field('versionCode,versionName,file_size,link')->where('type','eq',0)->find();

        return [
            'code'=>APICODE_SUCCESS,
            'msg'=>'成功',
            'data'=>$versions_record,
        ];
    }
}