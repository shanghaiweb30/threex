<?php

/**

 * 商品管理

 */



namespace app\manage\controller;



use controller\BasicAdmin;

use think\Db;

use think\Request;

use app\common\model\User as UserModel;

use app\common\model\Goods as GoodsModel;

use service\LogService;



class Goods extends BasicAdmin

{

    public function _initialize()

    {

        parent::_initialize();

        $this->assign('self_url', '#' . Request::instance()->url());

        $this->assign('self_no_url', Request::instance()->url());

    }



    public function index()

    {

        $this->assign('title', '商品列表');



        ////////////////// 查询条件 //////////////////

        $query = [

            'user_id' => input('user_id/s', ''),

            'username' => input('username/s', ''),

            'name' => input('name/s', ''),

            'status' => input('status/s', ''),

            'date_range' => input('date_range/s', ''),

        ];

        $where = $this->genereate_where($query);



        $goodsList = Db::name('goods')->alias('a')

            ->join('user b', 'a.user_id = b.id')

            ->join('link c', 'a.id = c.relation_id AND c.relation_type = "goods"')

            ->field('a.*,b.username,c.token as link')

            ->where($where)

            ->order('id desc')

            ->paginate(30, false, [

                'query' => $query

            ]);



        // 分页

        $page = str_replace('href="', 'href="#', $goodsList->render());

        $this->assign('page', $page);

        $this->assign('goodsList', $goodsList);



        $sum_money = Db::name('goods')->alias('a')->where($where)->sum('price');

        $this->assign('sum_money', $sum_money);

        $sum_order = Db::name('goods')->alias('a')->where($where)->count();

        $this->assign('sum_order', $sum_order);

        return $this->fetch();

    }


public function goodshsz()

    {

        $this->assign('title', '商品回收站');



        ////////////////// 查询条件 //////////////////

        $query = [

            'user_id' => input('user_id/s', ''),

            'username' => input('username/s', ''),

            'name' => input('name/s', ''),

            'status' => input('status/s', ''),

            'date_range' => input('date_range/s', ''),
            
            'status' => input('status/s', ''),
            
         

        ];

        $where = $this->genereate_wherehsz($query);
        
         $goodsList = Db::name('goods')->alias('a')

            ->join('user b', 'a.user_id = b.id')

            ->join('link c', 'a.id = c.relation_id AND c.relation_type = "goods"')

            ->field('a.*,b.username,c.token as link')

            ->where($where)
            
            ->where('a.delete_at >0')

            ->order('id desc')

            ->paginate(30, false, [

                'query' => $query

            ]);
            
 


        // 分页

        $page = str_replace('href="', 'href="#', $goodsList->render());

        $this->assign('page', $page);

        $this->assign('goodsList', $goodsList);



        $sum_money = Db::name('goods')->alias('a')->where($where)->sum('price');

        $this->assign('sum_money', $sum_money);

        $sum_order = Db::name('goods')->alias('a')->where($where)->count();

        $this->assign('sum_order', $sum_order);

        return $this->fetch();

    }

    

    public function goodschi()

    {

        $this->assign('title', '商品池');



        ////////////////// 查询条件 //////////////////

        $query = [

            'user_id' => input('user_id/s', ''),

            'content' => input('name/s', ''),

            'istop' => input('istop/s', ''),
			
			'status' => input('status/s', ''),

            'date_range' => input('date_range/s', ''),

        ];

        $where = $this->genereate_wheregoods($query);

     

        $goodsList = Db::name('goods_chi')->alias('a')

            ->join('user b', 'a.user_id = b.id')

            ->field('a.*,b.username')

            ->where($where)

            ->order('istop desc,flsorts asc,sorts asc,id desc')

            ->paginate(30, false, [

                'query' => $query

            ]);


         
        // 分页

        $page = str_replace('href="', 'href="#', $goodsList->render());

        $this->assign('page', $page);

        $this->assign('goodsList', $goodsList);



    

        return $this->fetch();

    }

 public function goodschiprice()

    {

        $this->assign('title', '商品池置顶价格');

        $goodspriceList = Db::name('goods_chi_price')->where('status=1')->order('sorts asc,id desc')->paginate(30);


        $page = str_replace('href="', 'href="#', $goodspriceList->render());

        $this->assign('page', $page);

        $this->assign('goodspriceList', $goodspriceList);



    

        return $this->fetch();

    }


public function chipriceadd(){
	
	if($_POST){
		$data=$_POST;
		$data['status']=1;
		Db::name('goods_chi_price')->insert($data);
		$this->success('更新成功！','');
	}
	 return $this->fetch();
}


public function chipriceedit(){
	
	if($_POST){
		$data=$_POST;
		Db::name('goods_chi_price')->update($data);
		$pxdata['flsorts']=$data['sorts'];
		Db::name('goods_chi')->where('zdtime='.$data['hours'])->update($pxdata);
		
		$this->success('更新成功！','');
	}
	  $id=input('id/d', '0');
	  $data=Db::name('goods_chi_price')->find($id);
	  $this->assign('data', $data);
	 return $this->fetch();
}


 public function delchiprice()

    {

        if ($this->request->post()) {

            $id = input('id/d', 0);

            $goods =Db::name('goods_chi_price')->find($id);

            if (!$goods) {

                return J(1, '不存在！');

            }



            $res = Db::name('goods_chi_price')->delete($id);

            if ($res !== false) {

                LogService::write('商品池价格管理', '成功' . '删除商品池价格，商品池价格ID:' . $id);

                return J(200, '删除成功！');

            } else {

                return J(500, '删除失败！');

            }

        }

    }




    /**

     * 生成查询条件

     */

    protected function genereate_where($params)

    {

        $where = [];

        $action = Request::instance()->action();

        switch ($action) {

            case 'index':

                if ($params['user_id'] !== '') {

                    $where['a.user_id'] = $params['user_id'];

                }

                if ($params['username']) {

                    $ids = Db::name('User')->field('id')->where(['username' => ['like', '%' . $params['username'] . '%']])->select();

                    if ($ids) {

                        $temp = [];

                        foreach ($ids as $id) {

                            $temp[] = $id['id'];

                        }

                        $temp = implode(',', $temp);

                        $where['a.user_id'] = ['IN', $temp];

                    } else {

                        $where['a.user_id'] = 0;

                    }

                }

                if ($params['name'] !== '') {

                    $where['a.name'] = ['like', '%' . $params['name'] . '%'];

                }

                if ($params['status'] !== '') {

                    $where['a.status'] = $params['status'];

                }
				


                if ($params['date_range'] && strpos($params['date_range'], ' - ') !== false) {

                    list($startDate, $endTime) = explode(' - ', $params['date_range']);

                    $where['a.create_at'] = ['between', [strtotime($startDate . ' 00:00:00'), strtotime($endTime . ' 23:59:59')]];

                }

                $where['a.delete_at'] = null;

                break;

        }

        return $where;

    }


protected function genereate_wherehsz($params)

    {

        $where = [];

        $action = Request::instance()->action();

        switch ($action) {

            case 'goodshsz':
                
                
                   if ($params['user_id'] !== '') {

                    $where['a.user_id'] = $params['user_id'];

                }

                if ($params['username']) {

                    $ids = Db::name('User')->field('id')->where(['username' => ['like', '%' . $params['username'] . '%']])->select();

                    if ($ids) {

                        $temp = [];

                        foreach ($ids as $id) {

                            $temp[] = $id['id'];

                        }

                        $temp = implode(',', $temp);

                        $where['a.user_id'] = ['IN', $temp];

                    } else {

                        $where['a.user_id'] = 0;

                    }

                }

                if ($params['name'] !== '') {

                    $where['a.name'] = ['like', '%' . $params['name'] . '%'];

                }

                if ($params['status'] !== '') {

                    $where['a.status'] = $params['status'];

                }
				


                if ($params['date_range'] && strpos($params['date_range'], ' - ') !== false) {

                    list($startDate, $endTime) = explode(' - ', $params['date_range']);

                    $where['a.create_at'] = ['between', [strtotime($startDate . ' 00:00:00'), strtotime($endTime . ' 23:59:59')]];

                }

             //   $where['a.delete_at'] =  null;

                break;

        }

        return $where;

    }



    /**

     * 生成查询条件

     */

    protected function genereate_wheregoods($params)

    {

        $where = [];

        $action = Request::instance()->action();

        switch ($action) {

            case 'goodschi':

                if ($params['user_id'] !== '') {

                    $where['a.user_id'] = $params['user_id'];

                }

               

                if ($params['content'] !== '') {

                    $where['a.content'] = ['like', '%' . $params['content'] . '%'];

                }

                if ($params['status'] !== '') {

                    $where['a.status'] = $params['status'];

                }
				
				  if ($params['istop'] !== '') {

                    $where['a.istop'] = $params['istop'];

                }


                if ($params['date_range'] && strpos($params['date_range'], ' - ') !== false) {

                    list($startDate, $endTime) = explode(' - ', $params['date_range']);

                    $where['a.addtime'] = ['between', [strtotime($startDate . ' 00:00:00'), strtotime($endTime . ' 23:59:59')]];

                }

               

                break;

        }

        return $where;

    }



    /**

     * 改变状态

     */

    public function change_status()

    {

        if (!$this->request->isAjax()) {

            $this->error('错误的提交方式！');

        }

        $id = input('id/d', 0);

        $status = input('value/d', 1);

        $goods = Db::name('Goods')->where([

            'id' => $id,

        ])->find();

        if ($goods['is_freeze'] == 1) {

            $this->error('请先解冻商品后再上架！');

        }



        $res = Db::name('Goods')->where([

            'id|proxy_id' => $id,

        ])->update([

            'status' => $status

        ]);

        $remark = $status == 1 ? '上架' : '下架';

        if ($res !== false) {

            LogService::write('商品管理', '成功' . $remark . '商品，商品ID:' . $id);

            $this->success('更新成功！', '');

        } else {

            $this->error('更新失败，请重试！');

        }

    }



    /**

     * 改变冻结状态

     */

    public function change_freeze()

    {

        if (!$this->request->isAjax()) {

            $this->error('错误的提交方式！');

        }

        $id = input('id/d', 0);

        $status = input('value/d', 1);

        $update_data = [

            'is_freeze' => $status

        ];

        if ($status == 1) {

            $update_data['status'] = !$status;

        }

        $res = Db::name('Goods')->where([

            'id' => $id,

        ])->update($update_data);

        $remark = $status == 1 ? '冻结' : '解冻';

        if ($res !== false) {

            LogService::write('商品管理', '成功' . $remark . '商品，商品ID:' . $id);

            $this->success('更新成功！', '');

        } else {

            $this->error('更新失败，请重试！');

        }

    }



    public function change_trade_no_status()

    {

        if ($this->request->isGet()) {

            return $this->fetch();

        }

        if ($this->request->isPost()) {

            $data = input('');

            if (strlen($data['user_order_profix']) > 3) {

                $this->error('订单前缀不能超过3位');

            }

            sysconf('order_type', $data['order_type']);

            sysconf('user_order_profix', $data['user_order_profix']);

            $this->success('操作成功', '');

        }

    }



    /**

     * 删除商品

     * @throws \think\Exception

     * @throws \think\exception\PDOException
     * 
     * 

     */

    public function del()

    {

        if ($this->request->post()) {

            $id = input('id/d', 0);

            $goods = GoodsModel::get(['id' => $id]);

            if (!$goods) {

                return J(1, '不存在该商品！');

            }



            $res = $goods->delete();

            if ($res !== false) {

                LogService::write('商品管理', '成功' . '删除商品，商品ID:' . $id);

                return J(200, '删除成功！');

            } else {

                return J(500, '删除失败！');

            }

        }

    }
    
    
     public function deldel()

    {

        if ($this->request->post()) {

            $id = input('id/d', 0);

           



            $res = Db::name('goods')->delete($id);

            if ($res !== false) {

                LogService::write('商品管理', '成功' . '删除商品，商品ID:' . $id);

                return J(200, '删除成功！');

            } else {

                return J(500, '删除失败！');

            }

        }

    }


 public function delhui()

    {

        if ($this->request->post()) {

            $id = input('id/d', 0);

            

 $update_data['delete_at']=null;
 $res = Db::name('Goods')->where([

            'id' => $id,

        ])->update($update_data);

            if ($res !== false) {

                LogService::write('商品管理', '成功' . '恢复商品，商品ID:' . $id);

                return J(200, '恢复成功！');

            } else {

                return J(500, '恢复失败！');

            }

        }

    }
    
    
    public function delbatchhsz(){
        
    if ($this->request->post()) {

            $id = input('id/d', 0);
         
       if($id>0)
       
       {
           
           
    switch ($id)
        {
        case 1:
         $startTime = date('Y-m-d', strtotime("-30 day"));
          break;  
        case 3:
         $startTime = date('Y-m-d', strtotime("-3 day"));
          break;
         case 7:
         $startTime = date('Y-m-d', strtotime("-7 day"));
          break;
        }
              
      
    
     
        $startTime = strtotime($startTime);
      
        $where['delete_at'] = ['<', $startTime];
        $count = Db::name('Goods')->where($where)->count();
        if ($count == 0) {
            $this->error('该日期范围没有删除商品！');
        }
        $res = Db::name('Goods')->where($where)->delete();
        if ($res) {
            LogService::write('商品管理', '批量删除商品成功，删除数量：' . $count);
            return J(200, '删除成功！');
        } else {
            return J(500, $e->getMessage());
        }
       }
        
      }
   }

 public function zdchi()

    {

        if ($_POST) {
            $zddata['id']=$_POST['id'];
			$zdt=explode("-",$_POST['zdtime']);
			if($_POST['istop']==1){
			$zddata['istop']=$_POST['istop'];
            $zddata['toptime']=time()+$zdt[0]*60*60;
			$zddata['zdtime']=$zdt[0];
			$zddata['sorts']=$_POST['sorts'];
			$zddata['flsorts']=$zdt[1];
			
			}else{
			$zddata['istop']=$_POST['istop'];
            $zddata['toptime']=time();
			$zddata['zdtime']=0;
			$zddata['sorts']=$_POST['sorts'];
			}
            $res = Db::name('goods_chi')->update($zddata);
            $this->success('操作成功', '');
        }
		
		$id = input('id/d', 0);
        $goods =Db::name('goods_chi')->find($id);
		$this->assign('goods', $goods);
		$goodspriceList = Db::name('goods_chi_price')->where('status=1')->order('id desc')->select();
        $this->assign('goodspriceList', $goodspriceList);
        return $this->fetch();
    }

 public function shchi()

    {

        if ($this->request->post()) {

            $id = input('id/d', 0);

            $goods =Db::name('goods_chi')->find($id);

            if (!$goods) {

                return J(1, '不存在该商品池！');

            }

            
			
            $zddata['id']=$id;
			$zddata['status']=1;
            $res = Db::name('goods_chi')->update($zddata);

            if ($res !== false) {

                LogService::write('商品池管理', '审核成功' . '审核商品池，商品池ID:' . $id);

                return J(200, '审核成功！');

            } else {

                return J(500, '审核失败！');

            }

        }

    }


 public function shallchi()

    {

        if ($this->request->post()) {

            $good_ids = input('');
            $good_ids = isset($good_ids['ids']) ? $good_ids['ids'] : [];

            $goods =Db::name('goods_chi')->where(['id' => ['in', $good_ids]])->select();

            if (!$goods) {

                return J(1, '不存在该商品池！');

            }

			$zddata['status']=1;
            $res =Db::name('goods_chi')->where(['id' => ['in', $good_ids]])->update($zddata);

            if ($res !== false) {

                LogService::write('商品池管理', '审核成功' . '审核商品池，商品池ID:' . json_encode($good_ids));

                return J(200, '审核成功！');

            } else {

                return J(500, '审核失败！');

            }

        }

    }
    

     public function delchi()

    {

        if ($this->request->post()) {

            $id = input('id/d', 0);

            $goods =Db::name('goods_chi')->find($id);

            if (!$goods) {

                return J(1, '不存在该商品池！');

            }



            $res = Db::name('goods_chi')->delete($id);

            if ($res !== false) {

                LogService::write('商品池管理', '成功' . '删除商品池，商品池ID:' . $id);

                return J(200, '删除成功！');

            } else {

                return J(500, '删除失败！');

            }

        }

    }

}


