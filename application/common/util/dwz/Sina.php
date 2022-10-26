<?php
/**
 * 新浪短网址
 * @author Veris
 */

namespace app\common\util\dwz;

use app\common\util\DWZ;
use service\HttpService;

class Sina extends DWZ
{
    const API_URL = 'https://api.d5.nz/api/dwz/tcn.php';

    public function create($url)
    {
        $res=HttpService::Get(SELF::API_URL,[
            'url'         =>$url,

        ]);
        $json=json_decode($res);
        if(!$json || $json->code==1){
            return false;
        }
        return $json->url;
    }
}