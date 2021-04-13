<?php

namespace app\backstage\controller;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;

class Advertisement extends Base
{
     //广告-公司列表
     public function AdvertisingCompany(){
         $params = [
             "city_id" => input('?city_id') ? input('city_id') : null,
             "company_id" => input('?company_id') ? input('company_id') : null,
         ];

         $params = $this->filterFilter($params);
         $required = ["city_id", "company_id"];
         if (!$this->checkRequire($required, $params)) {
             return [
                 "code" => APICODE_FORAMTERROR,
                 "msg" => "必填项不能为空，请检查输入"
             ];
         }

         $company = Db::name('company')->field('id,CompanyName')->where('superior_company','eq',0)->select() ;
         foreach ($company as $key=>$value){
             $company[$key]['children'] = Db::name('company')->field('id,CompanyName')->where('superior_company','eq',$value['id'])->select() ;
         }

         return [
             "code" => APICODE_SUCCESS,
             "msg" => "查询成功",
             "data" => $company
         ];
     }

     //广告
     public function AddAdvertising(){
        $data = input('') ;
        $ini = [] ;
        if(!empty($data['index'])){
            foreach ($data['index'] as $key=>$value){
                $ini[]=[
                    'company_id'=>input('company_id'),
                    'city_id'=>input('city_id'),
                    'type'=>$value['type'],
                    'is_simple'=>$value['is_simple'],
                    'img'=>$value['img'],
                    'lumo_title'=>$value['lump_title'],
                    'outer_link'=>$value['outer_link'],
                    'activity_id'=>$value['activity_id'],
                ];
            }
               $advertising = Db::name('advertising')->insertAll($ini) ;
           if($advertising){
               return [
                   "code" => APICODE_SUCCESS,
                   "msg" => "保存成功"
               ];
           }else{
               return [
                   "code" => APICODE_ERROR,
                   "msg" => "保存失败"
               ];
           }
        }
     }

     //广告列表
     public function AdvertisingList(){

     }




}