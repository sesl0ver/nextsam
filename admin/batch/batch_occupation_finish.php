<?php
set_time_limit(600);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

if (CONF_OCCUPATION_POINT_ENABLE !== true) {
    exit();
}

$PgGame = new Pg('DEFAULT');
$Event = new Event($PgGame);

if (! $Event->getTrigger('occupation_point')) {
    exit();
}

// 이벤트 종료 전 마지막 랭킹 갱신
require_once "./batch_occupation_point.php";

$Event->updateTrigger('occupation_point', 'false');

// 이벤트 종료 후 자원지 포인트 초기화

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['occupation_finish']);