<?php

namespace app\alapi\controller;

use think\Controller;
require ALI_PATH.'AopClient.php';
require ALI_PATH.'request/AlipaySystemOauthTokenRequest.php';
require ALI_PATH.'request/AlipayUserInfoShareRequest.php';


class Aliios extends Controller{
    public static $appid = '2021001159670172'; //appid
    public static $pub_key = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0gi1dIALpii12mVaBIVZdzPuIOZJ0H6CSZsFo/TyfOY5BpXCQwRLJDbHtitiy5mjw99o1IYqvSdIdRY42sTGr9j+7IxLkqC+l9DNsBa8DumFP4LxDCKI+c8OwAafk/HxgB7NL1IvbJSugcv7hD+uzoxZ1Lx6QN2B7BKbgIIDvT1/3aY66VSvVJJOnte9mpmrSTFC6P0JppsD12v7i9BamT29idZbf89MSgufY4cOZgooMkjlZmAW3eg+CkoZ5J/0FAh0cB1i8dxEWvvbweH2Bk5c+MxxZS9GIxT6IGvIexXnVSa28kYm2Abc+P/DUPYET9g0wSOSz/PCT8ihNxC+DQIDAQAB';//应用公钥
    public static $prikey = 'MIIEowIBAAKCAQEA0gi1dIALpii12mVaBIVZdzPuIOZJ0H6CSZsFo/TyfOY5BpXCQwRLJDbHtitiy5mjw99o1IYqvSdIdRY42sTGr9j+7IxLkqC+l9DNsBa8DumFP4LxDCKI+c8OwAafk/HxgB7NL1IvbJSugcv7hD+uzoxZ1Lx6QN2B7BKbgIIDvT1/3aY66VSvVJJOnte9mpmrSTFC6P0JppsD12v7i9BamT29idZbf89MSgufY4cOZgooMkjlZmAW3eg+CkoZ5J/0FAh0cB1i8dxEWvvbweH2Bk5c+MxxZS9GIxT6IGvIexXnVSa28kYm2Abc+P/DUPYET9g0wSOSz/PCT8ihNxC+DQIDAQABAoIBAENOuyesaQ9EeJKWbDSKr1L990/fvMPt5r7DyRjzxEm2VYwArhJf69ydGX8NhEmO4OZCUAvbOxMG5bdv+aRR7wInXfpcM1O345wvM9s8TePRffwOcETdRFwZuLZc6QK2RBg0xrhldAEt3IaH4gBNkC1s2NTN2bezxJDsnZyfDae02wflj0RvdyxGngLndXLCOsDlBnWYtZ2FlYXBZXX4DxSavCBF1ZS0rQWHyYmJzQ1j/Qk3yK2w/eRP4V574YW5yHtpQqMkI3h6L6bcuN2KoHjE8jV/StranYwYZ68+8eG28z+o0ECF6NbvlPqXaxvu7rmNCEv9Vv13jDX1rH4V8BkCgYEA6UMyXh+dPNaSe6ndImoP3f3hb90jPrW5P9blN+ZBNiJkRaGTFYFCXL0jB1KUD0T2C3zyRclwagPM8H5jOSoKEJJlGJ7B4bNNU5VrCxI6t1JeiBNoILG4i2x9IzrCpo08uMi24HzLjIN4bBDByJcFfnjAzAgi7MXDL4NCPhG3qVMCgYEA5oHfKboa9zAvbpiQ5Wt9X/E8R9Y0Gc2RKDsMXmK4AzpkiiFVabPTr5QQ5ztaCbHsd6A9qTl/8/kR/oGsBxsJLDaalO6aMwF+e73VRhDn5Zs5WHyLojis0hVtNDLJeB+3hw9Zb1ItO5voU3rmK9UxwIOABQanJIfa4u//TL6vLx8CgYAv2b0HWezjghDilWHroV5H58DLNc35G0Y5NlgnM3DFLiDrt814Z9+5LoN1CReeWkMu8B6y+jO5S7ZKz2KDY4BVDfL3LfoP1rxSHSCsUL0Cxj7mIzUFH+//ie3RwEgV6ns+XM5HFtKarI2TfYyDHZfe7d5+/FxNvfPgV0jLes72SQKBgBFPy6zcl03dRpKtzqQMUJw2B+r1QXB2qeI1nRYxn9ROPGLLYhjQMqPLIQHcyURVIodRd5AQC3YNTLaqknruIuA5MZ7h1J7kC9XLSgs7Fc9+uu3UXMBQNVqJ1WvILK1i5Gu2UWc06sTBTs+GOWctWdE1jxXRjBgIQ/4rPCdNYubNAoGBANorO40pm/KC/iITNO2XP/lhFiSptf1+xV6uy+LRLvvK/CHdG+mFk+pv8nUICOTX43rdsaHGEL8Kxh7UX1NLqz7dDAlhQbR5tDWY9BrbvLhNibTQorKqUl/2fgZItG3Y86LRs+xZ9reKXfkyiOmWgyAW9dg4L1+iMtwJJKPeT4bI'; //应用私钥
    public static $alipubkey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqiwL4RcVm+7TYTsExs+pKBuO86ysD04Xt0z0kkCNZaEY+Yz1eZ3ep16tmu7+gHBHQQzhC4V4RSdNSoTpBNvrv3bpW2Z4KrvwKFKHSBGq0Hg70e2mladXcjp2HEkzANvO5uPSkE5DhvQXPootMX6tqngeQVACwXD7v7Mjg5TOU/5nTb+UTOMt4XdfscsVLDHqNRDc2tdk3wNwJ4pHSMYaEtphNVb5/0G8DKdMYIYRIBKDixy2Xdyg7WDShDELspW/4bRMa3TShIUD2OVGlIFgcjS1SYixtnUf72SxvaxqmHbsixRsn/e5tJQXrSSzVrYYff14UIyryR4oYNutMhekAQIDAQAB';//支付宝公钥

    public function alilogin()
    {
        /**
         * 该方法执行后支付宝会调用你自己定义的回调
         */
        $appid = self::$appid;
        $state = md5(uniqid(rand(), TRUE));
        $nurl = 'http://php.51jjcx.com/alapi/ali/login'; //回调地址
        //                  模块  控制器  回调方法
        $url = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id='.$appid.'&scope=auth_user&redirect_uri='.$nurl.'&state='.$state;
        echo("<script> top.location.href='" . $url . "'</script>");
    }

    //回调代码
    //此方法是你在阿里支付宝配置的回调地址
    public function login()
    {
        //获取授权token
        $authcode = input('auth_code');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = self::$appid;
        $aop->rsaPrivateKey = self::$prikey;
        $aop->alipayrsaPublicKey=self::$alipubkey;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='utf-8';
        $aop->format='json';
        $request = new \AlipaySystemOauthTokenRequest ();
        $request->setGrantType("authorization_code");
        $request->setCode($authcode);
        $result = $aop->execute ( $request);
        // var_dump($result->alipay_system_oauth_token_response);die;
        if(isset($result->alipay_system_oauth_token_response->access_token))
        {
            //获取会员信息
            $requests = new \AlipayUserInfoShareRequest ();
            $results = $aop->execute ( $requests , $result->alipay_system_oauth_token_response->access_token );
            //$results 返回数组是会员信息，自行打印查看

            if($results->alipay_user_info_share_response->code == 10000)
            {

                $data = Db::name('user')->where('id',$result->alipay_system_oauth_token_response->user_id)->find();
                if(!empty($data))    //存在，返回id
                {
                    $ini['portrait'] = $results->alipay_user_info_share_response->avatar;
                    $city = $results->alipay_user_info_share_response->city;
                    $city_id = Db::name('cn_city')->where(['name'=>$city])->value('id');
                    $ini['nickname'] = $data->nick_name = $results->alipay_user_info_share_response->nick_name;
                    $ini['city_id'] = $city_id ;
                    $ini['PassengerGender'] = $results->alipay_user_info_share_response->gender;

                    $user_id = Db::name('user')->insertGetId($ini);
                }else{
                    $user_id = $result->alipay_system_oauth_token_response->user_id ;
                }
                session('user_id',$user_id);

                return ['msg'=>'登录成功','code'=>200,'user_id'=>$user_id];
            }else{
                return ['msg'=>'登录异常','code'=>400];
            }
        }else{
            return ['msg'=>'登录异常','code'=>400];
        }
        //object(stdClass)#43 (6) { ["access_token"]=> string(40) "authusrBfc3814381c2b49c096b672ffef3f7X67" ["alipay_user_id"]=> string(32) "20881040009279002140758050319167" ["expires_in"]=> int(1296000) ["re_expires_in"]=> int(2592000) ["refresh_token"]=> string(40) "authusrB2f29604ff780444dbb04b3303e571B67" ["user_id"]=> string(16) "2088512270592674" }
    }
}
