<?php
set_time_limit(200);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');

$Territory = new Territory($Session, $PgGame);
$Power = new Power($Session, $PgGame);
$Troop = new Troop($Session, $PgGame);
$Log = new Log($Session, $PgGame);

//$time = $argv[1];

$time = 240;
$power = 100;

/*
if ($time == 48) {
	$power = 0;
} else if ($time == 96) {
	$power = 10;
} else if ($time == 144) {
	$power = 30;
} else if ($time == 192) {
	$power = 500;
} else if ($time == 240) {
	$power = 1000;
}
*/

$PgGame->query("SELECT lord_pk, main_posi_pk FROM lord WHERE last_logout_dt + interval '$time hours' < now() 
AND power <= $1 AND lord_pk NOT IN (SELECT lord_pk FROM qbig_pack WHERE lord_pk = lord.lord_pk GROUP BY lord_pk)
AND level <= $2 AND is_logon = 'N' AND main_posi_pk is not null", [$power, 1]);
$PgGame->fetchAll();
$row = $PgGame->rows;

foreach($row AS $k => $v) {
	// 군주태학 영향력
	//$power = $Power->getLordTechniquePower($v['lord_pk']);
	$power = 0; // 영지 생성시 재계산함.
	// 부대관련 데이터 삭제
	$Troop->removeAllTroop($v['lord_pk']);

	$PgGame->query('SELECT title FROM territory WHERE posi_pk = $1', [$v['main_posi_pk']]);
	$_last_territory_name = $PgGame->fetchOne() . ' (' . $v['main_posi_pk'] . ')';
	$PgGame->query('SELECT roamerlord('. $v['lord_pk'] .', \''. $v['main_posi_pk'] . '\', '.$power.', \''.$_last_territory_name .'\')');
	$Log->setTerritory($v['lord_pk'], $v['main_posi_pk'], 'roamer_lord');
}

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['roamer_lord']);