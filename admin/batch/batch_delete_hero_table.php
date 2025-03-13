<?php
set_time_limit(0);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

$cnt = 1;
$start_dt = time();

while($cnt > 0) {
    $PgGame->query('DELETE FROM hero WHERE hero_pk IN (SELECT hero_pk FROM hero WHERE yn_del = \'Y\' LIMIT 10)');
    $PgGame->query('SELECT COUNT(hero_pk) FROM hero WHERE yn_del = \'Y\'');
	$cnt = $PgGame->fetchOne();
}
$end_dt = time();
$dur = $end_dt - $start_dt;
echo $dur;