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

class Cancellnvoice extends Base
{
    public function index(){
        $params = [
            "channel" => input('?channel') ? input('channel') : null,
            "timestamp" => input('?timestamp') ? input('timestamp') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "invoiceld" => input('?invoiceld') ? input('invoiceld') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["channel"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //将发票作废
        $ini['id'] = input('invoiceld') ;
        $ini['status'] = 2 ;
        Db::name('invoice')->update($ini);

        $data = [
            'status' => 2
        ];

        return [
            'result' => 0,
            'message' => 'SUCCESS',
            'data'=>$data
        ];
    }
}