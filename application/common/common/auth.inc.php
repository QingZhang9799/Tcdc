<?php

/*array(菜单名，菜单样式，是否显示)*/

//error_reporting(E_ALL);

/*

$acl_inc[$i]['low_leve']['global']  global是model

每个action前必须添加eq_前缀'eq_websetting'  => 'at1','at1'表示唯一标志,可独自命名,eq_后面跟的action必须统一小写

*/

$acl_inc =  array();

$i=0;



$acl_inc[$i]['low_title'] = array('控制台','fa fa-home',1);

$acl_inc[$i]['low_leve']['dashboard']= array( "控制台" =>array('index',

											array(

												 "列表" 		=> 'board',

												)

											),

										   "data" => array(

										   		//控制台

												'eq_index'  => 'board',

											)

							);

$i++;

$acl_inc[$i]['low_title'] =  array('全局设置','fa fa-cog');

$acl_inc[$i]['low_leve']['webconfig']= array( "配置管理" =>array('index',

                                              array(

												 "列表" 		=> 'webconfig1',

												 "添加" 		=> 'webconfig2',

											  )),

										   "data" => array(

										   		//配置管理

												'eq_index'      => 'webconfig1',

												"eq_create" 	=> 'webconfig2',

											)

							);

//$acl_inc[$i]['low_leve']['webtype']= array( "配置组管理" =>array('index',
//
//                                              array(
//
//                                                "列表"       => "webtype1",
//
//                                                "添加"       => "webtype2",
//
//                                                "修改"       => "webtype3",
//
//                                        )),
//
//                                        "data" =>array(
//
//                                                "eq_index"        => "webtype1",
//
//                                                "eq_create"       => "webtype2",
//
//                                                "eq_renewfield"   => "webtype3",
//
//                                        ));

$i++;

$acl_inc[$i]['low_title'] =  array('管理模块','fa fa-user');

$acl_inc[$i]['low_leve']['manager']= array( "管理员管理" =>array('index',

                                              array(

												 "列表" 		=> 'man1',

												 "添加" 		=> 'man2',

												 "修改" 		=> 'man3',

												 "删除" 		=> 'man4',

											  )),

										   "data" => array(

										   		//配置管理

												'eq_index'  => 'man1',

												'eq_create'  => 'man2',

												'eq_update'  => 'man3',

												'eq_renewfield'  => 'man3',

												'eq_delete'  => 'man4',

											)

							);

// 管理组管理

$acl_inc[$i]["low_leve"]["authgroup"]= array( "管理组管理" =>array("index",

                                        array(

                                                "列表"       => "authgroup1",

                                                "添加"       => "authgroup2",

                                                "修改"       => "authgroup3",

                                                "删除"       => "authgroup4",

                                                "权限"       => "authgroup5",

                                        )),

                                        "data" =>array(

                                                "eq_index"        => "authgroup1",

                                                "eq_create"       => "authgroup2",

                                                "eq_update"       => "authgroup3",

                                                "eq_renewfield"   => "authgroup3",

                                                "eq_delete"       => "authgroup4",

                                                "eq_setup"        => "authgroup5",

                                        ));

// 管理员日志

$acl_inc[$i]["low_leve"]["manager_log"]= array( "管理员日志" =>array("index",

                                        array(

                                                "列表"       => "manager_log1",

                                                "删除"       => "manager_log4",

                                        )),

                                        "data" =>array(

                                                "eq_index"        => "manager_log1",

                                                "eq_delete"       => "manager_log4",

                                        ));






//$i++;
//
//$acl_inc[$i]['low_title'] =  array('会员列表管理','fa fa-file-text');
//
//$acl_inc[$i]["low_leve"]["user"]= array( "会员列表" =>array("index",
//
//    array(
//        "列表"       => "user1",
//        "更改状态"       => "user2",
//        "添加"       => "user3",
//        "删除"       => "user4",
//    )),
//
//    "data" =>array(
//        "eq_index"     => "user1",
//        "eq_renewfield"    => "user2",
//        "eq_create"    => "user3",
//        "eq_delete"    => "user4",
//
//    ));


$i++;
$acl_inc[$i]['low_title'] =  array('新闻模块','fa fa-file-text');
// 单页模块
$acl_inc[$i]["low_leve"]["infoclass"]= array( "新闻分类" =>array("index",
    array(
        "列表"       => "infoclass1",
        "添加"       => "infoclass2",
        "修改"       => "infoclass3",
        "删除"       => "infoclass4",
        "添加子分类"       => "infoclass5",
    )),
    "data" =>array(
        "eq_index"        => "infoclass1",
        "eq_create"       => "infoclass2",
        "eq_update"       => "infoclass3",
        "eq_renewfield"   => "infoclass3",
        "eq_delete"       => "infoclass4",
        "eq_son"       => "infoclass5",
    ));
//// 新闻管理
$acl_inc[$i]["low_leve"]["infolist"]= array( "新闻列表" =>array("index",
    array(
        "列表"       => "infolist1",
        "添加"       => "infolist2",
        "修改"       => "infolist3",
        "删除"       => "infolist4",
        "置顶"       => "infolist5",

    )),
    "data" =>array(
        "eq_index"        => "infolist1",
        "eq_create"       => "infolist2",
        "eq_update"       => "infolist3",
        "eq_renewfield"   => "infolist3",
        "eq_delete"       => "infolist4",
        "eq_top"       => "infolist5",

    ));





$i++;

$acl_inc[$i]['low_title'] =  array('单页模块','fa fa-file-text');

// 单页模块

$acl_inc[$i]["low_leve"]["info"]= array( "单页管理" =>array("index",

                                        array(

                                                "列表"       => "info1",

                                                "添加"       => "info2",

                                                "修改"       => "info3",

                                                "删除"       => "info4",

                                        )),

                                        "data" =>array(

                                                "eq_index"        => "info1",

                                                "eq_create"       => "info2",

                                                "eq_update"       => "info3",

                                                "eq_renewfield"   => "info3",

                                                "eq_delete"       => "info4",

                                        ));







$i++;
$acl_inc[$i]['low_title'] = array('轮播图管理', 'fa fa-file-text');
// 轮播图管理
$acl_inc[$i]["low_leve"]["shuffling"] = array("图片信息列表" => array("index",
    array(
        "列表" => "shuffling1",
        "添加" => "shuffling2",
        "修改" => "shuffling3",
        "删除" => "shuffling4",
    )),
    "data" => array(
        "eq_index" => "shuffling1",
        "eq_create" => "shuffling2",
        "eq_update" => "shuffling3",
        "eq_renewfield" => "shuffling3",
        "eq_delete" => "shuffling4",
    ));



//$i++;
//$acl_inc[$i]['low_title'] = array('综合业务', 'fa fa-file-text');
//// 综合业务
//$acl_inc[$i]["low_leve"]["sandmessage"] = array("阿里短信对接" => array("index",
//    array(
//        "列表" => "sandmessage1",
//        "添加" => "sandmessage2",
//        "修改" => "sandmessage3",
//        "删除" => "sandmessage4",
//    )),
//    "data" => array(
//        "eq_index" => "sandmessage1",
//        "eq_create" => "sandmessage2",
//        "eq_update" => "sandmessage3",
//        "eq_renewfield" => "sandmessage3",
//        "eq_delete" => "sandmessage4",
//    ));

//$i++;
//
//$acl_inc[$i]['low_title'] =  array('新闻模块','fa fa-list-ul');
//
//// 新闻分类
//
//$acl_inc[$i]["low_leve"]["infoclass"]= array( "新闻分类" =>array("index",
//
//                                        array(
//
//                                                "列表"       => "infoclass1",
//
//                                                "添加"       => "infoclass2",
//
//                                                "修改"       => "infoclass3",
//
//                                                "删除"       => "infoclass4",
//
//                                        )),
//
//                                        "data" =>array(
//
//                                                "eq_index"        => "infoclass1",
//
//                                                "eq_create"       => "infoclass2",
//
//                                                "eq_update"       => "infoclass3",
//
//                                                "eq_renewfield"   => "infoclass3",
//
//                                                "eq_delete"       => "infoclass4",
//
//                                        ));
//
//// 新闻管理
//
//$acl_inc[$i]["low_leve"]["infolist"]= array( "新闻管理" =>array("index",
//
//                                        array(
//
//                                                "列表"       => "infolist1",
//
//                                                "添加"       => "infolist2",
//
//                                                "修改"       => "infolist3",
//
//                                                "删除"       => "infolist4",
//
//                                        )),
//
//                                        "data" =>array(
//
//                                                "eq_index"        => "infolist1",
//
//                                                "eq_create"       => "infolist2",
//
//                                                "eq_update"       => "infolist3",
//
//                                                "eq_renewfield"   => "infolist3",
//
//                                                "eq_delete"       => "infolist4",
//
//                                        ));
//
//
//
//$i++;
//
//$acl_inc[$i]['low_title'] =  array('会员模块','fa fa-users');
//
//// 用户管理
//
//$acl_inc[$i]["low_leve"]["user"]= array( "会员管理" =>array("index",
//
//                                        array(
//
//                                                "列表"       => "user1",
//
//                                                "添加"       => "user2",
//
//                                                "修改"       => "user3",
//
//                                                "删除"       => "user4",
//
//                                        )),
//
//                                        "data" =>array(
//
//                                                "eq_index"        => "user1",
//
//                                                "eq_create"       => "user2",
//
//                                                "eq_update"       => "user3",
//
//                                                "eq_renewfield"   => "user3",
//
//                                                "eq_delete"       => "user4",
//
//                                        ));
//




//$i++;

//$acl_inc[$i]['low_title'] =  array('清理垃圾','fa fa-cut');

//// 清洁管理

//$acl_inc[$i]["low_leve"]["clear"]= array( "图片清理" =>array("index",

//                                        array(

//                                                "列表"       => "clear1",

//                                        )),

//                                        "临时文件清理" =>array("temp",

//                                        array(

//                                                "列表"       => "temp1",

//                                        )),

//                                        "日志清理" =>array("log",

//                                        array(

//                                                "列表"       => "log1",

//                                        )),

//                                        "缓存清理" =>array("cache",

//                                        array(

//                                                "列表"       => "cache1",

//                                        )),

//                                        "data" =>array(

//                                                "eq_index"        => "clear1",

//                                                "eq_temp"         => "temp1",

//                                                "eq_log"          => "log1",

//                                                "eq_cache"        => "cache1",

//                                        ));


//
//$i++;
//
//$acl_inc[$i]['low_title'] =  array('商品订单','fa fa-cubes');
//
//// 商品分类
//
//$acl_inc[$i]["low_leve"]["goods_type"]= array( "商品分类" =>array("index",
//
//                                        array(
//
//                                                "列表"       => "goods_type1",
//
//                                                "添加"       => "goods_type2",
//
//                                                "修改"       => "goods_type3",
//
//                                                "删除"       => "goods_type4",
//
//                                        )),
//
//                                        "data" =>array(
//
//                                                "eq_index"        => "goods_type1",
//
//                                                "eq_create"       => "goods_type2",
//
//                                                "eq_update"       => "goods_type3",
//
//                                                "eq_renewfield"   => "goods_type3",
//
//                                                "eq_delete"       => "goods_type4",
//
//                                        ));
//
//// 商品模块
//
//$acl_inc[$i]["low_leve"]["goods"]= array( "商品管理" =>array("index",
//
//                                        array(
//
//                                                "列表"       => "goods1",
//
//                                                "添加"       => "goods2",
//
//                                                "修改"       => "goods3",
//
//                                                "删除"       => "goods4",
//
//                                        )),
//
//                                        "data" =>array(
//
//                                                "eq_index"        => "goods1",
//
//                                                "eq_show"         => "goods1",
//
//                                                "eq_create"       => "goods2",
//
//                                                "eq_update"       => "goods3",
//
//                                                "eq_renewfield"   => "goods3",
//
//                                                "eq_getattr"      => "goods3",
//
//                                                "eq_delete"       => "goods4",
//
//                                        ));

//$i++;

//$acl_inc[$i]['low_title'] =  array('工具','fa fa-wrench');

//$acl_inc[$i]['low_leve']['formbuilder']= array( "表单构建器" =>array('index',

//                                              array(

//                                                 "列表"       => 'build',

//                                              )),

//                                           "data" => array(

//                                                'eq_index'  => 'build',

//                                            )

//                            );

//$acl_inc[$i]['low_leve']['generate']= array( "代码生成器" =>array('index',

//                                              array(

//                                                 "列表"       => 'gener1',

//                                              )),

//                                           "data" => array(

//                                                'eq_index'  => 'gener1',

//                                                'eq_run'  => 'gener1',

//                                                'eq_cmd'  => 'gener1',

//                                            )

//                            );

