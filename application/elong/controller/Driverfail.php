<?php

namespace app\partner\controller;
use app\backstage\controller\Gps;
use think\Cache;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Request;

class Driverfail extends Base
{
    public function index()
    {
        $params = [
            "tcOrderStatus" => input('?tcOrderStatus') ? input('tcOrderStatus') : null,
            "supplierCode" => input('?supplierCode') ? input('supplierCode') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "clientId" => input('?clientId') ? input('clientId') : null,
            "orderId" => input('?orderId') ? input('orderId') : null,
            "cancelReason" => input('?cancelReason') ? input('cancelReason') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["tcOrderStatus"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //根据接单失败，刷新数据库
        $ini['id'] = input('orderId') ;
        $ini['reason'] = input('cancelReason') ;
        Db::name('order')->update($ini) ;

        return [
            'result' => 0,
            'message' => 'SUCCESS',
        ];
    }

}