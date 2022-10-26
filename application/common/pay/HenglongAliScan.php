<?php
/**
 * 恒隆支付宝扫码
 * @author mapeijian
 */
namespace app\common\pay;
use think\Request;
use app\common\Pay;
require_once (EXTEND_PATH.'Util/DzPay/Data.php');
require_once (EXTEND_PATH.'Util/DzPay/Api.php');
class HenglongAliScan extends Pay
{ 

    protected $gateway = 'http://www.codeku.cn/Pay_Index.html';
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
       var_dump(123);die;
 
        return $obj;
    }

    /**
     * 页面回调
     */
    public function page_callback($params,$order)
    {
                  //	var_dump('<pre>',1111);die;

        header("Location:" . url('/orderquery',['orderid'=>$order->trade_no]));
    }

    /**
     * 服务器回调
     */
    public function notify_callback($params,$order)
    {
           $result = \Respbase::InitFromArray($params);
            $platformOrderNo = $result->GetData("orderid");//平台单号
            $merchantOrderNo = $result->GetData("orderid");//商户单号
            $amount = $result->GetData("amount");//订单金额
            // 金额异常检测
           /** if($order->total_price!=$amount){
                record_file_log('DzWxScan_notify_error','金额异常！'."\r\n".$order->trade_no."\r\n订单金额：{$order->total_price}，已支付：{$amount}");
                die('金额异常！');
            }**/
            // 流水号
            $order->transaction_id =$platformOrderNo;
            $this->completeOrder($order);
            record_file_log('DzWxScan_notify_success',$order->trade_no);
            echo 'ok';
            return true;
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