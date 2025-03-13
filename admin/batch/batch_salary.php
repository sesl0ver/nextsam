<?php
set_time_limit(120);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

//-- loyalty down 대상 중 현재값이 1 인 영웅만 yn_strike_notify 셋
$PgGame->query("UPDATE my_hero
SET yn_strike_notify = 'Y'
WHERE hero_pk IN
(
 SELECT hero.hero_pk
 FROM hero,
 (
  SELECT t2.hero_pk
  FROM lord t1, my_hero t2
  WHERE t1.lord_pk = t2.lord_pk AND t2.status = 'A' AND t2.last_appoint_dt < (now()::date + Interval '-1 day +5 minute')::abstime AND ( t1.last_salary_dt IS NULL OR t1.last_salary_dt < (now()::date + Interval '-1 day +5 minute')::abstime )
 ) AS t
 WHERE hero.hero_pk = t.hero_pk AND hero.loyalty BETWEEN 1 AND 3
) AND yn_strike_notify = 'N'");

//-- loyalty down
$sql = "UPDATE hero SET loyalty = loyalty - (CASE WHEN loyalty < 3 THEN loyalty ELSE 3 END) FROM
(SELECT t2.hero_pk FROM lord t1, my_hero t2 WHERE t1.lord_pk = t2.lord_pk AND t2.status = 'A' AND
t2.last_appoint_dt < (now()::date + Interval '-1 day +5 minute')::abstime AND
( t1.last_salary_dt IS NULL OR t1.last_salary_dt < (now()::date + Interval '-1 day +5 minute')::abstime )) AS t";

for ($i = 1; $i <= 100; $i+=5) {
	$i_n = $i + 4;
	if ($i_n == 100) { // TODO 나중에 확인해서 개선 해야할듯?
		$i_n = 99;
	}
	$new_sql = sprintf('%s WHERE hero.hero_pk = t.hero_pk AND hero.loyalty BETWEEN %d AND %d', $sql, $i, $i_n);
	$PgGame->query($new_sql);
}

//-- 데일리 급여지급 퀘스트 리셋
// UPDATE my_quest SET status = 'P', reward_status = 'N', start_dt = now(), last_up_dt = now(), invisible = 'N' WHERE m_ques_pk = 600101;
$PgGame->query("UPDATE my_quest SET status = 'P', reward_status = 'N', start_dt = now(), last_up_dt = now(), invisible = 'N' WHERE m_ques_pk = 600101 AND (status = 'C' OR invisible = 'Y')");
