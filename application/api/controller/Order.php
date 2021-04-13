<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 19-2-26
 * Time: 上午10:53
 */

namespace app\api\controller;

use app\backstage\controller\Gps;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use app\wxapi\controller\Index;
use app\user\controller\Marketing;
include_once ROOT_PATH.'extend/Wexin_share/wy.php';

class Order extends Base
{
    //到达起点
    public function StartingPoint()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "arrive_time" => input('?arrive_time') ? input('arrive_time') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id", "arrive_time"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //到达起点之前，判断一下，订单状态是不是2,否则就不让点
        $order = Db::name('order')->where(['id' => input('order_id')])->find();
        //实时单
        if ($order['status'] != 2) {
            return [
                'code' => APICODE_ERROR,
                'msg' => '修改状态失败'
            ];
        }
//        //预约单
//        if ($order['status'] != 12 && $order['classification'] == '预约') {
//            return [
//                'code' => APICODE_ERROR,
//                'msg' => '修改状态失败'
//            ];
//        }

        $ini['id'] = input('order_id');
        $ini['status'] = 3;
        if(!empty(input('arrive_location'))){
            $ini['arrive_location'] = input('arrive_location') ;
        }
        //计算接乘客时间
        $create_time = Db::name('order')->where(['id' =>input('order_id') ])->value('create_time') ;

        if($order['classification'] == "实时"){
            $passengers_time = sprintf("%.2f", ((input('arrive_time')/1000 - $create_time))) ;
            $ini['passengers_time'] = $passengers_time ;
        }
        Db::name('order')->update($ini);
        $order_history = db('order_history')->insert($params);

//        if ($order_history) {
            $opneid = Db::name('user')->where(['id' => $order['user_id']])->value('openid');
            if (!empty($opneid)) {
                $index = new Index();
                $order_id=input('order_id');
                //取用户的openid
                $index->sendOrderStatus($opneid, '司机已到达您的位置', $order['order_name'], $order['origin'], $order['Destination'], date('Y年m月d日 H:i', time()),"pages/fastCar/order?orderId=$order_id");
            }
            //判断是不是美团订单
            $orders = Db::name('order')->where(['id' => input('order_id')])->find();
            if (!empty($orders['mtorderid'])) {
                $param = [];
                $tatol = [];
                $rates = [];
                $this->partner_post(input('order_id'), $param, 'ARRIVE', $orders, "0", $tatol, $rates,0);
            }
            //判断一下是否是出租车
            if($orders['classification'] == '出租车'){
                $this->newsService($orders,'司机已经到达，请准备上车，如发现车辆不符，请勿乘坐。投诉微信号：1932991034') ;
            }

            return [
                'code' => APICODE_SUCCESS,
                'msg' => '到达成功'
            ];
//        } else {
//            return [
//                'code' => APICODE_ERROR,
//                'msg' => '到达失败'
//            ];
//        }
    }
    //往公众号推送消息
    public function TencentPush($opneid){
        $file = fopen('./log.txt', 'a+');
        fwrite($file, "-------------------消息信息11111111111111111111111--------------------"."\r\n");
//        $message = '{
//                    "touser":'.$opneid.',
//                    "msgtype":"text",
//                    "text":
//                    {
//                         "content":"Hello World"
//                    }
//                }';
//        $wxObj = new \Wy("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449");
//        $res = $wxObj->_message($message);

    }

    private function partner_post($order_id, $param, $status, $orders, $eventCode, $total_price, $rates,$surcharge)
    {
//        $file = fopen('./log.txt', 'a+');
        if ($status == 'WAIT_PAY') {
            $rate = json_decode($rates, true);
//            var_dump($total_price);
//            var_dump($rate);
//            exit();
            $bill = [
                'totalPrice' => (int)($total_price["money"] * 100) - (int)($surcharge *100),//行程基础类费用总额，单位为分。完成履约行为后，除去高速费、停车费、感谢费、其他费用四项附加类费用项外订单产生的行程费用总和
                'driveDistance' => (int)($total_price["Mileage"] * 1000),//	行驶里程,单位m
                'driveTime' => (int)($total_price["total_time"] * 60 * 1000),//行驶时长,单位ms
                'initPrice' => (int)($rate["startMoney"]["money"]*100),//起步价。订单开始履约需要收取的费用，包含一定的里程和时长。注：当时长费和里程费不满起步价，但需要按照起步价金额收取的时候，里程费和时长费不需要传递，只传起步价金额。

//                'normalTimePrice' => 1,//正常时长费。非高峰及夜间时段行驶时间产生的费用总额
//                'normalDistancePrice' => 2,//正常里程费。非高峰及夜间时段行驶里程产生的费用总额
                'longDistancePrice' => (int)($rate["Kilometre"]["Longfee"]*100),//远程费。超过一定里程之后收取的远途行驶里程费用总和
                'longDistance' => (int)($rate["Kilometre"]["LongKilometers"]*1000),//	远程里程。单位m
//                'nightPrice' => 1,//夜间里程费。夜间时段行驶里程产生的费用总额
//                'nightDistance' => 1,//	夜间里程。单位m
//                'highwayPrice' => 0,//高速费。订单履约过程产生的高速类费用，此费用为代收代付类费用不能开票，不能抽佣，不能使用红包抵扣
//                'tollPrice' => 0,//通行费。订单履约过程产生的过路过桥类费用，此费用为代收代付类费用不能开票，不能抽佣，不能使用红包抵扣
//                'parkPrice' => 0,//停车费。订单履约过程产生的停车类费用，此费用为代收代付类费用不能开票，不能抽佣，不能使用红包抵扣
                'otherPrice' => (int)($surcharge *100),//其他费。订单履约过程中产生的其他代收代付类费用，如：清洁费等费用项，此费用为代收代付类费用不能开票，不能抽佣，不能使用红包抵扣
//                'dynamicPrice' => 0,//动态调价费。根据供需关系对订单费用进行实时调价的金额。（此值可正可负，当为正值时美团会处展示成溢价费；当为负值时美团会展示为动态折扣）
//                'cancelPay' => 0,//应收取消费。取消状态下等于waitingPrice+cancelPrice
//                'suspectStatus' => 0,//费用是否可异议:0-否,1-是
//                'discountPrice' => 0,//服务商折扣金额。服务商给美团渠道提供的优惠金额，此字段需要传正值
//                'waitingPrice' => 0,//等待费。等待时间产生的费用总和
//                'waitingTime' => 12,//等待时长。单位ms
//                'cancelPrice' => 0,//	取消费
//                'eDispatchPrice' => 0,//电调费金额，单位：分。出租车场景下，国家允许收取的加价费用
//                'taxiMeterFee' =>0,//计价器费金额，单位：分。出租车场景下，打表计费产生的费用
            ];
//            fwrite($file, "---------------------计费--------------------" . json_encode($rate, JSON_UNESCAPED_UNICODE) . "\r\n");
            $rate["Kilometre"][0]["LongKilometers"] == "0.00" ?: $bill["longDistance"] = (int)($rate["Kilometre"][0]["LongKilometers"] * 1000);
            $rate["Kilometre"][0]["Longfee"] == "0.00" ?: $bill["longDistancePrice"] = (int)($rate["Kilometre"][0]["Longfee"] * 100);
//            if ($total_price["money"] != $rate["startMoney"]["money"]) {
                $driveDistancePrice = 0 ;
                if(!empty($rate["Mileage"])){
                    foreach ($rate["Mileage"] as $key=>$value){
                        $driveDistancePrice += $value['money'] ;
                    }
                }
                $bill['driveDistancePrice'] = (int)($driveDistancePrice*100) ;//$rate["Mileage"][0]["money"]*100;//	里程费。行驶里程产生的费用总额，里程费=正常里程费+夜间里程费
                $driveTimePrice = 0 ;
                if(!empty($rate["tokinaga"])){
                    foreach ($rate["tokinaga"] as $k=>$v){
                        $driveTimePrice += (int)($v['money']*100) ;
                    }
                }
                $bill['driveTimePrice'] = (int)($driveTimePrice) ;//$rate["tokinaga"][0]["money"]*100;//时长费。行驶时间产生的费用总额
//            }
        } else {
            $bill = [
                'totalPrice' => 0,
                'driveDistance' => 0,
                'driveTime' => 0,
                'initPrice' => 0,
                'driveDistancePrice' => 0,
                'driveTimePrice' => 0,
            ];
        }

        //车辆
        $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => $orders['conducteur_id']])->value('vehicle_id');
        $vehicle = Db::name('vehicle')->field('PlateColor,VehicleNo,Model')->where(['id' => $vehicle_id])->find();

        $carInfo = [
            'carColor' => $vehicle['PlateColor'],
            'carNumber' => $vehicle['VehicleNo'],
            'brandName' => $vehicle['Model'],
        ];
        $conducteur = Db::name('conducteur')->field('id,DriverName,DriverPhone')->where(['id' => $orders['conducteur_id']])->find();
        $customerServiceInfo = [
            'cancelReason' => "司机取消",
            'opName' => $conducteur['DriverName'],
        ];
        $chargeInfo = [
            'offlinePayAmount' => 0
        ];
        $continuousAssign = [
            'preDestLng' => "1",
            'preDestLat' => "1",
            'preOrderId' => "1",
            'preRemainDistance' => 1,
            'preRemainSecond' => 1,
            'wholePickupDistance' => 1,
            'wholePickupSecond' => 1,
        ];

        $conducteur_id = $orders['conducteur_id'];
        $driverLastNames = mb_substr($conducteur['DriverName'], 0, 1, 'utf-8');
        $driverInfo = [
//            'driverLastName'=>"王先生",
//            'driverMobile'=>"15776833552",
//            'driverName'=>"王先生",
//            'driverVirtualMobile'=>"15776833552",
//            'partnerDriverId'=>"273",
//            'driverRate'=>"4",
//            'driverPic'=>"1",
            'driverLastName' => $driverLastNames,
            'driverMobile' => $conducteur['DriverPhone'],
            'driverName' => $conducteur['DriverName'],
            'driverVirtualMobile' => $orders['conducteur_virtual'],
            'partnerDriverId' => "$conducteur_id",
//            'driverRate' => "5",
//            'driverPic' => ""//$conducteur['grandet'],
        ];

        $product = [
            'partnerCarTypeId' => intval($orders['partnerCarTypeId']),
//            'outCarTypeId' => "1"
        ];
        $gps = new Gps();
        $driverInfos = db()->query("call getCarInfoByOrderId($vehicle_id)");

        $driverInfos = $driverInfos[0][0];
        $data_gps = $gps->getCarsStatus($driverInfos["Gps_number"])["data"][0];

        $ini['longitude'] = $data_gps["lonc"];
        $ini['latitude'] = $data_gps["latc"];

        $lat = 0 ;
        $lng = 0 ;
        if(!empty($data_gps)){
            $lat = $data_gps["latc"] ;
            $lng = $data_gps["lonc"] ;
        }else{
            //按照状态进行获取
//            if($orders['status'] == 2){  //证明已经接单了
//                $block = explode(',',$orders['orders_from'])  ;
//                $lng = floatval(sprintf("%.6f", $block[0]));//floatval($orders['DepLongitude']) ;
//                $lat = floatval(sprintf("%.6f", $block[1]));//floatval($orders['DepLatitude']) ;
//            }else{
                //除2状态就是行程中的订单-取块里面的数据
                $location = explode(',',$gps->getDriverPositionByDriverId($conducteur_id));
                if(!empty($location)){
                    $lat = floatval($location[0]) ;
                    $lng = floatval($location[1]) ;
                }else{
                    $lat = 45.69726 ;
                    $lng = 126.585479 ;
                }
//            }
        }

        $driverLocation = [
            'lat'=>$lat,
            'lng'=>$lng,
        ];

        $candidateConfirmList = [
            'driverInfo' => json_encode($driverInfo, JSON_UNESCAPED_UNICODE),
            'carInfo' => json_encode($carInfo, JSON_UNESCAPED_UNICODE),
            'product' => json_encode($product, JSON_UNESCAPED_UNICODE),
            'driverLocation' => json_encode($driverLocation, JSON_UNESCAPED_UNICODE),
        ];

        $driverFingerprint = [
            'wifimac' => "c4:b3:01:cb:f6:cf",
            'dm' => "false",
        ];

        $param['channel'] = strval("tcdc_car");
        $param['timestamp'] = strval(time() * 1000);

        $param['eventCode'] = $eventCode;
        $param['bill'] = json_encode($bill, JSON_UNESCAPED_UNICODE);
        $param['carInfo'] = json_encode($carInfo, JSON_UNESCAPED_UNICODE);

//        $param['chargeInfo'] = json_encode($chargeInfo, JSON_UNESCAPED_UNICODE);
        $param['driverInfo'] = json_encode($driverInfo, JSON_UNESCAPED_UNICODE);
        $param['eventTime'] = strval(time() * 1000);
        $param['mtOrderId'] = strval($orders['mtorderid']);
        $param['partnerOrderId'] = strval($order_id);
        $param['product'] = json_encode($product, JSON_UNESCAPED_UNICODE);
        $param['driverLocation'] = json_encode($driverLocation, JSON_UNESCAPED_UNICODE);
        $param['status'] = $status;

        if($status == "CANCEL_BY_DRIVER"){      //司机取消,进行虚拟号解绑
            $vritualController = new \app\api\controller\Vritualnumber() ;
            $result = $vritualController->releasePhoneNumberByOrderId((int)$order_id);
//            fwrite($file, "-------------------虚拟号:--------------------" . json_encode($result, JSON_UNESCAPED_UNICODE) . "\r\n");
            $param['customerServiceInfo'] = json_encode($customerServiceInfo, JSON_UNESCAPED_UNICODE);
        }
        //根据status,更新美团状态
        $inii['id'] = $order_id ;
        if($status == "CONFIRM"){
            $inii['mt_status'] = 30 ;
        }else if($status == "SET_OUT"){
            $inii['mt_status'] = 50 ;
        }else if($status == "ARRIVE"){
            $inii['mt_status'] = 60 ;
        }else if($status == "DRIVING"){
            $inii['mt_status'] = 70 ;
        }else if($status == "DELIVERED"){
            $inii['mt_status'] = 80 ;
        }else if($status == "WAIT_PAY"){
            $inii['mt_status'] = 90 ;
        }else{
            $inii['mt_status'] = 70 ;
        }
        Db::name('order')->update($inii);
//        $param['candidateConfirmList'] = json_encode($candidateConfirmList) ;
//        if ($eventCode == "0") {
//            $param['endTripLocation'] = json_encode(["lat" => 39.12504, "lng" => 117.200032], JSON_UNESCAPED_UNICODE);
//            $param['startTripLocation'] = json_encode(["lat" => 39.12504, "lng" => 117.200032], JSON_UNESCAPED_UNICODE);
//            $param['setoutLocation'] = json_encode(["lat" => 39.12504, "lng" => 117.200032], JSON_UNESCAPED_UNICODE);
//            $param['confirmLocation'] = json_encode(["lat" => 39.12504, "lng" => 117.200032], JSON_UNESCAPED_UNICODE);
//        }
//        $param['driverFingerprint'] = json_encode($driverFingerprint, JSON_UNESCAPED_UNICODE);
        $sign = $this->getSign($param, "IQBs6DADXQrBawyQyVZaQA==");
        $param['sign'] = $sign;//"4wpitbq9JyLEZXj3InLbTw==" ;
//        fwrite($file, "---------------------------------------" . json_encode($param, JSON_UNESCAPED_UNICODE) . "\r\n");
        $datas = $this->request_post("https://qcs-openapi.meituan.com/api/open/callback/common/v1/pushOrderStatus", $param);   //"application/x-www-from-urlencoded"
//        fwrite($file, "-------------------数据1:--------------------" . json_encode($datas, JSON_UNESCAPED_UNICODE) . "\r\n");
    }

    private function request_post_partner($url = "", $param = "", $header = "")
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

    //乘客已上车
    public function PassengerBoard()
    {
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id')
            ];

            $status = Db::name('order')->where(['id' => input('order_id')])->value('status');
            if ($status != 3) {
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '状态不对，无法上车'
                ];
            }


            //更新订单状态
            $ini['id'] = input('order_id');
            $ini['status'] = 4;
            if(!empty(input('passengers_location'))){
                $ini['passengers_location'] = input('passengers_location') ;
            }
            $order = Db::name('order')->update($ini);
//            $paramss = [
//                "order_id" => input('order_id'),
//                "arrive_time" => time()*1000,
//            ];
//            $order_history = db('order_history')->insert($paramss);

            if ($order) {
                //判断一下，是不是美团订单
                $orders = Db::name('order')->where(['id' => input('order_id')])->find();
                if (!empty($orders['mtorderid'])) {
                    $param = [];
                    $total = [];
                    $rates = [];
                    $this->partner_post($orders['id'], $param, 'DRIVING', $orders, "0", $total, $rates,0);
                }
                //判断一下是否是出租车
                if($orders['classification'] == '出租车'){
                    $this->newsService($orders,'司机已经确认您上车，行程即将开始，请系好安全带，如发现车辆不符，司机态度不好，可随时投诉。投诉微信号：1932991034') ;
                }
                return [
                    'code' => APICODE_SUCCESS,
                    'msg' => '上车成功'
                ];

            } else {
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '上车失败'
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }


    }


    //到达目的地
    public function ArriveDestination()
    {
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id')
            ];

            $status = Db::name('order')->where(['id' => input('order_id')])->value('status');
            if ($status != 4) {
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '状态不对，无法到达目的地'
                ];
            }

            //更新订单状态
            $ini['id'] = input('order_id');

            $orderInfo = Db::name('order')->where(['id' => input('order_id')])->find();
            if($orderInfo['classification'] == '代驾'){
                $ini['status'] = 16;
            }else{
                $ini['status'] = 11;
            }

            $ini['end_time'] = time();
            if(!empty(input('destination_location'))){
                $ini['destination_location'] = input('destination_location') ;
            }
            $order = Db::name('order')->update($ini);

            if ($order) {

                $m = new Marketing();
                //随机
                $extra['business_id'] = $orderInfo['business_id'];
                $extra['origin_latitude'] = $orderInfo['DepLatitude'];
                $extra['origin_longitude'] = $orderInfo['DepLongitude'];
                $extra['destination_latitude'] = $orderInfo['DestLatitude'];
                $extra['destination_longitude'] = $orderInfo['DestLongitude'];
                $m->judgeActivity($orderInfo['user_id'], $orderInfo['city_id'], 2, $extra);
                $m->judgeActivity($orderInfo['user_id'], $orderInfo['city_id'], 5, $extra);
                $m->judgeActivity($orderInfo['user_id'], $orderInfo['city_id'], 6, $extra);
                $m->judgeActivity($orderInfo['user_id'], $orderInfo['city_id'], 8, $extra);
                $opneid = Db::name('user')->where(['id' => $orderInfo['user_id']])->value('openid');
                if (!empty($opneid)) {
                    $index = new Index();
                    //取用户的openid
                    $order_id=input('order_id');
                    $index->sendOrderStatus($opneid, '您已到达目的地，请携带好随身物品', $orderInfo['order_name'], $orderInfo['origin'], $orderInfo['Destination'], date('Y年m月d日 H:i', time()),"pages/fastCar/order?orderId=$order_id");
                }
                //判断一下，是不是美团订单
                if (!empty($orderInfo['mtorderid'])) {
                    $param = [];
                    $tatol = [];
                    $rates = [];
                    $this->partner_post($orderInfo['id'], $param, 'DELIVERED', $orderInfo, "0", $tatol, $rates,0);
                }
                //判断一下是否是出租车
                if($orderInfo['classification'] == '出租车'){
//                    $this->newsService($orderInfo,'司机到达目的地') ;
                }

                return [
                    'code' => APICODE_SUCCESS,
                    'msg' => '到达成功'
                ];
            } else {
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '到达失败'
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //查询司机实时订单
    public function QueryRealtimeOrder()
    {
        if (input('?id')) {
            $params = [
                "conducteur_id" => input('id'),
            ];

            $order = db('order')->where($params)->where('status', 'in', '2,3,4')->find();

            $arrive_time = Db::name('order_history')->where(['order_id' => $order['id']])->value('arrive_time');
            if (!empty($arrive_time)) {
                $order['arrive_time'] = $arrive_time;
            } else {
                $order['arrive_time'] = 0;
            }

            if (!empty($order)) {
                $order['star'] = Db::name('user')->where(['id' => $order['user_id']])->value('star');         //五星
                $order['portrait'] = Db::name('user')->where(['id' => $order['user_id']])->value('portrait');//头像
            }

            $data = array($order);

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }
    }

    //确认费用
    public function RecognitionExpense()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //司机的信息
        $order = Db::name('order')->alias('o')
            ->field('c.key,c.service,c.terimnal,c.trace,o.company_id,o.business_id,o.business_type_id,h.arrive_time,o.end_time,o.gps_number,o.company_id,o.DepLongitude,o.DepLatitude,o.DestLongitude,o.DestLatitude')
            ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
            ->join('mx_order_history h', 'h.order_id = o.id', 'left')
            ->where(['o.id' => input('order_id')])
            ->find();
        $start_time = $order['arrive_time'];       // 订单开始时间
        $end_time = $order['end_time'];            // 订单结束时间
        if (strlen($start_time) == 10) {
            $start_time = $start_time * 1000;
        }
        if (strlen($end_time) == 10) {
            $end_time = $end_time * 1000;
        }
        $useGps = false;
        $locus = $this->gj($order['key'], $order['service'], $order['terimnal'], $order['trace'], $start_time, $end_time);
        $is_distance = Db::name('company')->where(['id' =>$order['company_id'] ])->value('is_distance') ;
        $is_scope = Db::name('company')->where(['id' =>$order['company_id'] ])->value('is_scope') ;
        $is_await = Db::name('company')->where(['id' =>$order['company_id'] ])->value('is_await') ;

        $is_different = 0 ;                         //议价
        $zero = 0 ;                                 //等待分钟价格
        if ($order["gps_number"]) {
            $locus2 = $this->gjByGps($order["gps_number"], $start_time, $end_time);
             if($is_distance == 1){     //按照gps优化计费  , 比较一下谁大取谁
                 if ($locus2['tracks'][0]['distance'] > 0 ) {     //$locus['tracks'][0]['distance']
                     $useGps = true;
                     $locus = $locus2;
                 }
             }else{                    //比较一下，gps距离和手机距离谁远，算谁的.
                 if ($locus2['tracks'][0]['distance'] > $locus['tracks'][0]['distance']) {     //$locus['tracks'][0]['distance']
                     $useGps = true;
                     $locus = $locus2;
                 }
             }
        }
        $tracks = json_encode($locus['tracks']);
        //总里程，总时长
        $Mileage = sprintf("%.2f", ($locus['tracks'][0]['distance'] / 1000));
        $total_time = sprintf("%.2f", ($locus['tracks'][0]['time'] / 1000 / 60));        //秒
        $money = 0;                                 //初始金额
        $lastlady = 0;                             //远途初始金额
        $company_rates = [] ;
        //根据订单是实时，还是预约
        $classification = Db::name('order')->where(['id' =>input('order_id') ])->value('classification') ;
        //获取公司的实时计价规则
        if($classification == '实时'){
            if($order['business_id'] == 100){
                $order['business_id'] = 2 ;
            }
            //是否需要范围判断
            if($is_scope == 1){
                //判断城内/城外/超出城外
                $company_scope = Db::name('company_scope')->where(['company_id'=>$order['company_id']])->select() ;
                //用户起点和终点
                $orgin_location = [
                    'lng'=>$order['DepLongitude'],
                    'lat'=>$order['DepLatitude'],
                ];
                $destination_location = [
                    'lng'=>$order['DestLongitude'],
                    'lat'=>$order['DestLatitude'],
                ];
                $scoep = $this->calculatescope($company_scope,$orgin_location,$destination_location);
                if($scoep == 1){                    //城内
                    $company_rates = Db::name('company')->alias('c')
                        ->field('r.*')
                        ->join('mx_company_rates r', 'r.company_id = c.id', 'inner')
                        ->where(['c.id' => $order['company_id']])
                        ->where(['r.business_id' => $order['business_id']])
                        ->where(['r.businesstype_id' => $order['business_type_id']])
                        ->where(['titles'=>'城内'])
                        ->find();
                }else if($scoep == 2){              //城外
                    $company_rates = Db::name('company')->alias('c')
                        ->field('r.*')
                        ->join('mx_company_rates r', 'r.company_id = c.id', 'inner')
                        ->where(['c.id' => $order['company_id']])
                        ->where(['r.business_id' => $order['business_id']])
                        ->where(['r.businesstype_id' => $order['business_type_id']])
                        ->where(['titles'=>'城外'])
                        ->find();
                }else if($scoep == 3){              //议价
                    $company_rates = Db::name('company')->alias('c')
                        ->field('r.*')
                        ->join('mx_company_rates r', 'r.company_id = c.id', 'inner')
                        ->where(['c.id' => $order['company_id']])
                        ->where(['r.business_id' => $order['business_id']])
                        ->where(['r.businesstype_id' => $order['business_type_id']])
                        ->where(['titles'=>'城外'])
                        ->find();
                    $is_different = 1;
                }
                //等待费
                if($is_await == 1){
                    $zero = Db::name('company_real_await')->where(['company_id'=>$order['company_id']])->value('zero');
                }
            }else{
                $company_rates = Db::name('company')->alias('c')
                    ->field('r.*')
                    ->join('mx_company_rates r', 'r.company_id = c.id', 'inner')
                    ->where(['c.id' => $order['company_id']])
                    ->where(['r.business_id' => $order['business_id']])
                    ->where(['r.businesstype_id' => $order['business_type_id']])
                    ->find();
            }
        }
        //获取公司预约的计价规则
        if($classification == '预约' || $classification == '代驾' || $classification == '公务车' ){
            if($is_scope == 1){
                //判断城内/城外/超出城外
                $company_scope = Db::name('company_scope')->where(['company_id'=>$order['company_id']])->select() ;
                //用户起点和终点
                $orgin_location = [
                    'lng'=>$order['DepLongitude'],
                    'lat'=>$order['DepLatitude'],
                ];
                $destination_location = [
                    'lng'=>$order['DestLongitude'],
                    'lat'=>$order['DestLatitude'],
                ];
                $scoep = $this->calculatescope($company_scope,$orgin_location,$destination_location);
                if($scoep == 1){                    //城内
                    $company_rates = Db::name('company')->alias('c')
                        ->field('r.*')
                        ->join('company_appointment_rates r', 'r.company_id = c.id', 'inner')
                        ->where(['c.id' => $order['company_id']])
                        ->where(['r.business_id' => $order['business_id']])
                        ->where(['r.businesstype_id' => $order['business_type_id']])
                        ->where(['titles'=>'城内'])
                        ->find();
                }else if($scoep == 2){              //城外
                    $company_rates = Db::name('company')->alias('c')
                        ->field('r.*')
                        ->join('company_appointment_rates r', 'r.company_id = c.id', 'inner')
                        ->where(['c.id' => $order['company_id']])
                        ->where(['r.business_id' => $order['business_id']])
                        ->where(['r.businesstype_id' => $order['business_type_id']])
                        ->where(['titles'=>'城外'])
                        ->find();
                }else if($scoep == 3){              //议价
                    $company_rates = Db::name('company')->alias('c')
                        ->field('r.*')
                        ->join('company_appointment_rates r', 'r.company_id = c.id', 'inner')
                        ->where(['c.id' => $order['company_id']])
                        ->where(['r.business_id' => $order['business_id']])
                        ->where(['r.businesstype_id' => $order['business_type_id']])
                        ->where(['titles'=>'城外'])
                        ->find();
                    $is_different = 1;
                }
                //等待费
                if($is_await == 1){
                    $zero = Db::name('company_appointment_await')->where(['company_id'=>$order['company_id']])->value('zero');
                }
            }else{
                $company_rates = Db::name('company')->alias('c')
                    ->field('r.*')
                    ->join('company_appointment_rates r', 'r.company_id = c.id', 'inner')
                    ->where(['c.id' => $order['company_id']])
                    ->where(['r.business_id' => $order['business_id']])
                    ->where(['r.businesstype_id' => $order['business_type_id']])
                    ->find();
            }
        }
        //远途公里
        $munication = $Mileage - $company_rates['LongKilometers'];

        //时间分段
        $data = $this->judgmentTimeSlicing($start_time, $end_time, $company_rates);

        if ($useGps) {
            //按照每段时间，来获取里程和时长
            foreach ($data as $key => $value) {        //按天 ，分割条数
                foreach ($value['timeSplice'] as $k => $v) {        //每天时间段
                    $result[]=array('process'=>$this->gjByGps($order["gps_number"], $v['startTime'], $v['endTime']),'valuation'=>$v['moneyRule'],'times'=>[$v['startTime'], $v['endTime'], $key]);
                }
            }
        } else {
            //按照每段时间，来获取里程和时长
            foreach ($data as $key => $value) {        //按天 ，分割条数
                foreach ($value['timeSplice'] as $k => $v) {        //每天时间段
                    $result[]=array('process'=>$this->gj($order['key'], $order['service'], $order['terimnal'], $order['trace'], $v['startTime'], $v['endTime']),'valuation'=>$v['moneyRule'],'times'=>[$v['startTime'], $v['endTime'], $key]);
                }
            }
        }

        $datas = [];
        $Startmileage = 0;//起步里程
        $startMin = 0;//起步时长
        $Mileage_data = [];
        $tokinaga_data = [];
        //总距离,总时长
        $sum_Mileage = 0;
        $sum_Tokinaga = 0;
        for ($i = 0; $i < count($result); $i++) {
            $startTime = substr(date('Y-m-d H:i:s', $result[$i]['times'][0]), 10, -3);
            $endTime = substr(date('Y-m-d H:i:s', $result[$i]['times'][1]), 10, -3);
            $day = $result[$i]['times'][2];
            $sy_startMile = $result[$i]["process"]['tracks'][0]['distance'] / 1000; //当前分段里程
            $sy_startMin = ($result[$i]['times'][1] - $result[$i]['times'][0]) / 60; //当前分段时长
            if ($i == 0) {  //给起步里程和起步时长赋值
                $Startmileage = floatval($result[0]['valuation']['startMile']);
                $startMin = floatval($result[0]['valuation']['startMin']);
                $money += $result[0]['valuation']['startMoney']; //起步价（只加一次）
                $datas['startMoney'] = [
                    'mileage' => $Startmileage,
                    'tokinaga' => $startMin,
                    'money' => $result[0]['valuation']['startMoney'],            //起步价
                ];
            }
            if ($sy_startMile > 0) {
                //仍存在剩余起步里程
                if ($sy_startMile > $Startmileage) {
                    //该笔订单的长度大于剩余起步里程长度
                    $sy_startMile = $sy_startMile - $Startmileage;
                    $sum_Mileage += sprintf("%.2f", $sy_startMile) + $Startmileage;
                    $Startmileage = 0;                 //起步里程清0
                } else {
                    $Startmileage = $Startmileage - $sy_startMile;
                    $sum_Mileage += sprintf("%.2f", $sy_startMile);
                    $sy_startMile = 0;
                }
            }
            $Mileage_data[] = [
                'day' => $day,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'mileage' => sprintf("%.2f", $sy_startMile),
                'money' => sprintf("%.2f", ($sy_startMile * $result[$i]['valuation']['moneyPerMile'])),
            ];

            $money += sprintf("%.2f", ($sy_startMile * $result[$i]['valuation']['moneyPerMile']));
            //起步时长
            if ($sy_startMin > 0) {
                if ($sy_startMin > $startMin) {
                    $sy_startMin = $sy_startMin - $startMin;
                    $sum_Tokinaga += $startMin;
                    $startMin = 0;                 //起步时长清0
                } else {
                    $startMin = $startMin - $sy_startMin;
                    $sum_Tokinaga += $sy_startMin;
                    $sy_startMin = 0;
                }
            }
            //返回列表
            $tokinaga_data[] = [
                'day' => $day,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'tokinaga' => sprintf("%.2f", ($sy_startMin)),
                'money' => sprintf("%.2f", ($sy_startMin * $result[$i]['valuation']['moneyPerMin'])),
            ];
            $money += sprintf("%.2f", ($sy_startMin * $result[$i]['valuation']['moneyPerMin']));
            $sum_Tokinaga += sprintf("%.2f", ($sy_startMin));
        }
        $Longfee = 0;          //远途费
        $LongKilometers = 0;
        if ($munication > 0) {
            $LongKilometers = $munication;
            $Longfee = $munication * $company_rates['Longfee'];
        }
        //远途
        $Kilometre[] = [
            'LongKilometers' => sprintf("%.2f", $LongKilometers),
            'Longfee' => sprintf("%.2f", $Longfee)
        ];
        $times = input('times') ;
        $await_min = 0 ;
        $moneyss = 0;

        if(!empty($times)){
            $await_min = sprintf("%.2f", ($times /60)) ;
            $moneyss = $await_min * $zero ;
        }
        $await[]=[
            'await_min'=>$await_min,
            'moneys'=>sprintf("%.2f", ($moneyss)),
        ];
        $datas['Mileage'] = $Mileage_data;
        $datas['tokinaga'] = $tokinaga_data;
        $datas['Kilometre'] = $Kilometre;
        $datas['await'] = $await ;              //等待费
        $rates = json_encode($datas);           //转换成json格式
        //更新订单金额
        $ini['id'] = input('order_id');
        $ini['money'] = $money + $Longfee;
        $ini['fare'] = $money + $Longfee;
        $ini['rates'] = $rates;
        $ini['tracks'] = $tracks;
        $ini['status'] = 11;
        $total_price = [
            'Mileage' => sprintf("%.2f", ($sum_Mileage)),
            'total_time' => sprintf("%.2f", ($sum_Tokinaga)),
            'money' => sprintf("%.2f", ($money + $Longfee + $moneyss)),
        ];
        $ini['total_price'] = json_encode($total_price);
        Db::name('order')->update($ini);

        return ['code' => APICODE_SUCCESS, "use_gps" => $useGps, 'msg' => '成功', 'Mileage' => sprintf("%.2f", ($sum_Mileage)), 'total_time' => sprintf("%.2f", ($sum_Tokinaga)), 'sum_money' => sprintf("%.2f", ($money + $Longfee + $moneyss)), 'data' => $datas,'is_different'=>$is_different,'is_await'=>$is_await,'await_min'=>$await_min];
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

    //预确认费用
    public function RecognitionExpenseTest()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //司机的信息
        $order = Db::name('order')->alias('o')
            ->field('c.key,c.service,c.terimnal,c.trace,o.company_id,o.business_id,o.business_type_id,h.arrive_time,o.end_time,o.gps_number')
            ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
            ->join('mx_order_history h', 'h.order_id = o.id', 'left')
            ->where(['o.id' => input('order_id')])
            ->find();
        $start_time = $order['arrive_time'];       // 订单开始时间
        $end_time = $order['end_time'];            // 订单结束时间
        if (strlen($start_time) == 10) {
            $start_time = $start_time * 1000;
        }
        if (strlen($end_time) == 10) {
            $end_time = $end_time * 1000;
        }
        $useGps = false;
        $locus = $this->gj($order['key'], $order['service'], $order['terimnal'], $order['trace'], $start_time, $end_time);
        if ($order["gps_number"]) {
            $locus2 = $this->gjByGps($order["gps_number"], $start_time, $end_time);
            if ($locus2['tracks'][0]['distance'] > 0) {     //$locus['tracks'][0]['distance']
                $useGps = true;
                $locus = $locus2;
            }
        }
        $tracks = json_encode($locus['tracks']);
        //总里程，总时长
        $Mileage = sprintf("%.2f", ($locus['tracks'][0]['distance'] / 1000));
        $money = 0;

        //获取公司的计价规则
        $company_rates = Db::name('company')->alias('c')
            ->field('r.*')
            ->join('mx_company_rates r', 'r.company_id = c.id', 'inner')
            ->where(['c.id' => $order['company_id']])
            ->where(['r.business_id' => $order['business_id']])
            ->where(['r.businesstype_id' => $order['business_type_id']])
            ->find();

        //远途公里
        $munication = $Mileage - $company_rates['LongKilometers'];
        //时间分段
        $data = $this->judgmentTimeSlicing($start_time, $end_time, $company_rates);
        if ($useGps) {
            //按照每段时间，来获取里程和时长
            foreach ($data as $key => $value) {        //按天 ，分割条数
                foreach ($value['timeSplice'] as $k => $v) {        //每天时间段
                    $result[]=array('process'=>$this->gjByGps($order["gps_number"], $v['startTime'], $v['endTime']),'valuation'=>$v['moneyRule'],'times'=>[$v['startTime'], $v['endTime'], $key]);
                }
            }
        } else {
            //按照每段时间，来获取里程和时长
            foreach ($data as $key => $value) {        //按天 ，分割条数
                foreach ($value['timeSplice'] as $k => $v) {        //每天时间段
                    $result[$k]['process'] = $this->gj($order['key'], $order['service'], $order['terimnal'], $order['trace'], $v['startTime'], $v['endTime']);          //过程
                    $result[$k]['valuation'] = $v['moneyRule'];
                    $result[$k]['times'] = [$v['startTime'], $v['endTime'], $key];
                }
            }
        }
        $datas = [];
        $Startmileage = 0;//起步里程
        $startMin = 0;//起步时长
        $Mileage_data = [];
        $tokinaga_data = [];
        //总距离,总时长
        $sum_Mileage = 0;
        $sum_Tokinaga = 0;
        for ($i = 0; $i < count($result); $i++) {
            $startTime = substr(date('Y-m-d H:i:s', $result[$i]['times'][0]), 10, -3);
            $endTime = substr(date('Y-m-d H:i:s', $result[$i]['times'][1]), 10, -3);
            $day = $result[$i]['times'][2];
            $sy_startMile = $result[$i]["process"]['tracks'][0]['distance'] / 1000; //当前分段里程
            $sy_startMin = ($result[$i]['times'][1] - $result[$i]['times'][0]) / 60; //当前分段时长
            if ($i == 0) {  //给起步里程和起步时长赋值
                $Startmileage = floatval($result[0]['valuation']['startMile']);
                $startMin = floatval($result[0]['valuation']['startMin']);
                $money += $result[0]['valuation']['startMoney']; //起步价（只加一次）
                $datas['startMoney'] = [
                    'mileage' => $Startmileage,
                    'tokinaga' => $startMin,
                    'money' => $result[0]['valuation']['startMoney'],            //起步价
                ];
            }
            if ($sy_startMile > 0) {
                //仍存在剩余起步里程
                if ($sy_startMile > $Startmileage) {
                    //该笔订单的长度大于剩余起步里程长度
                    $sy_startMile = $sy_startMile - $Startmileage;
                    $sum_Mileage += sprintf("%.2f", $sy_startMile) + $Startmileage;
                    $Startmileage = 0;                 //起步里程清0
                } else {
                    $Startmileage = $Startmileage - $sy_startMile;
                    $sum_Mileage += sprintf("%.2f", $sy_startMile);
                    $sy_startMile = 0;
                }
            }
            $Mileage_data[] = [
                'day' => $day,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'mileage' => sprintf("%.2f", $sy_startMile),
                'money' => sprintf("%.2f", ($sy_startMile * $result[$i]['valuation']['moneyPerMile'])),
            ];

            $money += sprintf("%.2f", ($sy_startMile * $result[$i]['valuation']['moneyPerMile']));
            //起步时长
            if ($sy_startMin > 0) {
                if ($sy_startMin > $startMin) {
                    $sy_startMin = $sy_startMin - $startMin;
                    $sum_Tokinaga += $startMin;
                    $startMin = 0;                 //起步时长清0
                } else {
                    $startMin = $startMin - $sy_startMin;
                    $sum_Tokinaga += $sy_startMin;
                    $sy_startMin = 0;
                }
            }
            //返回列表
            $tokinaga_data[] = [
                'day' => $day,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'tokinaga' => sprintf("%.2f", ($sy_startMin)),
                'money' => sprintf("%.2f", ($sy_startMin * $result[$i]['valuation']['moneyPerMin'])),
            ];
            $money += sprintf("%.2f", ($sy_startMin * $result[$i]['valuation']['moneyPerMin']));
            $sum_Tokinaga += sprintf("%.2f", ($sy_startMin));
        }
        $Longfee = 0;          //远途费
        $LongKilometers = 0;
        if ($munication > 0) {
            $LongKilometers = $munication;
            $Longfee = $munication * $company_rates['Longfee'];
        }
        //远途
        $Kilometre[] = [
            'LongKilometers' => sprintf("%.2f", $LongKilometers),
            'Longfee' => sprintf("%.2f", $Longfee)
        ];
        $datas['Mileage'] = $Mileage_data;
        $datas['tokinaga'] = $tokinaga_data;
        $datas['Kilometre'] = $Kilometre;
        $rates = json_encode($datas);           //转换成json格式
        //更新订单金额
        $ini['id'] = input('order_id');
//        $ini['money'] = $money + $Longfee;
//        $ini['fare'] = $money + $Longfee;
//        $ini['rates'] = $rates;
        $ini['tracks'] = $tracks;
//        $ini['status'] = 11;
//        $total_price = [
//            'Mileage' => sprintf("%.2f", ($sum_Mileage)),
//            'total_time' => sprintf("%.2f", ($sum_Tokinaga)),
//            'money' => sprintf("%.2f", ($money + $Longfee)),
//        ];
//        $ini['total_price'] = json_encode($total_price);
//        Db::name('order')->update($ini);
//        var_dump($ini);
//        exit;
        //判断是不是，美团订单

//        if (!empty($orders['mtorderid'])) {
//            $param = [];
//            $total = [] ;
//            $rates_q = [] ;
//            $this->partner_post(input('order_id'), $param, 'WAIT_PAY', $orders, "0", $total , $rates_q,0);
//        }

        return ['code' => APICODE_SUCCESS, "use_gps" => $useGps, 'msg' => '成功', 'Mileage' => sprintf("%.2f", ($sum_Mileage)), 'total_time' => sprintf("%.2f", ($sum_Tokinaga)), 'sum_money' => sprintf("%.2f", ($money + $Longfee)), 'data' => $datas];
    }
    //评价乘客
    public function EvaluationPassengers()
    {
        $params = [
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : 0,
            "user_id" => input('?user_id') ? input('user_id') : '',
            "item" => input('?item') ? input('item') : '',
            "mark" => input('?mark') ? input('mark') : '',
            "star" => input('?star') ? input('star') : '',
        ];
        $params = $this->filterFilter($params);
        $required = ["star", "user_id", "conducteur_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $conducteur_evaluate = Db::name('conducteur_evaluate')->insert($params);

        if ($conducteur_evaluate) {
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '评价成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '评价失败'
            ];
        }
    }

    //取消订单
    public function cancelOrder()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : 0,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //司机取消次数
        $orders = Db::name('order')->where(['id' => input('order_id') ])->find() ;
        $company_id = Db::name('conducteur')->where(['id' => $orders['conducteur_id'] ])->value('company_id');
        $start_time = date('Y-m-d',time())." 00:00:00" ;
        $end_time = date('Y-m-d',time())." 23:59:59" ;

        if ($orders['classification'] == '实时') {
            $company_elimination = Db::name('company_elimination')->where(['company_id' => $company_id, 'business_id' => $orders['business_id'], 'businesstype_id' => $orders['business_type_id']])->find(); //
            $motorman_count =  $company_elimination['motorman_count'];  //司机取消次数
            //获取一下，司机当天取消次数
            $order_count = Db::name('order')->where(['status'=>5,'conducteur_id'=>$orders['conducteur_id'],'is_cancel'=>1])
                                                    ->where('create_time','gt',$start_time)
                                                    ->where('create_time','lt',$end_time)
                                                    ->count();

            if($order_count >= $motorman_count){
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '取消失败,司机无法取消'
                ];
            }
        }

        if ($orders['classification'] == '预约') {
            $company_appointment = Db::name('company_appointment')->where(['company_id' => $company_id, 'business_id' => $orders['business_id'], 'businesstype_id' => $orders['business_type_id']])->find();
            $motorman_count =  $company_appointment['motorman_count'];  //司机取消次数
            //获取一下，司机当天取消次数
            $order_count = Db::name('order')->where(['status'=>5,'conducteur_id'=>$orders['conducteur_id'],'is_cancel'=>1])
                                                     ->where('create_time','gt',$start_time)
                                                     ->where('create_time','lt',$end_time)
                                                     ->count();

            if($order_count >= $motorman_count){
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '取消失败,司机无法取消'
                ];
            }
        }

        $ini['id'] = input('order_id');
        $ini['status'] = 5;
        $ini['is_cancel'] = 1;
        $ini['cancel_time'] = time() ;
        $order = Db::name('order')->update($ini);

        if ($order) {
            //取消成功之后,给订单回调
            $orders = Db::name('order')->where(['id' => input('order_id')])->find();
            if (!empty($orders['mtorderid'])) {
                $param = [];
                $tatol = [];
                $rates = [];
                $this->partner_post(input('order_id'), $param, 'CANCEL_BY_DRIVER', $orders, "0", $tatol, $rates,0);
            }
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '取消成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '取消失败'
            ];
        }
    }

    //查询轨迹
    public function gj($key, $sid, $tid, $trid, $start_time, $end_time)
    {
//        $key = "7609d7e35683fc4087c4351c6b8d96b5";
//        $sid = "148057";
//        $tid = "257445486";
//        $trid  = "20";
        if (strlen($start_time) == 10) {
            $start_time = $start_time * 1000;
        }
        if (strlen($end_time) == 10) {
            $end_time = $end_time * 1000;
        }
        $url = "https://tsapi.amap.com/v1/track/terminal/trsearch";
        $url .= "?key=" . $key;
        $url .= "&sid=" . $sid;
        $url .= "&tid=" . $tid;
        $url .= "&trid=" . $trid;
        $url .= "&starttime=" . $start_time;
        $url .= "&endtime=" . $end_time;
        $url .= "&correction=denoise%3d1%2cmapmatch%3d1%2cattribute%3d1%2cthreshold%3d0%2cmode%3ddriving";
        $url .= "&recoup=1";
        $url .= "&gap=50";
        $url .= "&ispoints=1";
        $url .= "&page=1";
        $url .= "&pagesize=999";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($data);
        $arr = $this->object_array($result);
//        dump($arr);
        return $arr['data'];
    }

    public function gjByGps($carId, $startTime, $endTime)
    {
        if (strlen($startTime) == 10) {
            $startTime = $startTime * 1000;
        }
        if (strlen($endTime) == 10) {
            $endTime = $endTime * 1000;
        }
//        $gps = new Gps();
        $data = $this->queryHistory($carId, $startTime, $endTime);
        $sumMile = 0;
        $sumTime = $endTime - $startTime;
        $startPoint = array();
        $endPoint = array();
        $points = [];
        if (count($data) > 0) {
            $sumMile = $data[count($data) - 1]["mileage"];
            foreach ($data as $index => $item) {
                if ($index == 0) {
                    $startPoint = array("location" => $item["lonc"] . "," . $item["latc"]);
                } else if ($index == count($data) - 1) {
                    $endPoint = array("location" => $item["lonc"] . "," . $item["latc"]);
                }
                array_push($points, array("location" => $item["lonc"] . "," . $item["latc"]));
            }
        }
        $res = array("tracks" => [array("distance" => $sumMile, "time" => $sumTime, "endPoint" => $endPoint, "startPoint" => $startPoint, "points" => $points)]);
        return $res;
    }

    public function getAdminToken()
    {
        $data = $this->request_post("http://www.gpsnow.net/user/login.do", array("name" => "tcdc", "password" => "123456"));
        $data = json_decode($data, true);
        $token = $data["data"]["token"];
        return $token;
    }

    public function getCarsStatus($carIds)
    {
        $data = $this->request_post("http://www.gpsnow.net/carStatus/getByCarIds.do", array("token" => $this->getAdminToken(), "carIds" => $carIds, "mapType" => 2));
        $data = json_decode($data, true);
        $data = $data["data"];
        return ['code' => APICODE_SUCCESS, 'data' => $data];
    }

    public function queryHistory($carId, $startTime, $endTime)
    {
        $startTime = mb_substr($startTime, 0, 10); //strtotime("2019/01/29 01:00:02");
        $endTime = mb_substr($endTime, 0, 10); //strtotime("2019/01/29 23:00:02");
        $startTime = gmdate("Y-m-d H:i:s", $startTime);
        $endTime = gmdate("Y-m-d H:i:s", $endTime);
        $token = $this->getAdminToken();
        $data = $this->request_post("http://www.gpsnow.net/position/queryHistory.do", array("carId" => $carId, "mapType" => "2", "startTime" => $startTime, "endTime" => $endTime, "token" => $token, "filter" => true));
        $data = json_decode($data, true);
        $res = $data["data"];
        return $res;
    }

    private function request_post($url = '', $post_data = array())
    {
        if (empty($url) || empty($post_data)) {
            return false;
        }
        $o = "";
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&";
        }
        $post_data = substr($o, 0, -1);
        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
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

    //订单消息接口
    public function OrderMessage()
    {
        if (input('?id')) {
            $params = [
                "order_id" => input('order_id')
            ];

            $data = db('order_message')->where($params)->select();

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

    //活动通知
    public function ActivityInform()
    {

        $data = Db::name('activity_inform')->select();

        return [
            "code" => APICODE_SUCCESS,
            "msg" => "查询成功",
            "data" => $data
        ];
    }

    //根据时间分段
    public function judgmentTimeSlicing($start, $end, $company_rates)
    {
//        echo "<pre>";
        //region 模拟数据
        $start_time = mb_substr($start, 0, 10); //strtotime("2019/01/29 01:00:02");
        $end_time = mb_substr($end, 0, 10);; //strtotime("2019/01/29 23:00:02");
//        $start_time = strtotime("2020/06/09 01:00:02");
//        $end_time = strtotime("2020/06/10 23:00:02");

        $company_rate = $company_rates;
        // startMoney   --  起步价,startMin     --  起步时长,startMile    --  起步里程,moneyPerMile --  里程费,moneyPerMin  --  时长费,longMileJudgement -- 远途公里,longMileMoney	-- 远途费
        $config = array(
            'Normal' => array("startMoney" => $company_rate['StartFare'], "startMin" => $company_rate['Tokinaga'], "startMile" => $company_rate['StartMile'], "moneyPerMile" => $company_rate['MileageFee'], "moneyPerMin" => $company_rate['HowFee'], "longMileJudgement" => $company_rate['LongKilometers'], "longMileMoney" => $company_rate['Longfee']),  //平时
            'WeehoursTime' => array("start" => $company_rate['weehoursOn'], "end" => $company_rate['weehoursOff'], "startMoney" => $company_rate['WeehoursStartFare'], "startMin" => $company_rate['WeehoursTokinaga'], "startMile" => $company_rate['WeehoursStartMile'], "moneyPerMile" => $company_rate['WeehoursMileageFee'], "moneyPerMin" => $company_rate['WeehoursHowFee'], "longMileJudgement" => $company_rate['WeehoursLongKilometers'], "longMileMoney" => $company_rate['WeehoursLongLongfee']),  //凌晨
            'MorningPeak' => array("start" => $company_rate['MorningPeakTimeOn'], "end" => $company_rate['MorningPeakTimeOff'], "startMoney" => $company_rate['MorningStartFare'], "startMin" => $company_rate['Tokinaga'], "startMile" => $company_rate['MorningStartMile'], "moneyPerMile" => $company_rate['MorningMileageFee'], "moneyPerMin" => $company_rate['MorningHowFee'], "longMileJudgement" => $company_rate['MorningLongKilometers'], "longMileMoney" => $company_rate['MorningLongLongfee']),  //早高峰 - 开始时间
            'AfterNoonPeak' => array("start" => $company_rate['EveningPeakTimeOn'], "end" => $company_rate['EveningPeakTimeOff'], "startMoney" => $company_rate['EveningStartFare'], "startMin" => $company_rate['EveningTokinaga'], "startMile" => $company_rate['EveningStartMile'], "moneyPerMile" => $company_rate['EveningMileageFee'], "moneyPerMin" => $company_rate['EveningHowFee'], "longMileJudgement" => $company_rate['EveningLongKilometers'], "longMileMoney" => $company_rate['EveningLongLongfee']),  //晚高峰 - 开始时间
            'EveningPeak' => array("start" => $company_rate['UsuallyLateNightOn'], "end" => $company_rate['UsuallyLateNightOff'], "startMoney" => $company_rate['NightStartFare'], "startMin" => $company_rate['NightStartingTokinaga'], "startMile" => $company_rate['NightStartMile'], "moneyPerMile" => $company_rate['NightMileageFee'], "moneyPerMin" => $company_rate['NightHowFee'], "longMileJudgement" => $company_rate['NightLongKilometers'], "longMileMoney" => $company_rate['NightLongfee']),  //深夜 - 开始时间
        );
        $configWeek = array(
            'Normal' => array("startMoney" => $company_rate['HolidaysStartFare'], "startMin" => $company_rate['HolidaysStartingTokinaga'], "startMile" => $company_rate['HolidaysStartMile'], "moneyPerMile" => $company_rate['HolidaysMileageFee'], "moneyPerMin" => $company_rate['HolidaysHowFee'], "longMileJudgement" => $company_rate['HolidaysStartingKilometre'], "longMileMoney" => $company_rate['HolidaysStartingLongfee']),  //平时
            'WeehoursTime' => array("start" => $company_rate['HolidaysWeeOn'], "end" => $company_rate['HolidaysWeeOff'], "startMoney" => $company_rate['HolidaysWeehoursStartFare'], "startMin" => $company_rate['HolidaysWeehoursStartingTokinaga'], "startMile" => $company_rate['HolidaysWeehoursStartMile'], "moneyPerMile" => $company_rate['HolidaysWeehoursMileageFee'], "moneyPerMin" => $company_rate['HolidaysWeehoursHowFee'], "longMileJudgement" => $company_rate['HolidaysWeehoursLongKilometers'], "longMileMoney" => $company_rate['HolidaysWeehoursLongfee']),  //凌晨
            'MorningPeak' => array("start" => $company_rate['HolidaysMorningOn'], "end" => $company_rate['HolidaysMorningOff'], "startMoney" => $company_rate['HolidaysMorningStartFare'], "startMin" => $company_rate['HolidaysMorningStartingTokinaga'], "startMile" => $company_rate['HolidaysMorningStartMile'], "moneyPerMile" => $company_rate['HolidaysMorningMileageFee'], "moneyPerMin" => $company_rate['HolidaysMorningHowFee'], "longMileJudgement" => $company_rate['HolidaysMorningLongKilometers'], "longMileMoney" => $company_rate['HolidaysMorningLongfee']),  //早高峰 - 开始时间
            'AfterNoonPeak' => array("start" => $company_rate['HolidaysEveningOn'], "end" => $company_rate['HolidaysEveningOff'], "startMoney" => $company_rate['HolidaysEveningStartFare'], "startMin" => $company_rate['HolidaysEveningStartingTokinaga'], "startMile" => $company_rate['HolidaysEveningStartMile'], "moneyPerMile" => $company_rate['HolidaysEveningMileageFee'], "moneyPerMin" => $company_rate['HolidaysEveningHowFee'], "longMileJudgement" => $company_rate['HolidaysEveningLongKilometers'], "longMileMoney" => $company_rate['HolidaysEveningLongfee']),  //晚高峰 - 开始时间
            'EveningPeak' => array("start" => $company_rate['HolidaysLateNightOn'], "end" => $company_rate['HolidaysLateNightOff'], "startMoney" => $company_rate['HolidaysNightStartFare'], "startMin" => $company_rate['HolidaysNightStartingTokinaga'], "startMile" => $company_rate['HolidaysNightStartMile'], "moneyPerMile" => $company_rate['HolidaysNightMileageFee'], "moneyPerMin" => $company_rate['HolidaysNightHowFee'], "longMileJudgement" => $company_rate['HolidaysNightLongKilometers'], "longMileMoney" => $company_rate['HolidaysNightLongfee']),  //深夜 - 开始时间
        );
        //endregion
        $start_time_date = strtotime("midnight", $start_time);//订单起始日期
        $end_time_date = strtotime("-1 second", strtotime("tomorrow", strtotime("midnight", $end_time)));//订单终止日期
        //region 计算第一天的订单时长
        $dates = array(
            $this->getFormatDateWithoutTime($start_time_date) => array(
                "day" => $this->getFormatDateWithoutTime($start_time),
                "dayStart" => $this->getFormatDateWithoutDate($start_time),
                "dayEnd" => $this->getFormatDateWithoutDate(strtotime("tomorrow", $start_time_date) > $end_time ? $end_time : strtotime("-1 second", strtotime("tomorrow", $start_time_date)))
            )
        );
        //endregion
        //region 将订单按天进行计算
        $tempDate = strtotime("tomorrow", $start_time_date);
        while ($tempDate < strtotime("tomorrow", $end_time_date)) {
            $dates[$this->getFormatDateWithoutTime($tempDate)] = array(
                "day" => $this->getFormatDateWithoutTime($tempDate),
                "dayStart" => $this->getFormatDateWithoutDate($tempDate),
                "dayEnd" => $this->getFormatDateWithoutDate(strtotime("midnight", strtotime("tomorrow", $tempDate)) > $end_time ? $end_time : strtotime("-1 second", strtotime("tomorrow", $tempDate)))
            );
            $tempDate = strtotime("tomorrow", $tempDate);
        }
        //endregion
//        echo "订单开始时间:", $this->getFormatDate($start_time), "<br>";
//        echo "订单结束时间:", $this->getFormatDate($end_time), "<br>";
//        echo "订单开始日期:", $this->getFormatDateWithoutTime($start_time_date), "<br>";
//        echo "订单结束日期:", $this->getFormatDateWithoutTime($end_time_date), "<br>";
        foreach ($dates as $key => $value) {
            //todo 根据$key(标准化日期，格式为2019-05-01),进行判断是否为节假日，决定当天是否使用节假日计价规则
            $daycode = Db::name('calendar')->where(['date' => $key])->value('daycode');
            if ($daycode == 1 || $daycode == 2) {
                //若为节假日
                $dates[$key]["timeSplice"] = $this->spliceDate($value, $configWeek);
            } else {
                $dates[$key]["timeSplice"] = $this->spliceDate($value, $config);
            }
        }
        return $dates;
    }

    //将时间戳格式化为日期(例:2019-01-05 23:15:00)
    private function getFormatDate($data)
    {
        return date('Y-m-d H:i:s', $data);
    }

    //将时间戳转化为日期(例:2019-01-05)
    private function getFormatDateWithoutTime($data)
    {
        return date('Y-m-d', $data);
    }

    //获取时间戳的时间部分(例:23:05:00)
    private function getFormatDateWithoutDate($data)
    {
        return date('H:i:s', $data);
    }

    //将每天的日期进行相关分段
    private function spliceDate($day, $specialTimeConfig)
    {
        $day["timeSplice"] = array();
        $startTime = $day["dayStart"];
        $endTime = $day["dayEnd"];
//        $startTime="09:30";
//        $endTime="09:35";

        foreach ($specialTimeConfig as $key => $config) {
            if ($key != "Normal") {
                if (strtotime($day["day"] . " " . $startTime) < strtotime($day["day"] . " " . $config["start"])) {
                    // region 起点在时间范围之前
                    if (strtotime($day["day"] . " " . $endTime) < strtotime($day["day"] . " " . $config["start"])) {
                        //终点在时间范围之前=>该笔订单不包含当前时间范围的内容;不进行当前时间段的计算,并标记为当天计算结束，因开始时间及结束时间皆在该时间段之前，因此之后的时间段无需进行计算
                        array_push($day["timeSplice"], array("startTime" => strtotime($day["day"] . " " . $startTime), "endTime" => strtotime($day["day"] . " " . $endTime), "type" => "Normal", "moneyRule" => $specialTimeConfig["Normal"]));
                        break;
                    } else if (strtotime($day["day"] . " " . $endTime) < strtotime($day["day"] . " " . $config["end"])) {
                        //终点在时间范围之中=》该笔订单分为2部分，时间范围前、时间范围内;将时间范围前部分，计入普通计价规则、将时间范围内部分，计入该特殊计价规则,并标记为当天计算结束，因结束时间皆该时间段结束之前，因此之后的时间段无需进行计算
                        //region 将时间范围前部分,计入普通计价规则
                        array_push($day["timeSplice"], array("startTime" => strtotime($day["day"] . " " . $startTime), "endTime" => strtotime($day["day"] . " " . $config["start"]), "type" => "Normal", "moneyRule" => $specialTimeConfig["Normal"]));
                        //endregion
                        //region 将时间范围内部分，计入该特殊计价规则
                        array_push($day["timeSplice"], array("startTime" => strtotime($day["day"] . " " . $config["start"]), "endTime" => strtotime($day["day"] . " " . $endTime), "type" => $key, "moneyRule" => $config));
                        //endregion
                        break;
                    } else {
                        //终点在时间范围之后=》该笔订单分为3部分,时间范围前、时间范围中、时间范围后;将时间范围前部分，计入普通计价规则、将时间范围内部分，计入该特殊计价规则、将开始时间重置为当前时间范围的结束时间用于下一步计算
                        //region 将时间范围前部分,计入普通计价规则
                        array_push($day["timeSplice"], array("startTime" => strtotime($day["day"] . " " . $startTime), "endTime" => strtotime($day["day"] . " " . $config["start"]), "type" => "Normal", "moneyRule" => $specialTimeConfig["Normal"]));
                        //endregion
                        //region 将时间范围内部分，计入该特殊计价规则
                        array_push($day["timeSplice"], array("startTime" => strtotime($day["day"] . " " . $config["start"]), "endTime" => strtotime($day["day"] . " " . $config["end"]), "type" => $key, "moneyRule" => $config));
                        //endregion
                        //region 将开始时间重置为当前时间范围的结束时间
                        $startTime = $config["end"];
                        //endregion
                    }
                    //endregion
                } else if (strtotime($day["day"] . " " . $startTime) < strtotime($day["day"] . " " . $config["end"])) {
                    //region 起点在时间范围之中
                    if (strtotime($day["day"] . " " . $endTime) < strtotime($day["day"] . " " . $config["end"])) {
                        //终点在时间范围之中=》该笔订单在此时间范围内;将时间范围内部分，计入该特殊计价规则,并标记为当天计算结束，因结束时间皆该时间段结束之前，因此之后的时间段无需进行计算
                        //region 将时间范围内部分，计入该特殊计价规则
                        array_push($day["timeSplice"], array("startTime" => strtotime($day["day"] . " " . $startTime), "endTime" => strtotime($day["day"] . " " . $endTime), "type" => $key, "moneyRule" => $config));
                        //endregion
                        break;
                    } else {
                        //终点在时间范围之后=》该笔订单分为2部分,时间范围中、时间范围后;将时间范围内部分，计入该特殊计价规则、将开始时间重置为当前时间范围的结束时间用于下一步计算
                        //region 将时间范围内部分，计入该特殊计价规则
                        array_push($day["timeSplice"], array("startTime" => strtotime($day["day"] . " " . $startTime), "endTime" => strtotime($day["day"] . " " . $config["end"]), "type" => $key, "moneyRule" => $config));
                        //endregion
                        //region 将开始时间重置为当前时间范围的结束时间
                        $startTime = $config["end"];
                        //endregion
                    }
                    //endregion
                } else {
                    //起点在结束时间之后=》该笔订单不包含当前时间范围的内容;不进行当前时间段的计算
                }
            }
        }
        return $day["timeSplice"];

    }

    //计算函数
    public function calculationFunction($Mileage, $total_time, $result, $money, $moneyRule)
    {
        //每一段的 路程和时长
        $distance = sprintf("%.2f", ($result['tracks'][0]['distance'] / 1000));
        $tokinaga = sprintf("%.2f", ($result['tracks'][0]['time'] / 1000));


        return $money;
    }

    //查询预约单
    public function QueryOrder()
    {
        if (input('?order_id')) {
            $params = [
                "o.id" => input('order_id')
            ];

            $order = Db::name('order')->alias('o')->field('o.id,o.origin,o.Destination,o.DepartTime,o.business_id,b.business_name,o.DepLongitude,o.DepLatitude,o.DestLongitude,o.DestLatitude,o.user_phone as user_phone,u.nickname as user_name,u.star,o.user_id,o.status,o.classification,u.portrait
            ,o.company_id,o.conducteur_virtual,o.user_virtual,t.title as business_type_name')
                ->join('mx_business b', 'b.id = o.business_id', 'left')
                ->join('mx_business_type t', 't.id = o.business_type_id', 'left')
                ->join('mx_user u', 'u.id = o.user_id')
                ->where($params)->find();
            if(empty($order['business_type_name'])){
                $order['business_type_name'] = '' ;
            }
            $order['query_time'] = time();

            //距离
            $arrive_time = Db::name('order_history')->where(['order_id' => $order['id']])->value('arrive_time');
            if (!empty($arrive_time)) {
                $order['arrive_time'] = $arrive_time;
            } else {
                $order['arrive_time'] = 0;
            }
            $data = array($order);
            //返回预约延长时间
            $restimatedDelayTime = Db::name('company')->where(['id'=>$order['company_id']])->value('restimatedDelayTime') ;
            if(empty($restimatedDelayTime)){
                $restimatedDelayTime = 0 ;
            }

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data,
                "restimatedDelayTime" => $restimatedDelayTime,
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //抢订单
    public function GrabOrder()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id", "conducteur_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $order = -1;
        $vehicle_id = 0 ;
        $data = [];
        if (!empty(input('conducteur_id'))) {

            //判断一下，当前订单是否被抢了
            $orders = Db::name('order')->field('id')->where(['id' => input('order_id')])->where('conducteur_id', 'neq', 0)->find();
            if (!empty($orders)) {
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '抢单失败'
                ];
            }
            //订单状态发生改变，证明已经抢了
            $order_q = Db::name('order')->field('id,status,mtorderid,user_id')->where(['id' => input('order_id')])->find();
            if ($order_q['status'] != 1) {
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '订单已被抢，抢单失败'
                ];
            }
            //美团订单回调一次，就不能抢了
            if (!empty($order_q['mtorderid'])) {
                $user_id = $order_q['user_id'] ;  //用户id
                $order_m =  Db::name('order')->field('id')->where(['user_id' => $user_id,'is_type' =>1,'status'=>12 ])->find();
                if(!empty($order_m)){  //证明其他的美团订单已经被抢了
                    return [
                        'code' => APICODE_ERROR,
                        'msg' => '订单已被抢，抢单失败'
                    ];
                }
            }


            //已经抢到预约单小于一个小时
            //获取现在订单的订单预计出发时间
            $DepartTime = Db::name('order')->where(['id' => input('order_id')])->value('DepartTime');
            $times = 1000 * 1000 * 60;

            $order_qd = Db::name('order')->field('DepartTime')->where(['conducteur_id' => input('conducteur_id')])->where(['classification' => '预约'])->where('status', 'eq', 2)->select();
            foreach ($order_qd as $key => $value) {
                $DepartTimes = (int)$DepartTime - (int)$times;         //后一个小时
                $DepartTimes_q = $value['DepartTime'] - (int)$times; //前一个小时

                if ($DepartTimes < $value['DepartTime']) {
                    return [
                        'code' => APICODE_ERROR,
                        'msg' => '该时段您已有预约行程'
                    ];
                }
                if ($DepartTimes_q < $DepartTime) {
                    return [
                        'code' => APICODE_ERROR,
                        'msg' => '该时段您已有预约行程'
                    ];
                }
            }

            //将司机id放到订单里面
            $ini['id'] = (int)input('order_id');
            $ini['conducteur_id'] = (int)input('conducteur_id');
            $ini['is_grab'] = 1;
            $ini['status'] = 12;

            //根据司机id存四个值
            $conducteur = Db::name('conducteur')->field('id,key,service,terimnal,trace,DriverName,DriverPhone,company_id')->where(['id' => (int)input('conducteur_id')])->find();
            $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => $conducteur['id']])->value('vehicle_id');  //车辆id
            $Gps_number = Db::name('vehicle')->where(['id' => $vehicle_id])->value('Gps_number');
            $ini['gps_number'] = $Gps_number;

            $ini['key'] = $conducteur['key'];
            $ini['service'] = $conducteur['service'];
            $ini['terimnal'] = $conducteur['terimnal'];
            $ini['trace'] = $conducteur['trace'];

            $ini['conducteur_name'] = $conducteur['DriverName'];
            $ini['conducteur_phone'] = $conducteur['DriverPhone'];
            $ini['company_id'] = $conducteur['company_id'];
            $order = Db::name('order')->update($ini);

            $order_d = Db::name('order')->alias('o')->field('o.id,o.origin,o.Destination,o.DepartTime,o.business_id,o.business_type_id,o.conducteur_id,b.business_name,o.DepLongitude,o.DepLatitude,o.DestLongitude,o.DestLatitude,u.PassengerPhone as user_phone,u.nickname as user_name,u.star,o.user_id,o.status,v.VehicleNo,o.classification')
                ->join('mx_business b', 'b.id = o.business_id', 'left')
                ->join('mx_user u', 'u.id = o.user_id')
                ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
                ->join('mx_vehicle_binding vb', 'vb.conducteur_id = c.id', 'left')
                ->join('mx_vehicle v', 'v.id = vb.vehicle_id', 'left')
                ->where(['o.id' => input('order_id')])->find();

            $arrive_time = Db::name('order_history')->where(['order_id' => $order_d['id']])->value('arrive_time');
            if (!empty($arrive_time)) {
                $order_d['arrive_time'] = $arrive_time;
            } else {
                $order_d['arrive_time'] = 0;
            }
        }
        $data = array($order_d);

        if ($order > 0) {
            //如果是美团订单，增加回调信息
            $orders = Db::name('order')->field('id,mtorderid,user_phone')->where(['id' => input('order_id')])->find();
            if (!empty($orders['mtorderid'])) {
                //虚拟号处理
//                $vritualController = new \app\api\controller\Vritualnumber() ;
//                $result_vritual = $vritualController->getPhoneNumberByOrderId(input('order_id')) ;

                $vritual['id'] = input('order_id') ;
                $vritual['conducteur_virtual'] = $conducteur['DriverPhone'];//$result_vritual['data']['conducteur_phone'] ;
                $vritual['user_virtual'] = $orders['user_phone'] ;//$result_vritual['data']['user_phone'] ;
//                $vritual['user_phone'] = $result_vritual['data']['user_phone'] ;
                Db::name('order')->update($vritual) ;

                $orderss = Db::name('order')->field('id,conducteur_id,conducteur_virtual,partnerCarTypeId,status,orders_from,mtorderid')->where(['id' => input('order_id')])->find();

                $param = [];
                $tatol = [];
                $rates = [];
                $this->partner_post(input('order_id'), $param, 'CONFIRM', $orderss, "0", $tatol, $rates,0);
            }
            //存一下，抢单时间，和计算时间冲突
            $inii['order_id'] = input('order_id');
            $inii['grabsingle_time'] = time() * 1000;
            Db::name('order_grabsingle')->insert($inii);

            $orderInfo = Db::name('order')->where(['id' => input('order_id')])->find();
            $opneid = Db::name('user')->where(['id' => $orderInfo['user_id']])->value('openid');
            if (!empty($opneid)) {
                $index = new Index();
                //取用户的openid
                $index->sendOrderStart($opneid, $conducteur['DriverName'], $order_d['VehicleNo'], $orderInfo['origin'], $conducteur["DriverPhone"], "请及时与司机联系确认出发时间");
            }
            //判断一下是否是出租车
            if($order_d['classification'] == '出租车'){
                $VehicleNo = Db::name('vehicle')->where(['id' => $vehicle_id])->value('VehicleNo');
                //分公司电话
                $phone = Db::name('company')->where(['id'=>$order_d['company_id']])->value('phone') ;
                $orders_id = $order_d['id'] ;
                $message = "接单司机: ".mb_substr($conducteur['DriverName'],0,1,"utf-8")."师傅\n"
                           ."车牌号: ".$VehicleNo."\n"
                           ."司机电话: ".$conducteur['DriverPhone']."\n"
                           ."出发位置: ".$order_d['origin']."附近\n"
                           ."\n"
                           ."临时有事儿不走了:\n"
                           ." <a href='https://php.51jjcx.com/home/demo/UserCancels/order_id/$orders_id'>【点击取消】</a>\n"
                           ."联系不上司机了/投诉建议微信:\n"
                           ."电话: ".$phone."\n";
//                           ."微信: 1932991034\n";

                $this->newsService($order_d,$message) ;
            }
            //发推送
            $this->appointmentByCompany("预约单来了",$orderInfo['company_id'],input('order_id'),2,$order_d['business_id'],$order_d['business_type_id'],$order_d['conducteur_id']);
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '抢单成功',
                'data' => $data
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '抢单失败'
            ];
        }
    }

    public function newsService($order_s,$message){
//        $file = fopen('./log.txt', 'a+');
//        fwrite($file, "-------------------出租车抢单进来了--------------------"."\r\n");
        //获取用户的公众号openid
        $bjnews_openid = Db::name('user')->where(['id'=>$order_s['user_id']])->value('bjnews_openid') ;
        $w = new Wechat("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449");
        $res = $w->sendServiceText($bjnews_openid,$message);
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
        $res = $this->request_posts($url, $params, $header);
        $res_arr = json_decode($res, true);
    }
    // 极光推送提交
    function request_posts($url = "", $param = "", $header = "")
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
    //查询所有预约单
    public function QueryReservation()
    {
        if (input('?conducteur_id')) {
            $params = [
                "o.conducteur_id" => input('conducteur_id')
            ];
            $time = strtotime(date("Y-m-d",strtotime("-1 day"))) ;   //昨天时间
            //根据司机id获取城市
            $city_id = Db::name('conducteur')->where(['id' => input('conducteur_id')])->value('city_id');
            $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => input('conducteur_id') ])->value('vehicle_id');
            $vehicle = Db::name('vehicle')->where(['id' => $vehicle_id ])->find() ;

            $data = Db::name('order')->alias('o')->field('o.id,o.origin,o.Destination,o.DepartTime,o.business_id,b.business_name,o.DepLongitude,o.DepLatitude,o.DestLongitude,o.DestLatitude
            ,u.PassengerPhone as user_phone,o.company_id,u.PassengerName as user_name,u.star,o.user_id,o.status,o.classification')
                ->join('mx_business b', 'b.id = o.business_id', 'left')
                ->join('mx_user u', 'u.id = o.user_id','left')
//                ->where($params)
                ->where(['o.classification' => '预约'])//必须是预约
                ->where(['o.city_id' => $city_id])//必须是司机的城市
                ->where('o.is_grab', 'eq', 0)//必须是没有抢过的
                ->where('o.status', 'in', "1,2")
                ->where(['o.business_id'=>$vehicle['business_id']])
                ->where(['o.business_type_id'=>$vehicle['businesstype_id']])
                ->where('o.DepartTime','gt',$time*1000)
                ->order('o.id desc')
                ->select();
            foreach ($data as $key => $value) {
                $arrive_time = Db::name('order_history')->where(['order_id' => $data['id']])->value('arrive_time');
                if (!empty($arrive_time)) {
                    $data[$key]['arrive_time'] = $arrive_time;
                } else {
                    $data[$key]['arrive_time'] = 0;
                }
                //返回预约延长时间
                $restimatedDelayTime = Db::name('company')->where(['id'=>$value['company_id']])->value('restimatedDelayTime') ;
                if (!empty($restimatedDelayTime)) {
                    $data[$key]['restimatedDelayTime'] = $restimatedDelayTime;
                } else {
                    $data[$key]['restimatedDelayTime'] = 0;
                }
            }

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data,
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    public function QueryGWReservation()
    {
        if (input('?conducteur_id')) {
            $params = [
                "o.conducteur_id" => input('conducteur_id')
            ];

            //根据司机id获取城市
            $city_id = Db::name('conducteur')->where(['id' => input('conducteur_id')])->value('city_id');
            $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => input('conducteur_id') ])->value('vehicle_id');
            $vehicle = Db::name('vehicle')->where(['id' => $vehicle_id ])->find() ;

            $data = Db::name('order')->alias('o')->field('o.id,o.origin,o.Destination,o.DepartTime,o.business_id,b.business_name,o.DepLongitude,o.DepLatitude,o.DestLongitude,o.DestLatitude,u.PassengerPhone as user_phone,u.PassengerName as user_name,u.star,o.user_id,o.status,o.classification')
                ->join('mx_business b', 'b.id = o.business_id', 'left')
                ->join('mx_user u', 'u.id = o.user_id','left')
//                ->where($params)
                ->where(['o.classification' => '公务车'])//必须是预约
                ->where(['o.city_id' => $city_id])//必须是司机的城市
                ->where('o.is_grab', 'eq', 0)//必须是没有抢过的
                ->where('o.status', 'in', "1,2")
                ->select();

            foreach ($data as $key => $value) {
                $arrive_time = Db::name('order_history')->where(['order_id' => $data['id']])->value('arrive_time');
                if (!empty($arrive_time)) {
                    $data[$key]['arrive_time'] = $arrive_time;
                } else {
                    $data[$key]['arrive_time'] = 0;
                }
//                $data[$key]['query_time'] = time() ;
            }

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
    //查询所有新的预约单(三种)
    public function QueryNewReservation()
    {
        if (input('?conducteur_id')) {
            $params = [
                "o.conducteur_id" => input('conducteur_id')
            ];

            //根据司机id获取城市
            $city_id = Db::name('conducteur')->where(['id' => input('conducteur_id')])->value('city_id');
            $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => input('conducteur_id') ])->value('vehicle_id');
            $vehicle = Db::name('vehicle')->where(['id' => $vehicle_id ])->find() ;

            $where = [] ; $where1 = [] ; $where2 = [] ; $where3 = [] ; $where4 = [] ;$where5 = [] ; $data = [] ;
            //获取司机身份
            $identity = Db::name('conducteur')->where(['id' => input('conducteur_id')])->value('identity');
            if(!empty($identity)){              //司机身份
                //专车、代驾、公务车
                $car = json_decode($identity ,true) ;
                $type = 0 ;                         //标志位
                foreach ($car as $key=>$value){
                    if($value['business_id'] == 2 ){    //专车
                        $type = 1 ;
                    }
                    if($key == 0){                      //专车
                        $where['o.business_id'] =  $value['business_id'] ;
                        $where1['o.business_type_id'] = $value['business_type_id'] ;
                    }else if($key == 1){               //代驾
                        $where2['o.business_id'] =  $value['business_id'] ;
                        $where3['o.business_type_id'] = $value['business_type_id'] ;
                    }
                }
                //公务车
                if($type == 1){
//                    $where4['o.business_id'] =  $value['business_id'] ;
//                    $where5['o.business_type_id'] = $value['business_type_id'] ;
                }
                $data = Db::name('order')->alias('o')->field('o.id,o.origin,o.Destination,o.DepartTime,o.business_id,b.business_name,o.DepLongitude,o.DepLatitude,o.DestLongitude,o.DestLatitude,u.PassengerPhone as user_phone,u.PassengerName as user_name,u.star,o.user_id,o.status,o.classification')
                    ->join('mx_business b', 'b.id = o.business_id', 'left')
                    ->join('mx_user u', 'u.id = o.user_id','left')
                    ->where(['o.city_id' => $city_id])//必须是司机的城市
                    ->where('o.is_grab', 'eq', 0)//必须是没有抢过的
                    ->where('o.status', 'in', "1,2")
                    ->where($where)
                    ->where($where1)
                    ->where($where2)
                    ->where($where3)
                    ->select();
            }else {                             //车辆身份
                $where['o.business_id'] =  $vehicle['business_id'] ;
                $where1['o.business_type_id'] = $vehicle['businesstype_id'] ;

                $data = Db::name('order')->alias('o')->field('o.id,o.origin,o.Destination,o.DepartTime,o.business_id,b.business_name,o.DepLongitude,o.DepLatitude,o.DestLongitude,o.DestLatitude,u.PassengerPhone as user_phone,u.PassengerName as user_name,u.star,o.user_id,o.status,o.classification')
                    ->join('mx_business b', 'b.id = o.business_id', 'left')
                    ->join('mx_user u', 'u.id = o.user_id','left')
                    ->where(['o.city_id' => $city_id])//必须是司机的城市
                    ->where('o.is_grab', 'eq', 0)//必须是没有抢过的
                    ->where('o.status', 'in', "1,2")
                    ->where($where)
                    ->where($where1)
                    ->select();
            }

            foreach ($data as $key => $value) {
                $arrive_time = Db::name('order_history')->where(['order_id' => $data['id']])->value('arrive_time');
                if (!empty($arrive_time)) {
                    $data[$key]['arrive_time'] = $arrive_time;
                } else {
                    $data[$key]['arrive_time'] = 0;
                }
//                $data[$key]['query_time'] = time() ;
            }

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

    //查询所有代驾预约单
    public function QueryDididaijiadriver()
    {
        if (input('?conducteur_id')) {
            $params = [
                "o.conducteur_id" => input('conducteur_id')
            ];

            //根据司机id获取城市
            $city_id = Db::name('conducteur')->where(['id' => input('conducteur_id')])->value('city_id');

            $data = Db::name('order')->alias('o')->field('o.id,o.origin,o.Destination,o.DepartTime,o.business_id,b.business_name,o.DepLongitude,o.DepLatitude,o.DestLongitude,o.DestLatitude,u.PassengerPhone as user_phone,u.PassengerName as user_name,u.star,o.user_id,o.status,o.classification')
                ->join('mx_business b', 'b.id = o.business_id', 'left')
                ->join('mx_user u', 'u.id = o.user_id')
//                ->where($params)
                ->where(['o.classification' => '代驾'])//必须是预约
                ->where(['o.city_id' => $city_id])//必须是司机的城市
                ->where('o.is_grab', 'eq', 0)//必须是没有抢过的
                ->where('o.status', 'in', "1,2")
                ->select();

            foreach ($data as $key => $value) {
                $arrive_time = Db::name('order_history')->where(['order_id' => $data['id']])->value('arrive_time');
                if (!empty($arrive_time)) {
                    $data[$key]['arrive_time'] = $arrive_time;
                } else {
                    $data[$key]['arrive_time'] = 0;
                }
            }

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
//查询所有预约单
    public function QueryTaxiReservation()
    {
        if (input('?conducteur_id')) {
            $params = [
                "o.conducteur_id" => input('conducteur_id')
            ];

            //根据司机id获取城市
            $city_id = Db::name('conducteur')->where(['id' => input('conducteur_id')])->value('city_id');
            $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => input('conducteur_id') ])->value('vehicle_id');
            $vehicle = Db::name('vehicle')->where(['id' => $vehicle_id ])->find() ;
//            var_dump($vehicle);
            $data = Db::name('order')->alias('o')->field('o.id,o.origin,o.Destination,o.DepartTime,o.business_id,b.business_name,o.DepLongitude,o.DepLatitude,o.DestLongitude,o.DestLatitude,u.PassengerPhone as user_phone,u.PassengerName as user_name,u.star,o.user_id,o.status,o.classification')
                ->join('mx_business b', 'b.id = o.business_id', 'left')
                ->join('mx_user u', 'u.id = o.user_id')
//                ->where($params)
                ->where(['o.classification' => '出租车'])//必须是预约
                ->where(['o.city_id' => $city_id])//必须是司机的城市
                ->where('o.is_grab', 'eq', 0)//必须是没有抢过的
                ->where('o.status', 'in', "1,2")
                ->where(['o.business_id'=>$vehicle['business_id']])
                ->where(['o.business_type_id'=>$vehicle['businesstype_id']])
                ->order('o.id desc')
                ->select();
//            var_dump($data);
            foreach ($data as $key => $value) {
                $arrive_time = Db::name('order_history')->where(['order_id' => $data['id']])->value('arrive_time');
                if (!empty($arrive_time)) {
                    $data[$key]['arrive_time'] = $arrive_time;
                } else {
                    $data[$key]['arrive_time'] = 0;
                }
            }

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
    //附加费
    public function surchargeOrder()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "surcharge" => input('?surcharge') ? input('surcharge') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id", "surcharge"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        //状态变成7
        $ini['id'] = input('order_id');
        $ini['status'] = 7;
        $ini['surcharge'] = input('surcharge');
        if(!empty(input('recognition_location'))){
            $ini['recognition_location'] = input('recognition_location') ;
        }
        $orderss = Db::name('order')->where(['id' =>input('order_id') ])->find();
        $total_prices = json_decode($orderss['total_price'] , true) ;
        //将金额进行修改
        $total_prices['money'] = sprintf("%.2f", ($total_prices['money'] + input('surcharge')))  ;
        $ini['total_price'] = json_encode($total_prices) ;
        Db::name('order')->update($ini);

        $orders = Db::name('order')->where(['id' =>input('order_id') ])->find();
        $opneid = Db::name('user')->where(['id' => $orders['user_id']])->value('openid');
        if (input('surcharge') != 0) {
            $order = Db::name('order')->where(['id' => input('order_id')])->setInc('money', input('surcharge'));

            if ($order) {
                if (!empty($opneid)) {
                    $index = new Index();
                    $order_id=input('order_id');
                    //取用户的openidß
                    $index->sendOrderWaitPay($opneid,  $orders["money"]+input('surcharge'), date('Y年m月d日 H:i', time()), $orders['origin'], $orders['Destination'],'司机已确认费用,点击去付款',"pages/fastCar/order?orderId=$order_id");
                }
                if (!empty($orders['mtorderid'])) {
                    $param = [];
                    $total_price = json_decode($orders['total_price'],true);
                    $rates = $orders['rates'];
                    $this->partner_post(input('order_id'), $param, 'WAIT_PAY', $orders, "0", $total_price, $rates,input('surcharge'));
                }
                return [
                    'code' => APICODE_SUCCESS,
                    'msg' => '附加成功'
                ];
            } else {
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '附加失败'
                ];
            }
        } else {
            if (!empty($opneid)) {
                $index = new Index();
                $order_id=input('order_id');
                //取用户的openidß
                $index->sendOrderWaitPay($opneid,  $orders["money"], date('Y年m月d日 H:i', time()), $orders['origin'], $orders['Destination'],'司机已确认费用,点击去付款',"pages/fastCar/order?orderId=$order_id");
            }
            if (!empty($orders['mtorderid'])) {
                $param = [];
                $total_price = json_decode($orders['total_price'],true);
                $rates = $orders['rates'];
                $this->partner_post(input('order_id'), $param, 'WAIT_PAY', $orders, "0", $total_price, $rates,input('surcharge'));
            }
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '附加成功'
            ];
        }
    }

    //协议分类
    public function ClassificationAgreement()
    {

        $agreement_classify = Db::name('agreement_classify')->select();

        return [
            'code' => APICODE_SUCCESS,
            'msg' => '创建成功',
            'data' => $agreement_classify
        ];
    }

    //协议列表
    public function AgreementList()
    {
        if (input('?agreement_classify_id')) {
            $params = [
                "agreement_classify_id" => input('agreement_classify_id')
            ];

            $data = db('agreement')->field('id,title')->where($params)->select();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "协议ID不能为空"
            ];
        }
    }

    //根据协议id查询详情
    public function getAgreement()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];

            $data = db('agreement')->where($params)->value('content');

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "协议ID不能为空"
            ];
        }
    }

    //（预约单）开始行程
    public function BeganTravel()
    {
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id'),
                'status' => 2
            ];

            //判断一下这个司机是否有已经出行的订单
            $orders = Db::name('order')->where(['conducteur_id' => input('conducteur_id')])->where('status', 'in', '2,3,4')->find();
            if (!empty($orders)) {
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '当前已有行程正在进行'
                ];
            }
            $order_s = Db::name('order')->where(['id' => input('order_id')])->find();
            if($order_s['status'] != 12){
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '状态不对，无法开始行程'
                ];
            }
            if(!empty(input('began_location'))){
                $params['began_location'] = input('began_location') ;
            }
            $params['travel_time'] = time() ;

            $data = Db::name('order')->update($params);

            $user_id = Db::name('order')->where(['id' => input('order_id')])->value('user_id');
            if($data > 0){
                //美团回调
                $order_m = Db::name('order')->where(['id' => input('order_id')])->find();
                if (!empty($order_m['mtorderid'])) {
                    $param = [];
                    $tatol = [];
                    $rates = [];
                    $this->partner_post(input('order_id'), $param, 'SET_OUT', $order_m, "0", $tatol, $rates,0);
                }
                //判断一下是否是出租车
                if($order_m['classification'] == '出租车'){
//                    $this->newsService($order_m,'司机已开始行程') ;
                }

                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "开始成功",
                    "user_id" => $user_id,
                ];
            }else{
                return [
                    "code" => APICODE_EMPTYDATA,
                    "msg" => "开始成功",
                    "user_id" => $user_id,
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //议价
    public function NegotiatingPrices(){
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "money" => input('?money') ? input('money') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id", "money"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $ini['id'] = input('order_id') ;
        $ini['money'] = input('money');
        $ini['fare'] = input('money');
        $ini['status'] = 7;
        $res = Db::name('order')->update($ini) ;

        if($res > 0){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'议价成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'议价失败'
            ];
        }
    }
}
