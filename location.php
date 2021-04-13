<?php
/*
 * 获取微信用户发来的地理位置
 */
header("Content-Type: text/html; charset=utf-8");   //设置字符编码为utf-8

$wechatObj = new wechat_php();
$wechatObj->GetLocationMsg();

class wechat_php {
    public function GetLocationMsg() {
        //获取微信服务器POST请求中的数据
        $postStr = file_get_contents("php://input");

        if (!empty($postStr)) { //如果数据不为空
            $postObj = simplexml_load_string($postStr, "SimpleXMLElement", LIBXML_NOCDATA); //将XML数据装载到对象中
            $fromUsername = $postObj->FromUserName; //微信用户名
            $toUsername = $postObj->ToUserName; //开发者微信号
            $msgType = $postObj->MsgType;   //消息的类型
            $location_X = trim($postObj->Location_X); //地理位置维度
            $location_Y = trim($postObj->Location_Y);  //地理位置的经度
            $scale = trim($postObj->Scale); //地图缩放大小
            $label = trim($postObj->Label); //地图缩放大小
            $msgId = trim($postObj->MsgId); //消息id
            $time = time(); //回复消息的时间

            //回复消息的XML格式
            $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";
            if (strtolower($msgType) != "location") {
                $msgType = "text";  //回复消息的类型
                $contentStr = "我只接受地理位置消息!";  //回复的内容
            }
            else {
                $msgType = "text";  //回复消息的类型
                $contentStr = "Location_X:".$location_X."\n";   //位置的纬度
                $contentStr = $contentStr."Location_Y:".$location_Y."\n";   //位置的经度
                $contentStr = $contentStr."Scale:".$scale."\n"; //地图的缩放大小
                $contentStr = $contentStr."Label:".$label;  //地图的缩放大小
            }
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
            echo $resultStr;
        }
        else {
            echo "";
            exit;
        }
    }
}