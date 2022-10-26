<?php

namespace app\merchant\controller;

use app\common\util\Sms;
use app\common\model\UserCollect as UserCollectModel;
use app\common\model\UserLoginLog;
use app\common\model\UserChannel as UserChannelModel;
use app\common\model\User as UserModel;
use service\MerchantLogService;

class Useryz extends Baseyz {


    public function codes() {
		
		if (!$this->request->isPost()) {
            $this->setTitle('验证安全码');
            return $this->fetch();
        }
        $password      = input('password/s', '');
		
		 if ($this->user->codes != $password) {
            $this->error('安全码输入错误！');
        }else{
			
			session('merchant.user_codes', time() + 60*10);
			$this->success('输入正确请继续操作...', url('/merchant/index'));
		}
		
        
      
    }

   
    
}
