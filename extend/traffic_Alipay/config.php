<?php



$config = array(

    //应用ID,您的APPID。

    'app_id' => "2021001193654498",


    //商户私钥，您的原始格式RSA私钥

    'merchant_private_key' => "MIIEpQIBAAKCAQEAphYpDkz/SSynwNG6XUL1YlzaaSeyOCcB7c/H8CpeNqGLiyptdlI88QS9etpJ0ci8DBXD4lzvo+TtMl1gSNY3186uvXeGvflgFFtX6ajwIvjgOjxpPrpTlKRse3RdhLH/U0nS/H0DYEEtqrgz6/6PCVYNiAbAer/saTMh9CiYK0aa4F32wq+aLbbii14Ncrea03u6Et9sLgnisQM8q1pOWTRE9dwWnnvvd5907Hg14/3fohF0z8mm9T7BWay6++gdFNi4cmE2pUAICNBz5RP4qJINcrzzmCyluz1bZBEUStaKPqbDG7dTHueFqL9FswC6hHE3pjwBjsUPmY6hzL2lHwIDAQABAoIBAQCNwVxJWG6LhhGoAVmPQBcwXRANsFPsmV6MG0wLMB45gqgXn57N3mMlU2Zl9OoMo8fciLcn/SqMOFg7JHeJs0z2ZPG/xMS8YJwgw9XFGOvc7Y50Jhut7lpoA+6TcD5hg4rpC5mI5yp6fSb9DztBsYNj9I6YCys9mZGuOHZCbmNyivA8w0OLxguJT0YEhTPkUKwbNwbQOKtIzjhfJURuMD69/OpGkUc9rL0dd/VNR7K2Xr58JEtjq/7wT6SOh6oHneZvSnTMiot1Ofx3k9YnZ1sAGpZO24nzo6a0BmOVFmCbiBtUECr4j/JtoGV0POO9pWrzQmFKbhL8lmWL3UQcwSABAoGBANiHtJ4+R74ghoOPdN0dp0mt9xQ3f5Bla9WLeYQiWDKJi4dhvEjUa91PNpqDBxjw6n5sygzlWjOrDGaoapo+mBat13xkTt+XJUVXh6Z8zYFrviQBt/a/wC/n/Z4x5KAD7bDrd6qy1Xt3fj/7zkCo7GEQyQOVs+grE+6BPfjP069/AoGBAMRchsgLrzjVHwT4ozgM/Oj2yQg9gBlJhp0MgVtNTqT4+pHrbj1RcMD54zO5a0FHWlBjsRxPPAiK3Lrrox0nmetlBgx/uOTSnqI++fKg0UrUA9iOX/fL6bQbGPemxyfkmYNf323l+fT0pvzwvRDH1z04SFQ5wta0WDj03E2g6tphAoGBAMFPXkwMVCaEiTK5B19E0w3vdu+goI08TqpGK8VwmAb+TwgdlGf85ROeXaRSKCr3IpKd80DSHdaU9axM3Wc5TLSqnP/b2aK6ILcobt2O/DV4CDfDJQbwp9bdKcpqxq6o8zKI9bv6jqb8xkS/PKLzbJ03zA4cP5Kdqty6m6YffOBnAoGACPbwcFGYPk/8io2PZg+xvDEIHIgyQPVKYAEiJrjwzjdPuTm2XrZJH4ZJCSN98gz/4ouqmlBDvWAZk68OU1ZrgIOsMwXhuxCijWWyo5ET/QaQ5mIZn4Z/tOlHyoaisP+OwqCt4qaNMtG4jfOvrgRxnynio3W/n228WV1UcXbXQgECgYEAs4Bg5iJSt1fu/88fzChq5gWmDbZlauANO/m5whW9U9hqzZRZzU39594yFBEhCCvJWU3upOMLAz/b12QkQ7yUXX97xFLAlK5c9nzpOdiilGiNCfPuh2uVLoLqJLhD1gG2XPhuDUPbKijwGKAtijop7S3+Ri4W4beGG2Ir6jM5Yxo=",  //异步通知地址



    'notify_url' => 'https://php.51jjcx.com/passengertraffic/userpay/alipay',



    //同步跳转

    'return_url' => '',



    //编码格式

    'charset' => "UTF-8",



    //签名方式

    'sign_type' => "RSA2",



    //支付宝网关 https://openapi.alipay.com/gateway.do

    'gatewayUrl' => "https://openapi.alipay.com/gateway.do",


    //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
    //'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApl80sHcUckYpNbHgY+zBtrd5bA9ffbxgzxlvK8XChAvJG1xjFQzL8m3pFZUCKzDEStNLP+2V+7t1G3rgsMlE76xw/kmK2qV9VlhbC/IrouknpID3qGIddfC+0fXBGZ8gfms/GQ2H3YhykzxMblsC/uR0+KIxFocxoqxio+8o7lBp2PwPzZ1YQF9Ed8W2jg1E+lIZ1oc4iapLAn3ctdIbsdeTnfaJzhm/U9KJnjW2mYQMuS5q/82vb6xlFAhiYuIGfDXotcVidrgNU6QqfYdUObZj3AGTigiLhAjTHvz7+L3f20fyLMj2lFiYVkkGuYA43GMdvyULJDb44bBRiGv2mQIDAQAB",
    'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAilI4TYGzgX3L3YVhqTznDIrMaism/BokwAdgrzJtTyNqnnMkfcdMWEyUadSNImzKfkHuq7vZ1F3SsZufmFehuxe2moTH+O9F04pwtbC/Rs/Rd+e6MPgHhqaR09BFmzFRHO28aj7D6ry/uopBashGSrMS8eL+eRGBvYLM4sL7vhBIiLpGWBrf7miEk6YNI02WCrkD5ctPnB6kgTSwbd+y+kyX3bnUD+qKvbuX5IODpWLrYgDUNul8IkuUqe4dGklc5Q0ajJtBgoRT/JnRr5KvF68YLGyySKafnMgvzmwt0VM8H5BpkirQDhiBatRtwAc90cZTqYQUby2L8nfOMZE4dQIDAQAB"



);



// halt($config);

$GLOBALS['config'] = $config;