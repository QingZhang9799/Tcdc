<?php
/** 活动管理模型
 * Created by PhpStorm.
 * User: yangyanghong
 * Date: 19-2-28
 * Time: 下午5:42
 */

namespace app\api\model;

class Company extends Base
{
    // 指定表名,不含前缀
    protected $name = 'company';

    /**
     * [saveVerify description]模型验证 添加和修改
     * @param  string $id [description]主键id
     * @return [type]     [description]
     */
    public static function saveVerify($data,$id = ''){

        $Model = new  Company();

        //判断是添加还是修改
        $handle = empty($id)? false : true;

        // 调用验证器类进行数据验证
        $result =  $Model->validate(true)->allowField(true)->isUpdate($handle)->save($data);

        if(false === $result){
            // 验证失败 输出错误信息
            return $Model->getError();
        }else{
            return true;
        }
    }



}