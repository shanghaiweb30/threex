<?php

namespace app\common\util\notify;

use think\Log;

/**
 * 提现通知
 * Class Stock
 * @package app\common\notify
 */
class Tixian
{

   
    public function notify($user,$money,$fee,$realmoney)
    {
        //站内信，邮件通知
        $title = "你好".$user['username']."今天已为您结算成功";
        $content = "提现金额：".$money."元扣手续费".$fee."元到账金额".$realmoney."元,感谢你的使用";
        if (1 == 1) {
          
           sendMessage(0, $user['id'], $title, $content);
        } else {
            // 发送邮件
            try {
                sendMail($user['email'], '【' . sysconf('site_name') . '】' . $title, $content, '', true);
            } catch (\Exception $e) {
                Log::record("提现诉提醒邮件发送失败：" . $e->getMessage() . '。 ' .
                    $user['email']. '【' . sysconf('site_name') . '】' . $title . ' | ' . $content, Log::ERROR);
            }
        }

        if($user['js'] && sysconf('wechat_cash_template')) {
        
            if($user['openid']) {
                $wechat = &load_wechat('Message');
                $wechat->sendTemplateMessage([
                    'touser' => $user['openid'],
                    'template_id' => sysconf('wechat_cash_template'),
                    'url' => sysconf('site_domain') . '/merchant/cash/index',
                    'data' => [
                        'first' => ['value' => $title],
                        'keyword1' => ['value' =>$realmoney],
                        'keyword2' => ['value' =>date('Y-m-d H:i:s')],
                        'remark' => ['value' => $content]
                    ],
                ]);
            }
        }
    }
}