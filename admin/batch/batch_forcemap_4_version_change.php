<?php
// TODO 세력도 삭제로 사용안함.
/*require_once('../../inc/config.php');
require_once_classes(Array('CSession', 'CPgsql'));

if (!$argv[1] || ($argv[1] != 'a' && $argv[1] != 'b'))
{
	echo 'specified version'. "\n";
	exit;
}

$Session = new CSession(false);
$Db = new CPgsql('DEF');

$new_ver = $argv[1];

$Db->query('update m_preference set forcemap_ver = $1, forcemap_upt_dt = now()', Array($new_ver));

$fd = '{"qbw_cmd_return":{"code":"OK","mesg":null,"add_data":{"update_dt":'. mktime() .',"target_path":"'. $new_ver. '"}}}';

$fn = CONF_FORCEMAP. 'ver.js';
$fp = fopen($fn, 'w');
if ($fp)
{
	fwrite($fp, $fd);
	fclose($fp);
}*/