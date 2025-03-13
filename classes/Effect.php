<?php

class Effect
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Resource $Resource;
    protected GoldPop $GoldPop;
    protected FigureReCalc|null $FigureReCalc;
    protected Log $Log;

    public array|null $territory_hero_effects;
    public array|null $technique_effects;
    public array|null $item_buff_effects;

    public function __construct(Session $_Session, Pg $_PgGame, Resource $Resource, GoldPop $GoldPop, FigureReCalc|null $FigureReCalc)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Resource = $Resource;
        $this->GoldPop = $GoldPop;
        $this->FigureReCalc = $FigureReCalc;

        $this->initEffects();
    }

    function classLog(): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
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

    public function initEffects(): true
    {
        $this->territory_hero_effects = null;
        $this->technique_effects = null;
        $this->item_buff_effects = null;
        return true;
    }

    // 효과 pk의 효과 type들을 리턴
    function getEffectTypes($_effects): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['table_effect']);
        $effect_types = [];
        foreach ($_effects AS $pk) {
            foreach ($_M['EFFE'] AS $k => $v) {
                if (array_key_exists($pk, $v)) {
                    $effect_types[] = $k;
                }
            }
        }
        // 중복 제거 후 리턴
        return array_unique($effect_types);
    }

    /*
     * 영웅이 가진 모든 능력효과와 기술 효과 추출
     */
    function getHeroCapacityEffects($_hero_pk): array|false
    {
        if (! $_hero_pk) {
            return false;
        }
        $capacities = []; // all, assign, skill
        $capacities['assign'] = [];
        $capacities['skill'] = [];
        $capacities['all'] = [];

        $this->PgGame->query("SELECT t1.leadership, t1.mil_force, t1.intellect, t1.politics, t1.charm,
  t3.slot_pk1, t3.m_hero_skil_pk1, t3.main_slot_pk1, t3.slot_pk2, t3.m_hero_skil_pk2, t3.main_slot_pk2,
  t3.slot_pk3, t3.m_hero_skil_pk3, t3.main_slot_pk3, t3.slot_pk4, t3.m_hero_skil_pk4, t3.main_slot_pk4,
  t3.slot_pk5, t3.m_hero_skil_pk5, t3.main_slot_pk5, t3.slot_pk6, t3.m_hero_skil_pk6, t3.main_slot_pk6
FROM my_hero t1, hero t2, getmyheroskillslot({$_hero_pk}) t3
WHERE t1.hero_pk = t2.hero_pk AND t1.hero_pk = $1", [$_hero_pk]);
        if (! $this->PgGame->fetch()) {
            return false;
        }
        $r =& $this->PgGame->row;
        if (! $r) {
            return false;
        }

        // 보유 기술효과
        foreach ([1, 2, 3, 4, 5, 6] as $i) {
            if (isset($r["m_hero_skil_pk{$i}"])) {
                if ($r["slot_pk{$i}"] == $r["main_slot_pk{$i}"]) {
                    $capacities['skill'][] = $r["m_hero_skil_pk{$i}"];
                }
            }
        }
        /* TODO for문으로 간소화 하였으니 동작하면 지우자
         * if ($r['m_hero_skil_pk1']) {
            if ($r['slot_pk1'] == $r['main_slot_pk1']) {
                $capacities['skill'][] = $r['m_hero_skil_pk1'];
            }
        }
        if ($r['m_hero_skil_pk2']) {
            if ($r['slot_pk2'] == $r['main_slot_pk2']) {
                $capacities['skill'][] = $r['m_hero_skil_pk2'];
            }
        }
        if ($r['m_hero_skil_pk3']) {
            if ($r['slot_pk3'] == $r['main_slot_pk3'])
                $capacities['skill'][] = $r['m_hero_skil_pk3'];
        }
        if ($r['m_hero_skil_pk4'])
        {
            if ($r['slot_pk4'] == $r['main_slot_pk4'])
                $capacities['skill'][] = $r['m_hero_skil_pk4'];
        }
        if ($r['m_hero_skil_pk5'])
        {
            if ($r['slot_pk5'] == $r['main_slot_pk5'])
                $capacities['skill'][] = $r['m_hero_skil_pk5'];
        }
        if ($r['m_hero_skil_pk6'])
        {
            if ($r['slot_pk6'] == $r['main_slot_pk6'])
                $capacities['skill'][] = $r['m_hero_skil_pk6'];
        }*/

        // 보유 능력효과
        $leadership = intval($r['leadership'] / 10);
        $mil_force = intval($r['mil_force'] / 10);
        $intellect = intval($r['intellect'] / 10);
        $politics = intval($r['politics'] / 10);
        $charm = intval($r['charm'] / 10);

        $this->PgGame->query('SELECT m_hero_assi_pk
FROM m_hero_assign
WHERE ( stat_type = \'L\' AND stat_step = $1)
   OR ( stat_type = \'M\' AND stat_step = $2)
   OR ( stat_type = \'I\' AND stat_step = $3)
   OR ( stat_type = \'P\' AND stat_step = $4)
   OR ( stat_type = \'C\' AND stat_step = $5)', [$leadership, $mil_force, $intellect, $politics, $charm]);
        while ($this->PgGame->fetch()) {
            $capacities['assign'][]= $this->PgGame->row['m_hero_assi_pk'];
        }
        $capacities['all'] = array_merge($capacities['assign'], $capacities['skill']);
        return $capacities;
    }

    // 건물 배속시 발휘 가능한 효과
    function getHeroAppliedAssignEffects($_m_buil_pk, $_capacities): array
    {
        $applies = []; // all, assign, skill
        $applies['assign'] = [];
        $applies['skill'] = [];
        $applies['all'] = [];

        if (COUNT($_capacities['assign']) > 0) {
            $this->PgGame->query('SELECT m_hero_assi_pk FROM m_building_hero_assign WHERE m_buil_pk = $1 AND m_hero_assi_pk = ANY ($2)', [$_m_buil_pk, '{'. implode(',', $_capacities['assign']). '}']);
            while ($this->PgGame->fetch()) {
                $applies['assign'][] = $this->PgGame->row['m_hero_assi_pk'];
            }
        }

        if (COUNT($_capacities['skill']) > 0) {
            // 영웅기술은 중복 적용 가능하도록 변경
            $query_params = [$_m_buil_pk];
            $query_strings = [];
            $col_cnt = 2;
            foreach ($_capacities['skill'] AS $pk) {
                $query_strings[] = 'SELECT m_hero_skil_pk FROM m_building_hero_skill WHERE m_buil_pk = $1 AND m_hero_skil_pk = $'. $col_cnt;
                $query_params[] = $pk;
                $col_cnt++;
            }
            $sql = implode(' union all ', $query_strings);
            $this->PgGame->query($sql, $query_params);
            while ($this->PgGame->fetch()) {
                $applies['skill'][] = $this->PgGame->row['m_hero_skil_pk'];
            }
        }
        $applies['all'] = array_merge($applies['assign'], $applies['skill']);
        return $applies;
    }

    // 명령 배속시 발휘 가능한 효과
    function getHeroAppliedCommandEffects($_m_cmd_pk, $_capacities): array
    {
        $applies = [];
        $applies['assign'] = [];
        $applies['skill'] = [];
        $applies['all'] = [];

        if (isset($_capacities['assign']) && is_array($_capacities['assign']) && COUNT($_capacities['assign']) > 0) {
            $this->PgGame->query('SELECT m_hero_assi_pk FROM m_cmd_hero_assign WHERE m_cmd_pk = $1 AND m_hero_assi_pk = ANY ($2)', [$_m_cmd_pk, '{'. implode(',', $_capacities['assign']). '}']);
            while ($this->PgGame->fetch()) {
                $applies['assign'][] = $this->PgGame->row['m_hero_assi_pk'];
            }
        }

        // 영웅기술은 중복 적용 가능하도록 변경
        $query_params = [$_m_cmd_pk];
        $query_strings = [];
        $col_cnt = 2;
        if (isset($_capacities['skill']) && COUNT($_capacities['skill']) > 0) {
            foreach ($_capacities['skill'] AS $pk) {
                $query_strings[] = 'SELECT m_hero_skil_pk FROM m_cmd_hero_skill WHERE m_cmd_pk = $1 AND m_hero_skil_pk = $'. $col_cnt;
                $query_params[] = $pk;
                $col_cnt++;
            }
            $sql = implode(' union all ', $query_strings);

            $this->PgGame->query($sql, $query_params);
            while ($this->PgGame->fetch()) {
                $applies['skill'][] = $this->PgGame->row['m_hero_skil_pk'];
            }
        }
        $applies['all'] = array_merge($applies['assign'], $applies['skill']);
        return $applies;
    }

    // 건물 배속 효과 적용
    function setTerritoryHeroEffects($_posi_pk, $_in_cast_pk, $_hero_pk, $_applies): int
    {
        $cnt = 0;
        // debug_mesg('I', __CLASS__, __FUNCTION__, __LINE__, 'setTerritoryHeroEffects');

        // 배속 능력 효과
        foreach($_applies['assign'] AS $pk) {
            $this->PgGame->query('INSERT INTO territory_hero_assign (posi_pk, m_hero_assi_pk, hero_pk, in_cast_pk, applied_dt, update_dt) VALUES ($1, $2, $3, $4, now(), now())', [$_posi_pk, $pk, $_hero_pk, $_in_cast_pk]);
            $cnt++;
        }

        // 배속 기술 효과
        foreach($_applies['skill'] AS $pk) {
            $this->PgGame->query('INSERT INTO territory_hero_skill (posi_pk, m_hero_skil_pk, hero_pk, in_cast_pk, applied_dt, update_dt, skill_cnt) VALUES ($1, $2, $3, $4, now(), now(), $5)', [$_posi_pk, $pk, $_hero_pk, $_in_cast_pk, $cnt]);
            $cnt++;
        }

        // 캐시 리셋
        $this->territory_hero_effects = null;
        return $cnt;
    }

    // 건물 배속 효과 해제
    function unsetTerritoryHeroEffects($_posi_pk, $_in_cast_pk): array
    {
        $pks = [];
        // debug_mesg('I', __CLASS__, __FUNCTION__, __LINE__, 'unsetTerritoryHeroEffects');

        $query_params = [$_posi_pk, $_in_cast_pk];

        // 배속 능력 해제
        $this->PgGame->query('SELECT m_hero_assi_pk AS pk FROM territory_hero_assign WHERE posi_pk = $1 AND in_cast_pk = $2', $query_params);
        while ($this->PgGame->fetch()) {
            $pks[] = $this->PgGame->row['pk'];
        }

        // 배속 기술 해제
        $this->PgGame->query('SELECT m_hero_skil_pk AS pk FROM territory_hero_skill WHERE posi_pk = $1 AND in_cast_pk = $2', $query_params);
        while ($this->PgGame->fetch()) {
            $pks[] = $this->PgGame->row['pk'];
        }

        $this->PgGame->query('DELETE FROM territory_hero_assign WHERE posi_pk = $1 AND in_cast_pk = $2', $query_params);
        $this->PgGame->query('DELETE FROM territory_hero_skill WHERE posi_pk = $1 AND in_cast_pk = $2', $query_params);

        // 캐시 리셋
        $this->territory_hero_effects = null;

        return $pks;
    }

    // 영지효과 - territory_hero_assign 과 skill 전체
    function fetchTerritoryHeroEffects($_posi_pk): void
    {
        if ($this->territory_hero_effects != null) {
            return;
        }
        // debug_mesg('I', __CLASS__, __FUNCTION__, __LINE__, 'fetchTerritoryHeroEffects');

        // 영지 전체의 영웅 배속 능력/기술 효과
        $this->territory_hero_effects = [];
        $this->PgGame->query('SELECT m_hero_assi_pk AS pk FROM territory_hero_assign WHERE posi_pk = $1
UNION ALL
SELECT m_hero_skil_pk AS pk FROM territory_hero_skill WHERE posi_pk = $1 AND in_cast_pk != $2', [$_posi_pk, 2]);
        while ($this->PgGame->fetch()) {
            $this->territory_hero_effects[] = $this->PgGame->row['pk'];
        }
    }

    // 영지효과 - technique 에 0 이상을 m_technique_effect 에서 추출한 전체
    function fetchTechniqueEffects($_posi_pk): void
    {
        if ($this->technique_effects != null) {
            return;
        }
        // debug_mesg('I', __CLASS__, __FUNCTION__, __LINE__, 'fetchTechniqueEffects');

        $this->technique_effects = [];

        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['technique']);

        // 영지의 기술 효과
        $this->PgGame->query('SELECT * FROM technique WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $r = &$this->PgGame->row;
        if (! $r) {
            return;
        }
        foreach ($_M['TECH_C'] AS $k => $v) {
            if ($r[$k] > 0) {
                $this->technique_effects[] = sprintf('%d_%02d', $v['m_tech_pk'], $r[$k]);
            }
        }
    }

    // 영지효과 - 아이템 버프 전체
    function fetchItemBuffEffects($_posi_pk): void
    {
        if ($this->item_buff_effects != null) {
            return;
        }
        // debug_mesg('I', __CLASS__, __FUNCTION__, __LINE__, 'fetchItemBuffEffects');

        $this->item_buff_effects = [];
        // 영지에 적용된 아이템 버프 효과
        $this->PgGame->query('SELECT m_item_pk AS pk FROM territory_item_buff WHERE posi_pk = $1 ORDER BY m_item_pk', [$_posi_pk]);
        while ($this->PgGame->fetch()) {
            // ※ 요충지와 중첩되지 않게 하기위해 추가함. m_item_pk가 낮은 순이므로 요충지 버프가 먼저 추가됨.
            if ($this->PgGame->row['pk'] == 500501 || $this->PgGame->row['pk'] == 500505) {
                // 이벤트 버프일 경우
                if (in_array(500221, $this->item_buff_effects)) {
                    // 요충지 버프가 적용 중인지 체크한 후 true 이면 패스함.
                    continue;
                }
            }
            $this->item_buff_effects[] = $this->PgGame->row['pk'];
        }
    }

    // fetch 래핑 함수
    function fetchAppliedEffects($_posi_pk): void
    {
        $this->fetchTerritoryHeroEffects($_posi_pk);
        $this->fetchTechniqueEffects($_posi_pk);
        $this->fetchItemBuffEffects($_posi_pk);
    }

    /*
     * 발생한 효과 type 전체 중 Active 효과에 대한 처리를 위해 통합 update 함수 호출
     */
    function setUpdateEffectTypes($_posi_pk, $_effect_types): void
    {
        foreach ($_effect_types AS $v) {
            if ($v == 'all_prod_increase') {
                $this->setUpdateEffectTypesResource($_posi_pk, ['food', 'horse', 'lumber', 'iron']);
            } else if ($v == 'food_prod_increase') {
                $this->setUpdateEffectTypesResource($_posi_pk, ['food']);
            } else if ($v == 'horse_prod_increase') {
                $this->setUpdateEffectTypesResource($_posi_pk, ['horse']);
            } else if ($v == 'lumber_prod_increase') {
                $this->setUpdateEffectTypesResource($_posi_pk, ['lumber']);
            } else if ($v == 'iron_prod_increase') {
                $this->setUpdateEffectTypesResource($_posi_pk, ['iron']);
            } else if ($v == 'population_upward_increase' || $v == 'population_upward_increase_re') {
                $this->setUpdateEffectTypesPopulationUpward($_posi_pk);
            } else if ($v == 'tax_rate_bonus_increase') {
                $this->setUpdateEffectTypesTaxRateBonus($_posi_pk);
            } else if ($v == 'storage_max_increase') {
                $this->setUpdateEffectTypesStorageMax($_posi_pk);
            }
        }
    }

    // 액티브 효과 - 자원
    function setUpdateEffectTypesResource($_posi_pk, $_resource_types): void
    {
        $this->classLog();

        // 현 시점 Tick 수치 저장
        $this->Resource->save($_posi_pk);

        $quest_string = 'UPDATE production SET %s_pct_plus_tech = $1, %s_pct_plus_hero_assign = $2, %s_pct_plus_hero_skill = $3, %s_pct_plus_item = $4, last_update_dt = now() WHERE posi_pk = $5';
        foreach ($_resource_types AS $v) {
            $ret = $this->getEffectedValue($_posi_pk, ['all_prod_increase', $v.'_prod_increase'], 100); // 100  수치는 의미 없음

            // production table 갱신
            $sql = sprintf($quest_string, $v, $v, $v, $v);
            $this->PgGame->query($sql, [$ret['effected_values']['tech'], $ret['effected_values']['hero_assign'], $ret['effected_values']['hero_skill'], $ret['effected_values']['item'], $_posi_pk]);

            // Resource ReCalc
            $this->classFigureReCalc();
            $this->FigureReCalc->finalProduction($_posi_pk, $v, false);

            // territory 갱신 값 전달
            $this->Session->sqAppend('PROD', [
                $v.'_pct_plus_tech' => $ret['effected_values']['tech'],
                $v.'_pct_plus_hero_assign' => $ret['effected_values']['hero_assign'],
                $v.'_pct_plus_hero_skill' => $ret['effected_values']['hero_skill'],
                $v.'_pct_plus_item' => $ret['effected_values']['item']
            ], null, $this->Session->lord['lord_pk'], $_posi_pk);
            // log
            $this->Log->setProduction(null, $_posi_pk, $v.'_pct_plus_tech['.$ret['effected_values']['tech'].'];'.$v.'_pct_plus_hero_assign['.$ret['effected_values']['hero_assign'].'];'.$v.'s_pct_plus_hero_skill['.$ret['effected_values']['hero_skill'].'];'.$v.'_pct_plus_item['.$ret['effected_values']['item'].'];');
        }

        // FigureReCalc->final_production 에서 수치 갱신 못하도록 막고 여기서 갱신
        $this->Resource->get($_posi_pk);
    }

    // 액티브 효과 - 인구
    function setUpdateEffectTypesPopulationUpward($_posi_pk): void
    {
        // debug_mesg('I', __CLASS__, __FUNCTION__, __LINE__, 'population_upward');

        // 저장
        $this->GoldPop->save($_posi_pk);
        $ret = $this->getEffectedValue($_posi_pk, ['population_upward_increase', 'population_upward_increase_re'], 0);

        $this->PgGame->query('UPDATE territory SET population_upward_plus_tech = $1, population_upward_plus_hero_assign = $2, population_upward_plus_hero_skill = $3, population_upward_plus_item = $4, population_upward_plus_amount = $5 WHERE posi_pk = $6',
            [$ret['effected_values']['tech'], $ret['effected_values']['hero_assign'], $ret['effected_values']['hero_skill'], $ret['effected_values']['item'], $ret['value'], $_posi_pk]);

        // 갱신
        $this->GoldPop->get($_posi_pk);

        // territory 갱신 값 전달
        $this->Session->sqAppend('TERR', [
            'population_upward_plus_tech' => $ret['effected_values']['tech'],
            'population_upward_plus_hero_assign' => $ret['effected_values']['hero_assign'],
            'population_upward_plus_hero_skill' => $ret['effected_values']['hero_skill'],
            'population_upward_plus_item' => $ret['effected_values']['item'],
            'population_upward_plus_amount' => $ret['value']
        ], null, $this->Session->lord['lord_pk'], $_posi_pk);
    }

    // 액티브 효과 - 세율
    function setUpdateEffectTypesTaxRateBonus($_posi_pk): void
    {
        // 저장
        $this->GoldPop->save($_posi_pk);

        $ret = $this->getEffectedValue($_posi_pk, ['tax_rate_bonus_increase'], 0);

        $this->PgGame->query('UPDATE territory SET tax_rate_plus_tech = $1, tax_rate_plus_hero_assign = $2, tax_rate_plus_hero_skill = $3, tax_rate_plus_item = $4, tax_rate_plus_amount = $5 WHERE posi_pk = $6',
            [$ret['effected_values']['tech'], $ret['effected_values']['hero_assign'], $ret['effected_values']['hero_skill'], $ret['effected_values']['item'], $ret['value'], $_posi_pk]);

        // debug_mesg('I', __CLASS__, __FUNCTION__, __LINE__, $ret['effected_values']['item']);

        // 갱신
        $this->GoldPop->get($_posi_pk);

        // territory 갱신 값 전달
        $this->Session->sqAppend('TERR', [
            'tax_rate_plus_tech' => $ret['effected_values']['tech'],
            'tax_rate_plus_hero_assign' => $ret['effected_values']['hero_assign'],
            'tax_rate_plus_hero_skill' => $ret['effected_values']['hero_skill'],
            'tax_rate_plus_item' => $ret['effected_values']['item'],
            'tax_rate_plus_amount' => $ret['value']
        ], null, $this->Session->lord['lord_pk'], $_posi_pk);
    }

    // 창고 저장 비율 증가
    function setUpdateEffectTypesStorageMax($_posi_pk): void
    {
        $this->FigureReCalc->storageMax($_posi_pk);
    }

    // 제공하는 값 $_value 에 $_effect_type 효과를 적용한 값을 리턴 (영웅이 명령을 수행할 경우 명령 효과 추가)
    function getEffectedValue($_posi_pk, $_effect_types, $_value, $_cmd_hero_effects = [])
    {
        // debug_mesg('I', __CLASS__, __FUNCTION__, __LINE__, 'getEffectedValue');
        if (! $_cmd_hero_effects) {
            $_cmd_hero_effects = [];
        }

        $this->fetchAppliedEffects($_posi_pk); // 전체 효과 얻기
        $all_effects = array_merge($this->territory_hero_effects, $this->technique_effects, $this->item_buff_effects, $_cmd_hero_effects); // 전체 효과 merging
        $all_effects_count = array_count_values($all_effects);
        $e_v = 0; // 계산용
        $effected_values = ['all' => [], 'hero_assign' => 0, 'hero_skill' => 0, 'item' => 0, 'tech' => 0]; // 각각의 계산 결과 버퍼 (완료 후 $_value 에 합산)

        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['table_effect']);

        // 하나의 수치를 계산하는데 두개 이상의 효과유형이 적용 가능 - E.g.) 모든자원/개별자원, 모든부대/병과별부대 등등
        foreach ($_effect_types AS $effect_type) {
            foreach ($_M['EFFE'][$effect_type] AS $k => $v) {
                // 타입의 효과가 applied_effects 에 있음 (적용!)
                if (in_array($k, $all_effects)) {
                    // 영웅기술은 중복 적용 가능하도록 변경
                    if ($v['src'] == 'hero_skill') {
                        $repeat = $all_effects_count[$k];
                    } else {
                        $repeat = 1;
                    }
                    for ($i = 0; $i < $repeat; $i++) {
                        // 효과 타입의 소스(능력인지 스킬인지 등) 별 효과합산
                        $effected_values[$v['src']] += $v['e_v'];

                        // 수치 유형(Integer or Percent) 별 적용될 수치 환산
                        if ($v['v_t'] == 'P') {
                            $e_v = $_value * ($v['e_v']*0.01);
                        } else {
                            $e_v = $v['e_v'];
                        }

                        // 단축, 감소 등 minus 처리
                        if ($v['c_t'] == '-') {
                            $e_v *= -1;
                        }

                        // 적용될 값들 저장 (아래에서 다시 foreach를 하는데 여기서 합산해도 될듯도 함.)
                        $effected_values['all'][] = $e_v;

                        if ($v['src'] == 'hero_skill') {
                            $effected_values['m_hero_skil_pk'][] = $k;
                        }
                    }
                }
            }
        }

        // 원 값에 적용
        foreach ($effected_values['all'] AS $v) {
            $_value += $v;
        }

        // 다양한 빽단 처리를 위해서 과정 값들도 함께 리턴
        return ['value' => $_value, 'effected_values' => $effected_values];
    }
}