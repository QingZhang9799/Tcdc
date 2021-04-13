<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 19-2-26
 * Time: 上午10:53
 */

namespace app\user\controller;

use app\api\model\Conducteur;
use app\api\model\Company;
use app\backstage\controller\Gps;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Config;

class Carpool extends Base
{
    public function matchExpress()
    {
        $params = [
            "origin" => input('?origin') ? input('origin') : null,
            "destination" => input('?destination') ? input('destination') : null,
            "times" => input('?times') ? input('times') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["origin", "destination", "times"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $order = Db::name('order')->alias('o')
            ->field('o.id,l.origin,l.destination,l.price,o.seating_count,o.Ridership,o.DepLongitude,o.DepLatitude,c.DriverName,c.DriverPhone,c.grandet,v.VehicleNo,v.PlateColor,v.OwnerName,v.VehicleColor')
            ->join('mx_order_line l', 'l.order_id = o.id', 'inner')
            ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
            ->join('mx_vehicle_binding b', 'b.conducteur_id = c.id', 'inner')
            ->join('mx_vehicle v', 'v.id = b.vehicle_id', 'left')
            ->where(['l.origin' => input('origin')])
            ->where(['l.destination' => input('destination')])
            ->where('o.DepartTime', 'egt', input('times'));

        return ['code' => APICODE_SUCCESS, 'data' => $order];
    }

    //确认司机信息
    public function notarizeChauffeur()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "order_line_id" => input('?order_line_id') ? input('order_line_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id", "order_line_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $order = Db::name('order')->alias('o')
            ->field('o.id,l.origin,l.destination,l.price,o.seating_count,o.Ridership,o.DepLongitude,o.DepLatitude,c.DriverName,c.DriverPhone,c.grandet,v.VehicleNo,v.PlateColor,v.OwnerName,v.VehicleColor,v.Model,o.DepartTime,l.seat_details,v.seating as seatings,o.create_time,o.line_id')
            ->join('mx_order_line l', 'l.order_id = o.id', 'inner')
            ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
            ->join('mx_vehicle_binding b', 'b.conducteur_id = c.id', 'inner')
            ->join('mx_vehicle v', 'v.id = b.vehicle_id', 'left')
            ->where(['l.id' => input('order_line_id')])
            ->where(['o.id' => input('order_id')])
            ->find();

        //已占用座位
        //获取子单子-> 座位情况
        if ($order['seating_count'] == 4) {
            $orders = Db::name('order')->distinct(true)->field('seat,Ridership')->where(['order_id' => input('order_id')])->where('status', 'in', '7,12,3,4')->select();
        } else {
            $orders = Db::name('order')->field('seat,Ridership')->where(['order_id' => input('order_id')])->where('status', 'in', '7,12,3,4')->select();
        }

        $seatOccputed = array();
        $Ridership = 0;
        //拼接座位情况，去除重复
        foreach ($orders as $key => $value) {
            $seatOccputed = array_merge($seatOccputed, explode(",", $value['seat']));
            $Ridership += (int)$value['Ridership'];
        }

        $str = implode('', $seatOccputed); //合并数组
        $remaining_seats = $str;
        $order['remaining_seats'] = $remaining_seats;
        $order['seatOccputed'] = $seatOccputed;
        $order['Ridership'] = $Ridership;

        //获取折扣
        $discount = Db::name('line')->where(['id'=>$order['line_id']])->value('discount') ;
        $order['l_discount'] = $discount ;
        return ['code' => APICODE_SUCCESS, 'data' => $order];
    }

    function mbstringtoarray($str, $charset)
    {
        $strlen = mb_strlen($str);
        while ($strlen) {
            $array[] = mb_substr($str, 0, 1, $charset);
            $str = mb_substr($str, 1, $strlen, $charset);
            $strlen = mb_strlen($str);
        }
        return $array;
    }

    //确认付款
    public function ConfirmPayment()
    {
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "order_line_id" => input('?order_line_id') ? input('order_line_id') : null,
            "money" => input('?money') ? input('money') : null,
            "seat" => input('?seat') ? input('seat') : null,
            "require" => input('?require') ? input('require') : "",
            "user_id" => input('?user_id') ? input('user_id') : null,
            "description" => input('?description') ? input('description') : "",
            "enterprise_id" => input('?enterprise_id') ? input('enterprise_id') : null,
        ];

        //下单之前，判断主单是正常状态
        $status = Db::name('order')->where(['id' => input('order_id')])->value('status');
        if ($status == 5) {
            return [
                'code' => APICODE_ERROR,
                'msg' => '订单已取消，无法下单'
            ];
        }
        //下单之前,存在订单
        $orders_o = Db::name('order')->where(['order_id' => input('order_id')])->where(['user_id' => input('user_id')])->where(['status' => 7])->find();
        if (!empty($orders_o)) {
            return [
                'code' => APICODE_ERROR,
                'msg' => '您当前有未支付订单，请先支付'
            ];
        }
        //下单判断座位是否满足
        $seating_count = Db::name('order')->where(['id' => input('order_id') ])->value('seating_count') ;
        //判断一下，主单还有剩余座位
        $Ridership = Db::name('order')->where(['order_id'=>input('order_id')])->where('status','not in',5)->value('sum(Ridership)');
        $residue = $seating_count - $Ridership ;
        if($residue <= 0 ){
            return ["code" => APICODE_ERROR, "msg" => "座位已占用，请重新下单"];
        }

        //用户订单
        $orders = Db::name('order')->where(['id' => input('order_id')])->find();
        $conducteur = Db::name('conducteur')->where(['id' => $orders['conducteur_id']])->find();
        //获取车辆
        $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id' => $orders['conducteur_id']])->value('vehicle_id');
        $Gps_number = Db::name('vehicle')->where(['id' => $vehicle_id])->value('Gps_number');
        $user = Db::name('user')->where(['id' => input('user_id')])->find();
        //线路订单
        $order_line = Db::name('order_line')->where(['id' => input('order_line_id')])->find();

        $OrderId = 'SF' . $orders['city_id'] . '0' . date('Ymdhis') . rand(0000, 9999);

        $params['OrderId'] = $OrderId;
        $params['order_id'] = input('order_id');
        $params['order_line_id'] = input('order_line_id');
        $params['user_id'] = input('user_id');
        $params['conducteur_id'] = input('conducteur_id');
        $params['company_id'] = $orders['company_id'];
        $params['city_id'] = $orders['city_id'];
        $params['conducteur_id'] = $orders['conducteur_id'];
        $params['DepartTime'] = $orders['DepartTime'];
        $params['classification'] = '顺风车';

        $params['conducteur_phone'] = $conducteur['DriverPhone'];
        $params['conducteur_name'] = $conducteur['DriverName'];

        $params['user_phone'] = $user['PassengerPhone'];
        $params['user_name'] = $user['nickname'];

        $params['origin'] = $order_line['origin'];
        $params['Destination'] = $order_line['destination'];
        //经度和纬度
        $params['DepLongitude'] = $order_line['origin_longitude'];
        $params['DepLatitude'] = $order_line['origin_latitude'];
        $params['DestLongitude'] = $order_line['destination_longitude'];
        $params['DestLatitude'] = $order_line['destination_latitude'];
        $params['status'] = 7;
        $params['order_name'] = "预约订单(城际)";
        $params['money'] = input('money');
        $params['fare'] = input('money');
        $params['create_time'] = time();
        //座位数和座位详情
        $params['Ridership'] = input('seating_count');
        $params['seat'] = input('seat');
        $params['business_id'] = 4;
        $params['description'] = input('description');
        $params['gps_number'] = $Gps_number;
        //保存线路id
        $params['line_id'] = $orders['line_id'] ;

        $order_id = Db::name('order')->insertGetId($params);

        if ($order_id > 0) {

            return [
                'code' => APICODE_SUCCESS,
                'msg' => '创建成功',
                'money' => input('money'),
                'order_id' => $order_id,
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '创建失败'
            ];
        }
    }

        public function appointment($title, $uid, $message, $type)
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

    //根据城市获取线路的起点
    public function accordingOriginList()
    {
        if (input('?city_id')) {
            $params = [
                "id" => input('city_id')
            ];

            $data = Db::name('line')->alias('l')
                ->distinct(true)
                ->field('d.origin')
//                ->field('d.origin as origin,d.origin_longitude,d.origin_latitude,l.display,l.is_display')
                ->join('line_detail d', 'd.line_id = l.id', 'inner')
                ->where('l.state', 'eq', 0)
                ->where(['l.city_id' => input('city_id')])
                ->select();
            $ini = [];

            foreach ($data as $key => $value) {
                $lines = Db::name('line')->alias('l')->field('d.origin_longitude,d.origin_latitude,l.display,l.is_display')
                ->join('line_detail d', 'd.line_id = l.id', 'inner')->whereOr(['d.origin'=>$value['origin']])->find();
                $data[$key]['origin_longitude'] = $lines['origin_longitude'];
                $data[$key]['origin_latitude'] = $lines['origin_latitude'];
                $data[$key]['display'] = $lines['display'];
                if ($lines['is_display'] == 1) {
                    $display = explode(',', $lines['display']);
                    foreach ($display as $k => $v) {
                        $name = explode('-', $v);
                        foreach ($name as $kk => $vv) {
                            if ($kk == 0) {
                                $data[$key]['displays'][] = $name[$kk];
                            }
                        }
                    }
                } else {
                    $data[$key]['displays'] = [];
                }
            }

            return [
                "code" => APICODE_SUCCESS,
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

    //根据城市id和经纬度获取线路起点
    public function CitySeekOrigin()
    {
        $params = [
            "longitude" => input('?longitude') ? input('longitude') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "latitude" => input('?latitude') ? input('latitude') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", 'longitude', 'latitude'];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $data = Db::name('line')->alias('l')
            ->distinct(true)
            ->field('d.origin as origin,d.origin_longitude,d.origin_latitude,l.display,l.is_display,l.id as line_id')
            ->join('line_detail d', 'd.line_id = l.id', 'left')
            ->where('l.state', 'eq', 0)
            ->where(['l.city_id' => input('city_id')])
            ->where('state', 'eq', 0)
            ->select();

        $ini = [];
        if (!empty($data)) {
            $sum = 0;       //距离
            $index = 0;   //索引
            $line_id = 0; //主线路id
            $origin = "";
            $displays = [];
            //循环筛选为一条
            $distance = 0;

            foreach ($data as $key => $value) {

                $distance = $this->getDistance(input('longitude'), input('latitude'), $value['origin_longitude'], $value['origin_latitude']);
                //判断最小距离
                if ($sum == 0) {
                    $sum = $distance;
                    $lines = Db::name('line')->field('id,origin,origin_longitude,display,is_display')->where(['id' => $value['line_id']])->find();
                    if ($lines['is_display'] == 1) {
                        $display = explode(',', $lines['display']);
                        foreach ($display as $k => $v) {
                            $name = explode('-', $v);
                            $data[$key]['displays'][] = $name[0];
                        }
                    } else {
                        $data[$key]['displays'] = [];
                    }
                }
                if ($distance < $sum) {
                    $sum = $distance;
                    $index = $key;
                    $line_id = $value['line_id'];

                    $lines = Db::name('line')->field('id,origin,origin_longitude,display,is_display')->where(['id' => $line_id])->find();
                    if ($lines['is_display'] == 1) {
                        $display = explode(',', $lines['display']);
                        foreach ($display as $k => $v) {
                            $name = explode('-', $v);
                            $data[$key]['displays'][] = $name[0];
                        }
                    } else {
                        $data[$key]['displays'] = [];
                    }
                }
                $data[$key]['distance'] = $distance;
            }

            //$index ==最终筛选数字 ,获取主路线

            $ini['origin'] = $data[$index]['origin'];
            $ini['origin_longitude'] = $data[$index]['origin_longitude'];
            $ini['origin_latitude'] = $data[$index]['origin_latitude'];
            $ini['display'] = $data[$index]['display'];
            $ini['is_display'] = $data[$index]['is_display'];
            $ini['displays'] = $data[$index]['displays'];
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $ini
            ];
        } else {
            $ini['origin'] = "";
            $ini['origin_longitude'] = "";
            $ini['origin_latitude'] = "";
            $ini['display'] = "";
            $ini['is_display'] = "";
            $ini['displays'] = array();
            return [
                "code" => APICODE_SUCCESS,
                "data" => $ini,
            ];
        }
    }

    public function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        $EARTH_RADIUS = 6378.137;
        $radLat1 = $this->rad($lat1);
        $radLat2 = $this->rad($lat2);
        $a = $radLat1 - $radLat2;
        $b = $this->rad($lng1) - $this->rad($lng2);
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s = $s * $EARTH_RADIUS;
        if ($s < 1) {
            $s = round($s * 1000);
            // $s.='m';
        } else {
            $s = round($s, 6);
            $s = $s * 1000;
            // $s.='km';
        }
        return $s;
    }

    public function rad($d)
    {
        return $d * 3.1415926535898 / 180.0;
    }

    //根据城市获取线路起点获取终点
    public function destinationDestination()
    {
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "origin" => input('?origin') ? input('origin') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "origin"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $data = Db::name('line')->alias('l')
            ->distinct(true)
            ->field('d.destination as destination')
            ->join('line_detail d', 'd.line_id = l.id', 'left')
            ->where('l.state', 'eq', 0)
            ->where('d.origin', 'eq', input('origin'))
            ->select();

        return [
            "code" => APICODE_SUCCESS,
            "msg" => "查询成功",
            "data" => $data
        ];
    }

    //根据城市起点终点获取订单
    public function acquireOrder()
    {
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "origin" => input('?origin') ? input('origin') : null,
            "destination" => input('?destination') ? input('destination') : null,
            "time" => input('?time') ? input('time') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "origin", "destination", "time"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //匹配订单 起点必须相等,终点可以不等
        $data = [];
        $time = date('Y-m-d', input('time') / 1000);

        $start_day = strtotime($time . " 00:00:00");
//        $end_day = strtotime($time." 23:59:59");

        $order = Db::name('order')->alias('o')->join('mx_order_line l', 'l.order_id = o.id', 'inner')
            ->join('mx_conducteur c', 'c.id = o.conducteur_id', 'left')
            ->join('mx_vehicle_binding b', 'b.conducteur_id = c.id', 'left')
            ->join('mx_vehicle v', 'v.id = b.vehicle_id', 'left')
            ->distinct(true)
            ->field('o.id,l.origin,l.destination,l.price,v.VehicleNo,v.PlateColor,v.Model,o.seating_count,o.Ridership,o.DepartTime,c.grandet,o.id as order_id,l.id as order_line_id,o.conducteur_phone,l.seat_details,v.seating as seatings,o.line_id')
            ->where('o.status', 'eq', "12")
            ->where(['o.city_id' => input('city_id')])
            ->where(['l.origin'=>input('origin')])
            ->where(['l.destination'=>input('destination')])
//                                                             ->where('DepartTime','gt',(int)(input('time')/1000) )
            ->where('DepartTime', 'gt', (int)$start_day)//大于当天起始时间
//                                                             ->where('DepartTime','lt',(int)$end_day )           //小于当天结束时间
            ->select();
        //匹配起点和终点 ,移除多余的

        foreach ($order as $key => $value) {
            if ($value['seating_count'] == 4) {
                $orders = Db::name('order')->distinct(true)->field('seat,Ridership')->where(['order_id' => $value['id']])->where('status', 'in', '7,12')->select();
            } else {
                $orders = Db::name('order')->field('seat,Ridership')->where(['order_id' => $value['id']])->where('status', 'in', '4,7,12')->select();
            }

            $Ridership = 0;
            foreach ($orders as $k => $v) {
                $Ridership += (int)$v['Ridership'];
            }
            if ($value['origin'] != input('origin')) {
                unset($order[$key]);
            }
//            if( $value['destination'] != input('destination')){
//                unset($order[$key]) ;
//            }
            if ((int)$value['seating_count'] == $Ridership) {             //满座
                unset($order[$key]);
            }
        }
        $discount = 100 ;
        foreach ($order as $k => $v) {
            if( !empty($v['line_id']) ){
                $discount = Db::name('line')->where(['id'=>$v['line_id']])->value('discount') ;
            }

            if ($v['seating_count'] == 4) {
                $orders = Db::name('order')->distinct(true)->field('seat,Ridership')->where(['order_id' => $v['id']])->where('status', 'in', '7,12')->select();
            } else {
                $orders = Db::name('order')->field('seat,Ridership')->where(['order_id' => $v['id']])->where('status', 'in', '7,12')->select();
            }

            $Ridership = 0;
            foreach ($orders as $key => $value) {
                $Ridership += (int)$value['Ridership'];
            }

            $seat_details = json_decode($v['seat_details'], true);
            $Premium_price = 0;
            foreach ($seat_details as $kk => $vv) {
                foreach ($vv as $kkk => $vvv) {
                    $Premium_price += $vvv;
                }
            }

            $data[] = [
                "origin" => $v['origin'],
                "destination" => $v['destination'],
                "price" => $v['price'],
                "VehicleNo" => $v['VehicleNo'],
                "PlateColor" => $v['PlateColor'],
                "Model" => $v['Model'],
                "seating_count" => $v['seating_count'],
                "Ridership" => $Ridership,
                "DepartTime" => $v['DepartTime'],
                "grandet" => $v['grandet'],
                "order_id" => $v['order_id'],
                "order_line_id" => $v['order_line_id'],
                "conducteur_phone" => $v['conducteur_phone'],
                "Premium_price" => sprintf("%.2f", ($Premium_price)),
                "seatings" => $v['seatings'],
                "l_discount" => $discount,
            ];
        }

        return [
            "code" => APICODE_SUCCESS,
            "msg" => "查询成功",
            "data" => $data
        ];

    }

    //乘客已上车
    public function PassengerAboard()
    {
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id'),
                "status" => 4
            ];
            //判断一下，主订单是否是出行中
            $order_id = Db::name('order')->where(['id' => input('order_id')])->value('order_id');
            $orders = Db::name('order')->where(['id' => $order_id])->where(['status' => 4])->find();
            $gps = new Gps();
            $nowPosition = $gps->getCarsStatus($orders["gps_number"]);
            $nowPosition = $nowPosition["data"][0];
            if(!empty($nowPosition)){
                $params["DepLongitude"] = $nowPosition["lonc"];
                $params["DepLatitude"] = $nowPosition["latc"];
            }

            if (!empty($orders)) {
                $res = Db::name('order')->update($params);
                //上车存一个到达时间
                $ini['order_id'] = input('order_id') ;
                $ini['arrive_time'] = time() ;
                Db::name('order_history')->insert($ini);

                if ($res > 0) {
                    //推送司机
                    $order_info = Db::name('order')->where(['id' => input('order_id')])->find();
                    $this->appointment('乘客已上车', $order_info['conducteur_id'], $order_info['order_id'], 100);
                    return [
                        "code" => APICODE_SUCCESS,
                        "msg" => "乘客已上车"
                    ];
                } else {
                    return [
                        'code' => APICODE_ERROR,
                        'msg' => '上车失败'
                    ];
                }
            } else {
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '司机未点击开始行程'
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
    }

    //我已到达
    public function CzasNadszed()
    {
        if (input('?order_id')) {
            $params = [
                "id" => input('order_id'),
                "status" => 9
            ];

            $status = Db::name('order')->where(['id' => input('order_id')])->value('status');

            if ($status != 4) {
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "状态不对，无法到达目的地",
                ];
            }
            $order_info = Db::name('order')->where(['id' => input('order_id')])->find();
            $gps = new Gps();
            $nowPosition = $gps->getCarsStatus($order_info["gps_number"]);
            $nowPosition = $nowPosition["data"][0];
            if(!empty($nowPosition)){
                $params["DepLongitude"] = $nowPosition["lonc"];
                $params["DepLatitude"] = $nowPosition["latc"];
            }

            $arrive_time = Db::name('order_history')->where(['order_id' => input('order_id') ])->value('arrive_time') ;
            $locus = $this->gjByGps($order_info["gps_number"], $arrive_time, $order_info['end_time']);
            $tracks = json_encode($locus['tracks']);
            $params["tracks"] =$tracks;
            $data = db('order')->update($params);
            if ($data > 0) {
                $this->appointment('已有乘客已到达', $order_info['conducteur_id'], $order_info['order_id'], 101);
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "我已到达",
                ];
            } else {
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "到达失败",
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单ID不能为空"
            ];
        }
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
        $endTime = mb_substr($endTime, 0, 10);; //strtotime("2019/01/29 23:00:02");
        $startTime = gmdate("Y-m-d H:i:s", $startTime);
        $endTime = gmdate("Y-m-d H:i:s", $endTime);
        $token = $this->getAdminToken();
        $data = $this->request_post("http://www.gpsnow.net/position/queryHistory.do", array("carId" => $carId, "mapType" => "2", "startTime" => $startTime, "endTime" => $endTime, "token" => $token, "filter" => true));
        $data = json_decode($data, true);
        $res = $data["data"];
        return $res;
    }

    //协议分类
    public function ClassificationAgreement()
    {

        $agreement_classify = Db::name('agreement_classify')->select();
        foreach ($agreement_classify as $key=>$value){
            $agreement = Db::name('agreement')->field('id,title')->where(['agreement_classify_id'=>$value['id']])->select() ;
            $agreement_classify[$key]['child'] = $agreement ;
        }

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

}