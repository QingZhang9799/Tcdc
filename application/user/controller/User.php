<?php


namespace app\user\controller;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Request;

class User extends Base
{
    //行程记录
    public function user_journey(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];

            $status = input('?status') ? input('status')  : null ;

            $where = [];

            if(!empty($status)){
                $where['status'] = ['in',$status];
            }

//            $pageSize = input('?pageSize') ? input('pageSize') : 100;
//            $pageNum = input('?pageNum') ? input('pageNum') : 0;

            $data = db('order')->field('id,order_name,status,create_time,origin,Destination,classification,money,classification,key,service,terimnal,trace,tracks')
                                       ->where($params)->where($where)->where('is_privacy','eq',0)
                                       ->where('is_type','eq',0)
                                       ->order('id desc')
//                                       ->page($pageNum, $pageSize)
                                       ->select();

            foreach ($data as $key=>$value){
                if($value['tracks'] == null || $value['tracks'] == 'null'){
                    $data[$key]['tracks'] = "" ;
                }
            }
            if($data){
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "查询成功",
                    "data" => $data
                ];
            }else{
                return [
                    "code" => APICODE_SUCCESS,
//                    "msg" => "查询失败",
                    "data" => []
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }
    //行程记录
    public function user_new_journey(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];

            $status = input('?status') ? input('status')  : null ;

            $where = [];

            if(!empty($status)){
                $where['status'] = ['in',$status];
            }

            $pageSize = input('?pageSize') ? input('pageSize') : 100;
            $pageNum = input('?pageNum') ? input('pageNum') : 0;

            $data = db('order')->field('id,order_name,status,create_time,origin,Destination,classification,money,classification,key,service,terimnal,trace,tracks')
                ->where($params)->where($where)->where('is_privacy','eq',0)
                ->where('is_type','eq',0)
                ->order('id desc')
                                       ->page($pageNum, $pageSize)
                ->select();

            $count = db('order')->field('id,order_name,status,create_time,origin,Destination,classification,money,classification,key,service,terimnal,trace,tracks')
                ->where($params)->where($where)->where('is_privacy','eq',0)
                ->where('is_type','eq',0)
                ->order('id desc')
                ->count();
            foreach ($data as $key=>$value){
                if($value['tracks'] == null || $value['tracks'] == 'null'){
                    $data[$key]['tracks'] = "" ;
                }
            }
            if($data){
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "查询成功",
                    "sum" => $count,
                    "data" => $data
                ];
            }else{
                return [
                    "code" => APICODE_SUCCESS,
//                    "msg" => "查询失败",
                    "data" => [],
                    "sum" => $count,
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //优惠券
    public function user_coupon(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            $user_coupon = db('user_coupon')->where('is_use','eq',0)->where($params)->select();
            foreach ($user_coupon as $key => $value) {
                $flag = $this->activityTimeVerify($value['times']);
                if($flag == 1){
                    $coupon[]=[
                        'id'=>$value['id'],
                        'coupon_name'=>$value['coupon_name'],
                        'times'=>$value['times'],
                        'user_id'=>$value['user_id'],
                        'order_type'=>$value['order_type'],
                        'city_id'=>$value['city_id'],
                        'discount'=>$value['discount'],
                        'min_money'=>$value['min_money'],
                        'man_money'=>$value['man_money'],
                        'minus_money'=>$value['minus_money'],
                        'pay_money'=>$value['pay_money'],
                        'type'=>$value['type'],
                        'is_use'=>$value['is_use']
                    ];
                }
            }
            $count = count($coupon) ;//db('user_coupon')->where('is_use','eq',0)->where($params)->count();
            return [
                "code" => $coupon ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "count" => $count,
                "data" => $coupon
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //优惠券
    public function user_coupon_new(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            $user_coupon = db('user_coupon')->where('is_use','eq',1)->where($params)->select();
            foreach ($user_coupon as $key => $value) {
                $flag = $this->activityTimeVerify($value['times']);
                if($flag == 1){
                    $coupon[]=[
                        'id'=>$value['id'],
                        'coupon_name'=>$value['coupon_name'],
                        'times'=>$value['times'],
                        'user_id'=>$value['user_id'],
                        'order_type'=>$value['order_type'],
                        'city_id'=>$value['city_id'],
                        'discount'=>$value['discount'],
                        'min_money'=>$value['min_money'],
                        'man_money'=>$value['man_money'],
                        'minus_money'=>$value['minus_money'],
                        'pay_money'=>$value['pay_money'],
                        'type'=>$value['type'],
                        'is_use'=>$value['is_use']
                    ];
                }
            }
            $count = count($coupon) ;//db('user_coupon')->where('is_use','eq',0)->where($params)->count();
            return [
                "code" => $coupon ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "count" => $count,
                "data" => $coupon
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //活动时间验证
    private function activityTimeVerify($times){
        $flag = 0 ;
        $time = time() ;
        $activity = json_decode($times,true) ;
        if(!empty($activity['startTime']) && !empty($activity['endTime'])){      //俩个都有值
            if($time > $activity['startTime'] && $time < $activity['endTime']){
                $flag = 1 ;
            }
        }
        if(!empty($activity['startTime']) && empty($activity['endTime'])){       //起始有值,终点为空
            if($time > $activity['startTime']){
                $flag = 1 ;
            }
        }
        if(empty($activity['startTime']) && !empty($activity['endTime'])){       //起点为空,终点有值
            if($time < $activity['endTime']){
                $flag = 1 ;
            }
        }
        return $flag ;
    }

    //积分明细
    public function integral_list(){

        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            $data = db('user_integral')->where($params)->select();

            $integral = Db::name('user')->where(['id'=>input('user_id')])->value('integral');

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data,
                "integral" => $integral,
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }

    }

    //我的钱包
    public function UserWallet(){
        if (input('?user_id')) {
            $params = [
                "id" => input('user_id')
            ];
            $data = db('user')->field('packet_money,balance,integral')->where($params)->find();

            //优惠券数量
            $user_coupon = Db::name('user_coupon')->where('is_use','eq',0)->where(['user_id'=>input('user_id')])->select();
            foreach ($user_coupon as $key => $value) {
                $flag = $this->activityTimeVerify($value['times']);
                if($flag == 1){
                    $coupon[]=[
                        'id'=>$value['id'],
                        'coupon_name'=>$value['coupon_name'],
                        'times'=>$value['times'],
                        'user_id'=>$value['user_id'],
                        'order_type'=>$value['order_type'],
                        'city_id'=>$value['city_id'],
                        'discount'=>$value['discount'],
                        'min_money'=>$value['min_money'],
                        'man_money'=>$value['man_money'],
                        'minus_money'=>$value['minus_money'],
                        'pay_money'=>$value['pay_money'],
                        'type'=>$value['type'],
                        'is_use'=>$value['is_use']
                    ];
                }
            }

            $data['user_coupon'] =  count($coupon) ;
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

    //设置-个人资料
    public function SetUserPersonal(){
        if (input('?user_id')) {
            $params = [
                "id" => input('user_id')
            ];
            $data = db('user')->field('id,portrait,PassengerPhone,PassengerName,number,nickname,home,units,Collecting,PassengerGender,enterprise_id')->where($params)->find();
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

    //设置中心-关于我们
    public function AboutUs(){
        $data['gfwz'] = 'www.tongchengdache.cn' ;
        $data['wxgzh'] = '同城打车' ;
        $data['kfdh'] = '400-607-7775' ;
        $data['version'] = 'v2.0' ;

        return ['code'=>APICODE_SUCCESS,'data'=>$data];
    }

    //意见反馈
    public function Feedback(){

        $params = [
            "content" => input('?content') ? input('content') : null,
            "user_id" => input('?user_id') ? input('user_id') : null,
            "img" => input('?img') ? input('img') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["content", "user_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $params['cause'] = $params['content'];
        unset($params['content']);
        $params['create_time'] = time();
        //查询城市
        $user = Db::name('user')->where(['id'=>input('user_id')])->find() ;
        $params['city_id'] = $user['city_id'] ;
        $params['user_name'] =$user['nickname'] ;
        $params['user_phone'] =$user['PassengerPhone'] ;

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

    //联系客服
    public function ContactCustomer(){
//        $data['kfdh'] = '400-607-7775' ;
//        $data['address'] = '黑龙江省哈尔滨市南岗区金爵万象三期三号楼' ;
//        $data['longitude'] = '123.12' ;
//        $data['latitude'] = '45.60' ;
//        return ['code'=>APICODE_SUCCESS,'data'=>$data];

        if (input('?city_id')) {
            $params = [
                "city_id" => input('city_id')
            ];

            $data = db('company')->field('kfdh_phone,ContactAddress,longitude,latitude')->where($params)->find();

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

    //我的钱包-立即充值-充值卡
    public function refillCard(){

        $params = [
            "phone" => input('?phone') ? input('phone') : null,
            "cardNumber" => input('?cardNumber') ? input('cardNumber') : null,
            "user_id" => input('?user_id') ? input('user_id') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["user_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        //验证卡号
        $money = Db::name('labour_rechargeable')->where(['cdkey'=>input('cardNumber')])->value('money');

        if(!empty($money)){
            //增加用户余额
            Db::name('user')->where(['id'=>input('user_id')])->setInc('balance',$money);
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'充值成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'充值卡号不正确,请重新填写'
            ];
        }
    }

    //车主报名
    public function OwnersApply(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "DriverName" => input('DriverName') ? input('DriverName') : null,
//            "number" => input('?number') ? input('number')  : null,
//            "carte_endroit_img" => input('?carte_endroit_img') ? input('carte_endroit_img')  : null,
//            "carte_inverse_img" => input('?carte_inverse_img') ? input('carte_inverse_img') : null,
//            "patente_home" => input('?patente_home') ? input('patente_home') : null,
//            "patente_prsident" => input('?patente_prsident') ? input('patente_prsident') : null,
//            "Driving_front" => input('?Driving_front') ? input('Driving_front') : null,
//            "Driving_side" => input('?Driving_side') ? input('Driving_side') : null,
//            "insurance_front" => input('?insurance_front') ? input('insurance_front') : null,
//            "withcars_front" => input('?withcars_front') ? input('withcars_front') : null,
            "car_type" => input('?car_type') ? input('car_type') : null,
            "phone" => input('?phone') ? input('phone') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["DriverName"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

//        $conducteur = db('conducteur')->insert($params);
        $params['create_time'] = time();
          $carowner_apply = Db::name('carowner_apply')->insert($params);

        if($carowner_apply){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'报名成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'报名失败'
            ];
        }
    }
    //设置家
    public function setHome(){
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : 0,
            "home" => input('?home') ? input('home') : '',
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "home"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $ini['id'] = input('user_id');
        $ini['home'] = input('home');

        $res = Db::name('user')->update($ini);

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "设置成功",
        ];
    }

    //设置单位
    public function setWorkUnit(){
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "units" => input('?units') ? input('units') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id","units"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $ini['id'] = input('user_id') ;
        $ini['units'] = input('units') ;

        $res = Db::name('user')->update($ini);

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "设置成功",
        ];
    }

    //设置收藏
    public function setCollecting(){
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "collect" => input('?collect') ? input('collect') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id","collect"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //取出原先收藏的地址
        $Collecting = Db::name('user')->where(['id'=>input('user_id')])->value('Collecting');

        $Collectings = $Collecting.",".input('collect');

        $ini['id'] = input('user_id') ;
        $ini['collect'] = $Collectings ;

        $res = Db::name('user')->update($ini);

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }

    //修改用户昵称
    public function updateUser(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "nickname" => input('?nickname') ? input('nickname') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["id","nickname"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $res = Db::name('user')->update($params);
        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];

    }

    //路线申报
    public function Routedeclare(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "origin" => input('?origin') ? input('origin') : null,
            "destination" => input('?destination') ? input('destination') : null,
            "cause" => input('?cause') ? input('cause') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id","origin","destination","cause"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $params['create_time'] = time();
        $route_declaration = Db::name('route_declaration')->insert($params);

        if($route_declaration){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'申报成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'申报失败'
            ];
        }
    }

    //隐藏按钮
    public function ConcealButtons(){
        return [
            'code'=> APICODE_SUCCESS,
            'flag'=> 0
        ];
    }

    //版本更新
    public function VersionsUpdate(){
        $versions_record = Db::name('versions_record')->field('versionCode,versionName,file_size,link')->where('type','eq',0)->find();

        return [
            'code'=>APICODE_SUCCESS,
            'msg'=>'成功',
            'data'=>$versions_record,
        ];
    }
    
    //推荐起点
    public function RecommendOrigin(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id'),
                "origin" => input('origin'),
                "address" => input('address'),
                "location" => input('location'),
            ];
             $user_history = Db::name('user_history')->where(['user_id' => input('user_id'),'origin' =>input('origin') ])->find();

            if(!empty($user_history)){
                return [
                    "code" => APICODE_SUCCESS,
//                    "msg" => "起点已存在",
                ];
            }else{
                $res =  Db::name('user_history')->insert($params);
                if($res){
                    $where=[
                        'origin'=>['exp','is not null']
                    ];
                    //当数据大于5条,删除最近一条
                    $user_history_count = Db::name('user_history')->where(['user_id' => input('user_id')])->where($where)->count();
                    if($user_history_count >= 5){
                        $user_history_id = Db::name('user_history')->where(['user_id' => input('user_id')])->where($where)->value('id');
                        Db::name('user_history')->where(['id'=>$user_history_id])->delete() ;
                    }
                    return [
                        "code" => APICODE_SUCCESS,
//                        "msg" => "保存成功",
                    ];
                }else{
                    return [
                        "code" => APICODE_SUCCESS,
//                        "msg" => "保存失败",
                    ];
                }
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //推荐终点
    public function RecommendDestination(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id'),
                "destination" => input('destination'),
                "address" => input('address'),
                "location" => input('location'),
            ];
            $user_history = Db::name('user_history')->where(['user_id' => input('user_id'),'destination' =>input('destination') ])->find();
            if(!empty($user_history)){
                return [
                    "code" => APICODE_SUCCESS,
//                    "msg" => "终点已存在",
                ];
            }else{
                $res =  Db::name('user_history')->insert($params);
                if($res){
                    $where=[
                        'destination'=>['exp','is not null']
                    ];
                    //当数据大于5条,删除最近一条
                    $user_history_count = Db::name('user_history')->where(['user_id' => input('user_id')])->where($where)->count();
                    if($user_history_count >= 5){
                        $user_history_id = Db::name('user_history')->where(['user_id' => input('user_id')])->where($where)->value('id');
                        Db::name('user_history')->where(['id'=>$user_history_id])->delete() ;
                    }
                    return [
                        "code" => APICODE_SUCCESS,
//                        "msg" => "保存成功",
                    ];
                }else{
                    return [
                        "code" => APICODE_SUCCESS,
//                        "msg" => "保存失败",
                    ];
                }
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //获取推荐起点
    public function getRecommendOrigin(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            $where=[
                'origin'=>['exp','is not null']
            ];
            $data = db('user_history')->group('origin')->order('id desc')->where($params)->where($where)->limit(5)
                ->select();
            return [
                "code" => APICODE_SUCCESS,
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

    //获取推荐终点
    public function getRecommendDestination(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            $where=[
                'destination'=>['exp','is not null']
            ];
            $data = db('user_history')->group('destination')->order('id desc')->where($params)->where($where)->limit(5)->select();
            return [
                "code" => APICODE_SUCCESS,
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

    //路径
    public function RouteProgramme(){
        $params = [
            "DepLongitude" => input('?DepLongitude') ? input('DepLongitude') : null,
            "DepLatitude" => input('?DepLatitude') ? input('DepLatitude') : null,
            "DestLongitude" => input('?DestLongitude') ? input('DestLongitude')  : null,
            "DestLatitude" => input('?DestLatitude') ? input('DestLatitude')  : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["DepLongitude", "DepLatitude", "DestLongitude", "DestLatitude"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
    }

    //扫码上车验证
    public function EwmVerification(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "business_id" => input('?business_id') ? input('business_id') : null,
            "conducteur_id" => input('?conducteur_id') ? input('conducteur_id')  : null,
            "businesstype_id" => input('?businesstype_id') ? input('businesstype_id')  : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "business_id", "conducteur_id", "businesstype_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $flag = 0 ;

        //获取车辆的业务
        $city_id = Db::name('conducteur')->where(['id'=>input('conducteur_id')])->value('city_id');
        $vehicle_id = Db::name('vehicle_binding')->where(['conducteur_id'=>input('conducteur_id')])->value('vehicle_id');
        $vehicle = Db::name('vehicle')->where(['id' => $vehicle_id])->find() ;

        if($city_id == input('city_id')){
            if($vehicle['business_id'] == input('business_id')) {
                if($vehicle['businesstype_id'] == input('businesstype_id')){
                    $flag = 1;
                    return [
                        'code'=>APICODE_SUCCESS,
                        'msg'=>'成功',
                        'flag'=>$flag,
                    ];
                }else{
                    return [
                        'code'=>APICODE_ERROR,
                        'msg'=>'车型不一致',
                        'flag'=>$flag,
                    ];
                }
            }else{
                return [
                    'code'=>APICODE_ERROR,
                    'msg'=>'业务不一致',
                    'flag'=>$flag,
                ];
            }
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'城市不一致',
                'flag'=>$flag,
            ];
        }
    }

    //司机距离
//    public function conducteurDistance(){
//        if (input('?conducteur_id')) {
//            $params = [
//                "conducteur_id" => input('conducteur_id')
//            ];
//
//            $vehicle_id = Db::name('vehicle_binding')->where($params)->value('vehicle_id') ;
//            $Gps_number = Db::name('vehicle')->where(['id' => $vehicle_id ])->value('Gps_number') ;
//
//
//
//            return [
//                "code" => APICODE_SUCCESS,
//                "msg" => "查询成功",
//            ];
//        } else {
//            return [
//                "code" => APICODE_FORAMTERROR,
//                "msg" => "司机ID不能为空"
//            ];
//        }
//    }
    //隐私用户订单
    public function PrivacyOrder(){
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "orders" => input('?orders') ? input('orders') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "orders"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $order = explode(',',input('orders')) ;

        foreach ($order as $key=>$value){
            $ini['id'] = $value;
            $ini['is_privacy'] = 1;

            Db::name('order')->update($ini) ;
        }

        return [
            'code'=>APICODE_SUCCESS,
            'msg'=>'隐私成功'
        ];
    }

    //同步取消原因
    public function CancelCause(){
        $params = [
            "order_id" => input('?order_id') ? input('order_id') : null,
            "cause" => input('?cause') ? input('cause') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["order_id", "cause"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $ini['id'] = input('order_id') ;
        $ini['reason'] = input('reason') ;

        $res = Db::name('order')->update($ini) ;

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "取消成功",
        ];
    }

    //判断是否认证和设置紧急联系人
    public function JudgmentWhetherUrgency(){
        if (input('?user_id')) {
            $params = [
                "id" => input('user_id')
            ];
            //认证状态
            $is_attestation = Db::name('user')->where($params)->value('is_attestation');
            //联系人
            $flag = 0 ;
            $user_contact = Db::name('user_contact')->where(['user_id'=>input('user_id')])->count();
            if($user_contact > 0 ){
                $flag = 1 ;
            }

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "is_attestation" => $is_attestation,
                "flag" => $flag
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //小程序版本号更新
    public function AppletUpdate(){
       $versionCode = Db::name('versions_record')->where(['id'=>4])->value('versionCode') ;
        //版本
        return ['code'=>200,'msg'=>'成功','version'=>$versionCode];
    }














}