<?php

/**获取 某个表的值
 * @param $tablename
 * @param $where
 * @param $value
 * @return mixed
 */
function get_value($tablename,$where,$value)
{
   return \think\Db::name($tablename)->where($where)->value($value);
}

/**

 * 对查询结果集进行排序

 * @access public

 * @param array $list   查询结果

 * @param string $field 排序的字段名

 * @param array $sortBy 排序类型

 *                      asc正向排序 desc逆向排序 nat自然排序

 * @return array|bool

 */

function list_sort_by($list, $field, $sortBy = 'asc')

{

    if (is_array($list)) {

        $refer = $resultSet = [];

        foreach ($list as $i => $data)

            $refer[$i] = &$data[$field];

        switch ($sortBy) {

            case 'asc': // 正向排序

                asort($refer);

                break;

            case 'desc': // 逆向排序

                arsort($refer);

                break;

            case 'nat': // 自然排序

                natcasesort($refer);

                break;

        }

        foreach ($refer as $key => $val)

            $resultSet[] = &$list[$key];



        return $resultSet;

    }



    return false;

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