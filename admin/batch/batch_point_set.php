<?php
set_time_limit(360);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/constant.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// 이벤트가 꺼져있다면 실행 금지.
if (! CONF_NPC_POINT_ENABLE) {
    exit();
}

$PgGame = new Pg('DEFAULT');

try {
    $PgGame->begin();

    // TODO 상황에 맞게 주석 해제 후 사용.
    // 요충지 타입 변경
    // $PgGame->query('UPDATE position SET type = $2, level = m_point.level from m_point WHERE posi_pk IN (SELECT m_posi_pk FROM m_point) AND type = $1', ['D', 'P']);
    // 요충지를 불모지로 변경
    // $PgGame->query('UPDATE position SET type = $2, level = 0 WHERE posi_pk IN (SELECT m_posi_pk FROM m_point) AND type = $1', ['P', 'D']);

    $PgGame->commit();
} catch (Throwable $e) {
    $PgGame->rollback();
    print_r($e);
}
