<?php
namespace app\user\controller;

use think\Controller;
use think\Db;

class Estimate extends Base
{
    //预估价详情
    public function EstimateParticulars(){
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "origin" => input('?origin') ? input('origin') : null,
            "Destination" => input('?Destination') ? input('Destination') : null,
            "DepLongitude" => input('?DepLongitude') ? input('DepLongitude') : null,
            "DepLatitude" => input('?DepLatitude') ? input('DepLatitude') : null,
            "DestLongitude" => input('?DestLongitude') ? input('DestLongitude') : null,
            "DestLatitude" => input('?DestLatitude') ? input('DestLatitude') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "classification" => input('?classification') ? input('classification') : null,
            "business_type_id" => input('?business_type_id') ? input('business_type_id') : null,
            "business_id" => input('?business_id') ? input('business_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "origin", "Destination", "DepLongitude"
            , "DepLatitude", "DestLongitude", "DestLatitude", "city_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //获取一个机型的预估价明细
        $start = $params['DepLongitude'] . ',' . $params['DepLatitude'];
        $end = $params['DestLongitude'] . ',' . $params['DestLatitude'];

        $company_id = Db::name('company')->where(['city_id' => input('city_id')])->value('id');

        $key = Db::name('company')->where(['city_id' => input('city_id')])->value('key');

        $autonavi = $this->autonavi($start, $end, $key);

        $distance = $autonavi[0]['distance'];
        $duration = $autonavi[0]['duration'];

        //获取公司id
        $data = [] ;
        if(input('classification') == "实时" || input('classification') == "\U5b9e\U65f6"){
            $data = Db::name('company_rates')->alias('c')
                ->field('b.right_degrees_img as b_img,b.title,c.*')
                ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
                ->where(['c.business_id' => input('business_id')])
                ->where(['c.businesstype_id' => input('business_type_id')])
                ->where(['c.company_id' => $company_id])
                ->select();
        }else if(input('classification') == "预约" || input('classification') == "代驾" || input('classification') == "\U9884\U7ea6" || input('classification') == "\U4ee3\U9a7e"){
            $data = Db::name('company_appointment_rates')->alias('c')
                ->field('b.right_degrees_img as b_img,b.title,c.*')
                ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
                ->where(['c.business_id' => input('business_id')])
                ->where(['c.businesstype_id' => input('business_type_id')])
                ->where(['c.company_id' => $company_id])
                ->select();
        }

        $activity_money = 0; //优惠金额
        $special_money = 0; //实时单价格
        $appointment_money = 0; //预约单价格
        $flag = 0;
        $orderController = new \app\api\controller\Order();
        foreach ($data as $key => $value) {
            $timeSlicing = $orderController->judgmentTimeSlicing(time(), time() + $autonavi[0]['duration'], $value);
            $timeSlicingResult=[];
            foreach ($timeSlicing as $key1 => $value1) {        //按天 ，分割条数
                foreach ($value1['timeSplice'] as $k => $v) {        //每天时间段
                    $timeSlicingResult[$k]['valuation'] = $v['moneyRule'];
                    $timeSlicingResult[$k]['times'] = [$v['startTime'], $v['endTime'], $key1];
                }
            }
            $timeSlicingResult=$timeSlicingResult[0]["valuation"];
            $timeSlicingResult["value"]=$value;
            $value['StartMile']=$timeSlicingResult["startMile"];
            $value['Tokinaga']=$timeSlicingResult["startMin"];
            $value['MileageFee']=$timeSlicingResult["moneyPerMile"];
            $value['StartFare']=$timeSlicingResult["startMoney"];
            $value['HowFee']=$timeSlicingResult["moneyPerMin"];

            //计算价格
            $Kilometer = $distance / 1000;  //公里
            $Minute = $duration / 60;       //分钟

            //远途公里
            $munication = $Kilometer - $value['LongKilometers'];

            $residue = 0;
            if ($Kilometer >= $value['StartMile']) {
                $residue = $Kilometer - $value['StartMile'];       //超出距离
            } else if ($Kilometer < $value['StartMile']) {
                $residue = 0;                                        //小于起步里程
            }
            $min = 0;
            if ($Minute >= $value['Tokinaga']) {
                $min = $Minute - $value['Tokinaga'];       //超出时长
            } else if ($Minute < $value['Tokinaga']) {
                $min = 0;                                        //小于起步时长
            }
            $Longfee = 0;          //远途费
            $LongKilometers = 0;
            if ($munication > 0) {
                $LongKilometers = $munication;
                $Longfee = $munication * $value['Longfee'];
            }
            $costs_money = $residue * $value['MileageFee'];        //距离费用
            $costs_min_money = $min * $value['HowFee'];            //时长费用

            $money = sprintf("%.2f", $costs_money) + sprintf("%.2f", $costs_min_money) + $Longfee+$value['StartFare'];
            $StartingPrice = [
                'StartFare'=>$value['StartFare'],                 //起步价
                'MileageFee'=>$value['StartMile'],                //里程
                'tokinaga'=>$value['Tokinaga'],                   //时长
            ];
            $Mileage_data = [
                'SumMile'=>sprintf("%.2f", $residue+$value['StartMile']) ,          //总里程
                'MileageFee'=>$value['StartMile'] ,               //起步里程
                'costs_money'=>sprintf("%.2f", $costs_money) ,                      //里程费
            ] ;
            $tokinaga_data = [
                'SumTokinaga'=>$min+$value['Tokinaga'] ,         //总时长
                'Tokinaga'=>$value['Tokinaga'] ,                  //起步时长
                'costs_min_money'=>sprintf("%.2f", $costs_min_money) ,              //时长费
            ] ;
            $data[$key]['StartingPrice'] = $StartingPrice;
            $data[$key]['Mileage'] = $Mileage_data;
            $data[$key]['tokinaga'] = $tokinaga_data;
            $data[$key]['money'] = sprintf("%.2f", $money);
        }
        $discounts = $this->DiscountsMoney(input('user_id'),input('business_id'),input('business_type_id'),input('city_id'),$money) ;
        return ['code' => APICODE_SUCCESS, 'data' => $data, 'activity_money' => sprintf("%.2f", $activity_money),'discounts'=>$discounts];
    }
    //优惠金额
    private function DiscountsMoney($user_id,$business_id,$businesstype_id,$city_id,$fare){
        $money = 0;

        $user_coupon = Db::name('user_coupon')->where(['city_id' => $city_id, 'user_id' => $user_id])->where('is_use', 'eq', 0)->select();

        foreach ($user_coupon as $key => $value) {
            if ($value['type'] == 4 || $value['type'] == 2) {         //最高金额,满减
                if (floatval($fare) < floatval($value['pay_money'])) {
                    unset($user_coupon[$key]);
                }
            }
        }

        foreach ($user_coupon as $key => $value) {
//               //匹配业务
            $type = explode(',', $value['order_type']);
            foreach ($type as $k => $v) {
                if ($business_id != $v) {
                    unset($user_coupon[$key]);
                } else {
                    $flag = $this->activityTimeVerify($value['times']);
                    if ($flag == 1) {
                        $minus_money = $value['minus_money'] ;
                        if($minus_money >= $money){
                            $money = $minus_money;
                        }
                    }
                }
            }
        }
        return $money;
    }
    //活动时间验证
    private function activityTimeVerify($times)
    {
        $flag = 0;
        $time = time();
        $activity = json_decode($times, true);
        if (!empty($activity['startTime']) && !empty($activity['endTime'])) {      //俩个都有值
            if ($time > $activity['startTime'] && $time < $activity['endTime']) {
                $flag = 1;
            }
        }
        if (!empty($activity['startTime']) && empty($activity['endTime'])) {       //起始有值,终点为空
            if ($time > $activity['startTime']) {
                $flag = 1;
            }
        }
        if (empty($activity['startTime']) && !empty($activity['endTime'])) {       //起点为空,终点有值
            if ($time < $activity['endTime']) {
                $flag = 1;
            }
        }
        return $flag;
    }

    public function autonavi($origins, $destination, $key)
    {
//        $key = "7609d7e35683fc4087c4351c6b8d96b5";
//        $origins = "116.481028,39.989643";    //|114.481028,39.989643
        //出发点 支持100个坐标对（公交仅支持20个），坐标对见用“| ”分隔；经度和纬度用","分隔
//        $destination = "114.465302,40.004717";
        //目的地  目的地参数只能有一个
        $address = "https://restapi.amap.com/v3/distance?origins=" . $origins;
        $address .= "&destination=" . $destination . "&output=json&key=" . $key;
        // 执行请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $address);
        $data = curl_exec($ch);
//        dump($data);
        curl_close($ch);

        $result = json_decode($data);
        $arr = $this->object_array($result);

        return $arr['results'];
//        dump($arr);
    }
    //对象转数组
    public function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }

    //计价规则
    public function ValuationRegular(){
        $params = [
            "business_id" => input('?business_id') ? input('business_id') : null ,
            "business_type_id" => input('?business_type_id') ? input('business_type_id') : null ,
            "city_id" => input('?city_id') ? input('city_id') : null ,
        ];

        $params = $this->filterFilter($params);
        $required = ["business_id", "business_type_id","city_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $company_id = Db::name('company')->where(['city_id' => input('city_id') ])->value('id') ;
        $rates = [] ;
        if(input('business_id') != 10){
            $rates = Db::name('company_rates')->where(['company_id' =>$company_id , 'business_id'=>input('business_id'),'businesstype_id'=>input('business_type_id') ])->find() ;
        }else{
            $rates = Db::name('company_appointment_rates')->where(['company_id' =>$company_id , 'business_id'=>input('business_id'),'businesstype_id'=>input('business_type_id') ])->find() ;
        }
        $business = Db::name('business')->where(['id' => input('business_id') ])->find() ;
        $business_type = Db::name('business_type')->where(['id' => input('business_type_id') ])->find() ;

        $title = [] ;
        if(input('business_id') == 10){  //代驾
            $title = [
                'business_name'=>$business['business_name'],
            ];
        }else{
            $title = [
                'business_name'=>$business['business_name'],
                'title'=>$business_type['title'],
                'img'=>$business_type['img'],
            ];
        }

        $data['title'] = $title;

        //起步价
        $StartingPrice = [
            'time1'=>$rates['weehoursOn']."-".$rates['weehoursOff'],
            'time2'=>$rates['MorningPeakTimeOn']."-".$rates['MorningPeakTimeOff'],
            'time3'=>$rates['EveningPeakTimeOn']."-".$rates['EveningPeakTimeOff'],
            'time4'=>$rates['UsuallyLateNightOn']."-".$rates['UsuallyLateNightOff'],
            'price1'=>$rates['StartFare'],                //普通时段价格
            'price2'=>$rates['WeehoursStartFare'],
            'price3'=>$rates['MorningStartFare'],
            'price4'=>$rates['EveningStartFare'],
            'price5'=>$rates['NightStartFare'],
            'mileage'=>$rates['StartMile'],
            'tokinaga'=>$rates['Normal_starting_time'],
        ];
        $data['StartingPrice'] = $StartingPrice ;

        //起步里程
        $StartMileage = [
            'time1'=>$rates['weehoursOn']."-".$rates['weehoursOff'],
            'time2'=>$rates['MorningPeakTimeOn']."-".$rates['MorningPeakTimeOff'],
            'time3'=>$rates['EveningPeakTimeOn']."-".$rates['EveningPeakTimeOff'],
            'time4'=>$rates['UsuallyLateNightOn']."-".$rates['UsuallyLateNightOff'],
            'price1'=>$rates['StartFare'],                //普通时段价格
            'price2'=>$rates['WeehoursStartFare'],
            'price3'=>$rates['MorningStartFare'],
            'price4'=>$rates['EveningStartFare'],
            'price5'=>$rates['NightStartFare'],
        ];
        $data['StartMileage'] = $StartMileage ;

        //里程费
        $MileageFee = [
            'time1'=>$rates['weehoursOn']."-".$rates['weehoursOff'],
            'time2'=>$rates['MorningPeakTimeOn']."-".$rates['MorningPeakTimeOff'],
            'time3'=>$rates['EveningPeakTimeOn']."-".$rates['EveningPeakTimeOff'],
            'time4'=>$rates['UsuallyLateNightOn']."-".$rates['UsuallyLateNightOff'],
            'price1'=>$rates['MileageFee'],                //普通时段价格
            'price2'=>$rates['WeehoursMileageFee'],
            'price3'=>$rates['MorningMileageFee'],
            'price4'=>$rates['EveningMileageFee'],
            'price5'=>$rates['NightMileageFee'],
        ];
        $data['MileageFee'] = $MileageFee ;

        //时长费
        $HowFee = [
            'time1'=>$rates['weehoursOn']."-".$rates['weehoursOff'],
            'time2'=>$rates['MorningPeakTimeOn']."-".$rates['MorningPeakTimeOff'],
            'time3'=>$rates['EveningPeakTimeOn']."-".$rates['EveningPeakTimeOff'],
            'time4'=>$rates['UsuallyLateNightOn']."-".$rates['UsuallyLateNightOff'],
            'price1'=>$rates['HowFee'],                //普通时段价格
            'price2'=>$rates['WeehoursHowFee'],
            'price3'=>$rates['MorningHowFee'],
            'price4'=>$rates['EveningHowFee'],
            'price5'=>$rates['NightHowFee'],
        ];
        $data['HowFee'] = $HowFee ;

        //远途费
        $data['LongFee'] = [
            'LongKilometers'=>$rates['LongKilometers'],
            'Longfee'=>$rates['Longfee'],
        ];

        //------------------------节假日------------------------------
        //起步价
        $HolidaysStartingPrice = [
            'time1'=>$rates['HolidaysWeeOn']."-".$rates['HolidaysWeeOff'],
            'time2'=>$rates['HolidaysMorningOn']."-".$rates['HolidaysMorningOff'],
            'time3'=>$rates['HolidaysEveningOn']."-".$rates['HolidaysEveningOff'],
            'time4'=>$rates['HolidaysLateNightOn']."-".$rates['HolidaysLateNightOff'],
            'price1'=>$rates['HolidaysStartFare'],                //普通时段价格
            'price2'=>$rates['HolidaysWeehoursStartFare'],
            'price3'=>$rates['HolidaysMorningStartFare'],
            'price4'=>$rates['HolidaysEveningStartFare'],
            'price5'=>$rates['HolidaysNightStartFare'],
            'mileage'=>$rates['HolidaysStartMile'],
            'tokinaga'=>$rates['HolidaysStartingTokinaga'],
        ];
        $data['HolidaysStartingPrice'] = $HolidaysStartingPrice ;
        //里程费
        $HolidaysMileageFee = [
            'time1'=>$rates['HolidaysWeeOn']."-".$rates['HolidaysWeeOff'],
            'time2'=>$rates['HolidaysMorningOn']."-".$rates['HolidaysMorningOff'],
            'time3'=>$rates['HolidaysEveningOn']."-".$rates['HolidaysEveningOff'],
            'time4'=>$rates['HolidaysLateNightOn']."-".$rates['HolidaysLateNightOff'],
            'price1'=>$rates['HolidaysMileageFee'],                //普通时段价格
            'price2'=>$rates['HolidaysWeehoursMileageFee'],
            'price3'=>$rates['HolidaysMorningMileageFee'],
            'price4'=>$rates['HolidaysEveningMileageFee'],
            'price5'=>$rates['HolidaysNightMileageFee'],
        ];
        $data['HolidaysMileageFee'] = $HolidaysMileageFee ;
        //时长费
        $HolidaysHowFee = [
            'time1'=>$rates['HolidaysWeeOn']."-".$rates['HolidaysWeeOff'],
            'time2'=>$rates['HolidaysMorningOn']."-".$rates['HolidaysMorningOff'],
            'time3'=>$rates['HolidaysEveningOn']."-".$rates['HolidaysEveningOff'],
            'time4'=>$rates['HolidaysLateNightOn']."-".$rates['HolidaysLateNightOff'],
            'price1'=>$rates['HolidaysHowFee'],                //普通时段价格
            'price2'=>$rates['HolidaysWeehoursHowFee'],
            'price3'=>$rates['HolidaysMorningHowFee'],
            'price4'=>$rates['HolidaysEveningHowFee'],
            'price5'=>$rates['HolidaysNightHowFee'],
        ] ;
        $data['HolidaysHowFee'] = $HolidaysHowFee ;
        //远途费
        $data['HolidaysLongFee'] = [
           'HolidaysStartingKilometre'=>$rates['HolidaysStartingKilometre'],
           'HolidaysStartingLongfee'=>$rates['HolidaysStartingLongfee']
        ] ;

        return [
            'code'=>APICODE_SUCCESS,
            'msg'=>'成功',
            'data'=>$data,
        ];
    }

    //常见问题列表
    public function QuestionsList(){
        $questions = Db::name('questions')->select() ;

        return [
            "code" => APICODE_SUCCESS,
            "msg" => "查询成功",
            "data" => $questions
        ];
    }
}