<?php


namespace app\backstage\controller;

use app\api\model\Conducteur;
use app\api\model\Company;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Exception;
use OSS\Core\OssException;
use OSS\OssClient;
use think\Controller;
use think\Image;
use think\Loader;


class Driver extends Base
{
    /**
     * 根据筛选条件获取司机列表
     */
    public function getDriversByFilter()
    {
        $params = [
//            "c.is_attestation" => input('?is_attestation') ? ['eq',(int)input('is_attestation') ] : null,
            "patente_number" => input('?patente_number') ? input('patente_number') : null,
            "nautre" => input('?nautre') ? input('nautre') : null,
            "c.is_compliance" => input('?is_compliance') ? ['eq',input('is_compliance') ] : null,
            "is_voitures" => input('?is_voitures') ? input('is_voitures') : null,
            "is_autoritaire" => input('?is_autoritaire') ? input('is_autoritaire') : null,
            "is_tourne" => input('?is_tourne') ? input('is_tourne') : null,
            "tourne_certificat" => input('?tourne_certificat') ? input('tourne_certificat') : null,
            "type_service" => input('?type_service') ? input('type_service') : null,
            "compagnie_id" => input('?compagnie_id') ? input('compagnie_id') : null,
//            "car_type" => input('?car_type') ? input('car_type') : null,
//            "status" => input('?status') ? input('status') : null,
//            "DriverName" => input('?DriverName') ? ['like','%'.input('DriverName').'%'] : null,
//            "DriverGender" => input('?DriverGender') ? input('DriverGender') : null,
//            "DriverPhone" => input('?DriverPhone') ? ['like','%'.(int)input('DriverPhone').'%'] : null
        ];
        $where = [];
        if(empty(input('status')) || input('status') == "0"){
            unset($params['status']);
        }else{
            $params['c.status'] = ['eq',input('status') ] ;
        }
        if (empty(input('DriverName')) || input('DriverName') == "null") {
            unset($params['DriverName']);
        }else{
            $params['DriverName'] = ['like','%'.input('DriverName').'%'] ;
        }
        if (empty(input('DriverPhone')) || input('DriverPhone') == "null") {
            unset($params['DriverPhone']);
        }else{
            $params['DriverPhone'] = ['like','%'.input('DriverPhone').'%'];
        }

        if (empty(input('is_attestation')) || input('is_attestation') == "-1" || input('is_attestation') == -1) {
            unset($params['is_attestation']);
        }else{
            $params['is_attestation'] = ['eq',(int)input('is_attestation') ] ;
        }
        if (empty(input('DriverGender')) || input('DriverGender') == 0) {
//            unset($params['DriverGender']);
        }else{
            $params['DriverGender'] = ['eq', input('DriverGender') ] ;
        }
        if (empty(input('city_id')) || input('city_id') == "null") {
            unset($params['city_id']);
        }else{
            $params['c.city_id'] = ['eq' , input('city_id')] ;
        }
        if (empty(input('bd_chars')) || input('bd_chars') == "null") {
            unset($params['bd_chars']);
        }else{
            $params['v.VehicleNo'] = ['like','%'.input('bd_chars').'%'] ;
        }
        if (empty(input('number')) || input('number') == "null") {
            unset($params['number']);
        } else {
            $params['number'] = ['like', '%' . input('number') . '%'];
        }
        if(empty(input('car_type')) || input('car_type') == "null"){
            unset($params['car_type']);
        }else{
            $params['c.car_type'] = ['eq', intval(input('car_type'))];
        }
        if(empty(input('company_id')) || input('company_id') == "null"){
            unset($params['company_id']);
        }else{
            $params['c.company_id'] = ['eq', intval(input('company_id'))];
        }
        $where1 = [] ; $where2 = [] ;
        if(empty(input('create_time')) || input('create_time') == "null"){
            unset($params['create_time']);
        }else{
            $time = explode(',',input('create_time'))  ;
            $start_time = strtotime( $time[0].' 00:00:00' ) ;
            $end_time = strtotime( $time[1].' 23:59:59' ) ;
            $where1['c.create_time'] = ['gt', $start_time];
            $where2['c.create_time'] = ['lt', $end_time];
        }
        if (empty(input('is_compliance')) || input('is_compliance') == "-1") {
            unset($params['is_compliance']);
        }else if(input('is_compliance') == "1"){
            $params['c.is_compliance'] = ['eq',(int)input('is_compliance') ] ;
        }

        $subSql = db("order")->field("conducteur_id,count(id) as count,sum(money) as sumMoney")->group("conducteur_id")->buildSql();//完成订单数及总额

        $stort = 'id desc' ;

        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;
        $sortBy=input('?orderBy') ? input('orderBy') : $stort;

        $sum = 0 ;
        $data = [] ;

        $data=db("conducteur c")
            ->join("vehicle_binding vb", "c.id=vb.conducteur_id", "left")
            ->join("vehicle v", "vb.vehicle_id=v.id", "left")
            ->join("mx_business_type b", "b.id=v.businesstype_id", "left")
            ->join("cn_city cn","cn.id = c.city_id",'left')
            ->join([$subSql=>"o"],"o.conducteur_id=c.id","left")
            ->field("c.*,v.VehicleNo,cn.name as city_name,b.title as b_title")
            ->where($where)
            ->where($where1)
            ->where($where2)
            ->where(self::filterFilter($params))->order($sortBy)->page($pageNum, $pageSize)
            ->select();

        $sum = db("conducteur c")
            ->join("vehicle_binding vb", "c.id=vb.conducteur_id", "left")
            ->join("vehicle v", "vb.vehicle_id=v.id", "left")
            ->join([$subSql=>"o"],"o.conducteur_id=c.id","left")
            ->where($where)
            ->where($where1)
            ->where($where2)
            ->where(self::filterFilter($params))->count();

        $Mileages = 0 ;
        foreach ($data as $key=>$value){
            $order = Db::name('order')->where(['conducteur_id' => $value['id'] ])->select() ;
            foreach ($order as $k=>$v){
                $total_price = json_decode($v['total_price'], true);
                $Mileages += $total_price['Mileage'] ;
                $data[$key]['Mileage'] =sprintf("%.2f", ($Mileages));
            }
            //car_type处理
            if(!empty($value['identity'])){
                $identity = json_decode($value['identity'],true) ;
                $business_id = "" ;
                foreach ($identity as $kkk=>$vvv){
                    //根据业务取名称
                    $business_name = Db::name('business')->where(['id' =>$vvv['business_id'] ])->value('business_name');
                    $business_id .= $business_name."," ;
                }
                $data[$key]['car_type'] = substr($business_id,0,-1)  ;
            }else{
                $business_name = Db::name('business')->where(['id' =>$value['car_type'] ])->value('business_name');
                $data[$key]['car_type'] = $business_name ;
            }
        }

        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" => $data
        ];
    }

    public function getDriverOnlineDate(){
        $driverId=input("driverId");
        $date=input("date");
        if(!$driverId||!$date){
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" =>"缺少关键参数"
            ];
        }
        $driverInfos = [] ;
        if( input('dataType') == 1 ){
            $driverInfos=db()->query("call getDriverOnlineTimeByIdAndDate($driverId,'$date')");
            $driverInfos=$driverInfos[0];
        }

        return [
            "code" => APICODE_SUCCESS,
            "msg" =>"",
            "data"=>$driverInfos
        ];
    }
    public function setDriverState()
    {
        if (input('?id') && input('?status') && input('?cause')) {
            $params = [
                "id" => input('id')
            ];
            db('conducteur')->where($params)->setField('status', input('status'));
            $data = [
                'conducteur_id' => input('id'),
                'cause' => input('cause'),
                'type' => input('status'),
            ];
            db('conducteur_banned')->insert($data);
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "操作成功"
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }


    }

    public function getDriverInfoByID()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('conducteur')->where($params)->find();

//            if(!empty($data['identity'])){
//                $identity = json_decode($data['identity'],true) ;
//                $str = [] ;
//
//                foreach ($identity as $key=>$value){
//                    $str[] = $value['business_id'] ;
//                }
//                $data['car_type'] = $str  ;
//            }else{
//                $data['car_type'] = []  ;
//            }
            $data['car_type'] = [$data['car_type']];
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

    public function setDriverInfo()
    {
        $datas = input('') ;
        $params = [
            "id" => input('?id') ? input('id') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "DriverName" => input('?DriverName') ? input('DriverName') : null,
            "DriverGender" => input('?DriverGender') ? input('DriverGender') : null,
            "number" => input('?number') ? input('number') : null,
            "DriverPhone" => input('?DriverPhone') ? input('DriverPhone') : null,
            "patente_number" => input('?patente_number') ? input('patente_number') : null,
            "carte_endroit_img" => input('?carte_endroit_img') ? input('carte_endroit_img') : null,
            "carte_inverse_img" => input('?carte_inverse_img') ? input('carte_inverse_img') : null,
            "bd_chars" => input('?bd_chars') ? input('bd_chars') : null,
            "patente_home" => input('?patente_home') ? input('patente_home') : null,
            "patente_prsident" => input('?patente_prsident') ? input('patente_prsident') : null,
            "nautre" => input('?nautre') ? input('nautre') : null,
            "grandet" => input('?grandet') ? input('grandet') : null,
            "conducteur_img" => input('?conducteur_img') ? input('conducteur_img') : null,
            "sex" => input('?sex') ? input('sex') : null,
            "is_voitures" => input('?is_voitures') ? input('is_voitures') : null,
            "is_autoritaire" => input('?is_autoritaire') ? input('is_autoritaire') : null,
            "is_tourne" => input('?is_tourne') ? input('is_tourne') : null,
            "tourne_certificat" => input('?tourne_certificat') ? input('tourne_certificat') : null,
            "auhor" => input('?auhor') ? input('auhor') : null,
            "concesseur_start_time" => input('?concesseur_start_time') ? input('concesseur_start_time') : null,
            "concesseur_end_time" => input('?concesseur_end_time') ? input('concesseur_end_time') : null,
            "demande_initiale_time" => input('?demande_initiale_time') ? input('demande_initiale_time') : null,
            "tourne_certificat_img" => input('?tourne_certificat_img') ? input('tourne_certificat_img') : null,
            "type_service" => input('?type_service') ? input('type_service') : null,
            "compagnie_id" => input('?compagnie_id') ? input('compagnie_id') : null,
            "contrat_start_time" => input('?contrat_start_time') ? input('contrat_start_time') : null,
            "contrat_end_time" => input('?contrat_end_time') ? input('contrat_end_time') : null,
            "signer_time" => input('?signer_time') ? input('signer_time') : null,
            "reprsenter" => input('?reprsenter') ? input('reprsenter') : null,
            "contrat_accessoire" => input('?contrat_accessoire') ? input('contrat_accessoire') : null,
            "clore_name" => input('?clore_name') ? input('clore_name') : null,
            "clore_banque" => input('?clore_banque') ? input('clore_banque') : null,
            "clore_adresse" => input('?clore_adresse') ? input('clore_adresse') : null,
            "status" => input('?status') ? input('status') : null,
            "is_attestation" => input('?is_attestation') ? input('is_attestation') : null,
            "model_number" => input('?model_number') ? input('model_number') : null,
            "brand_id" => input('?brand_id') ? input('brand_id') : null,
            "key" => input('?key') ? input('key') : null,
            "service" => input('?service') ? input('service') : null,
            "clore_account" => input('?clore_account') ? input('clore_account') : null,
            "vehicle_id" => input('?vehicle_id') ? input('vehicle_id') : null,
            "company_id" => input('?company_id') ? input('company_id') : null,
            "driving_age" => input('?driving_age') ? input('driving_age') : null,
            "is_compliance" => input('?is_compliance') ? input('is_compliance') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id", "DriverName"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //司机多身份
        $car_type = $datas['car_type'] ;
        $type = 0 ;              //中间值
        $car = [] ;
        $business_type_id = input('businesstype_id') ;
        foreach ($car_type as $key=>$value){  //2,10
            if($value == 1){            //快车
                $car[] = [
                    'business_id'=>$value ,
                    'business_type_id'=>$business_type_id ,
                ];
                $params['car_type'] = $value ;
                $type = $value ;
            }else if($value == 2){      //专车
                $car[] = [
                    'business_id'=>$value ,
                    'business_type_id'=>$business_type_id ,
                ];
                $params['car_type'] = $value ;
                $type = $value ;
            }else if($value == 11){
                $car[] = [
                    'business_id'=>$value ,
                    'business_type_id'=>$business_type_id ,
                ];
                $params['car_type'] = $value ;
            }else if($value == 10){
                $car[] = [
                    'business_id'=>$value ,
                    'business_type_id'=>$business_type_id ,
                ];
                if($type != 1 && $type != 2){
                    $params['car_type'] = $value ;
                }
            }else{
                $car[] = [
                    'business_id'=>$value ,
                    'business_type_id'=>0 ,
                ];
                $params['car_type'] = $value ;
            }
        }
//        $params['identity'] = json_encode($car) ;

        //处理高德
        $conducteur = Db::name('conducteur')->where(['id' => input('id')])->find();
        //修改的手机号
        $conducteurs = Db::name('conducteur')->where(['DriverPhone' => input('DriverPhone') ])->where('id','neq',input('id'))->find();
        if(!empty($conducteurs)){
            return [
                "code" => APICODE_ERROR,
                "msg" => "手机号已存在"
            ];
        }

        //创建终端
        if(empty($conducteur['terimnal'])){
            if (!empty($params['key'])) {
                $terimnal = $this->terimnal($conducteur['service'], $params['DriverName'] . round(0, 9999), '', $conducteur['key']);
                if (!empty($terimnal)) {
                    $autonavi_n = json_decode($terimnal, true);
                    $params['terimnal'] = $autonavi_n['data']['tid'];
                }
            }
        }

        //创建轨迹
        if( empty($conducteur['trace']) ){
            if (!empty($params['key'])) {
                $trace = $this->trace($conducteur['service'], $params['terimnal'], $conducteur['key']);
                if (!empty($trace)) {
                    $autonavi_t = json_decode($trace, true);
                    $params['trace'] = $autonavi_t['data']['trid'];
                }
            }
        }

        $id = $params["id"];
        unset($params["id"]);
        //创建二维码
//        $url = $this->Qrcodes($id);
        $res = db('conducteur')->where("id", $id)->update($params);

        //绑定车辆
        $vehicle_binding = Db::name('vehicle_binding')->where(['conducteur_id' => $id])->find();
        Db::name('vehicle_binding')->where(['id' => $vehicle_binding['id']])->delete();

        $ini['vehicle_id'] = input('vehicle_id');
        $ini['conducteur_id'] = $id;

        Db::name('vehicle_binding')->insert($ini);

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }

    //添加司机
    public function addDriverInfo()
    {
        $data = input('');

        $params = [
            "city_id" => input('?city_id') ? input('city_id') :  null,
            "DriverName" => input('?DriverName') ? input('DriverName') :  null,
            "number" => input('?number') ? input('number') :  null,
            "DriverPhone" => input('?DriverPhone') ? input('DriverPhone') :  null,
            "patente_number" => input('?patente_number') ? input('patente_number') :  null,
            "carte_endroit_img" => input('?carte_endroit_img') ? input('carte_endroit_img') :  null,
            "carte_inverse_img" => input('?carte_inverse_img') ? input('carte_inverse_img') :  null,
            "bd_chars" => input('?bd_chars') ? input('bd_chars') :  null,
            "patente_home" => input('?patente_home') ? input('patente_home') :  null,
            "patente_prsident" => input('?patente_prsident') ? input('patente_prsident') :  null,
            "nautre" => input('?nautre') ? input('nautre') :  null,
            "grandet" => input('?grandet') ? input('grandet') :  null,
            "conducteur_img" => input('?conducteur_img') ? input('conducteur_img') :  null,
            "is_voitures" => input('?is_voitures') ? input('is_voitures') :  null,
            "is_autoritaire" => input('?is_autoritaire') ? input('is_autoritaire') :  null,
            "is_tourne" => input('?is_tourne') ? input('is_tourne') :  null,
            "tourne_certificat" => input('?tourne_certificat') ? input('tourne_certificat') :  null,
            "auhor" => input('?auhor') ? input('auhor') :  null,
            "concesseur_start_time" => input('?concesseur_start_time') ? input('concesseur_start_time') :  null,
            "concesseur_end_time" => input('?concesseur_end_time') ? input('concesseur_end_time') :  null,
            "demande_initiale_time" => input('?demande_initiale_time') ? input('demande_initiale_time') :  null,
            "tourne_certificat_img" => input('?tourne_certificat_img') ? input('tourne_certificat_img') :  null,
            "type_service" => input('?type_service') ? input('type_service') :  null,
            "company_id" => input('?company_id') ? input('company_id') : null,
            "contrat_start_time" => input('?contrat_start_time') ? input('contrat_start_time') :  null,
            "contrat_end_time" => input('?contrat_end_time') ? input('contrat_end_time') :  null,
            "signer_time" => input('?signer_time') ? input('signer_time') :  null,
            "reprsenter" => input('?reprsenter') ? input('reprsenter') :  null,
            "contrat_accessoire" => input('?contrat_accessoire') ? input('contrat_accessoire') :  null,
            "clore_name" => input('?clore_name') ? input('clore_name') :  null,
            "clore_banque" => input('?clore_banque') ? input('clore_banque') :  null,
            "clore_adresse" => input('?clore_adresse') ? input('clore_adresse') :  null,
            "status" => input('?status') ? input('status') :  null,
            "is_attestation" => input('?is_attestation') ? input('is_attestation') :  null,
            "model_number" => input('?model_number') ? input('model_number') :  null,
            "brand_id" => input('?brand_id') ? input('brand_id') : null,
            "DriverGender" => input('?DriverGender') ? input('DriverGender') : null,
            "key" => input('?key') ? input('key') : null,
            "service" => input('?service') ? input('service') : null,
            "vehicle_id" => input('?vehicle_id') ? input('vehicle_id') : null,
            "clore_account" => input('?clore_account') ? input('clore_account') : null,
            "driving_age" => input('?driving_age') ? input('driving_age') : null,
            "is_compliance" => input('?is_compliance') ? input('is_compliance') : null,
        ];

        $params = $this->filterFilter($params);

        $required = ["DriverName", "DriverPhone"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        //手机号唯一
        $conducteur = Db::name('conducteur')->where(['DriverPhone' => input('DriverPhone') ])->find();
        if(!empty($conducteur)){
            return [
                "code" => APICODE_LOGINREQUEST,
                "msg" => "手机号已存在",
                "DriverName" => input('DriverName') ,
                "DriverPhone" => input('DriverPhone') ,
            ];
        }
        //司机多身份
        $car_type = $data['car_type'] ;
        $type = 0 ;             //中间值
        $car = [] ;
        $business_type_id = input('businesstype_id') ;
        foreach ($car_type as $key=>$value){  //2,10
            if($value == 1){            //快车
                $car[] = [
                   'business_id'=>$value ,
                   'business_type_id'=>$business_type_id ,
               ];
               $params['car_type'] = $value ;
               $type = $value ;
            }else if($value == 2){      //专车
                $car[] = [
                    'business_id'=>$value ,
                    'business_type_id'=>$business_type_id ,
                ];
                $params['car_type'] = $value ;
                $type = $value ;
            }else if($value == 11){
                $car[] = [
                    'business_id'=>$value ,
                    'business_type_id'=>$business_type_id ,
                ];
                $params['car_type'] = $value ;
            }else if($value == 10){
                $car[] = [
                    'business_id'=>$value ,
                    'business_type_id'=>$business_type_id ,
                ];
                if( $type != 1 && $type != 2 ){     //专车和快车，不加代驾
                    $params['car_type'] = $value ;
                }
            }else{
                $car[] = [
                    'business_id'=>$value ,
                    'business_type_id'=>0 ,
                ];
                $params['car_type'] = $value ;
            }
        }
//        $params['identity'] = json_encode($car) ;

        $params['create_time'] = time();
        //认证状态
//        $is_attestation = $data['is_attestation'] ? $data['is_attestation'] : null;
//        $open = '';
//        if (!empty($is_attestation)) {
//            foreach ($is_attestation as $key => $value) {
//
//                $open .= $value . ',';
//            }
//        }
//        $attestation = substr($open, 0, -1);
//        $params['is_attestation'] = $attestation;
        //默认头像
        $params['grandet'] = "https://tcdc-chauffeur.oss-cn-beijing.aliyuncs.com/sijitouxiang.png" ;
        //处理高德

        //创建终端
        if (!empty($params['key'])) {
            $terimnal = $this->terimnal($params['service'], $params['DriverName'], '', $params['key']);

            if (!empty($terimnal)) {
                $autonavi_n = json_decode($terimnal, true);

                $params['terimnal'] = $autonavi_n['data']['tid'];
            }
        }

        //创建轨迹
        if (!empty($params['key'])) {
            $trace = $this->trace($params['service'], $params['terimnal'], $params['key']);
            if (!empty($trace)) {
                $autonavi_t = json_decode($trace, true);

                $params['trace'] = $autonavi_t['data']['trid'];
            }
        }

        $params['status'] = 1;
        //初始密码 123456
        $params['password'] = encrypt_salt("123456") ;

//        if(!empty(input('car_type'))){
//            if( (int)input('car_type') == 5 ){
//                $params['is_attestation'] = 2 ;
//            }
//        }
        $invitation_code = $this->creatInvCodeTwo();
        $params['invitation_code'] = $invitation_code ;

        $conducteur_id = db('conducteur')->insertGetId($params);
        //更新推广码
        $inii['id'] = $conducteur_id ;
        $inii['qrcode'] = "http://php.51jjcx.com/backstage/Index/index?id=".$conducteur_id ;
        Db::name('conducteur')->update($inii);

        $vehicle_binding = Db::name('vehicle_binding')->where(['conducteur_id' => $conducteur_id])->find();
        Db::name('vehicle_binding')->where(['id' => $vehicle_binding['id']])->delete();

        $ini['vehicle_id'] = input('vehicle_id');
        $ini['conducteur_id'] = $conducteur_id;

        Db::name('vehicle_binding')->insert($ini);
        //创建二维码
//        $url = $this->Qrcodes($conducteur_id);

        //增加日志
//        $inii['title'] = "增加司机" ;
//        $inii['param'] = json_encode($params) ;
//        $inii['operate'] = "增加" ;
//        $inii['create_time'] = time() ;
//        $inii['manager_id'] = input('manager_id') ;
//        Db::name('manager_log')->insert($inii) ;

        return [
            "code" => APICODE_SUCCESS,
            "msg" => "添加成功",
        ];
    }

    //司机配置
    public function deploy()
    {

        $params = [
            "id" => input('?id') ? input('id') : null,
            "type" => input('?type') ? input('type') : null,
            "start_time" => input('?start_time') ? input('start_time') : null,
            "end_time" => input('?end_time') ? input('end_time') : null,
            "count" => input('?count') ? input('count') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["type"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $conducteur_deploy = db('conducteur_deploy')->insert($params);

        if ($conducteur_deploy) {
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

    //司机首页
    public function conducteur_home()
    {
        $where = [];
        $where1 = [];
        $where2 = [];
        $where3 = [];
        $where4 = [];
        $where5 = [];

        if (input('company_id') != 0) {           //分公司首页
            $where['u.city_id'] = ['eq', input('city_id')];
            $where1['c.city_id'] = ['eq', input('city_id')];
            $where2['company_id'] = ['eq', input('company_id')];
            $where3['o.city_id'] = ['eq', input('city_id')];
            $where4['city_id'] = ['eq', input('city_id')];
            $where5['e.city_id'] = ['eq', input('city_id')];
        }

        //全国线上司机总数
        $conducteur_count = Db::name('conducteur')
            ->join("conducteur_tokinaga t", "mx_conducteur.id=t.conducteur_id", "right")
            ->where('t.day', 'eq', date('Y_m_d'))
            ->where($where4)
            ->count();
        $data['conducteur_count'] = $conducteur_count;

        //全国在线总时长
        $online_time = Db::name('conducteur')->alias('c')
                                                      ->join('mx_conducteur_tokinaga t','t.conducteur_id = c.id','left')
                                                      ->where($where4)
                                                      ->sum('t.one_hour+two_hour+three_hour+four_hour+five_hour+six_hour+seven_hour+eight_hour+nine_hour+ten_hour+eleven_hour+twelve_hour+thirteen_hour+fourteen_hour+fifteen_hour+sixteen_hour+seventeen_hour+eighteen_hour+nineteen_hour+twenty_hour+twentyone_hour+twentytwo_hour+twentythree_hour+twentyfour_hour');

        if (empty($online_time)) {
            $online_time = 0;
        }
        $data['conducteur_online_time'] = $online_time;

        //全国今日营业额
        $todayStart = strtotime(date('Y-m-d 00:00:00', time()));

        $todayEnd = strtotime(date('Y-m-d 23:59:59', time()));

        $order_money = Db::name('order')->alias('o')
            ->where('o.create_time', 'egt', $todayStart)
            ->where('o.create_time', 'elt', $todayEnd)
            ->where($where3)
            ->sum('money');

        if (empty($order_money)) {
            $order_money = 0;
        }

        $data['order_money'] = $order_money;

        //全国订单总数
        $order_count = Db::name('order')->where($where4)->count();
        $data['order_count'] = $order_count;

        //全国上线司机总数排行
        $data['conducteur_Ranking'] = Db::name('conducteur')
            ->alias('e')
            ->join('mx_cn_city c', 'c.id = e.city_id', 'inner')
            ->field('c.name as c_name,count(e.id) as u_count')
            ->where($where5)
            ->group('e.city_id')->select();

        //全国司机有效时长
        $city_name = Db::name('conducteur')->alias('c')
            ->distinct(true)
            ->field('cc.name as c_name')
            ->join('mx_cn_city cc', 'cc.id = c.city_id', 'inner')
            ->where($where1)
            ->limit(3)
            ->select();

        //业务司机数
        $filiale_conducteur =  Db::name('conducteur')
                    ->alias('c')
                    ->field('b.business_name,count(c.id) as c_count')
                    ->join('mx_business b','b.id = c.car_type','inner')
                    ->where($where1)
                    ->group('c.car_type')->select();

        $Interval_o = 0; //订单的间隔天数
        $Interval_t = 0; //订单成交的间隔天数
        $Interval_d = 0; //订单总额的间隔天数

        $start_o = "";   //开始时间
        $end_o = "";    //结束时间
        $start_t = "";   //开始时间
        $end_t = "";    //结束时间

        $order_time = input('order_time');
        $transaction_time = input('transaction_time');
        $total_time = input('totall_time');

        //日期为null   本周
        $times = aweek("", 1);
        $beginThisweek = strtotime($times[0]);
        $endThisweek = strtotime($times[1]);

        if ($order_time == "null" || $order_time == "null " ) {
            $start_o = $beginThisweek;
            $end_o = $endThisweek;

            $Interval_o = diffBetweenTwoDays((int)$start_o, (int)$end_o);
        } else {
            $order_times = explode(',', $order_time);
            $start_o = strtotime( $order_times[0] ) ;
            $end_o = strtotime( $order_times[1] ) ;

            $Interval_o = diffBetweenTwoDays((int)$start_o, (int)$end_o);
        }

        if ($transaction_time == "null"|| $transaction_time == "null ") {
            $start_t = $beginThisweek;
            $end_t = $endThisweek;

            $Interval_t = diffBetweenTwoDays((int)$start_t, (int)$end_t);
        } else {
            $order_times = explode(',', $transaction_time);
            $start_t = strtotime($order_times[0]);
            $end_t = strtotime($order_times[1]);

            $Interval_t = diffBetweenTwoDays((int)$start_t, (int)$end_t);
        }
        if ($total_time == "null"|| $total_time == "null ") {
            $start = $beginThisweek;
            $end = $endThisweek;

            $Interval_d = diffBetweenTwoDays((int)$start, (int)$end);
        } else {
            $total_times = explode(',', $total_time);
            $start = strtotime( $total_times[0] );
            $end = strtotime( $total_times[1] ) ;

            $Interval_d = diffBetweenTwoDays((int)$start, (int)$end);
        }
        $order = [];
        for ($y = 0; $y <= $Interval_o; $y++) {     //行

            $op_o = date('Y-m-d', $start_o);

            $day_start_o = strtotime($op_o . ' 00:00:00');  //当天开始时间
            $day_end_o = strtotime($op_o . ' 23:59:59');    //当天结束时间
            $ini = date("Y-m-d",strtotime("+".$y." day",strtotime($op_o)));
            $times = date('m',strtotime($ini)). '_' .date('d',strtotime($ini)) ;
            $days =  $this->days(date('m', $start_o) );
//            if((((int)date('d', $start_o)) + $y) <= $days) {
                $order[] = [
                    'times' => date('m',strtotime($ini)). '/' .date('d',strtotime($ini)),
                    $city_name[0]['c_name'] => $this->Tokinaga($where4, $times,$city_name[0]['c_name']),
                    $city_name[1]['c_name'] => $this->Tokinaga($where4, $times,$city_name[1]['c_name']),
                    $city_name[2]['c_name'] => $this->Tokinaga($where4, $times,$city_name[2]['c_name']),
                ];
//            }
        }

        $data['cityOrdersTieData']['city_name'] = $city_name;
        $data['cityOrdersTieData']['conducteur'] = $order;

        //全国成单量走势
        $city_names = Db::name('order')->alias('o')
            ->distinct(true)
            ->field('c.name as c_name,count(o.id) as o_count')
            ->join('mx_cn_city c', 'c.id = o.city_id', 'inner')
            ->group('o.city_id')
            ->order('o_count desc')
            ->where($where3)
            ->limit(3)
            ->select();
        for ($y = 0; $y <= $Interval_t; $y++) {     //行

            $op_o = date('Y-m-d', $start_t);
//            halt($op_o);
            $day_start_o = strtotime($op_o . ' 00:00:00');  //当天开始时间
            $day_end_o = strtotime($op_o . ' 23:59:59');    //当天结束时间
            $ini = date("Y-m-d",strtotime("+".$y." day",strtotime($op_o)));
            $times = date('m',strtotime($ini)). '-' .date('d',strtotime($ini)) ;
            $days =  $this->days(date('m', $start_t) );

//            if((((int)date('d', $start_t)) + $y) <= $days) {
                $orders[] = [
                    'times' => date('m',strtotime($ini)). '/' .date('d',strtotime($ini)),
                    $city_names[0]['c_name'] => $this->OrderCount($city_names[0]['c_name'], $times),
                    $city_names[1]['c_name'] => $this->OrderCount($city_names[1]['c_name'], $times),
                    $city_names[2]['c_name'] => $this->OrderCount($city_names[2]['c_name'], $times),
                ];
//            }
        }

        $data['transaction']['city_name'] = $city_names;
        $data['transaction']['conducteur'] = $orders;

        //全国成交总额走势
        $city_namet = Db::name('order')->alias('o')
            ->distinct(true)
            ->field('c.name as c_name,count(o.id) as o_count')
            ->join('mx_cn_city c', 'c.id = o.city_id', 'inner')
            ->group('o.city_id')
            ->order('o_count desc')
            ->where($where3)
            ->limit(3)
            ->select();

        for ($y = 0; $y <= $Interval_d; $y++) {     //行

            $op_o = date('Y-m-d', $start);

            $day_start_o = strtotime($op_o . ' 00:00:00');  //当天开始时间
            $day_end_o = strtotime($op_o . ' 23:59:59');    //当天结束时间
            $ini = date("Y-m-d",strtotime("+".$y." day",strtotime($op_o)));
            $times = date('m',strtotime($ini)). '-' .date('d',strtotime($ini)) ;
            $days =  $this->days(date('m', $start) );
//            if((((int)date('d', $start)) + $y) <= $days) {
                $orderd[] = [
                    'times' => date('m',strtotime($ini)). '/' .date('d',strtotime($ini)),
                    $city_namet[0]['c_name'] => $this->OrderMoney($city_namet[0]['c_name'], $times),
                    $city_namet[1]['c_name'] => $this->OrderMoney( $city_namet[1]['c_name'], $times),
                    $city_namet[2]['c_name'] => $this->OrderMoney( $city_namet[2]['c_name'], $times),
                ];
//            }
        }

        $data['order_total']['city_name'] = $city_namet;
        $data['order_total']['conducteur'] = $orderd;

        $data['filiale_conducteur'] = $filiale_conducteur;

        return ['code' => APICODE_SUCCESS, 'data' => $data];
    }

    //返回时长
    private function Tokinaga($where4,$day,$city){
        $days = date('Y',time())."_".$day;

        $city_id = Db::name('cn_city')->where(['name'=>$city])->value('id');

        $toki = Db::name('conducteur')->alias('c')
            ->join('mx_conducteur_tokinaga t','t.conducteur_id = c.id','left')
            ->where($where4)
            ->where(['day'=>$days])
            ->where(['c.city_id'=>$city_id])
            ->sum('t.one_hour+two_hour+three_hour+four_hour+five_hour+six_hour+seven_hour+eight_hour+nine_hour+ten_hour+eleven_hour+twelve_hour+thirteen_hour+fourteen_hour+fifteen_hour+sixteen_hour+seventeen_hour+eighteen_hour+nineteen_hour+twenty_hour+twentyone_hour+twentytwo_hour+twentythree_hour+twentyfour_hour');

        if(empty($toki)){
            $toki = 0 ;
        }

        return $toki;
    }

    //返回订单数
    private function OrderCount($city_name,$times){
        $city_id = Db::name('cn_city')->where(['name'=>$city_name])->value('id') ;

        $start_time = strtotime(date('Y',time())."-".$times." 00:00:00");
        $end_time = strtotime(date('Y',time())."-".$times." 23:59:59") ;

        $order_count = Db::name('order')
                       ->where('create_time','gt',$start_time)
                       ->where('create_time','lt',$end_time)
                       ->where(['city_id'=>$city_id])->count();

        return $order_count ;
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

    //返回订单总额
    private function OrderMoney($city_name,$times){
        $city_id = Db::name('cn_city')->where(['name'=>$city_name])->value('id') ;
        $start_time = strtotime(date('Y',time())."-".$times." 00:00:00");
        $end_time = strtotime(date('Y',time())."-".$times." 23:59:59") ;

        $order_money = Db::name('order')
            ->where('create_time','gt',$start_time)
            ->where('create_time','lt',$end_time)
            ->where(['city_id'=>$city_id])->sum('money');

        if(empty($order_money)){
            $order_money = 0;
        }

        return $order_money ;
    }

    //审核认证司机
    public function AuditAuthenticationChauffeur()
    {

        $params = [
            "id" => input('?id') ? input('id') : null,
            "is_attestation" => input('?is_attestation') ? input('is_attestation') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id", "is_attestation"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $content = (input('is_attestation') == 2) ? '认证' : '拒绝';
        $params['attestation_pass_times'] = date('Y-m-d H:i:s', time());
        $conducteur = Db::name('conducteur')->update($params);

        if ($conducteur) {
            return [
                'code' => APICODE_SUCCESS,
                'msg' => $content . '成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => $content . '失败'
            ];
        }
    }

    //创建terimnal
    public function terimnal($sid, $name, $desc, $key)
    {
        $url = "https://tsapi.amap.com/v1/track/terminal/add";
        $postData = array(
            "sid" => $sid,
            "name" => $name,
            "desc" => $desc,
            "key" => $key,
            'props' => ''
        );
        $postData = http_build_query($postData);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.15');
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
    public function trace($sid, $tid, $key)
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
        curl_setopt($curl, CURLOPT_USERAGENT, 'Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.15');
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

    public function sq()
    {
        $urls = "http://php.51jjcx.com/backstage/Index/index";
        $url = $this->getQrcode($urls);

        return ['code' => APICODE_SUCCESS, 'data' => $url];
    }

    public function getQrcode($url)
    {
        $filename = date('Ymdhis') . rand(10000, 99999);
        $errorCorrectionLevel = 'H';
        $matrixPointSize = '6';
        $date = date('Ymd');
        //文件存放的路径
        $pathname = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'image' . DS . 'qrcode' . DS . $date;
        // 若目录不存在则创建之
        if (!is_dir($pathname)) {
            mkdir($pathname, 0777, true);
        }
        //生成图片
        $file = $pathname . DS . $filename . ".png";
        //存数据库路径
        $sql_path = DS . 'uploads' . DS . 'image' . DS . 'qrcode' . DS . $date . DS . $filename . ".png";

//        Loader::import('phpqrcode.phpqrcode', EXTEND_PATH, ".php");
        include_once "extend/phpqrcode/phpqrcode.php";

        $Qrcode = new \QRcode();

        $Qrcode::png(iconv('UTF-8', 'GBK//IGNORE', $url), $file, $errorCorrectionLevel, $matrixPointSize, 2);
        //-----------------curl 方式提交----------------------------//
        $post_url = "http://php.51jjcx.com/backstage/Driver/upload_to_oss";
        $curl = curl_init();
        $data = array('file' => new \CURLFile(realpath($sql_path)));
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($curl, CURLOPT_URL, $post_url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, "TEST");
        curl_exec($curl);
        curl_error($curl);
        //---------------------------------------------//

        //如果二维码图片不存在,则抛出异常
        if (!file_exists($file))
            throw new Exception('二维码生成失败!');
        return $sql_path;
    }

    //异步处理 上传oss
    public function upload_to_oss()
    {
        $file = $this->request->file();

        $alioss = new Oss();

        $arr = $alioss->uploadFile($file['file']);
        dump($arr);
        die;
        //将二维码地址保存在数据库中
    }

    //web页面
    public function index()
    {
        return view('index');
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

    //根据时间查询司机里程
    public function ConducteurMileage(){
        $params = [
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
            "start_time" => input('?start_time') ? input('start_time') : null,
            "end_time" => input('?end_time') ? input('end_time') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["conducteur_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $order = Db::name('order')->where('create_time','gt',strtotime(input('start_time')." 00:00:00"))
                                 ->where('create_time','lt',strtotime(input('end_time')." 23:59:59"))
                                 ->where(['conducteur_id'=>input('conducteur_id')])
                                 ->select();

        $Mileage = 0 ;
        foreach ($order as $k=>$v){
            $total_price = json_decode($v['total_price'], true);
            $Mileage += $total_price['Mileage'];
        }

        return [
            "code" => APICODE_SUCCESS,
            "msg" => "成功",
            "Mileage" => sprintf("%.2f", ($Mileage)),
            "conducteur_id"=>input('conducteur_id')
        ];
    }

    //司机产值
    public function DriverOutput(){
        $params = [
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id') : null,
            "start_time" => input('?start_time') ? input('start_time') : null,
            "end_time" => input('?end_time') ? input('end_time') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["conducteur_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $end_time = strtotime(date('Y-m-d',input('end_time')/1000)." 23:59:59")  ;

        $order = Db::name('order')
                                 ->field('sum(chauffeur_income_money) as moneys,sum(surcharge) as surcharges')
                                 ->where('pay_time','egt',input('start_time')/1000)
                                 ->where('pay_time','elt',$end_time)
                                 ->where(['conducteur_id'=>input('conducteur_id')])
                                 ->where('status','in','6,9')->find();

        $conducteur = Db::name('conducteur')->where(['id' => input('conducteur_id') ])->find() ;
        $data = [
            'DriverName'=>$conducteur['DriverName'],
            'DriverPhone'=>$conducteur['DriverPhone'],
            'money'=>$order['moneys'],
            'surcharge'=>$order['surcharges'],
        ];
        return [
            "code" => APICODE_SUCCESS,
            "msg" => "查询成功",
            "data" => $data,
        ];
    }

    //封禁司机预约单
    public function ForbiddenAppointment(){
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id'),
                "is_appointment" => input('is_appointment'),
            ];

            $res = Db::name('conducteur')->update($params);

            return [
                "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "更新成功",
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }
    }


}