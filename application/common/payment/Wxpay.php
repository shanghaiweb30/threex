<?php
namespace app\common\payment;
use think\Request;

class Wxpay extends DaifuBase{
    public $parameters; //提交参数，数组
	public $returnParameters;//返回参数，数组
	public $appid;
	public $secret;
	public $key;
	public $SSLCERT_PATH;
	public $SSLKEY_PATH;
	

    public $CURL_TIMEOUT = 30;

    public function api($name='',$data=array(),$ca=false){
    	$url="https://api.mch.weixin.qq.com/".$name;
		$xml=$this->createXml($data);
		if($ca){
		    $re = $this->postXmlSSLCurl($xml,$url,$this->CURL_TIMEOUT);
		}else{
			$re=$this->postXmlCurl($xml, $url, $this->CURL_TIMEOUT);
		}
		return $this->xmlToArray($re);
    }
    

	public function pay($cash){
		
		$this->key=$this->account->params->KEY;
		$this->SSLCERT_PATH=ROOT_PATH.'/cert/wxpay/apiclient_cert.pem';
    	$this->SSLKEY_PATH=ROOT_PATH.'/cert/wxpay/apiclient_key.pem';
		
		$this->parameters['mch_appid']=$this->account->params->appid;
		$this->parameters['mchid']=$this->account->params->MCHID;
		$this->parameters['nonce_str']=uniqid();
		$this->parameters['spbill_create_ip']=get_client_ip();
		
		$this->parameters['partner_trade_no']=$cash->orderid;
		$this->parameters['openid']=$cash->bank_card;
		$this->parameters['desc']='提现 '.$cash->orderid;
		$this->parameters['amount']=$cash->actual_money*100;
		$this->parameters['check_name']='FORCE_CHECK';
		$this->parameters['re_user_name']=$cash->realname;
		
		
		$re=$this->api('mmpaymkttransfers/promotion/transfers',$this->parameters,true);
		
		if($re['return_code']=='SUCCESS' && $re['result_code']=='SUCCESS'){
			//代付成功
            $cash->status = 1; //审核通过
            $cash->complete_at = $_SERVER['REQUEST_TIME'];
            $cash->save();
            // 记录用户金额变动日志
            $reason = "申请提现成功，提现金额{$cash->money}元，手续费{$cash->fee}元，实际到账{$cash->actual_money}元";
            record_user_money_log('cash_success', $cash->user_id, 0, $cash->user->money, $reason);
			return true;
			echo "成功";
		}else{
			echo "失败";
		}
		
	}
    
    
	
    /**
     * 	作用：以post方式提交xml到对应的接口url
     */
    public function postXmlCurl($xml, $url, $second = 30) {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOP_TIMEOUT, $this->CURL_TIMEOUT);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        curl_close($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:{$error}" . '<br>';
            echo '<a href=\'http://curl.haxx.se/libcurl/c/libcurl-errors.html\'>错误原因查询</a></br>';
            curl_close($ch);
            return false;
        }
    }

	/**
	 * 	作用：使用证书，以post方式提交xml到对应的接口url
	 */
	function postXmlSSLCurl($xml,$url,$second=30){
		$ch = curl_init();
		//超时时间
		curl_setopt($ch,CURLOPT_TIMEOUT,$second);
		//这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch,CURLOPT_HEADER,FALSE);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		//设置证书
		//使用证书：cert 与 key 分别属于两个.pem文件
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLCERT, $this->SSLCERT_PATH);
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLKEY, $this->SSLKEY_PATH);
		//post提交方式
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
		$data = curl_exec($ch);
		//返回结果
		if($data){
			curl_close($ch);
			return $data;
		}
		else { 
			$error = curl_errno($ch);
			echo "curl出错，错误码:$error"."<br>"; 
			echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
			curl_close($ch);
			return false;
		}
	}
	
	
    /**
     * 	作用：设置标配的请求参数，生成签名，生成接口参数xml
     */
    public function createXml($data=array()) {
		foreach ($data as $k => $v) {
			$this->parameters[$k]=$v;
		}
        $this->parameters['sign'] = $this->getSign($this->parameters);
        return $this->arrayToXml($this->parameters);
    }
	
	/**
     * 	作用：将xml转为array
     */
    public function xmlToArray($xml){
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }
	
    /**
     * 	作用：array转xml
     */
    public function arrayToXml($arr) {
        $xml = '<xml>';
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= '<' . $key . '>' . $val . '</' . $key . '>';
            } else {
                $xml .= '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
            }
        }
        $xml .= '</xml>';
        return $xml;
    }
    /**
     * 	作用：生成签名
     */
    public function getSign($Obj) {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String . '&key=' . $this->key;
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        //echo "【result】 ".$result_."</br>";
        return $result_;
    }
	
	
	/**
	 * 验签方法 2018-04-04
	 * @return boolean
	 */
	function check(){
		$data=$this->getData();
		$tmpData = $data;
		unset($tmpData['sign']);
		$sign = $this->getSign($tmpData);
		if ($data['sign'] == $sign) {
			$redata=array(
				'orderno'=>$data['out_trade_no'],
				'amount'=>$data['total_fee']/100,
				'other'=>$data['attach'],
				'pay_no'=>$data['transaction_id'],
				'pay_way'=>'微信',
				'pay_time'=>strtotime($data['time_end']),
			);
			return $redata;
		}
		return false;
	}
	
	
	/**
     * 	获取返回数据
     */
	function getData(){
		return $this->xmlToArray(file_get_contents("php://input"));
	}
	
	/**
	 * 设置返回微信的xml数据
	 */
	function setReturnParameter($parameter, $parameterValue){
		$this->returnParameters[$this->$parameter] = $parameterValue;
	}
	
	/**
	 * 将xml数据返回微信
	 */
	function returnXml(){
		if($this->returnParameters["return_code"] == "SUCCESS"){
		   	return $this->arrayToXml($this->returnParameters);
		}
	}
	
	
    /**
     * 	作用：格式化参数，签名过程需要使用
     */
    public function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = '';
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . '=' . $v . '&';
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
	
	
	function get_client_ip() {
	    if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
	        $ip = getenv('HTTP_CLIENT_IP');
	    } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
	        $ip = getenv('HTTP_X_FORWARDED_FOR');
	    } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
	        $ip = getenv('REMOTE_ADDR');
	    } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
	        $ip = $_SERVER['REMOTE_ADDR'];
	    }
	    return preg_match ( '/[\d\.]{7,15}/', $ip, $matches ) ? $matches [0] : '';
	}
	
	/**
	 * 回调通知
	 */
	function notifyReturn(){
		return '<xml>
  <return_code><![CDATA[SUCCESS]]></return_code>
  <return_msg><![CDATA[OK]]></return_msg>
</xml>';
	}
}

