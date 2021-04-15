<?php

/**

 * Created by PhpStorm.

 * User: Administrator

 * Date: 19-2-26

 * Time: 上午10:53

 */

namespace app\api\controller;
use app\api\model\Conducteur;
use app\api\model\Company;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;

class Rewards extends Base
{
    //司机奖励-推荐奖励-待开始
    public function ReferralBonusesTostart(){
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id')
            ];
            $data = db('activity')->field('id,activity_title,end_time')->where(['activity_type'=>2])->select();
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
    //司机奖励-推荐奖励-已结束
    public function ReferralBonusesComplete(){
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id')
            ];
            $data = db('activity')->field('id,activity_title,end_time')->where(['activity_type'=>2])->select();
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

    //活动详情-进行中
    public function ActivityInprogress(){
        if (input('?activity_id')) {
            $params = [
                "activity_id" => input('activity_id'),
                "conducteur_id" => input('conducteur_id'),
            ];

            $conducteur_recommend = db('conducteur_recommend')->field('nickname,create_time,money')->where($params)->select();
            $data['total_money'] = db('conducteur_recommend')->where($params)->sum('money');
            $data['conducteur_recommend'] = $conducteur_recommend  ;
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

    //推荐奖励
    public function recommendAward(){
        if (input('?conducteur_id')) {
            $params = [
                "invitation_id" => input('conducteur_id')
            ];

            $user_count = db('user')->where($params)->count();
            $data['user_count'] = $user_count ;

            $conducteur_count = Db::name('conducteur')->where(['recommend_id'=>input('conducteur_id')])->count();
            $data['conducteur_count'] = $conducteur_count ;

            $distribution_money = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->value('distribution_money');
            $data['marketing_money'] = $distribution_money ;

            return [
                "code" => APICODE_SUCCESS,
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

    //业务列表
    public function BusinessList(){
        $business = Db::name('business')->select();
        return [
            "code" => APICODE_SUCCESS,
            "msg" => "查询成功",
            "data" => $business
        ];
    }

    //判断司机是否接企业订单
    public function JudgmentDriverOrder(){





    }

    //司机余额
    public function CashBalance(){
        if (input('?conducteur_id')) {
            $params = [
                "conducteur_id" => input('conducteur_id')
            ];
            //根据司机获取公司参数
            $company_id = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->value('company_id') ;
            $freeze_balance = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->value('freeze_balance') ;
            $money = Db::name('conducteur_withdraw')->where(['conducteur_id'=>input('conducteur_id')])->where(['state'=>1])->value('sum(money)') ;

            $company = Db::name('company')->where(['id' =>$company_id ])->find() ;
            $minWithdraw = $company['minWithdraw'] ;
            //锁定天数
            $payment_days = $company['payment_days'] ;
            //可提现余额
            $order_money = Db::name('order')->where(['conducteur_id'=>input('conducteur_id')])->value('sum(money)') ;

            //锁定余额
            $conducteur_id = input('conducteur_id') ;
            //可提现余额
            $chauffeur_income_money =  Db::name('order')->where(['conducteur_id'=>$conducteur_id])
                                     ->where('create_time','gt',time()-60*60*24*$payment_days)
                                     ->where('status','in','6,9')
                                     ->value('sum(chauffeur_income_money)') ;

            if(empty($chauffeur_income_money)){
                $chauffeur_income_money = 0 ;
            }
            //锁定余额
            $chauffeur_income_moneys =  Db::name('order')->where(['conducteur_id'=>$conducteur_id])
                                                                  ->where('create_time','lt',time()-60*60*24*$payment_days)
                                                                  ->where('status','in','6,9')
                                                                  ->value('sum(chauffeur_income_money)') ;
            if(empty($chauffeur_income_moneys)){
                $chauffeur_income_moneys = 0 ;
            }

            $data = [
                'withdraw_balance' =>sprintf("%.2f" , ($chauffeur_income_moneys - $freeze_balance - $money) )   ,          //可提现余额
                'locking_balance' => $chauffeur_income_money ,          //锁定余额
                'freeze_balance' => $freeze_balance ,        //冻结余额
                'minWithdraw' => $minWithdraw ,              //单次提现金额最小值
            ] ;
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" =>$data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "企业ID不能为空"
            ];
        }
    }

    //未到账列表
    public function DeliveryAccount(){
        if (input('?conducteur_id')) {
            $params = [
                "conducteur_id" => input('conducteur_id')
            ];

            $company_id = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->value('company_id') ;
            $freeze_balance = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->value('freeze_balance') ;
            $money = Db::name('conducteur_withdraw')->where(['conducteur_id'=>input('conducteur_id')])->where(['state'=>1])->value('sum(money)') ;

            $company = Db::name('company')->where(['id' =>$company_id ])->find() ;
            $minWithdraw = $company['minWithdraw'] ;
            //锁定天数
            $payment_days = $company['payment_days'] ;

            $data =  Db::name('order')->field('id,OrderId,chauffeur_income_money,create_time')->where(['conducteur_id'=>input('conducteur_id')])
                ->where('create_time','gt',time()-60*60*24*$payment_days)
                ->where('status','in','6,9')
                ->where('chauffeur_income_money','gt',0)
                ->select();

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "企业ID不能为空"
            ];
        }
    }

    //查询订单
    public function QueryindentOrder(){
        if (input('?order_id')) {
            $params = [
                "o.id" => input('order_id')
            ];

            $data = Db::name('order')->alias('o')->field('o.id,o.origin,o.Destination,o.DepartTime,o.business_id,b.business_name,o.DepLongitude,o.DepLatitude,o.DestLongitude,o.DestLatitude
            ,u.PassengerPhone as user_phone,o.company_id,u.PassengerName as user_name,u.star,o.user_id,o.status,o.classification')
                ->join('mx_business b', 'b.id = o.business_id', 'left')
                ->join('mx_user u', 'u.id = o.user_id','left')
                ->where($params)
                ->find();

                $arrive_time = Db::name('order_history')->where(['order_id' => $data['id']])->value('arrive_time');
                if (!empty($arrive_time)) {
                    $data['arrive_time'] = $arrive_time;
                } else {
                    $data['arrive_time'] = 0;
                }
            //返回预约延长时间
            $restimatedDelayTime = Db::name('company')->where(['id'=>$data['company_id']])->value('restimatedDelayTime') ;

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

    //司机我的行程（筛选条件）
    public function MyTripsScreening(){
        if (input('?id')) {
            $params = [
                "o.conducteur_id" => input('id')
            ];

//            $data = db('order')->alias('o')->field('o.id,o.origin,o.status,o.Destination,o.create_time,o.money,o.DepLongitude,o.DepLatitude,o.DestLongitude
//            ,o.DestLatitude,o.order_name,u.PassengerPhone as user_phone,u.nickname as user_name,u.id as user_id,u.star,o.classification,u.portrait,o.DepartTime,o.rates,o.total_price,o.surcharge,o.key,o.service,o.terimnal,o.trace,o.tracks')
//                                       ->join('mx_user u','u.id = o.user_id','left')
//                                      ->where($params)->order('o.id desc')->select() ;
            $pageSize = input('?pageSize') ? input('pageSize') : 10;
            $pageNum = input('?pageNum') ? input('pageNum') : 0;

            $data = db('order')->alias('o')->field('o.id,o.origin,o.status,o.Destination,o.create_time,o.money,o.DepLongitude,o.DepLatitude,o.DestLongitude
            ,o.DestLatitude,o.order_name,u.PassengerPhone as user_phone,u.nickname as user_name,u.id as user_id,u.star,o.classification,u.portrait,o.DepartTime,o.rates
            ,o.total_price,o.surcharge,o.key,o.service,o.terimnal,o.trace,o.tracks,o.conducteur_virtual,o.user_virtual')
                ->join('mx_user u','u.id = o.user_id','left')
                ->order('o.id desc')->where(self::filterFilter($params))->order('id desc')->page($pageNum, $pageSize)
                ->select() ;

            foreach ($data as $key=>$value){
                $arrive_time = Db::name('order_history')->where(['order_id'=>$value['id']])->value('arrive_time');
                if(!empty($arrive_time)){
                    $data[$key]['arrive_time'] = $arrive_time ;
                }else{
                    $data[$key]['arrive_time'] = 0 ;
                }
                //在处理tracks
                if($value['tracks'] == null || $value['tracks'] == 'null'){
                    $data[$key]['tracks'] = '' ;
                }
            }

//            $sum = db('order')->alias('o')->field('o.id,o.origin,o.status,o.Destination,o.create_time,o.money,o.DepLongitude,o.DepLatitude,o.DestLongitude
//            ,o.DestLatitude,o.order_name,u.PassengerPhone as user_phone,u.nickname as user_name,u.id as user_id,u.star,o.classification,o.rates,o.total_price,o.surcharge,o.key,o.service,o.terimnal,o.trace,o.tracks')
//                ->join('mx_user u','u.id = o.user_id','left')
//                ->order('o.id desc')->where(self::filterFilter($params))->count();

            $sum = count($data) ;

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "sum" => $sum,
                "msg" => "查询成功",
                "data" =>$data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }
    }

    //是否等待
    public function exhausted(){
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id')
            ];
            $company_id = db('conducteur')->where($params)->value('company_id');
            $is_await =Db::name('company')->where(['id'=>$company_id])->value('is_await');


            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "is_await" => $is_await
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }
    }

    //查询所有未开始行程的订单
    public function NotStartedOrder(){
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
                ->where('o.status', 'eq', "2")
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
                "msg" => "司机ID不能为空"
            ];
        }
    }
}