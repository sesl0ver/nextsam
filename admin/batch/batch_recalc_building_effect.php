<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');

$NsGlobal = NsGlobal::getInstance();
$NsGlobal->requireMasterData(['army', 'building', 'fortification', 'building', 'encounter_range', 'troop', 'hero_encounter_hero_level', 'point_npc_troop', 'hero', 'hero_base']);

$Resource = new Resource($Session, $PgGame);
$GoldPop = new GoldPop($Session, $PgGame);
$FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
$Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);
$Technique = new Technique($Session, $PgGame, $Resource, $GoldPop);

$posi_pk = $_GET['posi_pk'];
$PgGame->query('SELECT lord_pk FROM lord WHERE lord_name = $1', [$_GET['name']]);
$lord_pk = $PgGame->fetchOne();
if (! $lord_pk) {
	echo '군주명이 일치하지 않습니다.';
	exit;
}

$Resource->save($posi_pk, $lord_pk);

$GoldPop->save($posi_pk, $lord_pk);

$Technique->updateTerritoryTechnique($lord_pk, $posi_pk);

global $_M;
// 효과적용 - 내성
$PgGame->query('SELECT m_buil_pk, in_castle_pk, status, level FROM building_in_castle WHERE posi_pk = $1', [$posi_pk]);
$PgGame->fetchAll();
$rows = $PgGame->rows;

foreach($rows As $k => $v) {
	$update_type = $_M['BUIL'][$v['m_buil_pk']]['update_type'];
	if ($update_type != 'NULL') {
		$FigureReCalc->dispatcher($posi_pk, $update_type, ['in_castle_pk' => $v['in_castle_pk'], 'status' => $v['status'], 'level' => $v['level']]);
	}
}

// 효과적용 - 외성
$PgGame->query('SELECT m_buil_pk, out_castle_pk, status, level FROM building_out_castle WHERE posi_pk = $1', [$posi_pk]);
$PgGame->fetchAll();
$rows = $PgGame->rows;
foreach($rows As $k => $v) {
	$update_type = $_M['BUIL'][$v['m_buil_pk']]['update_type'];
	if ($update_type != 'NULL') {
        // TODO in_castle_pk로 되어있어서 out_castle_pk로 바꿈. 나중에 확인 필요.
		$FigureReCalc->dispatcher($posi_pk, $update_type, ['out_castle_pk' => $v['out_castle_pk'], 'status' => $v['status'], 'level' => $v['level']]);
	}
}

echo "OK";