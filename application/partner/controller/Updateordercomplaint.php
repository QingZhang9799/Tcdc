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

class Updateordercomplaint extends Base
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
            "remark" => input('?remark') ? input('remark') : null,
            "partnerCaseId" => input('?partnerCaseId') ? input('partnerCaseId') : null,
            "complaintType" => input('?complaintType') ? input('complaintType') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["channel"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //根据美团订单号获取工单
        $individual_id =  Db::name('individual')->where(['mtOrderId' => input('mtOrderId') ])->value('id') ;

        //更新备注
        $ini['id'] = $individual_id ;
        $ini['remark'] = input('remark') ;
        $ini['complaintType'] = input('complaintType') ;
        Db::name('individual')->update($ini);

        $data = [] ;
        $data['partnerCaseId']  = input('partnerCaseId') ;

        return [
            'result' => 0,
            'message' => '成功',
            'data' => $data
        ];
    }
}