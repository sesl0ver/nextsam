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

$sql = [];
$type_array = ['A', 'E', 'F', 'M', 'L', 'G', 'R'];

// 점령 포인트 초기화
foreach ($type_array as $type) {
    $sql[] = "UPDATE position SET update_point_dt = now(), current_point = 0 WHERE position.type = '$type' AND position.level BETWEEN 1 AND 2";
    $sql[] = "UPDATE position SET update_point_dt = now(), current_point = 0 WHERE position.type = '$type' AND position.level BETWEEN 3 AND 4";
    $sql[] = "UPDATE position SET update_point_dt = now(), current_point = 0 WHERE position.type = '$type' AND position.level BETWEEN 5 AND 6";
    $sql[] = "UPDATE position SET update_point_dt = now(), current_point = 0 WHERE position.type = '$type' AND position.level BETWEEN 7 AND 8";
    $sql[] = "UPDATE position SET update_point_dt = now(), current_point = 0 WHERE position.type = '$type' AND position.level BETWEEN 9 AND 10";
}

foreach ($sql AS $cmd) {
    $PgGame->query($cmd);
}

// 이벤트 종료 후 필요한 액션

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['occupation_point_clear']);