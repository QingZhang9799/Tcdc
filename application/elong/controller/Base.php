<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 19-2-26
 * Time: 上午10:53
 */

namespace app\partner\controller;

use think\Controller;
use think\Session;
use think\Request;


class Base extends Controller
{
    public function getSign($data, $signKey = "")
    {
        $data["sign_key"] = $signKey;
        ksort($data);
        $string = "";
        foreach ($data as $key => $value) {
            if ($value != "") {
                $string = $string . $key . $value;
            }
        }
//        $file = fopen('./log.txt', 'a+');
//        fwrite($file, "-------------------源数据 :--------------------" .$string. "\r\n");
        return sha1($string);
    }

    public function filterFilter($array)
    {
        return array_filter($array, function ($var) {
            return !is_null($var);
        });
    }

    public function checkRequire($require, $param)
    {
        return count(array_intersect($require, array_keys($param))) == count($require);
    }

    public function pageReturn($db, $params)
    {
        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;
        $sum = $db->where(self::filterFilter($params))->count();
        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" => $db->where(self::filterFilter($params))->page($pageNum, $pageSize)
//                ->fetchSql(true)
                ->select()
        ];
    }

    private static function getPrivateKey()
    {
        $abs_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'rsa_private_key.pem';
        $content = file_get_contents($abs_path);
        $private_key = openssl_pkey_get_private($content);
        echo "私钥:" . $private_key;
        return $private_key;
    }

    /**
     * 获取公钥
     * @return bool|resource
     */
    private static function getPublicKey()
    {
        $abs_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'rsa_public_key.pem';
        $content = file_get_contents($abs_path);
        $public_key = openssl_pkey_get_public($content);
        echo "公钥:" . $public_key;
        return $public_key;
    }

    /**
     * 私钥加密
     * @param string $data
     * @return null|string
     */
    public static function privEncrypt($data = '')
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_private_encrypt($data, $encrypted, self::getPrivateKey()) ? base64_encode($encrypted) : null;
    }

    /**
     * 公钥加密
     * @param string $data
     * @return null|string
     */
    public static function publicEncrypt($data = '')
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_public_encrypt($data, $encrypted, self::getPublicKey()) ? base64_encode($encrypted) : null;
    }

    /**
     * 私钥解密
     * @param string $encrypted
     * @return null
     */
    public static function privDecrypt($encrypted = '')
    {
        if (!is_string($encrypted)) {
            return null;
        }
        return (openssl_private_decrypt(base64_decode($encrypted), $decrypted, self::getPrivateKey())) ? $decrypted : null;
    }

    /**
     * 公钥解密
     * @param string $encrypted
     * @return null
     */
    public static function publicDecrypt($encrypted = '')
    {
        if (!is_string($encrypted)) {
            return null;
        }
        return (openssl_public_decrypt(base64_decode($encrypted), $decrypted, self::getPublicKey())) ? $decrypted : null;
    }

    private static function getPrivateKey1()
    {
        $abs_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'rsa_private_key1.pem';
        $content = file_get_contents($abs_path);
        $private_key = openssl_pkey_get_private($content);
        return $private_key;
    }

    /**
     * 获取公钥
     * @return bool|resource
     */
    private static function getPublicKey1()
    {
        $abs_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'rsa_public_key1.pem';
        $content = file_get_contents($abs_path);
        $public_key = openssl_pkey_get_public($content);
        return $public_key;
    }

    /**
     * 私钥加密
     * @param string $data
     * @return null|string
     */
    public static function privEncrypt1($data = '')
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_private_encrypt($data, $encrypted, self::getPrivateKey1()) ? base64_encode($encrypted) : null;
    }

    /**
     * 公钥加密
     * @param string $data
     * @return null|string
     */
    public static function publicEncrypt1($data = '')
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_public_encrypt($data, $encrypted, self::getPublicKey1()) ? base64_encode($encrypted) : null;
    }

    /**
     * 私钥解密
     * @param string $encrypted
     * @return null
     */
    public static function privDecrypt1($encrypted = '')
    {
        if (!is_string($encrypted)) {
            return null;
        }
        return (openssl_private_decrypt(base64_decode($encrypted), $decrypted, self::getPrivateKey1())) ? $decrypted : null;
    }

    /**
     * 公钥解密
     * @param string $encrypted
     * @return null
     */
    public static function publicDecrypt1($encrypted = '')
    {
        if (!is_string($encrypted)) {
            return null;
        }
        return (openssl_public_decrypt(base64_decode($encrypted), $decrypted, self::getPublicKey1())) ? $decrypted : null;
    }
}