<?php


namespace app\user\controller;

use app\user\controller\Wxnewpays;
use app\user\controller\Wxnewpay;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;

class Order extends Base
{
    //支付订单
    public function PayforTheOrder()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "money" => input('?money') ? input('money') : null,
        ];

        include_once "../extend/WeChat/lib/WxPay.Api.php";
        include_once "../extend/WeChat/lib/WxPay.NativePay.php";
        require_once "../extend/phpqrcode/phpqrcode.php";


    }

    //创建订单
    public function createOrder()
    {
        $params = [
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
            "type_service" => input('?type_service') ? input('type_service') : null,
            "user_id" => input('?user_id') ? input('user_id') : null,
            "origin" => input('?origin') ? input('origin') : null,
            "Destination" => input('?Destination') ? input('Destination') : null,
            "DepLongitude" => input('?DepLongitude') ? input('DepLongitude') : null,
            "DepLatitude" => input('?DepLatitude') ? input('DepLatitude') : null,
            "DestLongitude" => input('?DestLongitude') ? input('DestLongitude') : null,
            "DestLatitude" => input('?DestLatitude') ? input('DestLatitude') : null,
            "classification" => input('?classification') ? input('classification') : null,
            "business_id" => input('?business_id') ? input('business_id') : null,
            "business_type_id" => input('?business_type_id') ? input('business_type_id') : null,
            "DepartTime" => input('?DepartTime') ? input('DepartTime') : null,
            "enterprise_id" => input('?enterprise_id') ? input('enterprise_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["origin", "Destination"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $business = Db::name('business')->where(['id' => input('business_id')])->find();
        $conducteur_id = input('conducteur_id');
        $user_id = input('user_id');
        //实时单和预约单的取消时长
        $cancel_tokinaga = 0;
        $free_time = 0;                //免费时间
        $order_id = 0;

        //用户
        $user = Db::name('user')->field('PassengerPhone,nickname')->where(['id' => input('user_id')])->find();
        if (input('?PassengerPhone') && input('PassengerPhone') != "null") {
            $params['user_phone'] = input('PassengerPhone');
        } else {
            $params['user_phone'] = $user['PassengerPhone'];
        }
        $params['user_name'] = $user['nickname'];
        $params['create_time'] = time();
        $params['order_name'] = $params['classification'] . '订单' . "(" . $business['business_name'] . ")";

        //gps
        $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => $conducteur_id])->value('vehicle_id');  //车辆id
        $Gps_number = Db::name('vehicle')->where(['id' => $vehicle_id])->value('Gps_number');
        $params['gps_number'] = $Gps_number;

        if (!empty($conducteur_id) && $conducteur_id != 0) {
            //司机拦截
            $order = Db::name('order')->where(['conducteur_id' => $conducteur_id])
                ->where('status', 'in', '1,2,3,4')->find();
            if (!empty($order)) {
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "司机存在订单,订单创建失败"
                ];
            }
            //用户拦截
            $orders = Db::name('order')->where(['user_id' => $user_id])
                ->where('classification', 'in', '实时,预约')
                ->where('status', 'in', '1,2,3,4,7')->find();
            if (!empty($orders)) {
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "用户存在订单,订单创建失败"
                ];
            }
            //司机
            $conducteur = Db::name('conducteur')->field('city_id,company_id,DriverName,DriverPhone,key,service,terimnal,trace')->where(['id' => input('conducteur_id')])->find();
            $order_code = $business['letter'] . $conducteur['city_id'] . '0' . date('YmdHis') . rand(0000, 999);
            $params['company_id'] = $conducteur['company_id'];
            $params['conducteur_name'] = $conducteur['DriverName'];
            $params['conducteur_phone'] = $conducteur['DriverPhone'];
            $params['city_id'] = $conducteur['city_id'];
            $params['OrderId'] = $order_code;
            $params['status'] = 2;

            //司机的四个字段
            $params['key'] = $conducteur['key'];
            $params['service'] = $conducteur['service'];
            $params['terimnal'] = $conducteur['terimnal'];
            $params['trace'] = $conducteur['trace'];

            if ($params['classification'] == '实时') {
                $cancel_tokinaga = Db::name('company_elimination')->where(['company_id' => $conducteur['company_id'], 'business_id' => input('business_id'), 'businesstype_id' => input('business_type_id')])->value('cancel_tokinaga'); //
                $free_time = $params['create_time'] + ($cancel_tokinaga * 60);
//                $flag = $this->JudgmentFence(input('DestLongitude'),input('DestLatitude')) ;
//                if($flag == 1){
//                    $params['business_id'] = 100 ;
//                }
            }
            $data['free_time'] = $free_time;
            $order_id = db('order')->insertGetId($params);

        } else {
            //创建预约单的时候，判断是否有用户有存在预约单
            $orders = Db::name('order')->where(['user_id' => input('user_id')])->where(['classification' => '预约'])->where('status', '2,3,4,10,11,12')->find();
            if (!empty($orders)) {
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '您有订单未完成，请完成之后，在进行叫车'
                ];
            }
            $orders_d = Db::name('order')->where(['user_id' => input('user_id')])->where(['classification' => '预约'])->where(['DepartTime' => input('DepartTime')])->where('status', 'not in', '5,6,9')->find();
            $file = fopen('./log.txt', 'a+');
            fwrite($file, "-------------------预约单:--------------------" . json_encode($orders_d) . "\r\n");
            if (!empty($orders_d)) {
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '预约单已存在'
                ];
            }

            $company_id = Db::name('company')->where(['city_id' => input('city_id')])->value('id');
            if ($params['classification'] == '预约') {
                $cancel_tokinaga = Db::name('company_appointment')->where(['company_id' => $company_id, 'business_id' => input('business_id'), 'businesstype_id' => input('business_type_id')])->value('cancel_tokinaga'); //
                $free_time = $params['DepartTime'] - ($cancel_tokinaga * 60);
            }
            $data['free_time'] = $free_time;
            $order_code = $business['letter'] . "00" . '0' . date('YmdHis') . rand(0000, 999);
            $params['OrderId'] = $order_code;
            $params['city_id'] = input('city_id');
            $params['company_id'] = $company_id;
            $params['DepartTime'] = input('DepartTime');
            $params['status'] = 1;
            $params['dispatch_fee'] = input('dispatch_fee');

            $order_id = db('order')->insertGetId($params);
            $laser = time();
            if ($params['classification'] == '预约' ||$params['classification'] == '出租车' ) {
                $this->appointmentByCompany("预约单来了", $company_id, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                if(input('city_id') == 62){     //如果城市为哈尔滨,多推一下，哈市加盟城市
                    $this->appointmentByCompany("预约单来了", 268, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 269, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 274, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 275, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 276, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 277, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 278, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 279, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 280, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 282, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 283, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 284, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 285, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 286, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                    $this->appointmentByCompany("预约单来了", 289, $order_id, 2, input('business_id'), input('business_type_id'), 0,$laser);
                }
            }else if($params['classification'] == '代驾'){
                $this->appointmentByCompany("预约单来了", $company_id, $order_id, 2, input('business_id'), input('business_type_id'), -1,$laser);
            }else if($params['classification'] == '公务车'){
                $this->appointmentByCompany("预约单来了", $company_id, $order_id, 20, 2, 3, -1,$laser);
            }
        }

        if ($order_id > 0) {
            return [
                'code' => APICODE_SUCCESS,
                'order_id' => $order_id,
                'msg' => '创建成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '创建失败'
            ];
        }
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

    function appointmentByCompany($title, $companyId, $message, $type, $business_id, $business_type_id, $conducteur_id,$laser)
    {
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
        $header = array(
            "Authorization:Basic $base64",
            "Content-Type:application/json"
        );
        $param = array("platform" => "all", "audience" => array("tag" => array("Company_$companyId")), "message" => array("msg_content" => $message . "," . $type . "," . $companyId . "," . $business_id . "," . $business_type_id . "," . $conducteur_id. "," . $laser, "title" => $title));
        $params = json_encode($param);
        $res = $this->request_post($url, $params, $header);
        $res_arr = json_decode($res, true);
    }

    function appointment($title, $uid, $message, $type)
    {
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
        $header = array(
            "Authorization:Basic $base64",
            "Content-Type:application/json"
        );
        $param = array("platform" => "all", "audience" => array("tag" => array("D_$uid")), "message" => array("msg_content" => $message . "," . $type, "title" => $title));
        $params = json_encode($param);
        $res = $this->request_post($url, $params, $header);
        $res_arr = json_decode($res, true);
    }

    // 极光推送提交
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

    //选择车型
    public function chooseVehicle()
    {
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "origin" => input('?origin') ? input('origin') : null,
            "Destination" => input('?Destination') ? input('Destination') : null,
            "DepLongitude" => input('?DepLongitude') ? input('DepLongitude') : null,
            "DepLatitude" => input('?DepLatitude') ? input('DepLatitude') : null,
            "DestLongitude" => input('?DestLongitude') ? input('DestLongitude') : null,
            "DestLatitude" => input('?DestLatitude') ? input('DestLatitude') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
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

        $start = $params['DepLongitude'] . ',' . $params['DepLatitude'];
        $end = $params['DestLongitude'] . ',' . $params['DestLatitude'];

        $company_id = Db::name('company')->where(['city_id' => input('city_id')])->value('id');
        $is_scope = Db::name('company')->where(['id' =>$company_id ])->value('is_scope') ;
        $key = Db::name('company')->where(['city_id' => input('city_id')])->value('key');

        $autonavi = $this->autonavi($start, $end, $key);

        $distance = $autonavi[0]['distance'];
        $duration = $autonavi[0]['duration'];
        $is_different = 0 ;                         //是否为议价

        //获取公司id
        $data = [] ;
        if(input('classification') == "实时" || input('classification') == "\U5b9e\U65f6"){
            //是否需要范围判断
            if($is_scope == 1){
                //判断城内/城外/超出城外
                $company_scope = Db::name('company_scope')->where(['company_id'=>$company_id])->select() ;
                //用户起点和终点
                $orgin_location = [
                    'lng'=>input('DepLongitude'),
                    'lat'=>input('DepLatitude'),
                ];
                $destination_location = [
                    'lng'=>input('DestLongitude'),
                    'lat'=>input('DestLatitude'),
                ];
                $scoep = $this->calculatescope($company_scope,$orgin_location,$destination_location);
                if($scoep == 1){                    //城内
                    $data = Db::name('company_rates')->alias('c')
                        ->field('b.img as b_img,b.left_degrees_img,b.title,c.*')
                        ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
                        ->where(['c.business_id' => input('business_id')])
                        ->where(['c.company_id' => $company_id])
                        ->where(['c.titles'=>'城内'])
                        ->select();
                }else if($scoep == 2){              //城外
                    $data = Db::name('company_rates')->alias('c')
                        ->field('b.img as b_img,b.left_degrees_img,b.title,c.*')
                        ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
                        ->where(['c.business_id' => input('business_id')])
                        ->where(['c.company_id' => $company_id])
                        ->where(['c.titles'=>'城外'])
                        ->select();
                }else if($scoep == 3){              //议价
                    $data = Db::name('company_rates')->alias('c')
                        ->field('b.img as b_img,b.left_degrees_img,b.title,c.*')
                        ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
                        ->where(['c.business_id' => input('business_id')])
                        ->where(['c.company_id' => $company_id])
                        ->where(['c.titles'=>'城外'])
                        ->select();
                    $is_different = 1;
                }
            }else{
                $data = Db::name('company_rates')->alias('c')
                    ->field('b.img as b_img,b.left_degrees_img,b.title,c.*')
                    ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
                    ->where(['c.business_id' => input('business_id')])
                    ->where(['c.company_id' => $company_id])
                    ->where(['c.titles'=>'城外'])
                    ->select();
            }
        }else if(input('classification') == "预约" || input('classification') == "代驾" || input('classification') == "\U9884\U7ea6" || input('classification') == "\U4ee3\U9a7e"){
            if($is_scope == 1){
                //判断城内/城外/超出城外
                $company_scope = Db::name('company_scope')->where(['company_id'=>$company_id])->select() ;
                //用户起点和终点
                $orgin_location = [
                    'lng'=>input('DepLongitude'),
                    'lat'=>input('DepLatitude'),
                ];
                $destination_location = [
                    'lng'=>input('DestLongitude'),
                    'lat'=>input('DestLatitude'),
                ];
                $scoep = $this->calculatescope($company_scope,$orgin_location,$destination_location);
                if($scoep == 1){                    //城内
                    $data = Db::name('company_appointment_rates')->alias('c')
                        ->field('b.img as b_img,b.left_degrees_img,b.title,c.*')
                        ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
                        ->where(['c.business_id' => input('business_id')])
                        ->where(['c.company_id' => $company_id])
                        ->where(['titles'=>'城内'])
                        ->select();
                }else if($scoep == 2){              //城外
                    $data = Db::name('company_appointment_rates')->alias('c')
                        ->field('b.img as b_img,b.left_degrees_img,b.title,c.*')
                        ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
                        ->where(['c.business_id' => input('business_id')])
                        ->where(['c.company_id' => $company_id])
                        ->where(['titles'=>'城外'])
                        ->select();
                }else if($scoep == 3){              //议价
                    $data = Db::name('company_appointment_rates')->alias('c')
                        ->field('b.img as b_img,b.left_degrees_img,b.title,c.*')
                        ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
                        ->where(['c.business_id' => input('business_id')])
                        ->where(['c.company_id' => $company_id])
                        ->where(['titles'=>'城外'])
                        ->select();
                    $is_different = 1;
                }
            }else{
                $data = Db::name('company_appointment_rates')->alias('c')
                    ->field('b.img as b_img,b.left_degrees_img,b.title,c.*')
                    ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
                    ->where(['c.business_id' => input('business_id')])
                    ->where(['c.company_id' => $company_id])
                    ->where(['titles'=>'城外'])
                    ->select();
            }
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
//            var_dump($timeSlicingResult);
            $value['StartMile']=$timeSlicingResult["startMile"];
            $value['Tokinaga']=$timeSlicingResult["startMin"];
            $value['MileageFee']=$timeSlicingResult["moneyPerMile"];
            $value['StartFare']=$timeSlicingResult["startMoney"];
            $value['HowFee']=$timeSlicingResult["moneyPerMin"];

//            echo json_encode($timeSlicingResult);
//            exit();
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
            $costs_money = $residue * $value['MileageFee'] + $value['StartFare'];        //距离费用
            $costs_min_money = $min * $value['HowFee'];        //时长费用

            $money = sprintf("%.2f", $costs_money) + sprintf("%.2f", $costs_min_money) + $Longfee;
            $data[$key]['money'] = sprintf("%.2f", $money);
            //低消
            $company_consumption = Db::name('company_consumption')->where(['company_id' => $company_id, 'business_id' => $value['business_id'], 'businesstype_id' => $value['businesstype_id']])->find();
            $balance = Db::name('user')->where(['id' => input('user_id')])->value('balance');  //余额
            $classification = input('?classification') ? input('classification') : null;
            $discounts = 0 ;  //优惠金额
            if (!empty($classification)) {
                if ($company_consumption['type'] == 0) {
                    if ($classification == '实时' || $classification == "\U5b9e\U65f6") {
                        if (floatval($balance) < floatval($money)) {
                            $data[$key]['special_money'] = sprintf("%.2f", floatval($money) - floatval($balance));
                            $flag = 1;
                            $data[$key]['flag'] = $flag;
                        } else {
                            $data[$key]['flag'] = 0;
                        }
                    } else if ($classification == '预约' || $classification == "\U9884\U7ea6") {
                        if (floatval($balance) < floatval($money)) {
                            $appointment_money = $company_consumption['appointment_money'];
                            $data[$key]['special_money'] = sprintf("%.2f", floatval($money) - floatval($balance));
                            $flag = 1;
                            $data[$key]['flag'] = $flag;
                        } else {
                            $data[$key]['flag'] = 0;
                        }
                    }
                } else if ($company_consumption['type'] == 1) {
                    if (floatval($money) >= floatval($company_consumption['order_money'])) {              //预估价大于订单金额
                        if ($classification == '实时' || $classification == "\U5b9e\U65f6") {
                            if (floatval($balance) < floatval($money)) {
                                $special_money = $company_consumption['special_money'];
                                $data[$key]['special_money'] = sprintf("%.2f", floatval($money) - floatval($balance));
                                $flag = 1;
                                $data[$key]['flag'] = $flag;
                            } else {
                                $data[$key]['flag'] = 0;
                            }
                        } else if ($classification == '预约' || $classification == "\U9884\U7ea6" || $classification == '代驾') {
                            if (floatval($balance) < floatval($money)) {
                                $data[$key]['special_money'] = sprintf("%.2f", floatval($money) - floatval($balance));
                                $flag = 1;
                                $data[$key]['flag'] = $flag;
                            } else {
                                $data[$key]['flag'] = 0;
                            }
                        }
                    } else {
                        $data[$key]['flag'] = 0;
                    }
                }
            }
            $discounts = $this->DiscountsMoney(input('user_id'),$value['business_id'],$value['businesstype_id'],input('city_id'),$money) ;
            if(empty($discounts)){
                $discounts = 0 ;
            }
            $data[$key]['discounts'] = $discounts;
        }
        return ['code' => APICODE_SUCCESS, 'data' => $data, 'activity_money' => sprintf("%.2f", $activity_money),'is_different'=>$is_different];
    }

    //同时呼叫
    public function SimultaneousCalling()
    {
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "origin" => input('?origin') ? input('origin') : null,
            "Destination" => input('?Destination') ? input('Destination') : null,
            "DepLongitude" => input('?DepLongitude') ? input('DepLongitude') : null,
            "DepLatitude" => input('?DepLatitude') ? input('DepLatitude') : null,
            "DestLongitude" => input('?DestLongitude') ? input('DestLongitude') : null,
            "DestLatitude" => input('?DestLatitude') ? input('DestLatitude') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
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

        $start = $params['DepLongitude'] . ',' . $params['DepLatitude'];
        $end = $params['DestLongitude'] . ',' . $params['DestLatitude'];

        $company_id = Db::name('company')->where(['city_id' => input('city_id')])->value('id');
        $is_scope = Db::name('company')->where(['id' =>$company_id ])->value('is_scope') ;
        $key = Db::name('company')->where(['city_id' => input('city_id')])->value('key');

        $autonavi = $this->autonavi($start, $end, $key);

        $distance = $autonavi[0]['distance'];
        $duration = $autonavi[0]['duration'];

        //获取业务和车型
        $company_business = Db::name('company_business')->alias('c')
                            ->field('c.alias,c.business_id,b.img as bimg')
                            ->join('mx_business b','b.id = c.business_id','left')
                            ->where('c.business_id','in','1,2,7')
                            ->where(['c.company_id'=>$company_id,'c.is_conceal'=>1])->select() ;

        foreach ($company_business as $kc=>$vc){
             if($vc['business_id'] == 7){        //出租车
                 $company_business[$kc]['money'] = 0 ;
                 $children = [
                     'id'=>5,
                     'title'=>"出租车",
                     'img'=>$vc['bimg'],
                     'money'=>0,
                 ];
                 $company_business[$kc]['children'][] = $children ;
             }else{
                 $data = Db::name('company_rates')->alias('c')
                     ->field('b.img as b_img,b.left_degrees_img,b.title,c.*')
                     ->join('mx_business_type b', 'c.businesstype_id = b.id', 'left')
                     ->where(['c.business_id' => $vc['business_id']])
                     ->where(['c.company_id' => $company_id])
                     ->where(['c.titles'=>'城外'])
                     ->select();
                //业务下的所有车型  strart
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
//            var_dump($timeSlicingResult);
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
                     $costs_money = $residue * $value['MileageFee'] + $value['StartFare'];        //距离费用
                     $costs_min_money = $min * $value['HowFee'];        //时长费用

                     $money = sprintf("%.2f", $costs_money) + sprintf("%.2f", $costs_min_money) + $Longfee;
                     $data[$key]['money'] = sprintf("%.2f", $money);
                     //低消
                     $company_consumption = Db::name('company_consumption')->where(['company_id' => $company_id, 'business_id' => $value['business_id'], 'businesstype_id' => $value['businesstype_id']])->find();
                     $balance = Db::name('user')->where(['id' => input('user_id')])->value('balance');  //余额
                     $classification = input('?classification') ? input('classification') : null;
                     $discounts = 0 ;  //优惠金额
                     if (!empty($classification)) {
                         if ($company_consumption['type'] == 0) {
                             if ($classification == '实时' || $classification == "\U5b9e\U65f6") {
                                 if (floatval($balance) < floatval($money)) {
                                     $data[$key]['special_money'] = sprintf("%.2f", floatval($money) - floatval($balance));
                                     $flag = 1;
                                     $data[$key]['flag'] = $flag;
                                 } else {
                                     $data[$key]['flag'] = 0;
                                 }
                             } else if ($classification == '预约' || $classification == "\U9884\U7ea6") {
                                 if (floatval($balance) < floatval($money)) {
                                     $appointment_money = $company_consumption['appointment_money'];
                                     $data[$key]['special_money'] = sprintf("%.2f", floatval($money) - floatval($balance));
                                     $flag = 1;
                                     $data[$key]['flag'] = $flag;
                                 } else {
                                     $data[$key]['flag'] = 0;
                                 }
                             }
                         } else if ($company_consumption['type'] == 1) {
                             if (floatval($money) >= floatval($company_consumption['order_money'])) {              //预估价大于订单金额
                                 if ($classification == '实时' || $classification == "\U5b9e\U65f6") {
                                     if (floatval($balance) < floatval($money)) {
                                         $special_money = $company_consumption['special_money'];
                                         $data[$key]['special_money'] = sprintf("%.2f", floatval($money) - floatval($balance));
                                         $flag = 1;
                                         $data[$key]['flag'] = $flag;
                                     } else {
                                         $data[$key]['flag'] = 0;
                                     }
                                 } else if ($classification == '预约' || $classification == "\U9884\U7ea6" || $classification == '代驾') {
                                     if (floatval($balance) < floatval($money)) {
                                         $data[$key]['special_money'] = sprintf("%.2f", floatval($money) - floatval($balance));
                                         $flag = 1;
                                         $data[$key]['flag'] = $flag;
                                     } else {
                                         $data[$key]['flag'] = 0;
                                     }
                                 }
                             } else {
                                 $data[$key]['flag'] = 0;
                             }
                         }
                     }

                     $discounts = $this->DiscountsMoney(input('user_id'),$value['business_id'],$value['businesstype_id'],input('city_id'),$money) ;
                     if(empty($discounts)){
                         $discounts = 0 ;
                     }
                     $data[$key]['discounts'] = $discounts;
                     $children = [
                        'id'=>$value['businesstype_id'],
                        'title'=>$value['title'],
                        'img'=>$value['b_img'],
                        'money'=>$money,
                     ];
                     $company_business[$kc]['children'][$key] = $children ;
                 }
             }
        }
        return ['code' => APICODE_SUCCESS, 'data' => $company_business, 'activity_money' => sprintf("%.2f", $discounts)];
    }

    //判断起点和终点是否城内或者城外或者超出城外
    private function calculatescope($company_scopes,$orgin_locations,$destination_locations){
        $flags = 0 ;
        $company_muang = explode('-',$company_scopes[0]['scope']) ;           //城内

        $company_town = explode('-',$company_scopes[1]['scope']) ;           //城外

        $muang = [] ;
        $town = [] ;

        //城内
        foreach ($company_muang as $key=>$value){
            $s = explode(',',$value) ;
            $muang[] = [
                'lng'=>floatval($s[0]),
                'lat'=>floatval($s[1]),
            ];
        }
        //城外
        foreach ($company_town as $k=>$v){
            $w = explode(',',$v) ;
            $town[] = [
                'lng'=>floatval($w[0]),
                'lat'=>floatval($w[1]),
            ];
        }

        //①起点在城内，终点也在城内 1/1
        $cn =  $this->isPointInPolygon($muang,$orgin_locations);
        $cn1 = $this->isPointInPolygon($muang,$destination_locations);
        if($cn&&$cn1){
            return $flags = 1 ;
        }
        //②起点和终点都在城外 1/1
        $cw = $this->isPointInPolygon($town,$orgin_locations) ;
        $cw1 = $this->isPointInPolygon($town,$destination_locations) ;
        //③起点在城内，终点在城外
        if($cn&&$cw1){
            return $flags = 2 ;
        }
        //④终点在城内，起点在城外
        if($cn1&&$cw){
            return $flags = 2 ;
        }
        if($cw&&$cw1){
            return $flags = 3 ;
        }
        //⑤起点终点都不在城内和城外
        if($cn == false && $cn1 == false && $cw == false && $cw1 == false){
            return $flags = 3 ;
        }
        //⑥起点在城内,终点超出城外
        if($cn&&$cn1 == false && $cw1 == false ){
            return $flags = 3 ;
        }
        //⑦起点在城外,终点超出城外
        if($cw&&$cn1 == false && $cw1 == false ){
            return $flags = 3 ;
        }
        //⑧终点在城内,起点超出城外
        if($cn1&&$cn == false && $cw == false){
            return $flags = 3 ;
        }
        //⑨终点在城外,起点超出城外
        if($cw1&&$cn== false &&$cw == false ){
            return $flags = 3 ;
        }
    }

    // 判断点 是否在多边形 内
    private function isPointInPolygon($polygon,$lnglat)
    {
        $count = count($polygon);
        $px = $lnglat['lat'];
        $py = $lnglat['lng'];
        $flag = FALSE;
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i, $i++) {
            $sy = $polygon[$i]['lng'];
            $sx = $polygon[$i]['lat'];
            $ty = $polygon[$j]['lng'];
            $tx = $polygon[$j]['lat'];
            if ($px == $sx && $py == $sy || $px == $tx && $py == $ty)
                return TRUE;
            if ($sy < $py && $ty >= $py || $sy >= $py && $ty < $py) {
                $x = $sx + ($py - $sy) * ($tx- $sx) / ($ty-$sy); if ($x == $px) return TRUE; if ($x > $px)
                    $flag = !$flag;
            }
        }
        return $flag;
    }

    //优惠金额
    private function DiscountsMoney($user_id,$business_id,$businesstype_id,$city_id,$fare){
        $money = 0;

        $user_coupon = Db::name('user_coupon')->field('id,type,pay_money,order_type,times,minus_money')->where(['city_id' => $city_id, 'user_id' => $user_id])->where('is_use', 'eq', 0)->select();

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

    private function unicode_encode($str, $encoding = 'UTF-8', $prefix = '&#', $postfix = ';') {
        $str = iconv($encoding, 'UCS-2', $str);
        $arrstr = str_split($str, 2);
        $unistr = '';
        for($i = 0, $len = count($arrstr); $i < $len; $i++) {
            $dec = hexdec(bin2hex($arrstr[$i]));
            $unistr .= $prefix . $dec . $postfix;
        }
        return $unistr;
    }

    //业务列表
    public function CompanyBusiness()
    {
        if (input('?city_id')) {
            $params = [
                "city_id" => input('city_id')
            ];
            $company_id = db('company')->where($params)->value('id');
            //通过公司找业务

            $data = Db::name('company_business')->where(['company_id' => $company_id])->where(['is_conceal'=>1])->select();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "城市ID不能为空"
            ];
        }
    }

    //取消订单
    public function cancelOrder()
    {
        if (input('?ordert_id')) {
            $params = [
                "id" => input('ordert_id')
            ];
            $orders = Db::name('order')->where(['id' =>(int) input('ordert_id')])->find();
            $company_id = $orders['company_id']; //Db::name('conducteur')->where(['id' => $orders['conducteur_id']])->value('company_id'); //根据司机id查询公司id
            //取消
            $ini['order_id'] = $orders['id'];
            $ini['user_id'] = $orders['user_id'];

            Db::name('order_cancel')->insert($ini);

            $time = time();
            $actually_money = 0;
            $flag = 0;

            if ($orders['status'] >= 4 && $orders['status'] <= 11) {
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "状态不对，不能取消"
                ];
            }


            if ($orders['classification'] == '实时') {
                if($orders['business_id'] == 100){
                    $orders['business_id'] = 2 ;
                }
                $company_elimination = Db::name('company_elimination')->where(['company_id' => $company_id, 'business_id' => $orders['business_id'], 'businesstype_id' => $orders['business_type_id']])->find(); //
                $cancel_tokinaga = $company_elimination['cancel_tokinaga'];  //取消时长

                $free_time = $orders['create_time'] + ($company_elimination['cancel_tokinaga'] * 60);
                $passenger_count = $company_elimination['passenger_count'];  //乘客取消次数
                //按天计算
                $start_time = strtotime(date('Y-m-d', time()) . " 00:00:00");
                $end_time = strtotime(date('Y-m-d', time()) . " 23:59:59");

                $order_count = Db::name('order')->where(['user_id' => $orders['user_id']])->where('create_time', 'gt', $start_time)
                    ->where('create_time', 'lt', $end_time)
                    ->where(['classification'=>'实时'])
                    ->where(['status' => 5])->count();
                if ($order_count >= $passenger_count) {       //有责
                    $flag = 1;
                    $surplus = intval(($time - $free_time) / 1000 / 60);       //剩余时长
                    $money = $company_elimination['money'] * $surplus;
                    if ($company_elimination['aSpotCat_type'] == 1) {
                        if ($money > $company_elimination['highest_charge']) {
                            $actually_money = $company_elimination['highest_charge'];
                        } else {
                            $actually_money = $money;
                        }
                    } else if ($company_elimination['aSpotCat_type'] == 2) {
                        $actually_money = $company_elimination['fixed_price'];
                    }
                    $inio['id'] = input('ordert_id');
                    $inio['status'] = 10;
                    $inio['money'] = $actually_money;
                    $inio['fare'] = $actually_money;
                    $inio['cancel_time'] = time();
                    $order = Db::name('order')->update($inio);

                    return [
                        'code' => APICODE_SUCCESS,
                        'msg' => '待付款',
                        'actually_money' => $actually_money,
                        'flag' => $flag
                    ];
                } else {                                      //无责
                    //再算时间-  有责
                    if ($time > $free_time) {
                        $flag = 1;
                        $surplus = intval(($time - $free_time) / 1000 / 60);       //剩余时长
                        if ($company_elimination['aSpotCat_type'] == 1) {
                            $money = $company_elimination['money'] * $surplus;
                            if ($money > $company_elimination['highest_charge']) {
                                $actually_money = $company_elimination['highest_charge'];
                            } else {
                                $actually_money = $money;
                            }
                        } else if ($company_elimination['aSpotCat_type'] == 2) {
                            $actually_money = $company_elimination['fixed_price'];
                        }

                        $inio['id'] = input('ordert_id');
                        $inio['status'] = 10;
                        $inio['money'] = $actually_money;
                        $inio['fare'] = $actually_money;
                        $inio['cancel_time'] = time();
                        $order = Db::name('order')->update($inio);

                        return [
                            'code' => APICODE_SUCCESS,
                            'msg' => '待付款',
                            'actually_money' => $actually_money,
                            'flag' => $flag
                        ];
                    } else {
                        $inid['id'] = input('ordert_id');
                        $inid['status'] = 5;
                        $inid['cancel_time'] = time();
                        $order = Db::name('order')->update($inid);

                        if ($order) {
                            return [
                                'code' => APICODE_SUCCESS,
                                'msg' => '取消成功',
                                'flag' => $flag
                            ];
                        } else {
                            return [
                                'code' => APICODE_ERROR,
                                'msg' => '取消失败'
                            ];
                        }
                    }

                }
            }

            if ($orders['classification'] == '预约') {
                //判断一下，是否匹配到司机
                if (empty($orders['conducteur_id'])) {
                    $ini['id'] = input('ordert_id');
                    $ini['status'] = 5;
                    $ini['cancel_time'] = time();
                    $order = Db::name('order')->update($ini);
                    //发推送
                    $this->appointment("取消订单",$orders['conducteur_id'],$orders['id'],3);
                    if ($order) {
                        return [
                            'code' => APICODE_SUCCESS,
                            'msg' => '取消成功',
                            'flag' => $flag
                        ];
                    } else {
                        return [
                            'code' => APICODE_ERROR,
                            'msg' => '取消失败'
                        ];
                    }
                }
                $company_appointment = Db::name('company_appointment')->where(['company_id' => $company_id, 'business_id' => $orders['business_id'], 'businesstype_id' => $orders['business_type_id']])->find();
                $cancel_tokinaga = $company_appointment['cancel_tokinaga'];  //取消时长
                //获取抢单时间
                $arrive_time = Db::name('order_grabsingle')->where(['order_id' => $orders['id']])->value('grabsingle_time');

                $residue_time = ($arrive_time / 1000) + $cancel_tokinaga * 60;

                $passenger_count = $company_appointment['passenger_count'];  //乘客取消次数
                //按天计算
                $start_time = strtotime(date('Y-m-d', time()) . " 00:00:00");
                $end_time = strtotime(date('Y-m-d', time()) . " 23:59:59");

                $order_count = Db::name('order')->where(['user_id' => $orders['user_id']])
                    ->where('create_time', 'gt', $start_time)
                    ->where('create_time', 'lt', $end_time)
                    ->where(['classification'=>'预约'])
                    ->where('conducteur_id','gt',0)
                    ->where(['status' => 5])->count();

                if ($order_count >= $passenger_count) {                        //乘客数有责
                    $flag = 1;
                    $surplus = intval(($time - $residue_time) / 1000 / 60);       //剩余时长(分钟)

                    if ($company_appointment['aSpotCat_type'] == 1) {
                        $money = $company_appointment['money'] * $surplus;
                        if ($money > $company_appointment['highest_charge']) {
                            $actually_money = $company_appointment['highest_charge'];
                        } else {
                            $actually_money = $money;
                        }
                    } else if ($company_appointment['aSpotCat_type'] == 2) {
                        $actually_money = $company_appointment['fixed_price'];
                    }
                    $ini['id'] = input('ordert_id');
                    $ini['status'] = 10;
                    $ini['money'] = $actually_money;
                    $ini['fare'] = $actually_money;
                    $ini['cancel_time'] = time();
                    $order = Db::name('order')->update($ini);
                    //发推送
                    $this->appointment("取消订单",$orders['conducteur_id'],$orders['id'],3);
                    return [
                        'code' => APICODE_SUCCESS,
                        'msg' => '待付款',
                        'actually_money' => $actually_money,
                        'flag' => $flag
                    ];
                } else {                                                              //无责
                    //按照时间有责
                    if ($time > $residue_time) {
                        $flag = 1;
                        $surplus = intval(($time - $residue_time) / 1000 / 60);       //剩余时长(分钟)

                        if ($company_appointment['aSpotCat_type'] == 1) {
                            $money = $company_appointment['money'] * $surplus;
                            if ($money > $company_appointment['highest_charge']) {
                                $actually_money = $company_appointment['highest_charge'];
                            } else {
                                $actually_money = $money;
                            }
                        } else if ($company_appointment['aSpotCat_type'] == 2) {
                            $actually_money = $company_appointment['fixed_price'];
                        }
                        $ini['id'] = input('ordert_id');
                        $ini['status'] = 10;
                        $ini['money'] = $actually_money;
                        $ini['fare'] = $actually_money;
                        $ini['cancel_time'] = time();
                        $order = Db::name('order')->update($ini);
                        //发推送
                        $this->appointment("取消订单",$orders['conducteur_id'],$orders['id'],3);
                        return [
                            'code' => APICODE_SUCCESS,
                            'msg' => '待付款',
                            'actually_money' => $actually_money,
                            'flag' => $flag
                        ];
                    } else {
                        $ini['id'] = input('ordert_id');
                        $ini['status'] = 5;
                        $ini['cancel_time'] = time();
                        $order = Db::name('order')->update($ini);
                        if ($order) {
                            //发推送
                            $this->appointment("取消订单",$orders['conducteur_id'],$orders['id'],3);
                            return [
                                'code' => APICODE_SUCCESS,
                                'msg' => '取消成功',
                                'flag' => $flag
                            ];
                        } else {
                            return [
                                'code' => APICODE_ERROR,
                                'msg' => '取消失败'
                            ];
                        }
                    }
                }
            }

            if ($orders['classification'] == '出租车') {
                $inii['id'] = (int)input('ordert_id');
                $inii['status'] = 5;
                $inii['cancel_time'] = time();
                Db::name('order')->update($inii);
                return [
                    'code' => APICODE_SUCCESS,
                    'msg' => '取消成功',
                ];
            }

            if ($orders['classification'] == '代驾') {
                //判断一下，是否匹配到司机
                if (empty($orders['conducteur_id'])) {
                    $ini['id'] = input('ordert_id');
                    $ini['status'] = 5;
                    $ini['cancel_time'] = time();
                    $order = Db::name('order')->update($ini);
                    //发推送
                    $this->appointment("取消订单",$orders['conducteur_id'],$orders['id'],3);
                    if ($order) {
                        return [
                            'code' => APICODE_SUCCESS,
                            'msg' => '取消成功',
                            'flag' => $flag
                        ];
                    } else {
                        return [
                            'code' => APICODE_ERROR,
                            'msg' => '取消失败'
                        ];
                    }
                }
                $company_appointment = Db::name('company_appointment')->where(['company_id' => $company_id, 'business_id' => $orders['business_id'], 'businesstype_id' => $orders['business_type_id']])->find();
                $cancel_tokinaga = $company_appointment['cancel_tokinaga'];  //取消时长
                //获取抢单时间
                $arrive_time = Db::name('order_grabsingle')->where(['order_id' => $orders['id']])->value('grabsingle_time');

                $residue_time = ($arrive_time / 1000) + $cancel_tokinaga * 60;

                $passenger_count = $company_appointment['passenger_count'];  //乘客取消次数
                //按天计算
                $start_time = strtotime(date('Y-m-d', time()) . " 00:00:00");
                $end_time = strtotime(date('Y-m-d', time()) . " 23:59:59");

                $order_count = Db::name('order')->where(['user_id' => $orders['user_id']])
                    ->where('create_time', 'gt', $start_time)
                    ->where('create_time', 'lt', $end_time)
                    ->where(['status' => 5])->count();

                if ($order_count >= $passenger_count) {                        //乘客数有责
                    $flag = 1;
                    $surplus = intval(($time - $residue_time) / 1000 / 60);       //剩余时长(分钟)

                    if ($company_appointment['aSpotCat_type'] == 1) {
                        $money = $company_appointment['money'] * $surplus;
                        if ($money > $company_appointment['highest_charge']) {
                            $actually_money = $company_appointment['highest_charge'];
                        } else {
                            $actually_money = $money;
                        }
                    } else if ($company_appointment['aSpotCat_type'] == 2) {
                        $actually_money = $company_appointment['fixed_price'];
                    }
                    //发推送
                    if($orders['status'] != 10){   //状态不为10
                        $this->appointment("取消订单",$orders['conducteur_id'],$orders['id'],3);
                    }

                    $ini['id'] = input('ordert_id');
                    $ini['status'] = 10;
                    $ini['money'] = $actually_money;
                    $ini['fare'] = $actually_money;
                    $ini['cancel_time'] = time();
                    $order = Db::name('order')->update($ini);

                    return [
                        'code' => APICODE_SUCCESS,
                        'msg' => '待付款',
                        'actually_money' => $actually_money,
                        'flag' => $flag
                    ];
                } else {                                                              //无责
                    //按照时间有责
                    if ($time > $residue_time) {
                        $flag = 1;
                        $surplus = intval(($time - $residue_time) / 1000 / 60);       //剩余时长(分钟)

                        if ($company_appointment['aSpotCat_type'] == 1) {
                            $money = $company_appointment['money'] * $surplus;
                            if ($money > $company_appointment['highest_charge']) {
                                $actually_money = $company_appointment['highest_charge'];
                            } else {
                                $actually_money = $money;
                            }
                        } else if ($company_appointment['aSpotCat_type'] == 2) {
                            $actually_money = $company_appointment['fixed_price'];
                        }
                        //发推送
                        if($orders['status'] != 10){
                            $this->appointment("取消订单",$orders['conducteur_id'],$orders['id'],3);
                        }

                        $ini['id'] = input('ordert_id');
                        $ini['status'] = 10;
                        $ini['money'] = $actually_money;
                        $ini['fare'] = $actually_money;
                        $ini['cancel_time'] = time();
                        $order = Db::name('order')->update($ini);

                        return [
                            'code' => APICODE_SUCCESS,
                            'msg' => '待付款',
                            'actually_money' => $actually_money,
                            'flag' => $flag
                        ];
                    } else {
                        $ini['id'] = input('ordert_id');
                        $ini['status'] = 5;
                        $ini['cancel_time'] = time();
                        $order = Db::name('order')->update($ini);
                        if ($order) {
                            //发推送
                            $this->appointment("取消订单",$orders['conducteur_id'],$orders['id'],3);
                            return [
                                'code' => APICODE_SUCCESS,
                                'msg' => '取消成功',
                                'flag' => $flag
                            ];
                        } else {
                            return [
                                'code' => APICODE_ERROR,
                                'msg' => '取消失败'
                            ];
                        }
                    }
                }
            }

            if ($orders['classification'] == '公务车') {
                $inii['id'] = (int)input('ordert_id');
                $inii['status'] = 5;
                $inii['cancel_time'] = time();
                Db::name('order')->update($inii);
                return [
                    'code' => APICODE_SUCCESS,
                    'msg' => '取消成功',
                ];
            }

        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //立即充值
    public function PrepaidImmediately()
    {
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "phone" => input('?phone') ? input('phone') : null,
            "money" => input('?money') ? input('money') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "phone", "money"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //给用户加钱
        $user = Db::name('user')->where(['id' => input('user_id')])->setInc('balance', input('money'));

        if ($user) {
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '充值成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '充值失败'
            ];
        }
    }

    //等待接驾
    public function WaitingWelcome()
    {
        if (input('?order_id')) {
            $params = [
                "o.id" => input('order_id')
            ];
            $data = db('order')->alias('o')
                ->field('v.VehicleNo,v.PlateColor,v.OwnerName,o.create_time,o.conducteur_id,c.DriverName,c.DriverPhone,c.score,v.Model,c.star,v.Model')
                ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
                ->join('mx_vehicle_binding b', 'b.conducteur_id = c.id', 'left')
                ->join('mx_vehicle v', 'v.id = b.vehicle_id', 'left')
                ->where($params)
                ->find();

            $city_id = Db::name('conducteur')->where(['id' => $data['conducteur_id']])->value('city_id');

            //免费取消时间
            $company_id = Db::name('company')->where(['city_id' => $city_id])->value('id');

            $cancel_tokinaga = Db::name('company_elimination')->where(['company_id' => $company_id])->value('cancel_tokinaga');

            $data['free_time'] = $data['create_time'] + ($cancel_tokinaga * 60);

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //到达乘客地点
    public function PlaceArrival()
    {
        if (input('?order_id')) {
            $params = [
                "o.id" => input('order_id')
            ];
            $data = db('order')->alias('o')
                ->field('v.VehicleNo,v.PlateColor,v.OwnerName,c.star,o.create_time,c.DriverName,v.Model,o.conducteur_id')
                ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
                ->join('mx_vehicle_binding b', 'b.conducteur_id = c.id', 'left')
                ->join('mx_vehicle v', 'v.id = b.vehicle_id', 'left')
                ->where($params)
                ->find();

            //订单历史- 司机到达时间
            $data['arrive_time'] = Db::name('order_history')->where(['order_id' => input('order_id')])->value('arrive_time');

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //行程结束
    public function JourneyEnd()
    {
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id')
            ];
            $data = db('order')->where($params)->value('money');
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "money" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //提供城市范围
    public function storageLocation()
    {
        if (input('?city_id')) {

//            $city_id = Db::name('user')->where(['id'=>input('user_id')])->value('city_id');

            $data = db('city_scope')->where(['city_id' => input('city_id')])->find();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "城市ID不能为空"
            ];
        }
    }

    //权重判断
    public function WeightJudgment()
    {

        $user = explode(',', input('conducteur_id'));

        $length = count($user);

        $data = [];
        //增加司机评分
        if ($length > 1) {
            foreach ($user as $key => $value) {
                $data[$key]['conducteur_id'] = $value;
                $data[$key]['score'] = Db::name('conducteur')->where(['id' => $value])->value('score');
            }
        }

        //判断哪个评分最高
        $conducteur = 0;  //司机id
        $sum = 0;
        if ($length > 1) {
            foreach ($data as $k => $v) {
                if ($v['score'] >= $sum) {
                    $sum = $v['score'];
                    $conducteur = $v['conducteur_id'];
                }
            }
        } else {
            $conducteur = input('conducteur_id');
        }

        return ['code' => APICODE_SUCCESS, 'msg' => '成功', 'conducteur_id' => $conducteur];
    }

    //评价乘客
    public function EvaluationPassengers()
    {


    }

    //创建充值订单
    public function RechargeOrder()
    {
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "money" => input('?money') ? input('money') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "money"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //创建充值订单
        $ordernum = 'CZ' . "00000" . date('Ymdhis');

        $ini['ordernum'] = $ordernum;
        $ini['money'] = input('money');
        $ini['user_id'] = input('user_id');
        $ini['state'] = 0;
        $ini['create_time'] = time();
        //获取用户的城市id
        $city_id = Db::name('user')->where(['id'=>input('user_id')])->value('city_id') ;
        $ini['city_id'] = $city_id ;

        $recharge_order = Db::name('recharge_order')->insert($ini);
        if ($recharge_order) {
            return [
                'code' => APICODE_SUCCESS,
                'ordernum' => $ordernum,
                'msg' => '创建订单成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '创建订单失败'
            ];
        }
    }

    //创建企业充值订单
    public function EnterpriseRechargeOrder()
    {
        $params = [
            "enterprise_id" => input('?enterprise_id') ? input('enterprise_id') : null,
            "money" => input('?money') ? input('money') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["enterprise_id", "money"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //创建充值订单
        $ordernum = 'QY' . "00000" . date('Ymdhis');

        $ini['ordernum'] = $ordernum;
        $ini['money'] = input('money');
        $ini['enterprise_id'] = input('enterprise_id');
        $ini['state'] = 0;
        $ini['create_time'] = time();

        $recharge_order = Db::name('enterprise_order')->insert($ini);
        if ($recharge_order) {
            return [
                'code' => APICODE_SUCCESS,
                'ordernum' => $ordernum,
                'msg' => '创建订单成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '创建订单失败'
            ];
        }
    }

    //更换乘车人
    public function changePassenger()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : 0,
            "user_name" => input('?user_name') ? input('user_name') : '',
            "user_phone" => input('?user_phone') ? input('user_phone') : '',
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id", "user_name", "user_phone"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $ini['id'] = input('order_id');
        $ini['user_name'] = input('user_name');
        $ini['user_phone'] = input('user_phone');

        $order = Db::name('order')->update($ini);

        if ($order > 0) {
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '更换成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '更换失败'
            ];
        }
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

    //根据订单id查询订单信息
    public function getOrderInfo()
    {
        if (input('?order_id')) {
            $params = [
                "o.id" => input('order_id')
            ];
            $data = db('order')->alias('o')
                ->field('v.VehicleNo,v.PlateColor,v.OwnerName,o.create_time,o.conducteur_id,c.grandet,c.DriverName,c.DriverPhone,c.score,v.Model,c.star,v.Model,o.*,c.grandet,c.driving_age,o.vehicle_brand,o.vehicle_number,o.classification,o.is_cancel,o.user_id')
                ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
                ->join('mx_vehicle_binding b', 'b.conducteur_id = c.id', 'left')
                ->join('mx_vehicle v', 'v.id = b.vehicle_id', 'left')
                ->where($params)
                ->find();
            $city_id = Db::name('conducteur')->where(['id' => $data['conducteur_id']])->value('city_id');
            //免费取消时间
            $company_id = Db::name('company')->where(['city_id' => $city_id])->value('id');
            $company_elimination = [] ;
            if($data['classification'] == '预约' || $data['classification'] == '代驾'){
                $company_elimination = Db::name('company_appointment')->where(['company_id' => $company_id, 'business_id' => $data['business_id'], 'businesstype_id' => $data['business_type_id']])->find();
            }else{
                $company_elimination = Db::name('company_elimination')->where(['company_id' => $company_id, 'business_id' => $data['business_id'], 'businesstype_id' => $data['business_type_id']])->find();
            }
            $data['free_time'] = $data['create_time'] + ($company_elimination['cancel_tokinaga'] * 60);
            //取消费用
            $time = time();
            $js_fz = intval(($time - $data['create_time']) / 60);                   //分钟取整
            $money = 0;
            if ($company_elimination['aSpotCat_type'] == 1) {               //按分钟
                $money = $js_fz * $company_elimination['money'];
                if ($money >= $company_elimination['highest_charge']) {
                    $money = $company_elimination['highest_charge'];
                }
            } else if ($company_elimination['aSpotCat_type'] == 2) {         //一口价
                $money = $company_elimination['fixed_price'];
            }

            $order_count = Db::name('order')->where(['conducteur_id' =>$data['conducteur_id'] ])->count();
            $data['order_count'] = $order_count ;
            //获取车型图片
            $left_degrees_img = Db::name('business_type')->where([ 'id' => $data['business_type_id'] ])->value('left_degrees_img') ;
            $business_type_title = Db::name('business_type')->where([ 'id' => $data['business_type_id'] ])->value('title') ;
            $data['left_degrees_img'] = $left_degrees_img ;
            $data['business_type_title'] = $business_type_title ;
            //司机到达时间
            $arrive_time = Db::name('order_history')->where([ 'order_id' => $data['id'] ])->value('arrive_time') ;
            $start_time = strtotime(date('Y-m-d', time()) . " 00:00:00");
            $end_time = strtotime(date('Y-m-d', time()) . " 23:59:59");

            $passenger_cancel_count = Db::name('order')->where(['user_id' => $data['user_id'],"is_cancel"=>0])->where('create_time', 'gt', $start_time)
                ->where('create_time', 'lt', $end_time)
                ->where(['status' => 5])->count();
            if(!empty($arrive_time)){
                $data['arrive_time'] = $arrive_time ;
            }else{
                $data['arrive_time'] = 0 ;
            }
            //取消分钟/取消次数
            $data['passenger_count'] = $company_elimination['passenger_count'] ;
            $data['passenger_cancel_count'] = $passenger_cancel_count ;
            $data['cancel_tokinaga'] = $company_elimination['cancel_tokinaga'] ;

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //根据用户id获取用户优惠券和红包信息
    public function getUserCoupon()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];

            $packet_money = db('user')->where($params)->value('packet_money');   //红包
            //优惠券
            $user_coupon = Db::name('user_coupon')->where('is_use', 'eq', 0)->where(['user_id' => input('id')])->select();

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $user_coupon,
                "packet_money" => $packet_money,
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //余额支付
    public function balancePayment()
    {
        $params = [
            "ordernum" => input('?ordernum') ? input('ordernum') : null,
            "money" => input('?money') ? input('money') : null,
            "actually_money" => input('?actually_money') ? input('actually_money') : null,
            "discount_money" => input('?discount_money') ? input('discount_money') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["ordernum", "money", "actually_money", "discount_money"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $actual_amount_money = input('actual_amount_money');
        //获取订单信息
        $order = Db::name('order')->where(['OrderId' => input('ordernum')])->find();
        //获取用户余额
        $balance = Db::name('user')->where(['id' => $order['user_id']])->value('balance');

        $calculate_money = 0;
        $flag = 0;
        if ($actual_amount_money > $balance) {
            $calculate_money = $balance;
            $flag = 1;
        } else {
            $calculate_money = $actual_amount_money;
        }

        $user = Db::name('user')->where(['id' => $order['user_id']])->setDec('balance', $calculate_money);   //更改用户余额

        $ini['id'] = $order['id'];


        $ini['balance_payment_money'] = input('balance_money');    //余额支付金额
        $ini['actual_amount_money'] = input('actual_amount_money'); //实际支付金额
        $ini['third_party_money'] = 0;                                 //第三方支付金额
        $ini['third_party_type'] = 2;                                  //第三方支付类型 0微信 1支付宝 2其他支付

        //抽成
        $this->companyMoney($order['conducteur_id'], input('money'), $order, input('discount_money'));

        if ($flag == 0) {
            $ini['status'] = 6;           //已支付
            Db::name('order')->update($ini);
        }
        return [
            'code' => APICODE_SUCCESS,
            'msg' => '余额支付成功',
            'flag' => $flag
        ];
    }

    //预支付
    public function advancePayment()
    {
        if (input('?order_id')) {
            $params = [
                "o.id" => input('order_id')
            ];
            $data = [];
            $order = db('order')->alias('o')->field('o.id,o.money,o.user_id,o.city_id,o.business_id,o.business_type_id,o.fare,o.surcharge,o.OrderId,o.company_id,o.classification')->where($params)->find();
            $user = Db::name('user')->alias('u')->field('u.id,u.balance,u.company_id,e.balance as enterprise_balence,u.enterprise_id')
                                            ->join('mx_enterprise e','e.id = u.enterprise_id','left')
                                            ->where(['u.id' => $order['user_id']])->find();

            $is_enterprise = 0;    //是否为企业用户
            $monthly_quota = 0;
            if (!empty($user['enterprise_id'])) {
                $is_enterprise = 1;
                $monthly_quota = Db::name('enterprise')->where(['id' => $user['company_id']])->value('monthly_quota');  //企业限额
                $is_switch = Db::name('user_rule')->where([ 'user_id' => $order['user_id'] ])->value('is_switch') ;

                $user['is_switch'] = $is_switch ;
            }else{
                $user['is_switch'] = 1 ;
            }

            //可用优惠券
            $coupon = [];

            $user_coupon = Db::name('user_coupon')->where(['city_id' => $order['city_id'], 'user_id' => $order['user_id']])->where('is_use', 'eq', 0)->select();

            foreach ($user_coupon as $key => $value) {
                if ($value['type'] == 4 || $value['type'] == 2) {         //最高金额,满减
                    if (floatval($order['fare']) < floatval($value['pay_money'])) {
                        unset($user_coupon[$key]);
                    }
                }
            }

            foreach ($user_coupon as $key => $value) {
//               //匹配业务
                $type = explode(',', $value['order_type']);
                foreach ($type as $k => $v) {
                    if ($order['business_id'] != $v) {
                        unset($user_coupon[$key]);
                    } else {
                        $flag = $this->activityTimeVerify($value['times']);
                        if ($flag == 1) {
                            $coupon[] = [
                                'id' => $value['id'],
                                'coupon_name' => $value['coupon_name'],
                                'times' => $value['times'],
                                'user_id' => $value['user_id'],
                                'order_type' => $value['order_type'],
                                'city_id' => $value['city_id'],
                                'discount' => $value['discount'],
                                'min_money' => $value['min_money'],
                                'man_money' => $value['man_money'],
                                'minus_money' => $value['minus_money'],
                                'pay_money' => $value['pay_money'],
                                'type' => $value['type'],
                                'is_use' => $value['is_use']
                            ];
                        }
                    }
                }
            }
            //可用红包
            $user_redpacket = Db::name('user_redpacket')->where(['user_id' => $order['user_id']])->where('money', 'gt', 0)->select();

            $data['order'] = $order;
            $data['user'] = $user;
            $data['coupon'] = $coupon;
            $data['user_redpacket'] = $user_redpacket;

            //免费取消时间
            $company_elimination = [] ;
            if($order['classification'] == '预约' || $order['classification'] == '代驾'){
                $company_elimination = Db::name('company_appointment')->where(['company_id' => $order['company_id'], 'business_id' => $order['business_id'], 'businesstype_id' => $order['business_type_id']])->find();
            }else{
                $company_elimination = Db::name('company_elimination')->where(['company_id' => $order['company_id'], 'business_id' => $order['business_id'], 'businesstype_id' => $order['business_type_id']])->find();
            }

            $cancel_tokinaga = $company_elimination['cancel_tokinaga'] ;

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $data,
                "is_switch" => $user['is_switch'],
                "cancel_tokinaga" => $cancel_tokinaga,
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
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

    //测试
//    public function test(){
//        $conducteur_id = 186 ;
//        $money = 10 ;
//        $order = [
//           'id'=>2172,
//           'business_id'=>2,
//           'businesstype_id'=>3,
//        ];
//        $discount_money = 0 ;
//        $this->companyMoney($conducteur_id, $money, $order, $discount_money) ;
//    }

    //抽成
    function companyMoney($conducteur_id, $money, $order, $discount_money)
    {

        $company_id = Db::name("conducteur")->where(['id' => $conducteur_id])->value('company_id');  //公司id

        //在获取抽成规则
        $company_ratio = Db::name('company_ratio')->where(['company_id' => $company_id, 'business_id' => $order['business_id'], 'businesstype_id' => $order['business_type_id']])->find();

        //总公司抽成
        $parent_company = ($company_ratio['parent_company_ratio'] / 100) * $money;
        //上级分公司抽成
        $superior_company = ($company_ratio['filiale_company_ratio'] / 100) * $money;  //没有上级值 为 0
        //分公司结算金额
        $compamy_money = $money - (($company_ratio['parent_company_ratio'] / 100) * $money) - (($company_ratio['company_ratio'] / 100) * $money) - $discount_money + $order['surcharge'];
        //分公司利润
        $compamy_profit = ($company_ratio['company_ratio'] / 100) * $money;
        //司机
        $chauffeur_money = $money - (($company_ratio['parent_company_ratio'] / 100) * $money) - (($company_ratio['filiale_company_ratio'] / 100) * $money) - (($company_ratio['company_ratio'] / 100) * $money);

        //司机增加附加费
        $chauffeur_money = $chauffeur_money + $order['surcharge'];

        $inii = [];
        $inii['id'] = $order['id'];
        $inii['parent_company_money'] = $parent_company;
        $inii['superior_company_money'] = $superior_company;
        $inii['filiale_company_money'] = $compamy_profit;
        $inii['chauffeur_income_money'] = $chauffeur_money;
        $inii['filiale_company_settlement'] = $compamy_money;

        Db::name('order')->update($inii);

        //司机加余额
        Db::name('conducteur')->where(['id' => $conducteur_id])->setInc('balance', $chauffeur_money);

        $this->conducteurBoard($conducteur_id, $chauffeur_money, $order['id']);
    }

    //(ios)实际支付(实时单/预约单/顺风车)
    public function actualPayment()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "is_balance" => input('?is_balance') ? input('is_balance') : null,
            "is_coupon" => input('?is_coupon') ? input('is_coupon') : null,
            "is_redpacket" => input('?is_redpacket') ? input('is_redpacket') : null,
            "is_enterprise" => input('?is_enterprise') ? input('is_enterprise') : null,
        ];
        $file = fopen('./log.txt', 'a+');
        $order = Db::name('order')->where(['id' => input('order_id')])->find();    //获取订单信息
        $balance = Db::name('user')->where(['id' => $order['user_id']])->value('balance');//获取用户余额
        $enterprise_money = Db::name('user')->where(['id' => $order['user_id']])->value('enterprise_money'); //获取企业用户余额
        $distribution_id = Db::name('user')->where(['id' => $order['user_id']])->value('distribution_id');   //获取分销司机id
        $fare = $order['fare'];            //车费

        $order_money = $order['money'];        //订单金额
        $is_payment = input('is_payment');      //支付方式

        $ini['third_party_money'] = 0;                                 //第三方支付金额
        $ini['third_party_type'] = $is_payment;                        //第三方支付类型 0微信 1支付宝 2余额支付

        //扣除优惠券
        $calculate_coupon_money = 0;        //参与计算优惠券价格
        $discounts_manner = 0;              //优惠方式
        $discounts_details = 0;            //优惠详情
        if (input('is_coupon') == 1) {       //使用优惠券
            $user_coupon = Db::name('user_coupon')->where(['id' => input('user_coupon_id')])->find();
            //计算优惠券价格
            if ($user_coupon['type'] == 1) {                    //无限制折扣
                $calculate_coupon_money = (10 - $user_coupon['discount']) / 10 * $fare;
            } else if ($user_coupon['type'] == 2) {              //有限制折扣
                if ($fare >= $user_coupon['pay_money']) {
                    $calculate_coupon_money = (10 - $user_coupon['discount']) / 10 * $fare;
                }
            } else if ($user_coupon['type'] == 3) {              //无限制满减
                $calculate_coupon_money = $user_coupon['minus_money'];
            } else if ($user_coupon['type'] == 4) {
                if ($fare >= $user_coupon['pay_money']) { //有限制满减
                    $calculate_coupon_money = $user_coupon['minus_money'];
                }
            } else if ($user_coupon['type'] == 5) {              //N元打车
                $calculate_coupon_money = $fare - $user_coupon['pay_money'];
            }
            $discounts_manner = 1;
            $discounts_details = $user_coupon['id'];
            //将优惠券变成已使用
            $inicoupon['id'] = $user_coupon['id'];
            $inicoupon['is_use'] = 1;
            $inicoupon['create_time'] = time();
            Db::name('user_coupon')->update($inicoupon);
        }
        fwrite($file, '------------优惠价格：' . $calculate_coupon_money . '---------------' . '\r\n');
        //扣除红包
        $redpacket_money = 0;           //参与计算红包金额
        if (input('is_redpacket') == 1) {
            $user_redpacket = Db::name('user_redpacket')->where(['id' => input('user_redpacket_id')])->find();
            $user_money = $user_redpacket['money'];                            //红包金额
            $user_redpacket_money = $user_redpacket['ratio'] * $fare;   //按订单金额可以抵扣的钱

            if ($user_money > $user_redpacket_money) {
                $redpacket_money = $user_redpacket_money;
            } else {
                $redpacket_money = $user_money;
            }
            $discounts_manner = 2;
            $iniiredpacket['id'] = $user_redpacket['id'];
            $iniiredpacket['money'] = $redpacket_money;
            Db::name('user_redpacket')->update($iniiredpacket);
        }
        //抽成
        if ($calculate_coupon_money >= $fare) {  //优惠金额大于车费，只优惠订单金额
            $calculate_coupon_money = $fare;
        }
        $discounts_gross_money = floatval($calculate_coupon_money) + floatval($redpacket_money);                    //总优惠金额 （优惠和红包只有一种）
        fwrite($file, '------------总优惠价格：' . $discounts_gross_money . '---------------' . '\r\n');
        //扣除余额
        $calculate_money = 0;             //参与计算余额
        if (input('is_balance') == 1) {    //使用余额
            $residue_money = $order_money - $discounts_gross_money;                       //剩余金额(去掉优惠总金额)
            if ($residue_money > $balance) {        //余额小于订单金额
                $calculate_money = $balance;
                $ini['balance_payment_money'] = $balance;                               //余额支付金额
                $ini['actual_amount_money'] = $residue_money;                            //实际支付金额
                $ini['discounts_money'] = $discounts_gross_money;                         //优惠金额
                $ini['id'] = input('order_id');
                $ini['status'] = 7;                                                       //待支付
                $ini['discounts_manner'] = $discounts_manner;                            //优惠方式
                $ini['discounts_details'] = $discounts_details;                            //优惠详情
                $ordersum = substr($order['OrderId'], 0, 5) .input('order_id') .date('YmdHis') . rand(0000, 999);
                $ini['OrderId'] = $ordersum;                            //优惠详情
                Db::name('order')->update($ini);
                $user = Db::name('user')->where(['id' => $order['user_id']])->setDec('balance', $balance);   //更改用户余额
                $users = Db::name('user')->where(['id' => $order['user_id']])->find();
                $this->BmikeceChange($users, 3, $balance, 2);
                $moneys = $residue_money - $calculate_money;
                fwrite($file, '------------第三方支付金额：' . $moneys . '---------------' . '\r\n');
//                $this->payorder($moneys,input('code'),3,$order['OrderId']);
                $OrderId = Db::name('order')->where(['id' => input('order_id')])->value('OrderId');
                $attach = "1";
                $passback_params = "1";
                //根据订单来判断选项
                if ($order['classification'] == '实时' || $order['classification'] == '预约' || $order['classification'] == '代驾') {
                    $attach = "1".",".$order['id'];
                    $passback_params = "1".",".$order['id'];
                } else if ($order['classification'] == '顺风车') {
                    $attach = "3".",".$order['id'];
                    $passback_params = "3".",".$order['id'];
                }else if($order['classification'] == '出租车'){
                    $attach = "4".",".$order['id'];
                    $passback_params = "4".",".$order['id'];
                }
                //支付
                if ($is_payment == 0) {                   //微信支付
                    $wxpay = new Wxnewpay();
                    $pay = [
                        'attach' => $attach,
                        'money' => $moneys,
                        'ordernum' => $OrderId,
                    ];
                    $arr = $wxpay->payment($pay);
                    return ['code' => '200', 'message' => '成功', 'data' => $arr];
                } else if ($is_payment == 1) {            //支付宝支付
                    $iospay = new Useriospay();
                    $ios = [
                        'title' => '支付宝支付',
                        'order_code' => $OrderId,
                        'money' => $moneys,
                        'passback_params' => $passback_params,
                    ];
                    $cart = new RSACrypt();
                    $response = $iospay->zfbpayment($ios, $cart);
                    return $cart->response(['code' => 200, 'msg' => '成功', 'data' => $response]);
                }
//                return ["code" => APICODE_ERROR,"msg" => "待支付",'money'=>$moneys];
            } else {                             //余额大于订单金额
                $calculate_money = $balance - $residue_money;
                $ini['balance_payment_money'] = $residue_money;                         //余额支付金额
                $ini['actual_amount_money'] = $residue_money;                            //实际支付金额
                $ini['discounts_money'] = $discounts_gross_money;                         //优惠金额
                $ini['discounts_manner'] = $discounts_manner;                            //优惠方式
                $ini['discounts_details'] = $discounts_details;                          //优惠详情
                $ini['id'] = input('order_id');
                //根据订单来判断选项
                if ($order['classification'] == '实时' || $order['classification'] == '预约' || $order['classification'] == '代驾' || $order['classification'] == '公务车') {
                    $ini['status'] = 6;

                    $this->companyMoney($order['conducteur_id'], $order['fare'], $order, $discounts_gross_money);                            //给司机分钱
                    $this->distributionChauffeur($distribution_id, $order['city_id'], $residue_money);                                       //给分销司机分钱
                    $this->companyBoard($order);                                                                                            //公司流水
                } else if ($order['classification'] == '顺风车') {
                    //判断一下，座位是否被占用
                    if (!empty($order['seat'])) {
                        $orders = Db::name('order')->where(['order_id' => $order['order_id']])->where(['status' => 12])->where('seat', 'in', $order['seat'])->select();     //所有付款的子订单
                        if (!empty($orders)) {
                            return ["code" => APICODE_ERROR, "msg" => "座位已经占用"];
                        }
                    }
                    //判断一下，主单状态
                    $order_id = Db::name('order')->where(['id' => input('order_id')])->value('order_id');
                    $status = Db::name('order')->where(['id' => $order_id])->value('status');
                    if ($status == 12) {
                        $ini['status'] = 12;
                    } else if ($status == 4) {
                        $ini['status'] = 3;
                    }
                    $seating_count = Db::name('order')->where(['id' => $order_id])->value('seating_count');
                    //判断一下，主单还有剩余座位
                    $Ridership = Db::name('order')->where(['order_id' => $order_id])->where(['status' => 12])->value('sum(Ridership)');
                    $residue = $seating_count - $Ridership;
                    if ($residue <= 0) {
                        return ["code" => APICODE_ERROR, "msg" => "座位已占用，请重新下单"];
                    }
                    $this->appointment("顺风车来了", $order['conducteur_id'], $order['order_id'], 4);
                }
                $ini['pay_time'] = time();
                Db::name('order')->update($ini);
                Db::name('user')->where(['id' => $order['user_id']])->setDec('balance', $residue_money);   //更改用户余额
                $user = Db::name('user')->where(['id' => $order['user_id']])->find();
                $this->BmikeceChange($user, 3, $residue_money, 2);
                return ["code" => APICODE_SUCCESS, "msg" => "支付成功"];
            }
        } else {
            $residue_money = $order_money - $discounts_gross_money;                       //剩余金额(去掉优惠总金额)
            fwrite($file, '------------优惠金额 ：' . $discounts_gross_money . '---------------' . '\r\n');
            $ini['discounts_money'] = $discounts_gross_money;                         //优惠金额
            $ini['discounts_manner'] = $discounts_manner;                            //优惠方式
            $ini['discounts_details'] = $discounts_details;                          //优惠详情
            $ini['id'] = input('order_id');
            Db::name('order')->update($ini);
            fwrite($file, '------------付款价格 ：' . $residue_money . '---------------' . '\r\n');
            if ($order['classification'] == '顺风车') {
                //判断一下，座位是否被占用(4座)
                if (!empty($order['seat'])) {
                    $orders = Db::name('order')->where(['order_id' => $order['order_id']])->where(['status' => 12])->where('seat', 'in', $order['seat'])->select();     //所有付款的子订单
                    if (!empty($orders)) {
                        return ["code" => APICODE_ERROR, "msg" => "座位已经占用"];
                    }
                }
                //判断一下，主单状态
                $order_id = Db::name('order')->where(['id' => input('order_id')])->value('order_id');
                $status = Db::name('order')->where(['id' => $order_id])->value('status');
                if ($status == 12) {
                    $ini['status'] = 12;
                } else if ($status == 4) {
                    $ini['status'] = 3;
                }
                $seating_count = Db::name('order')->where(['id' => $order_id])->value('seating_count');
                //判断一下，主单还有剩余座位
                $Ridership = Db::name('order')->where(['order_id' => $order_id])->where(['status' => 12])->value('sum(Ridership)');
                $residue = $seating_count - $Ridership;
                if ($residue <= 0) {
                    return ["code" => APICODE_ERROR, "msg" => "座位已占用，请重新下单"];
                }
            }
            //支付
            $attach = "1";
            $passback_params = "1";
            //根据订单来判断选项
            if ($order['classification'] == '实时' || $order['classification'] == '预约' || $order['classification'] == '代驾') {
                $attach = "1".",".$order['id'];
                $passback_params = "1".",".$order['id'];
            } else if ($order['classification'] == '顺风车') {
                $attach = "3".",".$order['id'];
                $passback_params = "3".",".$order['id'];
            }else if($order['classification'] == '出租车'){
                $attach = "4".",".$order['id'];
                $passback_params = "4".",".$order['id'];
            }
            if ($is_payment == 0) {                   //微信支付
                $wxpay = new Wxnewpay();
                $pay = [
                    'attach' => $attach,
                    'money' => $residue_money,
                    'ordernum' => $order['OrderId'],
                ];
                $arr = $wxpay->payment($pay);
                return ['code' => '200', 'message' => '成功', 'data' => $arr];
            } else if ($is_payment == 1) {            //支付宝支付
                $iospay = new Useriospay();
                $ios = [
                    'title' => '支付宝支付',
                    'order_code' => $order['OrderId'],
                    'money' => $residue_money,
                    'passback_params' => $passback_params,
                ];
                $cart = new RSACrypt();
                $response = $iospay->zfbpayment($ios, $cart);
                return $cart->response(['code' => 200, 'msg' => '成功', 'data' => $response]);
            }
        }
        //使用企业支付
        if (input('is_enterprise') == 1) {

            //先获取企业余额
            $enterprise_id = Db::name('user')->where(['id' =>$order['user_id'] ])->value('enterprise_id') ;
            $enterprise_balance = Db::name('enterprise')->where(['id' => $enterprise_id ])->value('balance') ;

            $result = $this->enterpriseuserconsumption($enterprise_id,$order['user_id'],$order_money);
            if($result['flag'] == 0){
                return ["code" => APICODE_ERROR, "msg" => $result['message']];
            }

            $residue = $enterprise_balance - $order_money ;//$discounts_gross_money - $balance;    //实付金额
            if ($residue >= 0) {
                //将企业余额减少
                Db::name('enterprise')->where(['id' =>$enterprise_id ])->setDec('balance' , $order_money );
                //消费总额增加
                Db::name('enterprise')->where(['id' =>$enterprise_id ])->setInc('gross_amount' , $order_money );

                $ini['balance_payment_money'] = $order_money;                         //余额支付金额
                $ini['actual_amount_money'] = $order_money;                            //实际支付金额
                $ini['discounts_money'] = 0;                         //优惠金额
                $ini['id'] = input('order_id');
                $ini['pay_time'] = time();
                $ini['payer_fut'] = 2 ;
                if ($order['classification'] == '顺风车') {
                    //判断一下，座位是否被占用(4座)
                    if (!empty($order['seat'])) {
                        $orders = Db::name('order')->where(['order_id' => $order['order_id']])->where(['status' => 12])->where('seat', 'in', $order['seat'])->select();     //所有付款的子订单
                        if (!empty($orders)) {
                            return ["code" => APICODE_ERROR, "msg" => "座位已经占用"];
                        }
                    }
                    //判断一下，主单状态
                    $order_id = Db::name('order')->where(['id' => input('order_id')])->value('order_id');
                    $status = Db::name('order')->where(['id' => $order_id])->value('status');
                    if ($status == 12) {
                        $ini['status'] = 12;
                    } else if ($status == 4) {
                        $ini['status'] = 3;
                    }
                    $seating_count = Db::name('order')->where(['id' => $order_id])->value('seating_count');
                    //判断一下，主单还有剩余座位
                    $Ridership = Db::name('order')->where(['order_id' => $order_id])->where(['status' => 12])->value('sum(Ridership)');
                    $residue = $seating_count - $Ridership;
                    if ($residue <= 0) {
                        return ["code" => APICODE_ERROR, "msg" => "座位已占用，请重新下单"];
                    }
                    $this->appointment("顺风车来了", $order['conducteur_id'], $order['id'], 4);
                }else{
                    $ini['status'] = 6;

                    $this->consumptionrecord($enterprise_id,$order['user_id'],$order_money) ;
                    $this->companyMoney($order['conducteur_id'], $order_money, $order, 0);                                 //给司机分钱
                    $this->distributionChauffeur($distribution_id, $order['city_id'], $calculate_money);                                       //给分销司机分钱
                }

                Db::name('order')->update($ini);

                return ["code" => APICODE_SUCCESS, "msg" => "支付成功"];
            } else {
                return ["code" => APICODE_ERROR, "msg" => "企业余额不足"];
            }
        }
    }

    //(安卓)实际支付(实时单/预约单/顺风车)
    public function AndroidActualPayment()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "is_balance" => input('?is_balance') ? input('is_balance') : null,
            "is_coupon" => input('?is_coupon') ? input('is_coupon') : null,
            "is_redpacket" => input('?is_redpacket') ? input('is_redpacket') : null,
            "is_enterprise" => input('?is_enterprise') ? input('is_enterprise') : null,
            "code" => input('?code') ? input('code') : null,
        ];

        $file = fopen('./log.txt', 'a+');
        $order = Db::name('order')->where(['id' => input('order_id')])->find();    //获取订单信息
        if($order['status'] == 6){
            return ["code" => APICODE_ERROR, "msg" => "该笔订单已经支付过了"];
        }
        $balance = Db::name('user')->where(['id' => $order['user_id']])->value('balance');//获取用户余额
        $enterprise_money = Db::name('user')->where(['id' => $order['user_id']])->value('enterprise_money'); //获取企业用户余额
        $distribution_id = Db::name('user')->where(['id' => $order['user_id']])->value('distribution_id');   //获取分销司机id

        $order_money = $order['money'];    //订单金额
        $fare = $order['fare'];            //车费
        $is_payment = input('is_payment');      //支付方式

        $ini['third_party_money'] = 0;                                 //第三方支付金额
        $ini['third_party_type'] = $is_payment;                        //第三方支付类型 0微信 1支付宝 2余额支付
        //扣除优惠券
        $calculate_coupon_money = 0;        //参与计算优惠券价格
        $discounts_manner = 0;              //优惠方式
        $discounts_details = 0;            //优惠详情
        if (input('is_coupon') == 1) {       //使用优惠券
            $user_coupon = Db::name('user_coupon')->where(['id' => input('user_coupon_id')])->find();
            //计算优惠券价格
            if ($user_coupon['type'] == 1) {                    //无限制折扣
                $calculate_coupon_money = (10 - $user_coupon['discount']) / 10 * $fare;
            } else if ($user_coupon['type'] == 2) {              //有限制折扣
                if ($fare >= $user_coupon['pay_money']) {
                    $calculate_coupon_money = (10 - $user_coupon['discount']) / 10 * $fare;
                }
            } else if ($user_coupon['type'] == 3) {              //无限制满减
                $calculate_coupon_money = $user_coupon['minus_money'];
            } else if ($user_coupon['type'] == 4) {
                if ($fare >= $user_coupon['pay_money']) { //有限制满减
                    $calculate_coupon_money = $user_coupon['minus_money'];
                }
            } else if ($user_coupon['type'] == 5) {              //N元打车
                $calculate_coupon_money = $fare - $user_coupon['pay_money'];
            }
            $discounts_manner = 1;
            $discounts_details = $user_coupon['id'];
            //将优惠券变成已使用
            $inicoupon['id'] = $user_coupon['id'];
            $inicoupon['is_use'] = 1;
            $inicoupon['create_time'] = time();
            Db::name('user_coupon')->update($inicoupon);
        }
        //扣除红包
        $redpacket_money = 0;           //参与计算红包金额
        if (input('is_redpacket') == 1) {
            $user_redpacket = Db::name('user_redpacket')->where(['id' => input('user_redpacket_id')])->find();
            $user_money = $user_redpacket['money'];                            //红包金额
            $user_redpacket_money = $user_redpacket['ratio'] * $fare;   //按订单金额可以抵扣的钱

            if ($user_money > $user_redpacket_money) {
                $redpacket_money = $user_redpacket_money;
            } else {
                $redpacket_money = $user_money;
            }
            $discounts_manner = 2;
            $iniiredpacket['id'] = $user_redpacket['id'];
            $iniiredpacket['money'] = $redpacket_money;
            Db::name('user_redpacket')->update($iniiredpacket);
        }


        //抽成
        if ($calculate_coupon_money >= $fare) {  //优惠金额大于订单金额，只优惠订单金额
            $calculate_coupon_money = $fare;
        }
        $discounts_gross_money = floatval($calculate_coupon_money) + floatval($redpacket_money);                    //总优惠金额 （优惠和红包只有一种）
        //扣除余额
        $calculate_money = 0;             //参与计算余额
        if (input('is_balance') == 1) {    //使用余额
            $residue_money = $order_money - $discounts_gross_money;                       //剩余金额(去掉优惠总金额)
            if ($residue_money > $balance) {        //余额大于订单金额
                $calculate_money = $balance;
                $ini['balance_payment_money'] = $balance;                               //余额支付金额
                $ini['actual_amount_money'] = $residue_money;                            //实际支付金额
                $ini['discounts_money'] = $discounts_gross_money;                         //优惠金额
                $ini['id'] = input('order_id');
                $ini['status'] = 7;                                                       //待支付
                $ini['discounts_manner'] = $discounts_manner;                            //优惠方式
                $ini['discounts_details'] = $discounts_details;                            //优惠详情
                $ordersum = substr($order['OrderId'], 0, 5) . date('YmdHis') . rand(0000, 999);
                $ini['OrderId'] = $ordersum;                            //优惠详情
                Db::name('order')->update($ini);
                $user = Db::name('user')->where(['id' => $order['user_id']])->setDec('balance', $balance);   //更改用户余额
                $users = Db::name('user')->where(['id' => $order['user_id']])->find();
                $this->BmikeceChange($users, 3, $balance, 2);
                $moneys = $residue_money - $calculate_money;
//                $this->payorder($moneys,input('code'),3,$order['OrderId']);
                $OrderId = Db::name('order')->where(['id' => input('order_id')])->value('OrderId');
                //支付
                $attach = "1";
                $passback_params = "1";
                //根据订单来判断选项
                if ($order['classification'] == '实时' || $order['classification'] == '预约' || $order['classification'] == '代驾') {
                    $attach = "1".",".$order['id'];
                    $passback_params = "1".",".$order['id'];
                } else if ($order['classification'] == '顺风车') {
                    $attach = "3".",".$order['id'];
                    $passback_params = "3".",".$order['id'];
                }else if($order['classification'] == '出租车'){
                    $attach = "4".",".$order['id'];
                    $passback_params = "4".",".$order['id'];
                }
                fwrite($file, '------------passback_paramsssss：' . $passback_params . '---------------' . '\r\n');
                if ($is_payment == 0) {                   //微信支付
                    $wxpay = new Wxnewpay();
                    $wxpays = new Wxnewpays() ;
                    $pay = [
                        'attach' => $attach,
                        'money' => $moneys,
                        'ordernum' => $OrderId,
                        'classification'=>$order['classification']
                    ];
                    if($order['classification'] == "实时" || $order['classification'] == '预约' || $order['classification'] == '顺风车'){
                        $arr = $wxpay->payment($pay);
                        return ['code' => '200', 'message' => '成功', 'data' => $arr];
                    }else{  //出租车
                        $arr = $wxpay->payment($pay);
                        return ['code' => '200', 'message' => '成功', 'data' => $arr];
                    }
                } else if ($is_payment == 1) {            //支付宝支付
                    $androidpay = new Userpay();
                    $android = [
                        'title' => '支付宝支付',
                        'order_code' => $OrderId,
                        'money' => $moneys,
                        'passback_params' => $passback_params,
                    ];
                    $cart = new RSACrypt();
                    $response = $androidpay->zfbpayment($android, $cart);
                    return $cart->response(['code' => 200, 'msg' => '成功', 'data' => $response]);
                }
//                return ["code" => APICODE_ERROR,"msg" => "待支付",'money'=>$moneys];
            } else {                             //余额大于订单金额
                $calculate_money = $balance - $residue_money;
                $ini['balance_payment_money'] = $residue_money;                         //余额支付金额
                $ini['actual_amount_money'] = $residue_money;                            //实际支付金额
                $ini['discounts_money'] = $discounts_gross_money;                         //优惠金额
                $ini['discounts_manner'] = $discounts_manner;                            //优惠方式
                $ini['discounts_details'] = $discounts_details;                          //优惠详情
                $ini['id'] = input('order_id');
                //根据订单来判断选项
                if ($order['classification'] == '实时' || $order['classification'] == '预约' || $order['classification'] == '代驾' || $order['classification'] == '公务车') {
                    $ini['status'] = 6;

                    $this->companyMoney($order['conducteur_id'], $order['fare'], $order, $discounts_gross_money);                            //给司机分钱
                    $this->distributionChauffeur($distribution_id, $order['city_id'], $residue_money);                                       //给分销司机分钱
                    $this->companyBoard($order);                                                                                            //公司流水
                } else if ($order['classification'] == '顺风车') {
                    //判断一下，座位是否被占用(4座)
                    if (!empty($order['seat'])) {
                        $orders = Db::name('order')->where(['order_id' => $order['order_id']])->where(['status' => 12])->where('seat', 'in', $order['seat'])->select();     //所有付款的子订单
                        if (!empty($orders)) {
                            return ["code" => APICODE_ERROR, "msg" => "座位已经占用"];
                        }
                    }
                    //判断一下，主单状态
                    $order_id = Db::name('order')->where(['id' => input('order_id')])->value('order_id');
                    $status = Db::name('order')->where(['id' => $order_id])->value('status');
                    if ($status == 12) {
                        $ini['status'] = 12;
                    } else if ($status == 4) {
                        $ini['status'] = 3;
                    }
                    $seating_count = Db::name('order')->where(['id' => $order_id])->value('seating_count');
                    //判断一下，主单还有剩余座位
                    $Ridership = Db::name('order')->where(['order_id' => $order_id])->where(['status' => 12])->value('sum(Ridership)');
                    $residue = $seating_count - $Ridership;
                    if ($residue <= 0) {
                        return ["code" => APICODE_ERROR, "msg" => "座位已占用，请重新下单"];
                    }
                    $this->appointment("顺风车来了", $order['conducteur_id'], $order['order_id'], 4);
                }
                $ini['pay_time'] = time();
                Db::name('order')->update($ini);
                Db::name('user')->where(['id' => $order['user_id']])->setDec('balance', $residue_money);   //更改用户余额
                $user = Db::name('user')->where(['id' => $order['user_id']])->find();
                $this->BmikeceChange($user, 3, $residue_money, 2);
                return ["code" => APICODE_SUCCESS, "msg" => "支付成功"];
            }
        } else {
            $residue_money = $order_money - $discounts_gross_money;                       //剩余金额(去掉优惠总金额)
            $ini['discounts_money'] = $discounts_gross_money;                         //优惠金额
            $ini['discounts_manner'] = $discounts_manner;                            //优惠方式
            $ini['discounts_details'] = $discounts_details;                          //优惠详情
            $ini['id'] = input('order_id');
            Db::name('order')->update($ini);
            if ($order['classification'] == '顺风车') {
                //判断一下，座位是否被占用(4座)
                if (!empty($order['seat'])) {
                    $orders = Db::name('order')->where(['order_id' => $order['order_id']])->where(['status' => 12])->where('seat', 'in', $order['seat'])->select();     //所有付款的子订单
                    if (!empty($orders)) {
                        return ["code" => APICODE_ERROR, "msg" => "座位已经占用"];
                    }
                }
                //判断一下，主单状态
                $order_id = Db::name('order')->where(['id' => input('order_id')])->value('order_id');
                $status = Db::name('order')->where(['id' => $order_id])->value('status');
                if ($status == 12) {
                    $ini['status'] = 12;
                } else if ($status == 4) {
                    $ini['status'] = 3;
                }
                $seating_count = Db::name('order')->where(['id' => $order_id])->value('seating_count');
                //判断一下，主单还有剩余座位
                $Ridership = Db::name('order')->where(['order_id' => $order_id])->where(['status' => 12])->value('sum(Ridership)');
                $residue = $seating_count - $Ridership;
                if ($residue <= 0) {
                    return ["code" => APICODE_ERROR, "msg" => "座位已占用，请重新下单"];
                }
            }
            //支付
            $attach = "1";
            $passback_params = "1";
            //根据订单来判断选项
            if ($order['classification'] == '实时' || $order['classification'] == '预约') {
                $attach = "1".",".$order['id'];
                $passback_params = "1".",".$order['id'];
            } else if ($order['classification'] == '顺风车') {
                $attach = "3".",".$order['id'];
                $passback_params = "3".",".$order['id'];
            }else if ($order['classification'] == '出租车') {
                $attach = "4".",".$order['id'];
                $passback_params = "4".",".$order['id'];
            }
            fwrite($file, '------------passback_params ：' . $passback_params . '---------------' . '\r\n');
            if ($is_payment == 0) {                   //微信支付
                $wxpay = new Wxnewpay();
                $wxpays = new Wxnewpays() ;

                $ordersum = substr($order['OrderId'], 0, 5) .$order['id'] .date('YmdHis') . rand(0000, 999);
                $os['id'] = $order['id'];
                $os['OrderId'] = $ordersum;
                Db::name('order')->update($os);
                $pay = [
                    'attach' => $attach,
                    'money' => $residue_money,
                    'ordernum' => $ordersum,
                    'classification'=>$order['classification']
                ];
                if($order['classification'] == "实时" || $order['classification'] == '预约' || $order['classification'] == '顺风车'){
                    $arr = $wxpay->payment($pay);
                    return ['code' => '200', 'message' => '成功', 'data' => $arr];
                }else{  //出租车
                    $arr = $wxpay->payment($pay);
                    return ['code' => '200', 'message' => '成功', 'data' => $arr];
                }
            } else if ($is_payment == 1) {            //支付宝支付
                $androidpay = new Userpay();
                $android = [
                    'title' => '支付宝支付',
                    'order_code' => $order['OrderId'],
                    'money' => $residue_money,
                    'passback_params' => $passback_params,
                ];
                $cart = new RSACrypt();
                $response = $androidpay->zfbpayment($android, $cart);
                return $cart->response(['code' => 200, 'msg' => '成功', 'data' => $response]);
            }
        }
        //使用企业支付
        if (input('is_enterprise') == 1) {
            //先获取企业余额
            $enterprise_id = Db::name('user')->where(['id' =>$order['user_id'] ])->value('enterprise_id') ;
            $enterprise_balance = Db::name('enterprise')->where(['id' => $enterprise_id ])->value('balance') ;
            $result = $this->enterpriseuserconsumption($enterprise_id,$order['user_id'],$order_money);
            if($result['flag'] == 0){
                return ["code" => APICODE_ERROR, "msg" => $result['message']];
            }
            $residue = $enterprise_balance - $order_money ;//$discounts_gross_money - $balance;    //实付金额
            if ($residue >= 0) {
                //将企业余额减少
                Db::name('enterprise')->where(['id' =>$enterprise_id ])->setDec('balance' , $order_money );
                //消费总额增加
                Db::name('enterprise')->where(['id' =>$enterprise_id ])->setInc('gross_amount' , $order_money );

                $ini['balance_payment_money'] = $order_money;                         //余额支付金额
                $ini['actual_amount_money'] = $order_money;                            //实际支付金额
                $ini['discounts_money'] = 0;                         //优惠金额
                $ini['id'] = input('order_id');
                $ini['pay_time'] = time();
                $ini['payer_fut'] = 2;
                //判断是否城际订单
                if ($order['classification'] == '顺风车') {
                    //判断一下，座位是否被占用(4座)
                    if (!empty($order['seat'])) {
                        $orders = Db::name('order')->where(['order_id' => $order['order_id']])->where(['status' => 12])->where('seat', 'in', $order['seat'])->select();     //所有付款的子订单
                        if (!empty($orders)) {
                            return ["code" => APICODE_ERROR, "msg" => "座位已经占用"];
                        }
                    }
                    //判断一下，主单状态
                    $order_id = Db::name('order')->where(['id' => input('order_id')])->value('order_id');
                    $status = Db::name('order')->where(['id' => $order_id])->value('status');
                    if ($status == 12) {
                        $ini['status'] = 12;
                    } else if ($status == 4) {
                        $ini['status'] = 3;
                    }
                    $seating_count = Db::name('order')->where(['id' => $order_id])->value('seating_count');
                    //判断一下，主单还有剩余座位
                    $Ridership = Db::name('order')->where(['order_id' => $order_id])->where(['status' => 12])->value('sum(Ridership)');
                    $residue = $seating_count - $Ridership;
                    if ($residue <= 0) {
                        return ["code" => APICODE_ERROR, "msg" => "座位已占用，请重新下单"];
                    }
                    $this->appointment("顺风车来了", $order['conducteur_id'], $order['id'], 4);
                }else{
                    $ini['status'] = 6;

                    $this->consumptionrecord($enterprise_id,$order['user_id'],$order_money) ;
                    $this->companyMoney($order['conducteur_id'], $order_money, $order, 0);                                 //给司机分钱
                    $this->distributionChauffeur($distribution_id, $order['city_id'], $calculate_money);                                       //给分销司机分钱
                }

                Db::name('order')->update($ini);

                return ["code" => APICODE_SUCCESS, "msg" => "支付成功"];
            } else {
                return ["code" => APICODE_ERROR, "msg" => "企业余额不足"];
            }
        }
    }

    //取消微信支付订单
    public function cancelWeChat()
    {
        $order = Db::name('order')->where(['id' => input('order_id')])->find();    //获取订单信息
        if (input('is_coupon') == 1) {       //使用优惠券
            $user_coupon = Db::name('user_coupon')->where(['id' => input('user_coupon_id')])->find();
            //将优惠券变成未使用
            $inicoupon['id'] = $user_coupon['id'];
            $inicoupon['is_use'] = 0;
            Db::name('user_coupon')->update($inicoupon);
        }
        if (input('is_balance') == 1) {
            $user = Db::name('user')->where(['id' => $order['user_id']])->setInc('balance', $order["balance_payment_money"]);   //更改用户余额
            //删除一条记录
            $user_balance_id = Db::name('user_balance')->where(['user_id'=>$order['user_id']])->where(['money'=>$order["balance_payment_money"]])
                ->where(['type'=>3])->where(['symbol'=>2])->value('id') ;

            Db::name('user_balance')->where(['id'=>$user_balance_id])->delete();
        }
        return ['code' => APICODE_SUCCESS, 'msg' => "取消成功"];
    }

    //取消支付宝订单
    public function cancelAlipay()
    {
        $order = Db::name('order')->where(['id' => input('order_id')])->find();    //获取订单信息
        if (input('is_coupon') == 1) {       //使用优惠券
            $user_coupon = Db::name('user_coupon')->where(['id' => input('user_coupon_id')])->find();
            //将优惠券变成未使用
            $inicoupon['id'] = $user_coupon['id'];
            $inicoupon['is_use'] = 0;
            Db::name('user_coupon')->update($inicoupon);
        }
        if (input('is_balance') == 1) {
            $user = Db::name('user')->where(['id' => $order['user_id']])->setInc('balance', $order["balance_payment_money"]);   //更改用户余额
            //删除一条记录
            $user_balance_id = Db::name('user_balance')->where(['user_id'=>$order['user_id']])->where(['money'=>$order["balance_payment_money"]])
                ->where(['type'=>3])->where(['symbol'=>2])->value('id') ;

            Db::name('user_balance')->where(['id'=>$user_balance_id])->delete();
        }
        return ['code' => APICODE_SUCCESS, 'msg' => "取消成功"];
    }

    //分销给司机分钱
    function distributionChauffeur($id, $city_id, $actually_money)
    {
        $distribution_id = $id;             //分销司机id
        $conducteur = Db::name('conducteur')->where(['id' => $distribution_id])->find();
        //司机状态为正常或者临时禁封，分销状态为正常，才可以正常分钱
        if (($conducteur['status'] == 1 || $conducteur['status'] == 3) && ($conducteur['distribution_state'] == 1)) {
            //判断订单的城市和司机城市在不在同一个地方
            if ($city_id == $conducteur['city_id']) {
                //获取分销规则
                $company_distribution = Db::name('company_distribution')->where(['company_id' => $conducteur['company_id']])->find();
                if ($company_distribution['type'] == 0) {                   //比例
                    $money = $actually_money * ($company_distribution['ratio'] / 100);
                    Db::name('conducteur')->where(['id' => $distribution_id])->setInc('distribution_money', $money);
                } else if ($company_distribution['type'] == 1) {             //区间
                    //获取区间信息
                    $company_distribution_detail = Db::name('company_distribution_detail')->where(['company_distribution_id' => $company_distribution['id']])->select();
                    //获取当前我有多少人
                    $people_count = Db::name('conducteur_distribution_balance')->where(['conducteur_id' => $distribution_id])->count();  //人数
                    foreach ($company_distribution_detail as $key => $value) {
                        if (($people_count >= $value['range_one']) || ($people_count <= $value['range_two'])) {
                            $money = $actually_money * ($value['ratio'] / 100);
                            Db::name('conducteur')->where(['id' => $distribution_id])->setInc('distribution_money', $money);
                        }
                    }
                }
            }
        }
    }

    //司机流水
    function conducteurBoard($conducteur_id, $money, $order_id)
    {
        $inic['conducteur_id'] = $conducteur_id;
        $inic['title'] = '接单';
        $inic['describe'] = "";
        $inic['order_id'] = $order_id;
        $inic['money'] = $money;
        $inic['symbol'] = 1;
        $inic['create_time'] = time();

        Db::name('conducteur_board')->insert($inic);
    }

    //用户余额变动
    function BmikeceChange($user, $type, $money, $symbol)
    {
        $ini['user_id'] = $user['id'];
        $ini['type'] = $type;
        $ini['money'] = $money;
        $ini['user_name'] = $user['PassengerName'];
        $ini['phone'] = $user['PassengerPhone'];
        $ini['create_time'] = time();
        $ini['symbol'] = $symbol;

        Db::name('user_balance')->insert($ini);
    }

    //公司流水
    public function companyBoard($order)
    {
        $company = [];
        //总公司
        $company[] = [
            'order_id' => $order['id'],
            'company_id' => 0,
            'money' => $order['parent_company_money'],
        ];
        //分公司
        $company[] = [
            'order_id' => $order['id'],
            'company_id' => $order['company_id'],
            'money' => $order['filiale_company_money'],
        ];
        //上级公司
        $superior_company = Db::name('company')->where(['id' => $order['company_id']])->value('superior_company');
        if ($superior_company > 0) {                              //上级公司id
            $company[] = [
                'order_id' => $order['id'],
                'company_id' => $superior_company,
                'money' => $order['superior_company_money'],
            ];
        }
        Db::name('company_board')->insertAll($company);
    }
    //消费记录
    private function consumptionrecord($enterprise_id,$user_id,$money){
        $consumerdetails['enterprise_id'] = $enterprise_id ;
        $consumerdetails['user_id'] = $user_id ;
        $nickname = Db::name('user')->where(['id' => $user_id])->value('nickname') ;
        $consumerdetails['times'] = time() ;
        $consumerdetails['title'] = $nickname."支付订单" ;
        $consumerdetails['money'] = $money ;

        Db::name('enterprise_consumerdetails')->insert($consumerdetails);
    }
    //企业用户消费规则
    private function enterpriseuserconsumption($enterprise_id,$user_id,$money){
        $flag = 0 ;
        $message = "" ;
        //获取用户的消费规则
        $user_rule = Db::name('user_rule')->where(['enterprise_id' =>$enterprise_id,'user_id'=>$user_id ])->find();
        $rule_money = $user_rule['money'] ;     //限制额度
        //根据类型
        if($user_rule['type'] == 1){                    //无限制模式
            $flag = 1 ;
        }else if($user_rule['type'] == 2){              //周期模式
            if($user_rule['type_item'] == 1){           //自然月
               //获取当月的时间
                $start_month= strtotime( date('Y-m-d H:i:s',mktime(0,0,0,date('m'),1,date('Y'))) );
                $end_month= strtotime( date('Y-m-d H:i:s',mktime(23,59,59,date('m'),date('t'),date('Y'))) );
               //查询已经消费的当月额度
               $moneys = Db::name('enterprise_consumerdetails')->where(['enterprise_id' =>$enterprise_id ,'user_id'=>$user_id ])
                                                                          ->where('times','gt',$start_month)
                                                                          ->where('times','lt',$end_month)
                                                                          ->value('sum(money)') ;

               //消费限制的金额，是否超过了限制
                $sum_money = $moneys + $money ;
                if($rule_money >= $sum_money){
                    $flag = 1;
                }else{
                    $message = "消费金额超过了限制额度" ;
                }
            }else if($user_rule['type_item'] == 2){     //自然天
                //获取当天时间
                $start_time = strtotime( date("Y-m-d",time())." 00:00:00" ) ;
                $end_time = strtotime( date("Y-m-d",time())." 24:00:00") ;

                //查询已经消费的当月额度
                $moneys = Db::name('enterprise_consumerdetails')->where(['enterprise_id' =>$enterprise_id ,'user_id'=>$user_id ])
                    ->where('times','gt',$start_time)
                    ->where('times','lt',$end_time)
                    ->value('sum(money)') ;

                //消费限制的金额，是否超过了限制
                $sum_money = $moneys + $money ;
                if($rule_money >= $sum_money){
                    $flag = 1;
                }else{
                    $message = "消费金额超过了限制额度" ;
                }
            }else if($user_rule['type_item'] == 3){     //自然周
                //获取自然周时间
                $week_start= strtotime( date('Y-m-d H:i:s',mktime(0,0,0,date('m'),date('d')-date('w')+1,date('Y'))) );
                $week_end= strtotime( date('Y-m-d H:i:s',mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'))) );
                //查询已经消费的当月额度
                $moneys = Db::name('enterprise_consumerdetails')->where(['enterprise_id' =>$enterprise_id ,'user_id'=>$user_id ])
                    ->where('times','gt',$week_start)
                    ->where('times','lt',$week_end)
                    ->value('sum(money)') ;

                //消费限制的金额，是否超过了限制
                $sum_money = $moneys + $money ;
                if($rule_money >= $sum_money){
                    $flag = 1;
                }else{
                    $message = "消费金额超过了限制额度" ;
                }
            }else if($user_rule['type_item'] == 4){     //自然年
                //获取本年的时间
                $start_time= strtotime( date('Y-m-d H:i:s',strtotime(date("Y",time())."-1"."-1")) );
                $end_time=strtotime( date('Y-m-d H:i:s',strtotime(date("Y",time())."-12"."-31 23:59:59")) );
                //查询已经消费的当月额度
                $moneys = Db::name('enterprise_consumerdetails')->where(['enterprise_id' =>$enterprise_id ,'user_id'=>$user_id ])
                    ->where('times','gt',$start_time)
                    ->where('times','lt',$end_time)
                    ->value('sum(money)') ;

                //消费限制的金额，是否超过了限制
                $sum_money = $moneys + $money ;
                if($rule_money >= $sum_money){
                    $flag = 1;
                }else{
                    $message = "消费金额超过了限制额度" ;
                }
            }else if($user_rule['type_item'] == 5){     //自然日
                $start_time = strtotime( $user_rule['start_time'] ) ;
                $end_time = strtotime( $user_rule['end_time'] ) ;
                $time = time() ;
                if( ($time > $start_time ) && ( $end_time < $time) ){
                    //已经消费的金额
                    $moneys = Db::name('enterprise_consumerdetails')->where(['enterprise_id' =>$enterprise_id ,'user_id'=>$user_id ])->value('sum(money)') ;
                    //消费的金额，是否超过了限制金额
                    $sum_money = $moneys + $money ;

                    if($rule_money >= $sum_money){
                        $flag = 1;
                    }else{
                        $message = "消费金额超过了限制额度" ;
                    }
                }else{
                    $message = "限制额度不在消费周期" ;
                }
            }
        }else if($user_rule['type'] == 3){              //固定模式
            //获取某日区间，在不在这里面
            $time = time() ;
            $start_time = strtotime( $user_rule['start_time'] ) ;
            $end_time = strtotime( $user_rule['end_time'] ) ;

            if( ($time > $start_time ) && ( $end_time < $time) ){
                //已经消费的金额
                $moneys = Db::name('enterprise_consumerdetails')->where(['enterprise_id' =>$enterprise_id ,'user_id'=>$user_id ])->value('sum(money)') ;
                //消费的金额，是否超过了限制金额
                $sum_money = $moneys + $money ;

                if($rule_money >= $sum_money){
                    $flag = 1;
                }else{
                    $message = "消费金额超过了限制额度" ;
                }
            }else{
                $message = "限制额度不在消费周期" ;
            }
        }
        $result['flag'] = $flag ;
        $result['message'] = $message ;
        return $result ;
    }

    //语音下单
    public function CreateVoiceOrder(){
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "classification" => input('?classification') ? input('classification') : null,
            "business_id" => input('?business_id') ? input('business_id') : null,
            "business_type_id" => input('?business_type_id') ? input('business_type_id') : null,
            "voice" => input('?voice') ? input('voice') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "classification"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $user = Db::name('user')->field('id,PassengerPhone,nickname')->where(['id'=>input('user_id')])->find();

        $params['create_time'] = time() ;
        $params['user_phone'] = $user['PassengerPhone'] ;
        $params['user_name'] = $user['nickname'] ;
        $params['status'] = 1 ;

        $res = Db::name('order')->insert( $params ) ;

        if($res){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'下单成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'下单失败'
            ];
        }
    }
}
