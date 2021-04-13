<?php


namespace app\user\controller;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Request;

class Oncall extends Base
{
    //获取用户信息
    public function checkUserInfo(){
        $user = Db::name('user')->where([ 'PassengerPhone' => input('tel') ])->find();
        return [
            'code'=>APICODE_SUCCESS,
            'msg'=>'查询成功',
            'data'=>$user
        ];
    }
    //创建订单
    public function sVoiceInfo(){
        $params = [
            "tel" => input('?tel') ? input('tel') : null,
            "voice" => input('?voice') ? input('voice') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["tel","voice"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        //获取用户id
        $users = Db::name('user')->where(['PassengerPhone'=>input('tel')])->find() ;
        if(empty($users)){  //不存在用户，保存
            $inii['PassengerPhone'] = input('tel') ;
            $inii['nickname'] = "同城". rand(0000, 9999) ;
            $inii['city_id'] = 62 ;
            $inii['create_time'] = time() ;

            $user_id = Db::name('user')->insertGetId($inii) ;
            $users = Db::name('user')->where(['id' =>$user_id ])->find() ;
        }

        $ini['user_phone'] = input('tel') ;
        $ini['voice'] = input('voice') ;
        $ini['company_id'] = 90 ;
        $ini['OrderId'] = "CZ" . 62 . '0' . date('YmdHis') . rand(0000, 999);
        $ini['DepartTime'] = time() ;
        $ini['city_id'] = 62 ;
        $ini['create_time'] = time() ;
        $ini['classification'] = "预约" ;
        $ini['status'] = 1 ;
        $ini['user_id'] = $users['id'] ;
        $ini['user_name'] = $users['nickname'] ;

        $res = Db::name('order')->insert($ini);

        if($res){
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "创建成功",
            ];
        }else{
            return [
                "code" => APICODE_ERROR,
                "msg" => "创建失败",
            ];
        }
    }
    //查询订单信息
    public function selectStatusInfo(){
        if (input('?orderId')) {
            $params = [
                "OrderId" => input('orderId')
            ];
            $data = db('order')->where($params)->find();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单编号不能为空"
            ];
        }
    }
    //取消订单
    public function cancel(){
        if (input('?orderId')) {
            $params = [
                "orderId" => input('orderId')
            ];

            $order_id = Db::name('order')->where(['orderId' =>input('orderId') ])->value('id') ;

            $ini['id'] = $order_id ;
            $ini['status'] = 5 ;
            $res = Db::name('order')->update($ini) ;

            if($res > 0){
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "取消成功",
                ];
            }else{
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "取消成功",
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单编号不能为空"
            ];
        }
    }
}