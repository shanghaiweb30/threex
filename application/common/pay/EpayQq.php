<?php

namespace app\common\pay;

use app\common\Epay;

class EpayQq extends Epay
{

    function order($outTradeNo, $subject, $totalAmount)
    {
        return $this->requestForm('EpayQq', $outTradeNo, $subject, $totalAmount, 'qqpay');
    }
}
