<?php
namespace app\api\controller;

class Wechat
{

    private $appId;
    private $appSecret;

    public function __construct($appId, $appSecret)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    /**
     * 获取正常调用票据access_token
     */
    public   function getAccessToken()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
        $res = json_decode($this->httpGet($url));

        $access_token = $res->access_token;
        // dump($access_token);die;
        return $access_token;
    }
    
      //添加客服
    public function addkf()
    {
        $url = 'https://api.weixin.qq.com/customservice/kfaccount/add?access_token='.$this->getAccessToken();

       $data = '{
         "kf_account" : "system@system",
         "nickname" : "同城客服"
        }';
        $res =  $this->curl_post($url,$data);
        return $res;
    }
    
    //发送消息
    public function sendServiceText($openid,$text)
    {
        // 不指定某个客服回复
        $json = '{
            "touser":"'.$openid.'",
            "msgtype":"text",
            "text":
             {
                "content":"'.$text.'"
             }   
        }' ;
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$this->getAccessToken();
        $res = $this->request_post($url,$json);
        $resArr = json_decode($res,true);
        if (isset($resArr['errcode']) && $resArr['errcode'] == 0) {
//            $this->logger("\r\n" . '发送成功');

            return true;
        }else{
            return false;
        }

    }

    public function curl_post($url , $data=array()){

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        // POST数据

        curl_setopt($ch, CURLOPT_POST, 1);

        // 把post的变量加上

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $output = curl_exec($ch);
        $file = fopen('./order.txt', 'a+');
        fwrite($file, "-------------------output:--------------------".json_encode($output)."\r\n");
        curl_close($ch);

        return $output;

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

    /**
     * @param  跳转地址
     * @param  授权作用域 默认为snsapi_base 不提示授权   snsapi_userinfo 提示用户授权
     */
    public function getCode($redirect_uri, $scope='snsapi_userinfo')
    {

        //halt($redirect_uri);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$this->appId&redirect_uri=$redirect_uri&response_type=code&scope=$scope&state=imaginiha#wechat_redirect";

        //appid公共号的唯一识别
        //redirect_uri授权后重定向的回调链接地址
        //response_type返回类型，请填写code
        //scope应用授权作用域，snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid），snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
        //state重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值，最多128字节
        //#wechat_redirect无论直接打开还是做页面302重定向时候，必须带此参数

        header("Location: $url");
    }
    /**
     * 微信开放平台 回调
     */
   public function getOpenCode($redirect_uri, $scope='snsapi_userinfo')
   {
       $url = "https://open.weixin.qq.com/connect/qrconnect?appid=$this->appId&redirect_uri=$redirect_uri&response_type=code&scope=$scope&state=STATE#wechat_redirect";

       header("Location: $url");
   }
//https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxe583df3a1b337ee9&redirect_uri=http://hljkjcyw.org.cn/wxapi/index/getUserInfo/&response_type=code&scope=snsapi_userinfo&state=imaginiha#wechat_redirect
    /**
     * 获取网页调用access_token,openid所在json
     */
    public  function getWebJson($code)
    {

        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$this->appId&secret=$this->appSecret&code=$code&grant_type=authorization_code";
       // halt($url);
        //$url返回的json如下
        /*
         {
               "access_token":"ACCESS_TOKEN",     //网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同
               "expires_in":7200,				//access_token接口调用凭证超时时间，单位（秒）
               "refresh_token":"REFRESH_TOKEN",//用户刷新access_token
               "openid":"OPENID",				//用户唯一标识
               "scope":"SCOPE",				//用户授权的作用域，使用逗号（,）分隔
               "unionid":"o6_bmasdasdsad6_2sgVt7hMZOPfL"   //当且仅当该公众号已获取用户的userinfo授权，并且该公众号已经绑定到微信开放平台帐号时，才会出现该字段
            }
        */
        $res = json_decode($this->httpGet($url),true);//json_decode 把JSON形式转化成php的变量
//        halt($res);
        return $res;
    }
    /**
     * 获取用户详细信息
     */
    public  function getDetail($code)
    {

        //https://api.weixin.qq.com/cgi-bin/user/info?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN
        $res = $this->getWebJson($code);

        $web_access_token = $res['access_token'];

        $openid = $res['openid'];
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=$web_access_token&openid=$openid&lang=zh_CN";
        /*{
           "openid":" OPENID",	//用户的唯一标识   28位字符
           " nickname":"NICKNAME",	//用户昵称
           "sex":"1",	//用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
           "province":"PROVINCE",	//用户个人资料填写的省份
           "city":"CITY",	//普通用户个人资料填写的城市
           "country":"COUNTRY",	//国家，如中国为CN
            "headimgurl":    "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46", 	//用户头像，最后一个数值代表正方形头像大小
            "privilege":[
            "PRIVILEGE1"
            "PRIVILEGE2"
            ],					//用户特权信息
            "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"   //只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段
        }*/

        $info = json_decode($this->httpGet($url));
        /* $data['name'] = $info->nickname;
         $data['image'] = $info->headimgurl;*/
        //print_r($info);exit;
        //stdClass Object ( [openid] => oDmfLwBtSBOHMmJdbPxgEwuVBqDk [nickname] => AS杨关宇 [sex] => 1 [language] => zh_CN [city] => 哈尔滨 [province] => 黑龙江 [country] => 中国 [headimgurl] => http://wx.qlogo.cn/mmopen/YP1VRDibb8Y5XfmrD1dgETLFz04K7YEOuianxL7u0bHErMrZ2WX2eZu7wBgxUcmwCIjo1OPEAd1QQF1hSDUlv6uJAgNTCqeJicp/0 [privilege] => Array ( ) )
        return $info;
    }


    /**
     * 获取关注公众号用户的所有人openid 与 next_openid
     *  返回 所有关注的用户openid，用逗号拼接成字符串 :
     */
    public function getUserList()
    {
        set_time_limit(0);//设置超时
        $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$this->getAccessToken();
        $res = $this->httpGet($url);
        $data = json_decode($res,true);
        $next_openid = $data['next_openid'];//

        $openid = $data['data']['openid'];//获取所有关注这的openid  返回数组
        $length = count($openid);//多少个用户
//        dump($openid);die;
        return $openid;
    }

    /**
     *请求微信服务器函数
     */
    public function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    // 发送小程序
    public function sendApp($openid,$title,$appid,$pagepath,$media_id){
        $json = '{
        "touser":"'.$openid.'",
        "msgtype":"miniprogrampage",
        "miniprogrampage":{
            "title":"'.$title.'",
            "appid":"'.$appid.'",
            "pagepath":"'.$pagepath.'",
            "thumb_media_id":"'.$media_id.'"
        }
    }';
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$this->getAccessToken();
        $rs = $this->curl_post($url,$json);
        $file = fopen('./order.txt', 'a+');
        fwrite($file, "-------------------rs:--------------------".json_encode($rs)."\r\n");
        return $rs;
    }

}