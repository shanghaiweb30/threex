<?php
namespace app\manage\controller;

use think\Controller;
use think\Db;
use think\Request;
use app\common\model\Channel as ChannelModel;
use think\db\Query;

class Updata extends Controller
{
	public function index(){
    	$channel_id=186;
		$channel = ChannelModel::get($channel_id);
		$data = [
			'id' => 12,
			'title' => '优创云微信',
			'show_name' => '优创云微信',
			'code' => 'UcyWxpay',
			'account_fields' => '优创云AppId:userid|优创云Appkey:userkey|商户MCHID:mchid',
			'is_available' => '0',
			'status' => '0',
			'lowrate'=> '0.0300',
			'rate_api'=> '0.0300',	
			'paytype' => '1',
			'applyurl'=> 'https://www.ucyzf.com',
		];
		if ($channel_id > 0) {
			//$res = $channel->save($data, ['id'=>$channel_id]);
			$res = $channel->where('id',$channel_id)->update($data);

			if($res!==false){
				echo '新增优创云微信接口成功!<br/>';
			}else{
				exit('新增接口失败，ID:'.$channel_id);
			}
		}
		
    	$channel_id=184;
		$channel = ChannelModel::get($channel_id);
		$data = [
			'id' => 10,
			'title' => '优创云支付宝',
			'show_name' => '优创云支付宝',
			'code' => 'UcyAlipay',
			'account_fields' => '优创云AppId:userid|优创云Appkey:userkey|商户MCHID:mchid',
			'is_available' => '0',
			'status' => '0',
			'lowrate'=> '0.0300',
			'rate_api'=> '0.0300',	
			'paytype' => '3',
			'applyurl'=> 'https://www.ucyzf.com',
		];
		if ($channel_id > 0) {
			$res = $channel->where('id',$channel_id)->update($data);

			if($res!==false){
				echo '新增优创云支付宝接口成功!<br/>';
			}else{
				exit('新增接口失败，ID:'.$channel_id);
			}
		}
				
    	$channel_id=165;
		$channel = ChannelModel::get($channel_id);
		$data = [
			'id' => 13,
			'title' => '优创云微信快捷支付',
			'show_name' => '优创云微信轮训通道',
			'code' => 'UcykjWxpay',
			'account_fields' => '优创云AppId:userid|优创云Appkey:userkey',
			'is_available' => '0',
			'status' => '0',
			'lowrate'=> '0.0300',
			'rate_api'=> '0.0300',	
			'paytype' => '1',
			'applyurl'=> 'https://www.ucyzf.com',
		];
		if ($channel_id > 0) {
			$res = $channel->where('id',$channel_id)->update($data);

			if($res!==false){
				echo '新增优创云微信轮训接口成功!<br/>';
			}else{
				exit('新增接口失败，ID:'.$channel_id);
			}
		}
		
		
    	$channel_id=185;
		$channel = ChannelModel::get($channel_id);
		$data = [
			'id' => 11,
			'title' => '优创云支付宝快捷支付',
			'show_name' => '优创云支付宝轮训通道',
			'code' => 'UcykjAlipay',
			'account_fields' => '优创云AppId:userid|优创云Appkey:userkey',
			'is_available' => '0',
			'status' => '0',
			'lowrate'=> '0.0300',
			'rate_api'=> '0.0300',	
			'paytype' => '3',
			'applyurl'=> 'https://www.ucyzf.com',
		];
		if ($channel_id > 0) {
			$res = $channel->where('id',$channel_id)->update($data);

			if($res!==false){
				echo '新增优创云支付宝轮训接口成功!<br/>';
			}else{
				exit('新增接口失败，ID:'.$channel_id);
			}
		}
		

		
		$res=Db::execute("INSERT INTO `channel_account` (`channel_id`, `name`, `params`, `max_money`, `cur_money`, `limit_time`, `status`, `lowrate`, `highrate`, `costrate`, `rate_type`) VALUES
(10, 'test', '{\"userid\":\"1601395114\",\"userkey\":\"91FDDAB5BFF65E3B75B0A38483D0E421\",\"mchid\":\"2088932485519123\"}', '0.00', '0.00', '', 1, '0.0000', '0.0000', '0.0000', 0),
(12, 'test', '{\"userid\":\"1601395114\",\"userkey\":\"91FDDAB5BFF65E3B75B0A38483D0E421\",\"mchid\":\"1603171594\"}', '0.00', '0.00', '', 0, '0.0000', '0.0000', '0.0000', 0),
(11, 'test', '{\"userid\":\"1601395114\",\"userkey\":\"91FDDAB5BFF65E3B75B0A38483D0E421\"}', '0.00', '0.00', '', 1, '0.0000', '0.0000', '0.0000', 0),
(13, 'test', '{\"userid\":\"1602584684\",\"userkey\":\"75D2FC3C2CC553ED58BCDEEDF539068E\"}', '0.00', '0.00', '', 1, '0.0000', '0.0000', '0.0000', 0)");
		if($res)
		{
			exit('添加测试账号成功，请删除接口内测试账号并更换您自己的账号.</br>
			感谢您使用优创云支付! 祝老板 日入百万！财运亨通！');
		}else
		{
			exit('添加账号失败');
		}
	}
}
?>