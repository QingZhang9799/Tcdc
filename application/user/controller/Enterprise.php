<?php


namespace app\user\controller;
use app\api\model\Conducteur;
use app\api\model\Company;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Config;

class Enterprise extends Base
{
    //我的企业
    public function MyBusiness(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];

            $enterprise_id = Db::name('user')->where(['id'=>input('user_id')])->value('enterprise_id');

            $data = db('enterprise')->where(['id'=>$enterprise_id])->find();

            $is_admin = 0 ;
            $money = 0 ;
            $is_wx = 0;  //是否为无限模式

            if($data['user_id'] == input('user_id')){  //是管理员
                $is_admin = 1;
                //获取规则里的额度
                $enterprise_rule =  Db::name('enterprise_rule')->where(['enterprise_id' => $enterprise_id ])->find();
                if( $enterprise_rule['type'] == 1 ){
                    $is_wx = 1 ;
                }else{
                    $money = $enterprise_rule['money'] ;
                }
            }else{               //非管理员
                $user_rule = Db::name('user_rule')->where(['user_id' =>input('user_id') ])->find() ;
                if( $user_rule['type'] == 1 ){
                    $is_wx = 1 ;
                }else{
                    $money = $user_rule['money'] ;
                }
                //非管理员已经消费
                $moneys =  Db::name('enterprise_consumerdetails')->where(['enterprise_id'=>$data['id'],'user_id'=>input('user_id')])->value('sum(money)') ;
                if(empty($moneys)){
                    $moneys = 0 ;
                }
                $data['gross_amount'] = $moneys ;
            }

            //消费明细
            if($data['user_id'] == input('user_id')){  //是管理员
                $data['consumerdetails'] =  Db::name('enterprise_consumerdetails')->where(['enterprise_id'=>$data['id']])->select();
            }else{      //非管理员
                $data['consumerdetails'] =  Db::name('enterprise_consumerdetails')->where(['enterprise_id'=>$data['id'],'user_id'=>input('user_id')])->select();
            }

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data,
                "money" => $money,
                "is_wx" => $is_wx,
                "is_admin" => $is_admin,
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //企业管理
    public function CorporateGovernance(){
        if (input('?enterprise_id')) {
            $params = [
                "enterprise_id" => input('enterprise_id')
            ];
            $pageSize = input('?pageSize') ? input('pageSize') : 10;
            $pageNum = input('?pageNum') ? input('pageNum') : 0;

            $user_id = Db::name('enterprise')->where(['id' =>input('enterprise_id') ])->where(['state'=>1])->value('user_id') ;
            $data = [] ;
            $money = 0 ;
            $is_wx = 0 ;
            if(!empty($user_id)){
                $data = db('user')->field('id,PassengerPhone,enterprise_nickname as nickname,portrait,enterprise_money')
                    ->where($params)
                    ->order('join_time')
                    ->page($pageNum, $pageSize)
                    ->select() ;
                $datas = db('user')->field('id,PassengerPhone,enterprise_nickname as nickname,portrait,enterprise_money')
                    ->where($params)
                    ->order('join_time')
                    ->count() ;
                foreach ($data as $key=>$value){
                    $user_rule = Db::name('user_rule')->where(['user_id' =>$value['id'] ])->find() ;
                    $moneys = 0 ;
                    $is_wxs = 0;    //是否为无限模式
                    $is_admin = 0 ; //是否为管理员
                    //获取规则里的额度

                    if($user_rule['type'] == 1){
                        $is_wxs = 1 ;
                    }else{
                        $moneys = $user_rule['money'] ;
                    }
                    if( $user_id == $value['id']){
                        $is_admin = 1 ;
                    }
                    $data[$key]['is_wxs']  = $is_wxs ;
                    $data[$key]['moneys']  = $moneys ;
                    $data[$key]['is_switch']  = $user_rule['is_switch'] ;
                    $data[$key]['is_admin']  = $is_admin ;
                }
                //获取企业限额
                $enterprise_rule = Db::name('enterprise_rule')->where(['enterprise_id'=>input('enterprise_id')])->find();
                $money = 0 ;
                $is_wx = 0;  //是否为无限模式
                //获取规则里的额度
                $enterprise_rule =  Db::name('enterprise_rule')->where(['enterprise_id' => input('enterprise_id')])->find();

                if($enterprise_rule['type'] == 1){
                    $is_wx = 1 ;
                }else{
                    $money = $enterprise_rule['money'] ;
                }
            }
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "sum"=>$datas,
                "data" => $data,
                "money" => $money,
                "is_wx" => $is_wx,
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "企业ID不能为空"
            ];
        }
    }

    //用户评价司机（订单）
    public function UserEvaluate(){
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "order_id" => input('?order_id') ? input('order_id') : null,
            "star" => input('?star') ? input('star') : null,
            "Detail" => input('?Detail') ? input('Detail') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["user_id","order_id","star"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $order = Db::name('order')->where([ 'id' => input('order_id') ])->find();
        $conducteur = Db::name('conducteur')->where(['id'=>$order['conducteur_id']])->find();

        //评价完，订单变成已完成
        $ini_o['id'] = $order['id'] ;
        $ini_o['status'] = 9 ;
        Db::name('order')->update($ini_o);

        $star = input('star');

        $score = 0 ;
        $con_score = 0 ;
        if($star == 5){
            $score = 0.1 ;
        }else if($star == 4){
            $score = 0.05;
        }else if($star == 3){
            $score = 0;
        }else if($star == 2){
            $score = -0.05;
        }else if($star == 1){
            $score = -0.1;
        }

        if((int)$conducteur['score'] >= 100){
             if(input('star') < 3){
                 $con_score = $conducteur['score'] + $score;
             }else{
                 $con_score = $conducteur['score'] ;
             }
        }else{
            $con_score = $conducteur['score'] + $score;
        }

        $ini['id'] = $conducteur['id'] ;
        $ini['score'] = $con_score ;
        Db::name('conducteur')->update($ini);

        //评价司机
        $inii['user_id'] = input('user_id') ;
        $inii['company_id'] = $order['company_id'] ;
        $inii['CompanyId'] = "" ;
        $inii['OrderId'] = $order['OrderId'] ;
        $inii['EvaluateTime'] = time() ;
        $inii['ServiceScore'] = 0 ;
        $inii['DriverScore'] = 0 ;
        $inii['VehicleScore'] = 0 ;
        $inii['Detail'] = input('Detail');
        $inii['create_time'] = time();
        $inii['star'] = input('star');

        Db::name('user_evaluate')->insert($inii);
        return [
            "code" => APICODE_SUCCESS,
            "msg" => "评价成功",
        ];
    }

    //账单
    public function ComputingBilling(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            //余额
            $user_balance = db('user_balance')->order('id desc')->where($params)->select();

            //账单
            $order = Db::name('order')->field('user_phone,user_name,actual_amount_money,create_time')
                ->where(['user_id'=>input('user_id')])->where('status','in','6,9')->order('id desc')->select();

            $data = [] ;

            foreach ($user_balance as $key=>$value){
                $data[] = [
                    'money'=>sprintf("%.2f", ($value['money']) ) ,
                    'type'=>$value['type'],
                    'user_name'=>$value['user_name'],
                    'phone'=>$value['phone'],
                    'create_time'=>$value['create_time'],
                    'symbol'=>$value['symbol']
                ];
            }

            foreach ($order as $k=>$v){
                $data[] = [
                    'money'=>sprintf("%.2f", ($v['actual_amount_money']) ),
                    'type'=>3,
                    'user_name'=>$v['user_name'],
                    'phone'=>$v['user_phone'],
                    'create_time'=>$v['create_time'],
                    'symbol'=>2
                ];
            }

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

    //添加乘车人
    public function AddPassenger(){
        $params = [
            "phone" => input('?phone') ? input('phone') : null,
            "name" => input('?name') ? input('name') : null,
            "enterprise_id" => input('?enterprise_id') ? input('enterprise_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["phone","name","enterprise_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $user =  Db::name('user')->where(['PassengerPhone' => input('phone') ])->find();
        //判断一下，用户是否存在企业
        if($user['enterprise_id'] > 0){
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'已经存在企业'
            ];
        }

        if(!empty($user)){
            $ini['id'] = $user['id'] ;
            $ini['enterprise_id'] = input('enterprise_id') ;
            $ini['join_time'] = time() ;
            $ini['enterprise_nickname'] = input('name') ;
            Db::name('user')->update($ini);
        }else{
         //自动注册
            $ini['nickname'] ="同城" . rand(0000, 9999) ;
            $ini['PassengerPhone'] = input('phone') ;
            $ini['create_time'] = time() ;
            $ini['enterprise_id'] = input('enterprise_id') ;
            $ini['join_time'] = time() ;
            $user_portrait = Db::name('user_allocation')->where(['id' => 1])->value('user_portrait');
            $ini['portrait'] = $user_portrait;
            $ini['enterprise_nickname'] = input('name') ;

            //获取企业的城市
            $city_id = Db::name('enterprise')->where(['id'=>input('enterprise_id')])->value('city_id') ;
            $ini['city_id'] = $city_id ;
            $user_id = Db::name('user')->insertGetId($ini);
            $user = Db::name('user')->where(['id' => $user_id ])->find() ;
        }
        //增加用户消费规则
        $enterprise_rule = Db::name('enterprise_rule')->where(['enterprise_id'=>input('enterprise_id')])->find();

        $iniii['type'] = $enterprise_rule['type'] ;
        $iniii['type_item'] = $enterprise_rule['type_item'] ;
        $iniii['money'] = $enterprise_rule['money'] ;
        $iniii['day'] = $enterprise_rule['day'] ;
        $iniii['start_time'] = $enterprise_rule['start_time'] ;
        $iniii['end_time'] = $enterprise_rule['end_time'] ;
        $iniii['create_time'] = time() ;
        $iniii['enterprise_id'] = input('enterprise_id') ;
        $iniii['user_id'] = $user['id'] ;
        Db::name('user_rule')->insert($iniii) ;

        return [
            'code'=>APICODE_SUCCESS,
            'msg'=>'添加成功'
        ];
    }

    //删除乘车人
    public function DELPassenger(){
        if (input('?user_id')) {
            $params = [
                "id" => input('user_id'),
                "enterprise_id" => 0,
            ];

            $data = Db::name('user')->update($params);
            //删除消费规则
            $user_rule_id = Db::name('user_rule')->where(['user_id'=>input('user_id')])->value('id') ;
            Db::name('user_rule')->where(['id'=>$user_rule_id])->delete() ;

            return [
                "code" => $data >0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "删除成功",
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //消费开关
    public function ConsumptionSwitch(){
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "is_switch" => input('?is_switch') ? input('is_switch') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "is_switch"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $user_rule_id = Db::name('user_rule')->where(['user_id' => input('user_id')])->value('id') ;

        $ini['id'] = $user_rule_id ;
        $ini['is_switch'] = input('is_switch') ;
        $data = Db::name('user_rule')->update($ini) ;

        $str = "" ;
        if(input('is_switch') == 0){
            $str = "打开" ;
        }else if(input('is_switch') == 1){
            $str = "关闭" ;
        }

        return [
            "code" => $data >0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => $str ."成功",
        ];
    }

    //修改每月额度
    public function UpdateMonthQuota(){
        $params = [
            "enterprise_id" => input('?enterprise_id') ? input('enterprise_id') : null,
            "money" => input('?money') ? input('money') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["enterprise_id", "money"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        //更改消费规则
        $enterprise_rule_id = Db::name('enterprise_rule')->where(['enterprise_id' =>input('enterprise_id') ])->value('id') ;

        $ini['id'] = $enterprise_rule_id ;
        $ini['enterprise_id'] = input('enterprise_id');
        $ini['money'] = input('money');

        $res = Db::name('enterprise_rule')->update($ini);

        if($res > 0){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'修改成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'修改失败'
            ];
        }
    }

    //修改人员额度
    public function UpdatePersonnelQuota(){
        $params = [
            "enterprise_id" => input('?enterprise_id') ? input('enterprise_id') : null,
            "user_id" => input('?user_id') ? input('user_id') : null,
            "quota" => input('?quota') ? input('quota') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["enterprise_id", "user_id","quota"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //获取规则
        $user_rule_id = Db::name('user_rule')->where(['user_id' =>input('user_id'),'enterprise_id'=>input('enterprise_id')  ])->value('id') ;

        $ini['id'] = $user_rule_id ;
        $ini['money'] = input('quota') ;
        $res = Db::name('user_rule')->update($ini) ;

        if($res > 0){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'修改成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'修改失败'
            ];
        }
    }

    //企业注册
    public function EstablishmentRegistration(){
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "enterprise_name" => input('?enterprise_name') ? input('enterprise_name') : null,
            "principal" => input('?principal') ? input('principal') : null,
            "principal_phone" => input('?principal_phone') ? input('principal_phone') : null,
            "vietinBanh_registration" => input('?vietinBanh_registration') ? input('vietinBanh_registration') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "city_id", "enterprise_name"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        //企业名称重复
        $enterprises = Db::name('enterprise')->where(['enterprise_name' => input('enterprise_name') ])->find();
        if(!empty($enterprises)){
            return [
                "code" => APICODE_ERROR,
                "msg" => "企业名称已重复"
            ];
        }


        $params['create_time'] = time() ;
        $params['state'] = 0 ;
        $enterprise = Db::name('enterprise')->insertGetId($params) ;

        //根据用户更新企业id
//        $inii['id'] = input('user_id') ;
//        $inii['enterprise_id'] = $enterprise ;
//        $inii['join_time'] = time() ;
//        Db::name('user')->update($inii);

        if($enterprise >0){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'注册成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'注册失败'
            ];
        }
    }

    //判断是否企业用户
    public function JudgmentWhether(){
        if (input('?user_id')) {
            $params = [
                "id" => input('user_id')
            ];
//            $enterprise_id = db('user')->where($params)->value('enterprise_id');

            //审核状态
            //如果是管理员
            $enterprise_id = Db::name('enterprise')->where(['user_id' =>input('user_id') ])
                ->where('state','in','0,1')->value('id') ;

            //不是管理员
            if(empty($enterprise_id)){
                $enterprise_id = db('user')->where($params)->value('enterprise_id');
            }
            $state = Db::name('enterprise')->where(['id' =>$enterprise_id])->value('state') ;
           if($state == null ){
               $state = -1 ;
           }
            $enterprise_name = Db::name('enterprise')->where(['id' =>$enterprise_id ])->value('enterprise_name') ;
            //管理员id
            $en_user_id = Db::name('enterprise')->where(['id' =>$enterprise_id ])->value('user_id') ;

            //判断一下,是否有公务车
            $business_id = Db::name('enterprise')->where(['id' =>$enterprise_id ])->where('business_id','in',11) ;
            $is_gwc = 0 ;   //是否有公务车
            if(!empty($business_id)){
                $is_gwc = 1 ;
            }

            $flag = 0 ;
            if( $state != 0 && $state != 1 && $state != 3){
                $state = -1 ;
            }

            if( $enterprise_id > 0 && ( $state == 1 || $state ==3)){
                $flag = 1 ;
            }

            $is_admin = 0 ;
            //判断是否为企业管理员
            if($en_user_id == input('user_id')){
                $is_admin = 1;
            }

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "flag" => $flag,
                "enterprise_id" => $enterprise_id,
                "state"=>$state,
                "is_admin"=>$is_admin,
                "enterprise_name"=>$enterprise_name,
                "is_gwc"=>$is_gwc,
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //个人费用明细
    public function UserExpensesDetails(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            //获取用户现在的企业id
            $enterprise_id = Db::name('user')->where(['id'=>input('user_id')])->value('enterprise_id');
            $params['enterprise_id'] = $enterprise_id ;
            $data = db('enterprise_consumerdetails')->where($params)->select();
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

    //获取司机位置
    public function AcquireDriverLocation(){
        if (input('?conducteur_id')) {
            $params = [
                "conducteur_id" => input('conducteur_id')
            ];

            $vehicle_id = Db::name('conducteur')->where(['id' =>input('conducteur_id') ])->value('vehicle_id');
            $GPS_number = Db::name('vehicle')->where(['id' =>$vehicle_id ])->value('GPS_number') ;

            $data = $this->request_post("http://www.gpsnow.net/car/getCarAndStatus.do", array("token" => $this->getAdminToken(),"carId"=>(int)$GPS_number , "mapType" => 2));
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

    public function getAdminToken()
    {
        $token = Cache::get('gps_token');
        if (!$token) {
            $data = $this->request_post("http://www.gpsnow.net/user/login.do", array("name" => "tcdc", "password" => "123456"));
            $data = json_decode($data, true);
            $token = $data["data"]["token"];
            Cache::set('gps_token', $token, 3600);
        }

        return $token;
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
}