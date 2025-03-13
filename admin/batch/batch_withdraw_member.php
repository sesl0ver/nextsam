<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgCommon = new Pg('COMMON');

$PgCommon->query("UPDATE qb_member SET id = MD5(id)||'_'||date_part('epoch', now())::integer, withdraw_id = SUBSTR(id, 1, 4)||LPAD('', LENGTH(id)-4, '*') WHERE withdraw = 'Y' AND withdraw_dt < now() + '7 day ago' AND withdraw_id IS NULL");

// TODO 밴드는 더이상 서비스 안해서 주석 처리
// $PgCommon->query("UPDATE band_member SET user_key = MD5(user_key)||'_'||now()::abstime::integer, withdraw_id = SUBSTR(user_key, 1, 4)||LPAD('', LENGTH(user_key)-4, '*') WHERE withdraw = 'Y' AND withdraw_dt < now() + '7 day ago' AND withdraw_id IS NULL");
