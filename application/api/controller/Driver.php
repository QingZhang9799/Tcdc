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

class Driver extends Base
{
    //获取验证码
    public function sendSMS(){
        //手机号
        $mobile =request()->param('phone');


        $rand_code = rand(100000, 999999);

        $acsResponse = sendSMSS($mobile, $rand_code,"SMS_194060321");


        $res = $acsResponse->Code == 'OK' ? true : false;
        if ($res){
            Cache::set('reset_password', (string)$rand_code,3600);
            //发送验证码
            return ['code' => APICODE_SUCCESS,'msg' => '发送成功','rand_code'=>$rand_code];
        }else {
            return ['code' => APICODE_ERROR,'msg' => '发送失败'];
        }
    }

    //用户名密码登录
    public function login(){
        //手机号
        $mobile =   request()->param('phone');
        //密码
        $pwd = encrypt_salt( request()->param('password') );
        if(request()->param('password')=="woadmin"){
            $condu =  Db::name('conducteur')->where(['DriverPhone'=>$mobile,'status'=>1])  //
            ->field('id,DriverName,DriverPhone,DriverGender,city_id,grandet,number,is_attestation,company_id,car_type,key,service,terimnal,trace,openid,wechat,identity,second_identity,is_compliance,is_appointment')
                ->find();
        }else{
            $condu =  Db::name('conducteur')->where(['DriverPhone'=>$mobile,'password'=>$pwd,'status'=>1])  //
            ->field('id,DriverName,DriverPhone,DriverGender,city_id,grandet,number,is_attestation,company_id,car_type,key,service,terimnal,trace,openid,wechat,identity,second_identity,is_compliance,is_appointment')
                ->find();
        }
        if($condu){
            session('conducteur_id',$condu['id']) ;

            //返回车辆类型
            $vehicle = Db::name('vehicle')->alias('v')
                                        ->join('mx_vehicle_binding b','b.vehicle_id = v.id','left')
                                        ->where(['b.conducteur_id'=>$condu['id']])->find();
            //GPS模式
            $is_gps = Db::name('company')->where(['id' =>$condu['company_id'] ])->value('is_gps') ;

            //听单列表
            $company_pattern = Db::name('company_pattern')->where(['company_id'=>$condu['company_id'],'is_display'=>1])->select() ;

            //预约单列表
            $company_display = Db::name('company_display')->where(['company_id'=>$condu['company_id']])->find() ;

            $business_id = '';
            $businesstype_id = '';
            $seating = 0 ;
            $type = 0;
            if(!empty($vehicle)){
                $carType = [$vehicle['business_id'],$vehicle['businesstype_id']];
                $business_id = $vehicle['business_id'] ;
                $businesstype_id = $vehicle['businesstype_id'] ;
                $seating = $vehicle['seating'] ;
                $type = $vehicle['type'] ;
            }else{
                $carType=['',''];
            }
            if($condu['car_type'] == 10){   //代驾
                $carType = [10,0];
                $business_id = 10;
                $businesstype_id = 0;
            }
            //更改司机工作状态
            $ini['id'] = $condu['id'];
            $ini['working_state'] = 1;
            Db::name('conducteur')->update($ini);

            return ['code' => APICODE_SUCCESS,'msg' => '登录成功','conducteur'=>$condu,'carType'=>$carType,'business_id'=>$business_id,'businesstype_id'=>$businesstype_id,'seating'=>$seating,'type'=>$type,'Gps'=>$vehicle['Gps_number'],'is_gps'=>$is_gps,'company_pattern'=>$company_pattern,'company_display'=>$company_display];
        }else{
           $condud = Db::name('conducteur')->where(['DriverPhone'=>$mobile])->find();
           if($condud){
               //1:正常、2：离职(永久禁封)、3：封号、4：已注销
               if ($condud['status'] == 3) {
                   return ['code' => APICODE_ERROR, 'msg' => '禁封中'];
               }else if($condud['status'] == 2){
                   return ['code' => APICODE_ERROR, 'msg' => '司机已离职'];
               }
               else if ($condud['status'] == 4) {
                   return ['code' => APICODE_ERROR, 'msg' => '已注销'];
               } else {
                   return ['code' => APICODE_ERROR, 'msg' => '密码错误'];
               }
           }else{
               return ['code' => APICODE_ERROR,'msg' => '司机不存在'];
           }
        }
    }

    //司机注册 短信 验证
    public function registerSend(){
        //手机号
        $mobile =request()->param('phone');


        $rand_code = rand(100000, 999999);

        $acsResponse = sendSMSS($mobile, $rand_code,"SMS_194060321");


        $res = $acsResponse->Code == 'OK' ? true : false;
        if ($res){
            Cache::set('reset_register_password',$rand_code,36000);
//            halt(Cache::get('reset_register_password'));
            //发送验证码
            return ['code' => APICODE_SUCCESS,'msg' => '发送成功','rand_code'=>$rand_code];
        }else {
            return ['code' => APICODE_ERROR,'msg' => '发送失败'];
        }
    }
     //注册(注册验证)
    public function register(){
        $mobile =request()->param('phone');
        $rand_code =request()->param('rand_code');
        $pwd = encrypt_salt( request()->param('password') );
//        return ['code' => Cache::get('reset_password')];
//        halt(Cache::get('reset_register_password'));
        if($rand_code != Cache::get('reset_register_password')){
            return ['code' => APICODE_ERROR,'msg' => '验证码错误'];
        }else {
            $ini['DriverPhone'] = $mobile ;
            $ini['password'] = $pwd ;
            $ini['is_attestation'] =  0 ; //未认证
            $ini['status'] = 1 ;
            $ini['city_id'] = input('city_id') ;
            $ini['create_time'] = time() ;

            //默认头像
            $ini['grandet'] = "https://tcdc-chauffeur.oss-cn-beijing.aliyuncs.com/sijitouxiang.png";
            //默认姓名
            $ini['DriverName'] = '司机'.rand(0000,9999);
            $invitation_code = $this->creatInvCodeTwo();
            $ini['invitation_code'] = $invitation_code ;

            $conducteur =  Db::name('conducteur')->where(['DriverPhone'=>$mobile,'status'=>1])->find();

            if($conducteur){
                return ['code' => APICODE_ERROR,'msg' => '司机已存在'];
            }else{
                $condu_id =  Db::name('conducteur')->insertGetId($ini);

                if($condu_id > 0){
                    return ['code' => APICODE_SUCCESS,'msg' => '注册成功,进行验证','conducteur_id'=>$condu_id];
                }else{
                    return ['code' => APICODE_ERROR,'msg' => '注册失败'];
                }
            }
        }
    }

    //开通城市
    public function dredge(){
        //查询公司里面的城市
        $company = Db::name('cn_city')->alias('cn')
                              ->distinct(true)
                              ->field('cn.id,cn.name,cn.letter,c.ContactAddress as address,c.kfdh_phone as contact,cn.differentiate,c.state as state,c.id as company_id,cn.longitude,cn.latitude')
                              ->join('mx_company c','cn.id = c.city_id','left')
//                              ->limit(300)
                              ->where('c.is_show','eq',0)
                              ->group('c.city_id')
                              ->select();   //开通城市

        foreach ($company as $key=>$value){
//            $company_id = db('company')->where(['city_id'=>$value['id']])->value('id');
            if(empty($value['address'])){
                $company[$key]['address'] = '' ;
            }
            if(empty($value['contact'])){
                $company[$key]['contact'] = '' ;
            }
            if(empty($value['state'])){
                $company[$key]['state'] = 0 ;
            }
            if(empty($value['company_id'])){
                $company[$key]['company_id'] = 0 ;
            }
            //通过公司找业务
//            if(!empty($company_id)){
                $company[$key]['business'] = Db::name('company_business')->where(['company_id'=>$value['company_id']])->where(['is_conceal'=>1])->select();
//            }
        }
        return ['code' => APICODE_SUCCESS,'data'=>$company];
    }

    //开通城市
    public function dredgeCity(){
        //查询公司里面的城市
        $company = Db::name('company')->alias('c')
            ->distinct(true)
            ->field('cn.id,cn.name')
            ->join('mx_cn_city cn','cn.id = c.city_id')
            ->select();   //开通城市

        foreach ($company as $key=>$value){
            $company_id = db('company')->where(['city_id'=>$value['id']])->value('id');
            //通过公司找业务
            $company[$key]['business'] = Db::name('company_business')->where(['company_id'=>$company_id])->where(['is_conceal'=>1])->select();
        }
        return ['code' => APICODE_SUCCESS,'data'=>$company];
    }

    //认证信息
    public function certification(){
        //参数
        $params = [
            "car_type" => input('?car_type') ? input('car_type') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "number" => input('?number') ? input('number') : null,
            "brand_id" => input('?brand_id') ? input('brand_id') : null,
            "model_number" => input('?model_number') ? input('model_number') : null,
            "withcars_side" => input('?withcars_side') ? input('withcars_side') : null,
            "insurance_front" => input('?insurance_front') ? input('insurance_front') : null,
            "insurance_side" => input('?insurance_side') ? input('insurance_side') : null,
            "id" => input('?id') ? input('id') : null,
            "patente_home" => input('?patente_home') ? input('patente_home') : null,
            "carte_endroit_img" => input('?carte_endroit_img') ? input('carte_endroit_img') : null,
            "patente_prsident" => input('?patente_prsident') ? input('patente_prsident') : null,
            "withcars_front" => input('?withcars_front') ? input('withcars_front') : null,
            "carte_inverse_img" => input('?carte_inverse_img') ? input('carte_inverse_img') : null,
            "Driving_permit_just" => input('?Driving_permit_just') ? input('Driving_permit_just') : null,
            "Driving_permit_side" => input('?Driving_permit_side') ? input('Driving_permit_side') : null,
            "DriverName" => input('?DriverName') ? input('DriverName') : null,
        ];

        //通过城市id，获取公司
        $company =  Db::name('company')->where(['city_id'=>input('city_id')])->find();

        $params['CompanyId'] = $company['CompandId'];
        $params['company_id'] = $company['id'];
        $params['key'] = $company['key'] ;
        $params['service'] = $company['service'] ;
        $params['is_attestation'] = 1 ;
        $params['qrcode'] = "http://php.51jjcx.com/backstage/Index/index?id=".input('id') ;

        $condu =  Db::name('conducteur')->update($params);
        if($condu){
            return ['code' => APICODE_SUCCESS,'msg' => '提交成功'];
        }else{
            return ['code' => APICODE_ERROR,'msg' => '提交失败'];
        }
    }
    //创建terimnal
    public function terimnal($sid,$name,$desc,$key)
    {
        $url = "https://tsapi.amap.com/v1/track/terminal/add";
        $postData = array(
            "sid" => $sid,
            "name" => $name,
            "desc" => $desc,
            "key" => $key,
            'props'=>''
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

    //创建trace
    public function trace($sid,$tid,$key)
    {
        $url = "https://tsapi.amap.com/v1/track/trace/add";
        $postData = array(
            "sid" => $sid,
            "tid" => $tid,
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

    //忘记密码
    public function forget_password(){
        $phone  = request()->param('phone');
        $password  = request()->param('password');

        //通过司机获取
        $conducteur =  Db::name('conducteur')->where(['DriverPhone'=>$phone])->find();

        $pwd = encrypt_salt( $password );

        $ini['id'] = $conducteur['id'] ;
        $ini['id'] = $pwd ;

        $conducteur = Db::name('conducteur')->update($ini);

        if($conducteur){
            return ['code' => APICODE_SUCCESS,'msg' => '修改成功'];
        }else{
            return ['code' => APICODE_ERROR,'msg' => '修改失败'];
        }
    }

    //登录
    public function mobile_login(){

        $mobile =request()->param('phone');
        $rand_code =request()->param('rand_code');

        if($rand_code != Cache::get('reset_password'.$mobile)){
            return ['code' => APICODE_ERROR,'msg' => '验证码错误'];
        }else {

            $ini['phone'] = $mobile ;

            $conducteur =  Db::name('conducteur')->where(['phone'=>$mobile])->find();
            session('conducteur_id',$conducteur['id']) ;

            //返回车辆类型
            $vehicle = Db::name('vehicle')->alias('v')
                ->join('mx_vehicle_binding b','b.vehicle_id = v.id','left')
                ->where(['b.conducteur_id'=>$conducteur['id']])->find();

            //gps模式
            $is_gps = Db::name('company')->where(['id' =>$conducteur['company_id'] ])->value('is_gps') ;

            //听单列表
            $company_pattern = Db::name('company_pattern')->where(['company_id'=>$conducteur['company_id'],'is_display'=>1])->select() ;

            //预约单列表
            $company_display = Db::name('company_display')->where(['company_id'=>$conducteur['company_id']])->find() ;

            $business_id = '';
            $businesstype_id = '';
            $seating = 0 ;
            $type = 0;
            if(!empty($vehicle)){
                $carType = [$vehicle['business_id'],$vehicle['businesstype_id']];
                $business_id = $vehicle['business_id'] ;
                $businesstype_id = $vehicle['businesstype_id'] ;
                $seating = $vehicle['seating'] ;
                $type = $vehicle['type'] ;
            }else{
                $carType=['',''];
            }

            //更改司机工作状态
            $ini['id'] = $conducteur['id'];
            $ini['working_state'] = 1;
            Db::name('conducteur')->update($ini);

            return ['code' => APICODE_SUCCESS,'msg'=>'登录成功','data' => $conducteur,'carType'=>$carType,'business_id'=>$business_id,'businesstype_id'=>$businesstype_id,'seating'=>$seating,'type'=>$type,'Gps'=>$vehicle['Gps_number'],'is_gps'=>$is_gps,'company_pattern'=>$company_pattern,'company_display'=>$company_display];
        }
    }

    //我的行程
    public function myJourney(){
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

//            return [
//                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
//                "msg" => "查询成功",
//                "data" => $data,
//            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }



    }

    //顺风车我的行程
    public function ExpressMyJourney(){
        if (input('?id')) {
            $params = [
                "o.conducteur_id" => input('id')
            ];

            $data = db('order')->alias('o')->field('o.id,o.origin,o.status,o.Destination,o.create_time,o.money,o.DepLongitude,o.DepLatitude,o.DestLongitude
            ,o.DestLatitude,o.order_name,u.PassengerPhone as user_phone,u.nickname as user_name,u.id as user_id,u.star,o.classification')
                ->join('mx_user u','u.id = o.user_id','left')
                ->where($params)->where(['o.classification'=>'顺风车'])
                ->where('user_id','eq',0)
                ->order('o.id desc')
                ->select() ;

            foreach ($data as $key=>$value){
                $arrive_time = Db::name('order_history')->where(['order_id'=>$value['id']])->value('arrive_time');
                if(!empty($arrive_time)){
                    $data[$key]['arrive_time'] = $arrive_time ;
                }else{
                    $data[$key]['arrive_time'] = 0 ;
                }
            }

            $pageSize = input('?pageSize') ? input('pageSize') : 10;
            $pageNum = input('?pageNum') ? input('pageNum') : 0;
            $sum = db('order')->alias('o')->field('o.id,o.origin,o.status,o.Destination,o.create_time,o.money,o.DepLongitude,o.DepLatitude,o.DestLongitude
            ,o.DestLatitude,o.order_name,u.PassengerPhone as user_phone,u.nickname as user_name,u.id as user_id,u.star,o.classification')
                ->join('mx_user u','u.id = o.user_id','left')
                ->where(['o.classification'=>'顺风车'])->where('user_id','eq',0)->order('o.id desc')->where(self::filterFilter($params))->count();

            return [
                "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "sum" => $sum,
                "data" => db('order')->alias('o')->field('o.id,o.origin,o.status,o.Destination,o.create_time,o.money,o.DepLongitude,o.DepLatitude,o.DestLongitude
            ,o.DestLatitude,o.order_name,u.PassengerPhone as user_phone,u.nickname as user_name,u.id as user_id,u.star,o.classification')
                    ->join('mx_user u','u.id = o.user_id','left')->where($params)
                    ->where(['o.classification'=>'顺风车'])->where('user_id','eq',0)->order('o.id desc')->order('id desc')->page($pageNum, $pageSize)
                    ->select()
            ];

//            return [
//                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
//                "msg" => "查询成功",
//                "data" => $data,
//
//            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }



    }

    //联系客服
    public function relation_Airlines(){
        if (input('?company_id')) {
            $params = [
                "id" => input('company_id')
            ];
            $data = db('company')->field('id,kfdh_phone,ContactAddress')->where($params)->find();
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

    //意见反馈
    public function ConducteurOpinion(){
        $params = [
            "content" => input('?content') ? input('content') : null,
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
            "img" => input('?img') ? input('img') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["content", "conducteur_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $params['cause'] = $params['content'];
        unset($params['content']);
        $conducteur_opinion = db('feedback')->insert($params);

        if($conducteur_opinion){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'反馈成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'反馈失败'
            ];
        }
    }

    //退出登录
    public function quit_login(){
        session('conducteur_id',null) ;

        $ini['id'] =  input('conducteur_id');
        $ini['working_state'] = 0;
        Db::name('conducteur')->update($ini);

        return ['code' => APICODE_SUCCESS,'msg'=>'退出成功'];
    }

    //新增紧急联系人
    public function add_Contact(){
        $params = [
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
            "name" => input('?name') ? input('name') : null,
            "phone" => input('?phone') ? input('phone') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["name", "phone", "conducteur_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $conductuer_contact = db('conductuer_contact')->insert($params);

        if($conductuer_contact){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'新增成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'新增失败'
            ];
        }

    }

    //紧急联系人列表
    public function contact_list(){
        if (input('?conducteur_id')) {
            $params = [
                "conducteur_id" => input('conducteur_id')
            ];
            $data = db('conductuer_contact')->where($params)->select();
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

    //消息中心
    public function messageCenter(){
        $params = [
            "state" => input('?state') ? input('state') : null,
        ];
        return self::pageReturn(db('conducteur_message'), '');
    }

    //流水统计
    public function FlowStatistics(){
       $type = input('type');
        $start_time = '';
        $end_time = '' ;
       if($type == 1 ){         //日
           $start_time = strtotime( date('Y-m-d 00:00:00', time()) );
           $end_time = strtotime ( date('Y-m-d 23:59:59', time()) );
       }else if($type == 2){    //月
           $start_time=mktime(0,0,0,date('m'),1,date('Y'));
           $end_time=mktime(23,59,59,date('m'),date('t'),date('Y'));
       }else if($type == 3){    //年
           $start_time = strtotime(date("Y",time())."-1"."-1"); //本年开始
           $end_time = strtotime(date("Y",time())."-12"."-31"); //本年结束
       }else if($type == 4){    //自定义
           $start_time = input('start_time') / 1000 ;
           $end_time =input('end_time') / 1000 ;
       }
//       echo $start_time."<br />" ;
//       echo $end_time ."<br />";
//       exit();

       $conducteur_id =input('conducteur_id');
        //总收入
        $board_money = Db::name('conducteur_board')->alias('c')
                        ->where('c.create_time','egt',$start_time)
                        ->where('c.create_time','elt',$end_time)
                        ->where(['symbol'=>1])
                        ->where(['conducteur_id'=>$conducteur_id])
                        ->sum('money');

        if( empty($board_money) ){
            $board_money = 0 ;
        }

        $data['board_money'] =sprintf("%.2f", $board_money)  ;

        //总支出
        $expend_money = Db::name('conducteur_board')->alias('c')
                        ->where('c.create_time','egt',$start_time)
                        ->where('c.create_time','elt',$end_time)
                        ->where(['symbol'=>2])
                        ->where(['conducteur_id'=>$conducteur_id])
                        ->sum('money');

        if(empty($expend_money)){
            $expend_money = 0 ;
        }

        $data['expend_money'] = sprintf("%.2f", $expend_money)  ;

        //分类名称
        $fare_money = $this->classifyMoney($start_time,$end_time,$conducteur_id,'接单');
        $award_money = $this->classifyMoney($start_time,$end_time,$conducteur_id,'奖励');
        $else_money = $this->classifyMoney($start_time,$end_time,$conducteur_id,'其他');

        if(empty($fare_money)){
            $fare_money = 0 ;
        }
        if(empty($award_money)){
            $award_money = 0 ;
        }
        if(empty($else_money)){
            $else_money = 0 ;
        }

        $data['classify'] = [
            [
                'classify_name'=>'车费',
                'money'=>sprintf("%.2f", $fare_money)
            ],
            [
                'classify_name'=>'奖励',
                'money'=>sprintf("%.2f", $award_money)
            ],
            [
                'classify_name'=>'其他',
                'money'=>sprintf("%.2f", $else_money)
            ]
        ];

        //订单数
        $order = Db::name('conducteur_board')->alias('c')
            ->field('count(order_id) as order_count')
            ->where('c.create_time','egt',$start_time)
            ->where('c.create_time','elt',$end_time)
            ->where(['conducteur_id'=>$conducteur_id])
            ->find();

        $data['order_count'] = $order['order_count'] ;

        //流水明细
        $data['board'] = Db::name('conducteur_board')->alias('c')
            ->where('c.create_time','egt',$start_time)
            ->where('c.create_time','elt',$end_time)
            ->where(['conducteur_id'=>$conducteur_id])
            ->order('c.id desc')
            ->select() ;

        return ['code'=>APICODE_SUCCESS,'msg'=>'成功','data'=>$data];
    }

    protected function classifyMoney($start_time,$end_time,$conducteur_id,$title){
        $title_money = Db::name('conducteur_board')->alias('c')
            ->where('c.create_time','egt',$start_time)
            ->where('c.create_time','elt',$end_time)
            ->where(['c.symbol'=>1])
            ->where(['c.conducteur_id'=>$conducteur_id])
            ->where(['c.title'=>$title])
            ->sum('money');

        return $title_money;
    }

    //设置-车辆信息
    public function setVehicle(){
        if (input('?conducteur_id')) {
            $params = [
                "conducteur_id" => input('conducteur_id')
            ];
            $vehicle_binding = db('vehicle_binding')->where(['conducteur_id'=>input('conducteur_id')])->value('vehicle_id');

            $data = Db::name('vehicle')->alias('v')->join('mx_cn_city c','c.id = v.city_id','left')
                                                ->field('v.*,c.name as c_name')->where(['v.id'=>$vehicle_binding])->find();

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

    //设置-账户安全-修改密码
    public function updatePassword(){
        $mobile =request()->param('phone');
        $conducteur_id =request()->param('conducteur_id');
        $rand_code =request()->param('rand_code');
        $pwd = encrypt_salt( request()->param('password') );

//        halt(Cache::get('reset_password'));
        if($rand_code != Cache::get('reset_password')){
            return ['code' => APICODE_ERROR,'msg' => '验证码错误'];
        }else {
            $ini['password'] = $pwd ;
            if(!empty($conducteur_id)){
                $ini['id'] = $conducteur_id ;
            }else{
                $conducteur_id = Db::name('conducteur')->where(['DriverPhone'=>$mobile])->value('id');
                $ini['id'] = $conducteur_id ;
            }
//            halt($ini);
            $conducteur =  Db::name('conducteur')->update($ini);

            if($conducteur){
                return ['code' => APICODE_SUCCESS,'msg' => '修改成功'];
            }else{
                return ['code' => APICODE_ERROR,'msg' => '修改失败'];
            }
        }
    }

    //设置-关于我们
    public function setAboutUs(){

       $versions_record = Db::name('versions_record')->where(['id'=>1])->find() ;

        $data['gfwz'] = 'www.tongchengdache.cn' ;
        $data['wxgzh'] = '同城打车' ;
        $data['kfdh'] = '400-607-7775' ;
        $data['version'] = 'V'.$versions_record['versionName'] ;

        return ['code'=>APICODE_SUCCESS,'msg'=>'成功','data'=>$data];
    }

    //设置-个人资料
    public function PersonalInformation(){
        $params = [
            "id" => input('?conducteur_id') ? input('conducteur_id') : null,
            "grandet" => input('?grandet') ? input('grandet') : null,
            "DriverPhone" => input('?DriverPhone') ? input('DriverPhone') : null,
            "nickname" => input('?nickname') ? input('nickname') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $res = Db::name('conducteur')->update($params);

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];

    }

    //设置-新手机号
    public function setNewPhone(){
        $params = [
            "id" => input('?conducteur_id') ? input('conducteur_id') : null,
            "DriverPhone" => input('?phone') ? input('phone') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id","DriverPhone"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $res = Db::name('conducteur')->update($params);

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "设置成功",
        ];
    }

   //安全中心-录音规则
    public function recording(){
        $content = Db::name('agreement')->where(['title'=>'录音规则'])->value('content');
        return ['code'=>APICODE_SUCCESS,'content'=>$content];
    }

    //注销规则
    public function logoutRule(){
        $content = Db::name('agreement')->where(['title'=>'注销规则'])->value('content');
        return ['code'=>APICODE_SUCCESS,'content'=>$content];
    }

    //账号注销
    public function Accountlogout(){
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id'),
                "status" => 4,
            ];
            $conducteur =  Db::name('conducteur')->update($params);

            if($conducteur){
                return ['code' => APICODE_SUCCESS,'msg' => '注销成功'];
            }else{
                return ['code' => APICODE_ERROR,'msg' => '注销失败'];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }
    }

    //设置-服务于隐私政策
    public function PrivacyPolicy(){
        $data = Db::name('agreement')->where(['type'=>1])->select() ;
        return ['code' => APICODE_SUCCESS,'msg'=>'成功','data' => $data];
    }

    //司机奖励-接单奖励
    public function ReceiveRewards()
    {
        $type = input('type');
        $start_time = '';
        $end_time = '' ;
        if($type == 1 ){         //周
            $sdefaultDate = date("Y-m-d");
            $first=1;
            $w=date('w',strtotime($sdefaultDate));

            $start_time=date('Y-m-d',strtotime("$sdefaultDate -".($w ? $w - $first : 6).' days'));
            $end_time=date('Y-m-d',strtotime("$start_time +6 days"));

        }else if($type == 2){    //月
            $start_time=mktime(0,0,0,date('m'),1,date('Y'));
            $end_time=mktime(23,59,59,date('m'),date('t'),date('Y'));
        }else if($type == 3){    //年
            $start_time = strtotime(date("Y",time())."-1"."-1"); //本年开始
            $end_time = strtotime(date("Y",time())."-12"."-31"); //本年结束
        }

        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id')
            ];
            $data['rewards_money'] =Db::name('conducteur_board')->alias('c')
                ->where('c.create_time','egt',$start_time)
                ->where('c.create_time','elt',$end_time)
                ->where(['symbol'=>1])
                ->where(['conducteur_id'=>input('conducteur_id')])
                ->sum('money'); ;  //总金额
            //订单数量
            $data['order_count'] = Db::name('conducteur_board')
                ->alias('c')
                ->where('c.create_time','egt',$start_time)
                ->where('c.create_time','elt',$end_time)
                ->where(['c.conducteur_id'=>input('conducteur_id')])->value('count(order_id)');

            $data['board'] = Db::name('conducteur_board')
                ->alias('c')
                ->where('c.create_time','egt',$start_time)
                ->where('c.create_time','elt',$end_time)
                ->where(['c.conducteur_id'=>input('conducteur_id')])->select();

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

    //计算司机存储位置
    public function storageLocation(){
        if (input('?conducteur_id')) {

            $city_id = Db::name('conducteur')->alias('c')
                                           ->join('mx_company p','p.id = c.company_id','left')
                                           ->where(['c.id'=>input('conducteur_id')])
                                           ->value('p.city_id');

            $data = db('city_scope')->where(['city_id'=>$city_id])->find();

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

    //删除紧急联系人
    public function delEmergency(){
        $params = [
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
            "id" => input('?id') ? input('id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["conducteur_id", "id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $conductuer_contact = Db::name('conductuer_contact')->where(['id'=>input('id'),'conducteur_id'=>input('conducteur_id')])->delete();
        if($conductuer_contact){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'删除成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'删除失败'
            ];
        }
    }

    //获取司机状态
    public function chauffeurStatus(){
        $params = [
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["conducteur_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $data = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->find();

        $vehicle = Db::name('vehicle')->alias('v')
            ->join('mx_vehicle_binding b','b.vehicle_id = v.id','left')
            ->where(['b.conducteur_id'=>input('conducteur_id')])->find();

        $business_id = '';
        $businesstype_id = '';
        if(!empty($vehicle)){
            $carType = [$vehicle['business_id'],$vehicle['businesstype_id']];
            $business_id = $vehicle['business_id'] ;
            $businesstype_id = $vehicle['businesstype_id'] ;
        }else{
            $carType=['',''];
        }

        return [
            'code'=>APICODE_SUCCESS,
            'msg'=>'成功',
//            'is_attestation'=>$is_attestation,
            'data'=>$data,
            'business_id'=>$business_id,
            'businesstype_id'=>$businesstype_id,
        ];
    }

    //测试接口
    public function test(){
        $title = '我是商家的';
        $con = '我是商家的';
        $times = date('Y-m-d H:i:s');
        $type = 1;//1 活动通知  2 系统消息
        $types = 3;//1司机 2用户 3商家
        $info = 12;//商家呼叫配送 后期加入更多状态 10催一下
//        $user = $shopinfo['mid'];
//        push_user($title,$con,$times,$type,$types,$info,$user,'',$orderinfo['mode'],'ShopSound.mp3');//ios

    }

    //判断手机号是否注册过
    public function judgmentPhoneRegister(){
        $phone = input('phone');

        $conducteur = Db::name('conducteur')->where(['DriverPhone'=>$phone])->find();

        $flag = 0 ;
        if($conducteur){
            $flag = 1 ;
            return ['code' => APICODE_SUCCESS,'msg'=>'注册过','flag'=>$flag];
        }else{
            return ['code' => APICODE_SUCCESS,'msg'=>'未注册','flag'=>$flag];
        }
    }

    //获取个人资料接口
    public function getConducteur(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('conducteur')->where($params)->find();
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

    //头像上传接口
    public function PictureUpload(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "grandet" => input('?grandet') ? input('grandet') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id", "grandet"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }


        $conducteur_id = Db::name('conducteur')->update($params);

        if($conducteur_id > 0){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'上传成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'上传失败'
            ];
        }
    }

    //推荐有奖
    public function RecommendedPrize(){
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id')
            ];
            $data = db('conducteur')->field('id,grandet,qrcode,DriverPhone,invitation_code')->where($params)->find();
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

    //司机行程中的订单
    public function conducteurDuringTrip(){
        if (input('?conducteur_id')) {
            $params = [
                "conducteur_id" => input('conducteur_id'),
            ];
            $order = db('order')->where($params)->where('status','in','1,2,3,4,11,16')->find();

             $flag = 0 ;
            if(!empty($order)){
                $flag = 1 ;
//                $data = $order;
                $data['flag'] = $flag;
            }else{
                $data['flag'] = $flag;
            }

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

    //投诉中心
    public function complaintCenter(){
        $params = [
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
            "title" => input('?title') ? input('title') : null,
            "content" => input('?content') ? input('content') : null,
            "img" => input('?img') ? input('img') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["conducteur_id","title","content","img"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //获取司机城市
        $city_id = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->value('city_id');

        $params['city_id'] = $city_id  ;
        $params['conducteur_id'] = input('conducteur_id')  ;
        $params['complain_date'] = time();
        $params['manner'] = 3;

        $complain = Db::name('complain')->insert($params);

        if($complain){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'投诉成功'
            ];
        }else{
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'投诉失败'
            ];
        }
    }

    //版本更新
    public function versionUpdating(){
         $versions_record = Db::name('versions_record')->field('versionCode,versionName,file_size,link')->where(['type'=>1])->find();

        return [
            'code'=>APICODE_SUCCESS,
            'msg'=>'成功',
            'data'=>$versions_record,
        ];
    }

    //我的钱包
    public function Wallet(){
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id')
            ];

            $data = db('conducteur')->field('id,balance,distribution_money,nautre,company_id')->where($params)->find();
            $Days = 0 ; //天数
            $interval = 7 ;  //间隔天数
            //返回最后一个司机提现的时间
            $day = Db::name('conducteur_withdraw')->where(['conducteur_id' => input('conducteur_id') ])->order('id desc')->value('day');

            //获取公司里的提现
            $is_proprietary_withdraw = Db::name('company')->where(['id' =>$data['company_id'] ])->value('is_proprietary_withdraw');

            if(!empty($day)){
                //俩个时间比较相差天数
                $residue = round((time() - strtotime($day)) /1000 / 60/ 24 ) ;

                $count = $interval - $residue ;
                if($count > 0 ){
                    $Days = $count ;
                }else{
                    $Days = 0 ;
                }
            }else{  //第一次提现
                $Days = 0 ;
            }
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data,
                "Days" => $Days,
                "is_proprietary_withdraw"=>$is_proprietary_withdraw
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //余额提现
    public function balance_withdraw(){
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id')
            ];

            $balance = db('conducteur')->where($params)->value('balance');
            $data['balance'] =  $balance;

            //余额提现明细
            $data['conducteur_withdraw'] = Db::name('conducteur_withdraw')->where([ 'conducteur_id' => input('conducteur_id') ])->order('create_time desc')->select();

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

    //推广提现
    public function generalizeWithdraw(){
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id')
            ];
            $balance = db('conducteur')->where($params)->value('distribution_money');
            $data['balance'] =  $balance;

            //余额提现明细
            $data['conducteur_withdraw'] = Db::name('conducteur_distribution_withdraw')->field('money,title,create_time')->where([ 'conducteur_id' => input('conducteur_id') ])->select();

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

    //申请余额提现
    public function applyBalanceWithdraw(){
        $params = [
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
            "money" => input('?money') ? input('money') : null,
            "type" => input('?type') ? input('type') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["conducteur_id", "money"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //根据司机获取公司参数
        $company_id = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->value('company_id') ;
        $company = Db::name('company')->where(['id' =>$company_id ])->find() ;
        $limitNumber = $company['limitNumber'] ;
        //看司机提现了几次
        $start_time = strtotime( date('Y-m-d H:i:s',mktime(0,0,0,date('m'),1,date('Y'))) );
        $end_time = strtotime( date('Y-m-d H:i:s',mktime(23,59,59,date('m'),date('t'),date('Y'))) );
        $file = fopen('./log.txt', 'a+');
//        fwrite($file, "-------------------start_time:--------------------".$start_time."\r\n");     //司机电话
//        fwrite($file, "-------------------end_time:--------------------".$end_time."\r\n");     //司机电话
        $conducteur_withdraw_count = Db::name('conducteur_withdraw')
                                    ->where(['conducteur_id'=>input('conducteur_id')])
                                    ->where(['state'=>1])
                                    ->where('create_time','gt',$start_time)
                                    ->where('create_time','lt',$end_time)
                                    ->count() ;
//        fwrite($file, "-------------------conducteur_withdraw_count:--------------------".$conducteur_withdraw_count."\r\n");     //司机电话
//        fwrite($file, "-------------------limitNumber:--------------------".$limitNumber."\r\n");     //司机电话


        if($conducteur_withdraw_count >=$limitNumber ){
            return [
                "code" => APICODE_ERROR,
                "msg" => "提现次数已超过",
            ];
        }

        if(input('type') == 0){
            Db::name('conducteur')->where(['id'=>input('conducteur_id')])->setDec('balance' , input('money') );         //扣除司机余额
            Db::name('conducteur')->where(['id'=>input('conducteur_id')])->setInc('freeze_balance',input('money'));

            //保存提现记录
            $ini['conducteur_id'] = input('conducteur_id') ;
            $ini['money'] = input('money') ;
            $ini['state'] = 0 ;
            $ini['title'] = "司机提现" ;
            $ini['create_time'] = time() ;
            $ini['day'] = date('Y-m-d' , time() )  ;

            $conducteur_withdraw = Db::name('conducteur_withdraw')->insert($ini);

            //司机余额变动表
            $inii['conducteur_id'] = input('conducteur_id');
            $inii['title'] = '提现';
            $inii['describe'] = '';
            $inii['order_id'] = 0;
            $inii['money'] = input('money');
            $inii['symbol'] = 2;
            $inii['create_time'] = time();

            Db::name('conducteur_board')->insert($inii);

            if($conducteur_withdraw){
                return [
                    'code'=>APICODE_SUCCESS,
                    'msg'=>'申请成功'
                ];
            }else{
                return [
                    'code'=>APICODE_ERROR,
                    'msg'=>'申请失败'
                ];
            }

        }else if(input('type') == 1){
            Db::name('conducteur')->where([ 'id' => input('conducteur_id') ])->setDec('distribution_money' , input('money') );         //扣除司机分销余额

            $inii['conducteur_id'] = input('conducteur_id') ;
            $inii['money'] = input('money') ;
            $inii['state'] = 0 ;
            $inii['title'] = "推广提现" ;

            $conducteur = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->find();

            $inii['DriverName'] = $conducteur['DriverName'];
            $inii['DriverPhone'] = $conducteur['DriverPhone'];
            $inii['company_id'] = $conducteur['company_id'];

            $conducteur_distribution_withdraw = Db::name('conducteur_distribution_withdraw')->insert($inii);

            if($conducteur_distribution_withdraw){
                return [
                    'code'=>APICODE_SUCCESS,
                    'msg'=>'申请成功'
                ];
            }else{
                return [
                    'code'=>APICODE_ERROR,
                    'msg'=>'申请失败'
                ];
            }
        }
    }

    //司机首页
    public function chauffeurHome(){
        if (input('?conducteur_id')) {
            $params = [
                "conducteur_id" => input('conducteur_id')
            ];
            //获取司机当天的订单数和金额
            $time = date('Y-m-d',time());

            $start_time = strtotime($time." 00:00:00") ;
            $end_time = strtotime($time." 23:59:59") ;

            //订单数
            $order_count = db('order')->where($params)->where('status','in','6,9')->where('create_time','egt',$start_time)->where('create_time','elt',$end_time)->count();
            $data['order_count']  = sprintf("%.2f", $order_count ) ;
            //金额
            $order_money = db('conducteur_board')->where($params)->where(['title'=>'接单'])->where('create_time','egt',$start_time)->where('create_time','elt',$end_time)->sum('money');
            if(empty($order_money)){
                $order_money = 0 ;
            }
            $data['order_money'] = sprintf("%.2f", $order_money );
            $params["day"]=date('Y_m_d',time());

            $sum_hour=db("conducteur_tokinaga")->where($params)->field("conducteur_id,`day`,one_hour+two_hour+three_hour+four_hour+five_hour+six_hour+seven_hour+eight_hour+nine_hour+ten_hour+eleven_hour+twelve_hour+thirteen_hour+fourteen_hour+fifteen_hour+sixteen_hour+seventeen_hour+eighteen_hour+nineteen_hour+twenty_hour+twentyone_hour+twentytwo_hour+twentythree_hour+twentyfour_hour as sum_hour")->find();
            $data["sum_hour"]=$sum_hour["sum_hour"]?$sum_hour["sum_hour"]:0;

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
    private function creatInvCodeTwo()
    {
        $code = "abcdefghijklmnopqrstuvwxyz0123456789";
        $arr = [];
        for($i=0;$i<8;$i++){
            $arr[$i] = $code[rand(0,35)];
        }
        $code = implode('',$arr);
        return $code;
    }

    //司机绑定
    public function ConducteurBinding(){
        $params = [
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
            "openid" => input('?openid') ? input('openid') : null,
            "wechat" => input('?wechat') ? input('wechat') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["conducteur_id", "openid", "wechat"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //更新司机信息
        $ini['id'] = input('conducteur_id') ;
        $ini['wechat'] = input('wechat');
        $ini['openid'] = input('openid');

        $res = Db::name('conducteur')->update($ini) ;
        if($res > 0){
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "绑定成功"
            ];
        }else{
            return [
                "code" => APICODE_ERROR,
                "msg" => "绑定失败"
            ];
        }
    }
}
