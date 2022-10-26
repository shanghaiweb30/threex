<?php
namespace app\common\payment;
use think\Request;
class Alipay extends Alipay
{

public function pay($cash)
    {
        $notifyUrl = Request::instance()->domain() . '/payment/Alipay/notify';

        $Amount = $cash->actual_money;
		
$aop = new AopClient ();
$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
$aop->appId = $this->account->params->AppID;
$aop->rsaPrivateKey = $this->account->params->AppSecret;
$aop->alipayrsaPublicKey=$this->account->params->alipayrsaPublicKey;
$aop->apiVersion = '1.0';
$aop->signType = 'RSA2';
$aop->postCharset='GBK';
$aop->format='json';
$request = new AlipayFundTransToaccountTransferRequest ();
$request->setBizContent(

            'OrderNO' => $cash->orderid,
            'BankCardNO' => $cash->bank_card,
            'ChineseName' => $cash->realname,
            'BankType' => 'ALIPAY',
            'Amount' => $Amount,
            'NotifyUrl' => $notifyUrl,
            'ExtParam' => '',

);
$result = $aop->execute ( $request); 

$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
$resultCode = $result->$responseNode->code;
if(!empty($resultCode)&&$resultCode == 10000){
echo "成功";
} else {
echo "失败";
}
    /**
     * @param $OrderNO 必填    接入商自己产生订单号（务超过21位）
     * @param $BankCardNO 必填    收款人的银行卡号
     * @param $ChineseName 必填    收款人姓名（需要进行URL编码）
     * @param $BankType 选填    开户行银联号
     * @param $Amount 必填    金额（单位元，精确度到分）
     * @param $ExtParam 选填    自定义扩展参数（需要进行URL编码）
     */