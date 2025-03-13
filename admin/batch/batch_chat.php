<?php
// TODO 사용안함.
/*require_once('../../inc/config.php');
require_once_classes(Array('CSession', 'CPgsql'));

$Db = new CPgsql('DEF');

$file = '/tmp/qbe_chat_cache.php';
$save_cnt = 20;

$CHAT_MAX = 0;
include_once('/tmp/qbe_chat_cache.php');

$cnt = 0;

$sql = 'SELECT chat_pk, type, sender, message, regist_dt::abstime::integer FROM chat WHERE chat_pk > $1 ORDER BY chat_pk ASC';
$Db->query($sql, Array($CHAT_MAX));
while($Db->fetch())
{
	$r =& $Db->row;
	$k = $r['chat_pk'];

	$CHAT[$k] = Array($r['type'], $r['sender'], $r['message'], $r['regist_dt']);
	$cnt++;

	if ($k > $CHAT_MAX)
		$CHAT_MAX = $k;
}

if ($cnt < 1)
	exit;

$fp = fopen($file, 'w');

$fd = '<?php'. "\n";
$fd .= '$CHAT_MAX = '. $CHAT_MAX. ";\n";
$fd .= '$CHAT = Array();'. "\n";

$ign_cnt = 0;
$loop_cnt = 0;

if (COUNT($CHAT) > $save_cnt)
	$ign_cnt = COUNT($CHAT) - $save_cnt;

foreach ($CHAT AS $k => $d)
{
	$loop_cnt++;
	if ($ign_cnt > $loop_cnt)
		continue;

	$fd .= "\$CHAT[$k] = Array(";
	$fd .= "'{$d[0]}', '{$d[1]}', '{$d[2]}', {$d[3]}";
	$fd .= ");\n";
}

$fd .= '?>'. "\n";

fwrite($fp, $fd);
fclose($fp);*/
