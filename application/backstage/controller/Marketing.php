<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 19-2-26
 * Time: 上午10:53
 */

namespace app\backstage\controller;

use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;

class Marketing extends Base
{
    //用户营销活动添加
    public function addActivity()
    {
        $data = input('');
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "activity_title" => input('?activity_title') ? input('activity_title') : null,
            "activity_recommend" => input('?activity_recommend') ? input('activity_recommend') : null,
            "start_time" => input('?start_time') ? input('start_time') : null,
            "end_time" => input('?end_time') ? input('end_time') : null,
            "people_count" => input('?people_count') ? input('people_count') : null,
            "start_timebucket" => input('?start_timebucket') ? input('start_timebucket') : null,
            "end_timebucket" => input('?end_timebucket') ? input('end_timebucket') : null,
            "everyone_restriction" => input('?everyone_restriction') ? input('everyone_restriction') : null,
            "status" => input('?status') ? input('status') : 1,
            "activity_type" => input('?activity_type') ? input('activity_type') : 1,
            "is_gift" => input('?is_gift') ? input('is_gift') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "activity_title"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $params['create_time'] = time();
        //计算活动开始时间和结束时间
        $params['activity_start_times'] = strtotime($params['start_time'] . "" . $params['start_timebucket']);
        $params['activity_end_times'] = strtotime($params['end_time'] . "" . $params['activity_end_times']);

        $activity_id = db('activity')->insertGetId($params);

        if ($activity_id > 0) {
            //保存活动方案
            $detailed = $data['detailed'];
            $ini = [];
            foreach ($detailed as $key => $value) {
                $ini['type'] = $value['type'];
                $ini['origin_radius'] = $value['origin_radius'];
                $ini['order_type'] = $value['order_type'];
                $ini['invitation_people'] = $value['invitation_people'];
                $ini['recharge_money'] = $value['recharge_money'];
                $ini['restrict_type'] = $value['restrict_type'];
                $ini['origin_longitude'] = $value['origin_longitude'];
                $ini['origin_latitude'] = $value['origin_latitude'];
                $ini['destination_longitude'] = $value['destination_longitude'];
                $ini['destination_latitude'] = $value['destination_latitude'];
                $ini['destination_radius'] = $value['destination_radius'];
                $ini['Orders_type'] = $value['Orders_type'];
                $ini['activity_id'] = $activity_id;
                $ini['originPositionName'] = $value['originPositionName'];
                $ini['destinationPositionName'] = $value['destinationPositionName'];
                $ini['userGroup'] = $value['userGroup'];

                Db::name('activity_program')->insert($ini);
            }

            //保存优惠券
            $coupon = $data['coupon'];
            $inii = [];
            foreach ($coupon as $k => $v) {
                $inii['strat_time'] = $v['strat_time'];
                $inii['end_time'] = $v['end_time'];
                //根据发送数量,向用户发送
                $inii['grant_count'] = $v['grant_count'];

                for ($i=1; $i<=$v['grant_count']; $i++) {
                    $inii['order_type'] = $v['order_type'];
                    $inii['activity_id'] = $activity_id;
                    $inii['coupon_type'] = $v['coupon_type'];
                    $inii['coupon_template_id'] = $v['coupon_template_id'];
                    $inii['start_day'] = $v['start_day'];
                    $inii['end_day'] = $v['end_day'];

                    //获取优惠模板
                    $coupon_template = Db::name('coupon_template')->where(['id' => $v['coupon_template_id']])->find();
                    $inii['type'] = $coupon_template['type'];
                    $inii['discount'] = $coupon_template['discount'];
                    $inii['min_money'] = $coupon_template['min_money'];
                    $inii['man_money'] = $coupon_template['man_money'];
                    $inii['pay_money'] = $coupon_template['pay_money'];
                    $inii['create_time'] = time();
                    $inii['status'] = $coupon_template['status'];
                    $inii['company'] = $coupon_template['city_id'];
                    $inii['arctic'] = $coupon_template['arctic'];
                    $inii['minus_money'] = $coupon_template['minus_money'];
                    $inii['title'] = $coupon_template['title'];

                    Db::name('activity_coupon')->insert($inii) ;
                }
            }

            //保存红包
            $envelope = $data['envelope'];
            $iniii = [];
            foreach ($envelope as $kk => $vv) {
                $iniii['money'] = $vv['money'];
                $iniii['activity_id'] = $activity_id;
                $iniii['redenvelope_temp_id'] = $vv['redenvelope_temp_id'];

                $redenvelope_temp = Db::name('redenvelope_temp')->where(['id' => $vv['redenvelope_temp_id']])->find();
                //保存红包信息
                $inii['title'] = $redenvelope_temp['title'];
                $inii['city_id'] = $redenvelope_temp['city_id'];
                $inii['arctic'] = $redenvelope_temp['arctic'];
                $inii['payment_Percentage'] = $redenvelope_temp['payment_Percentage'];

                Db::name('activity_envelope')->insert($iniii);
            }

            return [
                'code' => APICODE_SUCCESS,
                'msg' => '创建成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '创建失败'
            ];
        }
    }

    //活动列表
    public function activity_list()
    {
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "start_time" => input('start_time') ? ['gt', input('start_time')] : null,
            "end_time" => input('end_time') ? ['lt', input('end_time')] : null,
            "activity_title" => input('?activity_title') ? input('activity_title') : null,
            "activity_type" => input('activity_type') ? [input('activity_type')] : 1,
        ];
        return self::pageReturnStrot(db('activity'), $params,'id desc');
    }

    //根据id获取活动详情
    public function activity_details()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('activity')->where($params)->find();
            //活动方案
            $data['activity_program'] = Db::name('activity_program')->where(['activity_id' => input('id')])->select();
            //活动优惠券
            $data['activity_coupon'] = Db::name('activity_coupon')->where(['activity_id' => input('id')])->select();
            //活动红包表
            $data['activity_envelope'] = Db::name('activity_envelope')->where(['activity_id' => input('id')])->select();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "活动ID不能为空"
            ];
        }


    }

    //活动暂停
    public function activity_pause()
    {
        if (input('?id')) {
            $ini['id'] = input('id');
            $ini['status'] = input('status');
            $activity = Db::name('activity')->update($ini);
            if ($activity) {
                return ['code' => APICODE_SUCCESS, 'msg' => '暂停成功'];
            } else {

                return ['code' => APICODE_DATABASEERROR, 'msg' => '暂停失败'];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "活动ID不能为空"
            ];
        }
    }

    //更新用户营销
    public function UpdateUserActivity()
    {
        $data = input('');

        $params = [
            "id" => input('?id') ? input('id') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "activity_title" => input('?activity_title') ? input('activity_title') : null,
            "activity_recommend" => input('?activity_recommend') ? input('activity_recommend') : null,
            "start_time" => input('?start_time') ? input('start_time') : null,
            "end_time" => input('?end_time') ? input('end_time') : null,
            "people_count" => input('?people_count') ? input('people_count') : null,
            "start_timebucket" => input('?start_timebucket') ? input('start_timebucket') : null,
            "end_timebucket" => input('?end_timebucket') ? input('end_timebucket') : null,
            "everyone_restriction" => input('?everyone_restriction') ? input('everyone_restriction') : null,
            "status" => input('?status') ? input('status') : 1,
            "activity_type" => input('?activity_type') ? input('activity_type') : 1,
            "is_gift" => input('?is_gift') ? input('is_gift') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "activity_title"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $params['activity_start_times'] = strtotime($params['start_time'] . "" . $params['start_timebucket']);
        $params['activity_end_times'] = strtotime($params['end_time'] . "" . $params['activity_end_times']);

        $activity_id = db('activity')->update($params);
//        if($activity_id > 0){
        $activity_program = Db::name('activity_program')->where(['activity_id' => input('id')])->select();
        foreach ($activity_program as $kq => $vq) {
            Db::name('activity_program')->where(['id' => $vq['id']])->delete();
        }

        //保存活动方案
        $detailed = $data['detailed'];
        $ini = [];
        foreach ($detailed as $key => $value) {
            $ini['type'] = $value['type'];
            $ini['origin_radius'] = $value['origin_radius'];
            $ini['order_type'] = $value['order_type'];
            $ini['invitation_people'] = $value['invitation_people'];
            $ini['recharge_money'] = $value['recharge_money'];
            $ini['restrict_type'] = $value['restrict_type'];
            $ini['origin_longitude'] = $value['origin_longitude'];
            $ini['origin_latitude'] = $value['origin_latitude'];
            $ini['destination_longitude'] = $value['destination_longitude'];
            $ini['destination_latitude'] = $value['destination_latitude'];
            $ini['destination_radius'] = $value['destination_radius'];
            $ini['Orders_type'] = $value['Orders_type'];
            $ini['activity_id'] = input('id');
            $ini['originPositionName'] = $value['originPositionName'];
            $ini['destinationPositionName'] = $value['destinationPositionName'];
            $ini['userGroup'] = $value['userGroup'];

            Db::name('activity_program')->insert($ini);
        }

        //保存优惠券
        $coupon = $data['coupon'];
        $activity_coupon = Db::name('activity_coupon')->where(['activity_id' => input('id')])->select();
        foreach ($activity_coupon as $kk => $vv) {
            Db::name('activity_coupon')->where(['id' => $vv['id']])->delete();
        }

        $inii = [];
        foreach ($coupon as $k => $v) {
            $inii['strat_time'] = $v['strat_time'];
            $inii['end_time'] = $v['end_time'];
            $inii['grant_count'] = $v['grant_count'];

            for ($i=1; $i<=$v['grant_count']; $i++) {
                $inii['order_type'] = $v['order_type'];
                $inii['activity_id'] = input('id');
                $inii['coupon_type'] = $v['coupon_type'];
                $inii['coupon_template_id'] = $v['coupon_template_id'];
                $inii['start_day'] = $v['start_day'];
                $inii['end_day'] = $v['end_day'];

                //获取优惠模板
                $coupon_template = Db::name('coupon_template')->where(['id' => $v['coupon_template_id']])->find();
                $inii['type'] = $coupon_template['type'];
                $inii['discount'] = $coupon_template['discount'];
                $inii['min_money'] = $coupon_template['min_money'];
                $inii['man_money'] = $coupon_template['man_money'];
                $inii['pay_money'] = $coupon_template['pay_money'];
                $inii['create_time'] = time();
                $inii['status'] = $coupon_template['status'];
                $inii['company'] = $coupon_template['city_id'];
                $inii['arctic'] = $coupon_template['arctic'];
                $inii['minus_money'] = $coupon_template['minus_money'];
                $inii['title'] = $coupon_template['title'];

                Db::name('activity_coupon')->insert($inii);
            }
        }

        //保存红包
        $envelope = $data['envelope'];
        $activity_envelope = Db::name('activity_envelope')->where(['activity_id' => input('id')])->select();
        foreach ($activity_envelope as $kw => $vw) {
            Db::name('activity_envelope')->where(['id' => $vw['id']])->delete();
        }
        $iniii = [];
        foreach ($envelope as $kk => $vv) {
            $iniii['money'] = $vv['money'];
            $iniii['employ_scope'] = $vv['employ_scope'];
            $iniii['activity_id'] = input('id');
            $iniii['redenvelope_temp_id'] = $vv['redenvelope_temp_id'];

            Db::name('activity_envelope')->insert($iniii);
        }

        return [
            'code' => APICODE_SUCCESS,
            'msg' => '更新成功'
        ];
//        }else{
//            return [
//                'code'=>APICODE_ERROR,
//                'msg'=>'更新失败'
//            ];
//        }

    }

    //司机营销活动添加
    public function chauffeur_activity()
    {

        $data = input('');
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "activity_title" => input('?activity_title') ? input('activity_title') : null,
            "activity_recommend" => input('?activity_recommend') ? input('activity_recommend') : null,
            "start_time" => input('?start_time') ? input('start_time') : null,
            "end_time" => input('?end_time') ? input('end_time') : null,
            "people_count" => input('?people_count') ? input('people_count') : null,
            "start_timebucket" => input('?start_timebucket') ? input('start_timebucket') : null,
            "end_timebucket" => input('?end_timebucket') ? input('end_timebucket') : null,
            "everyone_restriction" => input('?everyone_restriction') ? input('everyone_restriction') : null,
            "status" => input('?status') ? input('status') : 1,
            "activity_type" => input('?activity_type') ? input('activity_type') : 2,
            "packet" => input('?packet') ? input('packet') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "activity_title"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $params['create_time'] = time();
        $activity_id = db('activity')->insertGetId($params);

        if ($activity_id > 0) {
            //保存活动方案
            $detailed = $data['detailed'];
            $ini = [];
            foreach ($detailed as $key => $value) {
                $ini['type'] = $value['type'];
                $ini['invitation_people'] = $value['invitation_people'];
                $ini['activity_id'] = $activity_id;

                Db::name('activity_motorman_program')->insert($ini);
            }
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '创建成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '创建失败'
            ];
        }


    }

    //司机营销活动列表
    public function chauffeur_activity_list()
    {
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "create_time" => input('create_time') ? ['gt', input('create_time')] : null,
            "start_time" => input('start_time') ? ['gt', input('start_time')] : null,
            "end_time" => input('end_time') ? ['lt', input('end_time')] : null,
            "people_count" => input('people_count') ? ['gt', input('people_count')] : null,
            "activity_type" => input('activity_type') ? [input('activity_type')] : 2,
        ];
        return self::pageReturn(db('activity'), $params);
    }

    //根据ID获取司机营销详情
    public function getConducteur_activity()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('activity')->where($params)->find();
            //活动方案
            $data['activity_motorman_program'] = Db::name('activity_motorman_program')->where(['activity_id' => input('id')])->select();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "活动ID不能为空"
            ];
        }
    }

    //更新司机营销
    public function UpdateConducteurActivity()
    {
        $data = input('');
        $params = [
            "id" => input('?id') ? input('id') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "activity_title" => input('?activity_title') ? input('activity_title') : null,
            "activity_recommend" => input('?activity_recommend') ? input('activity_recommend') : null,
            "start_time" => input('?start_time') ? input('start_time') : null,
            "end_time" => input('?end_time') ? input('end_time') : null,
            "people_count" => input('?people_count') ? input('people_count') : null,
            "start_timebucket" => input('?start_timebucket') ? input('start_timebucket') : null,
            "end_timebucket" => input('?end_timebucket') ? input('end_timebucket') : null,
            "everyone_restriction" => input('?everyone_restriction') ? input('everyone_restriction') : null,
            "status" => input('?status') ? input('status') : 1,
            "activity_type" => input('?activity_type') ? input('activity_type') : 2,
            "packet" => input('?packet') ? input('packet') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "activity_title"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $params['create_time'] = time();
        $activity_id = db('activity')->update($params);

//        if($activity_id > 0){
        //保存活动方案
        $activity_motorman_program = Db::name('activity_motorman_program')->where(['activity_id' => input('id')])->select();
        foreach ($activity_motorman_program as $k => $v) {
            Db::name('activity_motorman_program')->where(['id' => $v['id']])->delete();
        }

        $detailed = $data['detailed'];
        $ini = [];
        foreach ($detailed as $key => $value) {
            $ini['type'] = $value['type'];
            $ini['invitation_people'] = $value['invitation_people'];
            $ini['activity_id'] = input('id');

            Db::name('activity_motorman_program')->insert($ini);
        }
        return [
            'code' => APICODE_SUCCESS,
            'msg' => '更新成功'
        ];
    }

    //添加活动广告位
    public function addActivityCarousel()
    {
        $carousel = input('carousel');

        if (!empty($carousel)) {
            $ini = [];
            foreach ($carousel as $key => $value) {
                $ini['img'] = $value['img'];
                $ini['activity_id'] = $value['activity_id'];
                $ini['type'] = $value['type'];

                Db::name('activity_carousel')->insert($ini);
            }
            return ['code' => APICODE_SUCCESS, 'msg' => '添加成功'];
        } else {
            return ['code' => APICODE_DATABASEERROR, 'msg' => '参数为空'];
        }
    }

    //添加人工发放优惠券
    public function labour()
    {

        $data = input('');

        $params = [
            "title" => input('?title') ? input('title') : null,
            "cause" => input('?cause') ? input('cause') : null,
            "user_id" => input('?user_id') ? input('user_id') : null,
            "indate_type" => input('?indate_type') ? input('indate_type') : null,
            "start_time" => input('?start_time') ? input('start_time') : null,
            "end_time" => input('?end_time') ? input('end_time') : null,
            "start_days" => input('?start_days') ? input('start_days') : null,
            "end_days" => input('?end_days') ? input('end_days') : null,
            "type" => input('?type') ? input('type') : null,
            "count" => input('?count') ? input('count') : null,
            "create_time" => input('?create_time') ? input('create_time') : time(),
            "city_id" => input('?city_id') ? input('city_id') : null,
            "issuer" => input('?issuer') ? input('issuer') : null,
        ];

        $coupon_type = $data['coupon_type'] ? $data['coupon_type'] : array();
        $params["people"] = $data['people'] ? $data['people'] : array();
        if (!empty($coupon_type)) {
            $params['coupon_type'] = implode(",", $coupon_type);
        }
        $params = $this->filterFilter($params);
        $required = ["title", "cause", "indate_type", "type", "coupon_type", "count"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $labour_coupon = db('labour_coupon')->insertGetId($params);
        switch ($params["type"]) {
            case 1://发放至指定用户
                $user_ids = $params["people"] ? $params["people"] : array();
                for ($i = 0; $i < count($coupon_type); $i++) {
                    $mannel_coupon = Db::name('coupon_template')->where(['id' => $coupon_type[$i]])->find();
                    if ($mannel_coupon) {
                        $count = 0;
                        $insertSqls = [];
                        while ($count < $params["count"]) {
                            $count++;
                            $mannel_coupon["indate_type"] = $params["indate_type"];
                            $mannel_coupon["title"] = $params["title"];
                            $mannel_coupon["start_time"] = $params["start_time"] /1000 ;
                            $mannel_coupon["end_time"] = $params["end_time"]/1000;
                            $mannel_coupon["start_day"] = $params["start_days"];
                            $mannel_coupon["end_day"] = $params["end_days"];
                            for ($j = 0; $j < count($user_ids); $j++) {
                                $this->IssueCoupons($user_ids[$j], $labour_coupon, $mannel_coupon["city_id"], $mannel_coupon);
                            }
                        }

                    }
                }
                return [
                    'code' => APICODE_SUCCESS,
                    'msg' => '向用户发放优惠券成功'
                ];
                break;
                break;
            case 2://生成兑换码
                $count = 0;
                while ($count < $params["count"]) {
                    $count++;
                    db("receive")->insert(array("labour_id" => $labour_coupon, "type" => 1, "sended" => 0));
                }
                return [
                    'code' => APICODE_SUCCESS,
                    'msg' => '生成兑换码成功'
                ];
                break;
            default:
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '发放失败，原因为:发放类型不详'
                ];
        }
    }

    public function IssueCoupons($user_id, $labour_coupon, $city_id, $ac)
    {
        //获取优惠券信息，发给用户
//        $acs = $ac;
        $ini['coupon_name'] = $ac['title'];
        $times = array("startTime" => null, "endTime" => null);
        switch ($ac["indate_type"]) {
            case 1:
                //固定时间
                $times["startTime"] = $ac["start_time"];
                $times["endTime"] = $ac["end_time"];
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

        $couponId = Db::name('user_coupon')->insertGetId($ini);
        $sendHistory = array("labour_id" => $labour_coupon, "type" => 1, "user_id" => $user_id, "sended" => 1, "send_time" => time(), "coupon_id" => $couponId);
        db("receive")->insert($sendHistory);
    }

    //根据ID获取人工优惠券详情
    public function getLabourCoupon()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            //领取情况
            $data = Db::name('user_coupon u')
                ->join("receive r", "r.coupon_id=u.id", "right")
                ->where(['r.labour_id' => input('id')])
                ->field(array(
                    "r.id as recieve_id",
                    "r.type as recieve_type",
                    "r.code",
                    "r.sended",
                    "r.send_time",
                    "u.*"
                ))
                ->select();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "优惠券ID不能为空"
            ];
        }
    }

    //人工优惠券列表
    public function LabourCouponList()
    {
        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;

        $where = [] ; $where1 = [] ;
        if( !empty(input('city_id')) ){
            $where['l.city_id'] = ['eq' , input('city_id') ];
            $where1['city_id'] = ['eq' , input('city_id') ];
        }
        $where2 = [] ;
        if(!empty(input('issuer')) && input('issuer') != "null"){
            $where2['issuer'] = ['like',"%".input('issuer')."%"];
        }
        $where3 = [] ;
        if(!empty(input('title'))){
            $where3['title'] = ['like',"%".input('title')."%"];
        }
        $where4 = [] ;
        if(!empty(input('PassengerPhone'))){
            $where4['phone'] = ['like',"%".input('PassengerPhone')."%"];
        }
        $where5 = [] ; $where6 = [] ;
        if(!empty(input('create_time'))){
            $start_time = strtotime( input('create_time')." 00:00:00" ) ;
            $end_time = strtotime( input('create_time')." 23:59:59" ) ;

            $where5['create_time'] = ['gt' , $start_time ];
            $where6['create_time'] = ['lt' , $end_time];
        }

        $subSql = db("receive")->alias("r")->field("labour_id,user_id,count(labour_id) count")->group("labour_id")->buildSql();
        $subSql1 = db("receive")->alias("r")->field("labour_id,user_id,count(labour_id) succeed_count")->where(["sended" => 1])->group("labour_id")->buildSql();
        $subSql2 = db("user")->alias("u")->field("id,PassengerPhone as phone")->buildSql();

        $data = db('labour_coupon l')
            ->join([$subSql => "c"], "c.labour_id=l.id", "left")
            ->join([$subSql1 => "s"], "s.labour_id=l.id", "left")
            ->join([$subSql2 => "v"], "v.id=s.user_id", "left")
            ->where($where)
            ->where($where2)
            ->where($where3)
            ->where($where4)
            ->where($where5)
            ->where($where6)
            ->page($pageNum, $pageSize)
            ->order("create_time desc")
            ->select();

        $sum = db('labour_coupon l')
            ->join([$subSql => "c"], "c.labour_id=l.id", "left")
            ->join([$subSql1 => "s"], "s.labour_id=l.id", "left")
            ->join([$subSql2 => "v"], "v.id=s.user_id", "left")
            ->where($where)
            ->where($where2)
            ->where($where3)
            ->where($where4)
            ->where($where5)
            ->where($where6)
            ->count();

        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" => $data
        ];
    }

    //添加人工红包
    public function addLabourPacket()
    {
        $params = [
            "title" => input('?title') ? input('title') : null,
            "cause" => input('?cause') ? input('cause') : null,
            "type" => input('?type') ? input('type') : null,
            "money" => input('?money') ? input('money') : null,
            "cdkey" => input('?cdkey') ? input('cdkey') : null,
            "create_time" => input('?create_time') ? input('create_time') : time(),
        ];

        $params = $this->filterFilter($params);
        $required = ["title"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $labour_packet = db('labour_packet')->insertGetId($params);

        if ($labour_packet > 0) {
            //领取记录
            $ini['labour_id'] = $labour_packet;
            $ini['user_id'] = input('user_id');
            $ini['type'] = 2;

            $receive = Db::name('receive')->insert($ini);

            if ($receive) {
                //用户红包金额增加
                Db::name('user')->where(['id' => input('user_id')])->setInc('packet_money', input('money'));

                return [
                    'code' => APICODE_SUCCESS,
                    'msg' => '发放成功'
                ];
            } else {
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '发放失败'
                ];
            }
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '发放失败'
            ];
        }

    }

    //人工红包列表
    public function LabourPacketList()
    {
        return self::pageReturn(db('labour_packet'), '');
    }

    //根据ID获取人工红包详情
    public function getbourPacket()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('labour_packet')->where($params)->find();

            //领取情况
            $data['receive'] = Db::name('receive')->where(['labour_id' => input('id'), 'type' => 2])->select();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "红包ID不能为空"
            ];
        }
    }

    //添加人工充值卡
    public function addLabourRechargeable()
    {

        $params = [
            "title" => input('?title') ? input('title') : null,
            "cause" => input('?cause') ? input('cause') : null,
            "type" => input('?type') ? input('type') : null,
            "money" => input('?money') ? input('money') : null,
            "create_time" => input('?create_time') ? input('create_time') : time(),
        ];

        $params = $this->filterFilter($params);
        $required = ["title", "money"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $labour_rechargeable = db('labour_rechargeable')->insertGetId($params);

        if ($labour_rechargeable > 0) {
            //领取记录
            $ini['labour_id'] = $labour_rechargeable;
            $ini['user_id'] = input('user_id');
            $ini['type'] = 3;

            $receive = Db::name('receive')->insert($ini);

            if ($receive) {
                //给用户加余额
                Db::name('user')->where(['id' => input('user_id')])->setInc('balance', input('money'));
                return [
                    'code' => APICODE_SUCCESS,
                    'msg' => '创建成功'
                ];
            } else {
                return [
                    'code' => APICODE_ERROR,
                    'msg' => '创建失败'
                ];
            }
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '创建失败'
            ];
        }
    }

    //人工充值卡列表
    public function LabourRechargeableList()
    {
        return self::pageReturn(db('labour_rechargeable'), '');
    }

    //根据id获取人工充值卡详情
    public function getLabourRechargeable()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('labour_rechargeable')->where($params)->find();

            //领取记录
            $data['receive'] = Db::name('receive')->where(['labour_id' => input('id'), 'type' => 3])->select();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "充值卡ID不能为空"
            ];
        }
    }

    //添加优惠券魔板
    public function addCouponTemplate()
    {
        $params = [
            "title" => input('?title') ? input('title') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "arctic" => input('?arctic') ? input('arctic') : null,
            "type" => input('?type') ? input('type') : null,
            "discount" => input('?discount') ? input('discount') : null,
            "min_money" => input('?min_money') ? input('min_money') : null,
            "man_money" => input('?man_money') ? input('man_money') : null,
            "minus_money" => input('?minus_money') ? input('minus_money') : null,
            "pay_money" => input('?pay_money') ? input('pay_money') : null,
            "create_time" => input('?create_time') ? input('create_time') : time(),
            "status" => input('?status') ? input('status') : 1,
        ];

        $params = $this->filterFilter($params);
        $required = ["title", "city_id", "type"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $coupon_template = db('coupon_template')->insert($params);

        if ($coupon_template) {
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '创建成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '创建失败'
            ];
        }
    }

    //优惠券模板列表
    public function couponTemplateList()
    {
//        return self::pageReturn(db('coupon_template') ,'');
        $params = '';
        $pageSize = input('?pageSize') ? input('pageSize') : 100;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;

        $where = [] ;
        if( !empty(input('city_id')) ){
            $where['city_id'] = ['eq' , input('city_id')] ;
        }

        $sum = db('coupon_template')->where($where)->where(self::filterFilter($params))->count();

        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" => db('coupon_template')->order('id desc')->where($where)->where(self::filterFilter($params))->page($pageNum, $pageSize)
                ->select()
        ];
    }

    //根据模板id获取信息
    public function getTemplate()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('coupon_template')->where($params)->find();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "模板ID不能为空"
            ];
        }
    }

    //更新模板信息
    public function UpdateTemplate()
    {
        $params = [
            "id" => input('?id') ? input('id') : null,
            "title" => input('?title') ? input('title') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "arctic" => input('?arctic') ? input('arctic') : null,
            "type" => input('?type') ? input('type') : null,
            "discount" => input('?discount') ? input('discount') : null,
            "min_money" => input('?min_money') ? input('min_money') : null,
            "man_money" => input('?man_money') ? input('man_money') : null,
            "minus_money" => input('?minus_money') ? input('minus_money') : null,
            "pay_money" => input('?pay_money') ? input('pay_money') : null,
            "create_time" => input('?create_time') ? input('create_time') : time(),
            "status" => input('?status') ? input('status') : 1,
        ];

        $params = $this->filterFilter($params);
        $required = ["title", "city_id", "type"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $res = Db::name('coupon_template')->update($params);
        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }

    //添加红包模板
    public function addRedEnvelope()
    {
        $params = [
            "title" => input('?title') ? input('title') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "arctic" => input('?arctic') ? input('arctic') : null,
            "payment_Percentage" => input('?payment_Percentage') ? input('payment_Percentage') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["title", "city_id", "arctic"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $redenvelope = db('redenvelope_temp')->insert($params);

        if ($redenvelope) {
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '创建成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '创建失败'
            ];
        }
    }

    //红包模板列表
    public function RedEnvelopeList()
    {
        return self::pageReturn(db('redenvelope_temp'), '');
    }

    //根据id获取红包模板详情
    public function getEnvelope()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('redenvelope_temp')->where($params)->find();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "模板ID不能为空"
            ];
        }
    }

    //更新红包模板信息
    public function UpdateEnvelope()
    {
        $params = [
            "id" => input('?id') ? input('id') : null,
            "title" => input('?title') ? input('title') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "arctic" => input('?arctic') ? input('arctic') : null,
            "payment_Percentage" => input('?payment_Percentage') ? input('payment_Percentage') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["title", "city_id", "arctic"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $res = Db::name('redenvelope_temp')->update($params);
        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }

    //活动广告位列表
    public function activityList()
    {
        return self::pageReturn(db('advertising'), '');
    }

    //营销首页
    public function MarketingHome(){
        $where = [] ; $where1 = [] ;$where2 = [] ;
        if(!empty( input('city_id') )){
            $where['city_id'] = ['eq' , input('city_id') ] ;
            $where1['a.city_id'] = ['eq' , input('city_id') ] ;
            $where2['t.city_id'] = ['eq' , input('city_id') ] ;
        }

       //进行中活动数
       $data['underway_count'] = Db::name('activity')->where($where)->where(['status' => 1 ])->count() ;

       //今日活动参与人数
       $start_time = strtotime( date('Y-m-d',time() )." 00:00:00" ) ;
       $end_time = strtotime( date('Y-m-d',time() )." 23:59:59" ) ;

        $data['day_count'] = Db::name('activity_program_history')
                              ->alias('h')
                              ->join('mx_activity_program p','p.id = h.activity_program_id','left')
                              ->join('mx_activity a','a.id = p.activity_id','left')
                              ->where($where1)
                              ->where('h.time','gt' , $start_time )
                              ->where('h.time','lt',$end_time)
                              ->count() ;

       //昨日活动参与人数
       $past_start_time = strtotime ( date( "Y-m-d" , strtotime( "-1 day" ) ) . " 00:00:00" );
       $past_end_time = strtotime ( date( "Y-m-d" , strtotime( "-1 day" ) ) . " 23:59:59" ) ;

        $data['past_day_count'] = Db::name('activity_program_history')
                                    ->alias('h')
                                    ->join('mx_activity_program p','p.id = h.activity_program_id','left')
                                    ->join('mx_activity a','a.id = p.activity_id','left')
                                    ->where($where1)
                                    ->where('time','gt' , $past_start_time )
                                    ->where('time','lt',$past_end_time)
                                    ->count() ;

        //活动区域分布
        $data['activity_area_distribution'] = Db::name('activity')
                                                ->alias('a')
                                                ->join('mx_cn_city c','c.id = a.city_id','left')
                                                ->join('mx_cn_prov p','c.pcode = p.code')
                                                ->field('p.name as p_name,count(a.id) as a_count')
                                                ->where($where1)
                                                ->group('p.id')->select();

        //参与活动人数分布
        $data['participation_activity_people'] = Db::name('activity')
                                                    ->alias('a')
                                                    ->join('mx_activity_program g','g.activity_id = a.id')
                                                    ->join('mx_activity_program_history h','h.activity_program_id = g.id')
                                                    ->join('mx_cn_city c','c.id = a.city_id','left')
                                                    ->join('mx_cn_prov p','c.pcode = p.code')
                                                    ->field('p.name as p_name,count(g.id) as p_count')
                                                    ->group('p.id')
                                                    ->where($where1)
                                                    ->select();

        //优惠券分布
        $data['coupon_distribution'] = Db::name('coupon_template')
                                        ->alias('t')
                                        ->join('mx_cn_city c','c.id = t.city_id','left')
                                        ->join('mx_cn_prov p','c.pcode = p.code')
                                        ->field('p.name as p_name,count(t.id) as c_count')
                                        ->where($where2)
                                        ->group('p.id')->select();

        //优惠券使用趋势
        //日期为null   本周
        $times = aweek("",1);
        $beginThisweek = strtotime($times[0]);
        $endThisweek = strtotime($times[1]);
        $coupon_time = input('coupon_time');

        if($coupon_time == "null" || $coupon_time == "null "){
            $start = $beginThisweek ;
            $end = $endThisweek ;
            $Interval = diffBetweenTwoDays((int)$start,(int)$end);
        }else{
            $coupon_times = explode(',',$coupon_time);
            $start = strtotime($coupon_times[0]);
            $end = strtotime($coupon_times[1]);

            $Interval = diffBetweenTwoDays((int)$start,(int)$end);
        }

        $city_name = Db::name('coupon_template')->alias('t')
                     ->distinct(true)
                     ->field('c.name as c_name,count(t.id) as count')
                     ->join('mx_cn_city c','c.id = t.city_id','inner')
                     ->where($where2)
                     ->limit(3)
                     ->select();

        $order = [] ; $where = [] ;
        for ($y = 0; $y <= $Interval; $y++) {     //行

            $op_o = date('Y-m-d',$start);

            $day_start_o = strtotime($op_o.' 00:00:00') ;  //当天开始时间
            $day_end_o = strtotime($op_o.' 23:59:59') ;    //当天结束时间

            $days =  $this->days(date('m', $start) );
            $times = date('m', $start) . '-' . (((int)date('d', $start)) + $y) ;
            if((((int)date('d', $start)) + $y) <= $days) {
                $order[] = [
                    'times' => date('m', $start) . '/' . (((int)date('d', $start)) + $y),
                    $city_name[0]['c_name'] => $this->UserCoupon($times,$city_name[0]['c_name']),
                    $city_name[1]['c_name'] => $this->UserCoupon($times,$city_name[1]['c_name']),
                    $city_name[2]['c_name'] => $this->UserCoupon($times,$city_name[2]['c_name']),
                ];
            }
        }

        $data['coupon']['city_name'] = $city_name;
        $data['coupon']['order'] = $order;

        return ['code'=>APICODE_SUCCESS,'data'=>$data];
    }
    //用户使用优惠券数
    public function UserCoupon($times,$city){
        $day_start = strtotime(date('Y',time())."-".$times." 00:00:00") ;
        $day_end = strtotime(date('Y',time())."-".$times." 23:59:59") ;

        $city_id = Db::name('cn_city')->where(['name' =>$city ])->value('id') ;

        $user_count =  Db::name('user_coupon')->alias('u')
                                                       ->where([ 'is_use' => 1 ])
                                                       ->where(['city_id' => $city_id])
                                                       ->where('create_time','gt',$day_start)
                                                       ->where('create_time','lt',$day_end)
                                                       ->count() ;

        return $user_count ;
    }

    //天数
    private function days($months){
        $month = (int)$months;
        $day = 0 ;
        if($month == 1){
            $day = 31;
        }else if($month == 2){
            $day = 28;
        }else if($month == 3){
            $day = 31;
        }else if($month == 4){
            $day = 30;
        }else if($month == 5){
            $day = 31;
        }else if($month == 6){
            $day = 30;
        }else if($month == 7){
            $day = 31;
        }else if($month == 8){
            $day = 31;
        }else if($month == 9){
            $day = 30;
        }else if($month == 10){
            $day = 31;
        }else if($month == 11){
            $day = 30;
        }else if($month == 12){
            $day = 31;
        }
        return $day;
    }
}