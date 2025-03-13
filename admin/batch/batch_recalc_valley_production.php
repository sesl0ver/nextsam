<?php
set_time_limit(30);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');

$Resource = new Resource($Session, $PgGame);
$GoldPop = new GoldPop($Session, $PgGame);
$FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
$Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);

$PgGame->query("select ta.posi_pk, ta.food, ta.horse, ta.lumber, ta.iron
from
(
 select
  t1.posi_pk
  ,(CASE WHEN sum(food) IS NULL THEN 0 ELSE sum(food) END) food
  ,(CASE WHEN sum(horse) IS NULL THEN 0 ELSE sum(horse) END) horse
  ,(CASE WHEN sum(lumber) IS NULL THEN 0 ELSE sum(lumber) END) lumber
  ,(CASE WHEN sum(iron) IS NULL THEN 0 ELSE sum(iron) END) iron
 from territory t1 left join territory_valley t2 on (t1.posi_pk = t2.posi_pk) left join position t3 on (t3.posi_pk = t2.valley_posi_pk) left join m_productivity_valley t4 on (t3.type = t4.valley_type and t3.level = t4.level)
 group by t1.posi_pk
) ta, production tb
where ta.posi_pk = tb.posi_pk
 and (
  ta.food <> tb.food_production_valley
  or ta.horse <> tb.horse_production_valley
  or ta.lumber <> tb.lumber_production_valley
  or ta.iron <> tb.iron_production_valley
)");
$PgGame->fetchAll();
$rows = $PgGame->rows;

$cnt = 0;

foreach ($rows AS $v) {
	$PgGame->query('UPDATE production SET food_production_valley = $1, horse_production_valley = $2,
 lumber_production_valley = $3, iron_production_valley = $4 WHERE posi_pk = $5', [$v['food'], $v['horse'], $v['lumber'], $v['iron'], $v['posi_pk']]);

	$Effect->initEffects();

	$Effect->setUpdateEffectTypesResource($v['posi_pk'], ['food', 'horse', 'lumber', 'iron']);

	// debug_mesg('R', __CLASS__, __FUNCTION__, __LINE__, $v['posi_pk']. ';batch recalc valley production');

	$cnt++;
}

echo $cnt. "\n";

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['recalc_valley_production']);