<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/7
 * Time: 13:29
 */

namespace app\partner\controller;
use app\backstage\controller\Gps;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Request;

class Payordernotify extends Base
{
    public function index()
    {
        $params = [
            "channel" => input('?channel') ? input('channel') : null,
            "timestamp" => input('?timestamp') ? input('timestamp') : null,
            "sign" => input('?sign') ? input('sign') : null,
            "mtOrderId" => input('?mtOrderId') ? input('mtOrderId') : null,
            "partnerOrderId" => input('?partnerOrderId') ? input('partnerOrderId') : null,
            "amount" => input('?amount') ? input('amount') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["channel"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //更新订单金额
        $ini['id'] = input('partnerOrderId') ;
        $ini['money'] = input('amount')/100 ;
        $ini['status'] = 6 ;
        $ini['pay_time'] = time() ;
        $res = Db::name('order')->update($ini);

        if($res > 0){
            $orders = Db::name('order')->where(['id' => input('partnerOrderId') ])->find();
            $this->companyMoney($orders['conducteur_id'], $orders['money'], $orders, $orders['discount_money']);
//            $this->appointment("取消订单",$orders['conducteur_id'],$orders['id'],11);
            //解绑虚拟号
            $vritualController = new \app\api\controller\Vritualnumber() ;
            $result = $vritualController->releasePhoneNumberByOrderId((int)input('partnerOrderId'));
        }
        return [
            'result' => 0,
            'message' => 'SUCCESS',
        ];
    }
    function appointment($title, $uid, $message,$type)
    {
        $url = 'https://api.jpush.cn/v3/push';
        $base64 = base64_encode("ba5d96c2e4c921507909fccf:bf358847e1cd3ed8a6b46dd0");
        $header = array(
            "Authorization:Basic $base64",
            "Content-Type:application/json"
        );
        $param=array("platform"=>"all","audience"=>array("tag"=>array("D_$uid")),"message"=>array("msg_content"=>$message.",".$type,"title"=>$title));
        $params=json_encode($param);
        $res = $this->request_post($url, $params, $header);
        $res_arr = json_decode($res, true);
    }
    // 极光推送提交
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

    //抽成
    function companyMoney($conducteur_id, $money, $order, $discount_money)
    {

        $company_id = Db::name("conducteur")->where(['id' => $conducteur_id])->value('company_id');  //公司id

        //在获取抽成规则
        $company_ratio = Db::name('company_ratio')->where(['company_id' => $company_id, 'business_id' => $order['business_id'], 'businesstype_id' => $order['business_type_id']])->find();

        //总公司抽成
        $parent_company = ($company_ratio['mt_parent_company_ratio'] / 100) * $money;
        //上级分公司抽成
        $superior_company = ($company_ratio['mt_filiale_company_ratio'] / 100) * $money;  //没有上级值 为 0
        //分公司结算金额
        $compamy_money = $money - (($company_ratio['mt_parent_company_ratio'] / 100) * $money) - (($company_ratio['mt_company_ratio'] / 100) * $money) - $discount_money + $order['surcharge'];
        //分公司利润
        $compamy_profit = ($company_ratio['mt_company_ratio'] / 100) * $money;
        //司机
        $chauffeur_money = $money - (($company_ratio['mt_parent_company_ratio'] / 100) * $money) - (($company_ratio['mt_filiale_company_ratio'] / 100) * $money) - (($company_ratio['mt_company_ratio'] / 100) * $money);

        //司机增加附加费
        $chauffeur_money = $chauffeur_money + $order['surcharge'];

        $inii = [];
        $inii['id'] = $order['id'];
        $inii['parent_company_money'] = $parent_company;
        $inii['superior_company_money'] = $superior_company;
        $inii['filiale_company_money'] = $compamy_profit;
        $inii['chauffeur_income_money'] = $chauffeur_money;
        $inii['filiale_company_settlement'] = $compamy_money;

        Db::name('order')->update($inii);

        //司机加余额
        Db::name('conducteur')->where(['id' => $conducteur_id])->setInc('balance', $chauffeur_money);

        $this->conducteurBoard($conducteur_id, $chauffeur_money, $order['id']);
    }
    //司机流水
    function conducteurBoard($conducteur_id, $money, $order_id)
    {
        $inic['conducteur_id'] = $conducteur_id;
        $inic['title'] = '接单';
        $inic['describe'] = "";
        $inic['order_id'] = $order_id;
        $inic['money'] = $money;
        $inic['symbol'] = 1;
        $inic['create_time'] = time();

        Db::name('conducteur_board')->insert($inic);
    }
}