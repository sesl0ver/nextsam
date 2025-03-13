<?php
set_time_limit(120);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

$PgGame->query('delete from alliance_history where regist_dt < now() - interval \'4 day\'');
$PgGame->query('delete from alliance_war_history where regist_dt < now() - interval \'4 day\'');

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['alliance_history_reset']);