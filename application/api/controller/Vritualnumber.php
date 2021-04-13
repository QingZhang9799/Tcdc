<?php

namespace app\api\controller;
class Vritualnumber extends Base
{
    public function getPhoneNumberByOrderId($order_id)
    {
//        $order_id = input("order_id");
        $file = fopen('./log.txt', 'a+');
        fwrite($file, "-------------------虚拟号进来了:--------------------"  . "\r\n");
        fwrite($file, "-------------------order_id:--------------------" . $order_id . "\r\n");
        $orderInfo = db("order")->where(["id" => $order_id, "status" => ["in", "2,3,4,7,10,11,12"]])->find();
        if ($orderInfo) {
            $a = $orderInfo["conducteur_phone"];
            $b = $orderInfo["user_phone"];
            $hasBind = db("vritual_number_bind")->where(["a|b" => $a, "b|a" => $b])->find();
            if ($hasBind) {
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "虚拟号码已虚拟",
                    "data" => [
                        "conducteur_phone" => $hasBind["x"],
                        "user_phone" => $hasBind["x"]
                    ]
                ];
            } else {
                $sql = "(select number from mx_vritual_number WHERE number not IN (select x from mx_vritual_number_bind WHERE a='$a' or a='$b' or b='$a' or b='$b') LIMIT 1)";
                $x = db()->table($sql . "a")->value("number");
                if ($x) {
                    fwrite($file, "-------------------x:--------------------" . $x . "\r\n");
                    $bind_info = $this->bindPhoneMubner($a, $x, $b);
                    if ($bind_info["code"] == 0) {
                        db("vritual_number_bind")->insert(["a" => $a, "x" => $x, "b" => $b]);
                        return [
                            "code" => APICODE_SUCCESS,
                            "msg" => "判定需虚拟号码成功",
                            "data" => [
                                "conducteur_phone" => $x,
                                "user_phone" => $x
                            ]
                        ];
                    } else {
                        return [
                            "code" => APICODE_SUCCESS,
                            "msg" => "虚拟号码绑定失败，不进行虚拟",
                            "error" => $bind_info,
                            "data" => [
                                "conducteur_phone" => $a,
                                "user_phone" => $b
                            ]
                        ];
                    }
                } else {
                    return [
                        "code" => APICODE_SUCCESS,
                        "msg" => "虚拟号码已经全部占用，不进行虚拟",
                        "data" => [
                            "conducteur_phone" => $a,
                            "user_phone" => $b
                        ]
                    ];
                }
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单不存在或状态不正确"
            ];
        }
    }

    public function releasePhoneNumberByOrderId($order_id)
    {
//        $order_id = input("order_id");
        $orderInfo = db("order")->where(["id" => $order_id])->find();
        if ($orderInfo) {
            $a = $orderInfo["conducteur_phone"];
            $b = $orderInfo["user_phone"];
            $hasBind = db("vritual_number_bind")->where(["a" => $a, "b" => $b])->find();
            if ($hasBind) {
                $x = $hasBind["x"];
                $bind_info = $this->unbindAXB($a, $x, $b);
                if ($bind_info["code"] == 0) {
                    db("vritual_number_bind")->delete($hasBind["id"]);
                    return [
                        "code" => APICODE_SUCCESS,
                        "msg" => "虚拟号码解绑成功",
                        "data" => $bind_info
                    ];
                }
            } else {
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "虚拟号码未虚拟无需解绑",
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "订单不存在"
            ];
        }
    }

    public function bindPhoneMubner($a, $x, $b)
    {
        $data = [];
        $data["telA"] = $a;
        $data["telX"] = $x;
        $data["telB"] = $b;
        $data = json_encode($data);
        $bindInfo = $this->request("http://test.api.hox.net.cn/v1/axb/bind", "POST", $data);
        return json_decode($bindInfo, true);
    }

    private function getBindInfo()
    {
        $data = [];
        input("?a") ? $data["telA"] = input("a") : "";
        input("?x") ? $data["telX"] = input("x") : "";
        input("?b") ? $data["telB"] = input("b") : "";
        $data = json_encode($data);
        $bindInfo = $this->request("http://test.api.hox.net.cn/v1/axb/bindInfo", "GET", $data);
        echo $bindInfo;
    }

    public function unbindAXB($a, $x, $b)
    {
        $data = ["isManual" => 1];
        $data["telA"] = $a;
        $data["telX"] = $x;
        $data["telB"] = $b;
        $data = json_encode($data);
        $bindInfo = $this->request("http://test.api.hox.net.cn/v1/axb/unbind", "DELETE", $data);
        return json_decode($bindInfo, true);
    }

    function request($url = "", $type = "POST", $param = "")
    {
        $postUrl = $url;
        $curlPost = $param;
//        $appId = "66ce4ef9f3af8566df38d5842cd2b67c";
        $appId = "b346904ddd5f0d4af3298286ffa6daf3";
//        $appSecret = "7716939F8FA0FDB2F5B2974D8045BE14";
        $appSecret = "4CD620F487BD15CBCD18475C472E0C37";
        date_default_timezone_set('UTC');
        $timestamp = new \DateTime();
        $created = $timestamp->format("Y-m-d\TH:i:s\Z");
        $PasswordDigest = base64_encode(hash("sha256", "aabbcc" . $created . $appSecret, true));
        $header = ['X-WSSE:UsernameToken ' . 'Username="' . $appId . '",PasswordDigest="' . $PasswordDigest . '",Nonce="aabbcc",Created="' . $created . '"', 'Content-Type: application/json; charset=utf-8',];
//        var_dump($header);
        $ch = curl_init(); // 初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); // 抓取指定网页
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch); // 运行curl
        curl_close($ch);
        return $data;
    }
}