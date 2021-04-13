<?php
include_once ROOT_PATH.'extend/Wexin_share/wy.php';
use think\Db;

class WX {

    public function valid()
    {
        $echoStr = $_GET["echostr"];
        //valid signature , option
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }


    //消息推送
    public function responseMsg()
    {
        $file = fopen('./order.txt', 'a+');
        //get post data, May be due to the different environments
//        $postStr =  isset($GLOBALS["HTTP_RAW_POST_DATA"]) ?  $GLOBALS["HTTP_RAW_POST_DATA"]  : "" ;
        $postStr =  file_get_contents("php://input") ?  file_get_contents("php://input")  : "" ;
        //extract post data
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            
            //查看手册接收消息
            switch($postObj->MsgType)
            {
                case"event":
                    //关注事件
                    $this->_doEvent($postObj);
                    break;
                case"text";
                    //文本类型
                    $this->_doText($postObj);
                    break;
                case"image";
                    //图片类型
                    $this->_doImage($postObj);
                    break;
                case"voice";
                    //语言类型
                   // $this->_doVoice($postObj);
                   
                   $fromUsername = $postObj->FromUserName;
                   $toUsername = $postObj->ToUserName;
                   $keyword = trim($postObj->Recognition);//这里就是微信自带的语音识别返回的识别到的文本，然后格式化输出到公众号就ok了
                   $time = time();
                  
                   $msgType = "text";
                   $contentStr = "你发送的是语音\n内容为：\n"."$keyword";
//                   if($keyword == ""){
//                        $contentStr = "这小子真帅";
//                    }else{
//                        $contentStr = "你发送的内容是：".$keyword;
//                    }
                    
                   $textTpl = "<xml>
                                     <ToUserName><![CDATA[%s]]></ToUserName>
                                     <FromUserName><![CDATA[%s]]></FromUserName>
                                     <CreateTime>%s</CreateTime>
                                     <MsgType><![CDATA[%s]]></MsgType>
                                     <Content><![CDATA[%s]]></Content>
                                     <FuncFlag>0</FuncFlag>
                                         </xml>";

                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                    echo $resultStr;
                    break;  
                case"video";
                    //视频类型
                    $this->_doVideo($postObj);
                    break;

                case"location";
                //显示一下，获取位置的城市是否有运营商
                //地理位置
                   $fromUsername = $postObj->FromUserName;
                   $toUsername = $postObj->ToUserName;
                   $keyword =trim($postObj->Content);
                   $time = time();
                   $msgType = "text";
                   $latitude	= $postObj ->Location_X;//提取纬度
				   $longitude	= $postObj ->Location_Y;//提取经度
				   
				   $key = "NN5BZ-IUBCD-3SZ4Z-PBX4T-PKOS2-TDF5B";
				   $url = "https://apis.map.qq.com/ws/geocoder/v1/?location={$latitude},{$longitude}&key={$key}&get_poi=1";
				   $info = file_get_contents($url);
                   $infos = json_decode($info, true);
                    $city_name = $infos['result']['address_component']['city'] ;                   //城市
                    $district = $infos['result']['address_component']['district'] ;               //区

                    //   $contentStr = "你的纬度是{$latitude},你的经度是{$longitude}<a href=\"http://www.zxbaswyh.gov.cn/home/demo/index/la/{$latitude}/lo/{$longitude}\">请点击这里</a>";
//                   $contentStr = "你当前的地址是：{$info['result']['address']}--你的用户ID：{$fromUsername}<a href=\"https://php.51jjcx.com/home/demo/index/la/{$latitude}/lo/{$longitude}/address/{$info['result']['address']}/userid/{$fromUsername}\">请点击这里</a>";
                    $curl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx78c9900b8a13c6bd&secret=a1391017fa573860e266fd801f2b0449'; //通过该 URL 获取Access Token
                    $content = $this->_request($curl);  //发送请求

                    file_put_contents($file, $content);//保存Access Token 到文件
                    $content = json_decode($content); //解析json

                    $access_token = $content->access_token ;
//                    fwrite($file, "-------------------access_token:--------------------".$access_token."\r\n");

                    $urls = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$fromUsername."&lang=zh_CN" ;
                    $contents = json_decode($this->_request($urls),true);  //发送请求
//                    fwrite($file, "-------------------contents:--------------------".json_encode($contents)."\r\n");

                    $unionid = $contents['unionid'] ;
                    $nickname = $contents['nickname'] ;
                    $headimgurl = $contents['headimgurl'] ;
                    $city = $contents['city'] ;
                    $openid = $contents['openid'] ;

                    $user_id = 0 ;
                    $contentStr = "" ;
                    $url_o = "https://php.51jjcx.com/home/demo/index/la/{$latitude}/lo/{$longitude}/address/{$info['result']['address']}/userid/{$fromUsername}/unionid/{$unionid}/nickname/{$nickname}/headimgurl/{$headimgurl}/city/{$city}/openid/{$openid}/district/{$district}/citys/{$city_name}" ;

                       $content_o = $this->_request($url_o );  //发送请求

                       if($content_o == 666){
                        $contentStr = "您好，这个区域暂时没有开通！" ;
                       }

                       if($content_o == 555){
                            $contentStr = "您有未完成的订单，请不要重新叫车！" ;
                        }

                       if($content_o != 999 && $content_o != 666){
                         $contentStr = " 您好，您的用车需求已经通知给附近司机，请稍等 <a href='https://php.51jjcx.com/home/demo/UserCancel/order_id/$content_o '>【取消用车】</a>。";
                       }

                    $textTpl = "<xml>
                                     <ToUserName><![CDATA[%s]]></ToUserName>
                                     <FromUserName><![CDATA[%s]]></FromUserName>
                                     <CreateTime>%s</CreateTime>
                                     <MsgType><![CDATA[%s]]></MsgType>
                                     <Content><![CDATA[%s]]></Content>
                                    <FuncFlag>0</FuncFlag>
                            </xml>";
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                    echo $resultStr;
//                    fwrite($file, "-------------------content_o:--------------------".json_encode($content_o)."\r\n");
//                   if($content_o == 1){

                    // $this->_doLocation($postObj);
                    break;
                case"link";
                    //连接类型
                    $this->_doLink($postObj);
                    break;
            }
        }
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

    //获取关键字回复内容
    public function get_keyword($str)
    {
        if(empty($str)){
            echo "输入规则有无";die;
        }else{
            $con = file_get_contents('wxkeyword.txt');
            $arr = explode(',',$con);
            $con_arr = "";
            foreach($arr as $v)
            {
                $con_arr[]= explode("->",$v);//转二维数组
            }

            foreach($con_arr as $k=>$v){
                if($con_arr[$k][0] == $str){
                    return $con_arr[$k][1];
                }
            }
        }
    }

    //链接类型
    private  function _doLink($object)
    {
        $content = "你发送的是链接，标题为：".$object->Title."；内容为：".
            $object->Description."；链接地址为：".$object->Url;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //回复语音

    private function _doVoice($object)

    {
        $content = array("MediaId"=>$object->MediaId);
        $result = $this->transmitVoice($object, $content);
        return $result;

    }

    /**
     * 回复语音消息
     */
    private function transmitVoice($object)
    {
        if (isset($object->Recognition) && !empty($object->Recognition)){
            $content = "你刚才说的是：".$object->Recognition;
            $result = $this->transmitText($object, $content);
        }else{
            $content = array("MediaId"=>$object->MediaId);
            $result = $this->transmitVoice($object, $content);
        }
        return $result;
    }

    //视频类型
    private  function _doVideo($object)
    {
        $content = array("MediaId"=>$object->MediaId, "ThumbMediaId"=>$object->ThumbMediaId, "Title"=>"", "Description"=>"");
        $result = $this->transmitVideo($object, $content);
        return $result;
    }

    /**
     * 回复视频消息
     */
    private function transmitVideo($object, $videoArray)
    {
        $itemTpl = "<Video>
                        <MediaId><![CDATA[%s]]></MediaId>
                        <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
                        <Title><![CDATA[%s]]></Title>
                        <Description><![CDATA[%s]]></Description>
                    </Video>";

        $item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'],
            $videoArray['Title'], $videoArray['Description']);

        $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[video]]></MsgType>
                        $item_str
                     </xml>";

        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }


    //地理位置
    private  function _doLocation($object)
    {
        $content = "你发送的是位置，纬度为：".$object->Location_X."；经度为：".
            $object->Location_Y."；缩放级别为：".$object->Scale."；位置为：".$object->Label;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    /**
     * 回复文本消息
     */
    private function transmitText($object, $content)
    {
        $xmlTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime><![CDATA[%s]]></CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                    </xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }





    //图片内容回复
    private function _doImage($object)
    {
        $content = array("MediaId"=>$object->MediaId);
        $result = $this->transmitImage($object, $content);
        return $result;
    }

    //回复图片消息
    private function transmitImage($object, $imageArray)
    {
        $itemTpl = "<Image>
                    <MediaId><![CDATA[%s]]></MediaId>
                    </Image>";
        $item_str = sprintf($itemTpl, $imageArray['MediaId']);
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[image]]></MsgType>
                    $item_str
                    </xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time());
        echo $result;
    }

    //关键字回复

    private  function _doText($postObj)
    {
        $fromUsername = $postObj->FromUserName;//哪个用户
        $toUsername = $postObj->ToUserName;//给平台
        $keyword = trim($postObj->Content);//用户发送的内容
        $time = time();
        //$textTpl 消息模板
        $textTpl = "<xml>

                    <ToUserName><![CDATA[%s]]></ToUserName>

                    <FromUserName><![CDATA[%s]]></FromUserName>

                    <CreateTime>%s</CreateTime>

                    <MsgType><![CDATA[%s]]></MsgType>

                    <Content><![CDATA[%s]]></Content>

                    <FuncFlag>0</FuncFlag>

                    </xml>";

        //如果用户发送的消息内容不为空
        if(!empty( $keyword ))
        {
            //根据用户输入的内容，返回想对应的内容
            // $con = $this->get_keyword($keyword);
            // if($con){
            //     $contentStr = $con;
            // }else{
            //     $contentStr = "你输入的规则有误，请仔细检查";
            // }
            if($keyword == 1){
                $contentStr = "同城打车";
            }elseif($keyword == 2){
                $contentStr = "新闻介绍";
            }else if($keyword == 3){    //配置小程序链接
                $contentStr = '<a data-miniprogram-appid="wxfaa1ea1ef2c2be3f" data-miniprogram-path="/pages/index/index" href="">测试</a>';
            }else{
                $contentStr = "你输入的规则有误，请仔细检查";
            }
            $msgType = "text";
            //$contentStr = "微信接入开发第一天";
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);//讲内容序列化输出
            echo $resultStr;
        }else{
            echo "Input something...";
        }
    }

    //用户关注事件
    public function _doEvent($postObj)
    {
        $fromUsername = $postObj->FromUserName;//哪个用户
        $toUsername = $postObj->ToUserName;//给平台
        $time = time();
        //$textTpl 消息模板
        $textTpl = "<xml>

                    <ToUserName><![CDATA[%s]]></ToUserName>

                    <FromUserName><![CDATA[%s]]></FromUserName>

                    <CreateTime>%s</CreateTime>

                    <MsgType><![CDATA[%s]]></MsgType>

                    <Content><![CDATA[%s]]></Content>

                    <FuncFlag>0</FuncFlag>

                    </xml>";
        switch($postObj->Event)
        {
            case"subscribe":
                $con = file_get_contents('wxtuisong.txt');
                $contentStr =  $con;
                break;
            case"SCAN":
                $contentStr = '欢迎再次回来';
                break;
            case "LOCATION":
//                $contentStr = "上传位置：纬度 ".$postObj->Latitude.";经度 ".$postObj->Longitude;
                break;
            case "scancode_waitmsg":
                $contentStr = "扫码带提示：类型 ".$postObj->ScanCodeInfo->ScanType." 结果：".$postObj->ScanCodeInfo->ScanResult;
                break;
            case "scancode_push":
                $contentStr = "扫码推事件";
                break;
            case "pic_sysphoto":
                $contentStr = "系统拍照";
                break;
            case "pic_weixin":
                $contentStr = "相册发图：数量 ".$postObj->SendPicsInfo->Count;
                break;
            case "pic_photo_or_album":
                $contentStr = "拍照或者相册：数量 ".$postObj->SendPicsInfo->Count;
                break;
            case "location_select":
                $contentStr = "发送位置：标签 ".$postObj->SendLocationInfo->Label;
                break;
            default:
                $contentStr = "receive a new event: ".$postObj->Event;
                break;
        }
        $msgType = "text";
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);//讲内容序列化输出
        echo $resultStr;
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = "yangyanghong";
        logger($token);
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        logger($tmpStr);
        logger($signature);
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
}

    //检测

    function traceHttp()
    {
        logger("\n\nREMOTE_ADDR:".$_SERVER["REMOTE_ADDR"].(strstr($_SERVER["REMOTE_ADDR"],'101.226')? " FROM WeiXin": "Unknown IP"));
        logger("QUERY_STRING:".$_SERVER["QUERY_STRING"]);
    }

    //日志

    function logger($log_content)
    {
        if(isset($_SERVER['HTTP_APPNAME'])){   //SAE

            sae_set_display_errors(false);

            sae_debug($log_content);

            sae_set_display_errors(true);

        }else{ //LOCAL

            $max_size = 500000;

            $log_filename = "log.xml";

            if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}

            file_put_contents($log_filename, date('Y-m-d H:i:s').$log_content."\r\n", FILE_APPEND);

        }

    }

    traceHttp();

    //https://blog.csdn.net/m0_37735713/article/details/80814984  参考地址

    $w = new WX();

    //echo $w->_getAccessToken();die;

    if (isset($_GET['echostr'])) {

        $w->valid();

    }else{

        $w->responseMsg();

    }

