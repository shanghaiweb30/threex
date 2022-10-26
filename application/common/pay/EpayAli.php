<?php

namespace app\common\pay;

use app\common\Epay;

class EpayAli extends Epay
{

    function order($outTradeNo, $subject, $totalAmount)
    {
        return $this->requestForm('EpayAli', $outTradeNo, $subject, $totalAmount, 'alipay');
    }
}
