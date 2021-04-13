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
use think\Request;
class Company extends Base
{
    //公司列表
    public function company_list(){
        $params = [
            "c.city_id" => input('?city_id') ? input('city_id') : null,
        ];

        $where = [] ; $where1 = [] ;
        if(!empty(input('create_time'))){
            $where['c.create_time'] = [ 'gt' , strtotime(input('create_time')." 00:00:00") ] ;
            $where1['c.create_time'] = [ 'lt' , strtotime(input('create_time')." 23:59:59") ] ;
        }
        $where2 = [] ;
        if(!empty(input('CompanyName')) && input('CompanyName') != 'null' ){
            $where2['c.CompanyName'] = ['like','%'.input('CompanyName').'%'] ;
        }
        $where3 = [] ;
        if(!empty(input('city_id')) && input('city_id') != 'null' ){
            $where3['c.city_id'] = ['eq',input('city_id')] ;
        }
        $where4 = [] ;
        if(!empty(input('company_id')) && input('company_id') != 'null' ){
            $where4['c.id'] = ['eq',input('company_id')] ;
        }
        $where5 = [] ;
        if(!empty(input('superior_company')) && input('superior_company') != 'null' ){
            $where5['c.superior_company'] = ['eq',input('superior_company')] ;
        }

        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;

        $sum = db('company')->alias('c')->join('mx_manager m','m.id = c.super_id','left')
            ->join('mx_company y','y.id = c.superior_company','left')
            ->where($where)->where($where1)->where($where2)->where($where3)->where($where4)->where($where5)->count();

        $data = Db::name('company')->alias('c')->join('mx_manager m','m.id = c.super_id','left')
            ->join('mx_company y','y.id = c.superior_company','left')
            ->field('c.*,y.CompanyName as y_CompanyName,m.username as m_username')
            ->order('id desc')->page($pageNum, $pageSize)
            ->where($where)->where($where1)->where($where2)->where($where3)->where($where4)->where($where5)
            ->select();

        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" =>$data
        ];
    }
    //签约公司
    public function SignedCompanyList(){
        $params = [
            "c.city_id" => input('?city_id') ? input('city_id') : null,
        ];

        $where4 = [] ;
        if(!empty(input('company_id')) && input('company_id') != 'null' ){
            $where4['c.id'] = ['eq',input('company_id')] ;
        }
        $where5 = [] ;
        if(!empty(input('superior_company')) && input('superior_company') != 'null' ){
            $where5['c.superior_company'] = ['eq',input('superior_company')] ;
        }

        $data = Db::name('company')->alias('c')->join('mx_manager m','m.id = c.super_id','left')
            ->join('mx_company y','y.id = c.superior_company','left')
            ->field('c.*,y.CompanyName as y_CompanyName,m.username as m_username')
            ->whereOr($where4)
            ->whereOr($where5)
            ->select();

        return [
            "code" => APICODE_SUCCESS,
            "sum" => count($data),
            "data" =>$data
        ];



    }
    //添加公司
    public function add_company(){

        $data = input('');

        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "CompandId" => input('?CompandId') ? input('CompandId') : null,
            "kfdh_phone" => input('?kfdh_phone') ? input('kfdh_phone') : null,
            "ContactAddress" => input('?ContactAddress') ? input('ContactAddress') : null,
            "CompanyName" => input('?CompanyName') ? input('CompanyName') : null,
            "State" => input('?State') ? input('State') : 0,
            "Flag" => input('?Flag') ? input('Flag') : 1,
            "create_time" => input('?create_time') ? input('create_time') : time(),
            "phone" => input('?phone') ? input('phone') : null,
            'Identifier'=>input('?Identifier') ? input('Identifier') : null,
            'BusinessScope'=>input('?BusinessScope') ? input('BusinessScope') : null,
            'EconomicType'=>input('?EconomicType') ? input('EconomicType') : null,
            'RegCapital'=>input('?RegCapital') ? input('RegCapital') : null,
            'LegalName'=>input('?LegalName') ? input('LegalName') : null,
            'LegalPhone'=>input('?LegalPhone') ? input('LegalPhone') : null,
            'LegalPhoto'=>input('?LegalPhoto') ? input('LegalPhoto') : null,
            'dredge_type'=>input('?dredge_type') ? input('dredge_type') : null,
            'supervise_people'=>input('?supervise_people') ? input('supervise_people') : null,
            'merchants_personnel'=>input('?merchants_personnel') ? input('merchants_personnel') : null,
            'superior_company'=>input('?superior_company') ? input('superior_company') : null,
            'shareholder_phone'=>input('?shareholder_phone') ? input('shareholder_phone') : null,
            'proportional'=>input('?proportional') ? input('proportional') : null,
            'headquarters_people'=>input('?headquarters_people') ? input('headquarters_people') : null,
            'headquarters_phone'=>input('?headquarters_phone') ? input('headquarters_phone') : null,
            'headquarters_duty'=>input('?headquarters_duty') ? input('headquarters_duty') : null,
            'is_inservice'=>input('?is_inservice') ? input('is_inservice') : null,
            'key'=>input('?key') ? input('key') : null,
            'is_distribution'=>input('?is_distribution') ? input('is_distribution') : null,
            'Settlement_period'=>input('?Settlement_period') ? input('Settlement_period') : null,
            'longitude'=>input('?longitude') ? input('longitude') : null,
            'latitude'=>input('?latitude') ? input('latitude') : null,
            'merchant'=>input('?merchant') ? input('merchant') : null,
            'payment_days'=>input('?payment_days') ? input('payment_days') : null,
            'limitNumber'=>input('?limitNumber') ? input('limitNumber') : null,
            'minWithdraw'=>input('?minWithdraw') ? input('minWithdraw') : null,
            'restimatedDelayTime'=>input('?restimatedDelayTime') ? input('restimatedDelayTime') : null,
            'is_different'=>input('?is_different') ? input('is_different') : null,
            'is_scope'=>input('?is_scope') ? input('is_scope') : null,
            'is_gps'=>input('?is_gps') ? input('is_gps') : null,
            'is_insideScope'=>input('?is_insideScope') ? input('is_insideScope') : null,
        ];

//        $com = Db::name('company')->where(['city_id'=>input('city_id')])->find();
//        if(!empty($com)){
//            return [
//                'code'=>APICODE_ERROR,
//                'msg'=>'创建失败,城市已存在'
//            ];
//        }

        //处理开通端
        $openup_item =  $data['openup_item'] ? $data['openup_item'] : null ;
//       $open = '' ;
//        if(!empty($openup_item)){
//            foreach ($openup_item as $key=>$value){
//
//                $open .= $value .',' ;
//            }
//        }
//        $openup_items = substr($open,0,-1);
        $params['openup_item'] = $openup_item ;

//        halt($params);
        $params = $this->filterFilter($params);
        $required = ["key"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //处理高德
        $autonavi = $this->ser($params['CompanyName'],'',$params['key']);

        if(!empty($autonavi)){
            $autonavi_n = json_decode($autonavi,true);

            $params['service'] = $autonavi_n['data']['sid'];
        }

        //分销
        $fx_qx = "";
        if( $params['is_distribution'] == 1){
            $fx_qx = ",159,160,161";
        }

        $company = db('company')->insertGetId( $params );

        if($company > 0){
            //权限
//            "11,12,13,14,15,16,18,19,20,21,22,24,26,27,28,29,30,31,32,33,34,37,38,40,41,44,45,46,47,49,51,52,53,54,55,56,57,128,129,130,131,132,133,134,135,136,138,139,140,141,142,143,144,145,146,147,148,149,150,151,152,153,154,155,156".$fx_qx
            $rules = "11,12,13,14,29,30,31,44,45,46,47,55,56,57,70,71,72,77,83,94,100,101";

            //权限列表
            $permissios = ",151,152,153" ;

            $authgroup = [
                'title'=>"超管权限",
                'rules'=>$rules.$fx_qx.$permissios,
                'description'=>"",
                'status'=>1,
                'company_id'=>$company,
                'create_time'=>time(),
            ];
            $authgroup_id = Db::name('authgroup')->insertGetId($authgroup);

            //添加一个超级管理员
            $inii['username'] = "admin@".input('CompandId') ;
            $inii['password'] = encrypt_salt("123456") ;
            $inii['mobile'] =  input('phone');
            $inii['create_time'] = time();
            $inii['company_id'] = $company;
            $inii['nickname'] = input('CompanyName');
            $inii['group_id'] = $authgroup_id;
            $inii['city_id'] = input('city_id');
            $mamager_id = Db::name('manager')->insertGetId($inii);

            $com = [] ;
            $com['id'] = $company ;
            $com['super_id'] = $mamager_id ;

            Db::name('company')->update($com);

            $business = $data['business']? $data['business'] : null;          //开通业务
            $ratio = $data['ratio']? $data['ratio'] : null ;               //抽成业务
            $charge = $data['charge']? $data['charge'] : null;             //收费数组
            $accessory = $data['accessory']? $data['accessory'] : null;       //附件数组
            $rates = $data['rates']? $data['rates'] : null;                //实时计价规则
            $appointmentRates = $data['appointmentRates']? $data['appointmentRates'] : null; //预约计价规则
            $elimination = $data['elimination']? $data['elimination'] : null ;  //消单规则(实时)
            $appointment = $data['appointment']? $data['appointment'] : null ;  //消单规则(预约)
            $consumption = $data['consumption']? $data['consumption'] : null ;  //最低消费
            $bounds = $data['bounds']? $data['bounds'] : null ;  //城市范围

            $distribution = $data['distribution']? $data['distribution'] : null ;  //分销
//            halt($distribution);
            $payMethod = $data['payMethod']? $data['payMethod'] : null ;  //出租车付款方式
            $ratesSectionPriceForm = $data['ratesSectionPriceForm']? $data['ratesSectionPriceForm'] : null ;  //实时等待
            $appointmentRatesSectionPriceForm = $data['appointmentRatesSectionPriceForm']? $data['appointmentRatesSectionPriceForm'] : null ;  //出租车付款方式
            $insideBounds = $data['insideBounds']? $data['insideBounds'] : null ;  //城内范围

            $pay = [] ;
            if(!empty($payMethod)){
                $pay['is_lineon'] = $payMethod['is_lineon'] ;
                $pay['is_offline'] = $payMethod['is_offline'] ;
                $pay['company_id'] = $company;
                Db::name('company_paymethod')->insert($pay);
            }

            $ini = [] ;
            if(!empty($distribution)){
                if($distribution['type'] == 1){
                    $ini['type'] = $distribution['type'];
                    $ini['ratio'] = $distribution['ratio'];
                    $ini['company_id'] = $company;
                    Db::name('company_distribution')->insert($ini);

                }else if($distribution['type'] == 2){
                    $ini['type'] = $distribution['type'];
                    $ini['company_id'] = $company;
                    $company_distribution_id = Db::name('company_distribution')->insertGetId($ini);

                    $interval = $distribution['detail'];
                    $ini_dis = [] ;
                    foreach ($interval as $kd=>$vd){
                        $ini_dis[] = [
                            'company_distribution_id'=>$company_distribution_id,
                            'range_one'=>$vd['range_one'],
                            'range_two'=>$vd['range_two'],
                            'ratio'=>$vd['ratio'],
                        ];
                    }
                    Db::name('company_distribution_detail')->insertAll($ini_dis);
                }
            }

//            $this->scopes($insideBounds,$company,"城内") ;  //城内
//            $this->scope($bounds,$company,"城外") ;  //城外
            //halt($rates);
            $this->savecompany($business,$ratio,$charge,$accessory,$rates,$elimination,$company,$appointment,$consumption,$appointmentRates,$ratesSectionPriceForm,$appointmentRatesSectionPriceForm);

            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'创建成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'创建失败'
            ];
        }
    }
    public function scope($bounds,$company,$title,$business_id){
        $scope = "" ;
        foreach ($bounds[0] as $key=>$value){
            $scope .= $value['lng'].",".$value['lat']."-" ;
        }
        $scope = substr($scope,0,-1) ;

        $scopes['company_id'] = $company ;
        $scopes['scope'] = $scope ;
        $scopes['titles'] = $title ;
        $scopes['business_id'] = $business_id ;

        Db::name('company_scope')->insert($scopes) ;
    }
    public function scopes($bounds,$company,$title,$business_id){
        $scope = "" ;
        foreach ($bounds as $key=>$value){
            $scope .= $value['lng'].",".$value['lat']."-" ;
        }
        $scope = substr($scope,0,-1) ;

        $scopes['company_id'] = $company ;
        $scopes['scope'] = $scope ;
        $scopes['titles'] = $title ;
        $scopes['business_id'] = $business_id ;

        Db::name('company_scope')->insert($scopes) ;
    }
    //根据id获取公司详情
    public function getCompany(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = [] ;
            //公司信息
            $company = db('company')->where($params)->find();

            //开通端
            $openup_item = explode(',',$company['openup_item'] ) ;
            $company['openup_item'] = $openup_item ;
            $data = $company ;
            //收费
            $data['charge'] = Db::name('company_charge')->where(['company_id'=>input('id')])->select();
            //附件
            $data['accessory'] = Db::name('company_accessory')->where(['company_id'=>input('id')])->select();
            //业务
            $data['business'] = Db::name('company_business')->where(['company_id'=>input('id')])->select();
            //抽成
            $company_ratios = Db::name('company_ratio')->where(['company_id'=>input('id')])->select();
            foreach ($company_ratios as $kkk=>$vvv){
                if($vvv['business_id'] == 10){
                    $company_ratios[$kkk]['businesstype_id'] = -2;
                }else{
                    $company_ratios[$kkk]['businesstype_id'] = $vvv['businesstype_id'];
                }
            }
            $data['ratio'] = $company_ratios ;
            //实时计价规则
            $company_rates = Db::name('company_rates')->where(['company_id'=>input('id')])->select();
            //预约计价规则
            $company_appointment_rates = Db::name('company_appointment_rates')->where(['company_id'=>input('id')])->select();

            $ini = [] ;
            foreach ($company_rates as $key=>$value){
                $ini[$key]['Normal_starting_time']=[                                       //起步时长
                    'festiva'=>$value['HolidaysStartingTokinaga'],
                    'festivalMorningPeak'=>$value['HolidaysMorningStartingTokinaga'],
                    'festivalEveningPeak'=>$value['HolidaysEveningStartingTokinaga'],
                    'festivalMidnight'=>$value['HolidaysWeehoursStartingTokinaga'],
                    'usually'=>$value['Tokinaga'],
                    'usuallyMorningPeak'=>$value['MorningTokinaga'],
                    'usuallyEveningPeak'=>$value['EveningTokinaga'],
                    'usuallyMidnight'=>$value['WeehoursTokinaga'],
                    'festivaLateNightPeak'=>$value['HolidaysNightStartingTokinaga'],
                    'usuallyLateNightPeak'=>$value['NightStartingTokinaga']
                ];
                $ini[$key]['Remote_km']=[                                              //远途公里
                    'festiva'=>$value['HolidaysStartingKilometre'],
                    'festivalMorningPeak'=>$value['HolidaysMorningLongKilometers'],
                    'festivalEveningPeak'=>$value['HolidaysEveningLongKilometers'],
                    'festivalMidnight'=>$value['HolidaysWeehoursLongKilometers'],
                    'usually'=>$value['LongKilometers'],
                    'usuallyMorningPeak'=>$value['MorningLongKilometers'],
                    'usuallyEveningPeak'=>$value['EveningLongKilometers'],
                    'usuallyMidnight'=>$value['WeehoursLongKilometers'],
                    'festivaLateNightPeak'=>$value['HolidaysNightLongKilometers'],
                    'usuallyLateNightPeak'=>$value['NightLongKilometers']
                ];
                $ini[$key]['remote_fee'] = [           //远途费
                    'festiva'=>$value['HolidaysStartingLongfee'],
                    'festivalMorningPeak'=>$value['HolidaysMorningLongfee'],
                    'festivalEveningPeak'=>$value['HolidaysEveningLongfee'],
                    'festivalMidnight'=>$value['HolidaysWeehoursLongfee'],
                    'usually'=>$value['Longfee'],
                    'usuallyMorningPeak'=>$value['MorningLongLongfee'],
                    'usuallyEveningPeak'=>$value['EveningLongLongfee'],
                    'usuallyMidnight'=>$value['WeehoursLongLongfee'],
                    'festivaLateNightPeak'=>$value['HolidaysNightLongfee'],
                    'usuallyLateNightPeak'=>$value['NightLongfee']
                ];
                $ini[$key]['StartFare'] = [             //起步价
                    'festiva'=>$value['HolidaysStartFare'],
                    'festivalMorningPeak'=>$value['HolidaysMorningStartFare'],
                    'festivalEveningPeak'=>$value['HolidaysEveningStartFare'],
                    'festivalMidnight'=>$value['HolidaysWeehoursStartFare'],
                    'usually'=>$value['StartFare'],
                    'usuallyMorningPeak'=>$value['MorningStartFare'],
                    'usuallyEveningPeak'=>$value['EveningStartFare'],
                    'usuallyMidnight'=>$value['WeehoursStartFare'],
                    'festivaLateNightPeak'=>$value['HolidaysNightStartFare'],
                    'usuallyLateNightPeak'=>$value['NightStartFare']
                ] ;
                $ini[$key]['StartMile'] = [            //起步里程
                    'festiva'=>$value['HolidaysStartMile'],
                    'festivalMorningPeak'=>$value['HolidaysMorningStartMile'],
                    'festivalEveningPeak'=>$value['HolidaysEveningStartMile'],
                    'festivalMidnight'=>$value['HolidaysWeehoursStartMile'],
                    'usually'=>$value['StartMile'],
                    'usuallyMorningPeak'=>$value['MorningStartMile'],
                    'usuallyEveningPeak'=>$value['EveningStartMile'],
                    'usuallyMidnight'=>$value['WeehoursStartMile'],
                    'festivaLateNightPeak'=>$value['HolidaysNightStartMile'],
                    'usuallyLateNightPeak'=>$value['NightStartMile']
                ];
                $ini[$key]['mileage_fee'] = [              //里程费
                    'festiva'=>$value['HolidaysMileageFee'],
                    'festivalMorningPeak'=>$value['HolidaysMorningMileageFee'],
                    'festivalEveningPeak'=>$value['HolidaysEveningMileageFee'],
                    'festivalMidnight'=>$value['HolidaysWeehoursMileageFee'],
                    'usually'=>$value['MileageFee'],
                    'usuallyMorningPeak'=>$value['MorningMileageFee'],
                    'usuallyEveningPeak'=>$value['EveningMileageFee'],
                    'usuallyMidnight'=>$value['WeehoursMileageFee'],
                    'festivaLateNightPeak'=>$value['HolidaysNightMileageFee'],
                    'usuallyLateNightPeak'=>$value['NightMileageFee']
                ];
                $ini[$key]['how_fee'] = [                     //时长费
                    'festiva'=>$value['HolidaysHowFee'],
                    'festivalMorningPeak'=>$value['HolidaysMorningHowFee'],
                    'festivalEveningPeak'=>$value['HolidaysEveningHowFee'],
                    'festivalMidnight'=>$value['HolidaysWeehoursHowFee'],
                    'usually'=>$value['HowFee'],
                    'usuallyMorningPeak'=>$value['MorningHowFee'],
                    'usuallyEveningPeak'=>$value['EveningHowFee'],
                    'usuallyMidnight'=>$value['WeehoursHowFee'],
                    'festivaLateNightPeak'=>$value['HolidaysNightHowFee'],
                    'usuallyLateNightPeak'=>$value['NightHowFee']
                ];
                $ini[$key]['business_id'] = $value['business_id'];
                $ini[$key]['businesstype_id'] = $value['businesstype_id'];

                $ini[$key]['times'] = [              //时间
                    'MorningPeakTimeOn'=>$value['MorningPeakTimeOn'],
                    'MorningPeakTimeOff'=>$value['MorningPeakTimeOff'],
                    'EveningPeakTimeOn'=>$value['EveningPeakTimeOn'],
                    'EveningPeakTimeOff'=>$value['EveningPeakTimeOff'],
                    'UsuallyTimeOn'=>$value['UsuallyTimeOn'],
                    'UsuallyTimeOff'=>$value['UsuallyTimeOff'],
                    'weehoursOn'=>$value['weehoursOn'],
                    'weehoursOff'=>$value['weehoursOff'],
                    'HolidaysMorningOn'=>$value['HolidaysMorningOn'],
                    'HolidaysMorningOff'=>$value['HolidaysMorningOff'],
                    'HolidaysEveningOn'=>$value['HolidaysEveningOn'],
                    'HolidaysEveningOff'=>$value['HolidaysEveningOff'],
                    'HolidaysUsuallyOn'=>$value['HolidaysUsuallyOn'],
                    'HolidaysUsuallyOff'=>$value['HolidaysUsuallyOff'],
                    'HolidaysWeeOn'=>$value['HolidaysWeeOn'],
                    'HolidaysWeeOff'=>$value['HolidaysWeeOff'],
                    'HolidaysLateNightOn'=>$value['HolidaysLateNightOn'],
                    'HolidaysLateNightOff'=>$value['HolidaysLateNightOff'],
                    'UsuallyLateNightOn'=>$value['UsuallyLateNightOn'],
                    'UsuallyLateNightOff'=>$value['UsuallyLateNightOff']
                ];
                $ini[$key]['title'] = $value['titles'] ;
            }

            $inii = [] ;
            foreach ($company_appointment_rates as $k=>$v){
                $inii[$k]['Normal_starting_time']=[                                       //起步时长
                    'festiva'=>$v['HolidaysStartingTokinaga'],
                    'festivalMorningPeak'=>$v['HolidaysMorningStartingTokinaga'],
                    'festivalEveningPeak'=>$v['HolidaysEveningStartingTokinaga'],
                    'festivalMidnight'=>$v['HolidaysWeehoursStartingTokinaga'],
                    'usually'=>$v['Tokinaga'],
                    'usuallyMorningPeak'=>$v['MorningTokinaga'],
                    'usuallyEveningPeak'=>$v['EveningTokinaga'],
                    'usuallyMidnight'=>$v['WeehoursTokinaga'],
                    'festivaLateNightPeak'=>$v['HolidaysNightStartingTokinaga'],
                    'usuallyLateNightPeak'=>$v['NightStartingTokinaga']
                ];
                $inii[$k]['Remote_km']=[                                              //远途公里
                    'festiva'=>$v['HolidaysStartingKilometre'],
                    'festivalMorningPeak'=>$v['HolidaysMorningLongKilometers'],
                    'festivalEveningPeak'=>$v['HolidaysEveningLongKilometers'],
                    'festivalMidnight'=>$v['HolidaysWeehoursLongKilometers'],
                    'usually'=>$v['LongKilometers'],
                    'usuallyMorningPeak'=>$v['MorningLongKilometers'],
                    'usuallyEveningPeak'=>$v['EveningLongKilometers'],
                    'usuallyMidnight'=>$v['WeehoursLongKilometers'],
                    'festivaLateNightPeak'=>$v['HolidaysNightLongKilometers'],
                    'usuallyLateNightPeak'=>$v['NightLongKilometers']
                ];
                $inii[$k]['remote_fee'] = [           //远途费
                    'festiva'=>$v['HolidaysStartingLongfee'],
                    'festivalMorningPeak'=>$v['HolidaysMorningLongfee'],
                    'festivalEveningPeak'=>$v['HolidaysEveningLongfee'],
                    'festivalMidnight'=>$v['HolidaysWeehoursLongfee'],
                    'usually'=>$v['Longfee'],
                    'usuallyMorningPeak'=>$v['MorningLongLongfee'],
                    'usuallyEveningPeak'=>$v['EveningLongLongfee'],
                    'usuallyMidnight'=>$v['WeehoursLongLongfee'],
                    'festivaLateNightPeak'=>$v['HolidaysNightLongfee'],
                    'usuallyLateNightPeak'=>$v['NightLongfee']
                ];
                $inii[$k]['StartFare'] = [             //起步价
                    'festiva'=>$v['HolidaysStartFare'],
                    'festivalMorningPeak'=>$v['HolidaysMorningStartFare'],
                    'festivalEveningPeak'=>$v['HolidaysEveningStartFare'],
                    'festivalMidnight'=>$v['HolidaysWeehoursStartFare'],
                    'usually'=>$v['StartFare'],
                    'usuallyMorningPeak'=>$v['MorningStartFare'],
                    'usuallyEveningPeak'=>$v['EveningStartFare'],
                    'usuallyMidnight'=>$v['WeehoursStartFare'],
                    'festivaLateNightPeak'=>$v['HolidaysNightStartFare'],
                    'usuallyLateNightPeak'=>$v['NightStartFare']
                ] ;
                $inii[$k]['StartMile'] = [            //起步里程
                    'festiva'=>$v['HolidaysStartMile'],
                    'festivalMorningPeak'=>$v['HolidaysMorningStartMile'],
                    'festivalEveningPeak'=>$v['HolidaysEveningStartMile'],
                    'festivalMidnight'=>$v['HolidaysWeehoursStartMile'],
                    'usually'=>$v['StartMile'],
                    'usuallyMorningPeak'=>$v['MorningStartMile'],
                    'usuallyEveningPeak'=>$v['EveningStartMile'],
                    'usuallyMidnight'=>$v['WeehoursStartMile'],
                    'festivaLateNightPeak'=>$v['HolidaysNightStartMile'],
                    'usuallyLateNightPeak'=>$v['NightStartMile']
                ];
                $inii[$k]['mileage_fee'] = [              //里程费
                    'festiva'=>$v['HolidaysMileageFee'],
                    'festivalMorningPeak'=>$v['HolidaysMorningMileageFee'],
                    'festivalEveningPeak'=>$v['HolidaysEveningMileageFee'],
                    'festivalMidnight'=>$v['HolidaysWeehoursMileageFee'],
                    'usually'=>$v['MileageFee'],
                    'usuallyMorningPeak'=>$v['MorningMileageFee'],
                    'usuallyEveningPeak'=>$v['EveningMileageFee'],
                    'usuallyMidnight'=>$v['WeehoursMileageFee'],
                    'festivaLateNightPeak'=>$v['HolidaysNightMileageFee'],
                    'usuallyLateNightPeak'=>$v['NightMileageFee']
                ];
                $inii[$k]['how_fee'] = [                     //时长费
                    'festiva'=>$v['HolidaysHowFee'],
                    'festivalMorningPeak'=>$v['HolidaysMorningHowFee'],
                    'festivalEveningPeak'=>$v['HolidaysEveningHowFee'],
                    'festivalMidnight'=>$v['HolidaysWeehoursHowFee'],
                    'usually'=>$v['HowFee'],
                    'usuallyMorningPeak'=>$v['MorningHowFee'],
                    'usuallyEveningPeak'=>$v['EveningHowFee'],
                    'usuallyMidnight'=>$v['WeehoursHowFee'],
                    'festivaLateNightPeak'=>$v['HolidaysNightHowFee'],
                    'usuallyLateNightPeak'=>$v['NightHowFee']
                ];
                $inii[$k]['business_id'] = $v['business_id'];
                if($v['business_id'] == 10){
                    $inii[$k]['businesstype_id'] = -2;
                }else{
                    $inii[$k]['businesstype_id'] = $v['businesstype_id'];
                }

                $inii[$k]['times'] = [              //时间
                    'MorningPeakTimeOn'=>$v['MorningPeakTimeOn'],
                    'MorningPeakTimeOff'=>$v['MorningPeakTimeOff'],
                    'EveningPeakTimeOn'=>$v['EveningPeakTimeOn'],
                    'EveningPeakTimeOff'=>$v['EveningPeakTimeOff'],
                    'UsuallyTimeOn'=>$v['UsuallyTimeOn'],
                    'UsuallyTimeOff'=>$v['UsuallyTimeOff'],
                    'weehoursOn'=>$v['weehoursOn'],
                    'weehoursOff'=>$v['weehoursOff'],
                    'HolidaysMorningOn'=>$v['HolidaysMorningOn'],
                    'HolidaysMorningOff'=>$v['HolidaysMorningOff'],
                    'HolidaysEveningOn'=>$v['HolidaysEveningOn'],
                    'HolidaysEveningOff'=>$v['HolidaysEveningOff'],
                    'HolidaysUsuallyOn'=>$v['HolidaysUsuallyOn'],
                    'HolidaysUsuallyOff'=>$v['HolidaysUsuallyOff'],
                    'HolidaysWeeOn'=>$v['HolidaysWeeOn'],
                    'HolidaysWeeOff'=>$v['HolidaysWeeOff'],
                    'HolidaysLateNightOn'=>$v['HolidaysLateNightOn'],
                    'HolidaysLateNightOff'=>$v['HolidaysLateNightOff'],
                    'UsuallyLateNightOn'=>$v['UsuallyLateNightOn'],
                    'UsuallyLateNightOff'=>$v['UsuallyLateNightOff']
                ];
                $inii[$k]['title'] = $v['titles'] ;
            }

            $data['rates'] = $ini;
            $data['appointmentRates'] = $inii;
            //消单（实时）
            $data['elimination'] = Db::name('company_elimination')->where(['company_id'=>input('id')])->select();
            //消单（预约）
            $company_appointments =  Db::name('company_appointment')->where(['company_id'=>input('id')])->select();
            foreach ($company_appointments as $key=>$value){
                if($value['business_id'] == 10){
                    $company_appointments[$key]['businesstype_id'] = -2;
                }else{
                    $company_appointments[$key]['businesstype_id'] = $value['businesstype_id'];
                }
            }

            $data['appointment'] = $company_appointments ;

            //最低消费
            $company_consumptions = Db::name('company_consumption')->where(['company_id'=>input('id')])->select() ;
            foreach ($company_consumptions as $kk=>$vv){
                if($vv['business_id'] == 10){
                    $company_consumptions[$kk]['businesstype_id'] = -2;
                }else{
                    $company_consumptions[$kk]['businesstype_id'] = $vv['businesstype_id'];
                }
            }
            $data['consumption'] = $company_consumptions ;

            //分销
            $data['distribution'] = Db::name('company_distribution')->where(['company_id'=>input('id')])->find();
            //分销明细
            $data['distribution']['detail'] = Db::name('company_distribution_detail')->where(['company_distribution_id'=>$data['distribution']['id']])->select() ;

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "公司ID不能为空"
            ];
        }
    }
    //修改公司
    public function updateCompany(){
        $data = input('');

        $params = [
            "id" => input('?id') ? intval(input('id')) : null,
            "CompandId" => input('?CompandId') ? input('CompandId') : null,
            "kfdh_phone" => input('?kfdh_phone') ? input('kfdh_phone') : null,
            "ContactAddress" => input('?ContactAddress') ? input('ContactAddress') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "CompanyName" => input('?CompanyName') ? input('CompanyName') : null,
            "State" => input('?State') ? input('State') : 0,
            "Flag" => input('?Flag') ? input('Flag') : 1,
            "create_time" => input('?create_time') ? input('create_time') : time(),
            "phone" => input('?phone') ? input('phone') : null,
            'Identifier'=>input('?Identifier') ? input('Identifier') : null,
            'BusinessScope'=>input('?BusinessScope') ? input('BusinessScope') : null,
            'EconomicType'=>input('?EconomicType') ? input('EconomicType') : null,
            'RegCapital'=>input('?RegCapital') ? input('RegCapital') : null,
            'LegalName'=>input('?LegalName') ? input('LegalName') : null,
            'LegalPhone'=>input('?LegalPhone') ? input('LegalPhone') : null,
            'LegalPhoto'=>input('?LegalPhoto') ? input('LegalPhoto') : null,
            'dredge_type'=>input('?dredge_type') ? input('dredge_type') : null,
            'supervise_people'=>input('?supervise_people') ? input('supervise_people') : null,
            'merchants_personnel'=>input('?merchants_personnel') ? input('merchants_personnel') : null,
            'superior_company'=>input('?superior_company') ? input('superior_company') : null,
            'shareholder_phone'=>input('?shareholder_phone') ? input('shareholder_phone') : null,
            'proportional'=>input('?proportional') ? input('proportional') : null,
            'headquarters_people'=>input('?headquarters_people') ? input('headquarters_people') : null,
            'headquarters_phone'=>input('?headquarters_phone') ? input('headquarters_phone') : null,
            'headquarters_duty'=>input('?headquarters_duty') ? input('headquarters_duty') : null,
            'is_inservice'=>input('?is_inservice') ? input('is_inservice') : null,
            'key'=>input('?key') ? input('key') : null,
            'is_distribution'=>input('?is_distribution') ? input('is_distribution') : null,
            'longitude'=>input('?longitude') ? input('longitude') : null,
            'latitude'=>input('?latitude') ? input('latitude') : null,
            'merchant'=>input('?merchant') ? input('merchant') : null,
            'restimatedDelayTime'=>input('?restimatedDelayTime') ? input('restimatedDelayTime') : null,
            'is_different'=>input('?is_different') ? input('is_different') : null,
            'is_scope'=>input('?is_scope') ? input('is_scope') : null,
            'is_gps'=>input('?is_gps') ? input('is_gps') : null,
            'is_insideScope'=>input('?is_insideScope') ? input('is_insideScope') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $id = $params["id"];

        //处理开通端
        $openup_item =  $data['openup_item'] ? $data['openup_item'] : null ;
        $params['openup_item'] = $openup_item ;

        //处理高德
        //key和数据里相同就不用修改了
        $comany = Db::name('company')->where(['key' => $params['key']])->where(['id'=>$id])->find();
        if(empty($comany)){
            $autonavi = $this->ser($params['CompanyName'],'',$params['key']);
            if(!empty($autonavi)){
                $autonavi_n = json_decode($autonavi,true);
                $params['service'] = $autonavi_n['data']['sid'];
            }
        }

        $res = Db::name('company')->update($params);

        //删除中间数据
        $this->delcompany($id);

        $business = $data['business']? $data['business'] : null;          //开通业务
        $ratio = $data['ratio']? $data['ratio'] : null ;               //抽成业务
        $charge = $data['charge']? $data['charge'] : null;             //收费数组
        $accessory = $data['accessory']? $data['accessory'] : null;       //附件数组
        $rates = $data['rates']? $data['rates'] : null;                //实时计价规则
        $appointmentRates = $data['appointmentRates']? $data['appointmentRates'] : null; //预约计价规则
        $elimination = $data['elimination']? $data['elimination'] : null ;  //消单规则(实时)
        $appointment = $data['appointment']? $data['appointment'] : null ;  //消单规则(预约)
        $consumption = $data['consumption']? $data['consumption'] : null ;  //最低消费
        $distribution = $data['distribution']? $data['distribution'] : null ;  //分销
        $payMethod = $data['payMethod']? $data['payMethod'] : null ;            //出租车付费方式
        $bounds = $data['bounds']? $data['bounds'] : null ;  //城市范围
        $ratesSectionPriceForm = $data['ratesSectionPriceForm']? $data['ratesSectionPriceForm'] : null ;  //实时等待
        $appointmentRatesSectionPriceForm = $data['appointmentRatesSectionPriceForm']? $data['appointmentRatesSectionPriceForm'] : null ;  //出租车付款方式
        $insideBounds = $data['insideBounds']? $data['insideBounds'] : null ;  //城内范围

        $ini = [] ;
        if(!empty($distribution)){
            if($distribution['type'] == 1){
                $ini['type'] = $distribution['type'];
                $ini['ratio'] = $distribution['ratio'];
                $ini['company_id'] = intval(input('id'));
                Db::name('company_distribution')->insert($ini);

            }else if($distribution['type'] == 2){
                $ini['type'] = $distribution['type'];
                $ini['company_id'] = intval(input('id'));
                $company_distribution_id = Db::name('company_distribution')->insertGetId($ini);

                $interval = $distribution['detail'];
                $ini_dis = [] ;
                foreach ($interval as $kd=>$vd){
                    $ini_dis[] = [
                        'company_distribution_id'=>$company_distribution_id,
                        'range_one'=>$vd['range_one'],
                        'range_two'=>$vd['range_two'],
                        'ratio'=>$vd['ratio'],
                    ];
                }
                Db::name('company_distribution_detail')->insertAll($ini_dis);
            }
        }

        $pay = [] ;
        if(!empty($payMethod)){
            $pay['is_lineon'] = $payMethod['is_lineon'] ;
            $pay['is_offline'] = $payMethod['is_offline'] ;
            $pay['company_id'] = intval(input('id'));
            Db::name('company_paymethod')->insert($pay);
        }

//            //获取超管id
        $super_id = Db::name('company')->where(['id'=>intval(input('id'))])->value('super_id');
        $group_id = Db::name('manager')->where(['id'=>$super_id])->value('group_id');
        $rules = Db::name('authgroup')->where(['id'=>$group_id])->value('rules');
//
        $fx_qx = "";
        if( $params['is_distribution'] == 1){
            $authgroup = Db::name('authgroup')->where('rules','in','159')->where(['id'=>$group_id])->find();
            if(empty($authgroup)){             //判断是否设置过一次。
                $fx_qx = ",159,160,161";
            }
        }else if($params['is_distribution'] == 0){
            $authgroup = Db::name('authgroup')->where('rules','in','159')->where(['id'=>$group_id])->find();
            if(!empty($authgroup)){             //存在，就要移除权限
                $rules = "11,12,13,14,29,30,31,44,45,46,47,55,56,57,151,152,153";
            }
        }
        $authgroup = [
            'id'=>$group_id,
            'rules'=>$rules.$fx_qx
        ];
        $authgroup_id = Db::name('authgroup')->update($authgroup);
        //范围
//        $this->scopes($insideBounds,$id,"城内") ;  //城内
//        $this->scope($bounds,$id,"城外") ;        //城外
        $this->savecompany($business,$ratio,$charge,$accessory,$rates,$elimination,$id,$appointment,$consumption,$appointmentRates,$ratesSectionPriceForm,$appointmentRatesSectionPriceForm);

        return [
            "code" => APICODE_SUCCESS,
            "msg" => "更新成功",
        ];
    }
    //保存公司除基础数据
    protected function savecompany($business,$ratio,$charge,$accessory,$rates,$elimination,$company,$appointment,$consumption,$appointmentRates,$ratesSectionPriceForm,$appointmentRatesSectionPriceForm){
        if(!empty($business)){
            $inib = [] ;
            foreach ($business as $kq=>$vq){
                $inib['business_id'] = $vq['business_id'] ;
                $inib['alias'] = $vq['alias'] ;
                $inib['company_id'] = $company ;
                Db::name('company_business')->insert( $inib );
                if(!empty($business[0]['bargainConfig'])){
                    if(!empty($business[0]['bargainConfig']['insideBounds'])){      //城内
                        $this->scopes($business[0]['bargainConfig']['insideBounds'],$company,"城内",$vq['business_id']) ;        //城内
                    }
                    if(!empty($business[0]['bargainConfig']['bounds'])){             //城外
                        $this->scope($business[0]['bargainConfig']['bounds'],$company,"城外",$vq['business_id']) ;  //城外
                    }
                }
            }
        }
        if(!empty($ratio)){
            $inir = [] ;
            foreach ($ratio as $kr=>$vr){
                if($vr['businesstype_id'] < 0){
                    $vr['businesstype_id'] = 0 ;
                }
                $inir['business_id'] = $vr['business_id'];
                $inir['businesstype_id'] = $vr['businesstype_id'];
                $inir['parent_company_ratio'] = $vr['parent_company_ratio'];
                $inir['filiale_company_ratio'] = $vr['filiale_company_ratio'];
                $inir['company_ratio'] = $vr['company_ratio'];
                $inir['company_id'] =$company;

                $inir['mt_parent_company_ratio'] = $vr['mt_parent_company_ratio'];
                $inir['mt_filiale_company_ratio'] = $vr['mt_filiale_company_ratio'];
                $inir['mt_company_ratio'] = $vr['mt_company_ratio'];
                Db::name('company_ratio')->insert($inir);
            }
        }
        if(!empty($charge)){
            $ch = [] ;
            foreach ($charge as $k=>$v){
                $ch['charges_item'] = $v['charge_item'] ;
                $ch['charge_period'] = $v['charge_period'] ;
                $ch['charge_money'] = $v['charge_money'] ;
                $ch['times'] = time() ;
                $ch['company_id'] = $company ;
                $ch['mark'] = $v['mark'] ;
                Db::name('company_charge')->insert($ch);
            }
        }
        if(!empty($accessory)){
            $ac = [] ;

            foreach ($accessory as $kk=>$vv){
//                $file = $request->file('attachment_name');
//                halt($vv['attachment_name']);
                $ac['attachment_name'] = $vv['attachment_name'] ;
                $ac['attachment_explain'] = $vv['attachment_explain'] ;
                $ac['uploading_time'] = time() ;
                $ac['uploading_people'] = $vv['uploading_people'] ;
                $ac['company_id'] = $company ;
                Db::name('company_accessory')->insert($ac) ;
            }
        }
        if(!empty($rates)){
            $init = [] ;
            foreach ($rates as $kt=>$vt) {
                if(!empty($vt['Normal_starting_time'])){           //起步时长
                    $init['HolidaysWeehoursStartingTokinaga'] = $vt['Normal_starting_time']['festivalMidnight'] ;
                    $init['HolidaysMorningStartingTokinaga'] = $vt['Normal_starting_time']['festivalMorningPeak'] ;
                    $init['HolidaysEveningStartingTokinaga'] = $vt['Normal_starting_time']['festivalEveningPeak'] ;
                    $init['HolidaysStartingTokinaga'] = $vt['Normal_starting_time']['festiva'] ;
                    $init['Tokinaga'] = $vt['Normal_starting_time']['usually'] ;
                    $init['MorningTokinaga'] = $vt['Normal_starting_time']['usuallyMorningPeak'] ;
                    $init['EveningTokinaga'] = $vt['Normal_starting_time']['usuallyEveningPeak'] ;
                    $init['WeehoursTokinaga'] = $vt['Normal_starting_time']['usuallyMidnight'] ;

                    $init['HolidaysNightStartingTokinaga'] = $vt['Normal_starting_time']['festivaLateNightPeak'] ;
                    $init['NightStartingTokinaga'] = $vt['Normal_starting_time']['usuallyLateNightPeak'] ;
                }
                if(!empty($vt['Remote_km'])){                       //远途公里
                    $init['HolidaysStartingKilometre'] = $vt['Remote_km']['festiva'] ;
                    $init['HolidaysMorningLongKilometers'] = $vt['Remote_km']['festivalMorningPeak'] ;
                    $init['HolidaysEveningLongKilometers'] = $vt['Remote_km']['festivalEveningPeak'] ;
                    $init['HolidaysWeehoursLongKilometers'] = $vt['Remote_km']['festivalMidnight'] ;
                    $init['LongKilometers'] = $vt['Remote_km']['usually'] ;
                    $init['MorningLongKilometers'] = $vt['Remote_km']['usuallyMorningPeak'] ;
                    $init['EveningLongKilometers'] = $vt['Remote_km']['usuallyEveningPeak'] ;
                    $init['WeehoursLongKilometers'] = $vt['Remote_km']['usuallyMidnight'] ;

                    $init['HolidaysNightLongKilometers'] = $vt['Remote_km']['festivaLateNightPeak'] ;
                    $init['NightLongKilometers'] = $vt['Remote_km']['usuallyLateNightPeak'] ;
                }
                if(!empty($vt['remote_fee'])){                     //远途费
                    $init['HolidaysStartingLongfee'] = $vt['remote_fee']['festiva'] ;
                    $init['HolidaysMorningLongfee'] = $vt['remote_fee']['festivalMorningPeak'] ;
                    $init['HolidaysEveningLongfee'] = $vt['remote_fee']['festivalEveningPeak'] ;
                    $init['HolidaysWeehoursLongfee'] = $vt['remote_fee']['festivalMidnight'] ;
                    $init['Longfee'] = $vt['remote_fee']['usually'] ;
                    $init['MorningLongLongfee'] = $vt['remote_fee']['usuallyMorningPeak'] ;
                    $init['EveningLongLongfee'] = $vt['remote_fee']['usuallyEveningPeak'] ;
                    $init['WeehoursLongLongfee'] = $vt['remote_fee']['usuallyMidnight'] ;

                    $init['HolidaysNightLongfee'] = $vt['remote_fee']['festivaLateNightPeak'] ;
                    $init['NightLongfee'] = $vt['remote_fee']['usuallyLateNightPeak'] ;
                }
                if(!empty($vt['StartFare'])){
                    //起步价
                    $init['HolidaysStartFare'] = $vt['StartFare']['festiva'] ;
                    $init['HolidaysMorningStartFare'] = $vt['StartFare']['festivalMorningPeak'] ;
                    $init['HolidaysEveningStartFare'] = $vt['StartFare']['festivalEveningPeak'] ;
                    $init['HolidaysWeehoursStartFare'] = $vt['StartFare']['festivalMidnight'] ;
                    $init['StartFare'] = $vt['StartFare']['usually'] ;
                    $init['MorningStartFare'] = $vt['StartFare']['usuallyMorningPeak'] ;
                    $init['EveningStartFare'] = $vt['StartFare']['usuallyEveningPeak'] ;
                    $init['WeehoursStartFare'] = $vt['StartFare']['usuallyMidnight'] ;

                    $init['HolidaysNightStartFare'] = $vt['StartFare']['festivaLateNightPeak'] ;
                    $init['NightStartFare'] = $vt['StartFare']['usuallyLateNightPeak'] ;

                }
                if(!empty($vt['StartMile'])){                        //起步里程
                    $init['HolidaysStartMile'] = $vt['StartMile']['festiva'] ;
                    $init['HolidaysMorningStartMile'] = $vt['StartMile']['festivalMorningPeak'] ;
                    $init['HolidaysEveningStartMile'] = $vt['StartMile']['festivalEveningPeak'] ;
                    $init['HolidaysWeehoursStartMile'] = $vt['StartMile']['festivalMidnight'] ;
                    $init['StartMile'] = $vt['StartMile']['usually'] ;
                    $init['MorningStartMile'] = $vt['StartMile']['usuallyMorningPeak'] ;
                    $init['EveningStartMile'] = $vt['StartMile']['usuallyEveningPeak'] ;
                    $init['WeehoursStartMile'] = $vt['StartMile']['usuallyMidnight'] ;

                    $init['HolidaysNightStartMile'] = $vt['StartMile']['festivaLateNightPeak'] ;
                    $init['NightStartMile'] = $vt['StartMile']['usuallyLateNightPeak'] ;
                }
                if(!empty($vt['mileage_fee'])){                      //里程费
                    $init['HolidaysMileageFee'] = $vt['mileage_fee']['festiva'] ;
                    $init['HolidaysMorningMileageFee'] = $vt['mileage_fee']['festivalMorningPeak'] ;
                    $init['HolidaysEveningMileageFee'] = $vt['mileage_fee']['festivalEveningPeak'] ;
                    $init['HolidaysWeehoursMileageFee'] = $vt['mileage_fee']['festivalMidnight'] ;
                    $init['MileageFee'] = $vt['mileage_fee']['usually'] ;
                    $init['MorningMileageFee'] = $vt['mileage_fee']['usuallyMorningPeak'] ;
                    $init['EveningMileageFee'] = $vt['mileage_fee']['usuallyEveningPeak'] ;
                    $init['WeehoursMileageFee'] = $vt['mileage_fee']['usuallyMidnight'] ;

                    $init['HolidaysNightMileageFee'] = $vt['mileage_fee']['festivaLateNightPeak'] ;
                    $init['NightMileageFee'] = $vt['mileage_fee']['usuallyLateNightPeak'] ;
                }
                if(!empty($vt['how_fee'])){                          //时长费
                    $init['HolidaysHowFee'] = $vt['how_fee']['festiva'] ;
                    $init['HolidaysMorningHowFee'] = $vt['how_fee']['festivalMorningPeak'] ;
                    $init['HolidaysEveningHowFee'] = $vt['how_fee']['festivalEveningPeak'] ;
                    $init['HolidaysWeehoursHowFee'] = $vt['how_fee']['festivalMidnight'] ;
                    $init['HowFee'] = $vt['how_fee']['usually'] ;
                    $init['MorningHowFee'] = $vt['how_fee']['usuallyMorningPeak'] ;
                    $init['EveningHowFee'] = $vt['how_fee']['usuallyEveningPeak'] ;
                    $init['WeehoursHowFee'] = $vt['how_fee']['usuallyMidnight'] ;

                    $init['HolidaysNightHowFee'] = $vt['how_fee']['festivaLateNightPeak'] ;
                    $init['NightHowFee'] = $vt['how_fee']['usuallyLateNightPeak'] ;
                }

                //时间段
                if(!empty($vt['times'])){
                    $init['MorningPeakTimeOn'] = $vt['times']['MorningPeakTimeOn'] ;
                    $init['MorningPeakTimeOff'] = $vt['times']['MorningPeakTimeOff'] ;
                    $init['EveningPeakTimeOn'] = $vt['times']['EveningPeakTimeOn'] ;
                    $init['EveningPeakTimeOff'] = $vt['times']['EveningPeakTimeOff'] ;
                    $init['UsuallyTimeOn'] = $vt['times']['UsuallyTimeOn'] ;
                    $init['UsuallyTimeOff'] = $vt['times']['UsuallyTimeOff'] ;
                    $init['weehoursOn'] = $vt['times']['weehoursOn'] ;
                    $init['weehoursOff'] = $vt['times']['weehoursOff'] ;

                    $init['HolidaysMorningOn'] = $vt['times']['HolidaysMorningOn'] ;
                    $init['HolidaysMorningOff'] = $vt['times']['HolidaysMorningOff'] ;
                    $init['HolidaysEveningOn'] = $vt['times']['HolidaysEveningOn'] ;
                    $init['HolidaysEveningOff'] = $vt['times']['HolidaysEveningOff'] ;
                    $init['HolidaysUsuallyOn'] = $vt['times']['HolidaysUsuallyOn'] ;
                    $init['HolidaysUsuallyOff'] = $vt['times']['HolidaysUsuallyOff'] ;
                    $init['HolidaysWeeOn'] = $vt['times']['HolidaysWeeOn'] ;
                    $init['HolidaysWeeOff'] = $vt['times']['HolidaysWeeOff'] ;

                    $init['HolidaysLateNightOn'] = $vt['times']['HolidaysLateNightOn'] ;
                    $init['HolidaysLateNightOff'] = $vt['times']['HolidaysLateNightOff'] ;
                    $init['UsuallyLateNightOn'] = $vt['times']['UsuallyLateNightOn'] ;
                    $init['UsuallyLateNightOff'] = $vt['times']['UsuallyLateNightOff'] ;
                }
                if($vt['businesstype_id'] < 0){
                    $vt['businesstype_id'] = 0 ;
                }
                $init['company_id'] = $company ;
                $init['business_id'] = $vt['business_id'] ;
                $init['businesstype_id'] = $vt['businesstype_id'] ;
                $init['titles'] = $vt['title'] ;
                $init['sectionPriceForm'] = $vt['sectionPriceForm'] ;

                Db::name('company_rates')->insert($init);
            }
        }
        if(!empty($appointmentRates)){
            $inir = [] ;
            foreach ($appointmentRates as $kr=>$vr) {
                if(!empty($vr['Normal_starting_time'])){           //起步时长
                    $inir['HolidaysWeehoursStartingTokinaga'] = $vr['Normal_starting_time']['festivalMidnight'] ;
                    $inir['HolidaysMorningStartingTokinaga'] = $vr['Normal_starting_time']['festivalMorningPeak'] ;
                    $inir['HolidaysEveningStartingTokinaga'] = $vr['Normal_starting_time']['festivalEveningPeak'] ;
                    $inir['HolidaysStartingTokinaga'] = $vr['Normal_starting_time']['festiva'] ;
                    $inir['Tokinaga'] = $vr['Normal_starting_time']['usually'] ;
                    $inir['MorningTokinaga'] = $vr['Normal_starting_time']['usuallyMorningPeak'] ;
                    $inir['EveningTokinaga'] = $vr['Normal_starting_time']['usuallyEveningPeak'] ;
                    $inir['WeehoursTokinaga'] = $vr['Normal_starting_time']['usuallyMidnight'] ;

                    $inir['HolidaysNightStartingTokinaga'] = $vr['Normal_starting_time']['festivaLateNightPeak'] ;
                    $inir['NightStartingTokinaga'] = $vr['Normal_starting_time']['usuallyLateNightPeak'] ;
                }
                if(!empty($vr['Remote_km'])){                       //远途公里
                    $inir['HolidaysStartingKilometre'] = $vr['Remote_km']['festiva'] ;
                    $inir['HolidaysMorningLongKilometers'] = $vr['Remote_km']['festivalMorningPeak'] ;
                    $inir['HolidaysEveningLongKilometers'] = $vr['Remote_km']['festivalEveningPeak'] ;
                    $inir['HolidaysWeehoursLongKilometers'] = $vr['Remote_km']['festivalMidnight'] ;
                    $inir['LongKilometers'] = $vr['Remote_km']['usually'] ;
                    $inir['MorningLongKilometers'] = $vr['Remote_km']['usuallyMorningPeak'] ;
                    $inir['EveningLongKilometers'] = $vr['Remote_km']['usuallyEveningPeak'] ;
                    $inir['WeehoursLongKilometers'] = $vr['Remote_km']['usuallyMidnight'] ;

                    $inir['HolidaysNightLongKilometers'] = $vr['Remote_km']['festivaLateNightPeak'] ;
                    $inir['NightLongKilometers'] = $vr['Remote_km']['usuallyLateNightPeak'] ;
                }
                if(!empty($vr['remote_fee'])){                     //远途费
                    $inir['HolidaysStartingLongfee'] = $vr['remote_fee']['festiva'] ;
                    $inir['HolidaysMorningLongfee'] = $vr['remote_fee']['festivalMorningPeak'] ;
                    $inir['HolidaysEveningLongfee'] = $vr['remote_fee']['festivalEveningPeak'] ;
                    $inir['HolidaysWeehoursLongfee'] = $vr['remote_fee']['festivalMidnight'] ;
                    $inir['Longfee'] = $vr['remote_fee']['usually'] ;
                    $inir['MorningLongLongfee'] = $vr['remote_fee']['usuallyMorningPeak'] ;
                    $inir['EveningLongLongfee'] = $vr['remote_fee']['usuallyEveningPeak'] ;
                    $inir['WeehoursLongLongfee'] = $vr['remote_fee']['usuallyMidnight'] ;

                    $inir['HolidaysNightLongfee'] = $vr['remote_fee']['festivaLateNightPeak'] ;
                    $inir['NightLongfee'] = $vr['remote_fee']['usuallyLateNightPeak'] ;
                }
                if(!empty($vr['StartFare'])){
                    //起步价
                    $inir['HolidaysStartFare'] = $vr['StartFare']['festiva'] ;
                    $inir['HolidaysMorningStartFare'] = $vr['StartFare']['festivalMorningPeak'] ;
                    $inir['HolidaysEveningStartFare'] = $vr['StartFare']['festivalEveningPeak'] ;
                    $inir['HolidaysWeehoursStartFare'] = $vr['StartFare']['festivalMidnight'] ;
                    $inir['StartFare'] = $vr['StartFare']['usually'] ;
                    $inir['MorningStartFare'] = $vr['StartFare']['usuallyMorningPeak'] ;
                    $inir['EveningStartFare'] = $vr['StartFare']['usuallyEveningPeak'] ;
                    $inir['WeehoursStartFare'] = $vr['StartFare']['usuallyMidnight'] ;

                    $inir['HolidaysNightStartFare'] = $vr['StartFare']['festivaLateNightPeak'] ;
                    $inir['NightStartFare'] = $vr['StartFare']['usuallyLateNightPeak'] ;

                }
                if(!empty($vr['StartMile'])){                        //起步里程
                    $inir['HolidaysStartMile'] = $vr['StartMile']['festiva'] ;
                    $inir['HolidaysMorningStartMile'] = $vr['StartMile']['festivalMorningPeak'] ;
                    $inir['HolidaysEveningStartMile'] = $vr['StartMile']['festivalEveningPeak'] ;
                    $inir['HolidaysWeehoursStartMile'] = $vr['StartMile']['festivalMidnight'] ;
                    $inir['StartMile'] = $vr['StartMile']['usually'] ;
                    $inir['MorningStartMile'] = $vr['StartMile']['usuallyMorningPeak'] ;
                    $inir['EveningStartMile'] = $vr['StartMile']['usuallyEveningPeak'] ;
                    $inir['WeehoursStartMile'] = $vr['StartMile']['usuallyMidnight'] ;

                    $inir['HolidaysNightStartMile'] = $vr['StartMile']['festivaLateNightPeak'] ;
                    $inir['NightStartMile'] = $vr['StartMile']['usuallyLateNightPeak'] ;
                }
                if(!empty($vr['mileage_fee'])){                      //里程费
                    $inir['HolidaysMileageFee'] = $vr['mileage_fee']['festiva'] ;
                    $inir['HolidaysMorningMileageFee'] = $vr['mileage_fee']['festivalMorningPeak'] ;
                    $inir['HolidaysEveningMileageFee'] = $vr['mileage_fee']['festivalEveningPeak'] ;
                    $inir['HolidaysWeehoursMileageFee'] = $vr['mileage_fee']['festivalMidnight'] ;
                    $inir['MileageFee'] = $vr['mileage_fee']['usually'] ;
                    $inir['MorningMileageFee'] = $vr['mileage_fee']['usuallyMorningPeak'] ;
                    $inir['EveningMileageFee'] = $vr['mileage_fee']['usuallyEveningPeak'] ;
                    $inir['WeehoursMileageFee'] = $vr['mileage_fee']['usuallyMidnight'] ;

                    $inir['HolidaysNightMileageFee'] = $vr['mileage_fee']['festivaLateNightPeak'] ;
                    $inir['NightMileageFee'] = $vr['mileage_fee']['usuallyLateNightPeak'] ;
                }
                if(!empty($vr['how_fee'])){                          //时长费
                    $inir['HolidaysHowFee'] = $vr['how_fee']['festiva'] ;
                    $inir['HolidaysMorningHowFee'] = $vr['how_fee']['festivalMorningPeak'] ;
                    $inir['HolidaysEveningHowFee'] = $vr['how_fee']['festivalEveningPeak'] ;
                    $inir['HolidaysWeehoursHowFee'] = $vr['how_fee']['festivalMidnight'] ;
                    $inir['HowFee'] = $vr['how_fee']['usually'] ;
                    $inir['MorningHowFee'] = $vr['how_fee']['usuallyMorningPeak'] ;
                    $inir['EveningHowFee'] = $vr['how_fee']['usuallyEveningPeak'] ;
                    $inir['WeehoursHowFee'] = $vr['how_fee']['usuallyMidnight'] ;

                    $inir['HolidaysNightHowFee'] = $vr['how_fee']['festivaLateNightPeak'] ;
                    $inir['NightHowFee'] = $vr['how_fee']['usuallyLateNightPeak'] ;
                }

                //时间段
                if(!empty($vr['times'])){
                    $inir['MorningPeakTimeOn'] = $vr['times']['MorningPeakTimeOn'] ;
                    $inir['MorningPeakTimeOff'] = $vr['times']['MorningPeakTimeOff'] ;
                    $inir['EveningPeakTimeOn'] = $vr['times']['EveningPeakTimeOn'] ;
                    $inir['EveningPeakTimeOff'] = $vr['times']['EveningPeakTimeOff'] ;
                    $inir['UsuallyTimeOn'] = $vr['times']['UsuallyTimeOn'] ;
                    $inir['UsuallyTimeOff'] = $vr['times']['UsuallyTimeOff'] ;
                    $inir['weehoursOn'] = $vr['times']['weehoursOn'] ;
                    $inir['weehoursOff'] = $vr['times']['weehoursOff'] ;

                    $inir['HolidaysMorningOn'] = $vr['times']['HolidaysMorningOn'] ;
                    $inir['HolidaysMorningOff'] = $vr['times']['HolidaysMorningOff'] ;
                    $inir['HolidaysEveningOn'] = $vr['times']['HolidaysEveningOn'] ;
                    $inir['HolidaysEveningOff'] = $vr['times']['HolidaysEveningOff'] ;
                    $inir['HolidaysUsuallyOn'] = $vr['times']['HolidaysUsuallyOn'] ;
                    $inir['HolidaysUsuallyOff'] = $vr['times']['HolidaysUsuallyOff'] ;
                    $inir['HolidaysWeeOn'] = $vr['times']['HolidaysWeeOn'] ;
                    $inir['HolidaysWeeOff'] = $vr['times']['HolidaysWeeOff'] ;

                    $inir['HolidaysLateNightOn'] = $vr['times']['HolidaysLateNightOn'] ;
                    $inir['HolidaysLateNightOff'] = $vr['times']['HolidaysLateNightOff'] ;
                    $inir['UsuallyLateNightOn'] = $vr['times']['UsuallyLateNightOn'] ;
                    $inir['UsuallyLateNightOff'] = $vr['times']['UsuallyLateNightOff'] ;
                }
                if($vr['businesstype_id'] < 0){
                    $vr['businesstype_id'] = 0 ;
                }
                $inir['company_id'] = $company ;
                $inir['business_id'] = $vr['business_id'] ;
                $inir['businesstype_id'] = $vr['businesstype_id'] ;
                $inir['titles'] = $vr['title'] ;

                $inir['sectionPriceForm'] = $vr['sectionPriceForm'] ;

                Db::name('company_appointment_rates')->insert($inir);
            }
        }
        if(!empty($elimination)){
            $inie = [] ;
            foreach ($elimination as $ke=>$ve){
                if($ve['businesstype_id'] < 0){
                    $ve['businesstype_id'] = 0 ;
                }
                $inie['passenger_count'] = $ve['passenger_count'] ;
                $inie['motorman_count'] = $ve['motorman_count'] ;
                $inie['cancel_tokinaga'] = $ve['cancel_tokinaga'] ;
                $inie['cancel_cost'] = $ve['cancel_cost'] ;
                $inie['highest_charge'] = $ve['highest_charge'] ;
                $inie['aSpotCat_type'] = $ve['aSpotCat_type'] ;
                $inie['money'] = $ve['money'] ;
                $inie['fixed_price'] = $ve['fixed_price'] ;
                $inie['company_id'] = $company;
                $inie['business_id'] = $ve['business_id'] ;
                $inie['businesstype_id'] = $ve['businesstype_id'] ;

                Db::name('company_elimination')->insert($inie);
            }
        }
        if(!empty($appointment)){
            $inin = [] ;
            foreach ($appointment as $kn=>$vn){
                if($vn['businesstype_id'] < 0){
                    $vn['businesstype_id'] = 0 ;
                }
                $inin['passenger_count'] = $vn['passenger_count'] ;
                $inin['motorman_count'] = $vn['motorman_count'] ;
                $inin['cancel_tokinaga'] = $vn['cancel_tokinaga'] ;
                $inin['cancel_cost'] = $vn['cancel_cost'] ;
                $inin['highest_charge'] = $vn['highest_charge'] ;
                $inin['aSpotCat_type'] = $vn['aSpotCat_type'] ;
                $inin['money'] = $vn['money'] ;
                $inin['fixed_price'] = $vn['fixed_price'] ;
                $inin['company_id'] = $company;
                $inin['business_id'] = $vn['business_id'] ;
                $inin['businesstype_id'] = $vn['businesstype_id'] ;

                Db::name('company_appointment')->insert($inin);
            }
        }
        if(!empty($consumption)){
            $inic = [] ;
            foreach ($consumption as $kc=>$vc){
                if($vc['businesstype_id'] < 0){
                    $vc['businesstype_id'] = 0 ;
                }
                $inic['company_id'] = $company ;
                $inic['business_id'] = $vc['business_id'] ;
                $inic['businesstype_id'] = $vc['businesstype_id'] ;
                $inic['special_money'] = $vc['special_money'] ;
                $inic['appointment_money'] = $vc['appointment_money'] ;

                $inic['type'] = $vc['type'] ;
                $inic['order_money'] = $vc['order_money'] ;

                Db::name('company_consumption')->insert($inic);
            }
        }
        if(!empty($ratesSectionPriceForm)){
            $priceform['zero'] = $ratesSectionPriceForm['zero'] ;
            $priceform['zeroToThree'] = $ratesSectionPriceForm['zeroToThree'] ;
            $priceform['threeToSix'] = $ratesSectionPriceForm['threeToSix'] ;
            $priceform['sixToNine'] = $ratesSectionPriceForm['sixToNine'] ;

            Db::name('company_real_await')->insert($priceform) ;
        }
        if(!empty($appointmentRatesSectionPriceForm)){
            $Spriceform['zero'] = $appointmentRatesSectionPriceForm['zero'] ;
            $Spriceform['zeroToThree'] = $appointmentRatesSectionPriceForm['zeroToThree'] ;
            $Spriceform['threeToSix'] = $appointmentRatesSectionPriceForm['threeToSix'] ;
            $Spriceform['sixToNine'] = $appointmentRatesSectionPriceForm['sixToNine'] ;

            Db::name('company_appointment_await')->insert($Spriceform) ;
        }
    }
    //删除公司数据除基础数据以外
    protected function delcompany($id){
        //收费表
        $company_charge = Db::name('company_charge')->where(['company_id'=>$id])->select();
        foreach ($company_charge as $key=>$value){
            Db::name('company_charge')->where(['id'=>$value['id']])->delete();
        }
        //业务表
        $company_business = Db::name('company_business')->where(['company_id'=>$id])->select();
        foreach ($company_business as $k=>$v){
            Db::name('company_business')->where(['id'=>$v['id']])->delete();
        }
        //附件表
        $company_accessory = Db::name('company_accessory')->where(['company_id'=>$id])->select();
        foreach ($company_accessory as $kc=>$vc){
            Db::name('company_accessory')->where(['id'=>$vc['id']])->delete();
        }
        //抽成表
        $company_ratio = Db::name('company_ratio')->where(['company_id'=>$id])->select();
        foreach ($company_ratio as $kr=>$vr){
            Db::name('company_ratio')->where(['id'=>$vr['id']])->delete();
        }
        //实时计价规则
        $company_rates = Db::name('company_rates')->where(['company_id'=>$id])->select() ;
        foreach ($company_rates as $kcr=>$vcr){
            Db::name('company_rates')->where(['id'=>$vcr['id']])->delete();
        }
        //预约计价规则
        $company_appointment_rates = Db::name('company_appointment_rates')->where(['company_id'=>$id])->select() ;
        foreach ($company_appointment_rates as $kcrs=>$vcrs){
            Db::name('company_appointment_rates')->where(['id'=>$vcrs['id']])->delete();
        }
        //实时消单规则
        $company_elimination = Db::name('company_elimination')->where(['company_id'=>$id])->select();
        foreach ($company_elimination as $ke=>$ve){
            Db::name('company_elimination')->where(['id'=>$ve['id']])->delete();
        }
        //预约消单规则
        $company_appointment = Db::name('company_appointment')->where(['company_id'=>$id])->select();
        foreach ($company_appointment as $ka=>$va){
            Db::name('company_appointment')->where(['id'=>$va['id']])->delete();
        }
        //分销
        $company_distribution = Db::name('company_distribution')->where(['company_id'=>$id])->select();
        foreach ($company_distribution as $kd=>$vd){
            Db::name('company_distribution')->where(['id'=>$vd['id']])->delete();
            $company_distribution_detail = Db::name('company_distribution_detail')->where(['company_distribution_id'=>$vd['id']])->select();
            foreach ($company_distribution_detail as $kdd=>$vdd){
                Db::name('company_distribution')->where(['id'=>$vdd['id']])->delete();
            }
        }
        //最低消费
        $company_consumption = Db::name('company_consumption')->where(['company_id'=>$id])->select();
        foreach ($company_consumption as $cs=>$vs){
            Db::name('company_consumption')->where(['id'=>$vs['id']])->delete();
        }
        //付费方式
        $company_paymethod = Db::name('company_paymethod')->where(['company_id'=>$id])->select();
        foreach ($company_paymethod as $cp=>$vp){
            Db::name('company_paymethod')->where(['id'=>$vp['id']])->delete();
        }
        //范围
        $company_scope = Db::name('company_scope')->where(['company_id'=>$id])->select() ;
        foreach ($company_scope as $cso=>$vso){
            Db::name('company_scope')->where(['id'=>$vso['id']])->delete();
        }
    }
    //业务车型
    public function businessType(){
        if (input('?business_id')) {
            $params = [
                "business_id" => input('business_id')
            ];
            $data = db('business_type')->where($params)->select();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "类型ID不能为空"
            ];
        }
    }
    //业务列表
    public function businessList(){
        return self::pageReturn(db('business'), '');
    }
    //公司首页
    public function company_home(){
        //分公司分布情况
        $data['company_distribution'] = Db::name('company')
            ->alias('y')
            ->join('mx_cn_city c','c.id = y.city_id','left')
            ->join('mx_cn_prov p','p.code = c.pcode','left')
            ->field('p.name as p_name,count(p.id) as c_count')
            ->group('y.city_id')
            ->select();

        //分公司日流水排行
        $data['company_water'] = Db::name('company')
            ->alias('p')
            ->join('mx_order o','o.company_id = p.id','left')
            ->field('p.CompanyName,sum(o.money) as money')
            ->group('p.id')
            ->where('o.status','in',"6,9")
            ->select();

        //分公司司机在线时长排行
        $data['company_online'] = Db::name('company')->alias('c')
            ->field('c.CompanyName,(SUM(one_hour)+SUM(two_hour)+SUM(three_hour)+SUM(four_hour)+SUM(five_hour)
                                    +SUM(six_hour)+SUM(seven_hour)+SUM(eight_hour)+SUM(nine_hour)+SUM(eleven_hour)+SUM(twelve_hour)+SUM(thirteen_hour)
                                    +SUM(fourteen_hour)+SUM(fifteen_hour)+SUM(sixteen_hour)+SUM(seventeen_hour)+SUM(eighteen_hour)+SUM(nineteen_hour)+SUM(twenty_hour)
                                    +SUM(twentyone_hour)+SUM(twentytwo_hour)+SUM(twentythree_hour)+SUM(twentyfour_hour)) as count')
            ->join('mx_conducteur r','r.company_id = c.id','left')
            ->join('mx_conducteur_tokinaga t','t.conducteur_id = r.id')
            ->group('c.id')->select() ;

        //分公司平均日成交订单量排行
        $compamny = Db::name('company')->alias('c')
            ->join('mx_conducteur r','r.company_id = c.id','left')
            ->join('mx_order o','o.conducteur_id = r.id','left')
            ->field('c.CompanyName,c.create_time,count(o.id) as count')
            ->group('c.id')->select();

        foreach ($compamny as $key=>$value){
            //距离今天相距多少天
            $time = intval((time() - $value['create_time'])/60 / 60 / 24 ) ;
            $compamny[$key]['company_count'] =intval( $value['count']/ $time) ;
        }

        $data['company_order']  = $compamny;

        return ['code'=>APICODE_SUCCESS,'data'=>$data];

    }
    //创建service
    public function ser($name,$desc,$key)
    {
        $url = "https://tsapi.amap.com/v1/track/service/add";
        $postData = array(
            "name" => $name,
            "desc" => $desc,
            "key" => $key,
        );
        $postData = http_build_query($postData);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT,'Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.15');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }
    //删除service
    public function del_ser()
    {
        $url = "https://tsapi.amap.com/v1/track/service/delete";
        $postData = array(
            "sid" => "151035",
            "key" => "7609d7e35683fc4087c4351c6b8d96b5",
        );
        $postData = http_build_query($postData);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT,'Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.15');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
//        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($curl);
        curl_close($curl);
        dump($data);die;

    }
    //听单列表
    public function CompanyPatternList(){
        //根据公司id,删除数据
        $company_pattern = Db::name('company_pattern')->where(['company_id'=>input('company_id')])->select() ;
        foreach ($company_pattern as $key=>$value){
            Db::name('company_pattern')->where(['id'=>$value['id']])->delete() ;
        }

        $data = input('') ;
        $pattern = $data['pattern']? $data['pattern'] : null;          //听单列表

        if(!empty($pattern)){
            $inib = [] ;
            foreach ($pattern as $kq=>$vq){
                $inib['title'] = $vq['title'] ;
                $inib['is_display'] = $vq['is_display'] ;
                $inib['company_id'] = input('company_id') ;
                Db::name('company_pattern')->insert( $inib );
            }
        }

        return ['code'=>APICODE_SUCCESS,'msg'=>"保存成功"];
    }
    //显示听单列表
    public function ShowCompanyPattern(){
        if (input('?company_id')) {
            $params = [
                "company_id" => input('company_id')
            ];
            $data = db('company_pattern')->where($params)->select();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "公司ID不能为空"
            ];
        }
    }
    //保存预约单列表
    public function ShowDisplayList(){
        //根据公司id,删除数据
        $company_display = Db::name('company_display')->where(['company_id'=>input('company_id')])->find() ;
        Db::name('company_display')->where(['id'=>$company_display['id']])->delete() ;

        $inib = [] ;
        $inib['is_appointment'] = input('is_appointment') ;
        $inib['company_id'] = input('company_id') ;
        Db::name('company_display')->insert( $inib );

        return ['code'=>APICODE_SUCCESS,'msg'=>"保存成功"];
    }
    //显示预约单列表
    public function DisplayList(){
        if (input('?company_id')) {
            $params = [
                "company_id" => input('company_id')
            ];
            $data = db('company_display')->where($params)->find();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "公司ID不能为空"
            ];
        }
    }
    //增加计费模板
    public function AddCompanyBillingTemplate(){
        $rates = input('') ;
        $init = [] ;
        foreach ($rates as $kt=>$vt) {
            if(!empty($vt['Normal_starting_time'])){           //起步时长
                $init['HolidaysWeehoursStartingTokinaga'] = $vt['Normal_starting_time']['festivalMidnight'] ;
                $init['HolidaysMorningStartingTokinaga'] = $vt['Normal_starting_time']['festivalMorningPeak'] ;
                $init['HolidaysEveningStartingTokinaga'] = $vt['Normal_starting_time']['festivalEveningPeak'] ;
                $init['HolidaysStartingTokinaga'] = $vt['Normal_starting_time']['festiva'] ;
                $init['Tokinaga'] = $vt['Normal_starting_time']['usually'] ;
                $init['MorningTokinaga'] = $vt['Normal_starting_time']['usuallyMorningPeak'] ;
                $init['EveningTokinaga'] = $vt['Normal_starting_time']['usuallyEveningPeak'] ;
                $init['WeehoursTokinaga'] = $vt['Normal_starting_time']['usuallyMidnight'] ;

                $init['HolidaysNightStartingTokinaga'] = $vt['Normal_starting_time']['festivaLateNightPeak'] ;
                $init['NightStartingTokinaga'] = $vt['Normal_starting_time']['usuallyLateNightPeak'] ;
            }
            if(!empty($vt['Remote_km'])){                       //远途公里
                $init['HolidaysStartingKilometre'] = $vt['Remote_km']['festiva'] ;
                $init['HolidaysMorningLongKilometers'] = $vt['Remote_km']['festivalMorningPeak'] ;
                $init['HolidaysEveningLongKilometers'] = $vt['Remote_km']['festivalEveningPeak'] ;
                $init['HolidaysWeehoursLongKilometers'] = $vt['Remote_km']['festivalMidnight'] ;
                $init['LongKilometers'] = $vt['Remote_km']['usually'] ;
                $init['MorningLongKilometers'] = $vt['Remote_km']['usuallyMorningPeak'] ;
                $init['EveningLongKilometers'] = $vt['Remote_km']['usuallyEveningPeak'] ;
                $init['WeehoursLongKilometers'] = $vt['Remote_km']['usuallyMidnight'] ;

                $init['HolidaysNightLongKilometers'] = $vt['Remote_km']['festivaLateNightPeak'] ;
                $init['NightLongKilometers'] = $vt['Remote_km']['usuallyLateNightPeak'] ;
            }
            if(!empty($vt['remote_fee'])){                     //远途费
                $init['HolidaysStartingLongfee'] = $vt['remote_fee']['festiva'] ;
                $init['HolidaysMorningLongfee'] = $vt['remote_fee']['festivalMorningPeak'] ;
                $init['HolidaysEveningLongfee'] = $vt['remote_fee']['festivalEveningPeak'] ;
                $init['HolidaysWeehoursLongfee'] = $vt['remote_fee']['festivalMidnight'] ;
                $init['Longfee'] = $vt['remote_fee']['usually'] ;
                $init['MorningLongLongfee'] = $vt['remote_fee']['usuallyMorningPeak'] ;
                $init['EveningLongLongfee'] = $vt['remote_fee']['usuallyEveningPeak'] ;
                $init['WeehoursLongLongfee'] = $vt['remote_fee']['usuallyMidnight'] ;

                $init['HolidaysNightLongfee'] = $vt['remote_fee']['festivaLateNightPeak'] ;
                $init['NightLongfee'] = $vt['remote_fee']['usuallyLateNightPeak'] ;
            }
            if(!empty($vt['StartFare'])){
                //起步价
                $init['HolidaysStartFare'] = $vt['StartFare']['festiva'] ;
                $init['HolidaysMorningStartFare'] = $vt['StartFare']['festivalMorningPeak'] ;
                $init['HolidaysEveningStartFare'] = $vt['StartFare']['festivalEveningPeak'] ;
                $init['HolidaysWeehoursStartFare'] = $vt['StartFare']['festivalMidnight'] ;
                $init['StartFare'] = $vt['StartFare']['usually'] ;
                $init['MorningStartFare'] = $vt['StartFare']['usuallyMorningPeak'] ;
                $init['EveningStartFare'] = $vt['StartFare']['usuallyEveningPeak'] ;
                $init['WeehoursStartFare'] = $vt['StartFare']['usuallyMidnight'] ;

                $init['HolidaysNightStartFare'] = $vt['StartFare']['festivaLateNightPeak'] ;
                $init['NightStartFare'] = $vt['StartFare']['usuallyLateNightPeak'] ;

            }
            if(!empty($vt['StartMile'])){                        //起步里程
                $init['HolidaysStartMile'] = $vt['StartMile']['festiva'] ;
                $init['HolidaysMorningStartMile'] = $vt['StartMile']['festivalMorningPeak'] ;
                $init['HolidaysEveningStartMile'] = $vt['StartMile']['festivalEveningPeak'] ;
                $init['HolidaysWeehoursStartMile'] = $vt['StartMile']['festivalMidnight'] ;
                $init['StartMile'] = $vt['StartMile']['usually'] ;
                $init['MorningStartMile'] = $vt['StartMile']['usuallyMorningPeak'] ;
                $init['EveningStartMile'] = $vt['StartMile']['usuallyEveningPeak'] ;
                $init['WeehoursStartMile'] = $vt['StartMile']['usuallyMidnight'] ;

                $init['HolidaysNightStartMile'] = $vt['StartMile']['festivaLateNightPeak'] ;
                $init['NightStartMile'] = $vt['StartMile']['usuallyLateNightPeak'] ;
            }
            if(!empty($vt['mileage_fee'])){                      //里程费
                $init['HolidaysMileageFee'] = $vt['mileage_fee']['festiva'] ;
                $init['HolidaysMorningMileageFee'] = $vt['mileage_fee']['festivalMorningPeak'] ;
                $init['HolidaysEveningMileageFee'] = $vt['mileage_fee']['festivalEveningPeak'] ;
                $init['HolidaysWeehoursMileageFee'] = $vt['mileage_fee']['festivalMidnight'] ;
                $init['MileageFee'] = $vt['mileage_fee']['usually'] ;
                $init['MorningMileageFee'] = $vt['mileage_fee']['usuallyMorningPeak'] ;
                $init['EveningMileageFee'] = $vt['mileage_fee']['usuallyEveningPeak'] ;
                $init['WeehoursMileageFee'] = $vt['mileage_fee']['usuallyMidnight'] ;

                $init['HolidaysNightMileageFee'] = $vt['mileage_fee']['festivaLateNightPeak'] ;
                $init['NightMileageFee'] = $vt['mileage_fee']['usuallyLateNightPeak'] ;
            }
            if(!empty($vt['how_fee'])){                          //时长费
                $init['HolidaysHowFee'] = $vt['how_fee']['festiva'] ;
                $init['HolidaysMorningHowFee'] = $vt['how_fee']['festivalMorningPeak'] ;
                $init['HolidaysEveningHowFee'] = $vt['how_fee']['festivalEveningPeak'] ;
                $init['HolidaysWeehoursHowFee'] = $vt['how_fee']['festivalMidnight'] ;
                $init['HowFee'] = $vt['how_fee']['usually'] ;
                $init['MorningHowFee'] = $vt['how_fee']['usuallyMorningPeak'] ;
                $init['EveningHowFee'] = $vt['how_fee']['usuallyEveningPeak'] ;
                $init['WeehoursHowFee'] = $vt['how_fee']['usuallyMidnight'] ;

                $init['HolidaysNightHowFee'] = $vt['how_fee']['festivaLateNightPeak'] ;
                $init['NightHowFee'] = $vt['how_fee']['usuallyLateNightPeak'] ;
            }

            //时间段
            if(!empty($vt['times'])){
                $init['MorningPeakTimeOn'] = $vt['times']['MorningPeakTimeOn'] ;
                $init['MorningPeakTimeOff'] = $vt['times']['MorningPeakTimeOff'] ;
                $init['EveningPeakTimeOn'] = $vt['times']['EveningPeakTimeOn'] ;
                $init['EveningPeakTimeOff'] = $vt['times']['EveningPeakTimeOff'] ;
                $init['UsuallyTimeOn'] = $vt['times']['UsuallyTimeOn'] ;
                $init['UsuallyTimeOff'] = $vt['times']['UsuallyTimeOff'] ;
                $init['weehoursOn'] = $vt['times']['weehoursOn'] ;
                $init['weehoursOff'] = $vt['times']['weehoursOff'] ;

                $init['HolidaysMorningOn'] = $vt['times']['HolidaysMorningOn'] ;
                $init['HolidaysMorningOff'] = $vt['times']['HolidaysMorningOff'] ;
                $init['HolidaysEveningOn'] = $vt['times']['HolidaysEveningOn'] ;
                $init['HolidaysEveningOff'] = $vt['times']['HolidaysEveningOff'] ;
                $init['HolidaysUsuallyOn'] = $vt['times']['HolidaysUsuallyOn'] ;
                $init['HolidaysUsuallyOff'] = $vt['times']['HolidaysUsuallyOff'] ;
                $init['HolidaysWeeOn'] = $vt['times']['HolidaysWeeOn'] ;
                $init['HolidaysWeeOff'] = $vt['times']['HolidaysWeeOff'] ;

                $init['HolidaysLateNightOn'] = $vt['times']['HolidaysLateNightOn'] ;
                $init['HolidaysLateNightOff'] = $vt['times']['HolidaysLateNightOff'] ;
                $init['UsuallyLateNightOn'] = $vt['times']['UsuallyLateNightOn'] ;
                $init['UsuallyLateNightOff'] = $vt['times']['UsuallyLateNightOff'] ;
            }
            if($vt['businesstype_id'] < 0){
                $vt['businesstype_id'] = 0 ;
            }
            $init['title'] = $vt['title'] ;

            Db::name('company_billing_template')->insert($init);
        }
    }
    //修改计费模板
    public function UpdateCompanyBillingTemplate(){
        $id = input('id') ;
        Db::name('company_billing_template')->where(['id'=>$id])->delete() ;

        $rates = input('') ;
        $init = [] ;
        foreach ($rates as $kt=>$vt) {
            if(!empty($vt['Normal_starting_time'])){           //起步时长
                $init['HolidaysWeehoursStartingTokinaga'] = $vt['Normal_starting_time']['festivalMidnight'] ;
                $init['HolidaysMorningStartingTokinaga'] = $vt['Normal_starting_time']['festivalMorningPeak'] ;
                $init['HolidaysEveningStartingTokinaga'] = $vt['Normal_starting_time']['festivalEveningPeak'] ;
                $init['HolidaysStartingTokinaga'] = $vt['Normal_starting_time']['festiva'] ;
                $init['Tokinaga'] = $vt['Normal_starting_time']['usually'] ;
                $init['MorningTokinaga'] = $vt['Normal_starting_time']['usuallyMorningPeak'] ;
                $init['EveningTokinaga'] = $vt['Normal_starting_time']['usuallyEveningPeak'] ;
                $init['WeehoursTokinaga'] = $vt['Normal_starting_time']['usuallyMidnight'] ;

                $init['HolidaysNightStartingTokinaga'] = $vt['Normal_starting_time']['festivaLateNightPeak'] ;
                $init['NightStartingTokinaga'] = $vt['Normal_starting_time']['usuallyLateNightPeak'] ;
            }
            if(!empty($vt['Remote_km'])){                       //远途公里
                $init['HolidaysStartingKilometre'] = $vt['Remote_km']['festiva'] ;
                $init['HolidaysMorningLongKilometers'] = $vt['Remote_km']['festivalMorningPeak'] ;
                $init['HolidaysEveningLongKilometers'] = $vt['Remote_km']['festivalEveningPeak'] ;
                $init['HolidaysWeehoursLongKilometers'] = $vt['Remote_km']['festivalMidnight'] ;
                $init['LongKilometers'] = $vt['Remote_km']['usually'] ;
                $init['MorningLongKilometers'] = $vt['Remote_km']['usuallyMorningPeak'] ;
                $init['EveningLongKilometers'] = $vt['Remote_km']['usuallyEveningPeak'] ;
                $init['WeehoursLongKilometers'] = $vt['Remote_km']['usuallyMidnight'] ;

                $init['HolidaysNightLongKilometers'] = $vt['Remote_km']['festivaLateNightPeak'] ;
                $init['NightLongKilometers'] = $vt['Remote_km']['usuallyLateNightPeak'] ;
            }
            if(!empty($vt['remote_fee'])){                     //远途费
                $init['HolidaysStartingLongfee'] = $vt['remote_fee']['festiva'] ;
                $init['HolidaysMorningLongfee'] = $vt['remote_fee']['festivalMorningPeak'] ;
                $init['HolidaysEveningLongfee'] = $vt['remote_fee']['festivalEveningPeak'] ;
                $init['HolidaysWeehoursLongfee'] = $vt['remote_fee']['festivalMidnight'] ;
                $init['Longfee'] = $vt['remote_fee']['usually'] ;
                $init['MorningLongLongfee'] = $vt['remote_fee']['usuallyMorningPeak'] ;
                $init['EveningLongLongfee'] = $vt['remote_fee']['usuallyEveningPeak'] ;
                $init['WeehoursLongLongfee'] = $vt['remote_fee']['usuallyMidnight'] ;

                $init['HolidaysNightLongfee'] = $vt['remote_fee']['festivaLateNightPeak'] ;
                $init['NightLongfee'] = $vt['remote_fee']['usuallyLateNightPeak'] ;
            }
            if(!empty($vt['StartFare'])){
                //起步价
                $init['HolidaysStartFare'] = $vt['StartFare']['festiva'] ;
                $init['HolidaysMorningStartFare'] = $vt['StartFare']['festivalMorningPeak'] ;
                $init['HolidaysEveningStartFare'] = $vt['StartFare']['festivalEveningPeak'] ;
                $init['HolidaysWeehoursStartFare'] = $vt['StartFare']['festivalMidnight'] ;
                $init['StartFare'] = $vt['StartFare']['usually'] ;
                $init['MorningStartFare'] = $vt['StartFare']['usuallyMorningPeak'] ;
                $init['EveningStartFare'] = $vt['StartFare']['usuallyEveningPeak'] ;
                $init['WeehoursStartFare'] = $vt['StartFare']['usuallyMidnight'] ;

                $init['HolidaysNightStartFare'] = $vt['StartFare']['festivaLateNightPeak'] ;
                $init['NightStartFare'] = $vt['StartFare']['usuallyLateNightPeak'] ;

            }
            if(!empty($vt['StartMile'])){                        //起步里程
                $init['HolidaysStartMile'] = $vt['StartMile']['festiva'] ;
                $init['HolidaysMorningStartMile'] = $vt['StartMile']['festivalMorningPeak'] ;
                $init['HolidaysEveningStartMile'] = $vt['StartMile']['festivalEveningPeak'] ;
                $init['HolidaysWeehoursStartMile'] = $vt['StartMile']['festivalMidnight'] ;
                $init['StartMile'] = $vt['StartMile']['usually'] ;
                $init['MorningStartMile'] = $vt['StartMile']['usuallyMorningPeak'] ;
                $init['EveningStartMile'] = $vt['StartMile']['usuallyEveningPeak'] ;
                $init['WeehoursStartMile'] = $vt['StartMile']['usuallyMidnight'] ;

                $init['HolidaysNightStartMile'] = $vt['StartMile']['festivaLateNightPeak'] ;
                $init['NightStartMile'] = $vt['StartMile']['usuallyLateNightPeak'] ;
            }
            if(!empty($vt['mileage_fee'])){                      //里程费
                $init['HolidaysMileageFee'] = $vt['mileage_fee']['festiva'] ;
                $init['HolidaysMorningMileageFee'] = $vt['mileage_fee']['festivalMorningPeak'] ;
                $init['HolidaysEveningMileageFee'] = $vt['mileage_fee']['festivalEveningPeak'] ;
                $init['HolidaysWeehoursMileageFee'] = $vt['mileage_fee']['festivalMidnight'] ;
                $init['MileageFee'] = $vt['mileage_fee']['usually'] ;
                $init['MorningMileageFee'] = $vt['mileage_fee']['usuallyMorningPeak'] ;
                $init['EveningMileageFee'] = $vt['mileage_fee']['usuallyEveningPeak'] ;
                $init['WeehoursMileageFee'] = $vt['mileage_fee']['usuallyMidnight'] ;

                $init['HolidaysNightMileageFee'] = $vt['mileage_fee']['festivaLateNightPeak'] ;
                $init['NightMileageFee'] = $vt['mileage_fee']['usuallyLateNightPeak'] ;
            }
            if(!empty($vt['how_fee'])){                          //时长费
                $init['HolidaysHowFee'] = $vt['how_fee']['festiva'] ;
                $init['HolidaysMorningHowFee'] = $vt['how_fee']['festivalMorningPeak'] ;
                $init['HolidaysEveningHowFee'] = $vt['how_fee']['festivalEveningPeak'] ;
                $init['HolidaysWeehoursHowFee'] = $vt['how_fee']['festivalMidnight'] ;
                $init['HowFee'] = $vt['how_fee']['usually'] ;
                $init['MorningHowFee'] = $vt['how_fee']['usuallyMorningPeak'] ;
                $init['EveningHowFee'] = $vt['how_fee']['usuallyEveningPeak'] ;
                $init['WeehoursHowFee'] = $vt['how_fee']['usuallyMidnight'] ;

                $init['HolidaysNightHowFee'] = $vt['how_fee']['festivaLateNightPeak'] ;
                $init['NightHowFee'] = $vt['how_fee']['usuallyLateNightPeak'] ;
            }

            //时间段
            if(!empty($vt['times'])){
                $init['MorningPeakTimeOn'] = $vt['times']['MorningPeakTimeOn'] ;
                $init['MorningPeakTimeOff'] = $vt['times']['MorningPeakTimeOff'] ;
                $init['EveningPeakTimeOn'] = $vt['times']['EveningPeakTimeOn'] ;
                $init['EveningPeakTimeOff'] = $vt['times']['EveningPeakTimeOff'] ;
                $init['UsuallyTimeOn'] = $vt['times']['UsuallyTimeOn'] ;
                $init['UsuallyTimeOff'] = $vt['times']['UsuallyTimeOff'] ;
                $init['weehoursOn'] = $vt['times']['weehoursOn'] ;
                $init['weehoursOff'] = $vt['times']['weehoursOff'] ;

                $init['HolidaysMorningOn'] = $vt['times']['HolidaysMorningOn'] ;
                $init['HolidaysMorningOff'] = $vt['times']['HolidaysMorningOff'] ;
                $init['HolidaysEveningOn'] = $vt['times']['HolidaysEveningOn'] ;
                $init['HolidaysEveningOff'] = $vt['times']['HolidaysEveningOff'] ;
                $init['HolidaysUsuallyOn'] = $vt['times']['HolidaysUsuallyOn'] ;
                $init['HolidaysUsuallyOff'] = $vt['times']['HolidaysUsuallyOff'] ;
                $init['HolidaysWeeOn'] = $vt['times']['HolidaysWeeOn'] ;
                $init['HolidaysWeeOff'] = $vt['times']['HolidaysWeeOff'] ;

                $init['HolidaysLateNightOn'] = $vt['times']['HolidaysLateNightOn'] ;
                $init['HolidaysLateNightOff'] = $vt['times']['HolidaysLateNightOff'] ;
                $init['UsuallyLateNightOn'] = $vt['times']['UsuallyLateNightOn'] ;
                $init['UsuallyLateNightOff'] = $vt['times']['UsuallyLateNightOff'] ;
            }
            if($vt['businesstype_id'] < 0){
                $vt['businesstype_id'] = 0 ;
            }
            $init['title'] = $vt['title'] ;

            Db::name('company_billing_template')->insert($init);
        }
    }
}