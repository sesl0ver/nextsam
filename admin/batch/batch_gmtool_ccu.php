<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

$r = $PgGame->query('INSERT INTO gmtool_ccu (web_channel, ccu) SELECT b.web_channel, COUNT(b.web_channel) FROM lord a, lord_web b  WHERE a.is_logon = $1 AND a.lord_pk = b.lord_pk GROUP BY b.web_channel', ['Y']);
if (! $r) {
	echo 'GmTool_CCU Error!!';
} else {
	echo 'GmTool_CCU Update.';
}

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['gmtool_ccu']);
