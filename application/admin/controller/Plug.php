<?php
namespace app\admin\controller;

use upgrade\Service;
use upgrade\Http;
use think\Db;
use think\Cache;
use think\Config as Configplug;
use think\Controller;
use think\Exception;

class Plug extends Controller
{
    // 初始化方法
    public function _initialize()
    {
        define('UPGRADE_PATH',ROOT_PATH.Configplug::get('upgrade.system_dir').DS);
        if (!is_dir(UPGRADE_PATH)) {
            @mkdir(UPGRADE_PATH, 0755, true);
        }
        parent::_initialize();
    }

    public function index()
    {
        $upgrade=Configplug::get('upgrade');
        $this->assign("upgrade",$upgrade);
        return $this->view->fetch();
    }
	
	
	public function getTopHost(){
		 $url   = $_SERVER['HTTP_HOST'];
		 $url = strtolower($url);
		$data = explode('.', $url);
		$co_ta = count($data);
		//判断是否是双后缀
		$zi_tow = true;
		$host_cn = 'com.cn,net.cn,org.cn,gov.cn';
		$host_cn = explode(',', $host_cn);
		foreach($host_cn as $host){
			if(strpos($url,$host)){
				$zi_tow = false;
			}
		}
		
		 //如果是返回FALSE ，如果不是返回true
		if($zi_tow == true){
			$host = $data[$co_ta-2].'.'.$data[$co_ta-1];
		}else{
			$host = $data[$co_ta-3].'.'.$data[$co_ta-2].'.'.$data[$co_ta-1];
		}
	  return $host;
	}


public function getversion(){
	
	$id = $this->request->post("id");
	$where="plug_id=".$id;

	$pluggg = Db::name('plug')->where($where)->order('id desc')->find();
	
	return  $pluggg['version'];
}

    /**
     * 更新插件
     */
    public function upgrade()
    {
        $id = $this->request->post("id");
		$version = $this->request->post("ver");
		$cdata['id']=$id;
		$cdata['domain']=$this->getTopHost();
		$cdata['version'] = $version;
		deldir(UPGRADE_PATH);
        $name = 'system';
        $tp5upgradeTmpDir = RUNTIME_PATH . 'tp5upgrade' . DS;
        if (!is_dir($tp5upgradeTmpDir)) {
            @mkdir($tp5upgradeTmpDir, 0755, true);
        }
        try {
            $extend = [
                'id'   => $id,
				'domain' => $this->getTopHost(),
            ];
            // 调用更新的方法
            Service::plugupgrade($name, $extend);
            Cache::rm('__menu__');
			$plugdata['plug_id']=$id;
			$plugdata['version']=$version;
            Db::name('plug')->insert($plugdata);
		    deldir(UPGRADE_PATH);
            $this->success('升级版本：'.$version.'成功!');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
		// return $msg;
    }
}
