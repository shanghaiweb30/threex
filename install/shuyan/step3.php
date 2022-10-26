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
</head>
<body>
<div class="wrap">
  <?php require './shuyan/header.php';?>
  <section class="section">
    <div class="blank30"></div>
    <div class="go go3"></div>
    <div class="blank30"></div>
    <form id="J_install_form" action="index.php?step=4" method="post">
      <input type="hidden" name="force" value="0" />
      <div class="server">
        <table width="100%" id="table" border="0" cellspacing="1" cellpadding="4">
          <tr>
            <td class="td1" colspan="2">数据库信息</td>
          </tr>
      <tr>
            <td class="tar">数据库地址</td>
            <td><input type="text" name="dbhost" id="dbhost" value="localhost" class="input"><div id="J_install_tip_dbhost"><span class="gray">一般为localhost</span></div></td>
          </tr>
      <tr>
            <td class="tar">数据库端口</td>
            <td><input type="text" name="dbport" id="dbport" value="3306" class="input"><div id="J_install_tip_dbport"><span class="gray">一般为3306</span></div></td>
          </tr>
          <tr>
            <td class="tar">数据库用户名</td>
            <td><input type="text" name="dbuser" id="dbuser" value="root" class="input"><div id="J_install_tip_dbuser"></div></td>
          </tr>
          <tr>
            <td class="tar">数据库密码</td>
            <td><input type="password" name="dbpw" id="dbpw" value="" class="input" autoComplete="off" onBlur="TestDbPwd(0)"><div id="J_install_tip_dbpw"></div></td>
          </tr>
          <tr>
            <td class="tar">数据库名</td>
            <td><input type="text" name="dbname" id="dbname" value="" class="input" onBlur="TestDbPwd(0)"><div id="J_install_tip_dbname"></div></td>
          </tr>
        </table>
       
     <table width="100%" id="table" border="0" cellspacing="1" cellpadding="4">
          <tr>
            <td class="td1" colspan="2">管理员信息</td>
          </tr>
          <tr>
            <td class="tar">管理员帐号</td>
            <td><input type="text" name="manager" id="manager" value="admin" class="input"><div id="J_install_tip_manager"><span class="gray">默认帐号为 amdin  不可修改哦</span></div></td>
          </tr>
          <tr>
            <td class="tar">管理员密码</td>
            <td><input name="manager_pwd" type="text" class="input" id="manager_pwd" readonly="readonly" value="admin888" autoComplete="off">
            <div id="J_install_tip_manager_pwd"><span class="gray">默认密码为 amdin888  不可修改哦</span></div></td>
          </tr>
          <tr>
            <td class="tar">请确认密码</td>
            <td><input name="manager_ckpwd" type="text" class="input" id="manager_ckpwd" readonly="readonly" value="admin888" autoComplete="off">
            <div id="J_install_tip_manager_ckpwd"><span class="gray">默认密码为 amdin888  不可修改哦</span></div></td>
          </tr>
          
        </table>
        <div id="J_response_tips" style="display:none;"></div>
      </div>
      <div class="blank20"></div>
      <div class="bottom tac">
        <center>
        <a href="./index.php?step=2" class="btn_b">上一步</a>
        <button type="button" onClick="checkForm();" class="btn btn_submit J_install_btn">创建数据</button>
        </center>
      </div>
      <div class="blank20"></div>
    </form>
  </section>
  <div  style="width:0;height:0;overflow:hidden;"> <img src="./images/pop_loading.gif"> </div>
  <script src="./js/jquery.js?v=9.0"></script> 
  <script src="./js/validate.js?v=9.0"></script> 
  <script src="./js/ajaxForm.js?v=9.0"></script> 
  <script src="./../public/plugins/layer-v3.1.0/layer.js?v=9.0"></script> 
  <script>
   
  function TestDbPwd(connect_db)
  {
      var dbHost = $('#dbhost').val();
      var dbUser = $('#dbuser').val();
      var dbPwd = $('#dbpw').val();
      var dbName = $('#dbname').val();
      var dbport = $('#dbport').val();
      var demo  =  $('#demo').val();
      data={'dbHost':dbHost,'dbUser':dbUser,'dbPwd':dbPwd,'dbName':dbName,'dbport':dbport,'demo':demo};
      var url =  "<?php echo $_SERVER['PHP_SELF']; ?>?step=3&testdbpwd=1";
      $.ajax({
          type: "POST",
          url: url,
          data: data,
          dataType:'JSON',
          beforeSend:function(){         
          },
          success: function(res){     
              if(res.errcode == 1)
              {
                  if(connect_db == 1)
                  {
                    $("#J_install_form").submit(); // ajax 验证通过后再提交表单
                  }   
                  $('#J_install_tip_dbpw').html(res.dbpwmsg);
                  $('#J_install_tip_dbname').html(res.dbnamemsg);
              }
              else if(res.errcode == -1)
              {           
                  $('#J_install_tip_dbpw').html(res.dbpwmsg);
              }
              else if(res.errcode == -2)
              {           
                  $('#J_install_tip_dbname').html(res.dbnamemsg);
              }
              else
              {
                  $('#J_install_tip_dbpw').html(res.dbpwmsg);
              }
          },
          complete:function(){
          },
          error:function(){
              $('#J_install_tip_dbpw').html('<span for="dbname" generated="true" class="tips_error" style="">数据库连接失败，请重新设定</span>');    
          }
      });
  }
  

  function checkForm()
  {
      manager = $.trim($('#manager').val());        //用户名表单
      manager_pwd = $.trim($('#manager_pwd').val());        //密码表单
      manager_ckpwd = $.trim($('#manager_ckpwd').val());    //密码提示区
       
      if(manager.length == 0 )
      {
        layer.alert('管理员账号不能为空', {icon: 5});
        return false;
      }
      if(manager_pwd.length < 5 )
      {
        layer.alert('管理员密码必须5位数以上', {icon: 5});
        return false;
      } 
      if(manager_ckpwd !=  manager_pwd)
      {
        layer.alert('两次密码不一致', {icon: 5});
        return false;
      }       
      TestDbPwd(1);   
  }
</script> 
</div>
<?php require './shuyan/footer.php';?>
</body>
</html>