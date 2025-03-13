<?php
set_time_limit(120);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/constant.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');
$NsGlobal = new NsGlobal();
$NsGlobal->requireMasterData(['quest']);

// 매일매일 퀘스트 초기화
global $_M;
$quest_types = ['daily', 'daily_event'];
$m_quest = Useful::arrayFindAll($_M['QUES'], function ($m) use ($quest_types) {
    return in_array($m['type'], $quest_types);
});
foreach ($m_quest as $m) {
    $PgGame->query('UPDATE my_quest SET status = $1, reward_status = $2, start_dt = now(), last_up_dt = now(), invisible = $3, condition_value = 0 WHERE m_ques_pk = $4', ['P', 'N', 'N', $m['m_ques_pk']]);
}

// 매일매일 퀘스트 초기화시 버프 이벤트도 같이 초기화 해줌
$PgGame->query('UPDATE my_event SET time_buff_count = $1 WHERE time_buff_count > 0', [0]);

// 매직 큐브 카운트 리셋
$PgGame->query('UPDATE m_item SET magiccube_left_count = 100000000 WHERE magiccube_default_count > 0');

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now();', ['daily_reset']);
