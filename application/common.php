<?php
//error_reporting(E_ERROR | E_WARNING | E_PARSE);
error_reporting(0);
// +----------------------------------------------------------------------

// | ThinkPHP [ WE CAN DO IT JUST THINK ]

// +----------------------------------------------------------------------

// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.

// +----------------------------------------------------------------------

// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )

// +----------------------------------------------------------------------

// | Author: 流年 <liu21st@gmail.com>

// +----------------------------------------------------------------------

date_default_timezone_set("PRC");

use think\Loader;


use think\Exception;


include_once ROOT_PATH . 'extend/Alipush/aliyun-php-sdk-core/Config.php';


include ROOT_PATH . 'extend/Alipush/aliyun-php-sdk-push/Push/Request/V20160801/PushRequest.php';


use \Push\Request\V20160801 as Push;


include_once ROOT_PATH . 'extend/php/api_sdk/Dysmsapi/Request/V20170525/SendSmsRequest.php';


include_once ROOT_PATH . 'extend/php/api_sdk/Dysmsapi/Request/V20170525/QuerySendDetailsRequest.php';


function newyingye($shopid)
{
    $weekarray = array("business_hours_seven", "business_hours_one", "business_hours_two", "business_hours_three", "business_hours_four", "business_hours_five", "business_hours_six");
    return \think\Db::name('merchants_shop')->where('id', $shopid)->value($weekarray[date("w")]);
}


// 发短信
/*$mobile 手机号*/
/*$randcode 随机号码*/
/*$moban 模板*/
/*$Name 签名*/
function sendSMSS($mobile, $randcode, $moban)
{
//    echo $mobile.$randcode.$moban.$Name;die;
    //此处需要替换成自己的AK信息
    $accessKeyId = "LTAI4FiSXKWeviJpNpP62VJ2";
//    $accessKeyId = config("sandmessage.key");
    $accessKeySecret = "mReXGv4kl9CEJXVKU7LzBHwfvC7HfW";
//    $accessKeySecret = config("sandmessage.secret");
    //短信API产品名
    $product = "Dysmsapi";
    //短信API产品域名
    $domain = "dysmsapi.aliyuncs.com";
    //暂时不支持多Region
    $region = "cn-beijing";
    //初始化访问的acsCleint
    $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
    DefaultProfile::addEndpoint($region, $region, $product, $domain);
    $acsClient = new DefaultAcsClient($profile);
    $request = new Dysmsapi\Request\V20170525\SendSmsRequest;
    //必填-短信接收号码
    $request->setPhoneNumbers($mobile);
    //必填-短信签名
    $request->setSignName("同城打车");
//    $request->setSignName(config("sandmessage.names"));
    //必填-短信模板Code
    $request->setTemplateCode($moban);
    //选填-假如模板中存在变量需要替换则为必填(JSON格式)
    $request->setTemplateParam(json_encode(Array(  // 短信模板中字段的值
        "code" => $randcode,
    )));
    //选填-发送短信流水号
    $request->setOutId("1234");
    //发起访问请求
    $acsResponse = $acsClient->getAcsResponse($request);
//    dump($acsResponse);die;
    return $acsResponse;


}


// 短信通知  yangyanghong
/*$mobile 手机号*/
/*$randcode 随机号码*/
/*$moban 模板*/
/*$mtname 自定义模板  电话号码*/
/*$submittime 自定义模板  时间*/


function consoleSMSS($mobile, $rand_code, $moban, $mtname, $submittime)
{

    //此处需要替换成自己的AK信息

    $accessKeyId = config("console_sandmessage.key");

    $accessKeySecret = config("console_sandmessage.secret");
    //短信API产品名
    $product = "Dysmsapi";
    //短信API产品域名
    $domain = "dysmsapi.aliyuncs.com";
    //暂时不支持多Region
    $region = "cn-hangzhou";

    //初始化访问的acsCleint
    $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
    DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);
    $acsClient = new DefaultAcsClient($profile);

    $request = new Dysmsapi\Request\V20170525\SendSmsRequest;
    //必填-短信接收号码
    $request->setPhoneNumbers($mobile);
    //必填-短信签名
//    $request->setSignName("大和商贸");
    $request->setSignName(config("console_sandmessage.names"));
    //必填-短信模板Code
    $request->setTemplateCode($moban);
    //选填-假如模板中存在变量需要替换则为必填(JSON格式)

    $request->setTemplateParam(json_encode(Array(  // 短信模板中字段的值
        "mtname" => $mtname,
        "submittime" => $submittime,
    )));
    //选填-发送短信流水号
    $request->setOutId($rand_code);

    //发起访问请求
    $acsResponse = $acsClient->getAcsResponse($request);
    return $acsResponse;
}


// 应用公共文件

/**
 * 模拟tab产生空格
 * @param int $step
 * @param string $string
 * @param int $size
 * @return string
 */

function tab($step = 1, $string = ' ', $size = 4)

{

    return str_repeat($string, $size * $step);

}


/**
 * 获取自定义配置
 * @return int|string
 */

function get_conf($name)

{

    $conf = config("web_config." . $name);

    return $conf;

}

/**
 * 加密盐
 * md5(md5(password)+{salt})
 * @return string $password 密码明文
 */

function encrypt_salt($password)
{

    $salt = 'goodluck';

    $md5pass = md5(md5($password) . '{' . $salt . '}');

    return $md5pass;

}


/**阿里消息推送
 * @param $title  标题
 * @param $con   内容
 * @param $times  时间
 * @param $type  类型 1 活动通知  2 系统消息
 * @param $types 1 司机端  2 用户端 3 商家端
 *
 * info  跳转地址  1 商家呼叫配送  10催一下 11呼叫骑手 12下单通知商家 13接单
 *
 * ordernum 订单号 可为空
 *
 * mode 1外卖 2到店吃 3订位
 * music 用户default 商家 骑手 ShopSound.mp3
 * @param $user  用户
 *
 *



 */


function push_user($title, $con, $times, $type, $types, $info, $user = 'ALL', $ordernum = '', $mode, $music = 'default', $longitude = '1', $latitude = '1')

{

    $accessKeyId = config("alipush.accessKeyId");

    $accessKeySecret = config("alipush.accessKeySecret");

    $android_arr = explode("|", config("alipush.android_appKey"));

    $ios_arr = explode("|", config("alipush.ios_appKey"));


    if ($types == 1) {

        $android_key = $android_arr[0];

        $ios_key = $ios_arr[0];

    } elseif ($types == 2) {

        $android_key = $android_arr[1];

        $ios_key = $ios_arr[1];

    } elseif ($types == 3) {

        $android_key = $android_arr[2];

        $ios_key = $ios_arr[2];

    }


    $iClientProfile = DefaultProfile::getProfile("cn-hangzhou", $accessKeyId, $accessKeySecret);

    $client = new DefaultAcsClient($iClientProfile);

    $request = new Push\PushRequest();


// 推送目标


    if ($user == 'ALL') {

        $ABC = 'ALL';

    } else {

        $ABC = 'ACCOUNT';

    }


    $request->setTarget($ABC); //推送目标: DEVICE:推送给设备; ACCOUNT:推送给指定帐号,TAG:推送给自定义标签; ALL: 推送给全部

    $request->setTargetValue($user); //根据Target来设定，如Target=device, 则对应的值为 设备id1,设备id2. 多个值使用逗号分隔.(帐号与设备有一次最多100个的限制)

    $request->setDeviceType('ALL'); //设备类型 ANDROID iOS ALL.


    if ($type == 2) {

        $types = "MESSAGE";//消息

    } else {

        $types = "NOTICE";//通知

    }


    $request->setPushType($types); //消息类型 MESSAGE NOTICE

    $request->setTitle($title); // 消息的标题

    $request->setBody($con); // 消息的内容


// 推送配置: iOS


    $request->setiOSBadge(5); // iOS应用图标右上角角标


    $request->setiOSSilentNotification("false");//是否开启静默通知


    $request->setiOSMusic($music); // iOS通知声音


    $request->setiOSApnsEnv("PRODUCT");//iOS的通知是通过APNs中心来发送的，需要填写对应的环境信息。"DEV" : 表示开发环境 "PRODUCT" : 表示生产环境


    $request->setiOSRemind("false"); // 推送时设备不在线（既与移动推送的服务端的长连接通道不通），则这条推送会做为通知，通过苹果的APNs通道送达一次(发送通知时,Summary为通知的内容,Message不起作用)。注意：离线消息转通知仅适用于生产环境


    $request->setiOSRemindBody("iOSRemindBody");//iOS消息转通知时使用的iOS通知内容，仅当iOSApnsEnv=PRODUCT && iOSRemind为true时有效


    $request->setiOSExtParameters("{\"type\":\"$type\",\"times\":\"$times\",\"info\":\"$info\",\"ordernum\":\"$ordernum\",\"mode\":\"$mode\",\"longitude\":\"$longitude\",\"latitude\":\"$latitude\"}"); //自定义结构,开发者扩展用 针对iOS设备


// 推送配置: Android


    $request->setAndroidNotifyType("NONE");//通知的提醒方式 "VIBRATE" : 震动 "SOUND" : 声音 "BOTH" : 声音和震动 NONE : 静音

    // $request->setAndroidNotificationChannel(1);

    $request->setAndroidNotificationBarType(1);//通知栏自定义样式0-100


    $request->setAndroidOpenType("ACTIVITY");//点击通知后动作 "APPLICATION" : 打开应用 "ACTIVITY" : 打开AndroidActivity "URL" : 打开URL "NONE" : 无跳转

    $request->setAndroidNotificationChannel("1");

    $request->setAndroidOpenUrl("http://www.aliyun.com");//Android收到推送后打开对应的url,仅当AndroidOpenType="URL"有效


    $request->setAndroidActivity("com.alibaba.push2.demo.XiaoMiPushActivity");//设定通知打开的activity，仅当AndroidOpenType="Activity"有效


    $request->setAndroidMusic($music);//Android通知音乐


    $request->setAndroidXiaoMiActivity("com.ali.demo.MiActivity");//设置该参数后启动小米托管弹窗功能, 此处指定通知点击后跳转的Activity（托管弹窗的前提条件：1. 集成小米辅助通道；2. StoreOffline参数设为true


    $request->setAndroidXiaoMiNotifyTitle("Mi Title");


    $request->setAndroidXiaoMiNotifyBody("Mi Body");


    $request->setAndroidExtParameters("{\"type\":\"$type\",\"times\":\"$times\",\"info\":\"$info\",\"ordernum\":\"$ordernum\",\"mode\":\"$mode\",\"longitude\":\"$longitude\",\"latitude\":\"$latitude\"}"); // 自定义扩展属性


// 推送控制


    $pushTime = gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 second'));//延迟3秒发送


    $request->setPushTime($pushTime);


    $expireTime = gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 day'));//设置失效时间为1天


    $request->setExpireTime($expireTime);


    $request->setStoreOffline("false"); // 离线消息是否保存,若保存, 在推送时候，用户即使不在线，下一次上线则会收到

//   dump($request);die;


    $request->setAppKey($ios_key);


    $client->getAcsResponse($request);


    $request->setAppKey($android_key);


    $response = $client->getAcsResponse($request);


    // print_r("\r\n");


    // print_r($response);


}


//安卓阿里推送

function and_push_user($title, $con, $times, $type, $types, $info, $user = 'ALL')


{


    // 设置你自己的AccessKeyId/AccessSecret/AppKey


//    $accessKeyId = "LTAIL5fG4l1A8qFm";


    $accessKeyId = config("android_alipush.accessKeyId");


//    $accessKeySecret = "VHJSr5r8QzYQk0kEUvWdD4M7iNArLU";


    $accessKeySecret = config("android_alipush.accessKeySecret");


//    $appKey = "24872889";


    $arr = explode("|", config("android_alipush.appKey"));


    if ($types == 1) {

        $key = $arr[0];

    } elseif ($types == 2) {

        $key = $arr[1];

    } elseif ($types == 3) {

        $key = $arr[2];

    }


    $appKey = $key;


    $iClientProfile = DefaultProfile::getProfile("cn-hangzhou", $accessKeyId, $accessKeySecret);


    $client = new DefaultAcsClient($iClientProfile);


    $request = new Push\PushRequest();


// 推送目标


    $request->setAppKey($appKey);


    $request->setTarget("ACCOUNT"); //推送目标: DEVICE:推送给设备; ACCOUNT:推送给指定帐号,TAG:推送给自定义标签; ALL: 推送给全部


    $request->setTargetValue($user); //根据Target来设定，如Target=device, 则对应的值为 设备id1,设备id2. 多个值使用逗号分隔.(帐号与设备有一次最多100个的限制)


    $request->setDeviceType("ALL"); //设备类型 ANDROID iOS ALL.


    if ($type == 2) {


        $types = "MESSAGE";//消息


    } else {


        $types = "NOTICE";//通知


    }


    $request->setPushType($types); //消息类型 MESSAGE NOTICE


    $request->setTitle($title); // 消息的标题


    $request->setBody($con); // 消息的内容


// 推送配置: iOS


    $request->setiOSBadge(5); // iOS应用图标右上角角标


    $request->setiOSSilentNotification("false");//是否开启静默通知


    $request->setiOSMusic($music); // iOS通知声音


    $request->setiOSApnsEnv("DEV");//iOS的通知是通过APNs中心来发送的，需要填写对应的环境信息。"DEV" : 表示开发环境 "PRODUCT" : 表示生产环境


    $request->setiOSRemind("false"); // 推送时设备不在线（既与移动推送的服务端的长连接通道不通），则这条推送会做为通知，通过苹果的APNs通道送达一次(发送通知时,Summary为通知的内容,Message不起作用)。注意：离线消息转通知仅适用于生产环境


    $request->setiOSRemindBody("iOSRemindBody");//iOS消息转通知时使用的iOS通知内容，仅当iOSApnsEnv=PRODUCT && iOSRemind为true时有效


    $request->setiOSExtParameters("{\"type\":\"$type\",\"times\":\"$times\"}"); //自定义结构,开发者扩展用 针对iOS设备


// 推送配置: Android


    $request->setAndroidNotifyType("BOTH");//通知的提醒方式 "VIBRATE" : 震动 "SOUND" : 声音 "BOTH" : 声音和震动 NONE : 静音


    $request->setAndroidNotificationBarType(1);//通知栏自定义样式0-100


    $request->setAndroidOpenType("ACTIVITY");//点击通知后动作 "APPLICATION" : 打开应用 "ACTIVITY" : 打开AndroidActivity "URL" : 打开URL "NONE" : 无跳转


    $request->setAndroidOpenUrl("http://www.aliyun.com");//Android收到推送后打开对应的url,仅当AndroidOpenType="URL"有效


    $request->setAndroidActivity("com.alibaba.push2.demo.XiaoMiPushActivity");//设定通知打开的activity，仅当AndroidOpenType="Activity"有效


    $request->setAndroidMusic($music);//Android通知音乐


    $request->setAndroidXiaoMiActivity("com.ali.demo.MiActivity");//设置该参数后启动小米托管弹窗功能, 此处指定通知点击后跳转的Activity（托管弹窗的前提条件：1. 集成小米辅助通道；2. StoreOffline参数设为true


    $request->setAndroidXiaoMiNotifyTitle("Mi Title");


    $request->setAndroidXiaoMiNotifyBody("Mi Body");


    $request->setAndroidExtParameters("{\"type\":\"$type\",\"times\":\"$times\",\"info\":\"$info\"}"); // 自定义扩展属性


// 推送控制


    $pushTime = gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 second'));//延迟3秒发送


    $request->setPushTime($pushTime);


    $expireTime = gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 day'));//设置失效时间为1天


    $request->setExpireTime($expireTime);


    $request->setStoreOffline("false"); // 离线消息是否保存,若保存, 在推送时候，用户即使不在线，下一次上线则会收到


    $response = $client->getAcsResponse($request);


    print_r("\r\n");


    print_r($response);


}

function yuyan($a,$b,$c){
    $yuyan = array(
        '1' => array(
            '商家id不能为空',
            '订单id不能为空',
            '订单号不能为空',
            '店铺id不能为空',
            '评价id不能为空',
            '商家回复不能为空',
            '内容不能为空',
            '商品数组不能为空',
            '订单有误',
            '查询成功',
            '无',
            '提交参数不能为空',
            '用户id不能为空',
            '该餐厅不存在',
            '银行卡号已存在',
            '成功',
            '失败',
            '保存成功',
            '禁止重复保存',
            '商家不存在',
            '签名数据为空',
            '签名失败',
            '订单已经取消了',
            '商品id不能为空',
            '餐厅ID不能为空',
            '没有符合要求的数据',
            '用户经度不能为空',
            '用户纬度不能为空',
            '商品数量不能为空',
            '参数有误',
            '删除成功',
            '删除失败',
            '真实姓名不能为空',
            '性别不能为空',
            '地址id不能为空',
            '小区不能为空',
            '详细收货地址不能为空',
            '金额不能为空',
            '充值最小金额不满足',
            '用餐方式不能为空',
            '处理方式不能为空',
            '仅外卖',
            '仅堂食',
            '预计时间不能为空',
            '不能取消了',
            '状态错误',
            '20位字符密码',
            '国际区号不能为空',
            '手机号不能为空',
            '验证码不能为空',
            '账号或者密码不正确',
            '您已被平台禁用账号，请与管理员联系',
            '登录成功',
            '该手机号未注册，请直接注册',
            '验证码已过期,请重新获取',
            '新密码不能为空',
            '请输入6-20位字符密码',
            '确认密码不能为空',
            '两次输入的密码不一样',
            '密码更改成功',
            '密码更改失败',
            '登录密码长度不正确',
            '请选择地址',
            '账号或者密码错误',
            '没有商品',
            '订单状态已经是已支付',
            '订单状态已取消',
            '错误',
            '订单支付成功',
            '呼叫成功',
        ),
        '2' => array(
            'Merchant’s ID cannot be empty',
            'Order’s ID cannot be empty',
            'Order No. cannot be empty',
            'Shop’s ID cannot be empty',
            'Customer’s ID cannot be empty',
            'Merchant’s reply cannot be empty',
            'Content cannot be empty',
            'Product’s data cannot be empty',
            'Incorrect Order',
            'Search successful',
            'No data',
            'Submit data cannot be empty',
            'User’s ID cannot be empty',
            'the Restaurant cannot be found',
            'Bank card number already exists',
            'Successful',
            'Failure',
            'Saved successfully',
            'Do not allow repeated saves',
            'Merchant does not exist',
            'Signature data is empty',
            'Signature failed ',
            'Order has been cancelled',
            'Product’s ID cannot be empty',
            'Restaurant’s ID cannot be empty',
            'No data that meets the requirements',
            'User longitude cannot be empty',
            'User latitude cannot be empty',
            'Product quantity cannot be empty',
            'Incorrect data',
            'Deleted successfully',
            'Deleted failed',
            'The legal name cannot be empty',
            'Gender cannot be empty',
            'Address’s ID cannot be empty',
            'District cannot be empty',
            'The detailed shipping address cannot be empty',
            'Amount cannot be empty',
            'The minimum amount of recharge is not satisfied',
            'Meal style cannot be empty',
            'Processing method cannot be empty',
            'Take out/Delivery only',
            'Eat in only',
            'Estimated time cannot be empty',
            'Cannot be cancelled',
            'Status failure',
            '20-character password',
            'International area code cannot be empty',
            'Phone number cannot be empty',
            'The verification cade cannot be empty',
            'Account No./Password is incorrect ',
            'You have been disabled by the platform,please contact the administrator',
            'Login successfully',
            'The phone number is not registered, please register directly',
            'The verification code has expired, please re-acquire',
            'The new password cannot be empty',
            'Please enter 6—12 character password',
            'Confirmed password cannot be empty',
            'The password entered is different',
            'Password change successfully',
            'Password change failed',
            'Login password l is incorrect',
            'Please choose the address',
            'Error in account or password',
            'No merchandise',
            'Order status is already paid',
            'Order status cancelled',
            'error',
            'Order Payment Successful',
            'call success',
        )

    );
    if($c==1){
        return $yuyan['1'];
    }else{
        return $yuyan[$a][$b];
    }

}



function aweek($gdate = "", $first = 0){

    if(!$gdate) $gdate = date("Y-m-d");

    $w = date("w", strtotime($gdate));//取得一周的第几天,星期天开始0-6

    $dn = $w ? $w - $first : 6;//要减去的天数

    //本周开始日期

    $st = date("Y-m-d", strtotime("$gdate -".$dn." days"));

    //本周结束日期

    $en = date("Y-m-d", strtotime("$st +6 days"));

    //上周开始日期

    $last_st = date('Y-m-d',strtotime("$st - 7 days"));

    //上周结束日期

    $last_en = date('Y-m-d',strtotime("$st - 1 days"));

    return array($st, $en,$last_st,$last_en);//返回开始和结束日期

}



define("APICODE_SUCCESS",200);

define("APICODE_ERROR",400);
define("APICODE_TIMEOUT",300);
define("APICODE_DATABASEERROR",201);
define("APICODE_NOTFOUND",404);
define("APICODE_SYSTEMERROR",500);
define("APICODE_FORAMTERROR",202);
define("APICODE_EMPTYDATA",203);
define("APICODE_LOGINREQUEST",205);
define("APICODE_NOPOWER",206);