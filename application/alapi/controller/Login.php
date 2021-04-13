<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 20-5-16
 * Time: 下午1:59
 */
namespace app\alapi\controller;

use think\Controller;

class Login extends Controller
{
    public function index()
    {
        include_once  "extend/Alipay/wappay/service/AlipayTradeService.php" ;
        include_once  "extend/Alipay/aop/AopClient.php";
        include_once  "extend/Alipay/config.php";

        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2021001153638408';
        $aop->rsaPrivateKey = 'MIIEpQIBAAKCAQEAphYpDkz/SSynwNG6XUL1YlzaaSeyOCcB7c/H8CpeNqGLiyptdlI88QS9etpJ0ci8DBXD4lzvo+TtMl1gSNY3186uvXeGvflgFFtX6ajwIvjgOjxpPrpTlKRse3RdhLH/U0nS/H0DYEEtqrgz6/6PCVYNiAbAer/saTMh9CiYK0aa4F32wq+aLbbii14Ncrea03u6Et9sLgnisQM8q1pOWTRE9dwWnnvvd5907Hg14/3fohF0z8mm9T7BWay6++gdFNi4cmE2pUAICNBz5RP4qJINcrzzmCyluz1bZBEUStaKPqbDG7dTHueFqL9FswC6hHE3pjwBjsUPmY6hzL2lHwIDAQABAoIBAQCNwVxJWG6LhhGoAVmPQBcwXRANsFPsmV6MG0wLMB45gqgXn57N3mMlU2Zl9OoMo8fciLcn/SqMOFg7JHeJs0z2ZPG/xMS8YJwgw9XFGOvc7Y50Jhut7lpoA+6TcD5hg4rpC5mI5yp6fSb9DztBsYNj9I6YCys9mZGuOHZCbmNyivA8w0OLxguJT0YEhTPkUKwbNwbQOKtIzjhfJURuMD69/OpGkUc9rL0dd/VNR7K2Xr58JEtjq/7wT6SOh6oHneZvSnTMiot1Ofx3k9YnZ1sAGpZO24nzo6a0BmOVFmCbiBtUECr4j/JtoGV0POO9pWrzQmFKbhL8lmWL3UQcwSABAoGBANiHtJ4+R74ghoOPdN0dp0mt9xQ3f5Bla9WLeYQiWDKJi4dhvEjUa91PNpqDBxjw6n5sygzlWjOrDGaoapo+mBat13xkTt+XJUVXh6Z8zYFrviQBt/a/wC/n/Z4x5KAD7bDrd6qy1Xt3fj/7zkCo7GEQyQOVs+grE+6BPfjP069/AoGBAMRchsgLrzjVHwT4ozgM/Oj2yQg9gBlJhp0MgVtNTqT4+pHrbj1RcMD54zO5a0FHWlBjsRxPPAiK3Lrrox0nmetlBgx/uOTSnqI++fKg0UrUA9iOX/fL6bQbGPemxyfkmYNf323l+fT0pvzwvRDH1z04SFQ5wta0WDj03E2g6tphAoGBAMFPXkwMVCaEiTK5B19E0w3vdu+goI08TqpGK8VwmAb+TwgdlGf85ROeXaRSKCr3IpKd80DSHdaU9axM3Wc5TLSqnP/b2aK6ILcobt2O/DV4CDfDJQbwp9bdKcpqxq6o8zKI9bv6jqb8xkS/PKLzbJ03zA4cP5Kdqty6m6YffOBnAoGACPbwcFGYPk/8io2PZg+xvDEIHIgyQPVKYAEiJrjwzjdPuTm2XrZJH4ZJCSN98gz/4ouqmlBDvWAZk68OU1ZrgIOsMwXhuxCijWWyo5ET/QaQ5mIZn4Z/tOlHyoaisP+OwqCt4qaNMtG4jfOvrgRxnynio3W/n228WV1UcXbXQgECgYEAs4Bg5iJSt1fu/88fzChq5gWmDbZlauANO/m5whW9U9hqzZRZzU39594yFBEhCCvJWU3upOMLAz/b12QkQ7yUXX97xFLAlK5c9nzpOdiilGiNCfPuh2uVLoLqJLhD1gG2XPhuDUPbKijwGKAtijop7S3+Ri4W4beGG2Ir6jM5Yxo=';
        $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqiwL4RcVm+7TYTsExs+pKBuO86ysD04Xt0z0kkCNZaEY+Yz1eZ3ep16tmu7+gHBHQQzhC4V4RSdNSoTpBNvrv3bpW2Z4KrvwKFKHSBGq0Hg70e2mladXcjp2HEkzANvO5uPSkE5DhvQXPootMX6tqngeQVACwXD7v7Mjg5TOU/5nTb+UTOMt4XdfscsVLDHqNRDc2tdk3wNwJ4pHSMYaEtphNVb5/0G8DKdMYIYRIBKDixy2Xdyg7WDShDELspW/4bRMa3TShIUD2OVGlIFgcjS1SYixtnUf72SxvaxqmHbsixRsn/e5tJQXrSSzVrYYff14UIyryR4oYNutMhekAQIDAQAB';
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new \AlipayUserUserinfoShareRequest();
        $result = $aop->execute ( $request);
        //返回用户信息
        dump($result);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        //测试 返回状态值为10000
        //  dump($resultCode);
        if(!empty($resultCode)&&$resultCode == 10000){
            echo "成功";
        } else {
            echo "失败";
        }
    }
}
