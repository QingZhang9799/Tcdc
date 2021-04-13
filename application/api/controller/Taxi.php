<?php

/**

 * Created by PhpStorm.

 * User: Administrator

 * Date: 19-2-26

 * Time: 上午10:53

 */

namespace app\api\controller;
use app\api\model\Conducteur;
use app\api\model\Company;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;

class Taxi extends Base
{
    //付款按钮
    public function paymentbtn(){
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id')
            ];
            $company_id = db('conducteur')->where($params)->value('company_id');
            //获取公司付费方式
            $data = Db::name('company_paymethod')->where(['company_id'=>$company_id])->find();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }
    }

    //线下付款
    public function OfflinePayment(){
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id'),
                "status" => 6
            ];

            //将出租车订单变成已付款
            $res = Db::name('order')->update($params);

            if($res > 0){
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "付款成功",
                ];
            }else{
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "付款失败",
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //线上付款
    public function WirePayment(){
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "expenses" => input('?expenses') ? input('expenses') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id","expenses"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
//        $file = fopen('./order.txt', 'a+');
//        fwrite($file, "-------------------订单id:--------------------".input('order_id')."\r\n");

        //更改订单状态
        $ini['id'] = input('order_id');
        $ini['money'] = input('expenses');
        $ini['status'] = 7 ;
        $orders = Db::name('order')->update($ini) ;

        if($orders){
          $order_m = Db::name('order')->where(['id'=>input('order_id')])->find();
          $message = "司机已经结束行程，请确认行程信息\n
                    合计价格:   ".$order_m['money']."元" ;
          $this->newsService(input('order_id'),'司机已确认费用',$order_m['user_id']) ;

            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'确认成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'确认失败'
            ];
        }
    }
    public function newsService($order_id,$message,$user_id){
//        $file = fopen('./order.txt', 'a+');
//        fwrite($file, "-------------------出租车发送账单--------------------"."\r\n");
        $bjnews_openid = Db::name('user')->where(['id'=>$user_id])->value('bjnews_openid');
//        fwrite($file, "-------------------bjnews_openid--------------------".$bjnews_openid."\r\n");
        //om8QlxHFLDhGthT1MzSo__1QkZiY
        $w = new Wechat("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449") ;
        $res = $w->sendApp($bjnews_openid,"同城打车","wxfaa1ea1ef2c2be3f","/pages/index/index?is_gz=1&order_id".$order_id,"ltSB8hJPk-B6MNSLEC1SnFhuyNwdXaRC0o9lFBi2cZA") ;  //?is_tencent=1&payType=taxi&orderId=\".$order_id
//        echo $res ;
    }
}