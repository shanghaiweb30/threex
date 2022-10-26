<?php
/**
 * 易云微信WAP
 * @author mapeijian
 */
namespace app\common\pay;
use think\Request;
use app\common\Pay;
class UcykjWxpay extends Pay
{

    protected $gateway = 'http://api.ucyzf.com/submit.php';
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
        $param = [
			'pid'=>$this->account->params->userid,
			'name'=>$subject,
			'type'=>'wxpay',
			'money'=>number_format($totalAmount,2,'.',''),
			'out_trade_no'=>$outTradeNo,
			'notify_url'=>Request::instance()->domain().'/pay/notify/UcykjWxpay',
			'return_url'=>Request::instance()->domain().'/pay/page/UcykjWxpay',
		];
		$key = $this->account->params->userkey;
		ksort($param);
		$sign = '';
		foreach ($param as $k => $v) {
			$sign.=$k.'='.$v.'&';
		}
		$sign = trim($sign,'&');
		$param['sign'] = md5($sign.$key);
		$param['sign_type'] = 'MD5';
        $this->code    = 0;
        $obj           = new \stdClass();
        $obj->pay_url  = $this->createForm($this->gateway, $param);
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
        //file_put_contents("./logs.txt",'UcykjWxpay=='.print_r($params, true) . PHP_EOL);
		$status = isset($params['trade_status']) ? $params['trade_status'] : '';
        $key = $this->account->params->userkey;
		ksort($params);
		$sign = '';
		foreach ($params as $k => $v) {
			if ("sign" != $k && "" != $v&&"sign_type" != $k)$sign.=$k.'='.$v.'&';
		}
		$sign = trim($sign,'&');
        $mysign = md5($sign.$key);
        if($params['sign'] == $mysign) {
            if($status == 'TRADE_SUCCESS') {
                $order->transaction_id = $params['trade_no'];
                $this->completeOrder($order);
                //record_file_log('UckjWxpay_notify_success',$order->trade_no);
                echo 'success';
            } else {
                echo 'fail';
            }
        } else {
            echo 'signerr';
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