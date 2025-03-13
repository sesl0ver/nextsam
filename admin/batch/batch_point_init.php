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

try {
    $PgGame->begin();

    // 랭킹 초기화 및 대륙 초기화
    $PgGame->query('TRUNCATE TABLE ranking_point');
    $PgGame->query('UPDATE position SET lord_pk = null, last_update_dt = now() WHERE posi_pk IN (SELECT m_posi_pk FROM m_point) AND lord_pk IS NOT NULL');

    // 기존 부대 삭제
    $PgGame->query('TRUNCATE TABLE position_point');

    // 1. m_point 검색
    // 2. 해당 레벨에 맞는 영웅과 부대 배치
    $PgGame->query('SELECT m_posi_pk, level FROM m_point');
    $PgGame->fetchAll();
    $rows = $PgGame->rows;

    foreach ($rows AS $k => $v) {
        $hero_base_pk_arr = [];
        $hero_pk_list = [];

        for ($i = 0; $i < 3; $i++) {
            $ret = $Hero->getPointHeroPK($hero_base_pk_arr, 'point', $v['level']);
            $hero_base_pk_arr[] = $ret['m_hero_base_pk'];
            $hero_pk_list[$i]['hero_pk'] = $ret['hero_pk'];
            $hero_pk_list[$i]['desc'] =  $Troop->getHeroDesc($ret['hero_pk']);
        }

        // 부대 생성
        $type = rand(1, 3);
        $PgGame->query('SELECT worker, infantry, pikeman, spearman, scout, archer, horseman, transporter, armed_infantry, armed_horseman, bowman, battering_ram, catapult, adv_catapult FROM m_point_npc_troop WHERE level = $1 AND type = $2', [$v['level'], $type]);
        $PgGame->fetch();
        $army_info = $PgGame->row;

        $end_dt = 'now() + interval \'7 days\'';
        $PgGame->query("INSERT INTO position_point (
posi_pk, status, lord_pk,
captain_hero_pk, captain_desc, director_hero_pk, director_desc, staff_hero_pk, staff_desc, 
army_worker, army_infantry, army_pikeman, army_scout, army_spearman, army_armed_infantry,
army_archer, army_horseman, army_armed_horseman, army_transporter,
army_bowman, army_battering_ram, army_catapult, army_adv_catapult,
end_dt, type
) VALUES (
$1, $2, $3, 
$4, $5, $6, $7, $8, $9,
$10, $11, $12, $13, $14,
$15, $16, $17, $18, $19,
$20, $21, $22, $23,
$end_dt, $24
)", [$v['m_posi_pk'], 'N', 1, $hero_pk_list[0]['hero_pk'], $hero_pk_list[0]['desc'],
            $hero_pk_list[1]['hero_pk'], $hero_pk_list[1]['desc'], $hero_pk_list[2]['hero_pk'], $hero_pk_list[2]['desc'],
            $army_info['worker'], $army_info['infantry'], $army_info['pikeman'], $army_info['scout'],
            $army_info['spearman'], $army_info['armed_infantry'], $army_info['archer'], $army_info['horseman'],
            $army_info['armed_horseman'], $army_info['transporter'], $army_info['bowman'], $army_info['battering_ram'],
            $army_info['catapult'], $army_info['adv_catapult'], $type]);
    }

    $PgGame->query('UPDATE position SET last_update_dt = now() WHERE posi_pk IN (SELECT m_posi_pk FROM m_point)');

    // 요충지 외교서신 보낼 플래그
    $PgGame->query('UPDATE lord SET yn_point_letter = $1 WHERE lord_pk > $2 AND yn_point_letter = $3', ['N', NPC_TROOP_LORD_PK, 'Y']);

    $PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['point_init']);

    $PgGame->commit();
} catch (Throwable $e) {
    $PgGame->rollback();
    print_r($e);
}
