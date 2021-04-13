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

class Airlines extends Base
{
    //添加电话记录
    public function addPhoneRecord(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "answer_type" => input('?answer_type') ? input('answer_type') : null,
            "phone" => input('?phone') ? input('phone') : null,
            "personnel_label" => input('?personnel_label') ? input('personnel_label')  : null,
            "service_type" => input('?service_type') ? input('service_type') : null,
            "call_times" => input('?call_times') ? input('call_times') : null,
            "remark" => input('?remark') ? input('remark') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "phone", "answer_type"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $phone_record = db('phone_record')->insert($params);

        if($phone_record){
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
    //电话记录列表
    public function PhoneRecordList(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "answer_type" => input('?answer_type') ? input('answer_type') : null,
            "phone" => input('?phone') ? ['like', '%' . input('phone') . '%'] : null,
            "personnel_label" => input('?personnel_label') ? input('personnel_label') : null,
            "service_type" => input('?service_type') ? input('service_type') : null,
        ];
        return self::pageReturn(db('phone_record'), $params);
    }
    //添加客服回访
    public function airlinesCallback(){
        $params = [
            "times" => input('?times') ? input('times') : null,
            "status" => input('?status') ? input('status') : null,
            "content" => input('?content') ? input('content') : null,
            "user_phone" => input('?user_phone') ? input('user_phone')  : null,
            "user_name" => input('?user_name') ? input('user_name') : null,
            "cause" => input('?cause') ? input('cause') : null,
            "count" => input('?count') ? input('count') : null,
            "sum_count" => input('?sum_count') ? input('sum_count') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_phone", "user_name"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $params['create_time'] = time();
        $airlines_callback = db('airlines_callback')->insert($params);

        if($airlines_callback){
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
    //客服回访列表
    public function CallbackList(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "times" => input('?times') ? ['like', '%' . input('times') . '%'] : null,
            "status" => input('?status') ? input('status') : null,
            "operation" => input('?operation') ? input('operation') : null,
        ];
        return self::pageReturnStrot(db('airlines_callback'), $params,'id desc');
    }
    //根据id获取客服回访详情
    public function getCallback(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('airlines_callback')->where($params)->find();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "回访ID不能为空"
            ];
        }
    }
    //投诉列表
    public function complainList(){
        $params = [
            "c.city_id" => input('?city_id') ? input('city_id') : null,
            "c.complain_date" => input('?complain_date') ? input('complain_date') : null,
            "c.manner" => input('?manner') ? input('manner') : null,
        ];

        $params = $this->filterFilter($params);
        if(empty(input('manner'))){
            unset($params['c.manner']);
        }

        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;

        $data= db('complain')->alias('c')
            ->field('c.*,con.DriverName')
            ->join('mx_conducteur con','con.id = c.conducteur_id','left')->where(self::filterFilter($params))->order('c.id desc')->page($pageNum, $pageSize)
            ->select();

        $sum = db('complain')->alias('c')
            ->field('c.*,con.DriverName')
            ->join('mx_conducteur con','con.id = c.conducteur_id','left')->where(self::filterFilter($params))->count();

        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" => $data
        ];

//        return self::pageReturn( $db, $params);
    }
    //添加投诉
    public function addComplain(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "complain_date" => input('?complain_date') ? strtotime(input('complain_date')) : null,
            "manner" => input('?manner') ? input('manner') : null,
            "user_phone" => input('?user_phone') ? input('user_phone') : null,
            "title" => input('?title') ? input('title') : null,
            "DriverName" => input('?DriverName') ? input('DriverName') : null,
            "cause" => input('?cause') ? input('cause') : null,
            "content" => input('?content') ? input('content') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["city_id", "user_phone"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $complain = db('complain')->insert($params);

        if($complain){
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
    //根据id获取投诉详情
    public function getComplain(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('complain')->where($params)->find();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "投诉ID不能为空"
            ];
        }
    }
    //意见反馈列表
    public function feedbackList(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "times" => input('?times') ? input('times') : null,
            "status" => input('?status') ? input('status') : null,
        ];

        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;
        $sortBy=input('?orderBy') ? input('orderBy') : 'id desc';
        $data=Db::name('feedback')->where(self::filterFilter($params))->order($sortBy)->page($pageNum, $pageSize)
            ->select();
        $sum = Db::name('feedback')->where(self::filterFilter($params))->count();
        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" => $data
        ];

//        return self::pageReturnStrot(db('feedback'), $params,'id desc');
    }
    //更新状态
    public function updateFeedbackState(){
        $params = [
            "id" => input('?id') ? input('id') : null,
            "status" => input('?status') ? input('status') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["id", "status"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $res = Db::name('feedback')->update($params);
        if($res > 0){
            return [
                'code'=>APICODE_SUCCESS,
                'msg'=>'更新成功'
            ];
        }else{
            return [
                'code'=>APICODE_ERROR,
                'msg'=>'更新失败'
            ];
        }
    }
    //添加工单
    public function addIndividual(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "report_department" => input('?report_department') ? input('report_department') : null,
            "particulars" => input('?particulars') ? input('particulars') : null,
            "content" => input('?content') ? input('content')  : null,
            "company_id" => input('?company_id') ? input('company_id')  : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city_id", "report_department","company_id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $individual = db('individual')->insert($params);

        if($individual){
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
    //上报部门（角色）
    public function ReportedSector(){
        if (input('?company_id')) {
            $params = [
                "company_id" => input('company_id')
            ];
            $data = db('authgroup')->field('id,title')->where($params)->select();
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
    //工单列表
    public function individualList(){
        $params = [
            "city_id" => input('?city_id') ? input('city_id') : null,
            "start_time" => input('?start_time') ? ['gt',  input('start_time')] : null,
            "end_time" => input('?end_time') ? ['lt', input('end_time') ] : null,
            "report_department" => input('?report_department') ? input('report_department') : null,
            "status" => input('?status') ? input('status') : null,
        ];
        return self::pageReturn(db('individual'), $params);
    }
    //添加话术模板
    public function addWordsTemplate(){
        $params = [
            "type" => input('?type') ? input('type') : null,
            "solution" => input('?solution') ? input('solution') : null,
            "polite_words" => input('?polite_words') ? input('polite_words') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["type", "solution", "polite_words"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $words_template = db('words_template')->insert($params);

        if($words_template){
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
    //话术模板列表
    public function WordsTemplateList(){
        return self::pageReturn(db('words_template'), '');
    }
    //客服配置
    public function AirlinesConfiguration(){
        $params = [
            "id" => input('?id') ? input('id') : 1,
            "network_advisory_Interface" => input('?network_advisory_Interface') ? input('network_advisory_Interface') : null,
            "applet_advisory_Interface" => input('?applet_advisory_Interface') ? input('applet_advisory_Interface') : null,
            "user_advisory_Interface" => input('?user_advisory_Interface') ? input('user_advisory_Interface') : null,
            "driver_advisory_Interface" => input('?driver_advisory_Interface') ? input('driver_advisory_Interface') : null,
            "messages_advisory_Interface" => input('?messages_advisory_Interface') ? input('messages_advisory_Interface') : null,
            "lightalk_advisory_Interface" => input('?lightalk_advisory_Interface') ? input('lightalk_advisory_Interface') : null,
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
        $res = db('airlines_setting')->where("id", $id)->update($params);
        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }
    //客服人员管理日志
    public function AirlinesLogList(){
        return self::pageReturn(db('airlines_log'), '');
    }
    //客服首页
    public function airlines_home(){
        //待处理
        $dcl_individual =  Db::name('individual')->where(['status'=>0])->count();
        $data['dcl_individual'] = $dcl_individual ;

        //已处理
        $ycl_individual =  Db::name('individual')->where(['status'=>1])->count();
        $data['ycl_individual'] = $ycl_individual ;

        //处理中
        $clz_individual =  Db::name('individual')->where(['status'=>2])->count();
        $data['clz_individual'] = $clz_individual ;

        //公告列表
        $data['announcement'] = Db::name('airlines_announcement')->select();

        $order_time = input('time');
        //日期为null   本周
        $times = aweek("", 1);
        $beginThisweek = strtotime($times[0]);
        $endThisweek = strtotime($times[1]);

        if ($order_time == "null"||$order_time == "null ") {
            $start_o = $beginThisweek;
            $end_o = $endThisweek;

            $Interval_o = diffBetweenTwoDays((int)$start_o, (int)$end_o);
        } else {
            $order_times = explode(',', $order_time);
            $start_o = strtotime($order_times[0]);
            $end_o = strtotime($order_times[1]);
            $Interval_o = diffBetweenTwoDays((int)$start_o, (int)$end_o);
        }

        //业务咨询走势图
        $city_name = Db::name('airlines_callback')->alias('a')
            ->distinct(true)
            ->field('c.name as c_name,count(a.id) as count')
            ->join('mx_cn_city c', 'c.id = a.city_id', 'inner')
            ->limit(3)
            ->select();

        $order = [];
        $where3 = [] ;
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
            ];
//            }
        }
        $data['cityOrdersTieData']['city_name'] = $city_name;
        $data['cityOrdersTieData']['order'] = $order;

        return ['code'=>APICODE_SUCCESS,'data'=>$data];
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
    //订单数
    private function OrderCount($city,$times){

        $city_id = Db::name('cn_city')->where(['name'=>$city])->value('id');
        $day_start = strtotime(date('Y',time())."-".$times." 00:00:00") ;
        $day_end = strtotime(date('Y',time())."-".$times." 23:59:59") ;

        $order_count = Db::name('airlines_callback')->alias('o')
            ->where('o.create_time','gt' , $day_start )
            ->where('o.create_time','lt' , $day_end )
            ->where(['o.city_id'=>$city_id])
            ->count();

        return $order_count ;
    }
    //工单处理
    public function WorkorderDispose(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            //获取工单
            $individual = db('individual')->where($params)->find();

            //美团回调
            $param = [] ;

            $param['channel'] = "tcdc_car_test" ;
            $param['timestamp'] = strval(time() * 1000) ;
            $param['sign'] = "805b6d47ff4d878a4d73495953e270e08bb0dca9" ;
            $param['mtOrderId'] = "" ;
            $param['partnerOrderId'] = "" ;
            $param['refundType'] = "" ;
            $param['isFirstTime'] = "" ;
            $param['refundAmount'] = "" ;
            $param['sourceType'] = "" ;
            $param['processState'] = "" ;
            $param['reason'] = "" ;
            $param['remark'] = "" ;
            $param['handler'] = "" ;
            $param['partnerCaseId'] = "" ;
            $param['faqId'] = "" ;

            $datas = $this->request_post("https://qcs-openapi.apigw.test.meituan.com/api/open/callback/common/orderComplaint", $param);   //"application/x-www-from-urlencoded"

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "处理成功",
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "工单ID不能为空"
            ];
        }
    }
    function request_post($url = "", $param = "", $header = "")
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init(); // 初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); // 抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); // 设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); // post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch); // 运行curl

        curl_close($ch);
        return $data;
    }
}