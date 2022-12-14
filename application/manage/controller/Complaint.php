<?php
/**
 * 投诉管理
 */

namespace app\manage\controller;
use service\FileService;
use app\common\model\ComplaintMessage;
use app\common\model\Complaint as ComplaintModel;
use app\common\model\Order as OrderModel;
use controller\BasicAdmin;
use service\LogService;
use think\Db;
use think\Exception;
use think\Request;

class Complaint extends BasicAdmin {
    public function _initialize() {
        parent::_initialize();
        $this->assign('self_url', '#' . Request::instance()->url());
        $this->assign('self_no_url', Request::instance()->url());
    }

    public function index() {
        $this->assign('title', '投诉管理');

        ////////////////// 查询条件 //////////////////
        $query = [
            'user_id'    => input('user_id/s', ''),
            'username'   => input('username/s', ''),
            'trade_no'   => input('trade_no/s', ''),
            'status'     => input('status/s', ''),
            'admin_read' => input('admin_read/s', ''),
            'type'       => input('type/s', ''),
            'date_range' => input('date_range/s', ''),
        ];
        $where = $this->genereate_where($query);

        $complaints = ComplaintModel::where($where)->order('id desc')->paginate(30, false, [
            'query' => $query,
        ]);
        // 分页
        $page = str_replace('href="', 'href="#', $complaints->render());
        $this->assign('page', $page);
        $this->assign('complaints', $complaints);

        $sum_order = ComplaintModel::where($where)->count();
        $this->assign('sum_order', $sum_order);

        return $this->fetch();
    }

    /**
     * 投诉详情
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail() {
    	
    	if (empty($_SERVER['HTTP_REFERER'])) { 
           exit('&#x60A8;&#x8BBF;&#x95EE;&#x7684;&#x662F;&#x5546;&#x4E1A;&#x7248;&#xFF0C;&#x5B98;&#x65B9;&#x7F51;&#x5740;&#x4E3A;&#x77;&#x77;&#x77;&#x2E;&#x61;&#x38;&#x74;&#x67;&#x2E;&#x63;&#x6F;&#x6D;');
         }
         
        
        //获取投诉内容
        $id        = input('id/d');
        $complaint = ComplaintModel::where(['id' => $id])->find();
        if ($complaint) {
            //标记已读
            $complaint->admin_read = 1;
            $complaint->save();

            $this->assign('complaint', $complaint);

            //获取投诉对话内容
            $messages = DB::name('complaint_message')->where(['trade_no' => $complaint['trade_no']])->select();
            $this->assign('messages', $messages);

            return $this->fetch('detail');
        } else {
            $this->error('投诉不存在');
        }
    }
	
	/**
     * 发送沟通内容
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function send()
    {
        $content = input('content/s', '');
        if (empty($content)) {
            return J(500, '请输入沟通内容');
        }


        $id = input('id/d', '');
        $complaint = ComplaintModel::where(['id' => $id])->find();
        if ($complaint) {
            $data = [
                'from' => 1,
                'trade_no' => $complaint['trade_no'],
                'content' => $content,
                'create_at' => time(),
            ];
            ComplaintMessage::create($data);
            return J(200, '发送成功');
        } else {
            return J(500, '登录超时，请重新登录');
        }
    }

    /**
     * 发送图片
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sendImg()
    {
        //获取上传文件
        $file = $this->request->file('image');

        if($file){
        	 
       	
    

            //检查文件的扩展名
            $ext = strtolower(pathinfo($file->getInfo('name'), PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','gif','png'])){
                //检查投诉是否存在
                $id = input('id/d', '');
                $complaint = ComplaintModel::where(['id' => $id])->find();
                if ($complaint) {
                    //保存图片
                    $md5 = [uniqid(), uniqid()];
                    $filename = join('/', $md5) . ".{$ext}";
                    
                   if(sysconf('storage_type')=='local'){
                       
                       $info = $file->move('static' . DS . 'upload' . DS . $md5[0], $md5[1], true);

                    if ($info){
                    	
                    	               //生成水印
                $water_path = $info->getFilename();
                
          
                //打开图片
                $image =\think\Image::open($info);
                
                if($image){
                
             
                  $res=  $image->text(' ','static' . DS . 'simkai.ttf','20','#00000',\think\Image::THUMB_CENTER)->save('static' . DS . 'upload' . DS . $md5[0] . DS .$water_path);
                  
                       $res=  $image->text(' ','static' . DS . 'simkai.ttf','20','#00000',\think\Image::THUMB_CENTER)->save('static' . DS . 'upload' . DS . $md5[0] . DS .$water_path);
                  
                }else{
                     unlink('/static' . DS . 'upload' . DS . $md5[0] . DS .$water_path);
                     return json(['code' => 'ERROR', 'msg' => '文件上传失败']);
                }
                  
                  
                        
                        $data = [
                            'from' => 1,
                            'trade_no' => $complaint->trade_no,
                            'content' =>'/static' . DS . 'upload' . DS . $md5[0] . DS .$water_path,
                            'content_type' => '1',
                            'create_at' => time(),
                        ];
                        ComplaintMessage::create($data);
                        return J(200, '发送成功');
                    }else{
                       return J(500, '发送失败，请稍候再试');  
                    }
                   
                   }

                   
						$result=FileService::qiniu($filename, $file->getInfo('tmp_name'));
    				        if ($result) {	
							$file_url=$result['url'];
                        $data = [
                            'from' => 1,
                            'trade_no' => $complaint->trade_no,
                            'content' => $file_url,
                            'content_type' => '1',
                            'create_at' => time(),
                        ];
                        ComplaintMessage::create($data);
                        return J(200, '发送成功');
                    }else {
                        return J(500, '发送失败，请稍候再试');
                    }
                } else {
                    return J(500, '登录超时，请重新登录');
                }
            }else{
                return J(500, '发送失败，不支持的图片文件格式');
            }
        }else {
            return J(500, '请上传举证图片');
        }
    }

    /**
     * 投诉裁决
     *
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function win() {
        //获取投诉内容
        $id        = input('id/d');
        $complaint = ComplaintModel::where(['id' => $id, 'status' => 0])->find();
        if ($complaint) {
            $result            = input('result/d');
            $complaint->status = 1; //已处理
            $complaint->result = $result; //记录胜诉方

            $order = OrderModel::get(['trade_no' => $complaint->trade_no]);

            DB::startTrans();

            try {
                //锁定用户金额
                $user = Db::table('user')->where('id', $complaint['user_id'])->lock(true)->find();

                $res = $complaint->save();
                if ($res) {
                    if ($result == 1) {
                        //如果是商家胜诉，那么它对应的订单资金允许解冻
                        $res = Db::table('auto_unfreeze')->where(['trade_no' => $complaint->trade_no])->update(['status' => 1]);
                        if ($res) {
                            //资金状态修改成功，解冻订单
                            $res = Db::table('order')->where(['trade_no' => $complaint->trade_no])->update(['is_freeze' => 0]);

                            if ($res) {

                                //判断是否 T0 结算的订单，如果是，需要返还商家余额
                                if (0 == $order->settlement_type) {
                                    $user    = Db::table('user')->where('id', $order->user->id)->lock(true)->find();
                                    $balance = round($user['money'] + $order->total_price, 3);
                                    Db::table('user')->where('id', $user['id'])->update(['money' => ['exp', 'money+' . $order->total_price], 'freeze_money' => ['exp', 'freeze_money-' . $order->total_price]]);
                                    // 记录用户金额变动日志
                                    record_user_money_log('freeze', $user['id'], $order->total_price, $balance, "T0订单投诉胜诉，解冻金额：{$order->total_price}元");
                                }

                                DB::commit();
                                return J(200, '判决成功');
                            }
                        }
                    } else {
                        $user   = Db::table('user')->where('id', $complaint['user_id'])->find();
                        $unfree = Db::table('auto_unfreeze')->where(['trade_no' => $complaint->trade_no,'user_id'=>$complaint['user_id']])->find();
                        //扣除商家冻结余额
                        $res = Db::table('user')->where('id', $complaint['user_id'])->update([
                            'freeze_money' => ['exp', 'freeze_money-' . $unfree['money']],
                        ]);
                        
                          if($order->is_proxy) {
                           	
                           	     $proxyuser = Db::table('user')->where('id', $order->proxy_user_id)->lock(true)->find();
                           	     $proxyunfree = Db::table('auto_unfreeze')->where(['trade_no' => $complaint->trade_no,'user_id'=>$proxyuser['id']])->find();
                                //败诉，钱需要扣掉，不再补回余额中
                                Db::table('user')->where('id', $proxyuser['id'])->update(['freeze_money' => ['exp', 'freeze_money-' . $proxyunfree['money']]]);
                                // 记录用户金额变动日志
                       
                                record_user_money_log('freeze',  $proxyuser['id'],-$proxyunfree['money'], $proxyuser['money'], "订单败诉，扣除冻结金额，扣除金额：{$proxyunfree['money']}元");
                           	
                           }
                           
                        if (0 == $order->settlement_type) {
                            // T0 订单因为没有更新，所以会返回0
                            $res += 1;
                        }
                        if ($res) {
                            record_user_money_log('freeze', $complaint['user_id'], -$unfree['money'], $user['money'], "订单败诉，扣除冻结金额，扣除金额：{$unfree['money']}元");

                            //如果是买家胜诉，那么它对应的订单资金需要返回给买家，删除冻结资金记录
                            Db::table('auto_unfreeze')->where(['trade_no' => $complaint->trade_no])->delete();
                            LogService::write('投诉裁决', '投诉 ' . $complaint->trade_no . ' 裁决为买家胜诉，删除冻结资金记录，资金额为：' . $unfree['money'] . '元');

                            //判断是否 T0 结算的订单，如果是，需要扣除冻结了的金额
                            if (0 == $order->settlement_type) {
                                $user = Db::table('user')->where('id', $order->user->id)->lock(true)->find();
                                //败诉，钱需要扣掉，不再补回余额中
                                Db::table('user')->where('id', $user['id'])->update(['freeze_money' => ['exp', 'freeze_money-' . $unfree['money']]]);
                                // 记录用户金额变动日志
                                record_user_money_log('freeze', $user['id'], $unfree['money'], $user['money'], "T0订单投诉败诉，扣除冻结金额：{$unfree['money']}元");
                            }
                           
                         
                            

                            DB::commit();
                            return J(200, '判决成功');
                        }
                    }
                }

                DB::rollback();
                return J(500, '判决失败');
            } catch (Exception $e) {
                DB::rollback();
                return J(500, '判决失败'.$e->getMessage());
            }
        } else {
            return J(500, '投诉不存在');
        }
    }

    /**
     * 生成查询条件
     */
    protected function genereate_where($params) {
        $where  = [];
        $action = Request::instance()->action();
        switch ($action) {
            case 'index':
                if ($params['user_id']) {
                    $where['user_id'] = $params['user_id'];
                }
                if ($params['username']) {
                    $where['user_id'] = Db::name('User')->where(['username' => $params['username']])->value('id');
                }
                if ($params['trade_no']) {
                    $where['trade_no'] = $params['trade_no'];
                }
                if ($params['status'] !== '') {
                    $where['status'] = $params['status'];
                }
                if ($params['admin_read'] !== '') {
                    $where['admin_read'] = $params['admin_read'];
                }
                if ($params['type'] !== '') {
                    $where['type'] = $params['type'];
                }
                if ($params['date_range'] && strpos($params['date_range'], ' - ') !== false) {
                    list($startDate, $endTime) = explode(' - ', $params['date_range']);
                    $where['create_at'] = ['between', [strtotime($startDate . ' 00:00:00'), strtotime($endTime . ' 23:59:59')]];
                }
                break;
        }
        return $where;
    }

    /**
     * 改变状态
     */
    public function change_status() {
        if (!$this->request->isAjax()) {
            $this->error('错误的提交方式！');
        }
        $id     = input('id/d', 0);
        $status = input('value/d', 1);
        $res    = Db::name('Complaint')->where([
            'id' => $id,
        ])->update([
            'status' => $status,
        ]);
        $remark = $status == 1 ? '已处理' : '未处理';
        if ($res !== false) {
            LogService::write('用户管理', '设置投诉为' . $remark . '，ID:' . $id);
            $this->success('更新成功！', '');
        } else {
            $this->error('更新失败，请重试！');
        }
    }

    /**
     * 改变读取状态
     */
    public function change_admin_read() {
        if (!$this->request->isAjax()) {
            $this->error('错误的提交方式！');
        }
        $id     = input('id/d', 0);
        $status = input('value/d', 1);
        $res    = Db::name('Complaint')->where([
            'id' => $id,
        ])->update([
            'admin_read' => $status,
        ]);
        $remark = $status == 1 ? '已读取' : '未读取';
        if ($res !== false) {
            LogService::write('用户管理', '设置投诉为' . $remark . '，ID:' . $id);
            $this->success('更新成功！', '');
        } else {
            $this->error('更新失败，请重试！');
        }
    }

    /**
     * 删除投诉
     */
    public function del() {
        if ($this->request->isPost()) {
            $id  = input('id/d', 0);
            $res = Db::name('Complaint')->where('id', $id)->delete();
            if (false !== $res) {
                LogService::write('用户管理', '删除投诉成功，ID:' . $id);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }
}
