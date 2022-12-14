<?php
/**
 * 卡酷云科支付宝wap
 * @QQ 2011011181
 */
namespace app\common\pay;
use think\Request;
use app\common\Pay;
class KaKuAliWap extends Pay
{
	protected $gateway = 'http://api.0ka.co/api/submit';
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
        $params['version'] = '1.0';
        $params['customerid'] = $this->account->params->userid;
        $params['sdorderno'] = $outTradeNo;
        $params['total_fee'] = number_format($totalAmount,2,'.','');
        $params['paytype'] = 'aliscan';
        $params['notifyurl'] = Request::instance()->domain().'/pay/notify/KaKuAliWap';
        $params['returnurl'] = Request::instance()->domain().'/pay/page/KaKuAliWap';
        $params['remark'] = $subject;
        $params['is_qrcode'] = '1';
        $params['sign'] = md5('version='.$params['version'].'&customerid='.$params['customerid'].'&total_fee='.$params['total_fee'].'&sdorderno='.$params['sdorderno'].'&notifyurl='.$params['notifyurl'].'&returnurl='.$params['returnurl'].'&'.$this->account->params->userkey);
        $response = postCurl($this->gateway, $params);
        if(!$response) {
            $this->code    =201;
            $this->error = '调用接口失败';
            return false;
        }
        $response = json_decode($response, true);
        if($response['status'] == '1') {
            $this->code    =0;
            $obj           =new \stdClass();
            $obj->pay_url  =$response['code_url'];
            if(Request::instance()->isMobile()) {
                $obj->content_type = 1;
            }else{
                $obj->content_type = 1;
            }
            return $obj;
        } else {
            $this->code    =201;
            $this->error = isset($response['msg']) ? $response['msg'] : '支付失败';
            return false;
        }
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
                record_file_log('KaKuAliWap_notify_success',$order->trade_no);
                echo 'Success';
            } else {
                echo 'fail';
            }
        } else {
            echo 'signerr';
        }
    }
}
?>