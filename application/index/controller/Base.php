<?php



namespace app\index\controller;



use controller\BasicWechat;

use think\Controller;

use service\WechatService;

use think\Db;

use think\Request;

use app\common\model\User as UserModel;





class Base extends BasicWechat

{

    public function _initialize()

    {

        parent::_initialize();

        if (sysconf('wx_auto_login') && isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {// 微信浏览器打开

            $user_id = session("merchant.user");

            $openid = session('openid');

            if (!$openid && Request::instance()->action() != 'wx_js_api_call') {

                $this->oAuth();

            }

            if ($openid && !$user_id) {

                if (!$user_id) {

                    $wechat_fans = Db::name('wechat_fans')->where(["openid" => $openid])->find();

                    if (empty($wechat_fans)) {

                        $this->oAuth();

                    }

                    $member = UserModel::get(["openid" => $openid]);

                    if (!empty($member) && sysconf('wx_auto_login') == 1) {//自动登录

                        session('merchant.user', $member['id']);

                        session('merchant.username', $member['username']);

                        session('merchant.login_expire',time() + 7*86400);

                    }

                }

            }

        }



        //注册平台

        if ($this->request->isMobile()) {

            $this->view->config('view_platform', 'mobile');

        } else {

            $this->view->config('view_platform', 'pc');

        }



        //注册主题

        $this->view->config('view_theme', sysconf('index_theme') . DS);



        // 站点关闭

        if (sysconf('site_status') === '0') {

            die(sysconf('site_close_tips'));

        }

        $nav = Db::name('nav')->where('status=1')->order('sort DESC')->select();

        $this->assign('nav', $nav);

    }

    

    

    

    /**

     * 获取参数

     */

    protected function getParams() {

        // 渠道

        $channel = input('channel/s', '');

        $params  = [];

        switch ($channel) {

            case 'JyWxPay':

            case 'JyWxGzhPay':

            case 'PayapiAli':

            case 'PayapiWx':

            case 'Sihuih5'://思慧易支付H5+电脑

            case 'Sihuiwxh5'://思慧易支付H5+电脑

            case 'YiyunAliScan'://易云支付

            case 'KaKuAliScan'://卡酷云科支付宝扫码

			case 'KaKuAliWap'://卡酷云科支付宝wap

			case 'KaKuHbScan'://卡酷云科花呗扫码

			case 'KaKuHbWap'://卡酷云科花呗wap

			case 'KaKuWxScan'://卡酷云科微信扫码

			case 'KaKuWxGzh'://卡酷云科微信公众号

			case 'KaKuWxWap'://卡酷云科微信wap	

            case 'YiyunAliWap':

            case 'YiyunWxGzh':

            case 'YiyunWxScan':

            case 'YiyunWxWap':

            case 'HenglongAliScan'://恒隆支付

            case 'HenglongAliWap':

            case 'HenglongWxScan':

            case 'HenglongWxGzh':
			
			case 'YoujiuWxpay':
			
			case 'YoujiuAlipay':

            case 'ShenduAliScan'://深度支付

            case 'ShenduWxScan':

            case 'ShenduWxGzh':

            case 'ShenduAliJspay':

            case 'Juhezhifu'://聚合支付

			case 'EpayAli':

            case 'EpayQq':

            case 'EpayWx':

                $params = input('');

                break;

           case 'K219aliscan'; //219发卡支付宝

            case 'K219h5alipay'; //219发卡支付宝H5

            case 'K219h5wxpay'; //219发卡微信H5

            case 'K219qqpay'; //219发卡QQ钱包

            case 'K219wxpay'; //219发卡微信扫码

            case 'AlipayQr'://当面付
            case 'UcyAlipay':
            case 'UcyWxpay':
				$params = $_POST;

                break;
            case 'UcykjAlipay':
            case 'UcykjWxpay':

                $params = $_GET;

                break;

            case 'WsyhAliScan':

            case 'WsyhWxScan':

                $xml = file_get_contents('php://input');

                if (!empty($xml)) {

                    libxml_disable_entity_loader(true);

                    $params = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

                }

                if (empty($params)) {

                    $params = input('');

                }

                break;

            case 'PafbAliScan':

            case 'PafbWxScan':

            case 'PafbWxWap':

            case 'PafbAliWap':

                $params = json_decode(file_get_contents("php://input"), true);

                break;

            case 'Hkyh':

                //汉口银行

                $params = json_decode(file_get_contents("php://input"), true);

                break;

            case 'NZFAliqrcode': // 支付宝扫码

                parse_str(file_get_contents('php://input'), $params);

                break;

            case 'AlipayScan': // 支付宝扫码

            case 'AlipayWap': // 支付宝WAP

             case 'AlipayQr': 

                parse_str(file_get_contents('php://input'), $params);

                break;

            case 'WxpayScan': // 支付宝WAP

                $xml = file_get_contents('php://input');

                libxml_disable_entity_loader(true);

                $params = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

                break;

            case 'QPayAli':

                parse_str(file_get_contents('php://input'), $params);

                break;

            case 'QPayWx':

                parse_str(file_get_contents('php://input'), $params);

                break;

            case 'DzWxScan':

            case 'DzAliScan':

            case 'DzQqScan':

            case 'DzWxGzh':

            case 'DzJdScan': //点缀支京东扫码

            case 'DzWxH5': //点缀支付微信H5扫码

            case 'DzAliToPay': //点缀支付支付宝即时到账

                parse_str(file_get_contents('php://input'), $params);

                break;

            case 'Lh15173PcPay':

            case 'Lh15173WapPay':

            case 'Lh15173QqPay':

                $params = input('');

                break;

            case 'LkAlipayScanPay':

            case 'LkWxH5Pay':

            case 'LkWxPay':

            case 'LkQQPay':

            case 'LkBankPay':

                $params = input('');

                break;

            case 'KjWxSanPay':

            case 'KjWxH5Pay':

            case 'KjAlipayScanPay':

            case 'KjAlipayH5Pay':

                parse_str(file_get_contents('php://input'), $params);

                break;

            case 'Ka12QqNative':

            case 'Ka12QqWap':

            case 'Ka12QuickBank':

            case 'Ka12QuickWap':

            case 'Ka12AlipayScan':

            case 'Ka12AlipayWap':

            case 'Ka12WxScan':

            case 'Ka12WxWap':

                $params = input('');

                break;

            case 'CodePayWxScan':

            case 'CodePayAliScan':

            case 'CodePayQqScan':

                parse_str(file_get_contents('php://input'), $params);

                break;

            case 'WxpayH5':

            case 'QqNative':

            case 'SwiftAliScan':

            case 'SwiftAliWap':

            case 'SwiftWxScan':

            case 'SwiftJd':

            case 'SwiftWxWap':

            case 'SwiftWxGzh':

                $params = \Util\Qpay\QpayMchUtil::xmlToArray(file_get_contents('php://input'));

                break;

            case 'FnAliScan':

            case 'FnAliWap':

            case 'FnQqScan':

            case 'FnWxJspay':

            case 'FnWxScan':

                $params = input('');

                break;

            case 'ZlfWxScan':

            case 'ZlfQqScan':

            case 'ZlfAliScan':

            case 'ZlfWxJspay':

            case 'ZlfWxH5':

            case 'ZlfJdScan':

                $params = json_decode(file_get_contents("php://input"), true);

                break;

            case 'QgjfAlipayScan':

            case 'QgjfAlipayWap':

            case 'QgjfQqNative':

            case 'QgjfWxGzh':

            case 'QgjfWxScan':

            case 'QgjfWxWap':

            case 'TaomiAlipayScan':

            case 'TaomiAlipayWap':

            case 'TaomiQqNative':

            case 'TaomiWxGzh':

            case 'TaomiWxScan':

            case 'TaomiWxWap':

                parse_str(file_get_contents('php://input'), $params);

                break;

            case 'PYFalipay':

            case 'PYFqqpay':

            case 'PYFwxpay':

                //拼云付

                $params = json_decode(file_get_contents("php://input"), true);

                break;

            case 'YcshWxGzh':

            case 'YcshWxH5':

            case 'YcshWxScan':

            case 'YcshAliScan':

            case 'YcshAliWap':

            case 'YcshQqScan':

            case 'YcshQqWap':

                //优畅上海

                libxml_disable_entity_loader(true);

                $params = json_decode(json_encode(simplexml_load_string(file_get_contents("php://input"), 'SimpleXMLElement', LIBXML_NOCDATA)), true);

                break;

            case 'TpayWxpay':

            case 'TpayAlipay':

                //TPay

                $params = json_decode(file_get_contents("php://input"), true);

                break;

            case 'HnPayAliScan':

            case 'HnPayQqScan':

            case 'HnPayWxGzh':

            case 'HnPayWxH5':

            case 'HnPayWxScan':

                //海鸟

                parse_str(file_get_contents('php://input'), $params);

                break;

            case 'WmskWxScan':

            case 'WmskAliScan':

            case 'WmskQqScan':

            case 'WmskQqWap':

            case 'WmskWxWap':

            case 'WmskAliWap':

                //完美数卡

                $params = input('');

                break;

            case 'YunQq':

            case 'YunWx':

            case 'YunAli':

                //免签

                parse_str(file_get_contents('php://input'), $params);

                break;

            default:

                record_file_log('pay_params', '未知支付产品！' . $channel);

                die('未知支付产品！');

                break;

        }

        record_file_log('pay_params', json_encode($params));

        if (isset($params['channel'])) {

            unset($params['channel']);

        }

        return $params;

    }



    /**

     * 获取渠道名

     */

    protected function getChannelName($params) {

        // 渠道

        $channel = input('channel/s', '');

        switch ($channel) {

            case 'WsyhAliScan':

            case 'WsyhWxScan':

                $trade_no = isset($params['request']['body']['OutTradeNo']) ? $params['request']['body']['OutTradeNo'] : '';

                if (empty($trade_no)) {

                    $trade_no = isset($params['outTradeNo']) ? $params['outTradeNo'] : '';

                }

                break;

            case 'PafbAliScan':

            case 'PafbWxScan':

            case 'PafbWxWap':

            case 'PafbAliWap':

                $trade_no = isset($params['u_out_trade_no']) ? $params['u_out_trade_no'] : '';

                break;

            case 'Hkyh':

                //汉口银行

                $trade_no = isset($params['u_out_trade_no']) ? $params['u_out_trade_no'] : '';

                break;

            case 'NZFAliqrcode': // 支付宝扫码

                $trade_no = isset($params['orderid']) ? $params['orderid'] : '';

                break;

            case 'AlipayScan': // 支付宝扫码

            case 'AlipayWap': // 支付宝WAP

            case 'AlipayQr': 

            case 'WxpayScan': // 微信扫码

            case 'ShenduAliScan'://深度支付

            case 'ShenduWxScan':

            case 'ShenduWxGzh':

            case 'ShenduAliJspay':

			case 'EpayAli':

            case 'EpayQq':

            case 'EpayWx':

            case 'UcyAlipay':

            case 'UcyWxpay':

            case 'UcykjAlipay':

            case 'UcykjWxpay':

                $trade_no = isset($params['out_trade_no']) ? $params['out_trade_no'] : '';

                break;

            case 'QPayAli': //支付宝免签

                $trade_no = isset($params['orderid']) ? $params['orderid'] : '';

                break;

            case 'QPayWx': //微信免签

                $trade_no = isset($params['orderid']) ? $params['orderid'] : '';

                break;

            case 'PayapiAli': //PayApi支付宝免签

                $trade_no = isset($params['orderid']) ? $params['orderid'] : '';

                break;

            case 'PayapiWx': //PayApi微信免签

            case 'Juhezhifu'://聚合支付

                $trade_no = isset($params['orderid']) ? $params['orderid'] : '';

                break;

            case 'DzWxScan': //点缀支付微信扫码

            case 'DzAliScan': //点缀支付支付宝扫码

            case 'DzQqScan': //点缀QQ扫码

            case 'DzWxGzh': //点缀支付公众号支付

            case 'DzJdScan': //点缀支京东扫码

            case 'DzWxH5': //点缀支付微信H5扫码

            case 'DzAliToPay': //点缀支付支付宝即时到账

                $trade_no = isset($params['MerchantOrderNo']) ? $params['MerchantOrderNo'] : '';

                break;

            case 'Lh15173PcPay':

            case 'Lh15173WapPay':

            case 'Lh15173QqPay':

                $trade_no = isset($params['sp_billno']) ? $params['sp_billno'] : '';

                break;

            case 'LkAlipayScanPay':

            case 'LkWxH5Pay':

            case 'LkWxPay':

            case 'LkQQPay':

            case 'LkBankPay':

                $trade_no = isset($params['P_OrderId']) ? $params['P_OrderId'] : '';

                break;

            case 'KjWxSanPay':

            case 'KjWxH5Pay':

            case 'KjAlipayScanPay':

            case 'KjAlipayH5Pay':

                $trade_no = isset($params['merchant_order_no']) ? $params['merchant_order_no'] : '';

                break;

            case 'Ka12QqNative':

            case 'Ka12QqWap':

            case 'Ka12QuickBank':

            case 'Ka12QuickWap':

            case 'Ka12AlipayScan':

            case 'Ka12AlipayWap':

            case 'Ka12WxScan':

            case 'Ka12WxWap':

                $trade_no = isset($params['sdorderno']) ? $params['sdorderno'] : '';

                break;

             case 'K219aliscan'; //219发卡支付宝

            case 'K219h5alipay'; //219发卡支付宝H5

            case 'K219h5wxpay'; //219发卡微信H5

            case 'K219qqpay'; //219发卡QQ钱包

            case 'K219wxpay'; //219发卡微信扫码

                $trade_no = isset($params['orderno']) ? $params['orderno'] : '';

                break;

            case 'CodePayWxScan':

            case 'CodePayAliScan':

            case 'CodePayQqScan':

                $trade_no = isset($params['pay_id']) ? $params['pay_id'] : '';

                break;

            case 'WxpayH5':

                $trade_no = isset($params['out_trade_no']) ? $params['out_trade_no'] : '';

                break;

            case 'QqNative':

                $trade_no = isset($params['out_trade_no']) ? $params['out_trade_no'] : '';

                break;

            case 'FnAliScan':

            case 'FnAliWap':

            case 'FnQqScan':

            case 'FnWxJspay':

            case 'FnWxScan':

                $trade_no = isset($params['out_trade_no']) ? $params['out_trade_no'] : '';

                break;

            case 'ZlfWxScan':

            case 'ZlfQqScan':

            case 'ZlfAliScan':

            case 'ZlfWxJspay':

            case 'ZlfWxH5':

            case 'ZlfJdScan':

                $trade_no = isset($params['mchntOrderNo']) ? $params['mchntOrderNo'] : '';

                break;

            case 'QgjfAlipayScan':

            case 'QgjfAlipayWap':

            case 'QgjfQqNative':

            case 'QgjfWxGzh':

            case 'QgjfWxScan':

            case 'QgjfWxWap':

            case 'TaomiAlipayScan':

            case 'TaomiAlipayWap':

            case 'TaomiQqNative':

            case 'TaomiWxGzh':

            case 'TaomiWxScan':

            case 'TaomiWxWap':

                //黔贵金服支付

                $trade_no = isset($params['out_trade_no']) ? $params['out_trade_no'] : '';

                break;

            case 'PYFalipay':

            case 'PYFqqpay':

            case 'PYFwxpay':

                //拼云付

                $trade_no = isset($params['out_trade_no']) ? $params['out_trade_no'] : '';

                break;

            case 'YcshWxGzh':

            case 'YcshWxH5':

            case 'YcshWxScan':

            case 'YcshAliScan':

            case 'YcshAliWap':

            case 'YcshQqScan':

            case 'YcshQqWap':

                //优畅上海

                $trade_no = isset($params['out_trade_no']) ? $params['out_trade_no'] : '';

                break;

            case 'TpayWxpay':

            case 'TpayAlipay':

                //TPay

                $trade_no = isset($params['order_number']) ? $params['order_number'] : '';

                break;

            case 'HnPayAliScan':

            case 'HnPayQqScan':

            case 'HnPayWxGzh':

            case 'HnPayWxH5':

            case 'HnPayWxScan':

                //海鸟

                $trade_no = isset($params['out_trade_no']) ? $params['out_trade_no'] : '';

                break;

            case 'WmskWxScan':

            case 'WmskAliScan':

            case 'WmskQqScan':

            case 'WmskQqWap':

            case 'WmskWxWap':

            case 'WmskAliWap':

                //完美数卡

                $trade_no = isset($params['sdcustomno']) ? $params['sdcustomno'] : '';

                break;

            case 'JyWxPay':

            case 'JyWxGzhPay':

                $trade_no = isset($params['ordernumber']) ? $params['ordernumber'] : '';

                break;

            case 'YunQq':

            case 'YunWx':

            case 'YunAli':

                //免签

                $trade_no = isset($params['ddh']) ? $params['ddh'] : '';

                break;

            case 'SwiftAliScan':

            case 'SwiftAliWap':

            case 'SwiftWxScan':

            case 'SwiftJd':

            case 'SwiftWxWap':

            case 'SwiftWxGzh':

            case 'Sihuih5'://思慧易支付H5+电脑

            case 'Sihuiwxh5'://思慧易支付H5+电脑

                $trade_no = isset($params['out_trade_no']) ? $params['out_trade_no'] : '';

                break;

            case 'YiyunAliScan'://易云支付

            case 'YiyunAliWap':

            case 'YiyunWxGzh':

            case 'YiyunWxScan':

            case 'YiyunWxWap':

            case 'KaKuAliScan'://卡酷云科支付宝扫码

			case 'KaKuAliWap'://卡酷云科支付宝wap

			case 'KaKuHbScan'://卡酷云科花呗扫码

			case 'KaKuHbWap'://卡酷云科花呗wap

			case 'KaKuWxScan'://卡酷云科微信扫码

			case 'KaKuWxGzh'://卡酷云科微信公众号

			case 'KaKuWxWap'://卡酷云科微信wap

                $trade_no = isset($params['sdorderno']) ? $params['sdorderno'] : '';

                break;

            case 'HenglongAliScan'://恒隆支付

            case 'HenglongAliWap':

            case 'HenglongWxScan':

            case 'HenglongWxGzh':
			
			case 'YoujiuWxpay':
			
			case 'YoujiuAlipay':
			
			

                if(isset($params['orderid'])) {

                    $trade_no = $params['orderid'];

                } elseif(isset($params['ordernumber'])) {

                    $trade_no = $params['ordernumber'];

                } else {

                    $trade_no = '';

                }

                break;

            default:

                record_file_log('pay_params', '未知支付产品！' . $channel);

                die('未知支付产品！');

                break;

        }

        return $trade_no;

    }



    protected function repeat() {

        // 渠道

        $channel = input('channel/s', '');

        switch ($channel) {

            case 'WsyhAliScan':

            case 'WsyhWxScan':

                echo '<xml><RespInfo>SUCCESS</RespInfo></xml>';

                break;

            case 'Hkyh':

            case 'AlipayScan': // 支付宝扫码

            case 'AlipayWap': // 支付宝WAP

            case 'WxpayScan': // 微信扫码

            case 'LkAlipayScanPay':

            case 'LkWxH5Pay':

            case 'LkWxPay':

            case 'LkQQPay':

            case 'LkBankPay':

            case 'KjWxSanPay':

            case 'KjWxH5Pay':

            case 'KjAlipayScanPay':

            case 'KjAlipayH5Pay':

            case 'Ka12QqNative':

            case 'Ka12QqWap':

            case 'Ka12QuickBank':

            case 'Ka12QuickWap':

            case 'Ka12AlipayScan':

            case 'Ka12AlipayWap':

            case 'Ka12WxScan':

            case 'Ka12WxWap':

            case 'CodePayWxScan':

            case 'CodePayAliScan':

            case 'CodePayQqScan':

            case 'WxpayH5':

            case 'QgjfAlipayScan':

            case 'QgjfAlipayWap':

            case 'QgjfQqNative':

            case 'QgjfWxGzh':

            case 'QgjfWxScan':

            case 'QgjfWxWap':

            case 'TaomiAlipayScan':

            case 'TaomiAlipayWap':

            case 'TaomiQqNative':

            case 'TaomiWxGzh':

            case 'TaomiWxScan':

            case 'TaomiWxWap':

            case 'YcshWxGzh':

            case 'YcshWxH5':

            case 'YcshWxScan':

            case 'YcshAliScan':

            case 'YcshAliWap':

            case 'YcshQqScan':

            case 'YcshQqWap':

                //优畅上海

            case 'TpayWxpay':

            case 'TpayAlipay':

            case 'SwiftAliScan':

            case 'SwiftAliWap':

            case 'SwiftWxScan':

            case 'SwiftJd':

            case 'SwiftWxWap':

            case 'SwiftWxGzh':

            case 'Sihuih5'://思慧易支付H5+电脑

            case 'Sihuiwxh5'://思慧易支付H5+电脑

                //易云支付

            case 'YiyunAliScan':

            case 'YiyunAliWap':

            case 'YiyunWxGzh':

            case 'YiyunWxScan':

            case 'YiyunWxWap':

            case 'KaKuAliScan'://卡酷云科支付宝扫码

			case 'KaKuAliWap'://卡酷云科支付宝wap

			case 'KaKuHbScan'://卡酷云科花呗扫码

			case 'KaKuHbWap'://卡酷云科花呗wap

			case 'KaKuWxScan'://卡酷云科微信扫码

			case 'KaKuWxGzh'://卡酷云科微信公众号

			case 'KaKuWxWap'://卡酷云科微信wap	

                //深度支付

            case 'ShenduAliScan':

            case 'ShenduWxScan':

            case 'ShenduWxGzh':

            case 'ShenduAliJspay':

            case 'AlipayQr':

			case 'EpayAli':

            case 'EpayQq':

            case 'EpayWx':

                echo 'success';

                break;

            case 'NZFAliqrcode': // 支付宝扫码

            case 'QPayAli': //支付宝免签

            case 'QPayWx': //微信免签

            case 'Lh15173PcPay':

            case 'Lh15173WapPay':

            case 'Lh15173QqPay':

            case 'PayapiAli': //PayApi支付宝免签

            case 'PayapiWx': //PayApi微信免签

                echo 'OK';

                break;

            case 'DzWxScan': //点缀支付微信扫码

            case 'DzAliScan': //点缀支付支付宝扫码

            case 'DzQqScan': //点缀QQ扫码

            case 'DzWxGzh': //点缀支付公众号支付

            case 'DzJdScan': //点缀支京东扫码

            case 'DzWxH5': //点缀支付微信H5扫码

            case 'DzAliToPay': //点缀支付支付宝即时到账

            case 'HenglongAliScan'://恒隆支付

            case 'HenglongAliWap':

            case 'HenglongWxScan':
			
			case 'YoujiuWxpay':
			
			case 'YoujiuAlipay':

            case 'HenglongWxGzh':
			
			case 'YoujiuWxpay':
			
			case 'YoujiuAlipay':

            case 'Juhezhifu'://聚合支付

            case 'K219aliscan'; //219发卡支付宝

            case 'K219h5alipay'; //219发卡支付宝H5

            case 'K219h5wxpay'; //219发卡微信H5

            case 'K219qqpay'; //219发卡QQ钱包

            case 'K219wxpay'; //219发卡微信扫码

            

                echo 'ok';

                break;

            case 'QqNative':

                echo '<xml><return_code>SUCCESS</return_code></xml>';

                break;

            case 'PafbAliScan':

            case 'PafbWxScan':

            case 'PafbWxWap':

            case 'PafbAliWap':

            case 'FnAliScan':

            case 'FnAliWap':

            case 'FnQqScan':

            case 'FnWxJspay':

            case 'FnWxScan':

            case 'PYFalipay':

            case 'PYFqqpay':

            case 'PYFwxpay':

            case 'HnPayAliScan':

            case 'HnPayQqScan':

            case 'HnPayWxGzh':

            case 'HnPayWxH5':

            case 'HnPayWxScan':

                echo 'SUCCESS';

                break;

            case 'ZlfWxScan':

            case 'ZlfQqScan':

            case 'ZlfAliScan':

            case 'ZlfWxJspay':

            case 'ZlfWxH5':

            case 'ZlfJdScan':

                echo json_encode(['success' => 'true']);

                break;

            case 'WmskWxScan':

            case 'WmskAliScan':

            case 'WmskQqScan':

            case 'WmskQqWap':

            case 'WmskWxWap':

            case 'WmskAliWap':

            case 'YunQq':

            case 'YunWx':

            case 'YunAli':

            case 'JyWxPay':

            case 'JyWxGzhPay':

                //完美数卡

                echo "<result>1</result>";

                break;

            default:

                record_file_log('pay_params', '未知支付产品！' . $channel);

                die('未知支付产品！');

                break;

        }

        exit();

    }

    /**
     * 附加参数
     * @param int $status
     * @param array $data
     * @param string $message
     */
    protected function jsonReturn($status = 0, $data = [], $message = '')
    {
        $jsonData = [
            'status'   => $status,
            'data'     => $data,
            'message'  => $message
        ];
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode(array_merge($jsonData)));
    }
    /**
     * 操作数据成功
     * status 1000：操作数据成功；可能有数据返回对象形式
     * @param string $message
     * @param array $data
     */
    protected function jsonSuccess($message = '', $data = [])
    {
        return $this->jsonReturn(1000, (object)$data, $message);
    }

    /**
     * 操作数据失败
     * status 1001：操作数据失败
     * @param string $message
     * @param array $data
     */
    protected function jsonError($message = '', $data = [])
    {
        return $this->jsonReturn(1001, (object)$data, $message);
    }

}

