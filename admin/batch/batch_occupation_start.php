<?php
set_time_limit(600);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

if (CONF_OCCUPATION_POINT_ENABLE !== true) {
    exit();
}

$PgGame = new Pg('DEFAULT');
$Event = new Event($PgGame);

if ($Event->getTrigger('occupation_point')) {
    exit();
}

$Redis = new RedisCache();

// 이벤트 시작 전 랭킹 초기화
$Redis->del('ranking:lord:occupation_point');
$Redis->del('ranking:alliance:occupation_point');
$PgGame->query('DELETE FROM occupation_point WHERE lord_pk > 0');
$PgGame->query('DELETE FROM occupation_personal_reward WHERE lord_pk > 0');

$Event->updateTrigger('occupation_point', 'true');

// 이벤트 시작 전 포인트 갱신
require_once "./batch_occupation_point_refresh.php";

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['occupation_start']);