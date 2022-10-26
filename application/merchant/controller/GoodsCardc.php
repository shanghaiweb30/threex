<?php

namespace app\merchant\controller;

use app\common\model\Goods as GoodsModel;
use app\common\model\GoodsCard as CardModel;
use app\common\model\GoodsCategory as CategoryModel;
use service\MerchantLogService;
use think\Controller;
use think\Db;
use think\Request;

class GoodsCardc extends Base
{
   


    public function add()
    {
        if (!$this->request->isPost()) {
            $this->setTitle('添加虚拟卡');
            // 商品列表
            $goodsList = GoodsModel::where(['user_id' => $this->user->id,'is_proxy'=>0])->order('sort desc,id desc')->select();
            $this->assign('goodsList', $goodsList);
            return $this->fetch();
        }
        $goods_id = input('goods_id/d', 0);
        $goods = GoodsModel::get(['id' => $goods_id, 'user_id' => $this->user->id]);
        if (!$goods) {
            $this->error('不存在该商品！');
        }
        $import_type = 2;
       // $split_type = input('split_type/s', ' ');
       // $content = input('content/s', '');
        $check_card = input('check_card/d', 0);
        if ($import_type == 2 && isset($_FILES['file']) && $_FILES['file']['size'] <= 102400) {
            $content = iconv("gb2312", "utf-8//IGNORE", file_get_contents($_FILES['file']['tmp_name']));
        }

        $arr = explode(PHP_EOL, $content);
        $count = count($arr);
        //去除数组两端的空白字符
        //$arr = array_map(function ($v) {
            //return trim(str_replace(chr(194) . chr(160),' ', $v));
        //}, $arr);

        //检查输入是否重卡
        if ($check_card == 1) {
            $arr = array_values(array_unique($arr));
        }
       
	   /* if ($split_type == '0') { //自动识别
            if (strpos($arr[0], " ") !== false) {
                $split_type = " ";
            } elseif (strpos($arr[0], ",") !== false) {
                $split_type = ",";
            } elseif (strpos($arr[0], "|") !== false) {
                $split_type = "|";
            } elseif (strpos($arr[0], "----") !== false) {
                $split_type = "----";
            } else {
                $split_type = "";
            }
        }*/
        $cards = [];
        foreach ($arr as $v) {
           /* if (isset($card[0])) {
                $card[0] = trim(html_entity_decode($card[0]), chr(0xc2) . chr(0xa0));
            } else {
                continue;
            }
            if ($card[0] === '') {
                continue;
            }
            if (strlen($card[0]) > 255) {
                continue;
            }*/
            // if(validateURL($card[0])) {//禁止url
            //     $this->error('虚拟卡内容不能包含链接');
            // }
            $number ='';
            if ($v) {
               $v = trim(html_entity_decode($v), chr(0xc2) . chr(0xa0));
            } else {
                continue;
            }
            $secret = $v;
            // 检查重复
            if ($check_card == 1) {
                $isExist = CardModel::get(['user_id' => $this->user->id, 'goods_id' => $goods_id, 'secret'=>$secret]);
                if ($isExist) {
                    continue;
                }
            }
            $cards[] = [
                'user_id' => $this->user->id,
                'goods_id' => $goods_id,
                'number' => '',
                'secret' => str_replace("<","-",$secret),
                'status' => 1, // 未使用
                'create_at' => $_SERVER['REQUEST_TIME'],
            ];
        }
        if (empty($cards)) {
            $this->error('虚拟卡内容格式不正确, 或卡密已存在');
        }
        $CardModel = new CardModel;
        $res = $CardModel->saveAll($cards);
        $success = count($res);
        if ($res !== false) {
            MerchantLogService::write('成功添加卡密', '成功添加' . $success . '张卡密');
            $this->success("共{$count}张卡密，成功添加{$success}张卡密！", 'GoodsCard/index');
        } else {
            $this->error('添加失败！');
        }
    }

    
}
