<?php
set_time_limit(120);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

// 무역소 테이블 재 생성
$PgGame->query('DELETE FROM trade_price_list');
$PgGame->query('INSERT INTO trade_price_list SELECT \'O\', reso_type, unit_price, SUM(deal_amount) FROM trade_offer GROUP BY reso_type, unit_price');
$PgGame->query('INSERT INTO trade_price_list SELECT \'B\', reso_type, unit_price, SUM(deal_amount) FROM trade_bid GROUP BY reso_type, unit_price');

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['trade_list']);