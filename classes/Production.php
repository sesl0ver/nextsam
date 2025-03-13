<?php

class Production
{
    protected Session $Session;
    protected Pg $PgGame;
    protected FigureReCalc $FigureReCalc;
    protected GoldPop $GoldPop;
    protected Resource $Resource;
    protected Effect $Effect;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    function classResource(): void
    {
        if (! isset($this->Resource)) {
            $this->Resource = new Resource($this->Session, $this->PgGame);
        }
    }

    function classGoldPop(): void
    {
        if (! isset($this->GoldPop)) {
            $this->GoldPop = new GoldPop($this->Session, $this->PgGame);
        }
    }

    function classFigureReCalc(): void
    {
        $this->classResource();
        $this->classGoldPop();

        if (! isset($this->FigureReCalc)) {
            $this->FigureReCalc = new FigureReCalc($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classEffect(): void
    {
        if (! isset($this->Effect)) {
            $this->classResource();
            $this->classGoldPop();
            $this->classFigureReCalc();
            $this->Effect = new Effect($this->Session, $this->PgGame, $this->Resource, $this->GoldPop, $this->FigureReCalc);
        }
    }

    function get($_posi_pk, $_last_update_dt = 0): bool
    {
        $this->PgGame->query('SELECT gold_providence, gold_pct_plus_tech, gold_pct_plus_hero_assign, gold_pct_plus_hero_skill, gold_pct_plus_item, 
		food_providence, food_production_territory, food_production_valley, food_pct_plus_tech, food_pct_plus_hero_assign, food_pct_plus_hero_skill, food_pct_plus_item,
		horse_providence, horse_production_territory, horse_production_valley, horse_pct_plus_tech, horse_pct_plus_hero_assign, horse_pct_plus_hero_skill, horse_pct_plus_item,
		lumber_providence, lumber_production_territory, lumber_production_valley, lumber_pct_plus_tech, lumber_pct_plus_hero_assign, lumber_pct_plus_hero_skill, lumber_pct_plus_item,
		iron_providence, iron_production_territory, iron_production_valley, iron_pct_plus_tech, iron_pct_plus_hero_assign, iron_pct_plus_hero_skill, iron_pct_plus_item
		FROM production WHERE posi_pk = $1 AND date_part(\'epoch\', last_update_dt)::integer > $2', [$_posi_pk, $_last_update_dt]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $r = &$this->PgGame->row;
        if (! $r) {
            return false;
        }
        $this->Session->sqAppend('PROD', $r, null, $this->Session->lord['lord_pk'], $_posi_pk);
        return true;
    }

    public function recalculation ($_lord_pk = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->Session->lord['lord_pk'] : $_lord_pk;
        $this->PgGame->query('select date_part(\'epoch\', batch_recalc_check_dt)::integer from lord where lord_pk = $1', [$_lord_pk]);
        if ($this->PgGame->fetchOne() + 7200 < time()) {
            $posi_arr = [];
            $this->PgGame->query('select posi_pk from position where lord_pk = $1 and type = $2', [$_lord_pk, 'T']);
            while ($this->PgGame->fetch()) {
                $posi_arr[] = "'". $this->PgGame->row['posi_pk']. "'";
            }
            $posi_str = implode(',', $posi_arr);

            $this->PgGame->query("select ta.posi_pk, ta.food, ta.horse, ta.lumber, ta.iron
from
(select
  t1.posi_pk
  ,(CASE WHEN sum(food) IS NULL THEN 0 ELSE sum(food) END) food
  ,(CASE WHEN sum(horse) IS NULL THEN 0 ELSE sum(horse) END) horse
  ,(CASE WHEN sum(lumber) IS NULL THEN 0 ELSE sum(lumber) END) lumber
  ,(CASE WHEN sum(iron) IS NULL THEN 0 ELSE sum(iron) END) iron
 from territory t1 left join territory_valley t2 on (t1.posi_pk = t2.posi_pk) left join position t3 on (t3.posi_pk = t2.valley_posi_pk) left join m_productivity_valley t4 on (t3.type = t4.valley_type and t3.level = t4.level)
 where t1.posi_pk in ({$posi_str})
 group by t1.posi_pk
) ta, production tb
where ta.posi_pk = tb.posi_pk
 and (
  ta.food <> tb.food_production_valley
  or ta.horse <> tb.horse_production_valley
  or ta.lumber <> tb.lumber_production_valley
  or ta.iron <> tb.iron_production_valley
)");
            $this->PgGame->fetchAll();
            $rows = $this->PgGame->rows;

            $this->classEffect();
            foreach ($rows AS $v) {
                $this->PgGame->query('update production set 
 food_production_valley = $1,
 horse_production_valley = $2,
 lumber_production_valley = $3,
 iron_production_valley = $4
where posi_pk = $5', [$v['food'], $v['horse'], $v['lumber'], $v['iron'], $v['posi_pk']]);

                $this->Effect->initEffects();
                $this->Effect->setUpdateEffectTypesResource($v['posi_pk'], ['food', 'horse', 'lumber', 'iron']);
            }

            $this->PgGame->query("select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500024, 500029))
 where t1.food_pct_plus_item = 25 and (t2.end_dt is null or (t2.end_dt is not null and t2.end_dt < now()))
  and t1.posi_pk in ({$posi_str})
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500024, 500029))
 where t1.food_pct_plus_item = 0 and t2.end_dt > now()
  and t1.posi_pk in ({$posi_str})
union
select t1.posi_pk
from technique t1, production t2, m_technique_effect t3
where t1.posi_pk = t2.posi_pk and t3.m_tech_pk = 300100 and t3.level = t1.agriculture and t3.effect_type = 'food_prod_increase'
 and t2.food_pct_plus_tech <> t3.effect_value
and t1.posi_pk in ({$posi_str})
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500025, 500030))
 where t1.horse_pct_plus_item = 25 and (t2.end_dt is null or (t2.end_dt is not null and t2.end_dt < now()))
and t1.posi_pk in ({$posi_str})
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500025, 500030))
 where t1.horse_pct_plus_item = 0 and t2.end_dt > now()
and t1.posi_pk in ({$posi_str})
union
select t1.posi_pk
from technique t1, production t2, m_technique_effect t3
where t1.posi_pk = t2.posi_pk and t3.m_tech_pk = 300200 and t3.level = t1.stock_farming and t3.effect_type = 'horse_prod_increase'
 and t2.horse_pct_plus_tech <> t3.effect_value
and t1.posi_pk in ({$posi_str})
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500026, 500031))
 where t1.lumber_pct_plus_item = 25 and (t2.end_dt is null or (t2.end_dt is not null and t2.end_dt < now()))
and t1.posi_pk in ({$posi_str})
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500026, 500031))
 where t1.lumber_pct_plus_item = 0 and t2.end_dt > now()
and t1.posi_pk in ({$posi_str})
union
select t1.posi_pk
from technique t1, production t2, m_technique_effect t3
where t1.posi_pk = t2.posi_pk and t3.m_tech_pk = 300300 and t3.level = t1.lumbering and t3.effect_type = 'lumber_prod_increase'
 and t2.lumber_pct_plus_tech <> t3.effect_value
and t1.posi_pk in ({$posi_str})
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500027, 500032))
 where t1.iron_pct_plus_item = 25 and (t2.end_dt is null or (t2.end_dt is not null and t2.end_dt < now()))
and t1.posi_pk in ({$posi_str})
union
 select t1.posi_pk
 from production t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500027, 500032))
 where t1.iron_pct_plus_item = 0 and t2.end_dt > now()
and t1.posi_pk in ({$posi_str})
union
select t1.posi_pk
from technique t1, production t2, m_technique_effect t3
where t1.posi_pk = t2.posi_pk and t3.m_tech_pk = 300400 and t3.level = t1.mining and t3.effect_type = 'iron_prod_increase'
 and t2.iron_pct_plus_tech <> t3.effect_value
and t1.posi_pk in ({$posi_str})
union
 select t1.posi_pk
 from territory t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500028,500034))
 where t1.tax_rate_plus_item = 10 and (t2.end_dt is null or (t2.end_dt is not null and t2.end_dt < now()))
and t1.posi_pk in ({$posi_str})
union
 select t1.posi_pk
 from territory t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500028,500034))
 where t1.tax_rate_plus_item = 0 and t2.end_dt > now()
and t1.posi_pk in ({$posi_str})
union
 select t1.posi_pk
 from territory t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500033))
 where t1.population_upward_plus_item = 100 and (t2.end_dt is null or (t2.end_dt is not null and t2.end_dt < now()))
and t1.posi_pk in ({$posi_str})
union
 select t1.posi_pk
 from territory t1 left join territory_item_buff t2 on (t1.posi_pk = t2.posi_pk and t2.m_item_pk in (500033))
 where t1.population_upward_plus_item = 0 and t2.end_dt > now()
and t1.posi_pk in ({$posi_str})
union
select t1.posi_pk
from territory_hero_assign t1, m_hero_assign_effect t2, territory t3
where t1.m_hero_assi_pk = t2.m_hero_assi_pk and t1.posi_pk = t3.posi_pk
 and t2.effect_type = 'population_upward_increase'
 and t2.effect_value <> t3.population_upward_plus_hero_assign
and t1.posi_pk in ({$posi_str})
union
select t1.posi_pk
from technique t1, territory t2, m_technique_effect t3
where t1.posi_pk = t2.posi_pk and t3.m_tech_pk = 300700 and t3.level = t1.astronomy and t3.effect_type = 'population_upward_increase'
 and t2.population_upward_plus_tech <> t3.effect_value
and t1.posi_pk in ({$posi_str})
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
 )
 and t1.posi_pk in ({$posi_str})");
            $this->PgGame->fetchAll();
            $rows = $this->PgGame->rows;

            foreach ($rows AS $v) {
                $this->Effect->initEffects();

                $this->Effect->setUpdateEffectTypesResource($v['posi_pk'], ['food', 'horse', 'lumber', 'iron']);
                $this->Effect->setUpdateEffectTypesPopulationUpward($v['posi_pk']);
                $this->Effect->setUpdateEffectTypesTaxRateBonus($v['posi_pk']);
                $this->Effect->setUpdateEffectTypesStorageMax($v['posi_pk']);
            }

            $this->PgGame->query('update lord set batch_recalc_check_dt = now() where lord_pk = $1', [$_lord_pk]);
        }
    }
}