<?php
set_time_limit(0);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');
$Power = new Power($Session, $PgGame);

$power_point = 0;

$PgGame->query('SELECT lord_pk FROM lord WHERE lord_name = $1', [$_GET['lord_name']]);
$lord_pk = $PgGame->fetchOne();

// 건물 영향력
$PgGame->query('SELECT posi_pk FROM position WHERE lord_pk = $1 AND type = $2', [$lord_pk, 'T']);
$PgGame->fetchAll();
$rows = $PgGame->rows;
foreach($rows AS $k => $v) {
	$power_point += $Power->getBuildingPower($v['posi_pk']);
}

// 군주태학기술 영향력
$power_point += $Power->getLordTechniquePower($lord_pk);

// 영웅 영향력
$power_point += $Power->getHeroPower($lord_pk);

//영지 영향력
$power_point += $Power->getTerritoryPower($lord_pk);

// 자원지 영향력
$power_point += $Power->getVallyPower($lord_pk);

// 퀘스트 영향력
$power_point += $Power->getQuestPower($lord_pk);

$PgGame->query('SELECT power FROM lord WHERE lord_name = $1', [$_GET['lord_name']]);
$point = $PgGame->fetchOne();
echo '현재:'.$point .'<br/>';
echo '수정:'.($power_point - 500);
$point = $power_point - 500;

$PgGame->query('UPDATE lord SET power = $2 WHERE lord_name = $1', [$_GET['lord_name'], $point]);