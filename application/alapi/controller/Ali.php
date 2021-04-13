<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 20-4-23
 * Time: 下午2:26
 */

namespace app\alapi\controller;

use think\Controller;
use think\Request;
use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Util\Signer;
use think\Db;
use app\user\controller\Marketing;

function getOptions()
{
    $options = new Config();
    $options->protocol = 'http';
    $options->gatewayHost = 'openapi.alipay.com';
    $options->signType = 'RSA2';

    $options->appId = '2021001164600684';

    // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
    $options->merchantPrivateKey = 'MIIEpQIBAAKCAQEAphYpDkz/SSynwNG6XUL1YlzaaSeyOCcB7c/H8CpeNqGLiyptdlI88QS9etpJ0ci8DBXD4lzvo+TtMl1gSNY3186uvXeGvflgFFtX6ajwIvjgOjxpPrpTlKRse3RdhLH/U0nS/H0DYEEtqrgz6/6PCVYNiAbAer/saTMh9CiYK0aa4F32wq+aLbbii14Ncrea03u6Et9sLgnisQM8q1pOWTRE9dwWnnvvd5907Hg14/3fohF0z8mm9T7BWay6++gdFNi4cmE2pUAICNBz5RP4qJINcrzzmCyluz1bZBEUStaKPqbDG7dTHueFqL9FswC6hHE3pjwBjsUPmY6hzL2lHwIDAQABAoIBAQCNwVxJWG6LhhGoAVmPQBcwXRANsFPsmV6MG0wLMB45gqgXn57N3mMlU2Zl9OoMo8fciLcn/SqMOFg7JHeJs0z2ZPG/xMS8YJwgw9XFGOvc7Y50Jhut7lpoA+6TcD5hg4rpC5mI5yp6fSb9DztBsYNj9I6YCys9mZGuOHZCbmNyivA8w0OLxguJT0YEhTPkUKwbNwbQOKtIzjhfJURuMD69/OpGkUc9rL0dd/VNR7K2Xr58JEtjq/7wT6SOh6oHneZvSnTMiot1Ofx3k9YnZ1sAGpZO24nzo6a0BmOVFmCbiBtUECr4j/JtoGV0POO9pWrzQmFKbhL8lmWL3UQcwSABAoGBANiHtJ4+R74ghoOPdN0dp0mt9xQ3f5Bla9WLeYQiWDKJi4dhvEjUa91PNpqDBxjw6n5sygzlWjOrDGaoapo+mBat13xkTt+XJUVXh6Z8zYFrviQBt/a/wC/n/Z4x5KAD7bDrd6qy1Xt3fj/7zkCo7GEQyQOVs+grE+6BPfjP069/AoGBAMRchsgLrzjVHwT4ozgM/Oj2yQg9gBlJhp0MgVtNTqT4+pHrbj1RcMD54zO5a0FHWlBjsRxPPAiK3Lrrox0nmetlBgx/uOTSnqI++fKg0UrUA9iOX/fL6bQbGPemxyfkmYNf323l+fT0pvzwvRDH1z04SFQ5wta0WDj03E2g6tphAoGBAMFPXkwMVCaEiTK5B19E0w3vdu+goI08TqpGK8VwmAb+TwgdlGf85ROeXaRSKCr3IpKd80DSHdaU9axM3Wc5TLSqnP/b2aK6ILcobt2O/DV4CDfDJQbwp9bdKcpqxq6o8zKI9bv6jqb8xkS/PKLzbJ03zA4cP5Kdqty6m6YffOBnAoGACPbwcFGYPk/8io2PZg+xvDEIHIgyQPVKYAEiJrjwzjdPuTm2XrZJH4ZJCSN98gz/4ouqmlBDvWAZk68OU1ZrgIOsMwXhuxCijWWyo5ET/QaQ5mIZn4Z/tOlHyoaisP+OwqCt4qaNMtG4jfOvrgRxnynio3W/n228WV1UcXbXQgECgYEAs4Bg5iJSt1fu/88fzChq5gWmDbZlauANO/m5whW9U9hqzZRZzU39594yFBEhCCvJWU3upOMLAz/b12QkQ7yUXX97xFLAlK5c9nzpOdiilGiNCfPuh2uVLoLqJLhD1gG2XPhuDUPbKijwGKAtijop7S3+Ri4W4beGG2Ir6jM5Yxo=';
    //注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
    $options->alipayPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqiwL4RcVm+7TYTsExs+pKBuO86ysD04Xt0z0kkCNZaEY+Yz1eZ3ep16tmu7+gHBHQQzhC4V4RSdNSoTpBNvrv3bpW2Z4KrvwKFKHSBGq0Hg70e2mladXcjp2HEkzANvO5uPSkE5DhvQXPootMX6tqngeQVACwXD7v7Mjg5TOU/5nTb+UTOMt4XdfscsVLDHqNRDc2tdk3wNwJ4pHSMYaEtphNVb5/0G8DKdMYIYRIBKDixy2Xdyg7WDShDELspW/4bRMa3TShIUD2OVGlIFgcjS1SYixtnUf72SxvaxqmHbsixRsn/e5tJQXrSSzVrYYff14UIyryR4oYNutMhekAQIDAQAB';
    //可设置异步通知接收服务地址（可选）
    $options->notifyUrl = "<-- 请填写您的支付类接口异步通知接收服务地址，例如：https://www.test.com/callback -->";

    //可设置AES密钥，调用AES加解密相关接口时需要（可选）
//    $options->encryptKey = "<-- 请填写您的AES密钥，例如：aa4BtZ4tspm2wnXLb1ThQA== -->";
    return $options;
}

class Ali extends Controller
{
    public function __construct(Request $request = null)
    {

        Factory::setOptions(getOptions());
        parent::__construct($request);
    }

    public function alilogin()
    {
        $systemParams = [
            "apiname" => "com.alipay.account.auth",
            "app_id" => getOptions()->appId,
            "app_name" => "mc",
            "auth_type" => "AUTHACCOUNT",
            "biz_type" => "openservice",
            "method" => "alipay.open.auth.sdk.code.get",
            "pid" => "2088831059967521",
            "product_id" => "APP_FAST_LOGIN",
            "scope" => "kuaijie",
            "sign_type" => "RSA2",
            "target_id" => rand(1000000, 9999999),
        ];
        $singer=new Signer();
        $sign=$singer->getSignContent($systemParams);
        $res=$singer->sign($sign,getOptions()->merchantPrivateKey);
        $systemParams["sign"]=$res;
        $stringToBeSigned = "";
        $i = 0;
        foreach ($systemParams as $k => $v) {
            if ("@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
//        echo $stringToBeSigned;
        echo json_encode([
            "code" => APICODE_SUCCESS,
            "msg" => "成功",
            'data'=>$stringToBeSigned
        ]) ;
    }
    public function aliLoginCallBack(){
        $code=input("code");
        $result=Factory::base()->oauth()->getToken($code);
        var_dump($result);
        $systemParams = [
            "app_id" => getOptions()->appId,
            "app_name" => "mc",
            "charset" => "UTF-8",
            "format" => "json",
            "method" => "alipay.system.oauth.token",
            "sign_type" => "RSA2",
            "timestamp"=>date("Y-m-d H:i:s"),
            "version" => "1.0",
        ];
        $result=Factory::util()->generic()->execute("alipay.system.oauth.token",[
            "grant_type" => "authorization_code",
            "code" => $code
        ],[]);
        var_dump($result);
    }

    //回调代码
    //此方法是你在阿里支付宝配置的回调地址
    public function login()
    {
        require_once 'extend/Alipay/aop/AopClient.php';
        require_once 'extend/Alipay/aop/request/AlipaySystemOauthTokenRequest.php';
        require_once 'extend/Alipay/aop/request/AlipayUserInfoShareRequest.php';

        //获取授权token
        $authcode = input('auth_code');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = getOptions()->appId;
        $aop->rsaPrivateKey = getOptions()->merchantPrivateKey;
        $aop->alipayrsaPublicKey=getOptions()->alipayPublicKey;
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
            $flag = 0 ;

            if($results->alipay_user_info_share_response->code == 10000)
            {
                $data = Db::name('user')->where('ali_openid',$result->alipay_system_oauth_token_response->user_id)->find();
                if(empty($data))    //存在，返回id
                {
                    $ini['portrait'] = $results->alipay_user_info_share_response->avatar;
                    $city = $results->alipay_user_info_share_response->city;
                    $city_id = Db::name('cn_city')->where(['name'=>$city])->value('id');
                    $ini['nickname'] = $data->nick_name = $results->alipay_user_info_share_response->nick_name;
                    $ini['city_id'] = $city_id ;
                    $ini['PassengerGender'] = $results->alipay_user_info_share_response->gender;
                    $ini['ali_openid'] = $result->alipay_system_oauth_token_response->user_id ;
                    $ini['create_time'] = time() ;
                    $user_id = Db::name('user')->insertGetId($ini);
                    $flag = 2 ;

                    $m = new Marketing();
                    $active = $m->judgeActivity($user_id, $city_id, 1, '');
                    $active_active = 0;
                    $users = Db::name('user')->where(['id' =>$user_id ])->find() ;
                    if ($active) {
                        $active_active = 1;
                        $users["active_active"] = $active_active;
                    } else {
                        $users["active_active"] = $active_active;
                    }

                }else{
                    $user_id = $data['id'] ;
                    $users = Db::name('user')->where(['id' =>$user_id ])->find() ;
                }
                session('user_id',$user_id);

                return ['code'=> APICODE_SUCCESS, 'msg'=>'登录成功','data' => $users,'flag'=>$flag ];
            }else{
                return ['msg'=>'登录异常','code'=>APICODE_ERROR];
            }
        }else {
            return ['msg' => '登录异常', 'code' => APICODE_ERROR];
        }
    }
}
