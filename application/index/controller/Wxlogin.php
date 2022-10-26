<?php

namespace app\index\controller;

use app\common\model\InviteCode as InviteCodeModel;
use app\common\model\User as UserModel;
use app\common\model\UserLoginLog;
use app\common\util\notify\Denglu;
use app\common\util\Email;
use app\common\util\Sms;
use service\MerchantLogService;
use think\Db;
use think\Loader;
use think\Request;
use think\captcha\Captcha;

class Wxlogin extends Base
{
    public function __construct()
    {
			//=======【基本信息设置】=====================================
	//
	
	$this->appid = sysconf('wechat_appid'); 
    $this->appsecret = sysconf('wechat_appsecret');
	
	//=======【curl代理设置】===================================
	/**
	 * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
	 * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
	 * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
	 * @var unknown_type
	 */
	$this->CURL_PROXY_HOST = "0.0.0.0";//"10.152.18.220";
	$this->CURL_PROXY_PORT = 0;//8080;
	$this->curl_timeout=10;
	

        parent::__construct();
    }

    public function index()
    {
        $userid = input('userid/d', '0');
		
		$user = UserModel::get(['id' => $userid]);
		if($user && $user['openid']){
			
			$this->success('已绑定', '/merchant/user/wxsettings');
		}
		
		if($userid>0){
		  $userinfo=$this->getuserinfo($userid);
		}
		
		if($userid && $userinfo){
			
			$user = UserModel::get(['openid' => $userinfo['openid']]);
			if($user){
				$this->success('此微信已绑定过，请解绑原来账号或者更换新的微信绑定', '/merchant/index');
			}
			
			UserModel::where('id', $userid)->update(['openid' => $userinfo['openid']]);
			$this->success('绑定成功', '/merchant/user/wxsettings');
			
		}
		
		 
    }

	
	//=======【基本信息设置】===================================== end

	/**
	 * 
	 * 通过跳转获取用户的openid，跳转流程如下：
	 * 1、设置自己需要调回的url及其其他参数，跳转到微信服务器https://open.weixin.qq.com/connect/oauth2/authorize
	 * 2、微信服务处理完成之后会跳转回用户redirect_uri地址，此时会带上一些参数，如：code
	 * 
	 * @return 用户的openid
	 */
	public function getopenid()
	{
		//第一步 通过 用户的许可 获取 code
		if (!isset($_GET['code'])){
			//触发微信返回code码
			$baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING']);
			$url = $this->__CreateOauthUrlForCode($baseUrl);
			Header("Location: $url");
			exit();
		} else {
		    // 第二步 通过 code 获取 openid 和 access_token
		    $code = $_GET['code'];
			$openidAndAccess_token = $this->GetOpenidAndAccess_token($code);

			$openid = $openidAndAccess_token['openid'];
			return $openid;
		}
	}

	/**
	 * 
	 * 通过跳转获取用户的userinfo，跳转流程如下：
	 * 1、设置自己需要调回的url及其其他参数，跳转到微信服务器https://open.weixin.qq.com/connect/oauth2/authorize
	 * 2、微信服务处理完成之后会跳转回用户redirect_uri地址，此时会带上一些参数，如：code
	 * 
	 * @return 用户的openid
	 */

	public function getuserinfo($userid)
	{
		//第一步 通过 用户的许可 获取 code
		if (!isset($_GET['code'])){
			//触发微信返回code码
			$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://'; 
			$baseUrl = $http_type.$_SERVER['HTTP_HOST']."/index/wxlogin/index/userid/".$userid;
			$url = $this->__CreateOauthUrlForCode($baseUrl,'snsapi_userinfo');
			Header("Location: $url");
			exit();
		} else {
		    $code = $_GET['code'];

		    // 第二步 通过 code 获取 openid 和 access_token
			$openidAndAccess_token = $this->GetOpenidAndAccess_token($code);
			
			// 第三步 通过 openid 和 access_token 获取 userinfo
			$userinfo=$this->GetUserinfoByOpenidAndAccess_token($openidAndAccess_token);

			return $userinfo;
		}
	}


	/**
	 * 
	 * 通过code 从工作平台获取openid机器access_token
	 * @param string $code 微信跳转回来带上的code
	 * 
	 * @return openid
	 */
	public function GetOpenidAndAccess_token($code)
	{
		$url = $this->__CreateOauthUrlForOpenidAndAccess_token($code);
		//初始化curl
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		if($this->CURL_PROXY_HOST != "0.0.0.0" 
			&& $this->CURL_PROXY_PORT != 0){
			curl_setopt($ch,CURLOPT_PROXY, $this->CURL_PROXY_HOST);
			curl_setopt($ch,CURLOPT_PROXYPORT, $this->CURL_PROXY_PORT);
		}
		//运行curl，结果以jason形式返回
		$res = curl_exec($ch);
		curl_close($ch);
		//取出openid
		$data = json_decode($res,true);
		// $this->data = $data;
		// $openid = $data['openid'];
		// return $openid;
		return $data;
	}


	/**
	 * 
	 * 通过 openid 和 access_token 获取userinfo
	 * @param arr
	 * 
	 * @return userinfo
	 */
	public function GetUserinfoByOpenidAndAccess_token($data)
	{
		$url = $this->__CreateOauthUrlForUserinfo($data);
		//初始化curl
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		if($this->CURL_PROXY_HOST != "0.0.0.0" 
			&& $this->CURL_PROXY_PORT != 0){
			curl_setopt($ch,CURLOPT_PROXY, $this->CURL_PROXY_HOST);
			curl_setopt($ch,CURLOPT_PROXYPORT, $this->CURL_PROXY_PORT);
		}
		//运行curl，结果以jason形式返回
		$res = curl_exec($ch);
		curl_close($ch);
		$data = json_decode($res,true);
		return $data;
	}

	


	/**
	 * 
	 * 拼接签名字符串
	 * @param array $urlObj
	 * 
	 * @return 返回已经拼接好的字符串
	 */
	private function ToUrlParams($urlObj)
	{
		$buff = "";
		foreach ($urlObj as $k => $v)
		{
			if($k != "sign"){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}

	/**
	 * 
	 * 构造获取code的url连接
	 * @param string $redirectUrl 微信服务器回跳的url，需要url编码
	 * 
	 * @return 返回构造好的url
	 */
	private function __CreateOauthUrlForCode($redirectUrl,$scope='snsapi_base')
	{
		$urlObj["appid"] = $this->appid;
		$urlObj["redirect_uri"] = "$redirectUrl";
		$urlObj["response_type"] = "code";
		$urlObj["scope"] = $scope;
		$urlObj["state"] = "STATE"."#wechat_redirect";
		$bizString = $this->ToUrlParams($urlObj);

		
		return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;

		// 通过多一次的跳转，解决了微信限制回调域名只能设置一个的问题
		//return "http://hospital.seetest.cn/Plugins/GetWeixinCode-master/get-weixin-code.html?".$bizString;
	}

	/**
	 * 
	 * 构造获取openid和access_toke的url地址 通过code
	 * @param string $code，微信跳转带回的code
	 * 
	 * @return 请求的url
	 */
	private function __CreateOauthUrlForOpenidAndAccess_token($code)
	{
		$urlObj["appid"] = $this->appid;
		$urlObj["secret"] = $this->appsecret;
		$urlObj["code"] = $code;
		$urlObj["grant_type"] = "authorization_code";
		$bizString = $this->ToUrlParams($urlObj);
		return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
	}

	/**
	 * 
	 * 构造获取 用户信息的地址 url地址 通过 access_token 和 openid
	 * @param string $code，微信跳转带回的code
	 * 
	 * @return 请求的url
	 */
	private function __CreateOauthUrlForUserinfo($data)
	{
		$urlObj["access_token"] = $data['access_token'];
		$urlObj["openid"] = $data['openid'];
		$urlObj["lang"] = 'zh_CN';

		$bizString = $this->ToUrlParams($urlObj);
		return "https://api.weixin.qq.com/sns/userinfo?".$bizString;
	}

   
    //生成扫码登录的URL 
    public function qrconnect($redirect_url, $scope, $state = NULL) 
    { 
        $url = "https://open.weixin.qq.com/connect/qrconnect?appid=".$this->appid."&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=".$scope."&state=".$state."#wechat_redirect"; 
        return $url; 
    } 
 
    //生成OAuth2的Access Token 
    public function oauth2_access_token($code) 
    { 
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code"; 
        $res = $this->http_request($url); 
        return json_decode($res, true); 
    } 
 
    //获取用户基本信息（OAuth2 授权的 Access Token 获取 未关注用户，Access Token为临时获取） 
    public function oauth2_get_user_info($access_token, $openid) 
    { 
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN"; 
        $res = $this->http_request($url); 
        return json_decode($res, true); 
    }

	  //HTTP请求（支持HTTP/HTTPS，支持GET/POST）
    public function http_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}