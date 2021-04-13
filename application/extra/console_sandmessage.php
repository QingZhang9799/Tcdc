<?php
/**
 * Created by PhpStorm.
 * User: yangyanghong
 * Date: 18-5-2
 * Time: 下午2:30
 */
$data = \think\Db::name('sandmessage')->where('id',8)->find();
return [
    'key'=>$data['key'],//key
    'secret'=>$data['secret'],//秘钥
    'names'=>$data['names'],//签名
    'mcode'=>$data['mcode'],//模板code
    'type'=>$data['type'],//消息类型
    'cache_time_out'=>300,//缓存失效时间
];