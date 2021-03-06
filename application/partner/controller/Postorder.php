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
                "msg" => "???????????????????????????????????????"
            ];
        }
//        $file = fopen('./lease.txt', 'a+');
//        fwrite($file, "-------------------????????????--------------------".json_encode($params)."\r\n");     //????????????
        $city_id = 0;
//        if (input('cityId') == "1000000001") {
        if (input('cityId') == "105") {
            $city_id = 62;
        }else if(input('cityId') == "1000000001"){  //??????????????????????????????
            return [
                "code" => APICODE_ERROR,
                "msg" => "?????????????????????????????????"
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
        //??????????????????????????????????????????
        $user = Db::name('user')->field('id,nickname')->where(['PassengerPhone' => input('mobile')])->find();
        if (!empty($user)) {
            $ini['user_id'] = $user['id'];
            $ini['user_phone'] = input('mobile');//"15776833552" ;//;//"15776833552" ; //; //;
            $ini['user_name'] = $user['nickname'];
        } else {
            //???????????????????????????
            $inii['PassengerPhone'] = input('mobile');
            $inii['nickname'] = '??????' . substr(input('mobile'), 7, 11);
            $inii['create_time'] = time();
            $inii['city_id'] = $city_id;

            $user_id = Db::name('user')->insertGetId($inii);

            $ini['user_id'] = $user_id;
            $ini['user_phone'] = input('mobile');
            $ini['user_name'] = '??????' . substr(input('mobile'), 7, 11);
        }

        $classification = "";
        if (input('serviceId') == 1) {
            $classification = "??????";
            $ini['status'] = 1;
            $ini['mt_status'] = 10 ;
            //??????
            $flags = $this->JudgmentFence(input('startPointLng'),input('startPointLat')) ;
            if($flags == 1){
                $business_id = 100;
            }
            //??????
            $flag = $this->JudgmentFence(input('endPointLng'),input('endPointLat')) ;
            if($flag == 1){
                $business_id = 100;
            }
        } else if (input('serviceId') == 2) {
            $classification = "??????";
            $ini['status'] = 1;
            $ini['mt_status'] = 10 ;
        }

        $ini['city_id'] = $city_id;
        $ini['create_time'] = time();
        $ini['classification'] = $classification;
        $ini['order_name'] = $classification . "??????(??????)";
        $ini['money'] = input('estimateAmount') / 100 ;
        $ini['mtorderid'] = input('mtOrderId');
        $ini['is_type'] = 1 ;

        $ini['partnerCarTypeId'] = input('partnerCarTypeId');

        //?????????????????????????????????????????????
        $scope = db("city_scope")->where("city_id", 62)->find();
        $centerPoolID = $this->getBlockIdByLatlnt($params["startPointLat"], $params["startPointLng"], $scope);

        $ini['other_block'] = $centerPoolID ;

        $order_id = 0 ;
        if(input('serviceId') == 1){
//                $order_id = Db::name('order')->insertGetId($ini);
        }else if(input('serviceId') == 2){
            //??????????????????????????????,???????????????????????????
//            $orders = Db::name('order')->where([ 'city_id' => 62 , "DepartTime" =>input('orderBookingTime'), 'business_type_id' => $business_type_id, "is_type" =>1,"status"=>1 ])->find();
            if(empty($orders)){
//                $order_id = Db::name('order')->insertGetId($ini);
            }
        }
        $data['partnerOrderId'] =  strval($order_id) ;
//        if(input('serviceId') == 2){           //?????????
//            $this->appointmentByCompany("???????????????",$company_id,$order_id,2,$business_id,$business_type_id,0);
//            if($city_id == 62){
//                $this->appointmentByCompany("???????????????",268,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("???????????????",269,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("???????????????",274,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("???????????????",275,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("???????????????",276,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("???????????????",277,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("???????????????",278,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("???????????????",279,$order_id,2,$business_id,$business_type_id,0);
//                $this->appointmentByCompany("???????????????", 280,$order_id, 2,$business_id,$business_type_id, 0);
//                $this->appointmentByCompany("???????????????", 282,$order_id, 2,$business_id,$business_type_id, 0);
//                $this->appointmentByCompany("???????????????", 283,$order_id, 2,$business_id,$business_type_id, 0);
//                $this->appointmentByCompany("???????????????", 284,$order_id, 2,$business_id,$business_type_id, 0);
//                $this->appointmentByCompany("???????????????", 285,$order_id, 2,$business_id,$business_type_id, 0);
//                $this->appointmentByCompany("???????????????", 286,$order_id, 2,$business_id,$business_type_id, 0);
//                $this->appointmentByCompany("???????????????", 289,$order_id, 2,$business_id,$business_type_id, 0);
//            }
//        }



        //????????????
//        if(input('serviceId') == 1){        //?????????????????????
//            $param['db'] = 0;
//            $param['centerPoolID'] = $centerPoolID;
//            $param['notifyUrl'] = "https://php.51jjcx.com/partner/Dispatch?order_id=".$order_id;
//            $param['around'] = 4;
//            $param['business_type'] = $business_type_id;
//            $param['business_id'] = $business_id;
////            fwrite($file, "-------------------business_id:--------------------".$business_id."\r\n");     //????????????
////            fwrite($file, "-------------------??????????????????:--------------------".json_encode($param)."\r\n");     //????????????
//            $datss = $this->request_post("http://127.0.0.1:8888/makeAsyncTask",$param) ;
////            fwrite($file, "-------------------????????????:--------------------".json_encode($datss)."\r\n");     //????????????
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
        $ch = curl_init(); // ?????????curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); // ??????????????????
        curl_setopt($ch, CURLOPT_HEADER, 0); // ??????header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // ?????????????????????????????????????????????
        curl_setopt($ch, CURLOPT_POST, 1); // post????????????
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // ?????? HTTP Header?????????????????????
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        // ??????????????????????????????
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch); // ??????curl

        curl_close($ch);
        return $data;
    }

    private function getBlockIdByLatlnt($lat, $lnt, $scope)
    {
        $blockX = abs(floor((($lnt - $scope['scope_longitude']) * 111000) / $scope['scope']));
        $blockY = abs(floor((($lat - $scope['scope_latitude']) * 111000) / $scope['scope']));
        return "$blockX,$blockY";
    }

    //??????????????????
    public function JudgmentFence($lng,$lat){
        $flag = 0 ;
        $point = $this->returnSquarePoint();
        foreach ($point as $key=>$value){
            $right_bottom_lat = $value['right_bottom']['lat'];//????????????
            $left_top_lat = $value['left_top']['lat'];//????????????
            $left_top_lng = $value['left_top']['lng'];//????????????
            $right_bottom_lng = $value['right_bottom']['lng'];//????????????

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
     * ??????
     */
    public function returnSquarePoint()
    {
        //???????????????
        $arr[] = [
            'left_top'=>array('lng'=>126.529391,'lat'=>45.85418),
            'right_top'=>array('lng'=>126.541005, 'lat'=>45.85418),
            'left_bottom'=>array('lng'=>126.529391, 'lat'=>45.845516),
            'right_bottom'=>array('lng'=>126.541005, 'lat'=>45.845516)
        ];
        //???????????????
        $arr[] = [
            'left_top'=>array('lng'=>126.707735,'lat'=>45.790913),
            'right_top'=>array('lng'=>126.715178, 'lat'=>45.790913),
            'left_bottom'=>array('lng'=>126.707735, 'lat'=>45.784671),
            'right_bottom'=>array('lng'=>126.715178, 'lat'=>45.784671)
        ];
        //???????????????????????????
        $arr[] = [
            'left_top'=>array('lng'=>126.23184,'lat'=>45.643694),
            'right_top'=>array('lng'=>126.278395, 'lat'=>45.643694),
            'left_bottom'=>array('lng'=>126.23184, 'lat'=>45.605382),
            'right_bottom'=>array('lng'=>126.278395, 'lat'=>45.605382)
        ];
        //???????????????
        $arr[] = [
            'left_top'=>array('lng'=>126.568023,'lat'=>45.711683),
            'right_top'=>array('lng'=>126.584406, 'lat'=>45.711683),
            'left_bottom'=>array('lng'=>126.568023, 'lat'=>45.700991),
            'right_bottom'=>array('lng'=>126.584406, 'lat'=>45.700991)
        ];
        //????????????
        $arr[] = [
            'left_top'=>array('lng'=>126.626314,'lat'=>45.764338),
            'right_top'=>array('lng'=>126.634838, 'lat'=>45.764338),
            'left_bottom'=>array('lng'=>126.626314, 'lat'=>45.75721),
            'right_bottom'=>array('lng'=>126.634838, 'lat'=>45.75721)
        ];
        //?????????
        $arr[] = [
            'left_top'=>array('lng'=>126.676307,'lat'=>45.722588),
            'right_top'=>array('lng'=>126.682514, 'lat'=>45.722588),
            'left_bottom'=>array('lng'=>126.676307, 'lat'=>45.719243),
            'right_bottom'=>array('lng'=>126.682514, 'lat'=>45.719243)
        ];
        return $arr;
    }
}