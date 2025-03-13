<?php
set_time_limit(180);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');

$Resource = new Resource($Session, $PgGame);
$GoldPop = new GoldPop($Session, $PgGame);
$FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
$Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);

$PgGame->query("select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500024, 500029))
 where t1.food_pct_plus_item = 25 and (t2.end_dt is null or (t2.end_dt is not null and t2.end_dt < now()))
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500024, 500029))
 where t1.food_pct_plus_item = 0 and t2.end_dt > now()
union
select t1.posi_pk
from technique t1, production t2, m_technique_effect t3
where t1.posi_pk = t2.posi_pk and t3.m_tech_pk = 300100 and t3.level = t1.agriculture and t3.effect_type = 'food_prod_increase'
 and t2.food_pct_plus_tech <> t3.effect_value
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500025, 500030))
 where t1.horse_pct_plus_item = 25 and (t2.end_dt is null or (t2.end_dt is not null and t2.end_dt < now()))
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500025, 500030))
 where t1.horse_pct_plus_item = 0 and t2.end_dt > now()
union
select t1.posi_pk
from technique t1, production t2, m_technique_effect t3
where t1.posi_pk = t2.posi_pk and t3.m_tech_pk = 300200 and t3.level = t1.stock_farming and t3.effect_type = 'horse_prod_increase'
 and t2.horse_pct_plus_tech <> t3.effect_value
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500026, 500031))
 where t1.lumber_pct_plus_item = 25 and (t2.end_dt is null or (t2.end_dt is not null and t2.end_dt < now()))
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500026, 500031))
 where t1.lumber_pct_plus_item = 0 and t2.end_dt > now()
union
select t1.posi_pk
from technique t1, production t2, m_technique_effect t3
where t1.posi_pk = t2.posi_pk and t3.m_tech_pk = 300300 and t3.level = t1.lumbering and t3.effect_type = 'lumber_prod_increase'
 and t2.lumber_pct_plus_tech <> t3.effect_value
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500027, 500032))
 where t1.iron_pct_plus_item = 25 and (t2.end_dt is null or (t2.end_dt is not null and t2.end_dt < now()))
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500027, 500032))
 where t1.iron_pct_plus_item = 0 and t2.end_dt > now()
union
select t1.posi_pk
from technique t1, production t2, m_technique_effect t3
where t1.posi_pk = t2.posi_pk and t3.m_tech_pk = 300400 and t3.level = t1.mining and t3.effect_type = 'iron_prod_increase'
 and t2.iron_pct_plus_tech <> t3.effect_value
union
 select t1.posi_pk
 from territory t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500028,500034))
 where t1.tax_rate_plus_item = 10 and (t2.end_dt is null or (t2.end_dt is not null and t2.end_dt < now()))
union
 select t1.posi_pk
 from territory t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500028,500034))
 where t1.tax_rate_plus_item = 0 and t2.end_dt > now()
union
 select t1.posi_pk
 from territory t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500033))
 where t1.population_upward_plus_item = 100 and (t2.end_dt is null or (t2.end_dt is not null and t2.end_dt < now()))
union
 select t1.posi_pk
 from territory t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500033))
 where t1.population_upward_plus_item = 0 and t2.end_dt > now()
union
select t1.posi_pk
from territory_hero_assign t1, m_hero_assign_effect t2, territory t3
where t1.m_hero_assi_pk = t2.m_hero_assi_pk and t1.posi_pk = t3.posi_pk
 and t2.effect_type = 'population_upward_increase'
 and t2.effect_value <> t3.population_upward_plus_hero_assign
union
select t1.posi_pk
from technique t1, territory t2, m_technique_effect t3
where t1.posi_pk = t2.posi_pk and t3.m_tech_pk = 300700 and t3.level = t1.astronomy and t3.effect_type = 'population_upward_increase'
 and t2.population_upward_plus_tech <> t3.effect_value
union
select t1.posi_pk
from territory_hero_assign t1, m_hero_assign_effect t2, production t3
where t1.m_hero_assi_pk = t2.m_hero_assi_pk and t1.posi_pk = t3.posi_pk
 and t2.effect_type = 'all_prod_increase'
 and
 (
  t2.effect_value <> t3.food_pct_plus_hero_assign
  or t2.effect_value <> t3.horse_pct_plus_hero_assign
  or t2.effect_value <> t3.lumber_pct_plus_hero_assign
  or t2.effect_value <> t3.iron_pct_plus_hero_assign
 )");
$PgGame->fetchAll();
$rows = $PgGame->rows;

$cnt = 0;

foreach ($rows AS $v) {
	$Effect->initEffects();

	$Effect->setUpdateEffectTypesResource($v['posi_pk'], ['food', 'horse', 'lumber', 'iron']);
	$Effect->setUpdateEffectTypesPopulationUpward($v['posi_pk']);
	$Effect->setUpdateEffectTypesTaxRateBonus($v['posi_pk']);
	$Effect->setUpdateEffectTypesStorageMax($v['posi_pk']);

	// debug_mesg('R', __CLASS__, __FUNCTION__, __LINE__, $v['posi_pk']. ';batch recalc production');

	$cnt++;
}

$PgGame->query('UPDATE lord SET batch_recalc_check_dt = now()');

echo $cnt. "\n";