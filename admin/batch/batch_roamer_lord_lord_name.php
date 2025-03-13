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

$lord_name = $argv[1];
if (!$lord_name)
	exit;

$PgGame->query("SELECT lord_pk, main_posi_pk FROM lord WHERE lord_name like '$lord_name%'");
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