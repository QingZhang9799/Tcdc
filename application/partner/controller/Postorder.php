<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/7
 * Time: 13:29
 */

namespace app\partner\controller;

use app\backstage\controller\Gps;
use function GuzzleHttp\Psr7\str;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Request;

class PostOrder extends Base
{
    public function index()
    {
        $params = [
            "channel" => input('?channel') ? input('channel') : null,
            "timestamp" => input('?timestamp') ? input('timestamp') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "mobile" => input('?mobile') ? input('mobile') : null,
            "cityId" => input('?cityId') ? input('cityId') : null,
            "startPointAddress" => input('?startPointAddress') ? input('startPointAddress') : null,
            "startPointLng" => input('?startPointLng') ? input('startPointLng') : null,
            "startPointLat" => input('?startPointLat') ? input('startPointLat') : null,
            "startPointName" => input('?startPointName') ? input('startPointName') : null,
            "endPointAddress" => input('?endPointAddress') ? input('endPointAddress') : null,
            "endPointLng" => input('?endPointLng') ? input('endPointLng') : null,
            "endPointLat" => input('?endPointLat') ? input('endPointLat') : null,
            "endPointName" => input('?endPointName') ? input('endPointName') : null,
            "partnerCarTypeId" => input('?partnerCarTypeId') ? input('partnerCarTypeId') : null,
            "estimateId" => input('?estimateId') ? input('estimateId') : null,
            "estimateAmount" => input('?estimateAmount') ? input('estimateAmount') : null,
            "mtOrderId" => input('?mtOrderId') ? input('mtOrderId') : null,
            "orderBookingTime" => input('?orderBookingTime') ? input('orderBookingTime') : null,
            "serviceId" => input('?serviceId') ? input('serviceId') : null,
            "maxEda" => input('?maxEda') ? input('maxEda') : null,
            "maxEta" => input('?maxEta') ? input('maxEta') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["channel"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
//        $file = fopen('./lease.txt', 'a+');
//        fwrite($file, "-------------------下单参数--------------------".json_encode($params)."\r\n");     //司机电话
        $city_id = 0;
//        if (input('cityId') == "1000000001") {
        if (input('cityId') == "105") {
            $city_id = 62;
        }else if(input('cityId') == "1000000001"){  //测试服务器，不让下单
            return [
                "code" => APICODE_ERROR,
                "msg" => "测试服务器，不让下单。"
            ];
        }
        $company_id = Db::name('company')->where(['city_id' => $city_id])->value('id');

        $ini['company_id'] = $company_id;
        $order_code = "MT" . input('cityId') . '0' . date('YmdHis') . rand(0000, 999);
        $ini['OrderId'] = $order_code;
        $ini['DepartTime'] = input('orderBookingTime');
        $ini['OrderTime'] = 0;
        $ini['Departure'] = input('startPointAddress');
        $ini['DepLongitude'] = input('startPointLng');
        $ini['DepLatitude'] = input('startPointLat');
        $ini['Destination'] = input('endPointName');
        $ini['origin'] = input('startPointName');
        $ini['DestLongitude'] = input('endPointLng');
        $ini['DestLatitude'] = input('endPointLat');

        $business_id = 2 ;
        $business_type_id = 0;

        $ini['business_id'] = $business_id;
        if(input('partnerCarTypeId') == "1401"){
            $ini['business_type_id'] = 3;
            $business_type_id = 3;
        }else if(input('partnerCarTypeId') == "1402"){
            $ini['business_type_id'] = 18;
            $business_type_id = 18;
        }else if(input('partnerCarTypeId') == "1403"){
            $ini['business_type_id'] = 19;
            $business_type_id = 19;
        }
        //根据下单手机号，获取用户信息
        $user = Db::name('user')->field('id,nickname')->where(['PassengerPhone' => input('mobile')])->find();
        if (!empty($user)) {
            $ini['user_id'] = $user['id'];
            $ini['user_phone'] = input('mobile');//"15776833552" ;//;//"15776833552" ; //; //;
            $ini['user_name'] = $user['nickname'];
        } else {
            //系统里没有创建用户
            $inii['PassengerPhone'] = input('mobile');
            $inii['nickname'] = '同城' . substr(input('mobile'), 7, 11);
            $inii['create_time'] = time();
            $inii['city_id'] = $city_id;

            $user_id = Db::name('user')->insertGetId($inii);

            $ini['user_id'] = $user_id;
            $ini['user_phone'] = input('mobile');
            $ini['user_name'] = '同城' . substr(input('mobile'), 7, 11);
        }

        $classification = "";
        if (input('serviceId') == 1) {
            $classification = "实时";
            $ini['status'] = 1;
            $ini['mt_status'] = 10 ;
            //起点
            $flags = $this->JudgmentFence(input('startPointLng'),input('startPointLat')) ;
            if($flags == 1){
                $business_id = 100;
            }
            //终点
            $flag = $this->JudgmentFence(input('endPointLng'),input('endPointLat')) ;
            if($flag == 1){
                $business_id = 100;
            }
        } else if (input('serviceId') == 2) {
            $classification = "预约";
            $ini['status'] = 1;
            $ini['mt_status'] = 10 ;
        }

        $ini['city_id'] = $city_id;
        $ini['create_time'] = time();
        $ini['classification'] = $classification;
        $ini['order_name'] = $classification . "订单(专车)";
        $ini['money'] = input('estimateAmount') / 100 ;
        $ini['mtorderid'] = input('mtOrderId');
        $ini['is_type'] = 1 ;

        $ini['partnerCarTypeId'] = input('partnerCarTypeId');

        //根据下单经度和纬度查询附近车辆
        $scope = db("city_scope")->where("city_id", 62)->find();
        $centerPoolID = $this->getBlockIdByLatlnt($params["startPointLat"], $params["startPointLng"], $scope);

        $ini['other_block'] = $centerPoolID ;

        $order_id = 0 ;
        if(input('serviceId') == 1){
//                $order_id = Db::name('order')->insertGetId($ini);
        }else if(input('serviceId') == 2){
            //预约时间就能创建一个,相同机型只能有一个
//            $orders = Db::name('order')->where([ 'city_id' => 62 , "DepartTime" =>input('orderBookingTime'), 'business_type_id' => $business_type_id, "is_type" =>1,"status"=>1 ])->find();
            if(empty($orders)){
//                $order_id = Db::name('order')->insertGetId($ini);
            }
        }
        $data['partnerOrderId'] =  strval($order_id) ;
//        if(input('serviceId') == 2){           //实时单
//            $this->appointmentByCompany("预约单来了",$company_id,$order_id,2,$business_id,$business_type_id,0);
//            if($city_id == 62){
//                $this->appointmentByCompany("预约单来了",268,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("预约单来了",269,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("预约单来了",274,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("预约单来了",275,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("预约单来了",276,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("预约单来了",277,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("预约单来了",278,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("预约单来了",279,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("预约单来了", 280,$order_id, 2,$business_id,$business_type_id, 0);
//                $this->appointmentByCompany("预约单来了", 282,$order_id, 2,$business_id,$business_type_id, 0);
//                $this->appointmentByCompany("预约单来了", 283,$order_id, 2,$business_id,$business_type_id, 0);
//                $this->appointmentByCompany("预约单来了", 284,$order_id, 2,$business_id,$business_type_id, 0);
//                $this->appointmentByCompany("预约单来了", 285,$order_id, 2,$business_id,$business_type_id, 0);
//                $this->appointmentByCompany("预约单来了", 286,$order_id, 2,$business_id,$business_type_id, 0);
//                $this->appointmentByCompany("预约单来了", 289,$order_id, 2,$business_id,$business_type_id, 0);
//            }
//        }



        //异步处理
//        if(input('serviceId') == 1){        //实时时候，异步
//            $param['db'] = 0;
//            $param['centerPoolID'] = $centerPoolID;
//            $param['notifyUrl'] = "https://php.51jjcx.com/partner/Dispatch?order_id=".$order_id;
//            $param['around'] = 4;
//            $param['business_type'] = $business_type_id;
//            $param['business_id'] = $business_id;
////            fwrite($file, "-------------------business_id:--------------------".$business_id."\r\n");     //司机电话
////            fwrite($file, "-------------------发起异步参数:--------------------".json_encode($param)."\r\n");     //司机电话
//            $datss = $this->request_post("http://127.0.0.1:8888/makeAsyncTask",$param) ;
////            fwrite($file, "-------------------发起异步:--------------------".json_encode($datss)."\r\n");     //司机电话
//        }

        return [
            'result' => 0,
            'message' => 'SUCCESS',
            'data' => $data,
        ];
    }
    function appointmentByCompany($title, $companyId, $message,$type,$business_id,$business_type_id,$conducteur_id){
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
        $header = array(
            "Authorization:Basic $base64",
            "Content-Type:application/json"
        );
        $param=array("platform"=>"all","audience"=>array("tag"=>array("Company_$companyId")),"message"=>array("msg_content"=>$message.",".$type.",".$companyId.",".$business_id.",".$business_type_id.",".$conducteur_id,"title"=>$title));
        $params=json_encode($param);
        $res = $this->request_post($url, $params, $header);
        $res_arr = json_decode($res, true);
    }
    function request_post($url = "", $param = "", $header = "")
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init(); // 初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); // 抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); // 设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); // post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch); // 运行curl

        curl_close($ch);
        return $data;
    }

    private function getBlockIdByLatlnt($lat, $lnt, $scope)
    {
        $blockX = abs(floor((($lnt - $scope['scope_longitude']) * 111000) / $scope['scope']));
        $blockY = abs(floor((($lat - $scope['scope_latitude']) * 111000) / $scope['scope']));
        return "$blockX,$blockY";
    }

    //判断合不合规
    public function JudgmentFence($lng,$lat){
        $flag = 0 ;
        $point = $this->returnSquarePoint();
        foreach ($point as $key=>$value){
            $right_bottom_lat = $value['right_bottom']['lat'];//右下纬度
            $left_top_lat = $value['left_top']['lat'];//左上纬度
            $left_top_lng = $value['left_top']['lng'];//左上经度
            $right_bottom_lng = $value['right_bottom']['lng'];//右下经度

            if(floatval($lat)<$left_top_lat &&
                floatval($lat)>$right_bottom_lat &&
                floatval($lng)>$left_top_lng &&
                floatval($lng)<$right_bottom_lng
            ){

                $flag = 1 ;
                return $flag ;
            }else{
                $flag = 0 ;
            }
        }
        return $flag ;
    }

    /**
     * 围栏
     */
    public function returnSquarePoint()
    {
        //哈尔滨北站
        $arr[] = [
            'left_top'=>array('lng'=>126.529391,'lat'=>45.85418),
            'right_top'=>array('lng'=>126.541005, 'lat'=>45.85418),
            'left_bottom'=>array('lng'=>126.529391, 'lat'=>45.845516),
            'right_bottom'=>array('lng'=>126.541005, 'lat'=>45.845516)
        ];
        //哈尔滨东站
        $arr[] = [
            'left_top'=>array('lng'=>126.707735,'lat'=>45.790913),
            'right_top'=>array('lng'=>126.715178, 'lat'=>45.790913),
            'left_bottom'=>array('lng'=>126.707735, 'lat'=>45.784671),
            'right_bottom'=>array('lng'=>126.715178, 'lat'=>45.784671)
        ];
        //哈尔滨太平国际机场
        $arr[] = [
            'left_top'=>array('lng'=>126.23184,'lat'=>45.643694),
            'right_top'=>array('lng'=>126.278395, 'lat'=>45.643694),
            'left_bottom'=>array('lng'=>126.23184, 'lat'=>45.605382),
            'right_bottom'=>array('lng'=>126.278395, 'lat'=>45.605382)
        ];
        //哈尔滨西站
        $arr[] = [
            'left_top'=>array('lng'=>126.568023,'lat'=>45.711683),
            'right_top'=>array('lng'=>126.584406, 'lat'=>45.711683),
            'left_bottom'=>array('lng'=>126.568023, 'lat'=>45.700991),
            'right_bottom'=>array('lng'=>126.584406, 'lat'=>45.700991)
        ];
        //哈尔滨站
        $arr[] = [
            'left_top'=>array('lng'=>126.626314,'lat'=>45.764338),
            'right_top'=>array('lng'=>126.634838, 'lat'=>45.764338),
            'left_bottom'=>array('lng'=>126.626314, 'lat'=>45.75721),
            'right_bottom'=>array('lng'=>126.634838, 'lat'=>45.75721)
        ];
        //香坊站
        $arr[] = [
            'left_top'=>array('lng'=>126.676307,'lat'=>45.722588),
            'right_top'=>array('lng'=>126.682514, 'lat'=>45.722588),
            'left_bottom'=>array('lng'=>126.676307, 'lat'=>45.719243),
            'right_bottom'=>array('lng'=>126.682514, 'lat'=>45.719243)
        ];
        return $arr;
    }
}