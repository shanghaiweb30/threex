<?php
use think\Db;
$url = Db::table('system_config')->where('name', 'yc88_url')->value('value');
if($url == ''){
	$url = 'https://www.yc88.net';
}
return array (
  'version' => '4.7.1',
  'api_url' => 'https://www.yc88.net',
  'system_dir' => 'upgrade',
  'file_dir' => 'file',
  'RELEASE' => '20210116',
  'PROHIBIT' => '&#x68;&#x74;&#x74;&#x70;&#x3A;&#x2F;&#x2F;&#x77;&#x77;&#x77;&#x2E;&#x61;&#x38;&#x74;&#x67;&#x2E;&#x63;&#x6F;&#x6D;',
);

