<?php // 매시30분
set_time_limit(600);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

$PgGame->query('DELETE FROM my_hero WHERE status IN ($1, $2, $3) AND timedjob_dt < now()', ['C', 'S', 'V']);

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['hero_leave']);
