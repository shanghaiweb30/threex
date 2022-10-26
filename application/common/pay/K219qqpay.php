<?php
/**
 * 219卡支付平台QQ钱包
 * 官方网址 http://www.219ka.com
 * 需要购买自动发卡平台源码请到 www.a8tg.com
 */
namespace app\common\pay;
use think\Request;
use app\common\Pay;
class K219qqpay extends Pay
{

    protected $gateway = '';
    protected $code='';
    protected $error='';

    public function getCode()
    {
        return $this->code;
    }

    public function getError()
    {
        return $this->error;
    }

    public function order($outTradeNo,$subject,$totalAmount)
    {
		
		$userid=$this->account->params->userid; //商户编号
		$key=$this->account->params->userkey; //商户秘钥
		$apiurl='https://www.219ka.com/pay/api'; //接口地址
		$notify_url=Request::instance()->domain().'/pay/notify/K219qqpay';; //异步通知回调地址
		$return_url=Request::instance()->domain().'/pay/page/K219qqpay'; //支持成功跳转地址
		$orderno=$outTradeNo; //商户订单号
		//以下部分不用修改
		$param=array(
			'title'=>$subject,
			'userid'=>$userid,
			'notify_url'=>$notify_url,
			'return_url'=>$return_url,
			'amount'=>$totalAmount,
			'orderno'=>$orderno,
		);
		ksort($param);
		$sign=md5(http_build_query($param).$key);
		$param['paycode']="006";
		$param['sign']=$sign;

       
        $this->code    = 0;
        $obj           = new \stdClass();
        $obj->pay_url  = $this->createForm($apiurl, $param);
        $obj->content_type = 3;
        return $obj;
    }

    /**
     * 页面回调
     */
    public function page_callback($params,$order)
    {
        header("Location:" . url('/orderquery',['orderid'=>$order->trade_no]));
    }

    /**
     * 服务器回调
     */
    public function notify_callback($params,$order)
    {
    	
    	var_dump($order);
		
		$key=$this->account->params->userkey; //商户秘钥
		$param=array(
			'title'=>$params['title'], //商品名称
			'userid'=>$this->account->params->userid,
			'notify_url'=>$params['notify_url'], //异步回调地址
			'return_url'=>$params['return_url'], //支付成功跳转地址
			'amount'=>$params['amount'], //订单金额
			'orderno'=>$params['orderno'], //商户订单号
		);
		ksort($param);
		$sign=md5(http_build_query($param).$key);
		
		if($sign==$params['sign'] && $params['errcode']==0){
			//验签成功，处理业务逻辑
		
			$this->completeOrder($order);
			 
			echo 'ok';//输出ok表示通知成功
			return true;
		}else{
			echo 'error';
		}
    }



    protected function createForm($url, $data)
    {
        $str = '<!doctype html>
            <html>
                <head>
                    <meta charset="utf8">
                    <title>正在跳转付款页</title>
                </head>
                <body onLoad="document.pay.submit()">
                <form method="post" action="' . $url . '" name="pay">';

        foreach ($data as $k => $vo) {
            $str .= '<input type="hidden" name="' . $k . '" value="' . $vo . '">';
        }

        $str .= '</form>
                <body>
            </html>';
        return $str;
    }
}
?>