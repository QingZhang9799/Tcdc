<?php

namespace app\wxapi\controller;


use think\Controller;
include_once ROOT_PATH.'extend/Wexin_share/wy.php';
class Wxmenu extends Controller
{
    public function index()
    {
        $data = '{
                    "button": [
                        {
                            "name": "一键打车",
                            "sub_button": [                          
                                {
                                    "type": "location_select",
                                    "name": "普通出租",
                                    "key": "https://php.51jjcx.com/location.php"
                                }
                            ]
                        },
                        {
                           "type": "view",
                            "name": "同城打车",
                            "url": "https://php.51jjcx.com/home/Dache/index"
                        },
                        {
                             "type": "view",
                            "name": "个人中心",
                            "url": "https://php.51jjcx.com/wxapi/Indexs/index"
                        }
                    ]
                }';
        $wxObj = new \Wy("wx78c9900b8a13c6bd","a1391017fa573860e266fd801f2b0449");
        $res = $wxObj->_createMenu($data);//创建菜单
        echo json_encode($res) ;
        if($res == 1){
           echo "成功";
        }else{
            echo "失败";
        }
    }
}