<?php

namespace app\partner\controller;
use app\backstage\controller\Gps;
use think\Cache;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Request;

class Queryreference extends Base
{
    public function index()
    {
        $params = [
            "tcOrderStatus" => input('?tcOrderStatus') ? input('tcOrderStatus') : null,
            "supplierCode" => input('?supplierCode') ? input('supplierCode') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "clientId" => input('?clientId') ? input('clientId') : null,

            "timestamp" => input('?timestamp') ? input('timestamp') : null,                  //请求时间，Unix Timestamp单位秒
            "slon" => input('?slon') ? input('slon') : null,                                   //出发地经度
            "slat" => input('?slat') ? input('slat') : null,                                   //出发地纬度
            "sname" => input('?sname') ? input('sname') : null,                                //出发地名称
            "dlon" => input('?dlon') ? input('dlon') : null,                                   //目的地经度
            "dlat" => input('?dlat') ? input('dlat') : null,                                   //目的地纬度
            "dname" => input('?dname') ? input('dname') : null,                                //目的地名称
            "service_id" => input('?service_id') ? input('service_id') : null,               //用车服务类型
            "ride_type" => input('?ride_type') ? input('ride_type') : null,                  //运力类型，多个用逗号分隔
            "city_code" => input('?city_code') ? input('city_code') : null,                  //城市code，默认为出发地所在城市code
            "departure_time" => input('?departure_time') ? input('departure_time') : null,  //出发时间，Unix timestamp，单位秒（S），默认为当前时间. 针对预约单
            "flight_no" => input('?flight_no') ? input('flight_no') : null,                   //航班号，接机单必填
            "flight_date" => input('?flight_date') ? input('flight_date') : null,             //航班起飞日期，格式yyyy-MM-dd，接机单必填
            "flight_delay_time" => input('?flight_delay_time') ? input('flight_delay_time') : null,//非必选 航班到达后,延时 [10(含) 至 90(含)]分钟后用车
            "charge_type" => input('?charge_type') ? input('charge_type') : null,             //用车模式（0 一口价   1预估价）
            "ref_code" => input('?ref_code') ? input('ref_code') : null,                       //渠道code
        ];

        $params = $this->filterFilter($params);
        $required = ["tcOrderStatus"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $start = $params['startPointLng'] . ',' . $params['startPointLat'];
        $end = $params['endPointLng'] . ',' . $params['endPointLat'];

        $city = "哈尔滨市";
        $city_id = Db::name('cn_city')->where(['name' => $city])->value('id');
        $company_id = Db::name('company')->where(['city_id' => $city_id])->value('id');
        $key = Db::name('company')->where(['city_id' => $city_id])->value('key');
        $autonavi = $this->autonavi($start, $end, $key);

        $distance = $autonavi[0]['distance'];
        $duration = $autonavi[0]['duration'];

//          //获取公司id
//        $rates = Db::name('company_rates')->alias('c')
//            ->field('b.img as b_img,b.title,c.*')
//            ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
//            ->where(['c.business_id' => 2])
//            ->where(['c.company_id' => $company_id])->select();

        $rates = [] ;
        if(input('serviceId') == 1 ){
            $rates = Db::name('company_rates')->alias('c')
                ->field('b.img as b_img,b.title,c.*')
                ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
                ->where(['c.business_id' => 2])
                ->where(['c.company_id' => $company_id])->select();
        }else if(input('serviceId') == 2 ){
            $rates = Db::name('company_appointment_rates')->alias('c')
                ->field('b.img as b_img,b.title,c.*')
                ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
                ->where(['c.business_id' => 2])
                ->where(['c.company_id' => $company_id])->select();
        }

        //计算价格
//        $file = fopen('./estimate.txt', 'a+');
//        fwrite($file, "-------------------rates--------------------" .json_encode($rates) ."\r\n");
        $data = [];
        $details=[];
        $orderController = new \app\api\controller\Order();
        foreach ($rates as $key=>$value){
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
//            var_dump($timeSlicingResult);
            $value['StartMile']=$timeSlicingResult["startMile"];
            $value['Tokinaga']=$timeSlicingResult["startMin"];
            $value['MileageFee']=$timeSlicingResult["moneyPerMile"];
            $value['StartFare']=$timeSlicingResult["startMoney"];
            $value['HowFee']=$timeSlicingResult["moneyPerMin"];

//            fwrite($file, "-------------------value2--------------------" .json_encode($value) ."\r\n");

            $result = $this->calculateprice($distance,$duration,$value);

            //返回1401，1402,1403
            array_push($details,[
                'partnerCarTypeId' => $result['partnerCarTypeId'],
                'estimateId' => "10",
                'distance' => intval($distance),
                'estimateTime' => intval($duration),
                'estimatePrice' => (int)($result['moneys']),
                'partnerReduce'=>0,
                'travelPrice'=>(int)($result['moneys']),
                'distancePrice'=>(int)($result['distancePrice']),
                'timePrice'=>(int)($result['timePrice']),
                'initPrice'=>(int)($result['initPrice']),
                'minPrice'=>0,
                'nightPrice'=>0,
                'driveLongDistancePrice'=>0,
                'dynamicPrice'=>0,
                'periodTimePrice'=>0,
                'periodDistancePrice'=>0,
                'normalTimePrice'=>0,
                'normalDistancePrice'=>0,
//                'dispatchPrice'=>0,
//                'driveHighwayPrice'=>0,
            ]);
        }
        $data['details'] = $details;

//        fwrite($file, "-------------------data:--------------------" . json_encode($data) . "\r\n");
        return [
            'result' => 0,
            'message' => 'SUCCESS',
            'data' => $data
        ];
    }
    private function calculateprice($distance,$duration,$rates){

        $Kilometer = $distance / 1000;  //公里
        $Minute = $duration / 60;       //分钟

        $residue = 0;
        if ($Kilometer >= $rates['StartMile']) {
            $residue = $Kilometer - $rates['StartMile'];       //超出距离
        } else if ($Kilometer < $rates['StartMile']) {
            $residue = 0;                                        //小于起步里程
        }
        $min = 0;
        if ($Minute >= $rates['Tokinaga']) {
            $min = $Minute - $rates['Tokinaga'];       //超出时长
        } else if ($Minute < $rates['Tokinaga']) {
            $min = 0;                                        //小于起步时长
        }
        $costs_money = (int)($residue * $rates['MileageFee']*100) + (int)($rates['StartFare']*100);        //距离费用
        $costs_min_money = (int)($min * $rates['HowFee']*100);        //时长费用
        $money =  $costs_money +  $costs_min_money;
//        $moneys = sprintf("%.2f", $money);

        $result = [] ;

        if($rates['businesstype_id'] == 3){                 //经济型
            $result['partnerCarTypeId'] = 1401 ;
            $result['travelPrice'] = 0 ;
            $result['distancePrice'] = (int)($residue * $rates['MileageFee']*100) ;
            $result['timePrice'] = (int)($min * $rates['HowFee']*100) ;
            $result['initPrice'] = (int)($rates['StartFare']*100) ;
        }else if($rates['businesstype_id'] == 18){           //舒适型
            $result['partnerCarTypeId'] = 1402 ;
            $result['travelPrice'] = 0;
            $result['distancePrice'] = (int)($residue * $rates['MileageFee']*100) ;
            $result['timePrice'] = (int)($min * $rates['HowFee']*100) ;
            $result['initPrice'] = (int)($rates['StartFare']*100) ;
        }else if($rates['businesstype_id'] == 19){          //商务型
            $result['partnerCarTypeId'] = 1403 ;
            $result['travelPrice'] = 0 ;
            $result['distancePrice'] = (int)($residue * $rates['MileageFee']*100) ;
            $result['timePrice'] = (int)($min * $rates['HowFee']*100) ;
            $result['initPrice'] = (int)($rates['StartFare']*100) ;
        }
        $result['moneys'] = $money ;
        return $result ;
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
}