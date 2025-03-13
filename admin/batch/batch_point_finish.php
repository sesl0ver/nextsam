<?php
set_time_limit(360);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/constant.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// 이벤트가 꺼져있다면 실행 금지.
if (! CONF_NPC_POINT_ENABLE) {
    exit();
}

$NsGlobal = NsGlobal::getInstance();
$Session = new Session(false);
$PgGame = new Pg('DEFAULT');
$Hero = new Hero($Session, $PgGame);
$Troop = new Troop($Session, $PgGame);
$Timer = new Timer($Session, $PgGame);

try {
    $PgGame->begin();
    // TODO 요충지 전투를 종료하고 NPC 및 부대를 모두 제거 하며 주둔 부대를 모두 회군시키는 과정

    // 주둔중인 부대 회군처리
    $PgGame->query('SELECT t2.troo_pk FROM position_point t1, troop t2 WHERE t1.posi_pk = t2.dst_posi_pk AND t2.status = $1', ['C']);
    $PgGame->fetchAll();
    $rows = $PgGame->rows;
    if (COUNT($rows) > 0) {
        foreach ($rows AS $k => $v) {
            $Troop->setStatusRecall($v['troo_pk']);
        }
    }

    // 점령효과 삭제 - queue_pk 찾기
    $PgGame->query('SELECT terr_item_buff_pk, posi_pk FROM territory_item_buff WHERE m_item_pk = $1', [POSITION_POINT_EFFECT_ITEM]);
    $PgGame->fetchAll();
    $rows = $PgGame->rows;
    if (COUNT($rows) > 0) {
        foreach ($rows AS $k => $v) {
            // time_pk 찾기
            $PgGame->query('SELECT time_pk, ( date_part(\'epoch\', end_dt)::integer - date_part(\'epoch\', start_dt)::integer ) as speed_time FROM timer WHERE posi_pk = $1 AND status = $2 AND queue_type = $3 AND queue_pk = $4', [$v['posi_pk'], 'P', 'B', $v['terr_item_buff_pk']]);
            $PgGame->fetch();
            $Timer->speedup($PgGame->row['time_pk'], $PgGame->row['speed_time']);
        }
    }

    $PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['point_finish']);

    $PgGame->commit();
} catch (Throwable $e) {
    $PgGame->rollback();
    print_r($e);
}

// 이 시점에서 init 가 필요할까?
// require_once __DIR__ . '/batch_point_init.php';
