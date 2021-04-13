<?php

namespace app\backstage\controller;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;



class User extends Base
{
    //用户列表
   public function user_list(){
       $params = [
           "u.city_id" => input('?city_id') ? ['eq',input('city_id')] : null,
           "u.PassengerGender" => input('?PassengerGender') ? ['eq',input('PassengerGender')] : null,
           "u.PassengerName" => input('?PassengerName') ? ['like','%'.input('PassengerName').'%'] : null,
           "u.PassengerPhone" => input('?PassengerPhone') ? ['like','%'.input('PassengerPhone').'%'] : null,
           "u.number" => input('?number') ? ['like','%'.input('number').'%'] : null,
           "u.nickname" => input('?nickname') ? ['like','%'.input('nickname').'%'] : null,
           "u.status" => input('?status') ? ['eq',input('status')] : null,
       ];
       if(input('PassengerPhone') == "null" ){
           unset($params['u.PassengerPhone']);
       }
       if(input('number') == "null" ){
           unset($params['u.number']);
       }
       $where = [] ;  $where1= [] ;
       if(input('create_time') == "null" || empty(input('create_time'))){
           unset($params['u.create_time']);
       }else{
           $times = explode( ',' ,input('create_time'))  ;
           $start_time = strtotime($times[0]." 00:00:00") ;
           $end_time = strtotime($times[1]." 23:59:59") ;

           $where['u.create_time'] = [ 'gt' , $start_time ];
           $where1['u.create_time'] = [ 'lt' , $end_time ];
       }
       if( input('PassengerName') == "null" || empty(input('PassengerName')) ){
           unset($params['u.PassengerName']);
       }
       if( input('PassengerGender') == "0" || input('PassengerGender') == 0 ){
           unset($params['u.PassengerGender']);
       }
       if($params['u.city_id'] == "null" ){
           unset($params['u.city_id']);
       }
       if($params['u.is_attestation'] == -1 || $params['u.is_attestation']== "null"){
           unset($params['u.is_attestation']);
       }
       if($params['u.logon_tip'] == 0){
           unset($params['u.logon_tip']);
       }
       if($params['u.status'] == -1 || $params['u.status'] =="null" ){
           unset($params['u.status']);
       }
       if(input('nickname') == "null" ){
           unset($params['u.nickname']);
       }
       $pageSize = input('?pageSize') ? input('pageSize') : 10;
       $pageNum = input('?pageNum') ? input('pageNum') : 0;

       $sum = db('user')->alias('u')
           ->field('u.*,c.name as c_name')
           ->join('mx_cn_city c','c.id = u.city_id','left')->where($where)->where($where1)->where(self::filterFilter($params))->count();

       return [
           "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
           "sum" => $sum,
           "data" => db('user')->alias('u')
               ->field('u.*,c.name as c_name')
               ->join('mx_cn_city c','c.id = u.city_id','left')->where($where)->where($where1)->where(self::filterFilter($params))->order('id desc')->page($pageNum, $pageSize)
               ->select()
       ];
   }

   //添加用户
    public function add_user(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : 0,
            "portrait" => input('?portrait') ? input('portrait') : '',
            "user_pwd" => input('?user_pwd') ? input('user_pwd')  : '',
            "PassengerName" => input('?PassengerName') ? input('PassengerName')  : '',
            "PassengerGender" => input('?PassengerGender') ? input('PassengerGender') : '',
            "PassengerPhone" => input('?PassengerPhone') ? input('PassengerPhone') : '',
            "number" => input('?number') ? input('number') : '',
            "is_attestation" => input('?is_attestation') ? input('is_attestation') : 0,
            "trip_count" => input('?trip_count') ? input('trip_count') : 0,
            "total_consumption" => input('?total_consumption') ? input('total_consumption') : 0,
            "balance" => input('?balance') ? input('balance') : 0,
            "give_count" => input('?give_count') ? input('give_count') : 0,
            "finally_login_time" => input('?finally_login_time') ? input('finally_login_time') : 0,
            "status" => input('?status') ? input('status') : 0,
            "now_login" => input('?now_login') ? input('now_login') : 0,
            "departement_id" => input('?departement_id') ? input('departement_id') : 0,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "PassengerName", "user_pwd"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //电话不重复
        $users = Db::name('user')->where(['PassengerPhone'=>input('PassengerPhone')])->find();
        if(!empty($users)){
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'电话已存在'
            ];
        }

        //电话不重复
        $usersf = Db::name('user')->where(['number'=>input('number')])->find();
        if(!empty($usersf)){
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'身份证已存在'
            ];
        }
        $params['nickname'] = "同城". substr(input('PassengerPhone'), 7, 11);
        $params['user_pwd'] = encrypt_salt("12345678");
        $params['create_time'] = time();

        $user = db('user')->insert($params);

        //增加日志
        $ini['title'] = "增加用户" ;
        $ini['param'] = json_encode($params) ;
        $ini['operate'] = "增加" ;
        $ini['create_time'] = time() ;
        $ini['manager_id'] = input('manager_id') ;
        Db::name('manager_log')->insert($ini) ;

        if($user){
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

   //城市列表 (基础项，不用验证权限)
   public function city_list(){
      $cn_city = Db::name('cn_city')->where(['is_dredge'=>1])->select();
      foreach ($cn_city as $key=>$value){
         $db = Db::name('city_scope')->where(['city_id'=>$value['id']])->value('db') ;
         $cn_city[$key]['db'] = $db;
      }
       return ['code'=>APICODE_SUCCESS,'data'=>$cn_city];
   }

   //企业列表
    public function enterprise_list(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "enterprise_name" => input('?enterprise_name') ? ['like','%'.input('enterprise_name').'%'] : null,
            "business_license" => input('?business_license') ? ['like','%'.input('business_license').'%'] : null,
            "create_time" => input('?create_time') ? ['egt',input('create_time')] : null,
        ];
        return self::pageReturnStrot(db('enterprise'),$params,'id desc');
    }

    //用户配置
    public function user_deploy(){
        $params = [
            "user_portrait" => input('?user_portrait') ? input('user_portrait') : null,
            "enterprise_portrait" => input('enterprise_portrait') ? input('enterprise_portrait') : null,
        ];

        if($params['user_portrait'] == "null" ){
            unset($params['user_portrait']);
        }
        if($params['enterprise_portrait'] == "null" ){
            unset($params['enterprise_portrait']);
        }
        $params['id'] = 1 ;

        $user_allocation = Db::name("user_allocation")->update($params);

        if($user_allocation){

            return ['code'=>APICODE_SUCCESS,'msg'=>'配置成功'];
        }else{
            return ['code'=>APICODE_ERROR,'msg'=>'配置失败'];
        }
    }

    public function base64($file){
        $end_name = substr($file,strrpos($file,'.'));
        $img_data = ['.jpg'=>1,'.jpeg'=>1,'.gif'=>1,'.png'=>1,'.bmp'=>1];//支持这些常规图片
        if(!isset($img_data[$end_name])){
            $end_name = '.jpg';//如果是其他格式则默认为jpg
        }
        $img_str = file_get_contents($file);
        $imgbase64 = base64_encode($img_str);
        $imgbase64 = 'data:image/jpeg;base64,'.$imgbase64;
        $file_name = date("YmdHis").mt_rand(1000,9999).$end_name;
        $base64_string= explode(',', $imgbase64); //截取data:image/png;base64, 这个逗号后的字符
        $tmp_name = base64_decode($base64_string[1]);//对截取后的字符使用base64_decode进行解码
        return $tmp_name;
    }
    //封禁
    public function banned(){

        $status  = request()->param('status');
        $user_id  = request()->param('user_id');
        $times  = request()->param('times');
        $cause   = request()->param('cause');

        $ini['id'] = $user_id ;
        $ini['status'] = $status ;

        $user = Db::name('user')->update($ini);

        
        if($user){
            //保存原因
            $inii['user_id'] = $user_id ;
            $inii['cause'] = $cause ;
            $inii['times'] = $times ;
            $inii['type'] = 1 ;

            $user_banned = Db::name('user_banned')->insert($inii);
            if($user_banned){
                return ['code'=>APICODE_SUCCESS,'msg'=>'封禁成功'];
            }else{
                return ['code'=>APICODE_DATABASEERROR,'msg'=>'数据库错误'];
            }
        }else{
            return ['code'=>APICODE_DATABASEERROR,'msg'=>'数据库错误111'];
        }
    }

    //解封
    public function deblocking(){
        $user_id  = request()->param('user_id');
        $status  = request()->param('status');
        $cause  = request()->param('cause');

        $ini['id'] = $user_id ;
        $ini['status'] = $status ;

        $user = Db::name('user')->update($ini);

        if($user){
            //保存原因表
            $inii['user_id'] = $user_id ;
            $inii['cause'] = $cause ;
            $inii['type'] = 2 ;

            $user_banned = Db::name('user_banned')->insert($inii);
            if($user_banned){
                return ['code'=>APICODE_SUCCESS,'msg'=>'解封成功'];
            }else{
                return ['code'=>APICODE_DATABASEERROR,'msg'=>'数据库错误'];
            }
        }else {
            return ['code'=>APICODE_DATABASEERROR,'msg'=>'数据库错误'];
        }
    }

    //按id获取用户信息
    public function get_User(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('user')->where($params)->find();

            $data['user_pwd'] =  encrypt_salt($data['user_pwd']);

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

    //更新用户
    public function update_User(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "portrait" => input('?portrait') ? input('portrait') : null,
            "PassengerName" => input('?PassengerName') ? input('PassengerName') : null,
            "user_pwd" => input('?user_pwd') ? input('user_pwd') : null,
            "PassengerPhone" => input('?PassengerPhone') ? input('PassengerPhone') : null,
            "number" => input('?number') ? input('number') : null,
            "is_attestation" => input('?is_attestation') ? input('is_attestation') : null,
            "trip_count" => input('?trip_count') ? input('trip_count') : null,
            "total_consumption" => input('?total_consumption') ? input('total_consumption') : null,
            "balance" => input('?balance') ? input('balance') : null,
            "give_count" => input('?give_count') ? input('give_count') : null,
            "finally_login_time" => input('?finally_login_time') ? input('finally_login_time') : null,
            "status" => input('?status') ? input('status') : null,
            "company_id" => input('?company_id') ? input('company_id') : null,
            "now_login" => input('?now_login') ? input('now_login') : null,
            "departement_id" => input('?departement_id') ? input('departement_id') : null,
            "logon_tip" => input('?logon_tip') ? input('logon_tip') : null,
            "create_time" => input('?create_time') ? input('create_time') : null,
            "PassengerGender" => input('?PassengerGender') ? input('PassengerGender') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
//        $id = $params["id"];
//        unset($params["id"]);
        $params['user_pwd'] = encrypt_salt(input('user_pwd'));

        $res = Db::name('user')->update($params);

        //增加日志
        $ini['title'] = "更新用户" ;
        $ini['param'] = json_encode($params) ;
        $ini['operate'] = "更新" ;
        $ini['create_time'] = time() ;
        $ini['manager_id'] = input('manager_id') ;
        Db::name('manager_log')->insert($ini) ;

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }

    //添加企业
    public function add_enterprise(){
        $data = input('') ;

        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "enterprise_name" => input('?enterprise_name') ? input('enterprise_name') : null,
            "business_license" => input('?business_license') ? input('business_license') : null,
            "portrait" => input('?portrait') ? input('portrait') : null,
            "create_time" => input('?create_time') ? input('create_time') : null,
            "vietinBanh_registration" => input('?vietinBanh_registration') ? input('vietinBanh_registration') : null,
            "evaluate" => input('?evaluate') ? input('evaluate') : null,
            "department_count" => input('?department_count') ? input('department_count') : null,
            "employ_count" => input('?employ_count') ? input('employ_count') : null,
            "gross_amount" => input('?gross_amount') ? input('gross_amount') : null,
            "balance" => input('?balance') ? input('balance') : null,
            "cooperative_id" => input('?cooperative_id') ? input('cooperative_id') : null,
            "recommend_person" => input('?recommend_person') ? input('recommend_person') : null,
            "recommend_mark" => input('?recommend_mark') ? input('recommend_mark') : null,
            "principal" => input('?principal') ? input('principal') : null,
            "principal_phone" => input('?principal_phone') ? input('principal_phone') : null,
            "user_id" => input('?user_id') ? input('user_id') : null,
            "monthly_quota" => input('?monthly_quota') ? (int)input('monthly_quota') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["city_id", "enterprise_name"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $business = $data['business_id'] ;
        $str = "" ;
        foreach ($business as $key=>$value){
            $str .= $value.",";
        }
        $str = substr($str,0,-1) ;
        $params['business_id'] = $str ;

        // 判断一下。这个用户有没有企业
        $enterprise = Db::name('user')->where(['id' => input('user_id') ])->value('enterprise_id') ;
        if($enterprise > 0){
            return [
                "code" => APICODE_ERROR,
                "msg" => "管理员已有企业，请重新选择管理员"
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
        $enterprise_id = db('enterprise')->insertGetId($params);

        //根据用户更新企业id
        $inii['id'] = input('user_id') ;
        $inii['enterprise_id'] = $enterprise_id ;
        $inii['join_time'] = time() ;
        $nickname = Db::name('user')->where(['id' =>input('user_id') ])->value('nickname') ;
        $inii['enterprise_nickname'] = $nickname ;
        Db::name('user')->update($inii);

        //添加企业，增加消费规则,增加管理者的消费规则
        $rule = $data['rule'] ;
        if(!empty($rule)){
            $ini['type'] = $rule['type'] ;
            $ini['type_item'] = $rule['type_item'] ;
            $ini['money'] = $rule['money'] ;
            $ini['day'] = $rule['day'] ;
            $ini['start_time'] = $rule['start_time'] ;
            $ini['end_time'] = $rule['end_time'] ;
            $ini['create_time'] = time() ;
            $ini['enterprise_id'] = $enterprise_id ;
            Db::name('enterprise_rule')->insert($ini) ;

            $iniii['type'] = $rule['type'] ;
            $iniii['type_item'] = $rule['type_item'] ;
            $iniii['money'] = $rule['money'] ;
            $iniii['day'] = $rule['day'] ;
            $iniii['start_time'] = $rule['start_time'] ;
            $iniii['end_time'] = $rule['end_time'] ;
            $iniii['create_time'] = time() ;
            $iniii['enterprise_id'] = $enterprise_id ;
            $iniii['user_id'] = input('user_id') ;
            Db::name('user_rule')->insert($iniii) ;
        }

        if($enterprise_id > 0){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'添加成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'添加失败'
            ];
        }
    }

    //按id查询企业
    public function query_enterprise(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('enterprise')->where($params)->find();

            $business_id = explode(',' ,$data['business_id'] ) ;

            $data['business_id'] = $business_id ;

            $data['rule'] = Db::name('enterprise_rule')->where(['enterprise_id' =>input('id') ])->find() ;

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
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

    //更新企业
    public function update_enterprise(){
//        $data =
        $params = [
            "id" => input('?id') ? input('id') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "enterprise_name" => input('?enterprise_name') ? input('enterprise_name') : null,
            "business_license" => input('?business_license') ? input('business_license') : null,
            "portrait" => input('?portrait') ? input('portrait') : null,
            "vietinBanh_registration" => input('?vietinBanh_registration') ? input('vietinBanh_registration') : null,
            "evaluate" => input('?evaluate') ? input('evaluate') : null,
            "department_count" => input('?department_count') ? input('department_count') : null,
            "employ_count" => input('?employ_count') ? input('employ_count') : null,
            "gross_amount" => input('?gross_amount') ? input('gross_amount') : null,
            "balance" => input('?balance') ? input('balance') : null,
            "cooperative_id" => input('?cooperative_id') ? input('cooperative_id') : null,
            "recommend_person" => input('?recommend_person') ? input('recommend_person') : null,
            "recommend_mark" => input('?recommend_mark') ? input('recommend_mark') : null,
            "principal" => input('?principal') ? input('principal') : null,
            "principal_phone" => input('?principal_phone') ? input('principal_phone') : null,
            "monthly_quota" => input('?monthly_quota') ? (int)input('monthly_quota') : null,
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
        unset($params["id"]);

        $data = input('') ;

        $business = $data['business_id'] ;
        $str = "" ;
        foreach ($business as $key=>$value){
            $str .= $value.",";
        }
        $str = substr($str,0,-1) ;
        $params['business_id'] = $str ;

        $res = db('enterprise')->where("id", $id)->update($params);


        //删除消单规则
        $rule_id = Db::name('enterprise_rule')->where(['enterprise_id' => $id])->value('id');
        Db::name('enterprise_rule')->where(['id' => $rule_id ])->delete() ;

            $rule = $data['rule'] ;
            if(!empty($rule)) {
                $ini['type'] = $rule['type'];
                $ini['type_item'] = $rule['type_item'];
                $ini['money'] = $rule['money'];
                $ini['day'] = $rule['day'];
                $ini['start_time'] = $rule['start_time'];
                $ini['end_time'] = $rule['end_time'];
                $ini['create_time'] = time();
                $ini['enterprise_id'] = $id;
                Db::name('enterprise_rule')->insert($ini);
            }

            return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }

    //查询企业所有用户
    public function query_User(){

        $id  = request()->param('id');

        //根据企业id，查询用户
        $user = Db::name('user')->where(['enterprise_id'=>$id])->select();
        $en_user = Db::name('enterprise')->where(['id' =>$id ])->value('user_id') ;
        foreach ($user as $key=>$value){
            $is_admin = 0 ;
            if($en_user == $value['id']){
                $is_admin = 1 ;
            }
            $user_rule = Db::name('user_rule')->where(['user_id' =>$value['id'] ])->find() ;
            $user[$key]['rule'] = $user_rule ;
            $user[$key]['is_admin'] = $is_admin ;
        }

        return ['code'=>APICODE_SUCCESS,'sum'=>count($user),'data'=>$user];
    }

    //用户首页
    public function user_Home(){
        $where = [] ; $where1 = [] ; $where2 = [] ; $where3 = [] ; $where4 =[] ;
        if(input('company_id') != 0){           //分公司首页
            $where['u.city_id'] = ['eq',input('city_id')];
            $where1['c.city_id'] = ['eq',input('city_id')];
            $where2['company_id'] = ['eq',input('company_id')];
            $where3['o.city_id'] = ['eq',input('city_id')];
            $where4['city_id'] = ['eq',input('city_id')];
        }

        $zr_day = strtotime('-1 day');                 //昨日

        $zr_count = Db::name('user')->where($where4)->where('create_time','lt',$zr_day)->count();

        $nationwide_user = Db::name('user')->alias('u')
            ->join('mx_order r', 'r.user_id = u.id', 'left')
            ->where($where)
            ->count();
//        $data['nationwide_user'] = $nationwide_user;
        $data['zr_count'] = $nationwide_user ;              //全国昨日用户总量

        $todayStart= strtotime( date('Y-m-d 00:00:00', time()) );

        $todayEnd= strtotime ( date('Y-m-d 23:59:59', time()) );

        $jt_count = Db::name('user')->where('create_time','egt',$todayStart)
                                            ->where('create_time','elt',$todayEnd)
                                            ->where($where4)
                                            ->count();

        $data['jt_count'] = $jt_count ;              //今日新增用户总量


        $zr_hy_count = Db::name('user')->alias('u')
                                            ->join('mx_order o','o.user_id = u.id')
                                            ->where('u.create_time','lt',$zr_day)
                                            ->where($where)
                                            ->count();

        $data['zr_hy_count'] = $zr_hy_count ;              //全国昨日活跃用户总量

        $jr_hy_count = Db::name('user')->alias('u')
            ->join('mx_order o','o.user_id = u.id')
            ->where('u.create_time','egt',$todayStart)
            ->where('u.create_time','elt',$todayEnd)
            ->where($where)
            ->count();

        $data['jr_hy_count'] = $jr_hy_count ;              //全国今日活跃用户总量

        //各城市用户排行榜
        $data['user_rankinglist'] = Db::name('user')
                                      ->alias('u')
                                      ->join('mx_cn_city c','c.id = u.city_id','inner')
                                      ->field('c.name as c_name,count(u.id) as u_count')
                                      ->where($where)
                                      ->group('city_id')
                                      ->order('u_count desc')
                                      ->limit(0,10)
                                      ->select();

        //各城市用户分布
        $data['user_distribution'] = Db::name('user')
            ->alias('u')
            ->join('mx_cn_city c','c.id = u.city_id','left')
            ->join('mx_cn_prov p','c.pcode = p.code')
            ->field('p.name as p_name,count(u.id) as u_count')
            ->where($where)
            ->group('p.id')->select();

        //各城市订单走势图
        $city_name = Db::name('order')->alias('o')
            ->distinct(true)
            ->field('c.name as c_name,count(o.id) as count')
            ->join('mx_cn_city c','c.id = o.city_id','inner')
            ->where($where3)
            ->limit(3)
            ->group('o.city_id')
            ->order('count desc')
            ->select();

        //分后台
        $filiale_order = Db::name('order')->alias('o')
            ->field('b.business_name,count(o.id) as order_count')
            ->join('mx_business b','b.id = o.business_id','left')
            ->group('o.business_id')
            ->where('o.create_time', 'egt', $todayStart)
            ->where('o.create_time', 'elt', $todayEnd)
            ->where($where3)
            ->select();

        $order_time = input('order_time');
//        $statistics_time = input('statistics_time');
        $start = "";   //开始时间
        $end = "" ;    //结束时间
        $start_o = "";   //开始时间
        $end_o = "" ;    //结束时间
        $Interval = 0 ; //评价的间隔天数
        $Interval_o = 0 ; //订单的间隔天数

        //日期为null   本周
        $times = aweek("",1);
        $beginThisweek = strtotime($times[0]);
        $endThisweek = strtotime($times[1]);
        $evaluate_time = input('evaluate_time');

        if($evaluate_time == "null" || $evaluate_time == "null " ){
            $start = $beginThisweek ;
            $end = $endThisweek ;
            $Interval = diffBetweenTwoDays((int)$start,(int)$end);
        }else{
            $evaluate_times = explode(',',$evaluate_time);
            $start = strtotime($evaluate_times[0]);
            $end = strtotime($evaluate_times[1]);

            $Interval = diffBetweenTwoDays((int)$start,(int)$end);
        }

        if($order_time == "null" || $order_time == "null " ){
            $start_o = $beginThisweek ;
            $end_o = $endThisweek ;

            $Interval_o = diffBetweenTwoDays((int)$start_o,(int)$end_o);
        }else{
            $order_times = explode(',',$order_time);
            $start_o = strtotime($order_times[0]);
            $end_o = strtotime($order_times[1]);

            $Interval_o = diffBetweenTwoDays((int)$start_o,(int)$end_o);
        }

        $order = [] ;
        for ($y = 0; $y <= $Interval_o; $y++) {     //行

            $op_o = date('Y-m-d',$start_o);

            $day_start_o = strtotime($op_o.' 00:00:00') ;  //当天开始时间
            $day_end_o = strtotime($op_o.' 23:59:59') ;    //当天结束时间
            $days =  $this->days(date('m', $start_o) );
            $ini = date("Y-m-d",strtotime("+".$y." day",strtotime($op_o)));
            $times = date('m',strtotime($ini)). '-' .date('d',strtotime($ini)) ;
//            if((((int)date('d', $start_o)) + $y) <= $days) {
                $order[] = [
                    'times' => date('m',strtotime($ini)). '/' .date('d',strtotime($ini)),
                    $city_name[0]['c_name'] => $this->OrderCount($city_name[0]['c_name'],$times),
                    $city_name[1]['c_name'] => $this->OrderCount($city_name[1]['c_name'],$times),
                    $city_name[2]['c_name'] => $this->OrderCount($city_name[2]['c_name'],$times),
                ];
//            }
        }

        $data['cityOrdersTieData']['city_name'] = $city_name;
        $data['cityOrdersTieData']['order'] = $order;

        //全国评价及投诉走势图
        $reviewOrdersData = [] ;

        for ($i = 0; $i <= $Interval; $i++) {

            $op = date('Y-m-d',$start);

            $day_start = strtotime($op.' 00:00:00') ;  //当天开始时间
            $day_end = strtotime($op.' 23:59:59') ;    //当天结束时间

            $days =  $this->days(date('m', $start) );
            $ini = date("Y-m-d",strtotime("+".$i." day",strtotime($op)));
            $times = date('m',strtotime($ini)). '-' .date('d',strtotime($ini)) ;

//            if((((int)date('d', $start)) + $i) <= $days) {
                $reviewOrdersData[] = [
                    'times' => date('m',strtotime($ini)). '/' .date('d',strtotime($ini)),
                    'evaluate_count' => $this->EvaluateCount($where2, $times),
                    'complain_count' => $this->ComplaintCount($where2, $times),
                ];
//            }

        }

        $data['reviewOrdersData'] = $reviewOrdersData ;
        $data['filiale_order'] = $filiale_order;

        return ['code'=>APICODE_SUCCESS,'data'=>$data];
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

    //订单数
    private function OrderCount($city,$times){
        $city_id = Db::name('cn_city')->where(['name'=>$city])->value('id');
        $day_start = strtotime(date('Y',time())."-".$times." 00:00:00") ;
        $day_end = strtotime(date('Y',time())."-".$times." 23:59:59") ;

        $order_count = Db::name('order')->alias('o')
            ->where(['o.city_id'=>$city_id])
            ->where('o.create_time','gt' , $day_start )
            ->where('o.create_time','lt' , $day_end )
            ->count();
        return $order_count ;
    }

    //查询用户配置
    public function queryUserAllocation(){
        return self::pageReturn(db('user_allocation'), '');
    }

    //解绑企业用户
    public function UnlinkCompanyUser(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $ini['enterprise_id'] = 0;
            $ini['id'] = input('id') ;
            $user = db('user')->update($ini);
            //删除消费规则
            $user_rule_id = Db::name('user_rule')->where(['user_id'=>input('id')])->value('id') ;
            Db::name('user_rule')->where(['id'=>$user_rule_id])->delete();

            if($user){
                return [
                    'code'=>APICODE_SUCCESS,
                    'msg'=>'解绑成功'
                ];
            }else{
                return [
                    'code'=>APICODE_ERROR,
                    'msg'=>'解绑失败'
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //查询用户配置
    public function QueryUserConfiguration(){
       $data = Db::name('user_allocation')->where(['id'=>1])->find();
        return [
            "code" =>APICODE_SUCCESS,
            "msg" => "查询成功",
            "data" => $data
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
    //优惠券信息
    public function UserCoupon(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];
            $data = db('user_coupon')->where($params)->select();
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

    //企业消费记录
    public function ConsumptionRecord(){
        if (input('?enterprise_id')) {
            $params = [
                "enterprise_id" => input('enterprise_id')
            ];
            $data = db('enterprise_consumerdetails')->where($params)->select();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
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

    //消费规则(企业)
    public function EnterpriseRule(){
        $params = [
            "enterprise_id" => input('?enterprise_id') ? input('enterprise_id') : null,
            "type" => input('?type') ? input('type') : null,
            "type_item" => input('?type_item') ? input('type_item') : null,
            "money" => input('?money') ? input('money') : null,
            "day" => input('?day') ? input('day') : null,
            "start_time" => input('?start_time') ? input('start_time') : null,
            "end_time" => input('?end_time') ? input('end_time') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["enterprise_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //判断数据是否存在
        $enterprise = Db::name('enterprise_rule')->where(['enterprise_id' => input('enterprise_id') ])->find() ;
        if(!empty($enterprise)){
            $params['id'] =$enterprise['id'];
            $res = Db::name('enterprise_rule')->update($params);
            if($res > 0){
                return [
                    "code" =>APICODE_SUCCESS,
                    "msg" => "更新成功",
                ];
            }else{
                return [
                    "code" =>APICODE_ERROR,
                    "msg" => "更新失败",
                ];
            }
        }else{
            $params['create_time'] = time() ;
            $enterprise_rule = Db::name('enterprise_rule')->insert($params) ;
            if($enterprise_rule){
                return [
                    "code" =>APICODE_SUCCESS,
                    "msg" => "添加成功",
                ];
            }else{
                return [
                    "code" =>APICODE_ERROR,
                    "msg" => "添加失败",
                ];
            }
        }
    }

    //用户消费规则
    public function UserEnterpriseRule(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "user_id" => input('?user_id') ? input('user_id') : null,
            "enterprise_id" => input('?enterprise_id') ? input('enterprise_id') : null,
            "type" => input('?type') ? input('type') : null,
            "type_item" => input('?type_item') ? input('type_item') : null,
            "money" => input('?money') ? input('money') : null,
            "day" => input('?day') ? input('day') : null,
            "start_time" => input('?start_time') ? input('start_time') : null,
            "end_time" => input('?end_time') ? input('end_time') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["user_id","enterprise_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        //更新信息
        $res = Db::name('user_rule')->update($params);
        if($res > 0){
            return [
                "code" =>APICODE_SUCCESS,
                "msg" => "更新成功",
            ];
        }else{
            return [
                "code" =>APICODE_ERROR,
                "msg" => "更新失败",
            ];
        }
    }

    //更改用户的消费规则
    public function UpdateUserRules(){
        $data = input('') ;

        $params = [
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
        //获取企业id
        $enterprise_id = Db::name('user')->where(['id'=>input('user_id')])->value('enterprise_id') ;

        //删除用户消费规则
        $id = Db::name('user_rule')->where(['user_id' =>input('user_id') ])->value('id') ;
        Db::name('user_rule')->where(['id'=>$id])->delete() ;

        //然后存
        $rule = $data['rule'] ;

        $ini['type'] = $rule['type'];
        $ini['type_item'] = $rule['type_item'];
        $ini['money'] = $rule['money'];
        $ini['day'] = $rule['day'];
        $ini['start_time'] = $rule['start_time'];
        $ini['end_time'] = $rule['end_time'];
        $ini['create_time'] = time();
        $ini['enterprise_id'] = $enterprise_id;
        $ini['user_id'] = input('user_id') ;
        $res = Db::name('user_rule')->insert($ini);

        if($res > 0){
            return ['code'=>APICODE_SUCCESS,'msg'=>'更改成功'];
        }else{
            return ['code'=>APICODE_ERROR,'msg'=>'更改失败'];
        }
    }

    //企业审核
    public function EnterpriseAudit(){
        if (input('?enterprise_id')) {
            $params = [
                "id" => input('enterprise_id'),
                "state" => input('state'),
            ];
            $data = input('') ;
            //更改企业状态
            $res = Db::name('enterprise')->update($params) ;

            //添加企业，增加消费规则,增加管理者的消费规则
            $rule = $data['rule'] ;
            if(!empty($rule)){
                $ini['type'] = $rule['type'] ;
                $ini['type_item'] = $rule['type_item'] ;
                $ini['money'] = $rule['money'] ;
                $ini['day'] = $rule['day'] ;
                $ini['start_time'] = $rule['start_time'] ;
                $ini['end_time'] = $rule['end_time'] ;
                $ini['create_time'] = time() ;
                $ini['enterprise_id'] = input('enterprise_id') ;
                Db::name('enterprise_rule')->insert($ini) ;

                //获取企业的用户id
                $user_id = Db::name('enterprise')->where(['id'=>input('enterprise_id')])->value('user_id') ;
                //根据用户更新企业id
                $inii['id'] = $user_id ;
                $inii['enterprise_id'] = input('enterprise_id') ;
                $inii['join_time'] = time() ;
                $nickname = Db::name('user')->where(['id' => $user_id ])->value('nickname') ;
                $inii['enterprise_nickname'] = $nickname ;
                Db::name('user')->update($inii);

                $iniii['type'] = $rule['type'] ;
                $iniii['type_item'] = $rule['type_item'] ;
                $iniii['money'] = $rule['money'] ;
                $iniii['day'] = $rule['day'] ;
                $iniii['start_time'] = $rule['start_time'] ;
                $iniii['end_time'] = $rule['end_time'] ;
                $iniii['create_time'] = time() ;
                $iniii['enterprise_id'] = input('enterprise_id') ;
                $iniii['user_id'] = $user_id ;
                Db::name('user_rule')->insert($iniii) ;
            }

            if($res > 0){
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "审核成功",
                ];
            }else{
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "审核失败",
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "企业ID不能为空"
            ];
        }
    }

    //企业用户-封禁/解封
    public function EnterpriseDeblocking(){
        $params = [
            "enterprise_id" => input('?enterprise_id') ? input('enterprise_id') : null,
            "state" => input('?state') ? input('state') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["enterprise_id","state"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $ini['id'] = input('enterprise_id') ;
        $ini['state'] = input('state') ;
        $res = Db::name('enterprise')->update($ini) ;
        $str = "";
        if(input('state') == 1){
            $str = "解封" ;
        }else if(input('state') == 3){
            $str = "封禁" ;
        }
        if($res > 0){
            return [
                "code" =>APICODE_SUCCESS,
                "msg" => "成功",
            ];
        }else{
            return [
                "code" =>APICODE_SUCCESS,
                "msg" => "失败",
            ];
        }
    }
    
}