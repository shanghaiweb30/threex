<!doctype html>
<meta name="renderer" content="webkit">
<meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1" >
<html>
<head>
<meta charset="UTF-8" />
<meta http-equiv="Content-Language" content="zh-cn"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport" />
<title><?php echo $Title; ?> - <?php echo $Powered; ?></title>
<link rel="stylesheet" href="./css/install.css?v=9.0" />
<script src="js/jquery.js"></script>
<?php 
$uri = $_SERVER['REQUEST_URI'];
$root = substr($uri, 0,strpos($uri, "install"));
$admin = $root."../index.php/admin/Admin/";
?>
</head>
<body>
<div class="wrap">
  <?php require './shuyan/header.php';?>
  <section class="section">
    <div class="blank10"></div>
    <div class="blank30"></div>
    <div class="go go4"></div>
    <div class="blank10"></div>
    <div class="blank30"></div>

    <div class="">
      <div class="result cc"> 
        <h1>恭喜您，API支付代理版自动发卡平台系统！</h1>
        <h5>基于安全考虑，安装完成后即可将网站根目录下的“install”文件夹删除！</h5>
		<br>
		<h5>后台帐号：admin 密码：admin888  后台地址 http://域名/yc88net</h5>
      </div>
	        <div class="bottom tac"> 
          <center>
	        <a href="../" class="btn_b" style="color: #fff"> 访问网站首页 </a>
	        <a href="../yc88net" class="btn_a btn_submit J_install_btn">访问网站后台</a>	
          </center>
      </div>
      <div class=""> </div>
    </div>
  </section>
</div>
<div class="blank30"></div>

</body>
</html>