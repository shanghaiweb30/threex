<?php
/**
 * 易商微信扫码
 * @author mapeijian
 */
namespace app\common\pay;
use think\Request;
use app\common\Pay;
class UcyWxpay extends Pay
{

    protected $gateway = 'http://api.aikpay.cn/api/submit';
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
        $appid=$this->account->params->userid;
		$mchid=$this->account->params->mchid;
		$iswx=strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false?true:false;
		if($iswx){
			$param = [
				'appid'=>$appid,
				'name'=>$subject,
				'type'=>$iswx?'jsapi':'native',
				'money'=>number_format($totalAmount,2,'.',''),
				'out_trade_no'=>$outTradeNo,
				'notify_url'=>Request::instance()->domain().'/pay/notify/UcyWxpay',
				'return_url'=>Request::instance()->domain().'/pay/page/UcyWxpay',
				'mchid'=>$mchid,
			];
			if($iswx)$param['openid']=session('openid');
			$key = $this->account->params->userkey;
			$url = 'http://api.ucyzf.com/api/payment';
			ksort($param);
			$sign = '';
			foreach ($param as $k => $v) {
				$sign.=$k.'='.$v.'&';
			}
			$sign = trim($sign,'&');
			$param['sign'] = md5($sign.$key);
			$response = postCurl($url, $param);
			if(!$response) {
				$this->code    =201;
				$this->error = '调用接口失败';
				return false;
			}
			$response = json_decode($response, true);
			if($response['code'] == '1') {
				$this->code    =0;
				$obj           =new \stdClass();
				$obj->pay_url  =$iswx?$response['data']['packge']:$response['data']['code_url'];
				if(Request::instance()->isMobile()) {
					$obj->content_type = $iswx?5:1;
				}else{
					$obj->content_type = 1;
				}
				return $obj;
			} else {
				$this->code    =201;
				$this->error = isset($response['msg']) ? $response['msg'] : '支付失败';
				return false;
			}
		}else
		{
			$this->code    =0;
			$obj           =new \stdClass();
			$obj->pay_url  =url('index/pay/wxjspay').'?trade_no=' . $outTradeNo;
			$obj->content_type = 1;
			return $obj;
		}
    }

    /*public function order($outTradeNo,$subject,$totalAmount)
    {
        $params['version'] = '1.0';
        $params['customerid'] = $this->account->params->userid;
        $params['sdorderno'] = $outTradeNo;
        $params['total_fee'] = number_format($totalAmount,2,'.','');
        $params['paytype'] = 'wxgzh';
        $params['notifyurl'] = Request::instance()->domain().'/pay/notify/EsWxScan';
        $params['returnurl'] = Request::instance()->domain().'/pay/page/EsWxScan';
        $params['remark'] = $subject;
        $params['is_qrcode'] = '0';
        $params['sign'] = md5('version='.$params['version'].'&customerid='.$params['customerid'].'&total_fee='.$params['total_fee'].'&sdorderno='.$params['sdorderno'].'&notifyurl='.$params['notifyurl'].'&returnurl='.$params['returnurl'].'&'.$this->account->params->userkey);

        $qrcode=$this->gateway.'?'.http_build_query($params);
        $this->code    =0;
        $obj           =new \stdClass();
        $obj->pay_url  =$qrcode;
        $obj->content_type = 1;
        return $obj;
    }*/

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
		//file_put_contents("./logs.txt",'UcyWxpay=='.print_r($params, true) . PHP_EOL);
		$status = isset($params['status']) ? $params['status'] : '';
        $key = $this->account->params->userkey;
		ksort($params);
		$sign = '';
		foreach ($params as $k => $v) {
			if ("sign" != $k && "" != $v)$sign.=$k.'='.$v.'&';
		}
		$sign = trim($sign,'&');
        $mysign = md5($sign.$key);
        if($params['sign'] == $mysign) {
            if($status == '1') {
                $order->transaction_id = $params['transaction_id'];
                $this->completeOrder($order);
                //record_file_log('UcyWxpay_notify_success',$order->trade_no);
                echo 'success';
            } else {
                echo 'fail';
            }
        } else {
            echo 'signerr';
        }
	}
	
    /*public function notify_callback($params,$order)
    {
        $status = isset($params['status']) ? $params['status'] : '';
        $customerid = isset($params['customerid']) ? $params['customerid'] : '';
        $sdorderno = isset($params['sdorderno']) ? $params['sdorderno'] : '';
        $total_fee = isset($params['total_fee']) ? $params['total_fee'] : '';
        $paytype = isset($params['paytype']) ? $params['paytype'] : '';
        $sdpayno = isset($params['sdpayno']) ? $params['sdpayno'] : '';
        $sign = isset($params['sign']) ? $params['sign'] : '';
        $userkey = $this->account->params->userkey;
        $mysign = md5('customerid='.$customerid.'&status='.$status.'&sdpayno='.$sdpayno.'&sdorderno='.$sdorderno.'&total_fee='.$total_fee.'&paytype='.$paytype.'&'.$userkey);
        if($sign == $mysign) {
            if($status == '1') {
                $order->transaction_id = $sdpayno;
                $this->completeOrder($order);
                record_file_log('EsWxScan_notify_success',$order->trade_no);
                echo 'success';
            } else {
                echo 'fail';
            }
        } else {
            echo 'signerr';
        }
    }*/
}
?>