<?php
set_time_limit(360);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// 이벤트가 꺼져있다면 실행 금지.
if (! CONF_NPC_POINT_ENABLE) {
    exit();
}

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');
$Hero = new Hero($Session, $PgGame);
$Troop = new Troop($Session, $PgGame);

// TODO 요충지 관련 정보
//  https://cafe.daum.net/scsamguk/2oyf/23 - 과거 서비스 했던 콘텐츠 내용
//  1분당 1포인트를 지급했던 것으로 보아 해당 batch 파일은 10분에 1회 호출 하는 것으로 생각됨.
//  교전 가능시간 10시 ~ 24시 14시간동안만 활성화 (기획 변경 가능)
//  매주 화요일 0시 ~ 목요일 9시 59분까지는 비활성화 (기획 변경 가능) - 최종적으로 점령하고 있던 군주는 다음 요충지 활성때까지 요충지 효과를 적용받음
//  요충지 효과는 부대 훈련 시간 감소 20%, 사망자 10% 추가 치료(부상자로 살려준다는 의미인지?).
//  동맹원들끼리도 전쟁할수 있다! (기획 변경 가능한가?)
//  요충지 포기시 재공격까지 6시간 필요.
//  요충지의 전투는 결과는 실제 공/방 포인트에 반영되지 않음.
//  보상은 화요일 01시에 일괄 지급
//  점령 포인트 10포인트 = 1 점령 코인. 점령코인을 사용할 수 있는 제작 퀘스트가 있었음. (데이터에선 누락됨)
//  장기점령 보너스 시간당 (24/48/72/96) = (3000/6000/12000/24000)
//  랭킹 산정은 점령 포인트가 동점일 경우의 랭킹은 최초 요충지 점령 시간 > 요충지 점령 이력 > 최초 요충지 공격 시간 순으로 산정됩니다.

// 점령 시간에 맞춰 점수 업데이트
$PgGame->query('UPDATE ranking_point SET occu_point = occu_point + (floor( date_part(\'epoch\', now())::integer - date_part(\'epoch\', last_tick_up_dt)::integer ) / 60), last_tick_up_dt = now()
FROM position_point t1 WHERE t1.posi_pk = ranking_point.posi_pk AND t1.lord_pk = t1.prev_lord_pk AND t1.lord_pk = ranking_point.lord_pk');

$PgGame->query('UPDATE position_point SET prev_lord_pk = lord_pk WHERE lord_pk != 1 AND lord_pk != prev_lord_pk');

$PgGame->query('UPDATE position_point SET tick = tick + 1');

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['point_occupation_point']);
