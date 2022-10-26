<?php
use think\Config;
return [
    // 应用初始化
    'app_init'     => [],
    // 应用开始
    'app_begin'    => [],
    // 模块初始化
    'module_init'  => [],
    // 操作开始执行
    'action_begin' => ['hook\\AccessAuth'],
    // 视图内容过滤
    'view_filter'  => ['hook\\FilterView'],
    // 日志写入
    'log_write'    => [],
    // 应用结束
    'app_end'      => [],
];

/*检测开始*/
function get_remote_file($url = '', $timeout = 3)                                
{                                                                                
    if (empty($url))                                                             
        return;                                                                  
                                                                                 
    // 解析协议                                                                  
    $protocol = parse_url($url)['scheme'];                                       
    $options = [                                                                 
        'http' => [                                                              
            'method'  => 'GET',                                                  
            'timeout' => $timeout,                                               
        ],                                                                       
        'https' => [                                                             
            'method'  => 'GET',                                                  
            'timeout' => $timeout,                                               
        ]                                                                        
    ];                                                                           
    // 必须是二维数组                                                            
    $option[$protocol] = $options[$protocol];                                    
    $result  = @file_get_contents($url, false, stream_context_create($option));   
    return $result;                                                              
} 

function getnewversion(){
	
	$version= Config::get('upgrade.version');
	$url=Config::get('upgrade.api_url')."/api";
	   $url=get_remote_file($url);
	   if($url){
	   $urls=json_decode($url,true);
             if($urls['new']['version']==$version){
             }else{
             	echo '<a  href="/admin.html#/admin/shengji/index.html?spm=m-2-143"  title="您有新版本，请及时下载更新，过后将无法下载。" class="topbar-btn topbar-left topbar-info-item text-center" > <span style="color: rgb(255, 0, 0);"><img src="/static/xx.gif" width="37" height="25" />您有新版本 点击下载</span></a> <EMBED src="/static/xiaoxi.mp3" autostart="true" loop="true" width="0" height="0">  ';
             }
	   }else{
	   	 echo '';
	   }

}