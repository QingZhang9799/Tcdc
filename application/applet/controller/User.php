<?php


namespace app\applet\controller;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Request;

class User extends Base
{
    //测试分钱接口
    public function testpay(){
        $orderinfo = [
            'id'=>603,
            'business_id'=>1,
            'businesstype_id'=>1,
        ];
//        $this->companyMoney(133, 10, $orderinfo, 0);                    //给司机分钱
        $this->distributionChauffeur(133,62,10);
    }

    //抽成
    function companyMoney($conducteur_id,$money,$order,$discount_money){

        $company_id = Db::name("conducteur")->where(['id'=>$conducteur_id])->value('company_id');  //公司id

        //在获取抽成规则
        $company_ratio = Db::name('company_ratio')->where(['company_id'=>$company_id,'business_id'=>$order['business_id'],'businesstype_id'=>$order['businesstype_id']])->find();

        //总公司抽成
        $parent_company = ($company_ratio['parent_company_ratio']/100) * $money ;
        //上级分公司抽成
        $superior_company = ($company_ratio['filiale_company_ratio']/100) * $money ;  //没有上级值 为 0
        //分公司结算金额
        $compamy_money = ($money - $discount_money) - ( ($company_ratio['parent_company_ratio']/100) * $money ) - ( ($company_ratio['filiale_company_ratio']/100) * $money ) ;
        //分公司利润
        $compamy_profit = ($company_ratio['company_ratio']/100) * $money ;
        //司机
        $chauffeur_money = $money - ( ($company_ratio['parent_company_ratio']/100) * $money) - ( ($company_ratio['filiale_company_ratio']/100) * $money ) - ( ($company_ratio['company_ratio']/100) * $money) ;

        $inii = [];
        $inii['id'] = $order['id'];
        $inii['parent_company_money'] = $parent_company;
        $inii['superior_company_money'] = $superior_company;
        $inii['filiale_company_money'] = $compamy_profit;
        $inii['chauffeur_income_money'] = $chauffeur_money;
        $inii['filiale_company_settlement'] = $compamy_money;

        Db::name('order')->update($inii);

        //司机加余额
        Db::name('conducteur')->where(['id'=>$conducteur_id])->setInc('balance',$chauffeur_money);
    }
//分销给司机分钱
    function distributionChauffeur($id,$city_id,$actually_money){
        $distribution_id = $id;             //分销司机id
        $conducteur = Db::name('conducteur')->where(['id'=>$distribution_id])->find();
        //司机状态为正常或者临时禁封，分销状态为正常，才可以正常分钱
        if( ($conducteur['status'] == 1 || $conducteur['status'] == 3) && ($conducteur['distribution_state']  == 1 )){
            //判断订单的城市和司机城市在不在同一个地方
            if($city_id == $conducteur['city_id']){
                //获取分销规则
                $company_distribution = Db::name('company_distribution')->where(['company_id'=>$conducteur['company_id']])->find();
                if($company_distribution['type'] == 0){                   //比例
                    $money = $actually_money * ($company_distribution['ratio'] /100);
                    Db::name('conducteur')->where(['id'=>$distribution_id])->setInc('distribution_money',$money);
                }else if($company_distribution['type'] == 1){             //区间
                    //获取区间信息
                    $company_distribution_detail = Db::name('company_distribution_detail')->where(['company_distribution_id'=>$company_distribution['id']])->select();
                    //获取当前我有多少人
                    $people_count = Db::name('conducteur_distribution_balance')->where(['conducteur_id'=>$distribution_id])->count();  //人数
                    foreach ($company_distribution_detail as $key=>$value){
                        if( ( $people_count >= $value['range_one'] ) || ($people_count <= $value['range_two']) ){
                            $money = $actually_money * ( $value['ratio'] /100 );
                            Db::name('conducteur')->where(['id'=>$distribution_id])->setInc('distribution_money',$money);
                        }
                    }
                }
            }
        }
    }














}