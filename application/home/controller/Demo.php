<?php
namespace app\home\controller;
use think\Controller;
use think\Request;
use think\Db;

class Demo extends Controller
{
    public function index()
    {
        $data = Request::instance()->param();

        $file = fopen('./order.txt', 'a+');
//        fwrite($file, "-------------------------------------------"."\r\n");
//        fwrite($file, "-------------------data:--------------------".json_encode($data)."\r\n");
        //创建订单
        $users = Db::name('user')->where(['unionid' =>$data['unionid']])->find() ;
//        fwrite($file, "-------------------users:--------------------".json_encode($users)."\r\n");
        if($users == null ){ //如果为空，需要保存用户
//            fwrite($file, "-------------------方法进来了11111111:--------------------"."\r\n");
//            fwrite($file, "-------------------city--------------------".$data['city']."\r\n");
            $city_id = Db::name('cn_city')->where([ 'name' => $data['city']."市" ])->value('id') ;
            if(!empty($city_id)){
                $ini['city_id'] = $city_id ;
            }

            $ini['nickname'] = $data['nickname'] ;
            $ini['portrait'] = $data['headimgurl'] ;
            $ini['unionid'] = $data['unionid'] ;
            $ini['bjnews_openid'] = $data['openid'] ;
//            fwrite($file, "-------------------ini:--------------------".json_encode($ini)."\r\n");
            $user_id = Db::name('user')->insertGetId($ini);
//            fwrite($file, "-------------------user_id:--------------------".$user_id."\r\n");
            $users = Db::name('user')->find();

            //发送消息
            $message = "温馨提示:为了避免纠纷，请您仔细阅读乘客须知。" ;
            $this->newsService($data['openid'],$message) ;

            $messages = " 请先绑定手机号，再进行快捷叫车 <a href='https://php.51jjcx.com/wxapi/Indexs/phonenumber/user_id/$user_id'>【立即绑定】</a>" ;
            $this->newsService($data['openid'],$messages) ;

            return 999;
        }else{
            if(empty($users['PassengerPhone'])){
//发送消息
                $message = "温馨提示:为了避免纠纷，请您仔细阅读乘客须知。" ;
                $this->newsService($data['openid'],$message) ;
                $user_id = $users['id'] ;
                $messages = " 请先绑定手机号，再进行快捷叫车 <a href='https://php.51jjcx.com/wxapi/Indexs/phonenumber/user_id/$user_id'>【立即绑定】</a>" ;
                $this->newsService($data['openid'],$messages) ;

                return 999;
            }
        }

        //判断一下，当前是否还有出租车的订单
        $orders = Db::name('order')->where(['user_id'=>$users['id'],'business_id'=>7])
                                           ->where('status','in','1,2,3,4');
        if(!empty($orders)){
            return 555 ;
        }

        //根据位置来排定公司
        $city = $data['city'] ;         //城市
        $district = $data['district'] ;     //区/县
        $city_id = Db::name('cn_city')->where([ 'name' => $data['citys']])->value('id') ;

        if(empty($city_id)){
            $city_id = Db::name('cn_city')->where([ 'name' => $data['district'] ])->value('id') ;
        }
        $company_id = Db::name('company')->where(['city_id' =>$city_id ])->value('id') ;

        if(empty($company_id)){     //不存在公司
            return 666;
        }

        //进行springroot下单
        $classification = "预约" ;
        $url_o = "http://192.168.0.110:8985/OrderCall/newUserCall/cityID/{$city_id}/classification/{$classification}/requestBusinessType/0/requestCarType/7/requestService/1/userID/{$users['id']}" ;
        $this->_request($url_o );  //发送请求
        return 0 ;
    }
    public function newsService($bjnews_openid,$message){
//        $file = fopen('./log.txt', 'a+');
//        fwrite($file, "-------------------出租车抢单进来了--------------------"."\r\n");
        //获取用户的公众号openid
//        $bjnews_openid = Db::name('user')->where(['id'=>$user_id])->value('bjnews_openid') ;
        $w = new Wechat("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449");
        $res = $w->sendServiceText($bjnews_openid,$message);
    }
    function appointmentByCompany($title, $companyId, $message, $type, $business_id, $business_type_id, $conducteur_id)
    {
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
        $header = array(
            "Authorization:Basic $base64",
            "Content-Type:application/json"
        );
        $param = array("platform" => "all", "audience" => array("tag" => array("Company_$companyId")), "message" => array("msg_content" => $message . "," . $type . "," . $companyId . "," . $business_id . "," . $business_type_id . "," . $conducteur_id, "title" => $title));
        $params = json_encode($param);
        $res = $this->request_post($url, $params, $header);
        $res_arr = json_decode($res, true);
    }

    public function _request($curl, $https = true, $method = 'GET', $data = null){
        $ch = curl_init(); // 初始化curl
        curl_setopt($ch, CURLOPT_URL, $curl); //设置访问的 URL
        curl_setopt($ch, CURLOPT_HEADER, false); //放弃 URL 的头信息
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回字符串，而不直接输出
        if($https){ //判断是否是使用 https 协议
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不做服务器的验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  //做服务器的证书验证
        }
        if($method == 'POST'){ //是否是 POST 请求
            curl_setopt($ch, CURLOPT_POST, true); //设置为 POST 请求
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //设置POST的请求数据
        }
        $content = curl_exec($ch); //开始访问指定URL
        curl_close($ch);//关闭 cURL 释放资源
        return $content;
    }

    // 极光推送提交
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

    //用户取消用车
    public function UserCancel(){
        $data = Request::instance()->param();
        $order_id = intval( substr($data['order_id'], 1, -1) ) ;
        //获取用户id
        $user_id = Db::name('order')->where(['id'=>$order_id])->value('user_id') ;
        $bjnews_openid = Db::name('user')->where(['id'=>$user_id])->value('bjnews_openid') ;

        $orders = Db::name('order')->where(['id'=>$order_id,'status'=>5])->find();
        if(!empty($orders)){    //订单已取消，就不能在取消了。
            $this->newsService( $bjnews_openid ,'行程已取消') ;
        }

        $ini['id'] = $order_id ;
        $ini['status'] = 5 ;
        $res = Db::name('order')->update($ini) ;

        if($res > 0){
            $this->newsService( $bjnews_openid ,'行程已取消') ;
            $this->redirect('/wxapi/Indexs/canceltip');
        }else{
            $this->newsService( $bjnews_openid ,'行程取消失败') ;
        }
    }
    //用户取消用车
    public function UserCancels(){
        $data = Request::instance()->param();
        $user_id = Db::name('order')->where(['id'=>$data['order_id']])->value('user_id') ;
        $bjnews_openid = Db::name('user')->where(['id'=>$user_id])->value('bjnews_openid') ;

        $orders =Db::name('order')->where(['id'=>$data['order_id']])->find() ;
        if(!empty($orders)){
            $this->newsService( $bjnews_openid ,'行程已取消') ;
            return ;
        }

        $ini['id'] = $data['order_id'] ;
        $ini['status'] = 5 ;
        $res = Db::name('order')->update($ini) ;
        //获取用户id

        $conducteur_id = Db::name('order')->where(['id'=>$data['order_id']])->value('conducteur_id') ;
        if($res > 0){
            $this->newsService( $bjnews_openid ,'行程已取消') ;
            $this->appointment("取消订单",$conducteur_id,$data['order_id'],3);
            $this->redirect('/wxapi/Indexs/canceltip');
        }else{
            $this->newsService( $bjnews_openid ,'行程取消失败') ;
        }
    }
    function appointment($title, $uid, $message, $type)
    {
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
        $header = array(
            "Authorization:Basic $base64",
            "Content-Type:application/json"
        );
        $param = array("platform" => "all", "audience" => array("tag" => array("D_$uid")), "message" => array("msg_content" => $message . "," . $type, "title" => $title));
        $params = json_encode($param);
        $res = $this->request_post($url, $params, $header);
        $res_arr = json_decode($res, true);
    }

}

?>