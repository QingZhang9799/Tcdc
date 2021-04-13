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

class Ordercomplaint extends Base
{
    //创建工单
    public function index(){
        $params = [
            "channel" => input('?channel') ? input('channel') : null,
            "timestamp" => input('?timestamp') ? input('timestamp') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "mtOrderId" => input('?mtOrderId') ? input('mtOrderId') : null,
            "partnerOrderId" => input('?partnerOrderId') ? input('partnerOrderId') : null,
            "mtCaseId" => input('?mtCaseId') ? input('mtCaseId') : null,
            "faqId" => input('?faqId') ? input('faqId') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["channel"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $company_id = Db::name('order')->where(['id' => input('partnerOrderId') ])->value('company_id');
        $ini['mtOrderId'] = input('mtOrderId') ;
        $ini['order_id'] = input('partnerOrderId') ;
        $ini['company_id'] = $company_id ;
        $ini['faqId'] = input('faqId') ;
        $ini['mtCaseId'] = input('mtCaseId') ;

        $individual_id = Db::name('individual')->insertGetId($ini) ;

        $data = [] ;
        $data['partnerCaseId']  = $individual_id ;

        return [
            'result' => 0,
            'message' => '成功',
            'data' => $data
        ];
    }
}