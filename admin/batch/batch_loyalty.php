<?php
set_time_limit(120);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

// Loyalty를 떨어뜨려야 하는 리스트 추출 후 처리
$PgGame->query('SELECT posi_pk FROM territory WHERE loyalty > (100-tax_rate)');
$PgGame->fetchAll();
$rows = $PgGame->rows;

foreach ($rows AS $v) {
    $PgGame->query('SELECT CorrectPopulation(\''.$v['posi_pk'].'\','. '\'U\')');
}

$PgGame->query('UPDATE territory SET loyalty=loyalty-1 WHERE posi_pk IN (SELECT posi_pk FROM territory WHERE loyalty > (100-tax_rate) )');

// Loyalty를 증가해야 하는 리스트 추출 후 처리
$PgGame->query('SELECT posi_pk FROM territory WHERE loyalty < (100-tax_rate)');
$PgGame->fetchAll();
$rows = $PgGame->rows;

foreach ($rows AS $v) {
	$PgGame->query('SELECT CorrectPopulation(\''.$v['posi_pk'].'\','. '\'U\')');
}

$PgGame->query('UPDATE territory SET loyalty=loyalty+1 WHERE posi_pk IN (SELECT posi_pk FROM territory WHERE loyalty < (100-tax_rate) )');

$PgGame->query('UPDATE position_npc SET loyalty=loyalty+1 WHERE loyalty < 100');

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['loyalty']);