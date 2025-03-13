<?php // 매시15분과45분
set_time_limit(60);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

$PgGame->query("SELECT troo_pk, src_lord_pk FROM troop WHERE status = $1 AND withdrawal_dt < now() + interval '1 day' AND withdrawal_notify = $2", ['C', 'N']);

$cnt = 0;
while ($PgGame->fetch()) {
	$r =& $PgGame->row;
	// 보고서 발송! TODO 왜 비어있냐?
	$cnt++;
}

$PgGame->query("UPDATE troop SET withdrawal_notify = $3 WHERE status = $1 AND withdrawal_dt < now() + interval '1 day' AND withdrawal_notify = $2", ['C', 'N', 'Y']);

echo $cnt;

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['troop_withdrawal_notify']);