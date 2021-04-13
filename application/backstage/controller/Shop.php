<?php

namespace app\backstage\controller;

use app\api\model\Conducteur;
use app\api\model\Company;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Exception;
use OSS\Core\OssException;
use OSS\OssClient;
use think\Controller;
use think\Image;
use think\Loader;

class Shop extends Base
{
    //增加商家分类
    public function AddShopsClassify(){
        $params = [
            "title" => input('?title') ? input('title') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["title"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $shops_classify = Db::name('shops_classify')->insert($params);

        if($shops_classify){
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

    //根据id获取分类
    public function getShopsClassify(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('shops_classify')->where($params)->find();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "分类ID不能为空"
            ];
        }
    }

    //修改分类
    public function UpdateShopsClassify(){

        $params = [
            "id" => input('?id') ? input('id') : null,
            "title" => input('?title') ? input('title') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id","title"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $res = Db::name('shops_classify')->update($params);

        if($res > 0){
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "更新成功",
            ];
        }else{
            return [
                "code" => APICODE_ERROR,
                "msg" => "更新失败",
            ];
        }
    }

    //分类列表
    public function ShopsClassifyList(){
        return self::pageReturnStrot(db('shops_classify'),"",'id desc');
    }

    //添加商家
    public function AddShops(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "company_id" => input('?company_id') ? input('company_id') : null,
            "classify_id" => input('?classify_id') ? input('classify_id')  : null,
            "shop_name" => input('?shop_name') ? input('shop_name')  : null,
            "img" => input('?img') ? input('img') : null,
            "shop_phone" => input('?shop_phone') ? input('shop_phone') : null,
            "shop_address" => input('?shop_address') ? input('shop_address') : null,
            "contact" => input('?contact') ? input('contact') : null,
            "is_opening_activity" => input('?is_opening_activity') ? input('is_opening_activity') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "company_id", "classify_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $data = input('') ;

        $shops = Db::name('shops')->insert($params) ;

        if($shops){
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

    //根据id获取商家信息
    public function getShops(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('shops')->where($params)->find();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "商家ID不能为空"
            ];
        }
    }

    //修改商家
    public function UpdateShops(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "company_id" => input('?company_id') ? input('company_id') : null,
            "classify_id" => input('?classify_id') ? input('classify_id')  : null,
            "shop_name" => input('?shop_name') ? input('shop_name')  : null,
            "img" => input('?img') ? input('img') : null,
            "shop_phone" => input('?shop_phone') ? input('shop_phone') : null,
            "shop_address" => input('?shop_address') ? input('shop_address') : null,
            "contact" => input('?contact') ? input('contact') : null,
            "is_opening_activity" => input('?is_opening_activity') ? input('is_opening_activity') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "company_id", "classify_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $data = input('') ;

        $res = Db::name('shops')->update($params) ;

        if($res > 0){
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "更新成功",
            ];
        }else{
            return [
                "code" => APICODE_ERROR,
                "msg" => "更新失败",
            ];
        }
    }

    //商家列表
    public function ShopsList(){
        $params = [] ;

        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;
        $sortBy = input('?orderBy') ? input('orderBy') : "id desc";

        $data = Db::name('shops')->alias('s')
                ->field('s.*,c.name as city_name,cs.title as classify_name')
                ->join('mx_cn_city c','c.id = s.city_id','left')
                ->join('mx_shops_classify cs','cs.id = s.classify_id','left')
                ->where(self::filterFilter($params))->order($sortBy)->page($pageNum, $pageSize)->select();

        $sum = Db::name('shops')->where(self::filterFilter($params))->count();

        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" => $data
        ];
    }
}