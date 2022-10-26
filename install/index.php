<?php
if(version_compare(PHP_VERSION,'7.0.0','<'))  die('本系统要求PHP版本 >= 7.0.0，当前PHP版本为：'.PHP_VERSION . '，请到虚拟主机控制面板里切换PHP版本，或联系空间商协助切换。<a href="http://www.yc88.net/" target="_blank">购买自动发卡平台源码请到</a>');
// +----------------------------------------------------------------------
if(version_compare(PHP_VERSION,'7.0.99','>'))  die('本系统不能高于PHP7.0版，请降低您的PHP版本，当前PHP版本为：'.PHP_VERSION . '，请到虚拟主机控制面板里切换PHP版本，或联系空间商协助切换。<a href="http://www.yc88.net/" target="_blank">购买自动发卡平台源码请到</a>');
error_reporting(E_ERROR | E_WARNING | E_PARSE);//报告运行时错误

include 'auto.php';
if(IS_SAE)
header("Location: index_sae.php");

// php最低版本要求
$mini_php = '7.0.0';

if (file_exists('./install.lock')) {
    echo '
        <html>
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        </head>
        <body>
            你已经安装过该系统，如果想重新安装，请先删除站点install目录下的 install.lock 文件，然后再安装。
        </body>
        </html>';
    exit;
}
@set_time_limit(1000);
if (phpversion() <= $mini_php)
 //   set_magic_quotes_runtime(0);
if ($mini_php > phpversion()){
    header("Content-type:text/html;charset=utf-8");
    exit('您的php版本('.phpversion().')过低，不能安装本软件，请升级到'.$mini_php.'再安装，谢谢！');
}

define("EYOUCMS_VERSION", '20210101');
date_default_timezone_set('PRC');
error_reporting(E_ALL & ~E_NOTICE);
header('Content-Type: text/html; charset=UTF-8');
define('SITEDIR', _dir_path(substr(dirname(__FILE__), 0, -8)));
//define('SITEDIR2', substr(SITEDIR,0,-7));
//echo SITEDIR2;
//exit;
//数据库
$sqlFile = 'shuyan.sql';
$configFile = 'database.php';
if (!file_exists(SITEDIR . 'install/' . $sqlFile) || !file_exists(SITEDIR . 'install/' . $configFile)) {
    echo "缺少必要的安装文件（{$sqlFile} 或 {$configFile}）!";
    exit;
}
$Title = "API支付代理版自动发卡平台系统安装向导";
$Powered = "Powered by 爱发自动发卡平台";
$steps = array(
    '1' => '安装许可协议',
    '2' => '运行环境检测',
    '3' => '安装参数设置',
    '4' => '安装详细过程',
    '5' => '安装完成',
);
$step = isset($_GET['step']) ? $_GET['step'] : 1;

//地址
$scriptName = !empty($_SERVER["REQUEST_URI"]) ? $scriptName = $_SERVER["REQUEST_URI"] : $scriptName = $_SERVER["PHP_SELF"];
$rootpath = @preg_replace("/\/(I|i)nstall\/index\.php(.*)$/", "", $scriptName);
$domain = empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
if ((int) $_SERVER['SERVER_PORT'] != 80) {
    $domain .= ":" . $_SERVER['SERVER_PORT'];
}
$domain = $domain . $rootpath;

switch ($step) {

    case '1':
        include_once ("./shuyan/step1.php");
        exit();

    case '2':

        if (phpversion() < 7) {
            die('本系统需要PHP7.0.0以上 + MYSQL >= 5.5环境，当前PHP版本为：' . phpversion());
        }

        $err = 0;

        $phpv = @ phpversion();
        if ($mini_php <= phpversion()){
            $phpvStr = '<img src="images/ok.png">';
        }else{
            $phpvStr = '<img src="images/del.png"> 当前版本('.phpversion().')不支持';
            $err++;
        }
        $os = PHP_OS;
        //$os = php_uname();
        $tmp = function_exists('gd_info') ? gd_info() : array();
        $server = $_SERVER["SERVER_SOFTWARE"];
        $host = (empty($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_HOST"] : $_SERVER["SERVER_ADDR"]);
        $name = $_SERVER["SERVER_NAME"];
        $max_execution_time = ini_get('max_execution_time');
        $allow_reference = (ini_get('allow_call_time_pass_reference') ? '<img src="images/ok.png">' : '<img src="images/del.png">');
        $allow_url_fopen = (ini_get('allow_url_fopen') ? '<img src="images/ok.png">' : '<img src="images/del.png">');
        $safe_mode = (ini_get('safe_mode') ? '<img src="images/del.png">' : '<img src="images/ok.png">');
        
        if (empty($tmp['GD Version'])) {
            $gd = '<img src="images/del.png">';
            $err++;
        } else {
            $gd = '<img src="images/ok.png">';
        }
        if (function_exists('mysqli_connect')) {
            $mysql = '<img src="images/ok.png">';
        } else {
            $mysql = '<img src="images/del.png"> 请安装mysqli扩展';
            $err++;
        }
        if (ini_get('file_uploads')) {
            $uploadSize = '<img src="images/ok.png">';
        } else {
            $uploadSize = '<img src="images/del.png"> 禁止上传';
        }
        if (class_exists('pdo')) {
            $pdo = '<img src="images/ok.png">';
        } else {
            $pdo = '<img src="images/del.png">';
            $err++;
        }
        if (extension_loaded('pdo_mysql')) {
            $pdo_mysql = '<img src="images/ok.png">';
        } else {
            $pdo_mysql = '<img src="images/del.png">';
            $err++;
        }
        if (function_exists('mb_strlen')) {
            $mbstring = '<img src="images/ok.png">';
        } else {
            $mbstring = '<img src="images/del.png">';
            $err++;
        }

        if(function_exists('curl_init')){
            $curl = '<img src="images/ok.png">';
        }else{
            $curl = '<img src="images/del.png">';
            $err++;
        }
        if(function_exists('file_put_contents')){
            $file_put_contents = '<img src="images/ok.png">';
        }else{
            $file_put_contents = '<img src="images/del.png">';
            $err++;
        }
        
        $folder = array(
            'install',
        );
        include_once ("./shuyan/step2.php");
        exit();

    case '3':
        $dbName = trim($_POST['dbName']);
        $_POST['dbport'] = $_POST['dbport'] ? $_POST['dbport'] : '3306';
        if ($_GET['testdbpwd']) {
            $dbHost = $_POST['dbHost'];
            $conn = @mysqli_connect($dbHost, $_POST['dbUser'], $_POST['dbPwd'],NULL,$_POST['dbport']);          
            if (mysqli_connect_errno($conn)){
                die(json_encode(array(
                    'errcode'   => 0,
                    'dbpwmsg'    => "<span for='dbname' generated='true' class='tips_error'>数据库连接失败，请重新设定</span>",
                )));
            } else {
                

                if (empty($dbName)) {
                    die(json_encode(array(
                        'errcode'   => -2,
                        'dbpwmsg'    => "<span class='green'>信息正确</span>",
                        'dbnamemsg'    => "<span class='red'>数据库不能为空，请设定</span>",
                    )));

                } else {
                    /*检测数据库是否存在*/
                    $result = mysqli_query($conn,"select count(table_name) as c from information_schema.`TABLES` where table_schema='$dbName'");
                    $result = $result->fetch_array();
                    if($result['c'] > 0) { // 存在
                        $dbnamemsg = "<span class='red'>数据库已经存在，系统将覆盖数据库</span>";
                    } else { // 不存在
                        $dbnamemsg = "<span class='green'>数据库不存在，系统将自动创建</span>";
                    }
                    /*--end*/
                }
                
                die(json_encode(array(
                    'errcode'   => 1,
                    'dbpwmsg'    => "<span class='green'>信息正确</span>",
                    'dbnamemsg'    => $dbnamemsg,
                )));
            }
        }
        include_once ("./shuyan/step3.php");
        exit();


    case '4':
        if (intval($_GET['install'])) {
            $n = intval($_GET['n']);
            $arr = array();

            $dbHost = trim($_POST['dbhost']);
            $_POST['dbport'] = $_POST['dbport'] ? $_POST['dbport'] : '3306';
            $dbName = trim($_POST['dbname']);
            $dbUser = trim($_POST['dbuser']);
            $dbPwd = trim($_POST['dbpw']);
            $dbPrefix = empty($_POST['dbprefix']) ? 'ey_' : trim($_POST['dbprefix']);

            $username = trim($_POST['manager']);
            $password = trim($_POST['manager_pwd']);
            $email    = trim($_POST['manager_email']);
            
            if (!function_exists('mysqli_connect')) {
                $arr['msg'] = "请安装 mysqli 扩展!";
                echo json_encode($arr);
                exit;
            }           
            
            $conn = @mysqli_connect($dbHost, $dbUser, $dbPwd,NULL,$_POST['dbport']);
            if (mysqli_connect_errno($conn)){
                $arr['msg'] = "连接数据库失败!".mysqli_connect_error($conn);           
                echo json_encode($arr);
                exit;
            }
            mysqli_set_charset($conn, "utf8"); //,character_set_client=binary,sql_mode='';
            $version = mysqli_get_server_info($conn);
            if ($version < 5.1) {
                $arr['msg'] = '数据库版本太低! 必须5.1以上';
                echo json_encode($arr);
                exit;
            }
            
            if (!@mysqli_select_db($conn,$dbName)) {
                //创建数据时同时设置编码
                if (!@mysqli_query($conn,"CREATE DATABASE IF NOT EXISTS `" . $dbName . "` DEFAULT CHARACTER SET utf8;")) {
                    $arr['msg'] = '数据库 ' . $dbName . ' 不存在，也没权限创建新的数据库，建议联系空间商或者服务器负责人！';
                    echo json_encode($arr);
                    exit;
                }
                if ($n==-1) {
                    $arr['n'] = 0;
                    $arr['msg'] = "成功创建数据库:{$dbName}<br>";
                    echo json_encode($arr);
                    exit;
                }
                mysqli_select_db($conn , $dbName);
            }

            //读取数据文件
            $sqldata = file_get_contents(SITEDIR . 'install/' . $sqlFile);
            $sqlFormat = sql_split($sqldata, $dbPrefix);
            //创建写入sql数据库文件到库中 结束

            /**
             * 执行SQL语句
             */
            $counts = count($sqlFormat);

            for ($i = $n; $i < $counts; $i++) {
                $sql = trim($sqlFormat[$i]);

                if (strstr($sql, 'CREATE TABLE')) {
                    preg_match('/CREATE TABLE `([^ ]*)`/', $sql, $matches);
                    mysqli_query($conn,"DROP TABLE IF EXISTS `$matches[1]");
                    $ret = mysqli_query($conn,$sql);
                    if ($ret) {
                        $message = '<li><span class="correct_span">&radic;</span>创建数据表' . $matches[1] . '，完成!<span style="float: right;">'.date('Y-m-d H:i:s').'</span></li> ';
                    } else {
                        $message = '<li><span class="correct_span error_span">&radic;</span>创建数据表' . $matches[1] . '，失败!<span style="float: right;">'.date('Y-m-d H:i:s').'</span></li>';
                    }
                    $i++;
                    $arr = array('n' => $i, 'msg' => $message);
                    echo json_encode($arr);
                    exit;
                } else {
                    if(trim($sql) == '')
                       continue;
                    $ret = mysqli_query($conn,$sql);
                    $message = '';
                    $arr = array('n' => $i, 'msg' => $message);
                    //echo json_encode($arr); exit;
                }
            }


            delFile('../data/runtime');
            /*--end*/

            if ($i == 999999)
                exit;
                                    
            //读取配置文件，并替换真实配置数据1
            $strConfig = file_get_contents(SITEDIR . 'install/' . $configFile);
            $strConfig = str_replace('#DB_HOST#', $dbHost, $strConfig);
            $strConfig = str_replace('#DB_NAME#', $dbName, $strConfig);
            $strConfig = str_replace('#DB_USER#', $dbUser, $strConfig);
            $strConfig = str_replace('#DB_PWD#', $dbPwd, $strConfig);
            $strConfig = str_replace('#DB_PORT#', $_POST['dbport'], $strConfig);
            $strConfig = str_replace('#DB_PREFIX#', $dbPrefix, $strConfig);
            $strConfig = str_replace('#DB_CHARSET#', 'utf8', $strConfig);
            $strConfig = str_replace('#DB_DEBUG#', false, $strConfig);
            @chmod(SITEDIR . './application/database.php',0777); //数据库配置文件的地址
            @file_put_contents(SITEDIR . './application/database.php', $strConfig); //数据库配置文件的地址
            
            //读取配置文件，并替换缓存前缀
            $strConfig = file_get_contents(SITEDIR . './application/database.php');
            $uniqid_str = uniqid();
            $uniqid_str = md5($uniqid_str);
            $strConfig = str_replace('eyoucms_cache_prefix', $uniqid_str, $strConfig);           
            @chmod(SITEDIR . './application/database.php',0777); //配置文件的地址
            @file_put_contents(SITEDIR . './application/database.php', $strConfig); //配置文件的地址            
            
            //更新网站基本配置信息
            $web_basehost = 'http://'.trim($_SERVER['HTTP_HOST'], '/');
            $sql = "UPDATE `{$dbPrefix}config` SET `value` = '$web_basehost' WHERE name = 'web_basehost' AND inc_type = 'web'";
            mysqli_query($conn, $sql);

            //插入管理员表字段tp_admin表
            $time = time();
            $ip = get_client_ip();
            $ip = empty($ip) ? "0.0.0.0" : $ip;
            $password = md5('!*&^eyoucms<>|?'.trim($_POST['manager_pwd']));
            mysqli_query($conn, "DELETE FROM `{$dbPrefix}admin` WHERE user_name = 'admin'");
            mysqli_query($conn, " INSERT  INTO `{$dbPrefix}admin` (`admin_id`,`user_name`,`true_name`,`email`,`password`,`add_time`,`last_login`,`last_ip`,`login_cnt`,`status`) VALUES ('1','$username','$username','$email','$password','$time','0','$ip','1','1')");
                    
            $message = '成功添加管理员<br />成功写入配置文件<br>安装完成．';
            $arr = array('n' => 999999, 'msg' => $message);
            echo json_encode($arr);
            exit;
        }

        include_once ("./shuyan/step4.php");
        exit();

    case '5':
        $ip = get_server_ip();
        $host = $_SERVER['HTTP_HOST'];
        //$curent_version = file_get_contents(SITEDIR .'/data');
        $create_date = date("Ymdhis");
        $time = time();
        $phpv = urlencode(phpversion());
        $web_server    = urlencode($_SERVER['SERVER_SOFTWARE']);
        $mt_rand_str = $create_date.sp_random_string(6);
        $str_constant = "<?php".PHP_EOL."define('INSTALL_DATE',".$time.");".PHP_EOL."define('SERIALNUMBER','".$mt_rand_str."');";
        //@file_put_contents(SITEDIR . '/app/common/conf/db1.php', $str_constant);
        include_once ("./shuyan/step5.php");
        @touch('./install.lock');
        exit();
}

function testwrite($d) {
    $tfile = "_test.txt";
    $fp = @fopen($d . "/" . $tfile, "w");
    if (!$fp) {
        return false;
    }
    fclose($fp);
    $rs = @unlink($d . "/" . $tfile);
    if ($rs) {
        return true;
    }
    return false;
}

function sql_execute($sql, $tablepre) {
    $sqls = sql_split($sql, $tablepre);
    if (is_array($sqls)) {
        foreach ($sqls as $sql) {
            if (trim($sql) != '') {
                mysqli_query($sql);
            }
        }
    } else {
        mysqli_query($sqls);
    }
    return true;
}

function sql_split($sql, $tablepre) {

    if ($tablepre != "ey_")
        $sql = str_replace("ey_", $tablepre, $sql);
          
    $sql = preg_replace("/TYPE=(InnoDB|MyISAM|MEMORY)( DEFAULT CHARSET=[^; ]+)?/", "ENGINE=\\1 DEFAULT CHARSET=utf8", $sql);
    
    $sql = str_replace("\r", "\n", $sql);
    $ret = array();
    $num = 0;
    $queriesarray = explode(";\n", trim($sql));
    unset($sql);
    foreach ($queriesarray as $query) {
        $ret[$num] = '';
        $queries = explode("\n", trim($query));
        $queries = array_filter($queries);
        foreach ($queries as $query) {
            $str1 = substr($query, 0, 1);
            if ($str1 != '#' && $str1 != '-')
                $ret[$num] .= $query;
        }
        $num++;
    }
    return $ret;
}

function _dir_path($path) {
    $path = str_replace('\\', '/', $path);
    if (substr($path, -1) != '/')
        $path = $path . '/';
    return $path;
}

// 获取客户端IP地址
function get_client_ip() {
    static $ip = NULL;
    if ($ip !== NULL)
        return $ip;
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos)
            unset($arr[$pos]);
        $ip = trim($arr[0]);
    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
    return $ip;
}

// 服务器端IP
function get_server_ip(){   
    return gethostbyname($_SERVER["SERVER_NAME"]);   
}  

function dir_create($path, $mode = 0777) {
    if (is_dir($path))
        return TRUE;
    $ftp_enable = 0;
    $path = dir_path($path);
    $temp = explode('/', $path);
    $cur_dir = '';
    $max = count($temp) - 1;
    for ($i = 0; $i < $max; $i++) {
        $cur_dir .= $temp[$i] . '/';
        if (@is_dir($cur_dir))
            continue;
        @mkdir($cur_dir, 0777, true);
        @chmod($cur_dir, 0777);
    }
    return is_dir($path);
}

function dir_path($path) {
    $path = str_replace('\\', '/', $path);
    if (substr($path, -1) != '/')
        $path = $path . '/';
    return $path;
}

function sp_password($pw, $pre){
    $decor = md5($pre);
    $mi = md5($pw);
    return substr($decor,0,12).$mi.substr($decor,-4,4);
}

function sp_random_string($len = 8) {
    $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9"
    );
    $charsLen = count($chars) - 1;
    shuffle($chars);    // 将数组打乱
    $output = "";
    for ($i = 0; $i < $len; $i++) {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}
// 递归删除文件夹
function delFile($dir,$file_type='') {
    if(is_dir($dir)){
        $files = scandir($dir);
        //打开目录 //列出目录中的所有文件并去掉 . 和 ..
        foreach($files as $filename){
            if($filename!='.' && $filename!='..'){
                if(!is_dir($dir.'/'.$filename)){
                    if(empty($file_type)){
                        unlink($dir.'/'.$filename);
                    }else{
                        if(is_array($file_type)){
                            //正则匹配指定文件
                            if(preg_match($file_type[0],$filename)){
                                unlink($dir.'/'.$filename);
                            }
                        }else{
                            //指定包含某些字符串的文件
                            if(false!=stristr($filename,$file_type)){
                                unlink($dir.'/'.$filename);
                            }
                        }
                    }
                }else{
                    delFile($dir.'/'.$filename);
                    rmdir($dir.'/'.$filename);
                }
            }
        }
    }else{
        if(file_exists($dir)) unlink($dir);
    }
}
?>