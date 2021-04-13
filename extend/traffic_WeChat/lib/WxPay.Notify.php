<?php
/**
 * 
 * 回调基础类
 * @author widyhu
 *
 */
use app\common\model\BeanConfig;
use app\common\model\BeanLog;
use app\common\model\Order;
use app\common\model\User;
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
		//当返回false的时候，表示notify中调用NotifyCallBack回调失败获取签名校验失败，此时直接回复失败
		$result = WxpayApi::notify($config, array($this, 'NotifyCallBack'), $msg);
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
        $data = $objData->GetValues();
        //echo "处理回调";
        Log::DEBUG("call back:" . json_encode($data));
        //TODO 2、进行签名验证
        try {
            $checkResult = $objData->CheckSign($config);
            if($checkResult == false){
                //签名错误
                Log::ERROR("签名错误...");
                return false;
            }
        } catch(Exception $e) {
            Log::ERROR(json_encode($e));
            return false;
        }
        //TODO 3、业务逻辑处理
        Log::DEBUG("开始处理业务逻辑");
//        $body = explode(',', $data['attach']); // [订单支付 = 1; 砖石充值 = 2;余额充值 = 3], [user_id], [number]
        //商户订单号
        $out_trade_no = $data['out_trade_no'];
        //微信交易号
        $trade_no = $data['transaction_id'];
        //交易总额
        $total_amount = $data['total_fee']/100;

//        $pay_status = $body[0];

        if($data['attach']==1){
            $orderinfo = Db::name('order')->where('order_code',$out_trade_no)->find();

            $checkinfo = $orderinfo['status'];

            if ($checkinfo==7) {
                Log::DEBUG('-------------订单状态是未支付可以修改：-------------------'.$checkinfo);

                // 启动事务
                Db::startTrans();
                try {
                    // 处理支付日志
                    Log::DEBUG('-------------成功-------------------');
                    $datas['status'] = 6                                                                                            ;
                    $datas['pay_time'] = time();

                    $res = Db::name('order')->where('ordernum',$out_trade_no)->update($datas);
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
        }elseif($data['attach'] == 2){
//
        }

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