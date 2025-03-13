<?php // 매시
set_time_limit(60);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgCommon = new Pg('COMMON');

$PgCommon->query('SELECT type_1, type_2, type_3 FROM enter_event');
$PgCommon->fetch();
$row = $PgCommon->row;

$PgGame = new Pg('DEFAULT');
$PgGame->query('UPDATE enter_event SET type_1 = $1, type_2 = $2, type_3 = $3', [$row['type_1'], $row['type_2'], $row['type_3']]);