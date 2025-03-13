<?php

class FigureReCalc
{
    public Session $Session;
    protected Pg $PgGame;
    protected Resource $Resource;
    protected GoldPop $GoldPop;
    protected Effect $Effect;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame, Resource $_Resource, GoldPop $_GoldPop)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Resource = $_Resource;
        $this->GoldPop = $_GoldPop;
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

    function classEffect(): void
    {
        $this->classResource();
        $this->classGoldPop();

        if (! isset($this->Effect)) {
            $this->Effect = new Effect($this->Session, $this->PgGame, $this->Resource, $this->GoldPop, null);
        }
    }

    function dispatcher($_posi_pk, $_update_type, $_addData = null): bool
    {
        $result = false;
        //POP : population
        switch ($_update_type) {
            case 'POP_MAX':
                $result = $this->popMax($_posi_pk);
                break;
            case 'POP_ARMY':
                $result = $this->popArmy($_posi_pk);
                break;
            case 'CASTLE_RES_FOOD':
                $result = $this->castleResource($_posi_pk, 'food');
                break;
            case 'CASTLE_RES_HORSE':
                $result = $this->castleResource($_posi_pk, 'horse');
                break;
            case 'CASTLE_RES_LUMBER':
                $result = $this->castleResource($_posi_pk, 'lumber');
                break;
            case 'VALLEY_UPDATE':
                $result = $this->valleyResource($_posi_pk, $_addData);
                break;
            case 'STORAGE_MAX':
                $result = $this->storageMax($_posi_pk);
                break;
            case 'WALL_MAX':
                $result = $this->wallMax($_posi_pk);
                break;
            case 'WALL_FORT':
                $result = $this->wallFort($_posi_pk);
                break;
            case 'BUILD_ARMY':
                $result = $this->buildArmy($_posi_pk, $_addData);
                break;
            case 'BUILD_MEDICAL':
                $result = $this->buildMedical($_posi_pk, $_addData);
                break;
            /*case 'WORLD_FORT':
                $result = $this->worldFort($_posi_pk, $_addData);
                break;*/
            default:
                break;
        }
        return $result;
    }

    function createTerritory($_posi_pk): void
    {
        $this->dispatcher($_posi_pk, 'POP_MAX');
        $this->dispatcher($_posi_pk, 'CASTLE_RES_FOOD');
        $this->dispatcher($_posi_pk, 'CASTLE_RES_HORSE');
        $this->dispatcher($_posi_pk, 'CASTLE_RES_LUMBER');
        $this->dispatcher($_posi_pk, 'STORAGE_MAX');
        $this->dispatcher($_posi_pk, 'WALL_MAX');
    }

    // 인구 최대치 territory.population_max
    function popMax($_posi_pk): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['building', 'providence']);
        $max = 0;
        $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_COTTAGE]);
        $this->PgGame->fetchAll();
        foreach ($this->PgGame->rows as $row) {
            if ($row['level'] > 0) {
                $max += $_M['BUIL'][PK_BUILDING_COTTAGE]['level'][$row['level']]['variation_1'];
            }
        }
        // GoldPop 서버에 save 요청
        $this->GoldPop->save($_posi_pk);
        // DB 업데이트
        $this->PgGame->query('UPDATE territory SET population_max = $1+(SELECT population_max FROM m_providence) WHERE posi_pk = $2', [$max, $_posi_pk]);
        // 서버통보 (이 부분은 접속 중일 때만 유효)
        $this->GoldPop->get($_posi_pk);
        $this->Session->sqAppend('TERR', ['population_max' => $max+$_M['PROV']['population_max']], null, $this->Session->lord['lord_pk'], $_posi_pk);
        return true;
    }

    // 현재 병력수 territory.population_army
    function popArmy($_posi_pk): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army', 'providence']);
        $this->PgGame->query('SELECT * FROM army WHERE posi_pk = $1', [$_posi_pk]);

        $army = 0;
        if ($this->PgGame->fetch()) {
            $r = &$this->PgGame->row;
            foreach ($_M['ARMY_C'] AS $k => $v) {
                if ($r[$k] > 0) {
                    $army += $r[$k]*$v['need_population'];
                }
            }
        }

        // DB 업데이트
        $this->PgGame->query('UPDATE territory SET population_army = $1 WHERE posi_pk = $2', [$army, $_posi_pk]);

        // 병사 갱신
        $this->Session->sqAppend('TERR', ['population_army' => $army], null, $this->Session->lord['lord_pk'], $_posi_pk);

        return true;
    }

    function popLaborForce($_posi_pk): true
    {
        $this->PgGame->Query('UPDATE territory SET population_labor_force = (SELECT food_labor_force_max + horse_labor_force_max + lumber_labor_force_max + iron_labor_force_max FROM production WHERE posi_pk =  $1) WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->Query('SELECT population_labor_force FROM territory WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();

        // 노동인구 갱신
        $this->Session->sqAppend('TERR', $this->PgGame->row, null, $this->Session->lord['lord_pk'], $_posi_pk);

        return true;
    }

    // 자원생산량 (전답, 목장, 제재소)
    function castleResource($_posi_pk, $_resource_type): bool
    {
        global $_M, $NsGlobal;

        $m_buil_pk = constant('PK_BUILDING_'. strtoupper($_resource_type));
        if (!$m_buil_pk) {
            return false;
        }
        $NsGlobal->requireMasterData(['productivity_building']);

        $this->PgGame->query('SELECT level FROM building_out_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, $m_buil_pk]);

        $productivity = 0;
        $labor_force = 0;
        while (($lev = $this->PgGame->fetchOne()) != null) {
            $md =& $_M['PROD_BUIL'][$m_buil_pk][$lev];
            if (isset($md[$_resource_type])) {
                $productivity += $md[$_resource_type];
                $labor_force += $md['labor_force'];
            }
        }

        $col_productivity = $_resource_type. '_productivity';
        $col_labor_force_max = $_resource_type. '_labor_force_max';
        $col_labor_force_curr = $_resource_type. '_labor_force_curr';
        $col_productivity_territory = $_resource_type. '_production_territory';

        $this->PgGame->query("UPDATE production SET $col_productivity = $2, $col_labor_force_max = $3, $col_labor_force_curr = $3, $col_productivity_territory = $2, last_update_dt = now() WHERE posi_pk = $1", [$_posi_pk, $productivity, $labor_force]);

        // log
        $this->classLog();
        $this->Log->setProduction(null, $_posi_pk, $_resource_type. '_productivity['.$productivity.'];'.$_resource_type. '_labor_force_max['.$labor_force.'];'.$_resource_type. '_labor_force_curr['.$labor_force.'];'.$_resource_type. '_production_territory['.$productivity.'];');

        // 노동인구 최대치 재계산
        $this->popLaborForce($_posi_pk);

        // 효과를 포함한 최종 Production 재계산
        $this->finalProduction($_posi_pk, $_resource_type);

        // 갱신
        $this->updateProduction($_posi_pk, [$col_productivity => $productivity, $col_labor_force_max => $labor_force, $col_labor_force_curr => $labor_force, $col_productivity_territory => $productivity]);

        return true;
    }

    // 외부자원생산량 (초원, 숲, 광산) - 획득/상실 , plus/minus 처리가 가능하다.
    function valleyResource($_posi_pk, $_valley_posi_pk): true
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['productivity_valley']);

        $update_types = ['food' => 0, 'horse' => 0, 'lumber' => 0, 'iron' => 0];
        $this->PgGame->query('SELECT valley_posi_pk FROM territory_valley WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;

        $this->classLog();
        foreach($rows AS $v) {
            $this->PgGame->query('SELECT lord_pk, type, level FROM position WHERE posi_pk = $1', [$v['valley_posi_pk']]);
            if (! $this->PgGame->fetch()) {
                continue;
            }
            $r = $this->PgGame->row;
            $resource_types = null;
            switch ($r['type']) {
                case 'F': // 숲 - Forest
                case 'G': // 초원 - Grassland
                case 'L': // 저수지- Lake
                    $resource_types = ['food', 'horse', 'lumber'];
                    break;
                case 'M': // 광산 - Mine
                    $resource_types = ['iron'];
                    break;
                case 'R': // 농경지 - Ranch
                    $resource_types = ['food'];
                    break;
                default:
                    break;
            }
            if ($resource_types == null) {
                continue;
            }
            $md = &$_M['PROD_VALL'][$r['type']][$r['level']];
            foreach ($resource_types AS $resource_type) {
                $this_productivity = $md[$resource_type];
                $update_types[$resource_type] += $this_productivity;

                // 자원지 상실 (음수 처리)
                /*if ($r['lord_pk'] != $this->Session->lord['lord_pk'])
                {
                    $this_productivity = $this_productivity*-1;
                }

                $col_productivity_valley = $resource_type. '_production_valley';

                $query_params = Array($_posi_pk, $this_productivity);
                $this->Db->query("UPDATE production SET $col_productivity_valley = $col_productivity_valley + (\$2), last_update_dt = now() WHERE posi_pk = \$1", $query_params);

                // log
                $this->Log->setProduction(null, $_posi_pk, $resource_type. '_production_valley['.$this_productivity.'];');

                // 효과를 포함한 최종 Production 재계산
                $this->final_production($_posi_pk, $resource_type);*/
            }
        }

        foreach ($update_types AS $k => $v) {
            $col_productivity_valley = $k. '_production_valley';
            $this->PgGame->query("UPDATE production SET $col_productivity_valley = $2, last_update_dt = now() WHERE posi_pk = \$1", [$_posi_pk, $v]);

            // 갱신
            $this->updateProduction($_posi_pk, [$col_productivity_valley => $v]);
            // log
            $this->Log->setProduction(null, $_posi_pk, $k. '_production_valley['.$v.'];');

            $this->finalProduction($_posi_pk, $k);
        }

        return true;
    }

    function updateProduction($_posi_pk, $_arr_production): void
    {
        // 갱신
        $this->Session->sqAppend('PROD', $_arr_production, null, $this->Session->lord['lord_pk'], $_posi_pk);
    }

    // do_get = 바로 get을 할지 여부, CEffect에서 false로 온다.
    function finalProduction($_posi_pk, $_resource_type, $_do_get = true): true
    {
        $query_string = 'UPDATE resource SET %s_production = (SELECT %s_providence+%s_production_territory+%s_production_valley+((%s_providence+%s_production_territory+%s_production_valley)*(%s_pct_plus_tech+%s_pct_plus_hero_assign+%s_pct_plus_hero_skill+%s_pct_plus_item)*0.01) FROM production WHERE posi_pk = $1) WHERE posi_pk = $1';
        $query_string = sprintf($query_string, $_resource_type, $_resource_type, $_resource_type, $_resource_type, $_resource_type, $_resource_type, $_resource_type, $_resource_type, $_resource_type, $_resource_type, $_resource_type);
        $this->PgGame->query($query_string, [$_posi_pk]);
        if ($_do_get) {
            $this->Resource->get($_posi_pk);
        }
        return true;
    }

    // 자원 최대치 territory.storage_*_pct (창고 최대치 재계산 후에는 반드시 수행함)
    function resourceMax($_posi_pk): true
    {
        $this->PgGame->query('SELECT storage_max, storage_food_pct, storage_horse_pct, storage_lumber_pct, storage_iron_pct FROM territory WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $r = &$this->PgGame->row;

        $food_max = intval($r['storage_max']*$r['storage_food_pct']*0.01);
        $horse_max = intval($r['storage_max']*$r['storage_horse_pct']*0.01);
        $lumber_max = intval($r['storage_max']*$r['storage_lumber_pct']*0.01);
        $iron_max = intval($r['storage_max']*$r['storage_iron_pct']*0.01);
        $this->PgGame->query('UPDATE resource SET food_max = $1, horse_max= $2, lumber_max = $3, iron_max = $4 WHERE posi_pk = $5', [$food_max, $horse_max, $lumber_max, $iron_max, $_posi_pk]);

        $this->Resource->get($_posi_pk);
        return true;
    }

    // 창고 최대치
    function storageMax($_posi_pk): true
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['building', 'providence']);

        $max = 0;
        $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_STORAGE]);
        $this->PgGame->fetchAll();
        foreach ($this->PgGame->rows as $row) {
            if ($row['level'] > 0) {
                $max += $_M['BUIL'][PK_BUILDING_STORAGE]['level'][$row['level']]['variation_1'];
            }
        }

        $max += $_M['PROV']['storage_max'];

        $this->classEffect();
        $ret = $this->Effect->getEffectedValue($_posi_pk, ['storage_max_increase'], $max);
        $max = $ret['value'];

        // DB 업데이트
        $this->PgGame->query('UPDATE territory SET storage_max = $1 WHERE posi_pk = $2', [$max, $_posi_pk]);
        $this->Session->sqAppend('TERR', ['storage_max' => $max], null, $this->Session->lord['lord_pk'], $_posi_pk);

        // 자원별 최대 저장량
        $this->resourceMax($_posi_pk);

        return true;
    }

    // 성벽 최대치 (방어도 및 방어시설공간)
    function wallMax($_posi_pk): true
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['building']);

        $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_WALL]);
        $lev = $this->PgGame->fetchOne();

        $md = $_M['BUIL'][PK_BUILDING_WALL]['level'][$lev];

        $wall_vacancy_max = $md['variation_2'];
        $wall_shield_max = $md['variation_1'];

        // DB 업데이트
        $this->PgGame->query('UPDATE territory SET wall_vacancy_max = $1, wall_shield_max = $2 WHERE posi_pk = $3', [$wall_vacancy_max, $wall_shield_max, $_posi_pk]);

        // position last_levelup_dt, last_update_dt, level(성벽) 업데이트
        $this->PgGame->query('UPDATE position SET level = $2, last_levelup_dt = now(), last_update_dt = now() WHERE posi_pk = $1', [$_posi_pk, $lev]);

        $this->Session->sqAppend('TERR', ['wall_vacancy_max' => $wall_vacancy_max, 'wall_shield_max' => $wall_shield_max], null, $this->Session->lord['lord_pk'], $_posi_pk);
        return true;
    }

    // 현재 방어시설 territory.wall_vacancy_curr
    function wallFort($_posi_pk): true
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['fortification']);
        $this->PgGame->query('SELECT * FROM fortification WHERE posi_pk = $1', [$_posi_pk]);
        $fort = 0;
        if ($this->PgGame->fetch()) {
            $r = &$this->PgGame->row;
            foreach ($_M['FORT_C'] AS $k => $v) {
                if ($r[$k] > 0) {
                    $fort += $r[$k]*$v['need_vacancy'];
                }
            }
        }

        // 생산 중인 방어시설도 포함
        $this->PgGame->query('SELECT (t2.build_number * t3.need_vacancy) AS p
FROM build t1, build_fortification t2, m_fortification t3
WHERE t1.type = \'F\' AND t1.buil_pk = t2.buil_pk AND t2.status = \'P\' AND t2.m_fort_pk = t3.m_fort_pk AND t1.posi_pk = $1', [$_posi_pk]);
        if ($this->PgGame->fetch()) {
            $fort += $this->PgGame->row['p'];
        }

        // DB 업데이트
        $this->PgGame->query('UPDATE territory SET wall_vacancy_curr = $1 WHERE posi_pk = $2', [$fort, $_posi_pk]);
        $this->PgGame->query('SELECT lord_pk FROM position WHERE type = $1 AND posi_pk = $2', ['T', $_posi_pk]);
        if ($this->PgGame->fetch()){
            $this->Session->sqAppend('TERR', ['wall_vacancy_curr' => $fort], null, $this->PgGame->row['lord_pk'], $_posi_pk);
        }

        return true;
    }

    function buildArmy($_posi_pk, $_add_data): true
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['building']);

        if($_add_data['status'] == 'U') {
            $level = $_add_data['level'] + 1;
        } else if($_add_data['status'] == 'D') {
            $level = $_add_data['level'] - 1;
        } else {
            $level = $_add_data['level'];
        }
        if ($level < 1) {
            return true;
        }
        $queue_max = $_M['BUIL'][PK_BUILDING_ARMY]['level'][$level]['variation_1'] - 1;
        if (!$queue_max) {
            // 예외 처리용 코드, 실제적으로 동작하면 안되는 부분 (상단 level에서 return 하기 때문)
            $queue_max = 0;
        }
        $this->PgGame->query('UPDATE build SET queue_max = $1 WHERE posi_pk = $2 and in_cast_pk = $3', [$queue_max, $_posi_pk, $_add_data['in_castle_pk']]);
        return true;
    }

    function buildMedical($_posi_pk, $_add_data): true
    {
        // global $_M, $NsGlobal;
        // $NsGlobal->requireMasterData(['building']);

        if($_add_data['status'] == 'U') {
            $level = $_add_data['level'] + 1;
        } else if($_add_data['status'] == 'D') {
            $level = $_add_data['level'] - 1;
        } else {
            $level = $_add_data['level'];
        }
        if ($level < 1) {
            return true;
        }
        $queue_max = $level - 1; // 의료원은 variation_1 을 사망자 치료 %로 사용하므로
        if (!$queue_max) {
            // 예외 처리용 코드, 실제적으로 동작하면 안되는 부분 (상단 level에서 return 하기 때문)
            $queue_max = 0;
        }
        $this->PgGame->query('UPDATE build SET queue_max = $1 WHERE posi_pk = $2 and in_cast_pk = $3', [$queue_max, $_posi_pk, $_add_data['in_castle_pk']]);
        return true;
    }

    /*
     * 자원자 방어시설 추가
     */
    /*function world_fort($_posi_pk)
    {
        global $_M;

        require_once_caches(Array('fortification'));

        return true;
    }*/


}