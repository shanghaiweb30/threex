<?php
namespace app\admin\controller;

use upgrade\Service;
use think\Cache;
use think\Config;
use think\Controller;
use think\Exception;
use controller\BasicAdmin;


class Shengji extends Controller
{
    // 初始化方法
    public function _initialize()
    {
        define('UPGRADE_PATH',ROOT_PATH.Config::get('upgrade.system_dir').DS);
        if (!is_dir(UPGRADE_PATH)) {
            @mkdir(UPGRADE_PATH, 0755, true);
        }
        parent::_initialize();
    }

    public function index()
    {
		
        $upgrade=Config::get('upgrade');
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

    /**
     * 更新插件
     */
    public function upgrade()
    {
        $version = $this->request->post("version");
        if ($version == Config::get('upgrade.version')) {
            $this->error('当前版本您已经安装，请安装新前版本！');
        }

        deldir(UPGRADE_PATH);
        $name = 'system';
        $tp5upgradeTmpDir = RUNTIME_PATH . 'tp5upgrade' . DS;
        if (!is_dir($tp5upgradeTmpDir)) {
            @mkdir($tp5upgradeTmpDir, 0755, true);
        }
        try {
            $extend = [
                'version'   => $version,
                'domain'    =>$this->getTopHost(),
            ];
            //调用更新的方法
            Service::upgrade($name, $extend);
            Cache::rm('__menu__');
            upgradefile(['version'=>$version]);
			deldir(UPGRADE_PATH);
            $this->success('恭喜您：升级版本：'.$version.'成功!',url('/sql/'.$version.'.php'));
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
