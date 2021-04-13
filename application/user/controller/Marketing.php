<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 19-2-26
 * Time: 上午10:53
 */

namespace app\user\controller;

use think\Controller;
use think\Db;

class Marketing extends Controller
{
    /**
     * @param $user_id 用户ID
     * @param $city_id 城市ID
     * @param $type 触发条件类型
     * @param $extra 额外参数
     */
    public function judgeActivity($user_id, $city_id, $type, $extra)
    {
        $file = fopen('./log.txt', 'a+');
        $time = time();
        //查询该城市下，处于进行中，且拥有触发类型为指定类型的活动的触发器
        $acps = [] ;
        if($type == 4){
            $acps = Db::name('activity_program')
                ->alias('acp')
                ->join("mx_activity ac", "ac.id=acp.activity_id", "LEFT")
                ->where(array(
                    'acp.type' => ["like", "%$type%"],
                    'ac.activity_start_times' => ['elt', $time],
                    'ac.activity_end_times' => ['egt', $time],
                    'ac.status' => 1,
                    'ac.activity_type' => 1
                ))
                ->field("acp.*,everyone_restriction")
                ->select();
        }else{
            $acps = Db::name('activity_program')
                ->alias('acp')
                ->join("mx_activity ac", "ac.id=acp.activity_id", "LEFT")
                ->where(array(
                    'acp.type' => ["like", "%$type%"],
                    'ac.city_id' => $city_id,
                    'ac.activity_start_times' => ['elt', $time],
                    'ac.activity_end_times' => ['egt', $time],
                    'ac.status' => 1,
                    'ac.activity_type' => 1
                ))
                ->field("acp.*,everyone_restriction")
                ->select();
        }

        //找到现在满足的活动
        $active=false;
        foreach ($acps as $key => $acp) {
            $temp=$this->judgeActivityProgram($user_id, $acp, (int)$type, $extra);
            if($temp) $active=true;
        }
        return $active;
    }

    public function judgeActivityProgram($user_id, $acp, $type, $extra)
    {
        $file = fopen('./log.txt', 'a+');

        $active = false;
        $restriction_count = db('activity_program_history')->where(array("user_id" => $user_id, "activity_program_id" => $acp["id"]))->count();
        //获取参数活动的人数
        $history_count = db('activity_program_history')->where(array("activity_program_id" => $acp["id"]))->count();
        fwrite($file, "-------------------history_count:--------------------".$history_count."\r\n");     //司机电话
        if ( ($restriction_count < $acp["everyone_restriction"] || $acp["everyone_restriction"] == 0)  ) {
            fwrite($file, "-------------------进去了:-----if---------------"."\r\n");     //司机电话
            switch ($type) {
                case 1://注册
                    $active = true;
                    break;
                case 2://随机性
                    if (count(explode($extra["business_id"], $acp["order_type"])) > 1) {
                        switch ($acp["restrict_type"]) {
                            case 1://限制起点
                                if ($this->getDistance($acp["origin_longitude"], $acp["origin_latitude"], $extra["origin_longitude"], $extra["origin_latitude"]) < $acp["origin_radius"]) {
                                    $active = true;
                                }
                                break;
                            case 2://限制终点
                                if ($this->getDistance($acp["destination_longitude"], $acp["destination_latitude"], $extra["destination_longitude"], $extra["destination_latitude"]) < $acp["destination_radius"]) {
                                    $active = true;
                                }
                                break;
                            case 3://限制起终点
                                if ($this->getDistance($acp["destination_longitude"], $acp["destination_latitude"], $extra["destination_longitude"], $extra["destination_latitude"]) < $acp["destination_radius"] && $this->getDistance($acp["origin_longitude"], $acp["origin_latitude"], $extra["origin_longitude"], $extra["origin_latitude"]) < $acp["origin_radius"]) {
                                    $active = true;
                                }
                                break;
                        }
                    }
                    break;
                case 3://邀请
                    $count = db('user')->where(array("invite_id" => $user_id))->count();
                    if ($count > ($restriction_count + 1) * $acp["invitation_people"]) {
                        $active = true;
                    }
                    break;
                case 4://充值
                    if (sprintf("%.2f", ($acp["recharge_money"])) == sprintf("%.2f", ($extra["recharge_money"]))) {
                        $active = true;
                    }
                    break;
                case 5://用户首次下单
                    //用户所有非未取消的订单只有1个，判定为用户为首次下单
                    $count = db('order')->where(array("user_id" => $user_id, "status" => ["NEQ", 5]))->count();
                    if ($count == 1) {
                        $active = true;
                    }
                    break;
                case 6://用户下单
                    if (strpos($acp["Orders_type"],(string)$extra["business_id"]) !== false) {
                        if (strpos($acp["userGroup"],$user_id)!== false) {
                            $active = true;
                        }
                    }
                    $active = true;
                    break;
                case 7://用户实名认证
                    $active = true;
                    break;
                case 8://用户生日
                    $active = true;
                    break;
            }
            if ($active) {
                $this->activeActivity($user_id, $acp);
            }
        }
        return $active;
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
        $s = round($s, 6);
        $s = $s * 1000;
        return $s;
    }

    public function rad($d)
    {
        return $d * 3.1415926535898 / 180.0;
    }

    public function activeActivity($user_id, $acp)
    {
        $ac = db("activity_coupon")->where("activity_id", $acp["activity_id"])->select();
        $ar = db("activity_envelope")->where("activity_id", $acp["activity_id"])->find();
        if ($ac) {
            foreach ($ac as $key=>$value){
                $this->IssueCoupons($user_id, $value["coupon_template_id"], $value["company"], $value);
            }
        }
        if ($ar) {
            $this->IssuedEnvelope($user_id, $ar["redenvelope_temp_id"],$ar);
        }
        db('activity_program_history')->insert(array("user_id" => $user_id, "activity_program_id" => $acp["id"], "time" => time()));
    }
    //发放优惠券
    // user_id : 用户id ,coupon_id :优惠券id ,city_id ：城市id
    public function IssueCoupons($user_id, $coupon_id, $city_id, $ac)
    {
        //获取优惠券信息，发给用户
//        $acs = $ac;
        $ini['coupon_name'] = $ac['title'];
        $times = array("startTime" => null, "endTime" => null);
        switch ($ac["coupon_type"]) {
            case 1:
                //固定时间
                $times["startTime"] = $ac["strat_time"]/1000;
                $times["endTime"] = $ac["end_time"]/1000;
                break;
            case 2:
                //限制起始时间
                $issueTime = time();
                $times["startTime"] = strtotime("+" . $ac["start_day"] . " days", $issueTime);
                break;
            case 3:
                //限制结束时间
                $issueTime = time();
                $times["endTime"] = strtotime("+" . $ac["end_day"] . " days", $issueTime);
                break;
            case 4:
                //限制起始与结束时间
                $issueTime = time();
                $times["startTime"] = strtotime("+" . $ac["start_day"] . " days", $issueTime);
                $times["endTime"] = strtotime("+" . $ac["end_day"] . " days", $issueTime);
                break;
        }
        $ini['times'] = json_encode($times);
        $ini['user_id'] = $user_id;
        $ini['order_type'] = $ac['arctic'];
        $ini['city_id'] = $city_id;
        $ini['discount'] = $ac['discount'];
        $ini['min_money'] = $ac['min_money'];
        $ini['man_money'] = $ac['man_money'];
        $ini['minus_money'] = $ac['minus_money'];
        $ini['pay_money'] = $ac['pay_money'];
        $ini['coupon_name'] = $ac['title'];
        $ini['type'] = $ac['type'];
        $ini['is_use'] = 0;

        Db::name('user_coupon')->insert($ini);
    }

    //发放红包
    //user_id : 用户id ,redenvelope_id : 红包id
    public function IssuedEnvelope($user_id, $redenvelope_id,$ar)
    {
        //获取红包，发放给用户
//        $redenvelope_temp = Db::name('redenvelope_temp')->where(['id' => $redenvelope_id])->find();

        $inii['user_id'] = $user_id;
        $inii['money'] = $ar['money'];
        $inii['payment_Percentage'] = $ar['payment_Percentage'];
        $inii['title'] = $ar['title'];
        $inii['city_id'] = $ar['city_id'];
        $inii['arctic'] = $ar['arctic'];

        Db::name('user_redpacket')->insert($inii);
    }
}