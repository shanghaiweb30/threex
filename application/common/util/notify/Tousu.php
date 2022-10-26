<?php

namespace app\common\util\notify;

use think\Log;

/**
 * 投诉通知
 * Class Stock
 * @package app\common\notify
 */
class Tousu
{

   
    public function notify($user,$orderid)
    {
        //站内信，邮件通知
        $title = "投诉单号:{$orderid}";
        $content = "请登录会员中心处理您的投诉，如24小时内不处理，我们将判为买家胜诉";
        if (1 == 1) {
          
           sendMessage(0, $user['id'], $title, $content);
        } else {
            // 发送邮件
            try {
                sendMail($user['email'], '【' . sysconf('site_name') . '】' . $title, $content, '', true);
            } catch (\Exception $e) {
                Log::record("投诉提醒邮件发送失败：" . $e->getMessage() . '。 ' .
                    $user['email']. '【' . sysconf('site_name') . '】' . $title . ' | ' . $content, Log::ERROR);
            }
        }

        if($user['ts'] && sysconf('wechat_complaint_template')) {
        
            if($user['openid']) {
                $wechat = &load_wechat('Message');
                $wechat->sendTemplateMessage([
                    'touser' => $user['openid'],
                    'template_id' => sysconf('wechat_complaint_template'),
                    'url' => sysconf('site_domain') . '/Index/order/complaintpass?trade_no=' . $orderid,
                    'data' => [
                        'id' => ['value' => $orderid],
                        'type' => ['value' =>'商品投诉'],
                        'remark' => ['value' => '请登录会员中心处理您的投诉，如24小时内不处理，我们将判为买家胜诉']
                    ],
                ]);
            }
        }
    }
}