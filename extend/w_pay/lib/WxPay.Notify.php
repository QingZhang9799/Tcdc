<?php
/**
 * 
 * 回调基础类
 * @author widyhu
 *
 */
use think\Db;
class WxPayNotify extends WxPayNotifyReply
{
	private $config = null;
	/**
	 * 
	 * 回调入口
	 * @param bool $needSign  是否需要签名返回
	 */
	final public function Handle($config, $needSign = true)
	{

		$this->config = $config;
		$msg = "OK";
		\Log::DEBUG($msg);
		//当返回false的时候，表示notify中调用NotifyCallBack回调失败获取签名校验失败，此时直接回复失败
		$result = WxpayApi::notify($config, array($this, 'NotifyProcess'), $msg);
		\Log::DEBUG('WxpayApi::notify:'.json_encode($result));
		
		if($result == false){
			$this->SetReturn_code("FAIL");
			$this->SetReturn_msg($msg);
			$this->ReplyNotify(false);
			return;
		} else {
			//该分支在成功回调到NotifyCallBack方法，处理完成之后流程
			$this->SetReturn_code("SUCCESS");
			$this->SetReturn_msg("OK");
		}
		$this->ReplyNotify($needSign);
	}
	
	/**
	 * 
	 * 回调方法入口，子类可重写该方法
	 	//TODO 1、进行参数校验
		//TODO 2、进行签名验证
		//TODO 3、处理业务逻辑
	 * 注意：
	 * 1、微信回调超时时间为2s，建议用户使用异步处理流程，确认成功之后立刻回复微信服务器
	 * 2、微信服务器在调用失败或者接到回包为非确认包的时候，会发起重试，需确保你的回调是可以重入
	 * @param WxPayNotifyResults $objData 回调解释出的参数
	 * @param WxPayConfigInterface $config
	 * @param string $msg 如果回调处理失败，可以将错误信息输出到该方法
	 * @return true回调出来完成不需要继续回调，false回调处理未完成需要继续回调
	 */
	public function NotifyProcess($objData, $config, &$msg)
	{
		//TODO 用户基础该类之后需要重写该方法，成功的时候返回true，失败返回false
		\Log::DEBUG('NotifyProcess:'.json_encode($objData));
		$data = $objData->GetValues();
		\Log::DEBUG(var_export($data),true);
		//TODO 1、进行参数校验
		if(!array_key_exists("return_code", $data)
			||(array_key_exists("return_code", $data) && $data['return_code'] != "SUCCESS")) {
			//TODO失败,不是支付成功的通知
			//如果有需要可以做失败时候的一些清理处理，并且做一些监控
			$msg = "异常异常";
			return false;
		}
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}

		//TODO 2、进行签名验证
		try {
			$checkResult = $objData->CheckSign($config);
			if($checkResult == false){
				//签名错误
				\Log::ERROR("签名错误...");
				return false;
			}
		} catch(Exception $e) {
			\Log::ERROR(json_encode($e));
		}

		//TODO 3、处理业务逻辑
		\Log::DEBUG("call back:" . json_encode($data));
		$notfiyOutput = array();



		Db::startTrans();
		try {
			Log::DEBUG('我的业务逻辑');
			$out_trade_no = $data['out_trade_no']; //订单号
			Log::DEBUG('-------------WZ微信订单号1：' . $out_trade_no . '---------------');
			$acount = $data['total_fee'];  //订单总价格
			Log::DEBUG('-------------WZ微信订单总价格：' . $acount . '---------------');
			$attach = $data['attach']; //交易类型：1：充值；2：支付
//			$body = explode(',',$body);
//			$trade_type = $body[0];/*交易类型*/
//			$order_type = $body[1];/*订单类型*/
			Log::DEBUG('-------------WZ微信订单类型：' . $attach . '---------------');

			if($attach == 1){//支付
                $orderinfo = Db::name('order')->where('order_code',$out_trade_no)->find();

                $checkinfo = $orderinfo['status'];

                if ($checkinfo==7) {
                    Log::DEBUG('-------------订单状态是未支付可以修改：-------------------'.$checkinfo);

                    // 启动事务
                    Db::startTrans();
                    try {
                        // 处理支付日志
                        Log::DEBUG('-------------成功-------------------');
//                        $datas['status'] = 6                                                                                            ;
//                        $datas['pay_time'] = time();
//
//                        $res = Db::name('order')->where('ordernum',$out_trade_no)->update($datas);
//
//                        //处理分钱
//                        companyMoney($orderinfo['conducteur_id'],$orderinfo['money'],$orderinfo,$data['discount_money']);

                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        Log::DEBUG('-------------报错-------------------'.$e->getMessage());
                        // 回滚事务
                        Db::rollback();
                        throw new Exception($e->getMessage());
                    }
                }else{
                    Log::DEBUG('-------------订单状态已经是已支付-------------------');
                    echo "success";
                }
			}elseif($attach == 2){//充值

			}
			Db::commit();
		} catch (\Exception $e) {
			Db::rollback();
			\Log::DEBUG(json_encode(['getMessage' => $e->getMessage(),'getFile' => $e->getFile()]));
		}

		return true;
	}
	public function Queryorder($transaction_id)
	{
		$input = new \WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);

		$config = new \WxPayConfig();
		$result = \WxPayApi::orderQuery($config, $input);
		Log::DEBUG("query:" . json_encode($result));
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}


	/**
	*
	* 业务可以继承该方法，打印XML方便定位.
	* @param string $xmlData 返回的xml参数
	*
	**/
	public function LogAfterProcess($xmlData)
	{
		return;
	}
	
	/**
	 * 
	 * notify回调方法，该方法中需要赋值需要输出的参数,不可重写
	 * @param array $data
	 * @return true回调出来完成不需要继续回调，false回调处理未完成需要继续回调
	 */
	final public function NotifyCallBack($data)
	{
		$msg = "OK";
		$result = $this->NotifyProcess($data, $this->config, $msg);
		
		if($result == true){
			$this->SetReturn_code("SUCCESS");
			$this->SetReturn_msg("OK");
		} else {
			$this->SetReturn_code("FAIL");
			$this->SetReturn_msg($msg);
		}
		return $result;
	}
	
	/**
	 * 
	 * 回复通知
	 * @param bool $needSign 是否需要签名输出
	 */
	final private function ReplyNotify($needSign = true)
	{
		//如果需要签名
		if($needSign == true && 
			$this->GetReturn_code() == "SUCCESS")
		{
			$this->SetSign($this->config);
		}

		$xml = $this->ToXml();
		$this->LogAfterProcess($xml);
		WxpayApi::replyNotify($xml);
	}
}