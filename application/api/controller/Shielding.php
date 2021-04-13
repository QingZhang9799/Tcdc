<?php

namespace app\api\controller;
use app\api\model\Conducteur;
use app\api\model\Company;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;

class Shielding extends Base
{
    //是否需要上传打点验证
    public function JudgmentFence(){
        $params = [
            "conducteur_id	" => input('?conducteur_id') ? input('conducteur_id') : null,
            "longitude" => input('?longitude') ? input('longitude') : null,
            "latitude" => input('?latitude') ? input('latitude')  : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["conducteur_id", "longitude", "latitude"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $point = $this->returnSquarePoint();
        $driver['latitude'] = input('latitude') ;
        $driver['longitude'] = input('longitude') ;

        $right_bottom_lat = $point['right_bottom']['lat'];//右下纬度
        $left_top_lat = $point['left_top']['lat'];//左上纬度
        $left_top_lng = $point['left_top']['lng'];//左上经度
        $right_bottom_lng = $point['right_bottom']['lng'];//右下经度

        if($driver['latitude']<$left_top_lat && $driver['latitude']>$right_bottom_lat && $driver['longitude']>$left_top_lng && $driver['longitude']<$right_bottom_lng){
            //在范围内，不传点
        }else{
            //不在范围内
        }
    }

    /** 围栏范围
     * @param $lng
     * @param $lat
     */
    public function returnSquarePoint()
    {
        $arr= array(
            'left_top'=>array('lat'=>'126.529391','lng'=>'45.85418'),
            'right_top'=>array('lat'=>'126.541005', 'lng'=>'45.85418'),
            'left_bottom'=>array('lat'=>'126.529391', 'lng'=>'45.845516'),
            'right_bottom'=>array('lat'=>'126.541005', 'lng'=>'45.845516')
        );
        return $arr;
    }

    //强制取消
    public function CoerceCancel(){
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id'),
            ];
            //查询订单
             $order_id =Db::name('order')->where(['conducteur_id'=>input('conducteur_id')])->where(['status'=>2])->value('id') ;
            if(!empty($order_id)){
                $ini['id'] = $order_id;
                $ini['status'] = 5;
                $res = db('order')->update($ini) ;
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
            }else{
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "更新成功",
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }
    }
}