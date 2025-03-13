<?php
set_time_limit(60);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

$sql = [];
$type_array = ['A', 'E', 'F', 'M', 'L', 'G', 'R'];


// 타일 레벨 갱신 부터
foreach ($type_array as $type) {
    $PgGame->query("UPDATE position SET last_levelup_dt = now(), last_update_dt = now(), level = level+1 WHERE lord_pk IS NULL AND type = '$type' AND level = 10");
    $PgGame->query("UPDATE position SET last_levelup_dt = now(), last_update_dt = now(), level = level+1 WHERE lord_pk IS NULL AND type = '$type' AND level BETWEEN 7 AND 9");
    $PgGame->query("UPDATE position SET last_levelup_dt = now(), last_update_dt = now(), level = level+1 WHERE lord_pk IS NULL AND type = '$type' AND level BETWEEN 4 AND 6");
    $PgGame->query("UPDATE position SET last_levelup_dt = now(), last_update_dt = now(), level = level+1 WHERE lord_pk IS NULL AND type = '$type' AND level BETWEEN 1 AND 3");
    $PgGame->query("UPDATE position SET last_levelup_dt = now(), last_update_dt = now(), level = 1 WHERE lord_pk IS NULL AND type = '$type' AND level >= 11");
}

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['npc_valley_levelup']);

// 이벤트 시작 전 포인트 갱신
require_once "./batch_occupation_point_refresh.php";