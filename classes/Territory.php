<?php

class Territory
{
    protected Session $Session;
    protected Pg $PgGame;

    protected Resource $Resource;
    protected GoldPop $GoldPop;
    protected FigureReCalc $FigureReCalc;
    protected Effect $Effect;
    protected Bdic $Bdic;
    protected Bdoc $Bdoc;
    protected Hero $Hero;
    protected Lord $Lord;
    protected Army $Army;
    protected Technique $Technique;
    protected Item $Item;
    protected Medical $Medical;
    protected Fortification $Fortification;
    protected Troop $Troop;
    protected Timer $Timer;
    protected Letter $Letter;
    protected Power $Power;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    function classTechnique(): void
    {
        if (! isset($this->Technique)) {
            $this->Technique = new Technique($this->Session, $this->PgGame);
        }
    }

    function classLord(): void
    {
        if (! isset($this->Lord)) {
            $this->Lord = new Lord($this->Session, $this->PgGame);
        }
    }

    function classArmy(): void
    {
        if (! isset($this->Army)) {
            $this->classResource();
            $this->classGoldPop();
            $this->Army = new Army($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classHero(): void
    {
        if (! isset($this->Hero)) {
            $this->Hero = new Hero($this->Session, $this->PgGame);
        }
    }

    function classBdic(): void
    {
        if (! isset($this->Bdic)) {
            $this->classResource();
            $this->classGoldPop();
            $this->Bdic = new Bdic($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classBdoc(): void
    {
        if (! isset($this->Bdoc)) {
            $this->classResource();
            $this->classGoldPop();
            $this->Bdoc = new Bdoc($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classItem(): void
    {
        if (! isset($this->Item)) {
            $this->Item = new Item($this->Session, $this->PgGame);
        }
    }

    function classMedical(): void
    {
        if (! isset($this->Medical)) {
            $this->Medical = new Medical($this->Session, $this->PgGame);
        }
    }

    function classFortification(): void
    {
        if (! isset($this->Fortification)) {
            $this->Fortification = new Fortification($this->Session, $this->PgGame);
        }
    }

    function classTroop(): void
    {
        if (! isset($this->Troop)) {
            $this->Troop = new Troop($this->Session, $this->PgGame);
        }
    }

    function classTimer(): void
    {
        if (! isset($this->Timer)) {
            $this->Timer = new Timer($this->Session, $this->PgGame);
        }
    }

    function classLetter(): void
    {
        if (! isset($this->Letter)) {
            $this->Letter = new Letter($this->Session, $this->PgGame);
        }
    }

    function classPower(): void
    {
        if (! isset($this->Power)) {
            $this->Power = new Power($this->Session, $this->PgGame);
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
        if (! isset($this->FigureReCalc)) {
            $this->classResource();
            $this->classGoldPop();
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

    function classLog(): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function get($_posi_pk, $_col_arr = null, $_lord_pk = null): bool
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }
        if ($_col_arr){
            $cols= implode(',', $_col_arr);
        } else {
            $cols = 'flag, tax_rate, tax_rate_plus_tech, tax_rate_plus_hero_assign, tax_rate_plus_hero_skill, tax_rate_plus_item, tax_rate_plus_amount, loyalty, title, population_max, population_labor_force, population_army, population_upward_plus_tech, population_upward_plus_hero_assign, population_upward_plus_hero_skill, population_upward_plus_item, population_upward_plus_amount, storage_max, storage_food_pct, storage_horse_pct, storage_lumber_pct, storage_iron_pct, wall_vacancy_max, wall_vacancy_curr, status_gate, yn_alliance_camp';
            $cols .= ', wall_director_hero_pk, wall_staff_hero_pk, date_part(\'epoch\', comforting_up_dt)::integer as comforting_up_dt, date_part(\'epoch\', requisition_up_dt)::integer as requisition_up_dt, date_part(\'epoch\', title_change_up_dt)::integer as title_change_up_dt';
        }
        $this->PgGame->query("SELECT {$cols} FROM territory WHERE posi_pk = $1", [$_posi_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $r = &$this->PgGame->row;
        $this->Session->sqAppend('TERR', $r, null, $_lord_pk, $_posi_pk);
        return true;
    }

    function changeStoragePct($_posi_pk, $_food_pct, $_horse_pct, $_lumber_pct, $_iron_pct): true
    {
        $this->PgGame->query('UPDATE territory set storage_food_pct = $1, storage_horse_pct = $2, storage_lumber_pct = $3, storage_iron_pct = $4 WHERE posi_pk = $5', [$_food_pct, $_horse_pct, $_lumber_pct, $_iron_pct, $_posi_pk]);
        $this->Session->sqAppend('TERR', ['storage_food_pct' => $_food_pct, 'storage_lumber_pct' => $_lumber_pct, 'storage_horse_pct' => $_horse_pct, 'storage_iron_pct' => $_iron_pct], null, $this->Session->lord['lord_pk'], $_posi_pk);
        return true;
    }

    function useWallVacancy($_posi_pk, $_use_count): bool
    {
        $this->PgGame->query('SELECT wall_vacancy_curr, wall_vacancy_max FROM territory WHERE posi_pk = $1', [$_posi_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $r = $this->PgGame->row;
        if($r['wall_vacancy_max'] < $r['wall_vacancy_curr'] + $_use_count) {
            return false;
        } else if ($r['wall_vacancy_curr'] + $_use_count < 0) {
            // 감소 시에 감소되고 난 후가 0보다 작으면 불가
            return false;

        }
        $this->PgGame->query('UPDATE territory set wall_vacancy_curr = wall_vacancy_curr + $2 WHERE posi_pk = $1', [$_posi_pk, $_use_count]);
        $this->Session->sqAppend('TERR', ['wall_vacancy_curr' => $r['wall_vacancy_curr'] + $_use_count], null, $this->Session->lord['lord_pk'], $_posi_pk);
        return true;
    }

    function cancelUseWallVacancy($_posi_pk, $_use_count): false|array
    {
        $this->PgGame->query('SELECT wall_vacancy_curr FROM territory WHERE posi_pk = $1', [$_posi_pk]);
        $curr_count = $this->PgGame->fetchOne();
        if ($curr_count < 1) {
            return false;
        }
        $this->PgGame->query('UPDATE territory set wall_vacancy_curr = wall_vacancy_curr - $2 WHERE posi_pk = $1', [$_posi_pk, $_use_count]);
        $this->PgGame->query('SELECT wall_vacancy_curr FROM territory WHERE posi_pk = $1', [$_posi_pk]);
        if (!$this->PgGame->fetch()) {
            return false;
        }
        $_use_count = $this->PgGame->row['wall_vacancy_curr'];
        $this->Session->sqAppend('TERR', ['wall_vacancy_curr' => $_use_count], null, $this->Session->lord['lord_pk'], $_posi_pk);
        return ['curr_cnt' => $curr_count, 'update_cnt' => $_use_count];
    }

    // 군주 영지 점령
    function occupationLordTerritory($row): true
    {
        $this->classTechnique();
        $this->classLord();
        $this->classPower();

        // 확장 영지로 할당
        $this->PgGame->query('UPDATE position SET lord_pk = $1, last_update_dt = now() WHERE posi_pk = $2', [$row['src_lord_pk'], $row['dst_posi_pk']]);

        // 깃발명 변경
        $this->PgGame->query('UPDATE territory SET flag = a.flag FROM territory a WHERE territory.posi_pk = $1 AND a.posi_pk = $2', [$row['dst_posi_pk'], $row['src_posi_pk']]);

        // 영웅 배속
        $this->setAssignedLord($row);

        // 군주 태학 기술 레벨
        $prev_technique_power = $this->Power->getLordTechniquePower($row['src_lord_pk']);
        $this->Technique->initTechniqueLevel($row['dst_posi_pk']);
        $this->Technique->updateTerritoryTechnique($row['src_lord_pk'], $row['dst_posi_pk']);

        // 영향력(src_lord_pk증가, dst_lord_pk는 감소
        $change_power = $this->Power->getBuildingPower($row['dst_posi_pk']) + 500;
        $now_technique_power = $this->Power->getLordTechniquePower($row['src_lord_pk']);
        $change_power += $now_technique_power - $prev_technique_power;
        $this->Lord->increasePower($row['src_lord_pk'], $change_power);

        // 방랑 영주가 되는 경우 해당 territory의 power 컬럼을 업데이트하지 않은 상태로 여기로 넘어오게되는 문제 때문에 직접 업데이트함
        $this->PgGame->query('UPDATE territory SET power = $1 WHERE posi_pk = $2', [$change_power, $row['dst_posi_pk']]);

        // production 초기화
        $this->classResource();
        $this->classGoldPop();
        $this->classFigureReCalc();
        $this->classEffect();

        $this->Effect->initEffects();

        // 버프 아이템 적용
        $this->PgGame->query('SELECT m_item_pk, buff_time, start_dt, end_dt, date_part(\'epoch\', end_dt)::integer - date_part(\'epoch\', now())::integer as buff_time FROM territory_item_buff WHERE posi_pk = $1', [$row['src_posi_pk']]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        $this->setEffectBuffItem($rows, $row['src_lord_pk'], $row['dst_posi_pk']);

        $this->Effect->setUpdateEffectTypesResource($row['dst_posi_pk'], ['food', 'horse', 'lumber', 'iron']);
        $this->Effect->setUpdateEffectTypesPopulationUpward($row['dst_posi_pk']);
        $this->Effect->setUpdateEffectTypesTaxRateBonus($row['dst_posi_pk']);
        $this->Effect->setUpdateEffectTypesStorageMax($row['dst_posi_pk']);

        // 영지 효과 재계산
        $this->setFigureReCalc($row['dst_posi_pk']);

        $this->PgGame->query('UPDATE lord SET position_cnt = position_cnt + 1 WHERE lord_pk = $1', [$row['src_lord_pk']]);
        $this->Session->setLoginReload();

        // 점령 후 보호
        $this->setTruceStatus($row['dst_posi_pk'], 'O', 500107);

        // Log
        $this->classLog();
        $this->Log->setTerritory($row['src_lord_pk'], $row['dst_posi_pk'], 'occupation_lord_Territory', 'increase_power['.$change_power.'];dst_lord_pk['.$row['dst_lord_pk'].'];');

        return true;
    }

    // 군주 영지 상실
    function lossOwnershipLordTerritory($_lord_pk, $_posi_pk, $_aggression_lord_pk = null): true
    {
        $this->classArmy();
        $this->classHero();
        $this->classMedical();
        $this->classFortification();
        $this->classTroop();
        $this->classLord();
        $this->classPower();

        // 잔존병력
        $this->Army->deadAllArmy($_posi_pk);

        //수성병기 전체 사망처리
        $this->Fortification->demolishAllFortification($_posi_pk);

        // 모든 외부자원지 상실
        $this->Troop->lossOwnershipAllValley($_lord_pk, $_posi_pk);

        // 요충지 상실
        $this->PgGame->query('SELECT posi_pk FROM position WHERE lord_pk = $1 AND type = $2', [$_lord_pk, 'P']);
        $this->PgGame->fetchAll();
        $point_rows = $this->PgGame->rows;
        if ($point_rows) {
            foreach ($point_rows AS $point_posi_pk) {
                // TODO foreach 안에서 쿼리문 돌리는게 맞나?
                $this->PgGame->query('SELECT dst_posi_pk FROM troop WHERE src_lord_pk = $1 AND src_posi_pk = $2 AND dst_posi_pk = $3 AND status = $4', [$_lord_pk, $_posi_pk, $point_posi_pk['posi_pk'], 'C']);
                if ($this->PgGame->fetchOne()) {
                    $this->Troop->lossOwnershipPoint($_lord_pk, $point_posi_pk['posi_pk']);
                    $this->Troop->setNpcPoint($point_posi_pk['posi_pk']);
                }
            }
        }

        // 배속된 영웅 정보 삭제
        $this->PgGame->query('UPDATE building_in_castle SET assign_hero_pk = null WHERE posi_pk = $1', [$_posi_pk]);
        // 배속된 영웅 효과 삭제
        $this->PgGame->query('DELETE FROM territory_hero_assign WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->query('DELETE FROM territory_hero_skill WHERE posi_pk = $1', [$_posi_pk]);

        // 성벽에 배속된 부장과 참모
        $this->PgGame->query('UPDATE territory SET wall_director_hero_pk = null, wall_staff_hero_pk = null, power = 0 WHERE posi_pk = $1', [$_posi_pk]);

        // 메인영지일 경우 타 영지를 메인으로 설정
        $main_posi_pk = $this->Lord->updateMainPosiPk($_lord_pk, $_posi_pk);

        // 부상병 삭제, 영웅치료(방랑영주일 때는 다 치료해줌.)
        $this->Medical->doDemolish($_posi_pk);
        //$this->Medical->deadAllInjuryArmy($_posi_pk);
        $this->Medical->doMoveMedicalHero($_posi_pk, $_lord_pk, $main_posi_pk);

        // 탐색 기록 삭제. (탐색 중 영지를 뺏기면 영지를 가진 군주가 영지 포기할 시 탐색 기록 때문에 포기를 하지 못하는 문제가 있어서 추가.)
        $this->PgGame->query('DELETE FROM hero_encounter WHERE posi_pk = $1', [$_posi_pk]);

        // 건물 건설한 영향력
        $change_power = $this->Power->getBuildingPower($_posi_pk);
        //$change_power += $this->Power->getTechniquePower($_posi_pk);

        if ($main_posi_pk) {
            // 동맹 주둔 부대 자동 복귀(목적지 같은거 찾고, cmd_type가 'R'인것
            $this->Troop->RecallReinfArmy($_posi_pk, $main_posi_pk);

            // 이동중인 부대(출정, 회군, 취소)
            $this->Troop->setAllTroopStatusWithdrawal($_posi_pk, $main_posi_pk);

            // 다른곳에 주둔하고 있는 부대
            $this->Troop->setAllTroopStatusRecall($_posi_pk, $main_posi_pk);

            // 영웅들 영지 옮기고, 상태 'I'로...
            $this->Hero->setMoveHero($_posi_pk, $main_posi_pk);

            // 영향력 감소
            $this->Lord->decreasePower($_lord_pk, $change_power + 500);

            //메인 영지로 리플래쉬
            $this->Session->sqAppend('PUSH', ['LOSS_OWNERSHIP_LORD_TERRITORY' => $_posi_pk . ':' . $main_posi_pk], null, $_lord_pk);
        } else {
            // -> 설정 불가시 (방랑영주) => troop 테이블 만 삭제하면 됨.
            // 외부 주둔/출병/복귀 부대 전체 삭제 (자원 + 병력 상실, 영웅은 "해임->미등용" 으로 동일하게 처리됨.
            // 전체 영웅 해임 (재등용 쿨타임 미적용 해야함.)
            $this->Troop->removeAllTroop($_lord_pk);
            $this->Hero->setDismissAllHero($_lord_pk);

            // lord 테이블 update
            $this->PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$_aggression_lord_pk]);
            $_aggression_lord_name = $this->PgGame->fetchOne();

            $this->PgGame->query('SELECT title FROM territory WHERE posi_pk = $1', [$_posi_pk]);
            $_last_territory_name = $this->PgGame->fetchOne();
            $_last_territory_name = $_last_territory_name . ' (' . $_posi_pk . ')';

            // 태학 개발 영향력은 있어야 함.
            $change_power += $this->Power->getTechniquePower($_lord_pk);
            $this->PgGame->query('UPDATE lord SET power = $4, roamer_last_my_territory = $1, roamer_aggression_dt = now(), roamer_aggression_lord = $2 WHERE lord_pk = $3', [$_last_territory_name, $_aggression_lord_name, $_lord_pk, $change_power]);

            // 게임
            $this->Session->sqAppend('PUSH', ['LOSS_OWNERSHIP_LORD_ALL_TERRITORY' => $_posi_pk], null, $_lord_pk);

        }

        // production 초기화
        $this->classResource();
        $this->classGoldPop();
        $this->classFigureReCalc();
        $this->classEffect();

        $this->Effect->initEffects();

        $this->Effect->setUpdateEffectTypesResource($_posi_pk, ['food', 'horse', 'lumber', 'iron']);
        $this->Effect->setUpdateEffectTypesPopulationUpward($_posi_pk);
        $this->Effect->setUpdateEffectTypesTaxRateBonus($_posi_pk);
        $this->Effect->setUpdateEffectTypesStorageMax($_posi_pk);

        // Log
        $this->classLog();
        $this->Log->setTerritory($_lord_pk, $_posi_pk, 'loss_Territory', 'aggression['.$_aggression_lord_pk.'];aggression_lord_name['.$_aggression_lord_name.'];decrease_power['.($change_power + 500). '];');

        return true;
    }

    // 성주 할당
    function setAssignedLord($row): true
    {
        $this->classHero();
        $this->classBdic();

        // 인솔 영웅들 임무 해제
        $this->Hero->unsetCommand($row['captain_hero_pk']);
        if ($row['director_hero_pk'])
            $this->Hero->unsetCommand($row['director_hero_pk']);
        if ($row['staff_hero_pk'])
            $this->Hero->unsetCommand($row['staff_hero_pk']);
        // 부대를 편입 - 영웅 (성주, 기타)
        $this->Hero->setTerritory($row['captain_hero_pk'], $row['dst_posi_pk']);
        if ($row['director_hero_pk']) {
            $this->Hero->setTerritory($row['director_hero_pk'], $row['dst_posi_pk']);
        }
        if ($row['staff_hero_pk']) {
            $this->Hero->setTerritory($row['staff_hero_pk'], $row['dst_posi_pk']);
        }
        // 성주 할당
        $this->Hero->setCommand($row['dst_posi_pk'], $row['captain_hero_pk'], 'A');
        $this->Bdic->heroAssign($row['dst_posi_pk'], 1, $row['captain_hero_pk']);

        return true;
    }

    function appendResourceTerritory($_lord_pk, $reso, $_posi_pk, $gold): true
    {
        $this->classResource();
        $this->classGoldPop();

        if ($reso['food'] > 0 || $reso['horse'] > 0 || $reso['lumber'] > 0 || $reso['iron'] > 0) {
            $r = $this->Resource->increase($_posi_pk, $reso, $_lord_pk, 'terr_occupation');
            if (!$r) {
                // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '영지 점령시 자원 편입 오류');
            }
        }

        if ($gold > 0) {
            $r = $this->GoldPop->increaseGold($_posi_pk, $gold, $_lord_pk, 'terr_occupation');
            if (!$r) {
                // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '영지 점령시 황금 편입 오류');
            }
        }

        return true;
    }

    function appendArmyTerritory($_posi_pk, $att_data): true
    {
        $this->classArmy();

        $army_arr = [];
        foreach ($att_data AS $k => $v) {
            if ($v['unit_remain'] > 0) {
                $army_arr[$k] = $v['unit_remain'];
            }
        }
        $this->Army->returnArmy($_posi_pk, $army_arr);
        return true;
    }

    // NPC 영지 점령 후 레벨대별 기본 영지 건설
    function occupationNpcTerritory($_lord_pk, $_posi_pk, $_npc_level, $_flag): true
    {
        // 건물 건설
        $this->PgGame->query('SELECT occupationnpcterritory('. $_lord_pk .', \''. $_posi_pk . '\',' . $_npc_level . ', \'' . $_flag .'\')');


        // 군주 태학 기술 레벨
        $this->classTechnique();
        $this->Technique->initTechniqueLevel($_posi_pk);
        $this->Technique->updateTerritoryTechnique($_lord_pk, $_posi_pk);

        // 건물 영향력
        $this->classPower();
        $increasePower = $this->Power->getBuildingPower($_posi_pk);
        // 태학 영향력
        //$increasePower += $this->Power->getTechniquePower($_posi_pk);
        // 영지
        $increasePower += 500;

        // 영향력 감소
        $this->classLord();
        $this->Lord->increasePower($_lord_pk, $increasePower, $_posi_pk);

        // production 초기화
        $this->classResource();
        $this->classGoldPop();
        $this->classFigureReCalc();
        $this->classEffect();

        $this->Effect->initEffects();

        $this->Effect->setUpdateEffectTypesResource($_posi_pk, ['food', 'horse', 'lumber', 'iron']);
        $this->Effect->setUpdateEffectTypesPopulationUpward($_posi_pk);
        $this->Effect->setUpdateEffectTypesTaxRateBonus($_posi_pk);
        $this->Effect->setUpdateEffectTypesStorageMax($_posi_pk);

        // 영지 효과 재계산
        $this->setFigureReCalc($_posi_pk);

        // npc 영지 삭제
        $this->PgGame->query('DELETE FROM position_npc WHERE posi_pk = $1', [$_posi_pk]);

        // 데이터 다시 가져오기
        $this->Session->setLoginReload();

        // 점령 후 보호
        $this->setTruceStatus($_posi_pk, 'O', 500107);

        // 버프 아이템 적용
        $this->PgGame->query('SELECT m_item_pk, buff_time, start_dt, end_dt, date_part(\'epoch\', end_dt)::integer - date_part(\'epoch\', now())::integer as buff_time FROM territory_item_buff WHERE posi_pk = (SELECT main_posi_pk FROM lord WHERE lord_pk = $1)', [$_lord_pk]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        $this->setEffectBuffItem($rows, $_lord_pk, $_posi_pk);

        // Log
        $this->classLog();
        $this->Log->setTerritory($_lord_pk, $_posi_pk, 'occupation_npc_Territory', 'increase_power['.$increasePower.'];');

        return true;
    }

    function setEffectBuffItem($_rows, $_lord_pk, $_posi_pk): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item']);

        $this->classEffect();
        $this->classTimer();
        $this->classLog();

        $effects_for_update = [];

        foreach ($_rows AS $v) {
            $this->PgGame->query('INSERT INTO territory_item_buff (posi_pk, m_item_pk, buff_time, start_dt, end_dt) VALUES ($1, $2, $3, $4, $5)', [$_posi_pk, $v['m_item_pk'], $v['buff_time'], $v['start_dt'], $v['end_dt']]);
            $effects_for_update[] = $v['m_item_pk'];
            $terr_item_buff_pk = $this->PgGame->currSeq('territory_item_buff_terr_item_buff_pk_seq');

            $this->Timer->set($_posi_pk, 'B', $terr_item_buff_pk, 'B', ($v['m_item_pk']. ':'. $_M['ITEM'][$v['m_item_pk']]['use_type']), $v['buff_time']);

            if ($v['m_item_pk'] == BUILD_QUEUE_INCREASE_ITEM || $v['m_item_pk'] == BUILD_QUEUE2_INCREASE_ITEM) {
                $this->classItem();
                $this->Item->useIncreaseBuildQueue($_posi_pk, $v['m_item_pk']);
            }
            $this->Log->setBuff($_lord_pk, $_posi_pk, $terr_item_buff_pk, $v['m_item_pk'], 'P', $v['buff_time']);
        }

        if ($effects_for_update) {
            $effect_types = $this->Effect->getEffectTypes($effects_for_update);
            if (COUNT($effect_types) > 0) {
                $this->Effect->setUpdateEffectTypes($_posi_pk, $effect_types);
            }
        }
    }

    // 영지 포기 TODO 영지 확장이 제거되어 포기 기능은 더이상 사용안함. 번역제외
    function giveUpTerritory($_lord_pk, $_posi_pk): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['npc_territory', 'npc_hero', 'technique', 'army', 'fortification']);

        $this->PgGame->query('SELECT src_posi_pk FROM troop WHERE src_posi_pk = $1', [$_posi_pk]);
        if ($this->PgGame->fetch()) {
            $NsGlobal->setErrorMessage('이동 중인 부대가 있으므로 영지를 포기 할 수 없습니다.');
            return false;
        }

        $this->PgGame->query('SELECT hero_pk FROM my_hero WHERE posi_pk = $1 AND status_cmd = $2', [$_posi_pk, 'P']);
        if ($this->PgGame->fetchOne()) {
            $NsGlobal->setErrorMessage('영웅이 강화중이므로 영지를 포기 할 수 없습니다.');
            return false;
        }

        $this->PgGame->query('SELECT hero_invi_pk FROM hero_invitation WHERE hero_invi_pk IN (SELECT hero_invi_pk FROM hero_encounter WHERE posi_pk = $1)', [$_posi_pk]);
        if ($this->PgGame->fetchOne()) {
            $NsGlobal->setErrorMessage('초빙 중에는 영지를 포기 할 수 없습니다.');
            return false;
        }

        $this->PgGame->query('SELECT hero_pk FROM my_hero WHERE posi_pk = $1 AND yn_lord = $2', [$_posi_pk, 'Y']);
        if ($this->PgGame->fetchOne()) {
            $NsGlobal->setErrorMessage('군주가 있는 영지는 포기 할 수 없습니다.');
            return false;
        }

        //부상 중인 영웅에 대한 처리
        //$this->getMedical();
        //$this->Medical->doMoveMedicalHero($_posi_pk, $_lord_pk, $this->Session->lord['main_posi_pk']);
        /*
         * 차감할 영향력 계산
         * 1. 영지 포기에 따른 영지획득시 받은 영향력 감소
         * 2. 건물에 따른 영향력
         * 영지 포기 프로시저 성공 후 영향력 차감.
         */
        $this->classPower();
        // 건물 영향력
        $decreasePower = $this->Power->getBuildingPower($_posi_pk);

        // 태학 영향력
        //$decreasePower += $this->Power->getTechniquePower($_posi_pk);

        // 영지 포기
        $decreasePower += 500;

        // 내성 정보
        $in_castle_info = '';
        $this->PgGame->query('SELECT in_castle_pk, m_buil_pk, level FROM building_in_castle WHERE posi_pk = $1 ORDER BY in_castle_pk', [$_posi_pk]);
        while($this->PgGame->fetch()) {
            $in_castle_info .= '[' .$this->PgGame->row['in_castle_pk'] . ';' . $this->PgGame->row['m_buil_pk'] . ';' . $this->PgGame->row['level'] . ';];';
        }

        // 외성 정보
        $out_castle_info = '';
        $this->PgGame->query('SELECT out_castle_pk, m_buil_pk, level FROM building_out_castle WHERE posi_pk = $1 ORDER BY out_castle_pk', [$_posi_pk]);
        while($this->PgGame->fetch()) {
            $out_castle_info .= '[' .$this->PgGame->row['out_castle_pk'] . ';' . $this->PgGame->row['m_buil_pk'] . ';' . $this->PgGame->row['level'] . ';];';
        }

        // 병력 정보
        $this->PgGame->query('SELECT * FROM army WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        $army_info = '';
        foreach ($_M['ARMY_C'] AS $k => $v) {
            $army_info .= '[' . $k . ':' . $row[$k] .';];';
        }

        // 방어시설 정보
        $this->PgGame->query('SELECT * FROM fortification WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        $fort_info = '';
        foreach ($_M['FORT_C'] AS $k => $v) {
            $fort_info .= '[' . $k . ':' . $row[$k] .';];';
        }

        // 자원
        $reso_info = '';
        $this->PgGame->query('SELECT food_curr, horse_curr, lumber_curr, iron_curr FROM resource WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $reso_info .= $this->PgGame->row['food_curr'] .';'.$this->PgGame->row['horse_curr'] .';'.$this->PgGame->row['lumber_curr'] .';'.$this->PgGame->row['iron_curr'] .';';
        //황금
        $this->PgGame->query('SELECT gold_curr FROM gold WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $reso_info .= $this->PgGame->row['gold_curr'];

        // 현재 나에게 공격오고 있는 부대 타이머 삭제
        $this->PgGame->query('SELECT troo_pk FROM troop WHERE dst_posi_pk = $1 AND dst_time_pk IS NOT NULL', [$_posi_pk]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        if ($rows) {
            $this->classTroop();
            foreach($rows AS $k => $v) {
                $this->Troop->setStatusRecall($v['troo_pk'], null, null, true);
                $this->PgGame->query('UPDATE troop SET dst_time_pk = null WHERE troo_pk = $1', [$v['troo_pk']]);
            }
        }

        // 현재 개발중인 군주 태학 정보 저장 후, 영지포기 성공했을 경우 군주태학 리셋
        // 영지 포기전에  현재 진행중인해당 pk를 저장해야함. 영지포기후 군주태학 상태 리셋하기
        $this->PgGame->query('SELECT m_tech_pk FROM build_technique WHERE buil_pk = (SELECT buil_pk FROM build WHERE posi_pk = $1 AND type = $2) AND status = $3', [$_posi_pk, 'T', 'P']);
        $m_tehc_pk = $this->PgGame->fetchOne();

        //영지 포기 처리 전 부상 타이머가 존재한고 메인영지에 의료원이 존재한다면 메인 영지로 변경.
        $this->PgGame->query('SELECT m_buil_pk FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$this->Session->lord['main_posi_pk'], 200700]);
        $medi_build_use = $this->PgGame->fetchAll();
        if($medi_build_use > 0) {
            $this->PgGame->query('SELECT time_pk FROM timer WHERE posi_pk = $1 AND queue_type = $2 AND queue_action = $3', [$_posi_pk, 'M', 'M']);
            $this->PgGame->fetchAll();
            $rows = $this->PgGame->rows;
            if ($rows) {
                foreach($rows AS $k => $v) {
                    $this->PgGame->query('UPDATE timer SET posi_pk = $1 WHERE time_pk = $2', [$this->Session->lord['main_posi_pk'], $v['time_pk']]);
                }
            }
        }

        $this->PgGame->query('SELECT c.type, c.level FROM position a, m_position b, m_position_area c WHERE a.posi_pk = $1 AND a.posi_pk = b.m_posi_pk AND b.m_posi_area_pk = c.m_posi_area_pk', [$_posi_pk]);
        $this->PgGame->fetch();
        $type = $this->PgGame->row['type'];
        $level_default = (! $this->PgGame->row['level']) ? 1 : $this->PgGame->row['level'];

        $this->PgGame->query('SELECT giveupterritory('. $_lord_pk .', \''. $_posi_pk . '\', \''. $type . '\')');
        if ($this->PgGame->fetchOne() != 1) {
            $NsGlobal->setErrorMessage('영지 포기 실패하였습니다.');
            return false;
        }

        //해당 영지에 부상 중인 영웅이 있을 경우 메인 영지로 이동되었을 테니 여부를 판단하여 해당 영웅을 부상 상태로 만들어줌.
        $this->PgGame->query('SELECT medi_hero_pk, hero_pk FROM medical_hero WHERE posi_pk = $1', [$this->Session->lord['main_posi_pk']]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        if($medi_build_use  < 1) {
            if ($rows) {
                foreach($rows AS $k => $v) {
                    //메인영지에 의료원이 없을 경우 치료 중단.
                    $this->PgGame->query('UPDATE medical_hero SET status = $1, time_pk = $2, end_dt = $3 WHERE medi_hero_pk = $4', ['W', NULL, NULL, $v['medi_hero_pk']]);
                }
            }
        }
        if ($rows) {
            foreach($rows AS $k => $v) {
                $this->PgGame->query('UPDATE my_hero SET status_cmd = $1 WHERE hero_pk = $2', ['T', $v['hero_pk']]);
            }
        }

        // 군주태학 리셋
        if ($m_tehc_pk) {
            $this->PgGame->query('UPDATE lord_technique SET '. $_M['TECH'][$m_tehc_pk]['code']. '_status = $1 WHERE lord_pk = $2', [null, $_lord_pk]);
        }

        // 영향력 감소
        $this->classLord();
        $this->Lord->decreasePower($_lord_pk, $decreasePower);

        // 선전포고 삭제
        //$this->cancelOccupationInform($_posi_pk, $_lord_pk);

        // position_npc 생성(원래 타입으로 변경)
        if ($type == 'N') {
            $m_npc_terr =& $_M['NPC_TERR'][$level_default];
            $m_npc_hero =& $_M['NPC_HERO']['territory'][$level_default];

            // 황건적 장수 선택
            $z_arr = [];
            shuffle($m_npc_hero);
            $z_arr[] = $m_npc_hero[0];
            $z_arr[] = $m_npc_hero[1];
            $z_arr[] = $m_npc_hero[2];

            $this->PgGame->query('SELECT hero_pk, mil_force_basic+mil_force_enchant+mil_force_plusstat AS mil_force FROM hero WHERE hero_pk = ANY ($1) ORDER BY mil_force DESC', ['{'. implode(',', $z_arr). '}']);

            $z_arr = [];
            while ($this->PgGame->fetch()) {
                $z_arr[] = $this->PgGame->row['hero_pk'];
            }

            $captain_hero_pk = $z_arr[0];
            $director_hero_pk = $z_arr[1];
            $staff_hero_pk = $z_arr[2];

            $this->PgGame->query('INSERT INTO position_npc
(posi_pk, status, captain_hero_pk, director_hero_pk, staff_hero_pk,
 reso_gold, reso_food, reso_horse, reso_lumber, reso_iron,
 army_worker, army_infantry, army_pikeman, army_scout, army_spearman,
 army_armed_infantry, army_archer, army_horseman, army_armed_horseman, army_transporter, army_bowman,
 army_battering_ram, army_catapult, army_adv_catapult,
 fort_trap, fort_abatis, fort_tower
) VALUES (
 $1, \'N\', $2, $3, $4,
 $5, $6, $7, $8, $9,
 $10, $11, $12, $13, $14,
 $15, $16, $17, $18, $19, $20,
 $21, $22, $23,
 $24, $25, $26
)', [$_posi_pk, $captain_hero_pk, $director_hero_pk, $staff_hero_pk,
                $m_npc_terr['reso_gold'], $m_npc_terr['reso_food'], $m_npc_terr['reso_horse'], $m_npc_terr['reso_lumber'], $m_npc_terr['reso_iron'],
                $m_npc_terr['army_worker'], $m_npc_terr['army_infantry'], $m_npc_terr['army_pikeman'], $m_npc_terr['army_scout'], $m_npc_terr['army_spearman'],
                $m_npc_terr['army_armed_infantry'], $m_npc_terr['army_archer'], $m_npc_terr['army_horseman'], $m_npc_terr['army_armed_horseman'], $m_npc_terr['army_transporter'], $m_npc_terr['army_bowman'],
                $m_npc_terr['army_battering_ram'], $m_npc_terr['army_catapult'], $m_npc_terr['army_adv_catapult'],
                $m_npc_terr['fort_trap'], $m_npc_terr['fort_abatis'], $m_npc_terr['fort_tower']]);
        } else if ($type == 'E') {
            $this->PgGame->query('SELECT posi_area_pk, posi_regi_pk FROM position_area WHERE posi_area_pk = (SELECT posi_area_pk FROM position WHERE posi_pk = $1)', [$_posi_pk]);
            $this->PgGame->fetch();
            $row = $this->PgGame->row;
            // area
            $this->PgGame->query('UPDATE position_area SET ru_curr = ru_curr - 1 WHERE posi_area_pk = $1', [$row['posi_area_pk']]);
            // region
            $this->PgGame->query('UPDATE position_region SET ru_curr = ru_curr - 1 WHERE posi_regi_pk = $1', [$row['posi_regi_pk']]);
            // state
            $this->PgGame->query('SELECT posi_stat_pk FROM position_region WHERE posi_regi_pk = $1', [$row['posi_regi_pk']]);
            $this->PgGame->query('UPDATE position_state SET ru_curr = ru_curr - 1 WHERE posi_stat_pk = $1', [$this->PgGame->fetchOne()]);
        }

        // position 상태 업데이트
        /* $sql = 'UPDATE position SET lord_pk = null, type = $1, level = $2, last_levelup_dt = now(), last_update_dt = now() WHERE posi_pk = $3';
        $query_params = Array($type, $level_default, $_posi_pk);
        $this->PgGame->query($sql, $query_params);		 */

        $description = 'posi_pk:'.$_posi_pk.';decrease_power:'.$decreasePower.';';

        // Log
        $this->classLog();
        $this->Log->setTerritory($_lord_pk, $_posi_pk, 'giveup_terr', $description, $in_castle_info, $out_castle_info, $army_info, $fort_info, $reso_info);

        return true;
    }

    // 영지 건설
    function createTerritory($_lord_pk, $_posi_pk, $_flag): bool
    {
        global $NsGlobal;
        // 기본영지 건설
        $this->PgGame->query(sprintf("SELECT createterritory(%s,'%s','%s','%s')", $_lord_pk, $_posi_pk, 'New Territory', $_flag)); // TODO 텍스트 변경 필요.
        if ($this->PgGame->fetchOne() == 0) {
            $NsGlobal->setErrorMessage('Error Occurred. [26001]'); // 영지 건설에 실패하였습니다.
            return false;
        }
        return true;
    }

    // 영지 효과 재계산
    function setFigureReCalc($_posi_pk): void
    {
        $this->classResource();
        $this->classGoldPop();
        $this->classFigureReCalc();

        //기초 데이터 update
        $this->FigureReCalc->dispatcher($_posi_pk, 'POP_MAX');
        $this->FigureReCalc->dispatcher($_posi_pk, 'CASTLE_RES_FOOD');
        $this->FigureReCalc->dispatcher($_posi_pk, 'CASTLE_RES_HORSE');
        $this->FigureReCalc->dispatcher($_posi_pk, 'CASTLE_RES_LUMBER');
        $this->FigureReCalc->dispatcher($_posi_pk, 'STORAGE_MAX');
        $this->FigureReCalc->dispatcher($_posi_pk, 'WALL_MAX');
        $this->FigureReCalc->dispatcher($_posi_pk, 'WALL_FORT');
    }

    // 영지내에서 동작하고 있는 타이머 취소
    function cancellation($_posi_pk, $_lord_pk = null): true
    {
        $this->classBdic();
        $this->classBdoc();
        $this->classTechnique();
        $this->classArmy();
        $this->classFortification();
        $this->classItem();
        $this->classHero();

        // 건설(내성, 외성)
        $this->Bdic->cancelBdic($_posi_pk);
        $this->Bdoc->cancelBdoc($_posi_pk);

        // 태학
        $this->Technique->cancelTechnique($_posi_pk, $_lord_pk);

        // 병력
        $this->Army->cancelArmy($_posi_pk);

        // 수성병기
        $this->Fortification->cancelFortification($_posi_pk);

        $this->PgGame->query('UPDATE build SET concurr_curr = 0, queue_curr = 0 WHERE posi_pk = $1', [$_posi_pk]);

        // 버프
        $this->Item->cancelBuffItem($_posi_pk);

        // 초빙
        $this->Hero->cancelInvitation($_posi_pk);

        // 탐색
        $this->Hero->cancelEncounter($_posi_pk);

        // 입찰
        $this->Hero->cancelBid($_posi_pk);

        // 무역소 (이거는 안하기로 한듯)
        return true;
    }

    function setTruceStatus($_posi_pk, $truce_type, $_m_item_pk): true
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item']);

        $time = $_M['ITEM'][$_m_item_pk]['buff_time'];

        // 기존 타이머 있다면 종료
        $this->classTimer();
        $this->finishTimer($_posi_pk);

        $_status_truce = 'Y';

        if ($_m_item_pk == 500015) {
            // 평화서약 사용시간 저장
            $this->PgGame->query('UPDATE lord SET truce_up_dt = now() WHERE lord_pk = $1', [$this->Session->lord['lord_pk']]);

            // 다른 영지에도 적용해줌
            $this->PgGame->query('SELECT posi_pk FROM territory WHERE posi_pk IN (SELECT posi_pk FROM position WHERE lord_pk = $1 AND type = $2) AND status_truce != $3', [$this->Session->lord['lord_pk'], 'T', 'Y']);
            $this->PgGame->fetchAll();
            $rows = $this->PgGame->rows;

            foreach($rows AS $k => $v) {
                $this->PgGame->query('UPDATE territory SET status_truce = $2, truce_up_dt = now(), truce_type = $3 WHERE posi_pk = $1', [$v['posi_pk'], $_status_truce, $truce_type]);

                $this->Timer->set($v['posi_pk'], 'D', $_m_item_pk, 'B', $_m_item_pk. ':truce', $time);

                $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE posi_pk = $1', [$v['posi_pk']]);
            }
        } else {
            $this->PgGame->query('UPDATE territory SET status_truce = $2, truce_up_dt = null, truce_type = $3 WHERE posi_pk = $1', [$_posi_pk, $_status_truce, $truce_type]);

            $this->Timer->set($_posi_pk, 'D', $_m_item_pk, 'B', $_m_item_pk. ':truce', $time);

            $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE posi_pk = $1', [$_posi_pk]);
        }

        $this->Session->sqAppend('PUSH', ['TRUCE_UPDATE' => ['status' => 'P']], null, $this->Session->lord['lord_pk'], $this->Session->lord['main_posi_pk']);

        return true;
    }

    function finishTruceStatus($_posi_pk, $_m_item_pk = null, $_lord_pk = null): true
    {
        if (!$_lord_pk) {
            $_lord_pk = $this->Session->lord['lord_pk'];
        }

        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['item']);

        $time = $_M['ITEM'][500015]['buff_time'];

        $this->finishTimer($_posi_pk);

        if ($_m_item_pk == 500015) {
            $this->PgGame->query('UPDATE territory SET status_truce = $2, truce_up_dt = now(), truce_type = null WHERE posi_pk = $1', [$_posi_pk, 'N']);
        } else {
            // 평화의 서약이 아닐 경우 다른 영지에 평화의 서약 시간이 남아있으면 평화의 서약 발동 해준다.
            $this->PgGame->query('SELECT date_part(\'epoch\', truce_up_dt)::integer as last_dt, truce_up_dt, date_part(\'epoch\', now())::integer as now_dt FROM territory WHERE posi_pk IN (SELECT posi_pk FROM position WHERE lord_pk = $1 AND type = $2) AND status_truce = $3 AND truce_type =  $4 LIMIT 1', [$_lord_pk, 'T', 'Y', 'I']);
            if ($this->PgGame->fetch()) {
                $truce_up_dt = $this->PgGame->row['truce_up_dt'];
                $time = ($this->PgGame->row['last_dt'] + $time) - $this->PgGame->row['now_dt'];

                $this->PgGame->query('UPDATE territory SET status_truce = $2, truce_up_dt = \''.$truce_up_dt .'\', truce_type = $2 WHERE posi_pk = $1', [$_posi_pk, 'I']);

                $this->classTimer();
                $this->Timer->set($_posi_pk, 'D', $_m_item_pk, 'B', '500015:truce', $time);
            } else {
                $this->PgGame->query('UPDATE territory SET status_truce = $2, truce_up_dt = null, truce_type = null WHERE posi_pk = $1', [$_posi_pk, 'N']);
            }
        }

        $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE posi_pk = $1', [$_posi_pk]);

        // 외교서신
        if ($_m_item_pk == 500105) {
            $this->classLetter();

            $letter = [];
            $letter['type'] = 'S';
            $letter['title'] = $i18n->t('letter_finish_truce_status_subject'); // 초보자 보호 해제 알림 & 초보자 팁
            //$letter['content'] = '군주님의 초보자 보호가 해제되었음을 알려드립니다.<br /><br />지금부터 군주님은 다른 군주님의 영지와 자원지를 공격하실 수 있으며 공격을 받을 수도 있게 됩니다.<br /><br />천하평정을 위해 힘찬 출정을 시작하신 군주님의 건승을 기원합니다.';
            $letter['content'] = $i18n->t('letter_finish_truce_status_content');

            $this->Letter->sendLetter(ADMIN_LORD_PK, [$_lord_pk], $letter, true, 'Y', false);
        }

        return true;
    }

    function clearTruceStatus ($_posi_pk): void
    {
        $this->PgGame->query('UPDATE territory SET status_truce = $2, truce_up_dt = now(), truce_type = null WHERE posi_pk = $1', [$_posi_pk, 'N']);
        $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE posi_pk = $1', [$_posi_pk]);
    }

    function finishTimer($_posi_pk): void
    {
        $this->PgGame->query('SELECT time_pk FROM timer WHERE posi_pk = $1 AND status = $2 AND queue_type = $3', [$_posi_pk, 'P', 'D']);
        $time_pk = $this->PgGame->fetchOne();
        if ($time_pk) {
            $this->classTimer();
            $this->Timer->finish($time_pk);
        }
    }

    function cancelOccupationInform($_posi_pk, $_lord_pk): void
    {
        $this->classTimer();

        // 내 타이머 삭제
        $this->PgGame->query('UPDATE timer SET status = $2, queue_status = $3 WHERE posi_pk = $1 and queue_type = $4', [$_posi_pk, 'C', 'F', 'O']);

        // 공격선포 한것 해제
        $this->PgGame->query('SELECT def_posi_pk, def_lord_pk, def_time_pk FROM occupation_inform WHERE att_posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        $z = '';
        if ($rows) {
            foreach($rows AS $v) {
                $this->Timer->cancel($v['def_time_pk'], $v['def_lord_pk']);
                $z .= 'posi_pk[' . $v['def_posi_pk'] .';lord_pk['.$v['def_lord_pk'].';';
            }
            $this->PgGame->query('DELETE FROM occupation_inform WHERE att_posi_pk = $1', [$_posi_pk]);
        }

        $this->PgGame->query('SELECT att_posi_pk, att_lord_pk, att_time_pk FROM occupation_inform WHERE def_posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        if ($rows) {
            foreach($rows AS $k => $v) {
                $this->Timer->cancel($v['att_time_pk'], $v['att_lord_pk']);
            }
            $this->PgGame->query('DELETE FROM occupation_inform WHERE def_posi_pk = $1', [$_posi_pk]);
        }

        // 내 타이머 삭제
        $this->PgGame->query('DELETE FROM timer WHERE posi_pk = $1 AND status = $2 AND queue_type = $3', [$_posi_pk, 'C', 'O']);

        $this->classLog();
        $this->Log->setTerritory($_lord_pk, $_posi_pk, 'cancelOccupationInform', 'cancel_info:'.$z);
    }

    // 성문 개방 유무에 따른 버프 관리
    public function checkGate ($_lord_pk = null): void
    {
        $_lord_pk = (! $_lord_pk) ? $this->Session->lord['lord_pk'] : $_lord_pk;
        $this->PgGame->query('SELECT t1.posi_pk, t2.m_item_pk, t3.status_gate FROM position t1 LEFT JOIN territory_item_buff t2
ON t1.posi_pk = t2.posi_pk AND t2.m_item_pk = 500522 LEFT JOIN territory t3 ON t1.posi_pk = t3.posi_pk
WHERE t1.lord_pk = $1 AND t1.type = $2', [$_lord_pk, 'T']);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;

        $this->classItem();
        foreach($rows AS $v) {
            if ($v['status_gate'] == 'O') { // 성문이 열려있는데 버프가 없는 경우
                if (!$v['m_item_pk']) {
                    $this->Item->setGateBuff($_lord_pk, $v['posi_pk']);
                }
            } else if ($v['status_gate'] == 'C') { // 성문이 닫혀 있는데 버프가 있을 경우
                if ($v['m_item_pk']) {
                    $this->Item->delGateBuff($_lord_pk, $v['posi_pk']);
                }
            }
        }
    }
}