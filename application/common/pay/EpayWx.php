<?php

namespace app\common\pay;

use app\common\Epay;

class EpayWx extends Epay
{

    function order($outTradeNo, $subject, $totalAmount)
    {
        return $this->requestForm('EpayWx', $outTradeNo, $subject, $totalAmount, 'wxpay');
    }
}
