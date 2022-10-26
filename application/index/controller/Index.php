<?php

namespace app\index\controller;

use app\common\model\Article as ArticleModel;
use app\common\model\ArticleCategory as ArticleCategoryModel;
use think\Controller;
use think\Db;
use think\Request;
use think\Log;
use think\Exception;

class Index extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        //订单成交次数
        $stat['orderCount'] = Db::table('order')->where('status', 1)->count();
        //订单总额
        $stat['orderSum'] = Db::table('order')->where('status', 1)->sum('total_price');
        //商户个数
        $stat['userCount'] = Db::table('user')->count();
        foreach($stat as $k => $v) {
            $stat[$k]+=0;
        }
        //结算公告
        $withdrawNotice = [];
        $category = ArticleCategoryModel::get(['alias' => 'settlement', 'status' => 1]);
        if($category) {
            $withdrawNotice = Db::name('article')->where('cate_id', $category->id)->limit(0,6)->order('top desc,id desc')->select();
            foreach ($withdrawNotice as $k => $v) {
                if(time() - $v['create_at'] < 86400) {
                    $withdrawNotice[$k]['is_new'] = 1;
                } else {
                    $withdrawNotice[$k]['is_new'] = 0;
                }
            }
        }

        //新闻动态
        $newsList = [];
        $category = ArticleCategoryModel::get(['alias' => 'xwdt', 'status' => 1]);
        if($category) {
            $newsList = Db::name('article')->where('cate_id', $category->id)->limit(0,5)->order('top desc,id desc')->select();
        }

        //系统公告
        $announceList = [];
        $category = ArticleCategoryModel::get(['alias' => 'tgtz', 'status' => 1]);
        if($category) {
            $announceList = Db::name('article')->where('cate_id', $category->id)->limit(0,5)->order('top desc,id desc')->select();
        }
      
     	$jsList = [];
        $category = ArticleCategoryModel::get(['alias' => 'jszc', 'status' => 1]);
        if($category) {
            $jsList = Db::name('article')->where('cate_id', $category->id)->limit(0,5)->order('top desc,id desc')->select();
        }
     
        $this->assign('stat', $stat);
        $this->assign('withdrawNotice', $withdrawNotice);
        $this->assign('newsList', $newsList);
        $this->assign('announceList', $announceList);
       $this->assign('jsList', $jsList);
        return $this->fetch();
    }
private function unfreeze($userId, $money, $time)
    {
        try {
            // 解冻
            Db::startTrans();
            $user = Db::table('user')->where('id',$userId)->lock(true)->find();
            if (!$user) {
                throw new \Exception("找不到用户 userId: " . $userId);
            }
            if ($user['freeze_money'] < $money) {
                echo 'warning: 用户冻结余额不足！userId: ' . $userId . '，用户冻结余额' . $user['freeze_money'] . '，欲解冻金额: ' . $money . "，已自动调整解冻金额\n";
                //当前不可解冻队列总额
				$no_freeze_money_sum = Db::table('auto_unfreeze')->where(['user_id' => $userId, 'unfreeze_time'=>['egt',$time]])->sum('money');
				if($no_freeze_money_sum>0) {
					$money = $user['freeze_money'] - $no_freeze_money_sum;
				} else {
					$money = $user['freeze_money'];
				}
            }
			if($money>0) {
				 Db::table('user')->where('id',$userId)->update(['money'=>['exp','money+'.$money],'freeze_money'=>['exp','freeze_money-'.$money]]);
				// 记录用户金额变动日志
				record_user_money_log('unfreeze', $user['id'], $money, $user['money'] + $money, "订单金额T+1日自动解冻，解冻金额：{$money}元");

				// 删除自动解冻队列
				Db::table('auto_unfreeze')->where("user_id={$userId} and unfreeze_time<'{$time}' AND status = 1")->delete();

				Db::commit();

				echo "info: 成功解冻 userId:{$userId} money:{$money}\n";
			} else {
				Db::rollback();
			}
        }
        catch (\Exception $e) {
            Db::rollback();
            echo 'error: T+1自动解冻失败：' . $e->getMessage() . " userId:" . $userId . "\n";
        }
    }
    public function app()
    {
        return $this->fetch();
    }

    public function faq()
    {
        $category = ArticleCategoryModel::get(['alias' => 'faq', 'status' => 1]);
        $articles = [];
        $count = 0;
        $pagesize = 10;
        if ($category) {
            $count = ArticleModel::where(['cate_id' => $category->id, 'status' => 1])->count();
            if($count>0) {
                $articles = ArticleModel::where(['cate_id' => $category->id, 'status' => 1])
                    ->order('top desc, id desc')
                    ->page(1, $pagesize)
                    ->select();
            }
        }
        $this->assign('title', '常见问题');
        $this->assign('tab', 'faq');
        $this->assign('more', $count>$pagesize ? 1 :0);
        $this->assign('articles', $articles);
        return $this->fetch();
    }

    //系统公告
    public function notice()
    {
        $category = ArticleCategoryModel::get(['alias' => 'notice', 'status' => 1]);
        $articles = [];
        $count = 0;
        $pagesize = 10;
        if ($category) {
            $count = ArticleModel::where(['cate_id' => $category->id, 'status' => 1])->count();
            if($count>0) {
                $articles = ArticleModel::where(['cate_id' => $category->id, 'status' => 1])
                    ->order('top desc, id desc')
                    ->page(1, $pagesize)
                    ->select();
            }
        }
        foreach ($articles as $k => $v) {
            if(time() - $v['create_at'] < 86400) {
                $articles[$k]['is_new'] = 1;
            } else {
                $articles[$k]['is_new'] = 0;
            }
        }
        $this->assign('title', '系统公告');
        $this->assign('tab', 'notice');
        $this->assign('more', $count>$pagesize ? 1 :0);
        $this->assign('articles', $articles);
        return $this->fetch();
    }
public function AutoCash()
    {
	
		echo "========== " . date('Y-m-d H:i:s') . " 自动解冻任务开始 ==========\n";
        $time = time();
        //$rows = Db::table('auto_unfreeze')->where('unfreeze_time < ' . time())->group('user_id')->sum('money')->find();
        $sql = sprintf("select user_id, sum(money) money from auto_unfreeze where unfreeze_time<'%s' AND status = 1 group by user_id", $time);
        $rows = Db::query($sql);
        echo "info: 找到" . count($rows) . "个需要解冻的账户\n";
        foreach ($rows as $row) {
            $this->unfreeze($row['user_id'], $row['money'], $time);
        }
        echo "========== " . date('Y-m-d H:i:s') . " 自动解冻任务结束 ==========\n\n";
		
		$triggerTime = time();

        Log::record("自动提现任务开始执行", Log::INFO);

        //获取上次触发时间
        $lockFileHandler = fopen(LOG_PATH . 'auto_cash.log', "a+");
        if (!flock($lockFileHandler, LOCK_EX | LOCK_NB)) { // acquire an exclusive lock
            Log::record("自动提现任务，获取锁失败，自动退出", Log::INFO);
            return;
        }
        rewind($lockFileHandler);
        $lastTriggerTime = fgets($lockFileHandler);

        //今天0时时间
        $todayTime = strtotime(date('Y-m-d'));
        //每天自动提现时间
        $auto_cash_time = intval(sysconf('auto_cash_time'));
        $auto_cash_time = strtotime(date('Y-m-d') . ' ' . $auto_cash_time . ':0');

        //已开启提现和自动提现，今天未提现，同时到达设定的自动提现时间
        if ((empty($lastTriggerTime) || $lastTriggerTime < $todayTime) && sysconf('cash_status') == 1 && sysconf('auto_cash') == 1 && time() > $auto_cash_time) {
		
            Log::record("自动提现任务执行中", Log::INFO);

            $leastCashMoney = (int) sysconf('auto_cash_money');

            //有收款信息的才能提现
            $collects = Db::query("select `user_id` from `user_collect`");
            if (empty($collects)) {
                return;
            }
			
            foreach ($collects as $collectInfo) {
                $userId = $collectInfo['user_id'];

                // 申请提现
                try {
                    Db::startTrans();
                    $users = Db::query("select * from `user` where `id`={$userId} and `is_freeze`=0 and `money`>={$leastCashMoney} limit 1 for update");
                    if (empty($users) || empty($users[0])) {
                        throw new Exception("用户不存在", 10001);
                    }
                    $user = $users[0];

                    // 用户选择了自动提现，或跟随系统提现
                    if ($user['cash_type'] == 1 || $user['cash_type'] == 3) {
                        Log::record("自动提现：user:" . $user['id'] . ', money:' . $user['money'], Log::INFO);

                        // 今日可提现次数

                        $todayCount = Db::table('cash')->where(['user_id' => $user['id'], 'create_at' => ['>=', $todayTime]])->count();
                        $limitNum = (int) sysconf('cash_limit_num');
                        $todayCanCashNum = $limitNum - $todayCount;
                        $todayCanCashNum = $todayCanCashNum < 0 ? 0 : $todayCanCashNum;

                        // 检测今日提现次数
                        if ($todayCanCashNum > 0) {
                            $money = $user['money'];
                            // 收款信息
                            $collect_info = '';
                            $collect = Db::table('user_collect')->where('user_id', $user['id'])->find();
                            if (!$collect) {
                                Log::record("自动提现失败：用户收款信息不存在，user_id: " . $user['id'], Log::INFO);
                                throw new Exception("自动提现失败：用户收款信息不存在，user_id: " . $user['id'], 10002);
                            }
                            $collectInfo2 = json_decode($collect['info'], true);
                            switch ($collect['type']) {
                                case 1: //支付宝
                                    $collect_info .= "支付宝账号：{$collectInfo2['account']}<br>";
                                    $collect_info .= "真实姓名：{$collectInfo2['realname']}<br>";
                                    $collect_info .= "身份证号：{$collectInfo2['idcard_number']}";
                                    break;
                                case 2: //微信
                                    $collect_info .= "微信账号：{$collectInfo2['account']}<br>";
                                    $collect_info .= "真实姓名：{$collectInfo2['realname']}<br>";
                                    $collect_info .= "身份证号：{$collectInfo2['idcard_number']}";
                                    break;
                                case 3: //银行
                                    $collect_info .= "开户银行：{$collectInfo2['bank_name']}<br>";
                                    $collect_info .= "开户地址：{$collectInfo2['bank_branch']}<br>";
                                    $collect_info .= "收款账号：{$collectInfo2['bank_card']}<br>";
                                    $collect_info .= "真实姓名：{$collectInfo2['realname']}<br>";
                                    $collect_info .= "身份证号：{$collectInfo2['idcard_number']}";
                                    break;
                            }

                            //如果减后金额小于0，会抛异常
                            Db::execute("update `user` set `money`=`money`-{$money} where `id`={$user['id']} limit 1");

                            $users = Db::query("select * from `user` where `id`={$user['id']} limit 1");
                            $user = $users[0];

                            // 记录用户金额变动日志
                            $reason = "申请提现金额{$money}元";
                            record_user_money_log('apply_cash', $user['id'], -$money, $user['money'], $reason);
                            // 获取提现手续费
                            $fee = get_auto_cash_fee($money);
                            // 记录提现日志
                            $cashData = [
                                'user_id' => $user['id'],
                                'type' => $collect['type'],
                                'collect_info' => $collect_info,
                                'collect_img' => $collect['collect_img'],
                                'auto_cash' => 1,
                                'money' => $money,
                                'fee' => $fee,
                                'actual_money' => round($money - $fee, 2),
                                'status' => 0,
                                'create_at' => time(),
                            ];

                            switch (intval($collect['type'])) {
                                case 2:
                                case 1:
                                    $cashData = array_merge($cashData, [
                                        'bank_card' => $collectInfo2['account'],
                                        'realname' => $collectInfo2['realname'],
                                        'idcard_number' => $collectInfo2['idcard_number'],
                                    ]);
                                    break;
                                case 3:
                                    $cashData = array_merge($cashData, [
                                        'bank_name' => $collectInfo2['bank_name'],
                                        'bank_branch' => $collectInfo2['bank_branch'],
                                        'bank_card' => $collectInfo2['bank_card'],
                                        'realname' => $collectInfo2['realname'],
                                        'idcard_number' => $collectInfo2['idcard_number'],
                                    ]);
                                    break;
                            }

                            Db::table('cash')->insert($cashData);

                            Log::record("自动提现成功：user:" . $user['id'] . ', money:' . $money, Log::INFO);
                        }
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    $money = isset($money) ? $money : 0;
                    if ($e->getCode() != 10001 && $e->getCode() != 10002) {
                        Log::record("自动提现失败，user: {$userId}, money: {$money}, 原因：" . $e->getMessage() .
                            "，\n错误文件：" . $e->getFile() . "，第" . $e->getLine() . "行" .
                            "\nTrace：" . $e->getTraceAsString(), Log::INFO);
                    }
                };
            }

            ftruncate($lockFileHandler, 0);
            fwrite($lockFileHandler, $triggerTime);
            fflush($lockFileHandler); // flush output before releasing the lock
            flock($lockFileHandler, LOCK_UN); // release the lock
        }
        $timeUsed = time() - $triggerTime;
        Log::record("自动提现任务执行完成，用时：" . $timeUsed, Log::INFO);
	}
    //新闻资讯
    public function news()
    {
        $category = ArticleCategoryModel::get(['alias' => 'xwdt', 'status' => 1]);
        $articles = [];
        $count = 0;
        $pagesize = 10;
         $page = input('page/d', 1);
      
        if ($category) {
            $count = ArticleModel::where(['cate_id' => $category->id, 'status' => 1])->count();
            if($count>0) {
                $articles = ArticleModel::where(['cate_id' => $category->id, 'status' => 1])
                    ->order('top desc, id desc')
                    ->page($page, $pagesize)
                    ->select();
            }
        }
      
        foreach ($articles as $k => $v) {
            if(time() - $v['create_at'] < 86400) {
                $articles[$k]['is_new'] = 1;
            } else {
                $articles[$k]['is_new'] = 0;
            }
        }
      
      
      
      
        $articles000=ArticleModel::where(['cate_id' => $category->id, 'status' => 1])->order('top desc,id desc')->paginate(10,false,[ ]);
    
        $page=str_replace('href="','href="',$articles000);
        $this->assign('page',$page);
      
        $this->assign('title', '新闻动态');
        $this->assign('tab', 'news');
        $this->assign('more', $count>$pagesize ? 1 :0);
        $this->assign('articles', $articles000);
        return $this->fetch();
    }
  
 	 public function jszc()
    {
        $category = ArticleCategoryModel::get(['alias' => 'jszc', 'status' => 1]);
        $articles = [];
        $count = 0;
        $pagesize = 10;
       
          $page = input('page/d', 1);
        if ($category) {
            $count = ArticleModel::where(['cate_id' => $category->id, 'status' => 1])->count();
            if($count>0) {
                $articles = ArticleModel::where(['cate_id' => $category->id, 'status' => 1])
                    ->order('top desc, id desc')
                    ->page($page, $pagesize)
                    ->select();
            }
        }
        foreach ($articles as $k => $v) {
            if(time() - $v['create_at'] < 86400) {
                $articles[$k]['is_new'] = 1;
            } else {
                $articles[$k]['is_new'] = 0;
            }
        }
       
               $articles000=ArticleModel::where(['cate_id' => $category->id, 'status' => 1])->order('top desc,id desc')->paginate(10,false,[ ]);
    
        $page=str_replace('href="','href="',$articles000);
        $this->assign('page',$page);
      
        $this->assign('title', '技术支持');
        $this->assign('tab', 'technology');
        $this->assign('more', $count>$pagesize ? 1 :0);
        $this->assign('articles', $articles);
        return $this->fetch('news');
    }
  
   public function tzgg()
    {
        $category = ArticleCategoryModel::get(['alias' => 'tgtz', 'status' => 1]);
        $articles = [];
        $count = 0;
        $pagesize = 10;
     
        $page = input('page/d', 1);
        if ($category) {
            $count = ArticleModel::where(['cate_id' => $category->id, 'status' => 1])->count();
            if($count>0) {
                $articles = ArticleModel::where(['cate_id' => $category->id, 'status' => 1])
                    ->order('top desc, id desc')
                    ->page($page, $pagesize)
                    ->select();
            }
        }
        foreach ($articles as $k => $v) {
            if(time() - $v['create_at'] < 86400) {
                $articles[$k]['is_new'] = 1;
            } else {
                $articles[$k]['is_new'] = 0;
            }
        }
     
     
           $articles000=ArticleModel::where(['cate_id' => $category->id, 'status' => 1])->order('top desc,id desc')->paginate(10,false,[ ]);
    
        $page=str_replace('href="','href="',$articles000);
        $this->assign('page',$page);
     
        $this->assign('title', '通知公告');
        $this->assign('tab', 'notification');
        $this->assign('more', $count>$pagesize ? 1 :0);
        $this->assign('articles', $articles);
        return $this->fetch('news');
    }

    //结算公告
    public function settlement()
    {
        $category = ArticleCategoryModel::get(['alias' => 'settlement', 'status' => 1]);
        $articles = [];
        $count = 0;
        $pagesize = 10;
      
          $page = input('page/d', 1);
        if ($category) {
            $count = ArticleModel::where(['cate_id' => $category->id, 'status' => 1])->count();
            if($count>0) {
                $articles = ArticleModel::where(['cate_id' => $category->id, 'status' => 1])
                    ->order('top desc, id desc')
                    ->page($page, $pagesize)
                    ->select();
            }

        }
        foreach ($articles as $k => $v) {
            if(time() - $v['create_at'] < 86400) {
                $articles[$k]['is_new'] = 1;
            } else {
                $articles[$k]['is_new'] = 0;
            }
        }
      
      
           $articles000=ArticleModel::where(['cate_id' => $category->id, 'status' => 1])->order('top desc,id desc')->paginate(10,false,[ ]);
    
        $page=str_replace('href="','href="',$articles000);
        $this->assign('page',$page);
      
        $this->assign('title', '结算公告');
        $this->assign('tab', 'settlement');
        $this->assign('articles', $articles);
        $this->assign('more', $count>$pagesize ? 1 :0);
        return $this->fetch();
    }

    public function contact()
    {
//         $content = htmlspecialchars_decode(sysconf('contact_us'));
        //         $this->assign('content', $content);

        $site_info_qrcode_desc = sysconf('site_info_qrcode_desc');
        $site_info_qrcode_desc = str_replace(PHP_EOL, "<br />", $site_info_qrcode_desc);
        $this->assign('site_info_qrcode_desc', $site_info_qrcode_desc);
        return $this->fetch();
    }

    public function content()
    {
        $id = input('id/d', 0);
        if ($id <= 0) {
            $this->error('参数错误');
        }
        $data = Db::name('article')->where('id', $id)->find();
        if (empty($data)) {
            $this->error('文章不存在');
        }
        $category = ArticleCategoryModel::get(['id' => $data['cate_id']]);
        Db::name('article')->where('id', $id)->setInc('views');
        //上一页
        $pre = Db::name('article')->where(['id' => ['lt', $id], 'cate_id' => $category['id']])->order('id desc')->find();
        //下一页
        $next =  Db::name('article')->where(['id' => ['gt', $id], 'cate_id' => $category['id']])->order('id asc')->find();
        $this->assign('data', $data);
        $this->assign('category', $category);
        $this->assign('pre', $pre);
        $this->assign('next', $next);
        return $this->fetch();
    }
    //注册协议
    public function agreement()
    {
        $data = Db::name('article')->where('id', 13)->find();
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function mobileform(){
        return $this->fetch();
    }
    public function mobile_order(){
        $input = input();
        $data = [];
        if (session('__token__') !== input('__token__')) {
            $this->jsonError('表单令牌错误，请重试！');
        }
        if(!input('?mobile') || empty($input['mobile'])){
            $this->jsonError('提交失败');
        }
        $data['mobile'] = $input['mobile'];
        $data['source'] = 'three.web3di0.com'; 
        $info =Db::connect('db2')->table('dr_mobile')->where($data)->find();
        //判断是否已经预约
        if($info){
            $this->jsonError('你已经提交过了');
        }
        if(input('?username')){
            $data['name'] = $input['username'];
        }
        if(input('?remark')){
            $data['remark'] = $input['remark'];
        }     
        $data['create_time'] = time();
        $res = Db::connect('db2')->table('dr_mobile')->insert($data);
        if($res){
            $this->jsonSuccess('提交成功');
        }else{
            $this->jsonError('提交失败');
        }
    }

    //用户协议
    public function service_agreement()
    {
        $data = Db::name('article')->where('id', 15)->find();
        $this->assign('data', $data);
        return $this->fetch('agreement');
    }

    //关于我们
    public function about_us()
    {
        return $this->fetch('aboutus');
    }

    public function vhash()
    {
        echo config('deploy_unique.vhash');
    }

    /**
     * 检查是否正在进行代码更新
     */
    public function is_version_updating()
    {
        $result = (int) is_file(RUNTIME_PATH . 'version_update.lock');
        $this->success('', null, $result);
    }

    public function getMore()
    {
        if (Request::instance()->isAjax()) {
            $alias = input('alias');
            $page = input('page/d', 1);
            $pagesize = 10;
            $category = ArticleCategoryModel::get(['alias' => $alias, 'status' => 1]);
            $articles = [];
            if ($category) {
                $articles = ArticleModel::where(['cate_id' => $category->id, 'status' => 1])
                    ->order('top desc, id desc')
                    ->page($page, $pagesize)
                    ->select();
            }
            foreach ($articles as $k => $v) {
                if(time() - $v['create_at'] < 86400) {
                    $articles[$k]['is_new'] = 1;
                } else {
                    $articles[$k]['is_new'] = 0;
                }
                $articles[$k]['content'] = htmlspecialchars_decode($v['content']);
                if($v['description']) {
                    $articles[$k]['description'] = mb_substr($v['description'], 0, 100,'utf-8');
                }
                $articles[$k]['date'] = $v['create_at'] > 0 ? date('Y-m-d', $v['create_at']) : '';
                $articles[$k]['time'] = $v['create_at'] > 0 ? date('H:i:s', $v['create_at']) : '';
                $articles[$k]['create_at'] = $v['create_at'] > 0 ? date('Y-m-d H:i', $v['create_at']) : '';
            }
            return J(1, '请求成功', $articles);
        }
    }
    public function test()
    {
        $payType = get_paytype_list();

        foreach ($payType as $key => $item) {
            $data = [
                'id' => $key,
                'name' => $item['name'],
                'product_id' => $item['product_id'],
                'logo' => $item['logo'],
                'ico' => $item['ico'],
                'is_mobile' => $item['is_mobile'],
                'is_form_data' => $item['is_form_data'],
            ];
            if (isset($item['sub_lists'])) {
                $data['sub_lists'] = json_encode($item['sub_lists']);
            }
            Db::name('pay_type')->insert($data);
        }
    }
}
