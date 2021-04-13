<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/7
 * Time: 13:29
 */

namespace app\partner\controller;
use app\backstage\controller\Gps;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Request;

class Allowancenotify extends Base
{
    public function index(){
        $params = [
            "channel" => input('?channel') ? input('channel') : null,
            "timestamp" => input('?timestamp') ? input('timestamp') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "mtOrderId" => input('?mtOrderId') ? input('mtOrderId') : null,
            "partnerOrderId" => input('?partnerOrderId') ? input('partnerOrderId') : null,
            "partnerCostAmount" => input('?partnerCostAmount') ? input('partnerCostAmount') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["channel"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
//        $file = fopen('./log.txt', 'a+');
//        fwrite($file, "-------------------Allowancenotify--------------------" . "\r\n");
        if(input('partnerCostAmount') > 0){
            $ini['id'] = input('partnerOrderId') ;
            $ini['discounts_money'] = input('partnerCostAmount')/100 ;
            $res = Db::name('order')->update($ini);
        }

        return [
            'result' => 0,
            'message' => 'SUCCESS',
        ];
    }
}