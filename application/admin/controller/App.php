<?php

namespace app\admin\controller;

use controller\BasicAdmin;
use think\Db;

class App extends BasicAdmin
{

    public function index()
    {
        if (!$this->request->isPost()) {
            return $this->fetch();
        }


    }
}
