<?php
/**
 * 思慧支付宝扫码
 * @QQ 1904528722
 */
namespace app\common\pay;
use think\Request;
use app\common\Pay;
class Sihuih5 extends Pay
{
	protected $gateway = 'http://www.codeku.cn/submit.php';
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
        $params['pid'] = $this->account->params->userid;
        $params['out_trade_no'] = $outTradeNo;
        $params['name'] = $subject;
        $params['money'] = number_format($totalAmount,2,'.','');
        $params['type'] = 'alipay';
        $params['sitename'] = $subject;
        $params['notify_url'] = Request::instance()->domain().'/pay/notify/Sihuih5';
        $params['return_url'] = Request::instance()->domain().'/pay/page/Sihuih5';
       ksort($params); //重新排序$data数组
            reset($params); //内部指针指向数组中的第一个元素

            $sign = ''; //初始化需要签名的字符为空
            $urls = ''; //初始化URL参数为空

            foreach ($params AS $key => $val) { //遍历需要传递的参数
                 if($key == "sign" || $key == "sign_type" || $val == "")continue; //跳过这些不签名
                if ($sign != '') { //后面追加&拼接URL
                    $sign .= "&";
                    $urls .= "&";
                }
                $sign .= "$key=$val"; //拼接为url参数形式
                $urls .= "$key=" . urlencode($val); //拼接为url参数形式并URL编码参数值

            }
            $query = $this->gateway.'?'.$urls . '&sign=' . md5($sign . $this->account->params->userkey); //创建订单所需的参数
       //params['sign'] = md5('money='.$params['money'].'&money='.$params['name'].'&notify_url='.$params['notify_url'].'&out_trade_no='.$params['out_trade_no'].'&pid='.$params['pid'].'&type='.$params['type'].'&'.$this->account->params->userkey);
        record_file_log('codepay_sign_error','测试记录sign的数据'."\r\n".$query);
        $this->code    =0;
        $obj           =new \stdClass();
        $obj->pay_url  =$query;
        $obj->content_type = 2;
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
       record_file_log('codepay_sign_error','服务回调，'."\r\n");
      
        $status = isset($params['trade_status']) ? $params['trade_status'] : '';
        $customerid = isset($params['pid']) ? $params['pid'] : '';
        $sdorderno = isset($params['out_trade_no']) ? $params['out_trade_no'] : '';
        $total_fee = isset($params['money']) ? $params['money'] : '';
        $paytype = isset($params['type']) ? $params['type'] : '';
        $sdpayno = isset($params['trade_no']) ? $params['trade_no'] : '';
        $sign = isset($params['sign']) ? $params['sign'] : '';
        $userkey = $this->account->params->userkey;
      
            ksort($params); //重新排序$data数组
            reset($params); //内部指针指向数组中的第一个元素

            $sign = ''; //初始化需要签名的字符为空
            $urls = ''; //初始化URL参数为空

            foreach ($params AS $key => $val) { //遍历需要传递的参数
                 if($key == "sign" || $key == "sign_type" || $val == "")continue; //跳过这些不签名
                if ($sign != '') { //后面追加&拼接URL
                    $sign .= "&";
                    $urls .= "&";
                }
                $sign .= "$key=$val"; //拼接为url参数形式
                $urls .= "$key=" . urlencode($val); //拼接为url参数形式并URL编码参数值

            }
      
        $mysign =  md5($sign . $this->account->params->userkey); 
        record_file_log('codepay_sign_error','已经回调了，'."\r\n".$sign.$mysign.$status);

            if($status == 'TRADE_SUCCESS') {
                $order->transaction_id = $sdpayno;
                $this->completeOrder($order);
                record_file_log('Sihuih5_notify_success',$order->trade_no);
                echo 'Success';
            } else {
                echo 'fail';
            }
    }
}
?>