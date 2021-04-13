<?php


namespace app\user\controller;

use think\Cache;
use think\Db;
use think\Request;

class Passenger extends Base
{
    //添加乘车人
    public function AddPassenger()
    {
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "name" => input('?name') ? input('name') : null,
            "phone" => input('?phone') ? input('phone') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "name"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        //姓名不重复
        $name = Db::name('user_passenger')->where(['name' => input('name'), 'user_id' => input('user_id')])->select();
        if (!empty($name)) {
            return [
                'code' => APICODE_ERROR,
                'msg' => '姓名已重复'
            ];
        }
        $user_passenger = Db::name('user_passenger')->insert($params);

        if ($user_passenger) {
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '创建成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '创建失败'
            ];
        }
    }

    //删除乘车人
    public function DelPassenger()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];

            $user_passenger = db('user_passenger')->where(['id' => input('id')])->delete();

            if ($user_passenger > 0) {
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "删除成功",
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "乘车人ID不能为空"
            ];
        }
    }

    //乘车人列表
    public function PassengerList()
    {
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            $data = db('user_passenger')->where($params)->order("id desc")->limit(5)->select();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //消息中心列表
    public function MessageCenterList(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            $system_messages =  Db::name('system_messages')->select() ;
            foreach ($system_messages as $key =>$value){
                //区分未读和已读
               $user_look = Db::name('user_look')->where(['user_id'=>input('user_id')])->where(['system_messages_id'=>$value['id']]);
               if(!empty($user_look)){
                   $system_messages[$key]['is_look'] = 1 ;    //已读
               }else{
                   $system_messages[$key]['is_look'] = 0 ;    //未读
               }
            }
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $system_messages
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //按id查看消息
    public function getMessage(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('system_messages')->where($params)->find();

            //添加一条已读信息
            $ini['user_id'] = input('user_id') ;
            $ini['system_messages_id'] = input('id') ;
            Db::name('user_look')->insert($ini) ;

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "消息ID不能为空"
            ];
        }
    }
}