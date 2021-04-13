<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 19-2-26
 * Time: 上午10:53
 */

namespace app\user\controller;

use app\api\model\Conducteur;
use app\api\model\Company;
use mrmiao\encryption\RSACrypt;
use think\Cache;
use think\Db;
use think\Config;
use app\user\controller\Marketing;

class Login extends Base
{
    protected $appid;
    protected $appSecret;

    public function __construct()
    {
        $this->appid = config('wx_config.appid');
        $this->appSecret = config('wx_config.secret');
    }

    //获取验证码
    public function sendSMS()
    {
        //手机号
        $mobile = request()->param('phone');


        $rand_code = rand(100000, 999999);

        $acsResponse = sendSMSS($mobile, $rand_code, "SMS_194060321");

        $res = $acsResponse->Code == 'OK' ? true : false;
        if ($res) {
            Cache::set('reset_password', (string)$rand_code, 3600);
            //发送验证码
            return ['code' => APICODE_SUCCESS, 'msg' => '发送成功'];
        } else {
            return ['code' => APICODE_ERROR, 'msg' => '发送失败'];

        }
    }

    //自动注册
    public function auto_register()
    {
        $mobile = request()->param('phone');
        $rand_code = request()->param('code');

        if ($rand_code != Cache::get('reset_password')) {
            return ['code' => APICODE_ERROR, 'msg' => '验证码错误'];
        } else {
            $user = Db::name('user')->where(['PassengerPhone' => $mobile])->find();
            $flag = 0;
            $users = [];
            if ($user) {
                $ini['id'] = $user['id'];
                if (input('is_flag') == 0) {
                    $ini['city_id'] = input('city_id');
                }
                Db::name('user')->update($ini);
                $users = Db::name('user')->where(['id' => $user['id']])->find();
                session('user_id', $user['id']);
            } else {
                $city_id = input('?city_id') ? input('city_id') : 0;

                //自动注册
                $ini['PassengerPhone'] = $mobile;
                $ini['user_pwd'] = encrypt_salt('123456');
                $ini['create_time'] = time();
                $ini['city_id'] = $city_id;
//
//                //默认头像和昵称
                $user_portrait = Db::name('user_allocation')->where(['id' => 1])->value('user_portrait');
                $ini['portrait'] = $user_portrait;
//                $ini['nickname'] = $this->newNickName();
                $ini['nickname'] = "同城" . rand(0000, 9999);
                $ini['logon_tip'] = input('logon_tip');
                $ini['star'] = 5;
                $user_id = Db::name('user')->insertGetId($ini);

                $m = new Marketing();
                $active = $m->judgeActivity($user_id, $city_id, 1, '');
                $flag = 1;
                $users = Db::name('user')->where(['id' => $user_id])->find();
                $active_active = 0;
                if ($active) {
                    $active_active = 1;
                    $users["active_active"] = $active_active;
                } else {
                    $users["active_active"] = $active_active;
                }
                session('user_id', $users['id']);
            }
            return ['code' => APICODE_SUCCESS, 'msg' => '登录成功', 'flag' => $flag, 'data' => $users];
        }
    }

    //获取触发活动优惠券列表
    public function TriggerCoupon()
    {
        if (input('?user_id')) {
            $params = [
                "h.user_id" => input('user_id')
            ];

            $data = [];

            //所以能通知活动
            $activity_program_history = Db::name('activity_program_history')->alias('h')
                ->field('p.activity_id,h.id,h.activity_program_id')
                ->join('mx_activity_program p', 'p.id = h.activity_program_id', 'left')
                ->join('mx_activity a', 'a.id = p.activity_id', 'left')
                ->where($params)
                ->where('h.is_inform', 'eq', 0)
                ->where(['a.is_gift' => 1])
                ->select();
            //查询所有想通知的优惠券
            foreach ($activity_program_history as $key => $value) {
                $activity_coupon = Db::name('activity_coupon')->where(['activity_id' => $value['activity_id']])->select();
                foreach ($activity_coupon as $k => $v) {
                    $data[] = [
                        'coupon_type' => $v['coupon_type'],
                        'strat_time' => $v['strat_time'],
                        'end_time' => $v['end_time'],
                        'grant_count' => $v['grant_count'],
                        'title' => $v['title'],
                        'type' => $v['type'],
                        'discount' => $v['discount'],
                        'min_money' => $v['min_money'],
                        'man_money' => $v['man_money'],
                        'pay_money' => $v['pay_money'],
                        'create_time' => $v['create_time'],
                        'status' => $v['status'],
                        'company' => $v['company'],
                        'arctic' => $v['arctic'],
                        'start_day' => $v['start_day'],
                        'end_day' => $v['end_day'],
                        'order_type' => $v['order_type'],
                        'coupon_template_id' => $v['coupon_template_id'],
                        'minus_money' => $v['minus_money']
                    ];
                }
                $ini['id'] = $value['id'];
                $ini['is_inform'] = 1;
                Db::name('activity_program_history')->update($ini);
            }

            return [
                "code" => APICODE_SUCCESS,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //动态文字
    public function newNickName()
    {
        $tou = array('快乐', '冷静', '醉熏', '潇洒', '糊涂', '积极', '冷酷', '深情', '粗暴', '温柔', '可爱', '愉快', '义气', '认真', '威武', '帅气', '传统', '潇洒', '漂亮', '自然', '专一', '听话', '昏睡', '狂野', '等待', '搞怪', '幽默', '魁梧', '活泼', '开心', '高兴', '超帅', '留胡子', '坦率', '直率', '轻松', '痴情', '完美', '精明', '无聊', '有魅力', '丰富', '繁荣', '饱满', '炙热', '暴躁', '碧蓝', '俊逸', '英勇', '健忘', '故意', '无心', '土豪', '朴实', '兴奋', '幸福', '淡定', '不安', '阔达', '孤独', '独特', '疯狂', '时尚', '落后', '风趣', '忧伤', '大胆', '爱笑', '矮小', '健康', '合适', '玩命', '沉默', '斯文', '香蕉', '苹果', '鲤鱼', '鳗鱼', '任性', '细心', '粗心', '大意', '甜甜', '酷酷', '健壮', '英俊', '霸气', '阳光', '默默', '大力', '孝顺', '忧虑', '着急', '紧张', '善良', '凶狠', '害怕', '重要', '危机', '欢喜', '欣慰', '满意', '跳跃', '诚心', '称心', '如意', '怡然', '娇气', '无奈', '无语', '激动', '愤怒', '美好', '感动', '激情', '激昂', '震动', '虚拟', '超级', '寒冷', '精明', '明理', '犹豫', '忧郁', '寂寞', '奋斗', '勤奋', '现代', '过时', '稳重', '热情', '含蓄', '开放', '无辜', '多情', '纯真', '拉长', '热心', '从容', '体贴', '风中', '曾经', '追寻', '儒雅', '优雅', '开朗', '外向', '内向', '清爽', '文艺', '长情', '平常', '单身', '伶俐', '高大', '懦弱', '柔弱', '爱笑', '乐观', '耍酷', '酷炫', '神勇', '年轻', '唠叨', '瘦瘦', '无情', '包容', '顺心', '畅快', '舒适', '靓丽', '负责', '背后', '简单', '谦让', '彩色', '缥缈', '欢呼', '生动', '复杂', '慈祥', '仁爱', '魔幻', '虚幻', '淡然', '受伤', '雪白', '高高', '糟糕', '顺利', '闪闪', '羞涩', '缓慢', '迅速', '优秀', '聪明', '含糊', '俏皮', '淡淡', '坚强', '平淡', '欣喜', '能干', '灵巧', '友好', '机智', '机灵', '正直', '谨慎', '俭朴', '殷勤', '虚心', '辛勤', '自觉', '无私', '无限', '踏实', '老实', '现实', '可靠', '务实', '拼搏', '个性', '粗犷', '活力', '成就', '勤劳', '单纯', '落寞', '朴素', '悲凉', '忧心', '洁净', '清秀', '自由', '小巧', '单薄', '贪玩', '刻苦', '干净', '壮观', '和谐', '文静', '调皮', '害羞', '安详', '自信', '端庄', '坚定', '美满', '舒心', '温暖', '专注', '勤恳', '美丽', '腼腆', '优美', '甜美', '甜蜜', '整齐', '动人', '典雅', '尊敬', '舒服', '妩媚', '秀丽', '喜悦', '甜美', '彪壮', '强健', '大方', '俊秀', '聪慧', '迷人', '陶醉', '悦耳', '动听', '明亮', '结实', '魁梧', '标致', '清脆', '敏感', '光亮', '大气', '老迟到', '知性', '冷傲', '呆萌', '野性', '隐形', '笑点低', '微笑', '笨笨', '难过', '沉静', '火星上', '失眠', '安静', '纯情', '要减肥', '迷路', '烂漫', '哭泣', '贤惠', '苗条', '温婉', '发嗲', '会撒娇', '贪玩', '执着', '眯眯眼', '花痴', '想人陪', '眼睛大', '高贵', '傲娇', '心灵美', '爱撒娇', '细腻', '天真', '怕黑', '感性', '飘逸', '怕孤独', '忐忑', '高挑', '傻傻', '冷艳', '爱听歌', '还单身', '怕孤单', '懵懂');
        $do = array("的", "爱", "", "与", "给", "扯", "和", "用", "方", "打", "就", "迎", "向", "踢", "笑", "闻", "有", "等于", "保卫", "演变");
        $wei = array('嚓茶', '凉面', '便当', '毛豆', '花生', '可乐', '灯泡', '哈密瓜', '野狼', '背包', '眼神', '缘分', '雪碧', '人生', '牛排', '蚂蚁', '飞鸟', '灰狼', '斑马', '汉堡', '悟空', '巨人', '绿茶', '自行车', '保温杯', '大碗', '墨镜', '魔镜', '煎饼', '月饼', '月亮', '星星', '芝麻', '啤酒', '玫瑰', '大叔', '小伙', '哈密瓜，数据线', '太阳', '树叶', '芹菜', '黄蜂', '蜜粉', '蜜蜂', '信封', '西装', '外套', '裙子', '大象', '猫咪', '母鸡', '路灯', '蓝天', '白云', '星月', '彩虹', '微笑', '摩托', '板栗', '高山', '大地', '大树', '电灯胆', '砖头', '楼房', '水池', '鸡翅', '蜻蜓', '红牛', '咖啡', '机器猫', '枕头', '大船', '诺言', '钢笔', '刺猬', '天空', '飞机', '大炮', '冬天', '洋葱', '春天', '夏天', '秋天', '冬日', '航空', '毛衣', '豌豆', '黑米', '玉米', '眼睛', '老鼠', '白羊', '帅哥', '美女', '季节', '鲜花', '服饰', '裙子', '白开水', '秀发', '大山', '火车', '汽车', '歌曲', '舞蹈', '老师', '导师', '方盒', '大米', '麦片', '水杯', '水壶', '手套', '鞋子', '自行车', '鼠标', '手机', '电脑', '书本', '奇迹', '身影', '香烟', '夕阳', '台灯', '宝贝', '未来', '皮带', '钥匙', '心锁', '故事', '花瓣', '滑板', '画笔', '画板', '学姐', '店员', '电源', '饼干', '宝马', '过客', '大白', '时光', '石头', '钻石', '河马', '犀牛', '西牛', '绿草', '抽屉', '柜子', '往事', '寒风', '路人', '橘子', '耳机', '鸵鸟', '朋友', '苗条', '铅笔', '钢笔', '硬币', '热狗', '大侠', '御姐', '萝莉', '毛巾', '期待', '盼望', '白昼', '黑夜', '大门', '黑裤', '钢铁侠', '哑铃', '板凳', '枫叶', '荷花', '乌龟', '仙人掌', '衬衫', '大神', '草丛', '早晨', '心情', '茉莉', '流沙', '蜗牛', '战斗机', '冥王星', '猎豹', '棒球', '篮球', '乐曲', '电话', '网络', '世界', '中心', '鱼', '鸡', '狗', '老虎', '鸭子', '雨', '羽毛', '翅膀', '外套', '火', '丝袜', '书包', '钢笔', '冷风', '八宝粥', '烤鸡', '大雁', '音响', '招牌', '胡萝卜', '冰棍', '帽子', '菠萝', '蛋挞', '香水', '泥猴桃', '吐司', '溪流', '黄豆', '樱桃', '小鸽子', '小蝴蝶', '爆米花', '花卷', '小鸭子', '小海豚', '日记本', '小熊猫', '小懒猪', '小懒虫', '荔枝', '镜子', '曲奇', '金针菇', '小松鼠', '小虾米', '酒窝', '紫菜', '金鱼', '柚子', '果汁', '百褶裙', '项链', '帆布鞋', '火龙果', '奇异果', '煎蛋', '唇彩', '小土豆', '高跟鞋', '戒指', '雪糕', '睫毛', '铃铛', '手链', '香氛', '红酒', '月光', '酸奶', '银耳汤', '咖啡豆', '小蜜蜂', '小蚂蚁', '蜡烛', '棉花糖', '向日葵', '水蜜桃', '小蝴蝶', '小刺猬', '小丸子', '指甲油', '康乃馨', '糖豆', '薯片', '口红', '超短裙', '乌冬面', '冰淇淋', '棒棒糖', '长颈鹿', '豆芽', '发箍', '发卡', '发夹', '发带', '铃铛', '小馒头', '小笼包', '小甜瓜', '冬瓜', '香菇', '小兔子', '含羞草', '短靴', '睫毛膏', '小蘑菇', '跳跳糖', '小白菜', '草莓', '柠檬', '月饼', '百合', '纸鹤', '小天鹅', '云朵', '芒果', '面包', '海燕', '小猫咪', '龙猫', '唇膏', '鞋垫', '羊', '黑猫', '白猫', '万宝路', '金毛', '山水', '音响', '尊云', '西安');
        $tou_num = rand(0, 331);
        $do_num = rand(0, 19);
        $wei_num = rand(0, 327);
        $type = rand(0, 1);
        if ($type == 0) {
            $username = $tou[$tou_num] . $do[$do_num] . $wei[$wei_num];
        } else {
            $username = $wei[$wei_num] . $tou[$tou_num];
        }
        return $username;
    }

   //设置密码
   public function set_password()
    {

        $user_id = request()->param('user_id');
        $password = encrypt_salt(request()->param('password'));

        $ini['id'] = (int)$user_id;
        $ini['user_pwd'] = $password;

        $user = Db::name('user')->update($ini);

        if ($user) {
            return ['code' => APICODE_SUCCESS, 'msg' => '设置成功'];
        } else {
            return ['code' => APICODE_ERROR, 'msg' => '设置密码与原密码相同，请重新设置'];
        }
    }

   //密码登录
   public function pass_login()
    {
        $PassengerPhone = input('PassengerPhone');
        $user_pwd = encrypt_salt(input('user_pwd'));

        $user = Db::name('user')->where(['PassengerPhone' => $PassengerPhone,
            'user_pwd' => $user_pwd])->where('status', 'eq', 0)->find();

        if ($user) {
            if (input('is_flag') == 0) {
                $ini['city_id'] = input('city_id');
            }

            $ini['id'] = $user['id'];
            $ini['logon_tip'] = input('logon_tip');
            Db::name('user')->update($ini);
            $flag = 0;

            $users = Db::name('user')->where(['id' => $user['id']])->find();

            return ['code' => APICODE_SUCCESS, 'msg' => '登录成功', 'data' => $users, 'flag' => $flag];
        } else {
            $users = Db::name('user')->where(['PassengerPhone' => $PassengerPhone])->find();
            if ($users) {
                if ($users['status'] == 1) {
                    return ['code' => APICODE_ERROR, 'msg' => '禁封中'];
                } else if ($users['status'] == 2) {
                    return ['code' => APICODE_ERROR, 'msg' => '已注销'];
                } else {
                    return ['code' => APICODE_ERROR, 'msg' => '密码错误'];
                }
            } else {
                return ['code' => APICODE_ERROR, 'msg' => '账号不存在'];
            }
        }
    }

   //忘记密码
   public function forget_pass()
    {
        $phone = request()->param('phone');
        $code = request()->param('code');

        if ($code != Cache::get('reset_password')) {
            return ['code' => APICODE_ERROR, 'msg' => '验证码错误'];
        } else {
            //返回用户id
            $user = Db::name('user')->where(['PassengerPhone' => $phone])->find();
            return ['code' => APICODE_SUCCESS, 'msg' => '成功', 'user_id' => $user['id']];
        }
    }
    //修改密码
    public function ModificationUserPassword()
    {
        $phone = request()->param('phone');
        $code = request()->param('auth_code');

        if ($code != Cache::get('reset_password')) {
            return ['code' => APICODE_ERROR, 'msg' => '验证码错误'];
        } else {
               $user = Db::name('user')->where(['PassengerPhone' => $phone ])->find() ;
               $ini['id'] = $user['id'] ;
               $ini['user_pwd'] = encrypt_salt(input('password'));

               $res = Db::name('user')->update($ini);
               if($res > 0){
                   return ['code' => APICODE_SUCCESS, 'msg' => '修改成功', 'user_id' => $user['id']];
               }else{
                   return ['code' => APICODE_ERROR, 'msg' => '修改失败'];
               }
        }
    }

   //修改密码
   public function update_pass()
    {

        $user_id = input('user_id');
        $passwordss = encrypt_salt(input('password'));

        $ini['id'] = $user_id;
        $ini['user_pwd'] = $passwordss;

        $user = Db::name('user')->update($ini);

        if ($user > 0) {
            return ['code' => APICODE_SUCCESS, 'msg' => '修改成功'];
        } else {
            return ['code' => APICODE_ERROR, 'msg' => '修改失败'];
        }
    }

    //绑定手机号
    public function binding_phone()
    {
        $phone = request()->param('phone');
        $user_id = request()->param('user_id');
        $rand_code = request()->param('rand_code');

        if ($rand_code != Cache::get('reset_password')) {
            return ['code' => APICODE_ERROR, 'msg' => '验证码错误'];
        } else {

            $users = Db::name('user')->where(['PassengerPhone' => $phone])->find();
            if (!empty($users)) {
                return ['code' => APICODE_ERROR, 'msg' => '绑定手机号已存在'];
            }

            $ini['PassengerPhone'] = $phone;
            $ini['id'] = $user_id;

            $user = Db::name('user')->update($ini);

            if ($user) {
                $users = Db::name('user')->where(['id' => $user_id])->find();
                return ['code' => APICODE_SUCCESS, 'msg' => '绑定成功', 'data' => $users];
            } else {
                return ['code' => APICODE_ERROR, 'msg' => '绑定失败'];
            }
        }
    }

    //新绑定手机号
    public function NewBindingPhone()
    {
        $phone = request()->param('phone');
        $unionid = request()->param('unionid');
//        $user_id = request()->param('user_id');
        $rand_code = request()->param('rand_code');
        $user_id = 0;
        if ($rand_code != Cache::get('reset_password')) {
            return ['code' => APICODE_ERROR, 'msg' => '验证码错误'];
        } else {

            $users = Db::name('user')->where(['PassengerPhone' => $phone])->find();
            if (!empty($users)) {
                return ['code' => APICODE_ERROR, 'msg' => '绑定手机号已存在'];
            }

            $ini['PassengerPhone'] = $phone;
            //创建
            $city_id = Db::name('cn_city')->where(['initial' => input('city')])->value('id');
            if(!empty($city_id)){
                $ini['city_id'] = $city_id;
            }
            $ini['create_time'] = time();
            $ini['openid'] = input('openid');
            $ini['PassengerGender'] = input('PassengerGender');
            $ini['nickname'] = input('nickname');
            $ini['portrait'] = input('portrait');
            $ini['unionid'] = input('unionid');
            $ini['logon_tip'] = input('logon_tip');

            $user_id = Db::name('user')->insertGetId($ini);

            if ($user_id > 0) {

                $m = new Marketing();
                $active = $m->judgeActivity($user_id, $city_id, 1, '');
                $users = Db::name('user')->where(['id' => $user_id])->find();

                $active_active = 0;
                if ($active) {
                    $active_active = 1;
                    $users["active_active"] = $active_active;
                } else {
                    $users["active_active"] = $active_active;
                }
                return ['code' => APICODE_SUCCESS, 'msg' => '绑定成功', 'data' => $users];
            } else {
                return ['code' => APICODE_ERROR, 'msg' => '绑定失败'];
            }
        }
    }

    //创建用户(ios微信登录)
    public function WeChatLogin()
    {
        $params = [
            "city" => input('?city') ? input('city') : null,
            "openid" => input('?openid') ? input('openid') : null,
            "unionid" => input('?unionid') ? input('unionid') : null,
            "PassengerGender" => input('?PassengerGender') ? input('PassengerGender') : null,
            "nickname" => input('?nickname') ? input('nickname') : null,
            "portrait" => input('?portrait') ? input('portrait') : null,
            "logon_tip" => input('?logon_tip') ? input('logon_tip') : null,
        ];

        $params = $this->filterFilter($params);
        $required = ["city", "openid", "PassengerGender", "nickname", "portrait", "unionid"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }
        $flag = 0;
        $user = Db::name('user')->where(['unionid' => input('unionid')])->find();
        if ($user) {  //存在
            return [
                'code' => APICODE_SUCCESS,
                'msg' => '成功',
                'data' => $user,
                'flag' => $flag
            ];
        } else {
            //若openid存在，则更新该用户的openid的unionid
            $user = Db::name('user')->where(['openid' => input('openid')])->find();
            if ($user) {
                Db::name('user')->where(['openid' => input('openid')])->setField("unionid", input('unionid'));
                return [
                    'code' => APICODE_SUCCESS,
                    'msg' => '成功',
                    'data' => $user,
                    'flag' => $flag
                ];
            } else {
                $flag = 2;
                return [
                    'code' => APICODE_SUCCESS,
                    'msg' => '返回信息',
                    'data' => $params,
                    'flag' => $flag
                ];
            }
        }
    }

    //实名认证
    public function Certification()
        {
            $params = [
                "id" => input('?id') ? input('id') : null,
                "PassengerName" => input('?PassengerName') ? input('PassengerName') : null,
                "number" => input('?number') ? input('number') : null,
            ];

            $params = $this->filterFilter($params);
            $required = ["id", "PassengerName", "number"];
            if (!$this->checkRequire($required, $params)) {
                return [
                    "code" => APICODE_FORAMTERROR,
                    "msg" => "必填项不能为空，请检查输入"
                ];
            }
            $params['is_attestation'] = 1; // 已认证

            $res = Db::name('user')->update($params);

            $m = new Marketing();
            $city_id = Db::name('user')->where(['id' => input('id')])->value('city_id');
            $m->judgeActivity(input('id'), $city_id, 7, '');

            if ($res > 0) {
                return [
                    "code" => APICODE_SUCCESS,
                    "msg" => "更新成功",
                ];
            } else {
                return [
                    "code" => APICODE_ERROR,
                    "msg" => "更新失败",
                ];
            }
        }

    //上传头像
    public function UploadAvatar()
        {
            $params = [
                "id" => input('?id') ? (int)input('id') : null,
                "portrait" => input('?portrait') ? input('portrait') : null,
            ];
            $params = $this->filterFilter($params);
            $required = ["id", "portrait"];
            if (!$this->checkRequire($required, $params)) {
                return [
                    "code" => APICODE_FORAMTERROR,
                    "msg" => "必填项不能为空，请检查输入"
                ];
            }

            $user = Db::name('user')->update($params);

            return [
                "code" => $user > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "上传成功",
            ];
        }

    //更改昵称
    public function ChangeNickname()
    {
        $params = [
            "id" => input('?id') ? input('id') : null,
            "nickname" => input('?nickname') ? input('nickname') : null,
        ];
        $params = $this->filterFilter($params);
        $required = ["id", "nickname"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $user = Db::name('user')->update($params);

        return [
            "code" => $user > 0 ? APICODE_SUCCESS : APICODE_EMPTYDATA,
            "msg" => "更改成功",
        ];
    }

    //获取车型
    public function getArctic(){
        if (input('?business_id')) {
            $params = [
                "business_id" => input('business_id')
            ];
            $data = db('business_type')->where($params)->select();
            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "业务ID不能为空"
            ];
        }
    }

    //开通城市
    public function dredge(){
        //查询公司里面的城市
        $company = Db::name('company')->alias('c')
            ->field('cn.id,cn.name')
            ->join('mx_cn_city cn', 'cn.id = c.city_id and cn.is_dredge = 1', 'inner')
            ->select();   //开通城市

        return ['code' => APICODE_SUCCESS, 'data' => $company];
    }

    //用户端手机号判断是否注册过接口
    public function whetherRegister(){
        $phone = input('phone');

        $user = Db::name('user')->where(['PassengerPhone' => $phone])->find();

        $flag = 0;
        if ($user) {
            $flag = 1;
            return ['code' => APICODE_SUCCESS, 'msg' => '注册过', 'flag' => $flag, 'city_id' => $user['city_id']];
        } else {
            return ['code' => APICODE_ERROR, 'msg' => '未注册', 'flag' => $flag];
        }
    }

    //修改手机号
    public function UpdatePhone(){
        $phone = request()->param('phone');
        $user_id = request()->param('user_id');
        $rand_code = request()->param('rand_code');

        if ($rand_code != Cache::get('reset_password')) {
            return ['code' => APICODE_ERROR, 'msg' => '验证码错误'];
        } else {

            //判断更改的手机号，是否存在，不存在才能更改.
            $users = Db::name('user')->where(['PassengerPhone' => $phone])->find();
            if( !empty($users)){
                return ['code' => APICODE_ERROR, 'msg' => '更换手机已存在，请输入其他手机号'];
            }

            $ini['PassengerPhone'] = $phone;
            $ini['id'] = $user_id;

            $user = Db::name('user')->update($ini);

            if ($user) {
                return ['code' => APICODE_SUCCESS, 'msg' => '更换成功'];
            } else {
                return ['code' => APICODE_ERROR, 'msg' => '更换失败'];
            }
        }
    }

    //用户注销
    public function logout(){
        if (input('?id')) {
            $params = [
                "id" => input('id'),
                "status" => 2
            ];
            $user = db('user')->update($params);
            if ($user) {
                return ['code' => APICODE_SUCCESS, 'msg' => '注销成功'];
            } else {
                return ['code' => APICODE_ERROR, 'msg' => '注销失败'];
            }
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //添加紧急联系人
    public function AddUrgencyContact(){
        $params = [
            "user_id" => input('?user_id') ? input('user_id') : 0,
            "name" => input('?name') ? input('name') : '',
            "phone" => input('?phone') ? input('phone') : '',
        ];

        $params = $this->filterFilter($params);
        $required = ["user_id", "name", "phone"];
        if (!$this->checkRequire($required, $params)) {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "必填项不能为空，请检查输入"
            ];
        }

        $user_contact = Db::name('user_contact')->insert($params);

        if ($user_contact) {
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

    //查询紧急联系人
    public function QueryUserContact(){
        if (input('?user_id')) {
            $params = [
                "user_id" => input('user_id')
            ];

            $data = db('user_contact')->where($params)->select();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "用户ID不能为空"
            ];
        }
    }

    //删除紧急联系人
    public function DELUserContact(){
        if (input('?id')) {
            $params = [
                "id" => input('id')
            ];

            $data = db('user_contact')->where($params)->delete();

            return [
                "code" => $data ? APICODE_SUCCESS : APICODE_EMPTYDATA,
                "msg" => "查询成功",
                "data" => $data
            ];
        } else {
            return [
                "code" => APICODE_FORAMTERROR,
                "msg" => "紧急联系人ID不能为空"
            ];
        }
    }
}