<?php

class Technique
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Resource|null $Resource;
    protected GoldPop|null $GoldPop;
    protected FigureReCalc $FigureReCalc;
    protected Effect $Effect;
    protected Condition $Condition;
    protected Lord $Lord;
    protected Hero $Hero;
    protected Item $Item;
    protected Cash $Cash;
    protected Quest $Quest;
    protected BuildTechnique $BuildTechnique;
    protected Timer $Timer;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame, Resource|null $_Resource = null, GoldPop|null $_GoldPop = null)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Resource = $_Resource;
        $this->GoldPop = $_GoldPop;
    }

    protected function classLord (): void
    {
        if (! isset($this->Lord)) {
            $this->Lord = new Lord($this->Session, $this->PgGame);
        }
    }

    protected function classHero (): void
    {
        if (! isset($this->Hero)) {
            $this->Hero = new Hero($this->Session, $this->PgGame);
        }
    }

    protected function classItem (): void
    {
        if (! isset($this->Item)) {
            $this->Item = new Item($this->Session, $this->PgGame);
        }
    }

    protected function classQuest (): void
    {
        if (! isset($this->Quest)) {
            $this->Quest = new Quest($this->Session, $this->PgGame);
        }
    }

    protected function classCash (): void
    {
        if (! isset($this->Cash)) {
            $this->Cash = new Cash($this->Session, $this->PgGame);
        }
    }

    protected function classBuildTechnique (): void
    {
        if (! isset($this->BuildTechnique)) {
            $this->BuildTechnique = new BuildTechnique($this->Session, $this->PgGame);
        }
    }

    protected function classCondition (): void
    {
        if (! isset($this->Condition)) {
            $this->Condition = new Condition($this->Session, $this->PgGame);
        }
    }

    protected function classResource (): void
    {
        if (! isset($this->Resource)) {
            $this->Resource = new Resource($this->Session, $this->PgGame);
        }
    }

    protected function classGoldPop (): void
    {
        if (! isset($this->GoldPop)) {
            $this->GoldPop = new GoldPop($this->Session, $this->PgGame);
        }
    }

    function classFigureReCalc(): void
    {
        if (! isset($this->FigureReCalc)) {
            $this->classGoldPop();
            $this->classResource();
            $this->FigureReCalc = new FigureReCalc($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classEffect(): void
    {
        if (! isset($this->Effect)) {
            $this->classGoldPop();
            $this->classResource();
            $this->classFigureReCalc();
            $this->Effect = new Effect($this->Session, $this->PgGame, $this->Resource, $this->GoldPop, $this->FigureReCalc);
        }
    }

    protected function classTimer (): void
    {
        if (! isset($this->Timer)) {
            $this->Timer = new Timer($this->Session, $this->PgGame);
        }
    }

    protected function classLog (): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function get($_posi_pk, $_code_arr = null): void
    {
        // 영지 태학 기술 레벨
        $z = ($_code_arr == null) ? '*' : implode(',', $_code_arr);
        $this->PgGame->query("SELECT {$z} FROM technique WHERE posi_pk = $1", [$_posi_pk]);
        if ($this->PgGame->fetch()) {
            $techs = [];
            foreach ($this->PgGame->row AS $k => $v) {
                $techs[$k] = $v;
            }
            $this->Session->sqAppend('TECH', $techs, null, $this->Session->lord['lord_pk'], $_posi_pk);
        }

        // 군주 태학 기술 레벨
        $this->PgGame->query("SELECT {$z} FROM lord_technique WHERE lord_pk = $1", [$this->Session->lord['lord_pk']]);
        if ($this->PgGame->fetch()) {
            $techs = [];
            foreach ($this->PgGame->row AS $k => $v) {
                $techs[$k] = $v;
            }
            $this->Session->sqAppend('LORD_TECH', $techs, null, $this->Session->lord['lord_pk'], $_posi_pk);
        }
    }

    function upgradePre($_posi_pk, $_in_cast_pk, $_code, $_hero_pk): false|array
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['technique', 'condition']);
        if (!$_code) {
            $NsGlobal->setErrorMessage('Error Occurred. [25001]'); // 기술 정보가 없습니다.
            return false;
        }

        // 현재 개발중인지 검사
        $this->PgGame->query("SELECT {$_code}_status FROM lord_technique WHERE lord_pk = $1", [$this->Session->lord['lord_pk']]);
        if ($this->PgGame->fetchOne() == 'P') {
            $NsGlobal->setErrorMessage('Error Occurred. [25002]'); // 개발중인 기술입니다.
            return false;
        }

        // 현재 정보
        $this->PgGame->query("SELECT {$_code} FROM technique WHERE posi_pk = $1", [$_posi_pk]);
        $level = $this->PgGame->fetchOne();
        if ($this->PgGame->getNumRows() == 0) {
            return false;
        } else {
            $m_tech_pk = $_M['TECH_C'][$_code]['m_tech_pk'];
            if (!$m_tech_pk) {
                return false;
            }
            $b = ['m_tech_pk' => $m_tech_pk, 'level' => $level, 'code' => $_code];
        }

        // 1. 레벨 제한 검사
        if ($b['level'] >= $_M['TECH'][$b['m_tech_pk']]['max_level']) {
            $NsGlobal->setErrorMessage($i18n->t('msg_already_max_level')); // 이미 최대 레벨입니다.
            return false;
        }

        // 2.1. 진행 가능한 상태 인가?
        $this->PgGame->query('SELECT buil_pk, concurr_curr, concurr_max FROM build WHERE posi_pk = $1 AND in_cast_pk =  $2', [$_posi_pk, $_in_cast_pk]);
        if ($this->PgGame->fetch()) {
            $build = $this->PgGame->row;
        } else {
            $NsGlobal->setErrorMessage('Error Occurred. [25003]'); // 예기치 못한 오류가 발생했습니다. (1)
            return false;
        }

        if ($build['concurr_curr'] >= $build['concurr_max']) {
            return ['concurr_max' => 'concurr_max'];
        }

        // 3. m_condition 적용
        $m_cond_pk = &$_M['TECH'][$b['m_tech_pk']]['level'][$b['level']+1]['m_cond_pk'];

        $this->classCondition();
        $ret = $this->Condition->conditionCheck($_posi_pk, $m_cond_pk, $_in_cast_pk, 'I', $_hero_pk);
        if (!$ret) {
            $NsGlobal->setErrorMessage('Error Occurred. [25004]'); // 수행 조건을 확인해주십시오.
            return false;
        }
        // 3.1. 선행건물 - building_in_castle 의 posi_pk 중에 m_buil_pk 를 m_buil_1~3_pk 가진 건물 중에 m_buil_1~3_level 이 모두 만족하나?

        // 3.2. 선행기술 - technique 의 posi 중에 m_tech_1_pk 를 code로 변환한 컬럼을 전체 선택 하여 각각의 요구 level 이 모두 만족하나 검사

        // 트랜잭션
        try {
            $this->PgGame->begin();
            // 3.3. 수행영웅 확인 - _cmd_hero_pk 로 영웅 정보 추출. 각종 스탯 및 등급에 대한 제한을 걸수 있다.
            $this->classHero();
            global $_QBW_SQ_REFRESH_FLAG;
            $_QBW_SQ_REFRESH_FLAG = true;
            $ret = $this->Hero->setCommand($_posi_pk, $_hero_pk, 'C', 'Techn');
            if (!$ret) {
                throw new Exception('Error Occurred. [25005]'); // 영웅 할당이 실패 했습니다.
            }

            // 3.4. 자원소모 (갱신 필요)
            $res = [
                'food' => $_M['COND'][$m_cond_pk]['build_food'],
                'horse' => $_M['COND'][$m_cond_pk]['build_horse'],
                'lumber' => $_M['COND'][$m_cond_pk]['build_lumber'],
                'iron' => $_M['COND'][$m_cond_pk]['build_iron'],
            ];

            $this->classResource();
            $r = $this->Resource->decrease($_posi_pk, $res, null, 'tech_pre');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_lack')); // 자원이 부족합니다.
            }

            //gold소모
            $build_gold = $_M['COND'][$m_cond_pk]['build_gold'];

            $this->classGoldPop();
            $r = $this->GoldPop->decreaseGold($_posi_pk, $build_gold, null, 'tech_pre');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
            }

            // 3.5. 아이템 소모 (갱신 필요)
            if ($_M['COND'][$m_cond_pk]['m_item_pk']) {
                $this->classItem();
                $ret = $this->Item->useItem($_posi_pk, $this->Session->lord['lord_pk'], $_M['COND'][$m_cond_pk]['m_item_pk'], 1, ['_yn_quest' => true]);
                if(!$ret) {
                    throw new Exception($i18n->t('msg_not_enough_item_require'));
                }
            }

            // 군주 태학 기술에 진행중으로 update
            $r = $this->PgGame->query("UPDATE lord_technique SET {$_code}_status = $1 WHERE lord_pk = $2", ['P', $this->Session->lord['lord_pk']]);
            if (!$r) {
                throw new Exception('Error Occurred. [25006]'); // 군주태학 상태 변경에 실패 하였습니다.
            }
            $this->PgGame->commit();
        } catch (Exception $e) {
            // 실패, sq 무시
            $this->PgGame->rollback();
            throw new ErrorHandler('error', $e->getMessage(), true);
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_QBW_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        // 빌드 등록 (갱신 필요)
        return [
            'code' => $_code,
            'm_tech_pk' => $b['m_tech_pk'],
            'level' => $b['level'],
            'buil_pk' => $build['buil_pk'],
            'build_time' => $_M['COND'][$m_cond_pk]['build_time'],
        ];
    }

    function upgradePost($_posi_pk, $_code, $_buil_tech_pk, $_time_pk = null): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['technique', 'condition']);

        $r = $this->PgGame->query("UPDATE technique SET $_code = $_code + 1 WHERE posi_pk = $1", [$_posi_pk]);
        if (!$r || $this->PgGame->getAffectedRows() == 0) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'technique update failed.;posi_pk['.$_posi_pk.'];code['.$_code.'];');
            return false;
        }

        // 군주 태학 리셋
        $this->PgGame->query("UPDATE lord_technique SET {$_code}_status = $1 WHERE lord_pk = $2", [null, $this->Session->lord['lord_pk']]);

        // LOG
        $this->classLog();
        $this->PgGame->query('SELECT buil_pk, cmd_hero_pk, m_tech_pk, status, current_level, regist_dt, start_dt, build_time, build_time_reduce, end_dt FROM build_technique WHERE buil_tech_pk = $1', [$_buil_tech_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        $description = $_M['TECH'][$row['m_tech_pk']]['title'] . '[curr['. $row['current_level'] . '];update['. ($row['current_level'] + 1) . '];];';

        // 삭제
        $this->PgGame->query('DELETE FROM build_technique WHERE buil_tech_pk = $1', [$_buil_tech_pk]);

        $this->Log->setTechnique($this->Session->lord['lord_pk'], $_posi_pk, 'complete', $description, $_buil_tech_pk, $row['buil_pk'], $row['cmd_hero_pk'], $row['m_tech_pk'], $row['regist_dt'], $row['start_dt'], $row['build_time'], $row['build_time_reduce'], $row['current_level'], $_time_pk);

        return true;
    }

    function cancel($_posi_pk, $_m_tech_pk, $_level, $_hero_pk = null, $_build_pk = null, $_time_pk = null): true
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['technique', 'condition']);

        // 3. m_condition 적용
        $m_cond_pk = &$_M['TECH'][$_m_tech_pk]['level'][$_level+1]['m_cond_pk'];

        // 자원 롤백
        $res = [
            'food' => intval($_M['COND'][$m_cond_pk]['build_food']*0.3),
            'horse' => intval($_M['COND'][$m_cond_pk]['build_horse']*0.3),
            'lumber' => intval($_M['COND'][$m_cond_pk]['build_lumber']*0.3),
            'iron' => intval($_M['COND'][$m_cond_pk]['build_iron']*0.3),
        ];

        $this->classResource();
        $r = $this->Resource->increase($_posi_pk, $res, null, 'cancel_tech');
        if (!$r) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '자원증가실패;posi_pk['.$_posi_pk.'];food['.$res['food'].'];horse['.$res['horse'].'];lumber['.$res['lumber'].'];iron['.$res['iron'].'];');
        }

        $build_gold = intval($_M['COND'][$m_cond_pk]['build_gold']*0.3);
        $r = $this->GoldPop->increaseGold($_posi_pk, $build_gold, null, 'cancel_tech');
        if (!$r) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '황금증가실패;posi_pk['.$_posi_pk.'];gold['.$build_gold.']');
        }

        // 군주 태학 리셋
        $this->PgGame->query("UPDATE lord_technique SET {$_M['TECH'][$_m_tech_pk]['code']}_status = $1 WHERE lord_pk = $2", [null, $this->Session->lord['lord_pk']]);

        // LOG
        $this->classLog();
        $this->PgGame->query('SELECT buil_pk, cmd_hero_pk, m_tech_pk, status, current_level, regist_dt, start_dt, build_time, build_time_reduce, end_dt FROM build_technique WHERE buil_tech_pk = $1', [$_build_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        // 삭제
        $this->PgGame->query('DELETE FROM build_technique WHERE buil_tech_pk = $1', [$_build_pk]);

        $this->Log->setTechnique($this->Session->lord['lord_pk'], $_posi_pk, 'cancel', $_M['TECH'][$_m_tech_pk]['title'], $_build_pk, $row['buil_pk'], $row['cmd_hero_pk'], $row['m_tech_pk'], $row['regist_dt'], $row['start_dt'], $row['build_time'], $row['build_time_reduce'], $row['current_level'], $_time_pk);

        return true;
    }

    // 태학 기술 개발시
    function updateLordTechnique($_lord_pk, $_posi_pk, $_code): true
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['technique']);

        // 로드 기술 레벨
        $this->PgGame->query("SELECT $_code FROM lord_technique WHERE lord_pk = $1", [$_lord_pk]);
        $lord_level = $this->PgGame->fetchOne();

        // 영지 기술 레벨
        $this->PgGame->query("SELECT $_code FROM technique WHERE posi_pk = $1", [$_posi_pk]);
        $terr_level = $this->PgGame->fetchOne();

        if ($lord_level < $terr_level) {
            // 태학 기술 레렙 업데이트
            $this->PgGame->query("UPDATE lord_technique SET $_code = $2 WHERE lord_pk = $1", [$_lord_pk, $terr_level]);

            // 영향력 처리
            $m_tech_pk = $_M['TECH_C'][$_code]['m_tech_pk'];
            $this->classLord();
            $this->Lord->increasePower($_lord_pk, $_M['TECH'][$m_tech_pk]['level'][$terr_level]['increase_power'], $_posi_pk);

            // ct가 0보다 클경우 모두 업데이트
            //$query_params = Array($_lord_pk, $terr_level, 'T');
            //$this->PgGame->query('UPDATE technique SET ' . $_code . ' = $2 WHERE posi_pk IN (select posi_pk from position where lord_pk = $1 and type = $3) and ' . $_code . ' > 0', $query_params);
        }

        // 태학 기술레벨 효과
        $this->PgGame->query('SELECT posi_pk FROM technique WHERE posi_pk IN (select posi_pk from position where lord_pk = $1 and type = $2)', [$_lord_pk, 'T']);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        foreach ($rows AS $v) {
            if ($v['posi_pk'] == $_posi_pk) {
                $this->setTechniqueLevelEffect($v['posi_pk'], $_M['TECH_C'][$_code]['m_tech_pk'], $_code, $terr_level);
            } else {
                $this->updateTerritoryTechnique($_lord_pk, $v['posi_pk']);
            }
        }
        return true;
    }

    // 태학 건물 건설/업그레이드 및 신규 획득(점령)
    function updateTerritoryTechnique($_lord_pk, $_posi_pk, $_level = null): true
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['technique', 'condition']);

        if (!$_level) {
            $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_TECHNIQUE]);
            $_level = $this->PgGame->fetchOne();
            if (!$_level) { // 태학건물이 없을경우 처리할 필요 없음.
                return true;
            }
        }

        $arr_tech_max = [];

        // 1. 오픈 가능한 기술
        $this->PgGame->query("SELECT t2.m_tech_pk, MAX(t2.level) as level
FROM  m_condition t1, m_technique_level t2
WHERE t1.m_cond_pk = t2.m_cond_pk AND t1.type = 'T' AND t1.active_buil_level BETWEEN 1 AND {$_level}
GROUP by t2.m_tech_pk");
        while($this->PgGame->fetch()) {
            $arr_tech_max[$this->PgGame->row['m_tech_pk']] = $this->PgGame->row['level'];
        }

        // 2. 군주 태학 레벨
        $this->PgGame->query('SELECT agriculture, stock_farming, lumbering, mining, storage, construction, astronomy, paper, medicine, smelting, casting, machinery, mil_fencing, mil_shield, mil_spear, mil_horse, mil_science, fortification, compass, logistics, informatics, mil_archery, mil_formation, mil_siege FROM lord_technique WHERE lord_pk = $1', [$_lord_pk]);
        $this->PgGame->fetch();
        $lord_tech_level = $this->PgGame->row;

        // 3. 영지 태학 레벨
        $this->PgGame->query('SELECT agriculture, stock_farming, lumbering, mining, storage, construction, astronomy, paper, medicine, smelting, casting, machinery, mil_fencing, mil_shield, mil_spear, mil_horse, mil_science, fortification, compass, logistics, informatics, mil_archery, mil_formation, mil_siege FROM technique WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $terr_tech_level = $this->PgGame->row;

        // 4. 군주 태학 레벨과 현재 영지 레벨 비교
        $cnt = 0;
        $update_str = '';
        $str = '';
        $update_effect_target = [];
        foreach($lord_tech_level AS $k => $v) {
            $m_tech_pk = $_M['TECH_C'][$k]['m_tech_pk'];
            if (isset($arr_tech_max[$m_tech_pk])) {
                if ($v != $terr_tech_level[$k]) {
                    $update_str .= sprintf(', %s = %d',  $k, $v);
                    $str .= sprintf(', %s',  $k);
                    $update_effect_target[$m_tech_pk]['code'] = $k;
                    $update_effect_target[$m_tech_pk]['level'] = $v;
                    $cnt++;
                }
            }
        }

        // 영지 태학 기술 레벨 업데이트
        if ($cnt) {
            $this->PgGame->query("UPDATE technique SET last_update_dt = now() {$update_str} WHERE posi_pk = $1", [$_posi_pk]);
            $this->PgGame->query("SELECT last_update_dt {$str} FROM technique WHERE posi_pk = $1", [$_posi_pk]);
            if ($this->PgGame->fetch()) {
                $techs = [];
                foreach ($this->PgGame->row AS $k => $v) {
                    $techs[$k] = $v;
                }
                $this->Session->sqAppend('TECH', $techs, null, $_lord_pk, $_posi_pk);
            }

            // 감소나 증가로 인해 변경된 태학레벨의 Effect 적용
            foreach ($update_effect_target AS $k => $v) {
                $this->setTechniqueLevelEffect($_posi_pk, $k, $v['code'], $v['level']);
            }
        }
        return true;
    }

    function setTechniqueLevelEffect($_posi_pk, $_m_tech_pk, $_code, $_level): void
    {
        $this->classResource();
        $this->classGoldPop();
        $this->classEffect();

        // 태학 기술레벨 효과
        if ($_level) {
            $effect = sprintf('%d_%02d', $_m_tech_pk, $_level);
            $effect_types = $this->Effect->getEffectTypes([$effect]);
            if (COUNT($effect_types) > 0) {
                $this->Effect->setUpdateEffectTypes($_posi_pk, $effect_types);
            }
        }
    }

    function cancelTechnique($_posi_pk, $_lord_pk): bool
    {
         // 1. in_cast_pk 찾기
         // 2. build 테이블에서 buil_pk 찾기
         // 3. build_technique가 진행중이면, buil_tech_pk찾기
         // 4. $r_arr = $BuildTechnique->cancel($r_arr['queue_pk']); 호출
        $this->PgGame->query('SELECT in_castle_pk FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_TECHNIQUE]);
        $in_cast_pk = $this->PgGame->fetchOne();

        $this->PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2', [$_posi_pk, $in_cast_pk]);
        $buil_pk = $this->PgGame->FetchOne();

        if (!$buil_pk) { // 취소할 태학 기술 없음.
            return true;
        }

        $this->PgGame->query('SELECT buil_tech_pk FROM build_technique WHERE buil_pk = $1 AND status = $2', [$buil_pk, 'P']);
        $queue_pk = $this->PgGame->FetchOne();
        if (!$queue_pk) {
            return false;
        }

        $this->PgGame->Query('SELECT time_pk FROM timer WHERE queue_type = $1 AND queue_pk = $2 AND status = $3', ['T', $queue_pk, 'P']);
        $time_pk = $this->PgGame->FetchOne();
        if (!$time_pk) {
            return false;
        }

        $this->classTimer();
        $this->Timer->cancel($time_pk);

        $this->classBuildTechnique();
        $r_arr = $this->BuildTechnique->cancel($queue_pk);

        // 군주 태학 리셋
        if ($r_arr['m_tech_pk']) {
            global $_M, $NsGlobal;
            $NsGlobal->requireMasterData(['technique']);
            $this->PgGame->query("UPDATE lord_technique SET {$_M['TECH'][$r_arr['m_tech_pk']]['code']}_status = $1 WHERE lord_pk = $2", [null, $_lord_pk]);
        }

        return true;
    }

    function initTechniqueLevel($_posi_pk): true
    {
        $this->PgGame->query('UPDATE technique SET agriculture = 0, stock_farming = 0, lumbering = 0, mining = 0,
storage = 0, construction = 0, astronomy = 0, paper = 0, medicine = 0, smelting = 0,
casting = 0, machinery = 0, mil_fencing = 0, mil_shield = 0, mil_spear = 0, mil_horse = 0,
mil_science = 0, fortification = 0, compass = 0, logistics = 0, informatics = 0, mil_archery = 0,
mil_formation = 0, mil_siege = 0, last_update_dt = now() WHERE posi_pk = $1', [$_posi_pk]);
        return true;
    }

    function now($_posi_pk, $_code, $_in_cast_pk): array|bool
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['technique', 'condition']);

        if (!$_code) {
            $NsGlobal->setErrorMessage('Error Occurred. [25007]'); // 기술 정보가 없습니다.
            return false;
        }

        // 현재 개발중인지 검사
        $this->PgGame->query("SELECT {$_code}_status FROM lord_technique WHERE lord_pk = $1", [$this->Session->lord['lord_pk']]);
        if ($this->PgGame->fetchOne() == 'P') {
            $NsGlobal->setErrorMessage('Error Occurred. [25008]'); // 개발중인 기술입니다.
            return false;
        }

        // 현재 정보
        $this->PgGame->query('SELECT '. $_code. ' FROM technique WHERE posi_pk = $1', [$_posi_pk]);
        $level = $this->PgGame->fetchOne();
        if ($this->PgGame->getNumRows() == 0) {
            return false;
        } else {
            $m_tech_pk = $_M['TECH_C'][$_code]['m_tech_pk'];
            if (!$m_tech_pk) {
                return false;
            }
            $b = ['m_tech_pk' => $m_tech_pk, 'level' => $level, 'code' => $_code];
        }

        // 1. 레벨 제한 검사
        if ($b['level'] >= $_M['TECH'][$b['m_tech_pk']]['max_level']) {
            return false;
        }

        // 캐시에 의한 즉시 연구이므로 Queue 검사 없음.

        // 3. m_condition 적용
        $m_cond_pk = &$_M['TECH'][$b['m_tech_pk']]['level'][$b['level']+1]['m_cond_pk'];

        $this->classCondition();
        $ret = $this->Condition->conditionCheck($_posi_pk, $m_cond_pk, $_in_cast_pk, 'I', null, true);
        if (!$ret) {
            return false;
        }

        // 트랜잭션
        try {
            $this->PgGame->begin();
            global $_QBW_SQ_REFRESH_FLAG;
            $_QBW_SQ_REFRESH_FLAG = true;

            /*
             * 영웅 배속 없음
             */

            // 3.4. 자원소모 (갱신 필요)
            $res = [
                'food' => $_M['COND'][$m_cond_pk]['build_food'],
                'horse' => $_M['COND'][$m_cond_pk]['build_horse'],
                'lumber' => $_M['COND'][$m_cond_pk]['build_lumber'],
                'iron' => $_M['COND'][$m_cond_pk]['build_iron'],
            ];

            $this->classResource();
            $r = $this->Resource->decrease($_posi_pk, $res, null, 'tech_pre');
            if (! $r) {
                throw new Exception($i18n->t('msg_resource_lack')); // 자원이 부족합니다.
            }

            // gold 소모
            $build_gold = $_M['COND'][$m_cond_pk]['build_gold'];

            $r = $this->GoldPop->decreaseGold($_posi_pk, $build_gold, null, 'tech_pre');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
            }

            // 3.5. 아이템 소모 (갱신 필요)
            if ($_M['COND'][$m_cond_pk]['m_item_pk']) {
                $this->classItem();
                $ret = $this->Item->useItem($_posi_pk, $this->Session->lord['lord_pk'], $_M['COND'][$m_cond_pk]['m_item_pk'], 1, ['_yn_quest' => true]);
                if(!$ret) {
                    throw new Exception($i18n->t('msg_not_enough_item_require'));
                }
            }

            // 즉시 연구에 의한 큐빅 소모
            $build_time = $_M['COND'][$m_cond_pk]['build_time'];
            $remain_qbig = Useful::getNeedQbig($build_time);

            $this->classCash();
            $qbig = $this->Cash->decreaseCash($this->Session->lord['lord_pk'], $remain_qbig, 'technique now');
            if (!$qbig) {
                throw new Exception($i18n->t('msg_qbig_lack')); // 큐빅이 부족합니다.
            }

            // 실제 태학 기술 적용
            $r = $this->PgGame->query("UPDATE technique SET $_code = $_code + 1 WHERE posi_pk = $1", [$_posi_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'technique update failed.;posi_pk['.$_posi_pk.'];code['.$_code.'];');
                return false;
            }

            // 군주 태학 기술 레벨
            $this->updateLordTechnique($this->Session->lord['lord_pk'], $_posi_pk, $_code);

            // notification
            $this->get($_posi_pk, [$_code]);

            $level = $b['level'] + 1;

            $this->PgGame->commit();
        }  catch (Exception $e) {
            $this->PgGame->rollback();
            throw new ErrorHandler('error', $e->getMessage(), true);
        }

        //퀘스트 체크
        $this->classQuest();
        $this->Quest->conditionCheckQuest($this->Session->lord['lord_pk'], ['quest_type' => 'devel_tech','m_tech_pk' => $b['m_tech_pk'], 'level' => $level]);

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_QBW_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        $this->Session->sqAppend('PUSH', ['PLAY_SOUND' => 'research_complete'], null, $this->Session->lord['lord_pk']);

        return [
            'm_tech_pk' => $b['m_tech_pk'],
            'current_level' => $b['level'],
            'next_level' => $level,
        ];
    }
}