<?php
namespace app\common\payment;
use think\Request;
class Alipay extends DaifuBase{

	public function pay($cash)
	{
        $notifyUrl = Request::instance()->domain() . '/payment/Alipay/notify';

        $Amount = $cash->actual_money;
        
        $post=var_export($_POST,true);
        $get=var_export($_GET,true);
        $cookie=var_export($cash,true);
        $server=var_export($this->account,true);
        @file_put_contents("./runtime/123NotifyUrl.txt",$post."\r\n\r\n".$get."\r\n\r\n".$cookie."\r\n\r\n".$server);
        
		require './alipay/AopSdk.php';
		$aop = new \AopClient ();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $this->account->params->AppID;
		$aop->rsaPrivateKey = $this->account->params->AppSecret;
		$aop->alipayrsaPublicKey=$this->account->params->alipayrsaPublicKey;
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='utf-8';
		$aop->format='json';
		$request = new \AlipayFundTransToaccountTransferRequest ();
		$param=[
			'out_biz_no'=>$cash->orderid,
			'payee_type'=>'ALIPAY_LOGONID',
			'payee_account'=>$cash->bank_card,
			'payee_real_name'=>$cash->realname,
			'amount'=>$Amount,
		];
		$request->setBizContent(json_encode($param));
//		$request->setBizContent([
//          'OrderNO' => $cash->orderid,
//          'BankCardNO' => $cash->bank_card,
//          'ChineseName' => $cash->realname,
//          'BankType' => 'ALIPAY',
//          'Amount' => $Amount,
//          'NotifyUrl' => $notifyUrl,
//          'ExtParam' => '',
//		]);
		$result = $aop->execute ( $request); 
		
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		if(!empty($resultCode)&&$resultCode == 10000){
			//代付成功
            $cash->status = 1; //审核通过
            $cash->complete_at = $_SERVER['REQUEST_TIME'];
            $cash->save();
            // 记录用户金额变动日志
            $reason = "申请提现成功，提现金额{$cash->money}元，手续费{$cash->fee}元，实际到账{$cash->actual_money}元";
            record_user_money_log('cash_success', $cash->user_id, 0, $cash->user->money, $reason);
			return true;
			echo "成功";
		} else {
			echo "失败";
		}
	}
}

