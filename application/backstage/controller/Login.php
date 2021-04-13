<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/31
 * Time: 16:44
 */

namespace app\backstage\controller;

use think\Db;
class Login
{
    //验证码
    public function verification_code()
    {
        $config = [
            'length' => 4,
            'codeSet' => "0123456789"
        ];
        return captcha("", $config);
    }
    public function outLog(){
        $file_path = "/root/TCDCIMSERVER_jar/rbserver_logs/log_file.log";
        $handle = fopen($file_path, "r");//读取二进制文件时，需要将第二个参数设置成'rb'
        var_dump($handle);
        //通过filesize获得文件大小，将整个文件一下子读到一个字符串中
        $contents = fread($handle, filesize ($file_path));
        $contents =str_replace("\r\n","<br />",$contents );
        echo $contents;
        fclose($handle);
    }
    //登录
    public function login()
    {
//        $code = input('code');
//        if (captcha_check($code)||true) {
            $username = request()->param('username');
            $password = encrypt_salt(request()->param('password'));
            $data = [];
            $manager = Db::name('manager')->alias('m')->field('m.*,c.CompanyName,c.gps_parentId,c.gps_userId')->join('mx_company c','c.id = m.company_id','left')->where(['m.username' => $username, 'm.password' => $password])->find();
            if ($manager) {
                session('manager_id', $manager['id']);
                //权限
                $data = $manager;
                $data['city_id'] = (int)$manager['city_id'];
                $data['company_id'] = (int)$manager['company_id'];
                $rules = Db::name('authgroup')->where(['id' => $manager['group_id']])->value('rules');

                $permission = Db::name('permission')->where('id','in',$rules)->select();
                $data['row'] = $this->get_tree($permission);
                return ['code' => APICODE_SUCCESS, 'msg' => '登录成功', 'data' => $data];
            } else {
                $managers = Db::name('manager')->where(['username' => $username])->find();
                if ($managers) {
                    return ['code' => 400, 'msg' => '密码错误'];
                } else {
                    return ['code' => 400, 'msg' => '用户不存在'];
                }
            }
//        } else {
//            return ['code' => 400, 'msg' => '验证码错误'];
//        }
    }

    public function loginasother()
    {
        $userid = input("userid");
        $otherUserId = input("otherUserId");
        $token = input("token");
        $manager = Db::name('manager')->where(['id' => $userid, 'password' => $token])->find();
        if ($manager) {
            //todo 判断当前用户是否拥有模拟登陆权限
            $otherUser= Db::name('manager')->alias('m')->field('m.*,c.CompanyName,c.gps_parentId,c.gps_userId')->join('mx_company c','c.id = m.company_id','left')->where(['m.id' => $otherUserId])->find();
            session('manager_id', $otherUser['id']);
            $data = $otherUser;
            $rules = Db::name('authgroup')->where(['id' => $otherUser['group_id']])->value('rules');
            $permission = Db::name('permission')->where('id','in',$rules)->select();
            $data['row'] = $this->get_tree($permission);

            return ['code' => APICODE_SUCCESS, 'msg' => '登录成功', 'data' => $data];
        } else {
            return ['code' => 400, 'msg' => '认证失败，请重试'];
        }
    }

    //递归
    public function get_tree($data){
        $items = array();
        foreach ($data as $key=>$value){
            $items[$value['id']] = $value ;
        }
        $tree = array();
        foreach ($items as $k=>$v){
            if(isset($items[$v['superior']])){
                $items[$v['superior']]['son'][] = &$items[$k];
            }else{
                $tree[] = &$items[$k];
            }
        }
        return $tree ;
    }
}