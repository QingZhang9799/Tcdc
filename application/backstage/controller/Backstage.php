<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 19-2-26
 * Time: 上午10:53
 */

namespace app\backstage\controller;


use app\api\model\MerchantsShop;

use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Exception;

class Backstage extends Base
{
    //添加管理员
    public function add_Admin()
    {
        $params = [
            "username" => input('?username') ? input('username') : null,
            "password" => input('?password') ? input('password') : null,
            "nickname" => input('?nickname') ? input('nickname') : null,
            "mobile" => input('?mobile') ? input('mobile') : null,
            "avatar" => input('?avatar') ? input('avatar') : null,
            "company_id" => input('?company_id') ? input('company_id') : null,
            "group_id" => input('?group_id') ? input('group_id') : null,
        ];

        $params = self::filterFilter($params);

        if ($params["company_id"] && $params["company_id"] != "0") {
            $CompandId = db("company")->where(array("id" => $params["company_id"]))->value("CompandId");
            $params["username"] = $params["username"] . "@" . $CompandId;
        }
        $params['password'] = encrypt_salt($params['password']);
        try {
            $manager = Db::name('manager')->fetchSql(false)->insert($params);
        } catch (Exception $e) {

        }

        if ($manager) {
            return ['code' => APICODE_SUCCESS, 'msg' => '添加成功'];
        } else {
            return ['code' => APICODE_ERROR, 'msg' => '添加失败'];
        }
    }

    //管理员列表
    public function manager_list()
    {
        $params = [
            "m.company_id" => input('?company_id') ? input('company_id') : null,
        ];
        $db = db("manager m")
            ->join("authgroup ag", "m.group_id=ag.id", "left")
            ->join("company c", "c.id=m.company_id", "left")
            ->field("m.*,ag.title as group_name,c.CompanyName as company_name");
        return self::pageReturn($db, $params);
    }

    //权限列表
    public function permission_list()
    {
        $data = [];
        $data = Db::name('permission')->where(['superior' => 0])->select();                 //一级
        foreach ($data as $key => $value) {
            $data[$key]['access'] = $this->getAccessPermission($value['id']);                       //二级
            foreach ($data[$key]['access'] as $k => $v) {
                $data[$key]['access'][$k]['tertiary'] = $this->getAccessPermission($v['id']);      //三级
                foreach ($data[$key]['access'][$k]['tertiary'] as $kk => $vv) {
                    $data[$key]['access'][$k]['tertiary'][$kk]['fourstage'] = $this->getAccessPermission($vv['id']);  //四级
                }
            }
        }
        return ['code' => APICODE_SUCCESS, 'data' => $data];
    }

    //首页
    public function getHome()
    {
        $where = [];
        $where1 = [];
        $where2 = [];
        $where3 = [];
        $where4 = [];
        $where5 = [];
        $where6 = [];
        $where7 = [];
        $where8 = [];

        if (input('company_id') != 0) {           //分公司首页
            $where['u.city_id'] = ['eq', input('city_id')];
            $where1['c.city_id'] = ['eq', input('city_id')];
            $where2['company_id'] = ['eq', input('company_id')];
            $where3['o.city_id'] = ['eq', input('city_id')];
            $where4['city_id'] = ['eq', input('city_id')];
            $where5['o.company_id'] = ['eq', input('company_id')];
            $where6['u.company_id'] = ['eq', input('company_id')];
            $where7['c.company_id'] = ['eq', input('company_id')];
            $where8['j.city_id'] = ['eq', input('city_id')];
        }

        //全国活跃用户量
        $nationwide_user = Db::name('user')->alias('u')
//            ->join('mx_order r', 'r.user_id = u.id', 'left')
            ->where($where)
            ->count();
        if(input('city_id') == 0){
            $nationwide_user = $nationwide_user ;
        }
        $data['nationwide_user'] = $nationwide_user;

        //今日订单总量
        $todayStart = strtotime(date('Y-m-d 00:00:00', time()));
        $todayEnd = strtotime(date('Y-m-d 23:59:59', time()));

        $order_count = Db::name('order')->alias('u')
//            ->where('u.create_time', 'egt', $todayStart)
//            ->where('u.create_time', 'elt', $todayEnd)
            ->where($where)
            ->where($where6)
            ->count();

        //增加历史订单
        if(input('city_id') == 0){
            $net_sendsingle = Db::name('net_sendsingle')->count();
            $net_sendsingles = Db::name('net_sendsingles')->count();
            $net_sendsinglet = Db::name('net_sendsinglet')->count();
            $net_sendsingler = Db::name('net_sendsingletr')->count();
            //客运订单
            $journey_count = Db::name('journey')->where('times','gt',$todayStart)
                                        ->where('times','lt',$todayEnd)
                                        ->count() ;
            $order_count = $order_count + $net_sendsingle*3 + $net_sendsingles + $net_sendsinglet + $net_sendsingler*3-80000 + $journey_count ;
        }
        $data['order_count'] = $order_count;

        //当前城市业务订单数
        $filiale_order = Db::name('order')->alias('o')
            ->field('b.business_name,count(o.id) as order_count')
            ->join('mx_business b', 'b.id = o.business_id', 'left')
            ->group('o.business_id')
            ->where('o.create_time', 'egt', $todayStart)
            ->where('o.create_time', 'elt', $todayEnd)
            ->where($where3)
            ->where($where5)
            ->select();

        //今日上线司机数
        $conducteur_count = Db::name('conducteur')->alias('c')
            ->join("conducteur_tokinaga t", "c.id=t.conducteur_id", "right")
            ->where('t.day', 'eq', date('Y_m_d'))
            ->where($where1)
            ->where($where7)
            ->count();
        if(input('city_id') == 0){
            $lepin_user = Db::name('lepin_user')->where(['logType' => 1 ])->count();
            $conducteur_count = $conducteur_count + $lepin_user ;
        }
        $data['conducteur_count'] = $conducteur_count;

        //全国营运流水总额
        $order_money = 0 ;
        if(input('city_id') == 0){
            $order_money = Db::name('order')->alias('u')
//                ->where('u.create_time', 'egt', $todayStart)
//                ->where('u.create_time', 'elt', $todayEnd)
                ->where($where)
                ->where($where6)
                ->sum('money');

            $journey_money = Db::name('journey')->alias('j')
                                        ->where($where8)
                                        ->sum('total_price');

            $order_money = $order_money + $journey_money ;
        }else{
           $order_money = Db::name('order')->alias('u')
                        ->where('u.create_time', 'egt', $todayStart)
                        ->where('u.create_time', 'elt', $todayEnd)
                        ->where($where)
                        ->where($where6)
                        ->sum('money');
        }

        if (empty($order_money)) {
            $order_money = 0;
        }
        $data['order_money'] = sprintf("%.2f", $order_money);

        //日期为null   本周
        $times = aweek("", 1);

        $beginThisweek = strtotime($times[0]);
        $endThisweek = strtotime($times[1]);

        $ranking_time = input('ranking_time');
        $evaluate_time = input('evaluate_time');
        $order_time = input('order_time');
        $statistics_time = input('statistics_time');

        $start = "";   //开始时间
        $end = "";    //结束时间
        $start_o = "";   //开始时间
        $end_o = "";    //结束时间
        $start_e = "";   //开始时间
        $end_e = "";    //结束时间
        $start_s = "";   //开始时间
        $end_s = "";    //结束时间

        $Interval = 0; //评价的间隔天数
        $Interval_o = 0; //订单的间隔天数

        if ($ranking_time == "null" || $ranking_time == "null ") {
            $start_r = $beginThisweek;
            $end_r = $endThisweek;
        } else {

            $ranking_times = explode(',', $ranking_time);
            $start_r = strtotime($ranking_times[0]);
            $end_r = strtotime($ranking_times[1]);
        }

        //各城市用户排行榜
        $order = Db::name('order')
            ->alias('u')
            ->join('mx_cn_city c', 'c.id = u.city_id', 'inner')
            ->field('c.name as c_name,count(u.id) as u_count')
            ->where('u.create_time','gt',$start_r)
            ->where('u.create_time','lt',$end_r)
            ->where($where)
            ->where($where6)
            ->group('u.city_id')
            ->order('u_count desc')
            ->select();                                                                     //订单

        $orders = Db::name('journey')
            ->alias('j')
            ->join('mx_cn_city c', 'c.id = j.city_id', 'inner')
            ->field('c.name as c_name,count(j.id) as u_count')
            ->where('j.times','gt',$start_r)
            ->where('j.times','lt',$end_r)
            ->where($where8)
            ->group('j.city_id')
            ->order('u_count desc')
            ->select();

        $ini = [] ;
        foreach ($order as $key=>$value){
            $ini[] = [
                'c_name'=>$value['c_name'],
                'u_count'=>$value['u_count'],
            ];
        }

        foreach ($orders as $k=>$v){
            $ini[] = [
                'c_name'=>$v['c_name'],
                'u_count'=>$v['u_count'],
            ];
        }

        $data['user_rankinglist'] = $ini ;

        if ($evaluate_time == "null"|| $evaluate_time == "null ") {
            $start_e = $beginThisweek;
            $end_e = $endThisweek;

            $Interval = diffBetweenTwoDays((int)$start_e, (int)$end_e);
        } else {
            $evaluate_times = explode(',', $evaluate_time);
            $start_e = strtotime($evaluate_times[0]);
            $end_e = strtotime($evaluate_times[1]);

            $Interval = diffBetweenTwoDays((int)$start_e, (int)$end_e);
        }

        if ($order_time == "null"|| $order_time == "null ") {
            $start_o = $beginThisweek;
            $end_o = $endThisweek;

            $Interval_o = diffBetweenTwoDays((int)$start_o, (int)$end_o);
        } else {
            $order_times = explode(',', $order_time);
            $start_o = strtotime($order_times[0]);
            $end_o = strtotime($order_times[1]);
            $Interval_o = diffBetweenTwoDays((int)$start_o, (int)$end_o);
        }
        if ($statistics_time == "null" || $statistics_time == "null ") {
            $start_s = $beginThisweek;

            $end_s = $endThisweek;

        } else {
            $statistics_times = explode(',', $statistics_time);
            $start_s = strtotime($statistics_times[0]." 00:00:00");
            $end_s = strtotime($statistics_times[1]." 23:59:59");
        }

        //全国评价及投诉走势图
        $reviewOrdersData = [];
        for ($i = 0; $i <= $Interval; $i++) {

            $op = date('Y-m-d', $start_e);
            $day_start = strtotime($op . ' 00:00:00');  //当天开始时间
            $day_end = strtotime($op . ' 23:59:59');    //当天结束时间

            $ini = date("Y-m-d",strtotime("+".$i." day",strtotime($op)));
            $times = date('m',strtotime($ini)). '-' .date('d',strtotime($ini)) ;
//            if((((int)date('d', $start_e)) + $i) <= $days) {
                $reviewOrdersData[] = [
                    'times' =>date('m',strtotime($ini)). '/' .date('d',strtotime($ini)), //date('m', $start_e) . '/' . (((int)date('d', $start_e)) + $i),
                    'evaluate_count' => $this->EvaluateCount($where2,$times),
                    'complain_count' => $this->ComplaintCount($where2,$times),
                ];
//            }
        }

        $data['reviewOrdersData'] = $reviewOrdersData;

        //各城市订单走势图
        $city_name = [] ;
        if(input('city_id') == 0){ //总公司查看订单前三名的城市

            $city_names = Db::name('order')->alias('o')
                ->distinct(true)
                ->field('c.name as c_name,count(o.id) as count')
                ->join('mx_cn_city c', 'c.id = o.city_id', 'inner')
                ->where($where3)
                ->where($where5)
                ->group('o.city_id')
                ->order('count desc')
                ->limit(3)
                ->select();

           $journey_name =  Db::name('journey')->alias('j')
                ->field('c.name as c_name,count(j.id) as count')
                ->join('mx_cn_city c', 'c.id = j.city_id', 'left')
//                ->where($where8)
                ->group('j.city_id')
                ->order('count desc')
                ->limit(1)
                ->select();
//            halt($city_names);
           foreach ($city_names as $key=>$value){
               if($value['count'] > 0){
                   $city_name[] = [
                       'c_name'=>$value['c_name'],
                       'count'=>$value['count'],
                   ];
               }
           }
           foreach ($journey_name as $k=>$v){
               $city_name[] = [
                   'c_name'=>$v['c_name'],
                   'count'=>$v['count'],
               ];
           }
        }else{           //按照分公司
            $city_name = Db::name('order')->alias('o')
                ->distinct(true)
                ->field('c.name as c_name,count(o.id) as count')
                ->join('mx_cn_city c', 'c.id = o.city_id', 'inner')
                ->where($where3)
                ->where($where5)
                ->group('o.city_id')
                ->limit(3)
                ->select();
        }

        $order = [];
        for ($y = 0; $y <= $Interval_o; $y++) {     //行
            $op_o = date('Y-m-d', $start_o);

            $day_start_o = strtotime($op_o . ' 00:00:00');  //当天开始时间
            $day_end_o = strtotime($op_o . ' 23:59:59');    //当天结束时间

            $days =  $this->days(date('m', $start_o) );
            $ini = date("Y-m-d",strtotime("+".$y." day",strtotime($op_o)));
            $times = date('m',strtotime($ini)). '-' .date('d',strtotime($ini)) ;

//            if((((int)date('d', $start_o)) + $y) <= $days) {
                $order[] = [
                    'times' => date('m',strtotime($ini)). '/' .date('d',strtotime($ini)),
                    $city_name[0]['c_name'] => $this->OrderCount($city_name[0]['c_name'],$times),
                    $city_name[1]['c_name'] => $this->OrderCount($city_name[1]['c_name'],$times),
                    $city_name[2]['c_name'] => $this->OrderCount($city_name[2]['c_name'],$times),
                    $city_name[3]['c_name'] => $this->OrderNewCount($city_name[3]['c_name'],$times),
                ];
//            }
        }

        $data['cityOrdersTieData']['city_name'] = $city_name;
        $data['cityOrdersTieData']['order'] = $order;

        //全国新用户注册统计
//        halt($start_s) ;
        $users = Db::name('user')->where($where4)->field('count(*) as cont')
                                                         ->group('logon_tip')
                                                         ->where('create_time','gt',$start_s)
                                                         ->where('create_time','lt',$end_s)
                                                         ->select();


        if (empty($users[0]['cont'])) {
            $users[0]['cont'] = 0;
        }
        if (empty($users[1]['cont'])) {
            $users[1]['cont'] = 0;
        }
        if (empty($users[2]['cont'])) {
            $users[2]['cont'] = 0;
        }

        $statistics = [
            '苹果' => $users[0]['cont'],
            '安卓' => $users[1]['cont'],
            '小程序' => $users[2]['cont']
        ];

        $data['user_statistics'] = $statistics;
        $data['filiale_order'] = $filiale_order;
        return ['code' => APICODE_SUCCESS, 'data' => $data];
    }
    //订单数
    private function OrderCount($city_name,$times){
        $city_id = Db::name('cn_city')->where(['name'=>$city_name])->value('id');
        $day_start = strtotime(date('Y',time())."-".$times." 00:00:00") ;
        $day_end = strtotime(date('Y',time())."-".$times." 23:59:59") ;

        $order_count = Db::name('order')->alias('o')
                                                 ->where(['o.city_id'=>$city_id])
                                                 ->where('o.create_time','gt' , $day_start )
                                                 ->where('o.create_time','lt' , $day_end )
                                                 ->count();
        return $order_count ;
    }
    private function OrderNewCount($city_name,$times){
        $city_id = Db::name('cn_city')->where(['name'=>$city_name])->value('id');
        $day_start = strtotime(date('Y',time())."-".$times." 00:00:00") ;
        $day_end = strtotime(date('Y',time())."-".$times." 23:59:59") ;

        $order_count = Db::name('journey')->alias('o')
            ->where(['o.city_id'=>$city_id])
            ->where('o.times','gt' , $day_start )
            ->where('o.times','lt' , $day_end )
            ->count();
        return $order_count ;
    }

    //评价数
    private function EvaluateCount($where2,$times){
       $day_start = strtotime(date('Y',time())."-".$times." 00:00:00") ;
       $day_end = strtotime(date('Y',time())."-".$times." 23:59:59") ;
        $user_count = Db::name('user_evaluate')
            ->where('create_time', 'egt', $day_start)
            ->where('create_time', 'elt', $day_end)
            ->where($where2)
            ->value('count(*) as cont');  //评价数

        return $user_count;
    }

    //投诉数
    private function ComplaintCount($where2,$times){
        $day_start = strtotime(date('Y',time())."-".$times." 00:00:00") ;
        $day_end = strtotime(date('Y',time())."-".$times." 23:59:59") ;

        $user_complain = Db::name('user_complain')
            ->where('create_time', 'egt', $day_start)
            ->where('create_time', 'elt', $day_end)
            ->where($where2)
            ->value('count(*) as cont');  //投诉数
        return $user_complain ;
    }

    //获取二级
    protected function getAccessPermission($id)
    {
        $ini = [];
        $ini = Db::name('permission')->where(['superior' => $id])->select();
        return $ini;
    }

    //添加角色
    public function addAuthgroup()
    {
        $params = [
            "title" => input('?title') ? input('title') : null,
            "description" => input('?description') ? input('description') : null,
            "company_id" => input('?company_id') ? input('company_id') : null,
            "rules" => input('?rules') ? input('rules') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["title", "rules"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $params['create_time'] = time();
        $authgroup = db('authgroup')->insert($params);

        if ($authgroup) {
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

    //修改权限
    public function updatePermission()
    {
        $params = [
            "id" => input('?id') ? input('id') : null,
            "rules" => input('?rules') ? input('rules') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $res = Db::name('authgroup')->update($params);

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }

    //角色列表
    public function PermissionList()
    {
        $params = [
            "company_id" => input('?company_id') ? input('company_id') : null,
        ];
        return self::pageReturn(db('authgroup'), $params);
    }

    //权限(动态生成导航)
    public function permissionStair()
    {
        $data = [];

        //获取session的id
        $manager_id = 28;// session('manager_id');
        //获取权限数组
        $rules = Db::name('manager')->alias('m')
            ->where(['m.id' => $manager_id])
            ->join('mx_authgroup a', 'm.group_id = a.id', 'left')
            ->value('a.rules');

        $permission = Db::name('permission')->where('id', 'in', $rules)->select();

        $row = $this->get_tree($permission);

        return ['code' => APICODE_SUCCESS, 'data' => $data];
    }

//    //递归
//    public function get_tree($data){
//        $items = array();
//        foreach ($data as $key=>$value){
//            $items[$value['id']] = $value ;
//        }
//        $tree = array();
//        foreach ($items as $k=>$v){
//            if(isset($items[$v['superior']])){
//                $items[$v['superior']]['son'][] = &$item[$k];
//            }else{
//                $tree[] = &$item[$k];
//            }
//        }
//        return $tree ;
//    }

    //递归
    public function get_tree($data)
    {
        $items = array();
        foreach ($data as $key => $value) {
            $items[$value['id']] = $value;
        }
        $tree = array();
        foreach ($items as $k => $v) {
            if (isset($items[$v['superior']])) {
                $items[$v['superior']]['son'][] = &$items[$k];
            } else {
                $tree[] = &$items[$k];
            }
        }
        return $tree;
    }

    //分公司权限列表
    public function filiale_permission_list()
    {
        if (input('?company_id')) {
            $params = [
                "id" => input('company_id')
            ];
            $data = [];
            //获取分公司超级管理员的权限
            $super_id = Db::name('company')->where($params)->value('super_id');                 //超管id
            $group_id = Db::name('manager')->where(['id' => $super_id])->value('group_id');       //角色id
            $rules = Db::name('authgroup')->where(['id ' => $group_id])->value('rules');

            $permission = Db::name('permission')->where('id', 'in', $rules)->select();

            $row = $this->get_tree($permission);

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $row
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "公司ID不能为空"
            ];
        }
    }

    //修改管理员密码
    public function UpdateAdminPassword()
    {
        $params = [
            "id" => input('?id') ? input('id') : null,
            "vikaren_password" => input('?vikaren_password') ? input('vikaren_password') : null,
            "password" => input('?password') ? input('password') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id", "vikaren_password", "password"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $manager = Db::name('manager')->where(['id' => input('id')])->where(['password' => encrypt_salt(input('vikaren_password'))]);

        $ini['id'] = input('id');
        $ini['password'] = encrypt_salt(input('vikaren_password'));

        $res = Db::name('manager')->update($ini);

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }

    //根据id获取管理员信息
    public function getManagerInfo(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('manager')->where($params)->find();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "管理员ID不能为空"
            ];
        }
    }

    //更新管理员信息
    public function UpdateManagerInfo(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "username" => input('?username') ? input('username') : null,
            "nickname" => input('?nickname') ? input('nickname') : null,
            "mobile" => input('?mobile') ? input('mobile') : null,
            "avatar" => input('?avatar') ? input('avatar') : null,
            "group_id" => input('?group_id') ? input('group_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $res = Db::name('manager')->update($params);

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
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