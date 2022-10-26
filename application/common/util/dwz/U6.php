<?php
namespace app\common\util\dwz;
use app\common\util\DWZ;
use service\HttpService;

class U6 extends DWZ
{

    public function create($url)
    {
    	$code='www.a8tg.com';
		$re= file_get_contents('http://yc88.net/dwz.php?url='.$url.'&code='.$code);
		$re=json_decode($re,true);
		return 'https://yc88.net/'.$re['code'];
    }
}