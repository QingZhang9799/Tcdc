<?php
namespace think;
use think\Db;
//thinkphp 5 版本-马淑霞
//数据库
/*
-- ----------------------------
-- max_manager 用户表，只需要一个字段关联 用户组权限表 group_id
-- max_authgroup 用户组权限表 id：主键， title:用户组中文名称， description:用户组功能描述， rules：用户组权限，status 状态：为1正常，为0禁用
-- ----------------------------
-- ----------------------------
-- Table structure for max_authgroup
-- ----------------------------
DROP TABLE IF EXISTS `max_authgroup`;
CREATE TABLE `max_authgroup` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(100) NOT NULL DEFAULT '',
  `description` char(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `rules` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

 */

class Authmenu{

   //默认配置
    protected $_config = array(
        'AUTH_GROUP'        => 'authgroup',        // 用户组数据表名
        'AUTH_USER'         => 'manager',            // 用户信息表
		'AUTH_MENU'         => 'common/common/auth.inc.php'            // 权限菜单配置文件路径
    );

	  /**
      * 检查权限
     */
    public function check($uid) {
		 $module = Request()->module();
         $controller = Request()->controller();
         $action = Request()->action();
		
		  $group_id = DB::name($this->_config['AUTH_USER'])->where('id',$uid)->value('group_id');
		  
		  $auth = $this->get_user_auth($controller,$action,$group_id);
		  if (!$auth[0]) {
			   return $auth;
		  }
		  else{
			  if($group_id==1){
				  $menu_left = $this->get_admin_menu($module,$auth[1]); 
			  }
			  else{
				  $menu_left = $this->get_user_menu($module,$auth[1],$group_id);
			  }
			  return array(true,$menu_left,$auth[2],$auth[3]); 
		  }
    }
	
	protected function get_admin_menu($module,$act_key= ""){
		require APP_PATH.$this->_config['AUTH_MENU'];
		$module = strtolower($module);
		$arr = array();
		foreach($acl_inc as $k=>$v){
			$a['title'] = $v['low_title'][0];
			$a['class'] = $v['low_title'][1];
			$a['set'] = isset($v['low_title'][2])?1:0;
			$a['active'] = 0;
			$a['low'] = array();
			foreach($v['low_leve'] as $key=>$val){
						unset($val['data']);
						foreach($val as $keyname=>$keyval){
							   //判断菜单是否做指向隐藏
							  if(strstr($keyval[0],"@")){
								  $appoint=str_replace('@','',$keyval[0]);
								  if(in_array($act_key,$keyval[1])){
									  if(isset($arr[$k]['low'])){
										foreach($arr[$k]['low'] as $ke=>$low){
											  if($appoint==$low['key']){
												$arr[$k]['low'][$ke]['active'] = 1;
												$arr[$k]['active']=1;
												break;
											 }
										 }
									  }
								  }
							  }
							 else{
									 $b['key'] = $key;
									 $b['keyname'] = $keyname;
									 $b['url']  =  $this->geturl($module,$key,$keyval[0]);
									 if(in_array($act_key,$keyval[1])){
										$b['active'] = 1;
										$a['active'] = 1;
									 }
									 else{
										$b['active'] = 0;
									 }
									 if(isset($arr[$k])){
										 if($b['active']==1){
											$arr[$k]['active']=$b['active'];
										 }
										 array_push($arr[$k]['low'],$b);
										 }
									 else{
										 $a['low'][] = $b;
										 }
							  }
						 }
					 if(!isset($arr[$k])){
						$arr[$k] = $a;
					 }
				}	
					 
		}
		return $arr;
	}
	
	protected function get_user_menu($module,$act_key= "",$group_id){
		require APP_PATH.$this->_config['AUTH_MENU'];
		$data = DB::name($this->_config['AUTH_GROUP'])->where('id',$group_id)->value('rules');
		$rules = unserialize($data);
		$module = strtolower($module);
		$arr = array();
		foreach($rules as $key=>$val){
			foreach($acl_inc as $k=>$v){
				 if(isset($v['low_leve'][$key])){
					unset($v['low_leve'][$key]['data']);
					$a['title'] = $v['low_title'][0];
					$a['class'] = $v['low_title'][1];
					$a['set'] = isset($v['low_title'][2])?1:0;
					$a['active'] = 0;
					$a['low'] = array();
					 foreach($v['low_leve'][$key] as $keyname=>$keyval){
							 if(strstr($keyval[0],"@")){
								  $appoint=str_replace('@','',$keyval[0]);
								  if(in_array($act_key,$keyval[1])){
									  if(isset($arr[$k]['low'])){
										foreach($arr[$k]['low'] as $ke=>$low){
											  if($appoint==$low['key']){
												$arr[$k]['low'][$ke]['active'] = 1;
												$arr[$k]['active']=1;
												break;
											 }
										 }
									  }
								  }
							  }
							 else{
									 $b['key'] = $key;
									 $b['keyname'] = $keyname;
									 $b['url']  =  $this->geturl($module,$key,$keyval[0]);
									 if(in_array($act_key,$keyval[1])){
										$b['active'] = 1;
										$a['active'] = 1;
									 }
									 else{
										$b['active'] = 0;
									 }
									 if(isset($arr[$k])){
										 if($b['active']==1){
											$arr[$k]['active']=$b['active'];
										 }
										 array_push($arr[$k]['low'],$b);
										 }
									 else{
										 $a['low'][] = $b;
										 }
							  }
						 }
				   
					   if(!isset($arr[$k])){
						$arr[$k] = $a;
					   }
				 }
			
			}
			
		}
		return $arr;
	}
	protected function geturl($module,$key,$url){
		if(strstr($url,"?")){
			$params = strstr($url, '?');//加了？的参数
			$rurl = str_replace($params,'',$url);//去掉参数的方法名

			return url($module.'/'.$key.'/'.$rurl).$params;
		}
		else{
			return url($module.'/'.$key.'/'.$url);
		}

	}
	
	protected function get_user_auth($controller,$action,$group_id)
	{
		$model = strtolower($this->parse_name($controller, 0));
		$action=strtolower($action);

		require APP_PATH.$this->_config['AUTH_MENU'];
		$inc = $acl_inc;
		//获取唯一值
		$acl_key = $this->acl_get_key($model,$action,$inc);

		if($acl_key=='0'){
			return array(false,'权限菜单配置缺少这个方法！');
		}
		else{
			$array_model = array();
			foreach($inc as $key => $v){
					if(isset($v['low_leve'][$model])){
						$array_model = $v['low_leve'][$model];
						continue;
					}
			}//找到auth.inc中对当前模块的定义的数组
			$operate='';
			$module='';
			unset($array_model['data']);
	        foreach($array_model as $key => $val){
	        	foreach($val[1] as $k => $v){
	        	      if($v==$acl_key){
	        	     	$operate=$k;
			            $module=$key;
	                    break; 
	        	    }
	            }
	        }
			if($group_id==1){
				return array(true,$acl_key,$operate,$module);
				}
			else{
				$al      = $this->get_group_data($group_id);
				$acl     = $al['rules'];
				if(empty($acl)){
					return array(false,'管理组没有任何权限！');
				}
				else{
					if (array_keys($acl[$model], $acl_key)) {
						return array(true,$acl_key,$operate,$module);
					} else {
						return array(false,'您没有操作权限！');
					}
				}
			}

		}

	}
	
	
	protected function acl_get_key($model,$action,$inc){
		$keys = array($model,'data','eq_'.$action);
		
		$array = array();
		foreach($inc as $key => $v){
				if(isset($v['low_leve'][$model])){
					$array = $v['low_leve'];
					continue;
				}
		}//找到auth.inc中对当前模块的定义的数组
		
		$num = count($keys);
		$num_last = $num - 1;
		$this_array_0 = &$array;
		$last_key = $keys[$num_last];
		
		for ($i = 0; $i < $num_last; $i++){
			$this_key = $keys[$i];
			$this_var_name = 'this_array_' . $i;
			$next_var_name = 'this_array_' . ($i + 1);        
			if (!array_key_exists($this_key, $$this_var_name)) {            
				break;       
			}        
			$$next_var_name = &${$this_var_name}[$this_key];    
		}    

		if(!array_key_exists($last_key,${$next_var_name})){
			return '0';
		}
		/*取得条件下的数组  ${$next_var_name}得到data数组 $last_key即$keys = array($model,'data','eq_'.$action);里面的'eq_'.$action,所以总的组成就是，在auth.inc数组里找到键为$model的数组里的键为data的数组里的键为'eq_'.$action的值;*/
		$actions = ${$next_var_name}[$last_key];//这个值即为当前action的别名,然后用别名与用户的权限比对,如果是带有参数的条件则$actions是数组，数组里有相关的参数限制

		//if($actions){

		//}

		if(is_array($actions)){
			foreach($actions as $key_s => $v_s){
				$ma = true;
				if(isset($v_s['POST'])){
					if(!empty($v_s['POST'])){
						foreach($v_s['POST'] as $pkey => $pv){
							switch($pv){
								case 'G_EMPTY';//必须为空
									if(input('?post.'.$pkey) && input('post.'.$pkey)!='' ) $ma = false;
								break;
							
								case 'G_NOTSET';//不能设置
									if( input('?post.'.$pkey) ) $ma = false;
								break;
							
								case 'G_ISSET';//必须设置
									if( !input('?post.'.$pkey) ) $ma = false;
								break;
							
								default;//默认
									if( !input('?post.'.$pkey) || strtolower(input('post.'.$pkey)) != strtolower($pv) ) $ma = false;
								break;
							}
						}
					}
					else{
						if(!request()->isPost()) $ma = false;
						}
				}
				
				if(isset($v_s['GET'])){
					if(!empty($v_s['GET'])){
						foreach($v_s['GET'] as $pkey => $pv){
							switch($pv){
								case 'G_EMPTY';//必须为空
									if( input('?get.'.$pkey) && input('get.'.$pkey)!='' ) $ma = false;
								break;
							
								case 'G_NOTSET';//不能设置
									if( input('?get.'.$pkey) ) $ma = false;
								break;
							
								case 'G_ISSET';//必须设置
									if( !input('?get.'.$pkey) ) $ma = false;
								break;
							
								default;//默认
									if( !input('?get.'.$pkey) || strtolower(input('get.'.$pkey)) != strtolower($pv) ) $ma = false;
								break;
							}
							
						}
					}
					else{
						if(!request()->isGet()) $ma = false;
						}
				}
				if($ma)	return $key_s;
				else $actions="0";
			}//foreach
		}else{
			return $actions;
		}
	}
	
	protected function get_group_data($gid = 0)
	{
		$gid  = intval($gid);
		$list = array();
		if ($gid == 0) {
			if (!cache("ACL_all")) {
				$_acl_data =  DB::name($this->_config['AUTH_GROUP'])->select();
				$acl_data  = array();
				foreach ($_acl_data as $key => $v) {
					$acl_data[$v['id']]               = $v;
					$acl_data[$v['id']]['rules'] = unserialize($v['rules']);
				}
				cache("ACL_all", $acl_data);
				$list = $acl_data;
			} else {
				$list = cache("ACL_all");
			}
		} else if (!cache("ACL_" . $gid)) {
			$_acl_data               =DB::name($this->_config['AUTH_GROUP'])->find($gid);
			$_acl_data['rules'] = unserialize($_acl_data['rules']);
			$acl_data                = $_acl_data;
			cache("ACL_" . $gid, $acl_data);
			$list = $acl_data;
		} else {
			$list = cache("ACL_" . $gid);
		}
	
		return $list;
	}
	
	protected function parse_name($name, $type=0) {
	    if ($type) {
	        // return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
            $str = preg_replace_callback('/([-_]+([a-z]{1}))/i',function($matches){
                       return strtoupper($matches[2]);
                        },$name);
            return ucfirst($str);
	    } else {
	        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
	    }
	 }

}
