<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/31
 * Time: 16:44
 */

namespace app\backstage\controller;

use think\Db;
class Popup extends Base
{

    //增加弹窗
    public function AddPopup(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "company_id" => input('?company_id') ? input('company_id') : null,
            "content" => input('?content') ? input('content')  : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "company_id", "content"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $popup = Db::name('popup')->insert($params) ;

        if($popup){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'创建成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'创建失败'
            ];
        }
    }

    //修改弹窗
    public function UpdatePopup(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "company_id" => input('?company_id') ? input('company_id') : null,
            "content" => input('?content') ? input('content') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["id","city_id","company_id","content"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $res = Db::name('popup')->update($params) ;

        if($res > 0){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'更新成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'更新失败'
            ];
        }
    }

    //弹窗列表
    public function PopupList(){
        $params = [];
        return self::pageReturnStrot(db('popup'),$params,'id desc');
    }

    //根据id获取弹窗信息
    public function getPopup(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('popup')->where($params)->find();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "弹窗ID不能为空"
            ];
        }
    }
}