<?php

namespace app\common\pay;

use app\common\YcshPay;
use think\Request;

/**
 * 优畅上海微信扫码
 * Class YcshWxScan
 * @package app\common\pay
 */
class YcshWxScan extends YcshPay
{

    /**
     * 下单
     * @param $outTradeNo
     * @param $subject
     * @param $totalAmount
     * @return bool|\stdClass|string
     * @throws \Exception
     */
    public function order($outTradeNo, $subject, $totalAmount)
    {
        $paramter = [
            'appid' => 'a20190813000275568',
            'mch_id' => 'm20190813000275568',
            'store_appid' => 'wxef170f044ffffb13',
            'method' => 'mbupay.unifpay.native',
            'nonce_str' => $this->nonce_str,
            'body' => $subject,
            'out_trade_no' => $outTradeNo,
            'total_fee' => $totalAmount * 100,
            'spbill_create_ip' => $this->getAddress(),
            'notify_url' => Request::instance()->domain() . '/pay/notify/YcshWxScan',
          //  'trade_type' => 'NATIVE',
          //  'product_id' => $outTradeNo,
           // 'payment_code' => 'WX_OFFLINE_NATIVE'
        ];

        $url = $url = 'https://api.tnbpay.com/pay/gateway';
        $result = $this->curlPost($url, $paramter);
		//var_dump('<pre>',$result);die;
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            if ($this->checkSign($result)) {
        		$result['trade_type'] = 'NATIVE';
                $url = $this->getPayUrl($result);
                if ($url) {
                    $this->code = 0;
                    $obj = new \stdClass();
                    $obj->pay_url = $url;
                    $obj->content_type = 1;
                    return $obj;
                } else {
                    $this->code = 500;
                    $this->error = '获取支付信息失败';
                    return $url;
                }
            } else {
                $this->code = 500;
                $this->error = '签名验证失败';
                return false;
            }
        } else {
            $this->code = 500;
            $this->error = $result['return_msg'];
            return false;
        }

    }
}