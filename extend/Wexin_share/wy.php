<?php

class Wy{
    private $_appid;
    private $_appsecret;

    public function __construct($_appid, $_appsecret){
        $this->_appid = $_appid;
        $this->_appsecret = $_appsecret;
    }




    public function _getAccessToken(){ //获取Access Token

        $file = './accesstoken';	//设置Access Token的存放位置
//        if(file_exists($file)){
//            halt(1111111111);
//            $content = file_get_contents($file); //读取文档
//            $content = json_decode($content); //解析json数据
//            if(time() - filemtime($file) < $content->expires_in) //判断access token是否过期
//                return $content->access_token;
//        }
        $curl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->_appid.'&secret='.$this->_appsecret; //通过该 URL 获取Access Token
        $content = $this->_request($curl);  //发送请求

        file_put_contents($file, $content);//保存Access Token 到文件
        $content = json_decode($content); //解析json
        return $content->access_token;
    }

    public function _message($message){
        $curl = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$this->_getAccessToken();
        $content = json_decode($this->_request($curl,true,'POST',$message));//http请求方式：POST（请使用https协议）
        $file = fopen('./log.txt', 'a+');
        fwrite($file, "-------------------content:--------------------".json_encode($content)."\r\n");
        if($content->errcode == 0){
            // 正确时的返回JSON数据包如下：
            //  {"errcode":0,"errmsg":"ok"}
            return 1;
        }else{
            return 2;
        }
    }

    public function _createMenu($menu){
        $curl = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->_getAccessToken();
        $content = json_decode($this->_request($curl,true,'POST',$menu));//http请求方式：POST（请使用https协议）
        if($content->errcode == 0){
            // 正确时的返回JSON数据包如下：
            //  {"errcode":0,"errmsg":"ok"}
            return 1;
        }else{
            return 2;
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

}