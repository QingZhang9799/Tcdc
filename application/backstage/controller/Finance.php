<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 19-2-26
 * Time: 上午10:53
 */

namespace app\backstage\controller;

use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;

class Finance extends Base
{
    //充值明细列表
    public function topup_detail_list()
    {
        //数组
        $data = input('');
        $status = implode(",", $data['status']);

        $params = [
            "u.city_id" => input('?city_id') ? input('city_id') : null,
            "u.ordernum" => input('?ordernum') ? ['like', '%' . input('order_code') . '%'] : null,
            "u.nickname" => input('?user_name') ? ['like', '%' . input('user_name') . '%'] : null,
            "u.PassengerPhone" => input('?phone') ? ['like', '%' . input('phone') . '%'] : null,
            "r.money" => input('?money') ? ['eq', (int)input('money')] : null,
            "r.state" => input('?state') ? ['in', $status] : null,
            "r.transaction_id" => input('?order_code') ? ['like', '%' . input('order_code') . '%'] : null,
        ];
        self::filterFilter($params);

        if ($params['u.city_id'] == "0") {
            unset($params['u.city_id']);
        }
        if (input('channel') != null && input('channel') != 'null') {
//            $channel = implode(',', $data['channel']);
            $params['r.is_payment'] = ['in', input('channel')];
        }
        if (input('order_id') != null && input('order_id') != 'null') {
            $params['r.ordernum'] = ['like', "%".input('order_id')."%"];
        }
        $where = [];
        $where1 = [];
        if (!empty(input('times')) && input('times') != "null") {
            $times = explode(",", input('times')) ;
            $where['r.pay_time'] = ['gt', strtotime($times[0] . " 00:00:00")];
            $where1['r.pay_time'] = ['lt', strtotime($times[1] . " 23:59:59")];

        }
//        halt($params);
        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;

        $data = Db::name('recharge_order r')->field("r.*,u.*,r.create_time as create_time1")
            ->join('user u', 'u.id = r.user_id', "left")
            ->where($where)->where($where1)->where(self::filterFilter($params))->order('r.create_time desc')
            ->page($pageNum, $pageSize)
            ->select();

//        echo Db::name('recharge_order r')->getLastSql();
//        exit();

        $sum = Db::name('recharge_order r')->join('user u', 'u.id = r.user_id', "left")
            ->where($where)->where($where1)->where(self::filterFilter($params))->count();

        return [
            "code" => APICODE_SUCCESS,
            "sum" => $sum,
            "data" => $data
        ];
//        return self::pageReturn($db, $params,"r.create_time desc");
    }

    //根据id获取充值详情
    public function getRecharge()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('recharge_order')->where($params)->find();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "充值ID不能为空"
            ];
        }
    }

    //修改充值详情
    public function update_Recharge()
    {
        $params = [
            "id" => input('?id') ? input('id') : null,
            "user_id" => input('?user_id') ? input('user_id') : null,
            "user_name" => input('?user_name') ? input('user_name') : null,
            "phone" => input('?phone') ? input('phone') : null,
            "city_id" => input('?city_id') ? input('city_id') : null,
            "times" => input('?times') ? input('times') : null,
            "channel" => input('?channel') ? input('channel') : null,
            "order_code" => input('?order_code') ? input('order_code') : null,
            "money" => input('?money') ? input('money') : null,
            "status" => input('?status') ? input('status') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $id = $params["id"];
        unset($params["id"]);
        $res = db('user_recharge')->where("id", $id)->update($params);
        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }

    //充值退款
    public function refund()
    {
        if (input('?id')) {

            $ini['id'] = input('?id');
            $ini['status'] = 4;

            $user_recharge = db('user_recharge')->update($ini);

            if ($user_recharge) {
                //理由记录
                $inii['user_recharge_id'] = input('?id');
                $inii['money'] = input('?money');
                $inii['reason'] = input('?reason');

                $user_refund = Db::name('user_refund')->insert($inii);

                if ($user_refund) {
                    return [
                        "code" => APICODE_SUCCESS,
                        "msg" => "退款成功"
                    ];
                } else {
                    return [
                        "code" => APICODE_ERROR,
                        "msg" => "退款失败"
                    ];
                }
            } else {
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "退款失败"
                ];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "充值ID不能为空"
            ];
        }
    }

    //提现列表
    public function withdraw_list()
    {
        $params = [
//            "create_time" => input('?times') ? input('times') : null,
            "con.DriverName" => input('?name') ? ['like', '%' . input('name') . '%'] : null,
//            "type" => input('?type') ? input('type') : null,
//            "phone" => input('?phone') ? ['like', '%' . input('phone') . '%'] : null,
            "c.state" => input('?status') ? ['in', input('status')] : null,
//            "withdraw_bank" => input('?withdraw_bank') ? ['like', '%' . input('withdraw_bank') . '%'] : null,
//            "withdraw_account" => input('?withdraw_account') ? ['like', '%' . input('withdraw_account') . '%'] : null,
//            "withdraw_money" => input('?withdraw_money') ? ['lt', input('withdraw_money')] : null,
        ];

        $where = [];
        $where1 = [];
        $where2 = [];
        if (!empty(input('times')) && input('times') != 'null') {
            $times = explode(',', input('times'));
            $where['c.create_time'] = ['gt', strtotime($times[0] . " 00:00:00")];
            $where1['c.create_time'] = ['lt', strtotime($times[1] . " 23:59:59")];
        }

        if (!empty(input('city_id'))) {
            $where2['con.city_id'] = ['eq', input('city_id')];
        }

        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;

        $data = Db::name('conducteur_withdraw')->alias('c')
            ->join('mx_conducteur con', 'con.id = c.conducteur_id', 'left')
            ->field('c.*,con.DriverName')->where($where)->where($where1)->where($where2)->where(self::filterFilter($params))->order('c.id desc')->page($pageNum, $pageSize)
            ->select();
        $sum = Db::name('conducteur_withdraw')->alias('c')
            ->join('mx_conducteur con', 'con.id = c.conducteur_id', 'left')
            ->field('c.*,con.DriverName')->where($where)->where($where1)->where($where2)->where(self::filterFilter($params))->count();

        return [
            "code" => 200,
            "sum" => $sum,
            "data" => $data
        ];
    }

    //提现审核
    public function withdraw_audit()
    {
        //改变状态
        $ini['id'] = input('id');
        $ini['status'] = input('status');

        $withdraw = Db::name('withdraw')->update($ini);

        if (input('type') == 1) {    //用户提现
            if ($withdraw) {
                //减去用户余额
                $user = Db::name('user')->where(['id' => input('withdrawal_id')])->setDec('balance', input('withdraw_money'));

                if ($user) {
                    return [
                        "code" => APICODE_SUCCESS,
                        "msg" => "审核成功"
                    ];
                } else {
                    return [
                        "code" => APICODE_ERROR,
                        "msg" => "审核失败"
                    ];
                }
            } else {
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "审核失败"
                ];
            }
        } else {                     //司机提现
            if ($withdraw) {
                //减去司机余额
                $conducteur = Db::name('conducteur')->where(['id' => input('withdrawal_id')])->setDec('balance', input('withdraw_money'));

                if ($conducteur) {
                    return [
                        "code" => APICODE_SUCCESS,
                        "msg" => "审核成功"
                    ];
                } else {
                    return [
                        "code" => APICODE_ERROR,
                        "msg" => "审核失败"
                    ];
                }
            } else {
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "审核失败"
                ];
            }
        }
    }

    //根据id获取提现详情
    public function getWithdraw()
    {
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];
            $data = db('withdraw')->where($params)->find();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "提现ID不能为空"
            ];
        }
    }

    //修改提现
    public function updateWithdraw()
    {
        $params = [
            "id" => input('?id') ? input('id') : null,
            "withdraw_account" => input('?withdraw_account') ? input('withdraw_account') : null,
            "withdraw_bank" => input('?withdraw_bank') ? input('withdraw_bank') : null,
            "name" => input('?name') ? input('name') : null,
            "phone" => input('?phone') ? input('phone') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["id"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $id = $params["id"];
        unset($params["id"]);
        $res = db('withdraw')->where("id", $id)->update($params);
        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更新成功",
        ];
    }

    //提现添加
    public function addWithdraw()
    {
        $params = [
            "withdrawal_id" => input('?withdrawal_id') ? input('withdrawal_id') : null,
            "type" => input('?type') ? input('type') : null,
            "name" => input('?name') ? input('name') : null,
            "phone" => input('?phone') ? input('phone') : null,
            "withdraw_money" => input('?withdraw_money') ? input('withdraw_money') : null,
            "withdraw_bank" => input('?withdraw_bank') ? input('withdraw_bank') : null,
            "withdraw_account" => input('?withdraw_account') ? input('withdraw_account') : null,
            "times" => input('?times') ? input('times') : time(),
        ];

        $params = $this->filterFilter($params);
        $required = ["type", "withdrawal_id", "withdraw_money"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $withdraw = db('withdraw')->insert($params);

        if ($withdraw) {
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '创建成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '创建失败'
            ];
        }
    }

    //普通用户余额列表
    public function UserBalanceList()
    {
        $params = [
//            "city_id" => input('?city_id') ? input('city_id') : null,
            "PassengerPhone" => input('?PassengerPhone') ? ['like', '%' . input('PassengerPhone') . '%'] : null,
            "number" => input('?number') ? ['like', '%' . input('number') . '%'] : null,
            "start_time" => input('?start_time') ? ['gt', input('start_time')] : null,
            "end_time" => input('?end_time') ? ['lt', input('end_time')] : null,
            "PassengerGender" => input('?PassengerGender') ? ['eq', (int)input('PassengerGender')] : null,
            "is_attestation" => input('?is_attestation') ? ['eq', input('is_attestation')] : null,
            "trip_count" => input('?trip_count') ? ['gt', input('trip_count')] : null,
            "balance" => input('?balance') ? ['egt', input('balance')] : null,
            "status" => input('?status') ? ['in', (int)input('status')] : null,
        ];

        if (input('city_id') != "0" && !empty(input('city_id'))) {
            $params['city_id'] = ['eq', input('city_id')];
        }
        $where = [];
        $where1 = [];
        if (input('create_time') != 'null' && input('create_time')) {
            $where['create_time'] = ['gt', strtotime(input('create_time') . " 00:00:00")];
            $where1['create_time'] = ['lt', strtotime(input('create_time') . " 23:59:59")];
        }

        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;

        $sortBy = input('?orderBy') ? input('orderBy') : "id desc";
//        halt((self::filterFilter($params)));
        $data = db('user')->where($where)->where($where1)->where(self::filterFilter($params))->order($sortBy)->page($pageNum, $pageSize)->select();

        $sum = db('user')->where($where)->where($where1)->where(self::filterFilter($params))->count();

        return [
            "code" => $sum > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "sum" => $sum,
            "data" => $data
        ];
    }

    //用户余额明细列表
    public function UserBalanceDetailsList()
    {
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "money" => input('?money') ? ['egt', input('money')] : null,
            "type" => input('?type') ? ['eq', input('type')] : null,
//            "create_time" => input('?create_time') ? ['egt', input('create_time')] : null,
        ];
//        return self::pageReturn(db('user_balance'), $params);

        $where = [];
        $where1 = [];
        if (input('create_time') != "null" && !empty(input('create_time'))) {
            $where['create_time'] = ['gt', strtotime(input('create_time') . " 00:00:00")];
            $where1['create_time'] = ['lt', strtotime(input('create_time') . " 23:59:59")];
        }

        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;

        $data = db('user_balance')->where($where)->where($where1)->where(self::filterFilter($params))->order('id desc')->page($pageNum, $pageSize)->select();

        $sum = db('user_balance')->where($where)->where($where1)->where(self::filterFilter($params))->count();

        return [
            "code" => APICODE_SUCCESS,
            "sum" => $sum,
            "data" => $data
        ];
    }

    //用户余额明细添加
    public function AddUserBalance()
    {
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "money" => input('?money') ? input('money') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "money"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        //增加用户余额
        Db::name('user')->where(['id' => input('user_id')])->setInc('balance', input('money'));

        //保存用户余额明细
        $ini['user_id'] = input('user_id');
        $ini['money'] = input('money');
        $ini['type'] = 5;
        $ini['user_name'] = input('user_name');
        $ini['phone'] = input('phone');
        $ini['create_time'] = time();

        $user_balance = db('user_balance')->insert($params);

        if ($user_balance) {
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '创建成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '创建失败'
            ];
        }


    }

    //司机余额列表
    public function ConducteurBalanceList()
    {
        $params = [
//            "city_id" => input('?city_id') ? input('city_id') : null,
//            "company_id" => input('?company_id') ? input('company_id') : null,
//            "status" => input('?status') ? input('status') : null,
//            "DriverName" => input('?DriverName') ? ['like', '%' . input('DriverName') . '%'] : null,
//            "DriverPhone" => input('?DriverPhone') ? ['like', '%' . input('DriverPhone') . '%'] : null,
//            "is_attestation" => input('?is_attestation') ? input('is_attestation') : null,
            "score" => input('?score') ? ['gt', input('score')] : null,
        ];
        if (!empty(input('city_id'))) {
            if (input('city_id') != 'null' && input('city_id') != '0') {
                $params['city_id'] = ['eq', input('city_id')];
            }
        }
        if (!empty(input('status'))) {
            if (input('status') != 'null' && input('status') != '0' && input('status') != null) {
                $params['status'] = ['eq', input('status')];
            }
        }
        if (!empty(input('is_attestation'))) {
            if (input('is_attestation') != 'null' && input('is_attestation') != '0') {
                $params['is_attestation'] = ['eq', input('is_attestation')];
            }
        }

        if (!empty(input('DriverName'))) {
            if (input('DriverName') != 'null' && input('DriverName') != '0') {
                $params['DriverName'] = ['like', '%' . input('DriverName') . '%'];
            }
        }
        if (!empty(input('DriverPhone'))) {
            if (input('DriverPhone') != 'null' && input('DriverPhone') != '0') {
                $params['DriverPhone'] = ['like', '%' . input('DriverPhone') . '%'];
            }
        }
        if (input('company_id') != 'null' && empty(input('company_id')) && input('company_id') != '0') {
            $params['company_id'] = ['eq', input('company_id')];
        }
        $pageSize = input('?pageSize') ? input('pageSize') : 10;
        $pageNum = input('?pageNum') ? input('pageNum') : 0;

//        halt(self::filterFilter($params));
        $data = db('conducteur')->where(self::filterFilter($params))->page($pageNum, $pageSize)
            ->order('id desc')
            ->select();

        $sum = db('conducteur')->where(self::filterFilter($params))->count();
        return [
            "code" => APICODE_SUCCESS,
            "sum" => $sum,
            "data" => $data
        ];
    }

    //司机余额明细列表
    public function ConducteurBalance()
    {
        $params = [
            "money" => input('?money') ? input('money') : null,
            "type" => input('?type') ? input('type') : null,
            "create_time" => input('?create_time') ? ['egt', input('create_time')] : null,
        ];
        return self::pageReturn(db('condueur_balance'), $params);
    }

    //添加司机余额明细
    public function addConducteurBalance()
    {
        $params = [
            "condueur_id" => input('?condueur_id') ? input('condueur_id') : null,
            "money" => input('?money') ? input('money') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["condueur_id", "money"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        //增加
        Db::name('conducteur')->where(['id' => input('?condueur_id')])->setInc('balance', input('money'));

        //保存明细
        $ini['money'] = input('money');
        $ini['type'] = 4;
        $ini['create_time'] = time();

        $condueur_balance = db('condueur_balance')->insert($ini);

        if ($condueur_balance) {
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '添加成功'
            ];
        } else {
            return [
                'code' => APICODE_ERROR,
                'msg' => '添加失败'
            ];
        }


    }

    //财务首页
    public function finance_home()
    {
        $where1 = [];
        $where2 = [];
        $where3 = [];
        $where4 = [];
        $where5 = [];
        $wheres = [];
        $whered = [];
        if (!empty(input('company_id'))) {
            $where2['company_id'] = ['eq', input('company_id')];
            $where3['c.id'] = ['eq', input('company_id')];
            $where4['u.city_id'] = ['eq', input('city_id')];
            $where5['r.city_id'] = ['eq', input('city_id')];
        }
        //如果俩个时间都为空，表示是当天
        $start_time = strtotime(date("Y-m-d"), time());
        $end_time = strtotime($start_time." 23:59:59");

        if (!empty(input('start_time'))) {
            $start_time = strtotime(input('start_time'));
        }
        if (!empty(input('end_time'))) {
            $end_time = strtotime(input('end_time')." 23:59:59");
        }

        $where = ["status" => ["in", "6,9,12,3,4,5"],"pay_time" => ['egt', $start_time]];
        $wheres['r.pay_time'] = ['egt', $start_time];
        $where1['pay_time'] = ['elt', $end_time];
        $whered['r.pay_time'] = ['elt', $end_time];
        //入账金额
        //region 订单入账金额
        $order_money = Db::name('order')->where($where)->where($where1)->where($where2)->value('sum(third_party_money) as money');                            //订单入账

        if (empty($order_money)) {
            $order_money = 0;
        }

        //endregion
        //region 充值入账金额
        $recharge_money = Db::name('recharge_order')->alias('r')//->join('mx_user u', 'u.id = r.user_id', 'left')
        ->where(['r.state' => 1])
            ->where($wheres)
            ->where($whered)
            ->where($where5)
            ->value('sum(money) as money');        //充值入账
        //企业充值
        $enterprise_money = Db::name('enterprise_order')->alias('r')->where(['r.state' => 1])
            ->where($wheres)->where($whered)
            ->where($where5)
            ->value('sum(money) as money');        //企业充值入账

        if (empty($recharge_money)) {
            $recharge_money = 0;
        }
        //endregion
        //入账总金额
        $sum_money = sprintf("%.2f", ($order_money + $recharge_money + $enterprise_money));

        //订单入账-支付宝和微信
        $order_wechat_money = Db::name('order')->where('third_party_type', 'eq', 0)->where($where)->where($where1)->where($where2)->value('sum(third_party_money) as money');       //订单微信
        $order_alipay_money = Db::name('order')->where('third_party_type', 'eq', 1)->where($where)->where($where1)->where($where2)->value('sum(third_party_money) as money');       //订单支付宝
//        halt($order_alipay_money);
        //充值入账-微信和支付宝
        $recharge_wechat_money = Db::name('recharge_order')->alias('r')->join('mx_user u', 'u.id = r.user_id', 'left')
            ->where(['r.state' => 1])->where('r.is_payment', 'eq', 0)->where($wheres)->where($whered)
            ->where($where5)
            ->value('sum(money) as money');
        $recharge_alipay_money = Db::name('recharge_order')->alias('r')->join('mx_user u', 'u.id = r.user_id', 'left')
            ->where(['r.state' => 1])->where('r.is_payment', 'eq', 1)->where($wheres)->where($whered)
            ->where($where5)
            ->value('sum(money) as money');

        $recharge_enterprise_wechat_money = Db::name('enterprise_order')->alias('r')
            ->where(['r.state' => 1])->where('r.is_payment', 'eq', 0)->where($wheres)->where($whered)
            ->where($where5)
            ->value('sum(money) as money');
        $recharge_enterprise_alipay_money = Db::name('enterprise_order')->alias('r')
            ->where(['r.state' => 1])->where('r.is_payment', 'eq', 1)->where($wheres)->where($whered)
            ->where($where5)
            ->value('sum(money) as money');

        if (empty($order_money)) {
            $order_money = 0;
        }
        if (empty($order_wechat_money)) {
            $order_wechat_money = 0;
        }
        if (empty($order_alipay_money)) {
            $order_alipay_money = 0;
        }
        if (empty($recharge_wechat_money)) {
            $recharge_wechat_money = 0;
        }
        if (empty($recharge_alipay_money)) {
            $recharge_alipay_money = 0;
        }
        if (empty($recharge_enterprise_wechat_money)) {
            $recharge_enterprise_wechat_money = 0;
        }
        if (empty($recharge_enterprise_alipay_money)) {
            $recharge_enterprise_alipay_money = 0;
        }

        $data['recorded'] = [
            'sum_money' => $sum_money,
            'order_money' => $order_money,
            'recharge_money' => sprintf("%.2f", $enterprise_money + $recharge_money),
            'order' => [
                'order_wechat_money' => $order_wechat_money,
                'order_alipay_money' => $order_alipay_money,
            ],
            'recharge' => [
                'recharge_wechat_money' => sprintf("%.2f", (sprintf("%.2f", $recharge_wechat_money) + sprintf("%.2f", $recharge_enterprise_wechat_money))),
                'recharge_alipay_money' => sprintf("%.2f", (sprintf("%.2f", $recharge_alipay_money) + sprintf("%.2f", $recharge_enterprise_alipay_money))),
            ]
        ];

        //流水
        $flow_order_money = Db::name('order')->where($where)->where($where1)->where($where2)->value('sum(money) as money');                                           //流水订单入账
        $flow_recharge_money = Db::name('recharge_order')->alias('r')->join('mx_user u', 'u.id = r.user_id', 'left')
            ->where(['r.state' => 1])->where($wheres)->where($whered)->where($where4)->value('sum(money) as money');        //流水充值入账
        $flow_sum_money = sprintf("%.2f", ($flow_order_money + $flow_recharge_money));                                                                     //流水入账总金额

        //流水订单-支付宝和微信
        $flow_order_wechat_money = Db::name('order')->where('third_party_type', 'eq', 0)->where($where)->where($where1)->where($where2)->value('sum(third_party_money) as money');       //订单微信
        $flow_order_alipay_money = Db::name('order')->where('third_party_type', 'eq', 1)->where($where)->where($where1)->where($where2)->value('sum(third_party_money) as money');       //订单支付宝
        $flow_order_balance_money = Db::name('order')->where($where)->where($where1)->where($where2)->value('sum(balance_payment_money) as money');       //订单余额
        $flow_order_discounts_money = Db::name('order')->where($where)->where($where1)->where($where2)->value('sum(discounts_money) as money');       //订单优惠金额

        //流水充值-微信和支付宝
        $flow_recharge_wechat_money = Db::name('recharge_order')->alias('r')->join('mx_user u', 'u.id = r.user_id', 'left')
            ->where(['r.state' => 1])->where('r.is_payment', 'eq', 0)->where($wheres)->where($whered)->where($where4)->value('sum(money) as money');
        $flow_recharge_alipay_money = Db::name('recharge_order')->alias('r')->join('mx_user u', 'u.id = r.user_id', 'left')
            ->where(['r.state' => 1])->where('r.is_payment', 'eq', 1)->where($wheres)->where($whered)->where($where4)->value('sum(money) as money');

        if (empty($flow_order_money)) {
            $order_money = 0;
        }
        if (empty($flow_order_wechat_money)) {
            $flow_order_wechat_money = 0;
        }
        if (empty($flow_order_alipay_money)) {
            $flow_order_alipay_money = 0;
        }
        if (empty($flow_recharge_wechat_money)) {
            $flow_recharge_wechat_money = 0;
        }
        if (empty($flow_recharge_alipay_money)) {
            $flow_recharge_alipay_money = 0;
        }
        $data['flow'] = [
            'flow_sum_money' => $flow_sum_money,
            'flow_order_money' => $flow_order_money,
            'recharge_money' => sprintf("%.2f", $flow_recharge_money),
            'flow_order' => [
                'flow_order_wechat_money' => $flow_order_wechat_money,
                'flow_order_alipay_money' => $flow_order_alipay_money,
                'flow_order_balance_money' => $flow_order_balance_money,
                'flow_order_discounts_money' => $flow_order_discounts_money,
            ],
            'flow_recharge' => [
                'flow_recharge_wechat_money' => $flow_recharge_wechat_money,
                'flow_recharge_alipay_money' => $flow_recharge_alipay_money,
            ]
        ];

        $company = Db::name('company')->alias('c')
            ->field('c.id,c.CompanyName')
            ->where($where3)
            ->select();

        foreach ($company as $key => $value) {
            $money = Db::name('order')->where(['company_id' => $value['id']])->where('status', 'in', '6,8,9,12,3,4')->where($where)->where($where1)->sum('money');
            if (empty($money)) {
                $money = 0;
            }

            $company[$key]['money'] = $money  ;                                  //订单流水总额

            $discounts_money = Db::name('order')->where(['company_id' => $value['id']])->where('status', 'in', '6,8,9,12,3,4')->where($where)->where($where1)->sum('discounts_money');
            if (empty($discounts_money)) {
                $discounts_money = 0;
            }
            $company[$key]['discounts_money'] = $discounts_money;           //订单优惠总额
            $wechat_third_party_money = Db::name('order')->where(['company_id' => $value['id']])->where('status', 'in', '6,8,9,12,3,4')->where($where)->where($where1)->where('third_party_type', 'eq', 0)->sum('third_party_money');
            if (empty($wechat_third_party_money)) {
                $wechat_third_party_money = 0;
            }
            $company[$key]['wechat_third_party_money'] = $wechat_third_party_money;           //微信支付总额
            $alipay_third_party_money = Db::name('order')->where(['company_id' => $value['id']])->where('status', 'in', '6,8,9,12,3,4')->where($where)->where($where1)->where('third_party_type', 'eq', 1)->sum('third_party_money');
            if (empty($alipay_third_party_money)) {
                $alipay_third_party_money = 0;
            }
            $company[$key]['alipay_third_party_money'] = $alipay_third_party_money;           //支付宝支付总额
            $third_party_money = Db::name('order')->where(['company_id' => $value['id']])->where('status', 'in', '6,8,9,12,3,4')->where($where)->where($where1)->where('third_party_type', 'in', '0,1')->sum('third_party_money');
            if (empty($third_party_money)) {
                $third_party_money = 0;
            }
            $company[$key]['third_party_money'] = $third_party_money;                          //第三方支付总额
            $balance_payment_money = Db::name('order')->where(['company_id' => $value['id']])->where('status', 'in', '6,8,9')->where($where)->where($where1)->sum('balance_payment_money');
            if (empty($balance_payment_money)) {
                $balance_payment_money = 0;
            }
            $company[$key]['balance_payment_money'] = $balance_payment_money;                          //订单余额支付总额
            $parent_company_money = Db::name('order')->where(['company_id' => $value['id']])->where('status', 'in', '6,8,9')->where($where)->where($where1)->sum('parent_company_money');
            if (empty($parent_company_money)) {
                $parent_company_money = 0;
            }

            $company[$key]['parent_company_money'] = $parent_company_money;                          //总公司抽成金额
            $superior_company_money = Db::name('order')->where(['company_id' => $value['id']])->where('status', 'in', '6,8,9')->where($where)->where($where1)->sum('superior_company_money');
            if (empty($superior_company_money)) {
                $superior_company_money = 0;
            }
            $company[$key]['superior_company_money'] = $superior_company_money;                          //上级分公司抽成金额
            $filiale_company_money = Db::name('order')->where(['company_id' => $value['id']])->where('status', 'in', '6,8,9')->where($where)->where($where1)->sum('filiale_company_money');
            if (empty($filiale_company_money)) {
                $filiale_company_money = 0;
            }
            $company[$key]['filiale_company_money'] = $filiale_company_money;                          //分公司抽成金额
            $filiale_company_settlement = Db::name('order')->where(['company_id' => $value['id']])->where('status', 'in', '6,8,9')->where($where)->where($where1)->sum('filiale_company_settlement');
            if (empty($filiale_company_settlement)) {
                $filiale_company_settlement = 0;
            }
            $company[$key]['filiale_company_settlement'] = $filiale_company_settlement;                          //分公司结算金额
        }

        $data['company'] = $company;

        return ['code' => APICODE_SUCCESS, 'data' => $data, 'start_time' => input('start_time'), 'end_time' => input('end_time')];
    }

    //提现审核
    public function WithdrawalAudit()
    {
        $params = [
            "id" => input('?id') ? input('id') : null,
            "state" => input('?state') ? input('state') : null,
            "pass_time" => time(),
        ];

        $params = $this->filterFilter($params);
        $required = ["id", "state"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $str = "";
        if (input('state') == 1) {
            $str = "通过";
            //通过企业零钱,给司机结款
            $enpay = new \app\backstage\controller\EnPay();
            //$amount,$re_openid,$desc='测试',$check_name=''
            $withdraw = Db::name('conducteur_withdraw')->where(['id' => input('id')])->find();
            $openid = Db::name('conducteur')->where(['id' => $withdraw['conducteur_id']])->value('openid');
            //清除冻结提现冻结金额
            Db::name('conducteur')->where(['id' => $withdraw['conducteur_id']])->setDec('freeze_balance', $withdraw['money']);
//            $enpay->sendMoney($withdraw['money'],$openid) ;

        } else if (input('state') == 2) {
            $str = "拒绝";
            //将司机的钱，加回去.
            $conducteur_withdraw = Db::name('conducteur_withdraw')->where(['id' => input('id')])->find();

            Db::name('conducteur')->where(['id' => $conducteur_withdraw['conducteur_id']])->setInc('balance', $conducteur_withdraw['money']);
            Db::name('conducteur')->where(['id' => $conducteur_withdraw['conducteur_id']])->setDec('freeze_balance', $conducteur_withdraw['money']);
        }

        $res = Db::name('conducteur_withdraw')->update($params);

        return [
            "code" => $res > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => $str . "成功",
        ];
    }

    //用户余额变动
    public function UserVariation()
    {
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : null,
            "money" => input('?money') ? input('money') : null,
            "type" => input('?type') ? input('type') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "money", "type"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $symbol = 0;
        if (input('type') == 4) {     //赠送
            Db::name('user')->where(['id' => input('user_id')])->setInc('balance', input('money'));
            $symbol = 1;
        } else if (input('type') == 3) {  //消费
            Db::name('user')->where(['id' => input('user_id')])->setDec('balance', input('money'));
            $symbol = 2;
        } else if (input('type') == 2) {  //提现
            Db::name('user')->where(['id' => input('user_id')])->setDec('balance', input('money'));
            $symbol = 2;
        }
        $user = Db::name('user')->where(['id' => input('user_id')])->find();
        //保存余额变动记录
        $ini['user_id'] = input('user_id');
        $ini['money'] = input('money');
        $ini['type'] = input('type');
        $ini['user_name'] = $user['PassengerName'];
        $ini['phone'] = $user['PassengerPhone'];
        $ini['create_time'] = time();
        $ini['symbol'] = $symbol;

        Db::name('user_balance')->insert($ini);

        return [
            'code' => APICODE_SUCCESS,
            'msg' => '添加成功'
        ];
    }

    //司机余额变动明细
    public function ChauffeurVariation()
    {
        if (input('?conducteur_id')) {
            $params = [
                "conducteur_id" => input('conducteur_id')
            ];
            return self::pageReturn(db('conducteur_board'), $params, "id desc");

        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }
    }

    //全部通过
    public function AllPass()
    {
        //获取提现数组
        $ids = explode(',', input('id'));

        //通过企业零钱,给司机结款
        $enpay = new \app\backstage\controller\EnPay();

        foreach ($ids as $key => $value) {
            $withdraw = Db::name('conducteur_withdraw')->where(['id' => $value])->find();
            $openid = Db::name('conducteur')->where(['id' => $withdraw['conducteur_id']])->value('openid');
            //清除冻结提现冻结金额
            Db::name('conducteur')->where(['id' => $withdraw['conducteur_id']])->setDec('freeze_balance', $withdraw['money']);
            $enpay->sendMoney($withdraw['money'], $openid);
        }
        return [
            "code" => APICODE_SUCCESS,
            "msg" => "成功",
        ];
    }

    //司机余额抵扣
    public function DriverDeduction()
    {
        if (input('?conducteur_id')) {
            $params = [
                "id" => input('conducteur_id')
            ];
            //减少司机余额
            db('conducteur')->where($params)->setDec('balance',input('money')) ;
            //增加司机流水负数
            $ini['conducteur_id'] = input('conducteur_id') ;
            $ini['title'] = "抵扣" ;
            $ini['describe'] = "" ;
            $ini['order_id'] = 0 ;
            $ini['money'] = input('money') ;
            $ini['symbol'] = 2 ;
            $ini['create_time'] = time() ;

            Db::name('conducteur_board')->insert($ini) ;
            return [
                "code" => APICODE_SUCCESS,
                "msg" => "抵扣成功",
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "司机ID不能为空"
            ];
        }
    }
    //批量导出司机在线时长
    public function DriverOnlineDuration(){
//        $driverId=input("driverId");
        $date=input("date");
        $driverInfos = [] ;
        $conducteur = Db::name('conducteur')->where(['city_id'=>62])->limit(5)->select() ;
        foreach ($conducteur as $key =>$value ){
            if( input('dataType') == 1 ){
                $driverId = $value['id'] ;
                $driverInfos=db()->query("call getDriverOnlineTimeByIdAndDate($driverId,'$date')");
                $driverInfos=$driverInfos[0];
            }
        }

        return [
            "code" => APICODE_SUCCESS,
            "msg" =>"",
            "sum"=>count($driverInfos),
            "data"=>$driverInfos,
        ];
    }
}