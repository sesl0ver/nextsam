<?php

class Medical
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Army $Army;
    protected BuildMedical $BuildMedical;
    protected Bdic $Bdic;
    protected Condition $Condition;
    protected Hero $Hero;
    protected Territory $Territory;
    protected Cash $Cash;
    protected GoldPop $GoldPop;
    protected Resource $Resource;
    protected FigureReCalc $FigureReCalc;
    protected Effect $Effect;
    protected Timer $Timer;
    protected Report $Report;
    protected Quest $Quest;
    protected Troop $Troop;
    protected Lord $Lord;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    function classArmy(): void
    {
        if (! isset($this->Army)) {
            $this->classResource();
            $this->classGoldPop();
            $this->Army = new Army($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classBuildMedical(): void
    {
        if (! isset($this->BuildMedical)) {
            $this->classTimer();
            $this->BuildMedical = new BuildMedical($this->Session, $this->PgGame, $this->Timer);
        }
    }

    function classHero(): void
    {
        if (! isset($this->Hero)) {
            $this->Hero = new Hero($this->Session, $this->PgGame);
        }
    }

    public function classQuest(): void
    {
        if (!isset($this->Quest)) {
            $this->Quest = new Quest($this->Session, $this->PgGame);
        }
    }

    protected function classTerritory (): void
    {
        if (! isset($this->Territory)) {
            $this->Territory = new Territory($this->Session, $this->PgGame);
        }
    }

    function classGoldPop(): void
    {
        if (! isset($this->GoldPop)) {
            $this->GoldPop = new GoldPop($this->Session, $this->PgGame);
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

    function classCondition(): void
    {
        if (! isset($this->Condition)) {
            $this->Condition = new Condition($this->Session, $this->PgGame);
        }
    }

    function classCash(): void
    {
        if (! isset($this->Cash)) {
            $this->Cash = new Cash($this->Session, $this->PgGame);
        }
    }

    function classResource(): void
    {
        if (! isset($this->Resource)) {
            $this->Resource = new Resource($this->Session, $this->PgGame);
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
            $this->classResource();
            $this->classGoldPop();
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

    function classReport(): void
    {
        if (!isset($this->Report)) {
            $this->Report = new Report($this->Session, $this->PgGame);
        }
    }

    protected function classTroop (): void
    {
        if (! isset($this->Troop)) {
            $this->Troop = new Troop($this->Session, $this->PgGame);
        }
    }

    protected function classLord (): void
    {
        if (! isset($this->Lord)) {
            $this->Lord = new Lord($this->Session, $this->PgGame);
        }
    }

    protected function classLog (): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    // 부상 병사 추가
    function setInjuryArmy($_posi_pk, $_army_arr, &$abandon_army): false|array
    {
        global $_M, $_M_ARMY_C, $NsGlobal;
        $NsGlobal->requireMasterData(['building', 'army']);

        $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_MEDICAL]);
        $level = $this->PgGame->fetchOne();

        if (!$level) {
            // 의료원이 없으면 병사는 부상자 처리 없음!
            return false;
        }

        $this->PgGame->query('SELECT posi_pk FROM medical_army WHERE posi_pk = $1', [$_posi_pk]);
        if (!$this->PgGame->fetch()) {
            $this->PgGame->query('INSERT INTO medical_army (posi_pk) VALUES ($1)', [$_posi_pk]);
        }

        $max_injury_number = $_M['BUIL']['200700']['level'][$level]['variation_2'];

        $this->PgGame->query('SELECT worker+infantry+pikeman+scout+spearman+armed_infantry+archer+horseman+armed_horseman+transporter+bowman+battering_ram+catapult+adv_catapult FROM medical_army WHERE posi_pk = $1', [$_posi_pk]);
        $curr_injury_number = $this->PgGame->fetchOne();

        $passable_injury_number = $max_injury_number - $curr_injury_number;

        // 사망병에서 부상병 추출
        $z_cnt = 2;
        $z = '';
        $z_arr = [$_posi_pk];
        $log_description = '';
        $injury_army = []; // null

        // 부상병 처리 우선 순위
        $army_sort_arr = [];
        foreach ($_army_arr AS $k => $v) {
            $army_sort_arr[$_M['ARMY_C'][$k]['priority'] - 1]['code'] = $k;
            $army_sort_arr[$_M['ARMY_C'][$k]['priority'] - 1]['injury'] = $v;
        }
        // 정렬
        ksort($army_sort_arr);

        foreach ($army_sort_arr AS $v) {
            // 스킬 적용
            $this->classEffect();
            $this->Effect->initEffects();
            $ret = $this->Effect->getEffectedValue($_posi_pk, ['injury_army_increase'], 1);
            $skill_effect = $ret['effected_values']['hero_skill'] * 0.01;

            $ret = $this->Effect->getEffectedValue($_posi_pk, ['army_cure_increase'], 1);
            $item_buff_effect = $ret['effected_values']['item'] * 0.01;

            // 부상병 계산 - $injury = floor((0.02 * $level + $skill_effect + $item_buff_effect)*$v);
            $per = (0.01 * $_M['BUIL']['200700']['level'][$level]['variation_1']) + $skill_effect + $item_buff_effect;

            if ($per > 0.90) {
                $per = 0.90;
            }

            $injury = floor($per*$v['injury']);
            $code = $v['code'];

            // 부상병이 있으면...
            if ($injury > 0) {
                if ($passable_injury_number < $injury) {
                    $injury = $passable_injury_number;
                    $abandon_army = true;
                }

                $z .= ", $code = $code+\$$z_cnt";
                $z_arr[] = $injury;
                $z_cnt++;

                $injury_army[$code] = $injury;
                $log_description .= "{$_M_ARMY_C[$code]['m_army_pk']}[$injury];";

                $passable_injury_number -= $injury;

                if ($passable_injury_number <= 0) {
                    $log_description .= "limit[max[$max_injury_number];curr[$curr_injury_number];];";
                    break;
                }
            }
        }

        if ($z) {
            $result = $this->PgGame->query('UPDATE medical_army SET last_update_dt = now() '. $z. ' WHERE posi_pk = $1', $z_arr);

            if ($result) {
                // 로그
                $this->classLog();
                $this->Log->setArmy(null, $_posi_pk, 'injury_army', $log_description);
            }
        }

        $this->getInjuryArmy($_posi_pk);

        return $injury_army;
    }

    // 부상 병사 현황 (전체 치료비용 포함)
    function getInjuryArmy($_posi_pk): bool|array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);

        $this->PgGame->query('SELECT * FROM medical_army WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $r = $this->PgGame->row;

        // 값이 존재하지 않으면
        if (!$r || count($r) < 1) {
            $r = [];
            foreach($_M['ARMY'] AS $v) {
                $r[$v['code']] = 0;
            }
        }

        // PosiPk 기준으로 군주Pk 알아옴
        $this->PgGame->query('SELECT lord_pk FROM position WHERE posi_pk = $1', [$_posi_pk]);
        $lord_pk = $this->PgGame->fetchOne();

        $this->Session->sqAppend('ARMY_MEDI', $r, null, $lord_pk, $_posi_pk);

        unset($r['posi_pk']);
        unset($r['last_update_dt']);

        return $r;
    }

    // 부상 병사 전체 치료
    function doInjuryArmyTreatment($_posi_pk, $_army_arr): bool
    {
        // 부상병 삭제
        $z_cnt = 2;
        $z = '';
        $z_arr = [$_posi_pk];

        $this->PgGame->query('SELECT * FROM medical_army WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $curr_army_arr = $this->PgGame->row;

        foreach ($_army_arr AS $k => $v) {
            // 병력 유효 검사
            if ($curr_army_arr[$k] - $v < 0 || $curr_army_arr[$k] < 0) {
                return false;
            }
            $z .= ", $k = $k-\$$z_cnt";
            $z_arr[] = $v;
            $z_cnt++;
        }

        if ($z) {
            $this->PgGame->query('UPDATE medical_army SET last_update_dt = now() '. $z. ' WHERE posi_pk = $1', $z_arr);
        }

        // 병사에 추가
        $z_cnt = 2;
        $z = '';
        $z_arr = [$_posi_pk];
        $desc = '';

        foreach ($_army_arr AS $k => $v) {
            $z .= ", $k = $k+\$$z_cnt";
            $z_arr[] = $v;
            $z_cnt++;
            if ($v > 0) {
                $desc .= $k . '[' .$v .'];';
            }
        }

        if ($z) {
            $this->PgGame->query('UPDATE army SET last_update_dt = now() '. $z. ' WHERE posi_pk = $1', $z_arr);
        }

        // 로그
        $this->classLog();
        // $this->Log->setArmy(null, $_posi_pk, 'incr_army_Medical', $desc);

        return true;
    }

    // 부상 영웅 추가 - TODO 사용안함?
    function setInjuryHero($_posi_pk, $_hero_pk, $_status_health, $_lord_pk = null): bool
    {
        // 성주일 경우는 처리 안함.
        $this->PgGame->query('SELECT assign_hero_pk FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2 AND assign_hero_pk = $3', [$_posi_pk, PK_BUILDING_CITYHALL, $_hero_pk]);
        if ($this->PgGame->fetchOne()) {
            return true;
        }

        if (!$_lord_pk) {
            $this->PgGame->query('SELECT lord_pk FROM position WHERE posi_pk = $1', [$_posi_pk]);
            $_lord_pk = $this->PgGame->fetchOne();
        }

        global $NsGlobal;
        // 트랜잭션
        try {
            $this->PgGame->begin();

            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            $wall_director_hero_pk = null;
            $wall_staff_hero_pk = null;

            // 기본 등록
            $this->PgGame->query('INSERT INTO medical_hero (posi_pk, status, status_health, hero_pk, regist_dt) VALUES ($1, $2, $3, $4, now())', [$_posi_pk, 'W', $_status_health, $_hero_pk]);

            // 영웅 상태 변경 - 배속 해제
            $this->PgGame->query('SELECT in_castle_pk FROM building_in_castle WHERE posi_pk = $1 And assign_hero_pk = $2', [$_posi_pk, $_hero_pk]);
            $in_cast_pk = $this->PgGame->fetchOne();
            if ($in_cast_pk) {
                $this->classBdic();
                $ret = $this->Bdic->heroUnassign($_posi_pk, $in_cast_pk, true, $_lord_pk);
                if (!$ret) {
                    throw new Exception('InjuryHero Unassig fail;hero_pk['. $_hero_pk. '];');
                }
            } else {
                // 성벽에 배속 되어 있을 경우
                $this->PgGame->query('SELECT wall_director_hero_pk, wall_staff_hero_pk FROM territory WHERE posi_pk = $1', [$_posi_pk]);
                $this->PgGame->fetch();

                if ($this->PgGame->row['wall_director_hero_pk'] == $_hero_pk) {
                    $wall_director_hero_pk = $_hero_pk;
                    $this->PgGame->query('UPDATE territory SET wall_director_hero_pk = null WHERE posi_pk = $1', [$_posi_pk]);
                } else if ($this->PgGame->row['wall_staff_hero_pk'] == $_hero_pk) {
                    $wall_staff_hero_pk = $_hero_pk;
                    $this->PgGame->query('UPDATE territory SET wall_staff_hero_pk = null WHERE posi_pk = $1', [$_posi_pk]);
                }
            }

            // 명령해제
            $this->classHero();
            $this->PgGame->query('SELECT status_cmd FROM my_hero WHERE hero_pk = $1', [$_hero_pk]);
            if ($this->PgGame->fetchOne() != 'I') {
                $ret = $this->Hero->unsetCommand($_hero_pk, false);
                if (!$ret) {
                    throw new Exception('InjuryHero unsetCommand fail;hero_pk['. $_hero_pk. '];');
                }
            }

            // 치료명령
            $ret = $this->Hero->setCommand($_posi_pk, $_hero_pk, 'T', 'Treat', true, true);
            if (!$ret) {
                throw new Exception('InjuryHero setCommand fail;hero_pk['. $_hero_pk. '];');
            }

            // 의료원 상태
            $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_MEDICAL]);
            $level = $this->PgGame->fetchOne();

            // 의료원 존재시 자동 시작
            if ($level) {
                $ret = $this->doInjuryHeroTreatment($this->PgGame->currSeq('medical_hero_medi_hero_pk_seq'));
                if (!$ret) {
                    throw new Exception('InjuryHero doInjuryHeroTreatment fail;hero_pk['. $_hero_pk. '];');
                }
            }

            $this->classTerritory();
            if ($wall_director_hero_pk) {
                $this->Territory->get($_posi_pk, ['wall_director_hero_pk'], $_lord_pk);
            } else if ($wall_staff_hero_pk) {
                $this->Territory->get($_posi_pk, ['wall_staff_hero_pk'], $_lord_pk);
            } else {
                $this->Session->sqAppend('HERO', [$_hero_pk => $this->Hero->getMyHeroStatus($_hero_pk)], null, $_lord_pk, $_posi_pk);
            }

            $this->PgGame->commit();
        } catch (Exception $e) {
            // 실패, sq 무시
            $this->PgGame->rollback();

            // 에러 메시지 추가
            $NsGlobal->setErrorMessage($e->getMessage());

            //dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$_posi_pk.'];');

            return false;
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        return true;
    }

    // 부상 영웅들
    function getInjuryHeroes($_posi_pk): array
    {
        $this->classHero();
        // TODO abstime 변경 필요.
        $this->PgGame->query('SELECT medi_hero_pk, status, status_health, hero_pk, time_pk, date_part(\'epoch\', end_dt)::integer as end_dt FROM medical_hero WHERE posi_pk = $1 ORDER BY medi_hero_pk DESC', [$_posi_pk]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        foreach ($rows AS $k => $v) {
            $rows[$k]['herodata'] = $this->Hero->getMyHeroInfo($v['hero_pk']);
        }
        return $rows;
    }

    // 치료 시작
    function doInjuryHeroTreatment($_medi_hero_pk): bool
    {
        global $NsGlobal;
        $this->PgGame->query('SELECT posi_pk, status, status_health, hero_pk FROM medical_hero WHERE medi_hero_pk = $1', [$_medi_hero_pk]);
        if (!$this->PgGame->fetch()) {
            $NsGlobal->setErrorMessage('Error Occurred. [22001]'); // 치료 대상 영웅을 찾을 수 없습니다.
            return false;
        }

        $posi_pk = $this->PgGame->row['posi_pk'];
        $status_health = $this->PgGame->row['status_health'];
        $hero_pk = $this->PgGame->row['hero_pk'];

        // 치료시간
        global $_M;
        $treatment_time = $_M['CODESET']['HERO_TREATMENT_TIME'][$status_health];

        // 영웅 배속시 치료시간 단축
        $this->classEffect();
        $this->Effect->initEffects();
        $effect_types = ['treatment_time_decrease'];
        $ret = $this->Effect->getEffectedValue($posi_pk, $effect_types, $treatment_time);
        $treatment_time = intval($ret['value']);

        // 타이머 등록
        // CTimer
        $this->classTimer();
        $desc = '영웅치료';
        $this->Timer->set($posi_pk, 'M', $_medi_hero_pk, 'M', $desc, $treatment_time, null);

        // time_pk
        $time_pk = $this->Timer->getTimePk();
        $this->PgGame->query("UPDATE medical_hero SET status = $1, time_pk = $2, end_dt = now() + interval '$treatment_time second' WHERE medi_hero_pk = $3", ['T', $time_pk, $_medi_hero_pk]);

        // Log
        $this->classLog();
        $this->Log->setBuildingMedical(null, $posi_pk, 'InjuryHeroTreatment', $hero_pk, null, $status_health);

        return true;
    }

    // 치료 시간 단축 - TODO 비었네?
    function doInjuryHeroTreatmentInstantly()
    {

    }

    // 치료 완료
    function doInjuryHeroTreatmentFinish($_medi_hero_pk): bool
    {
        $this->classHero();
        $this->classReport();
        $this->classTroop();

        $this->PgGame->query('SELECT posi_pk, hero_pk, date_part(\'epoch\', regist_dt)::integer as regist_dt, (SELECT lord_pk FROM position WHERE posi_pk = medical_hero.posi_pk) AS lord_pk FROM medical_hero WHERE medi_hero_pk = $1', [$_medi_hero_pk]);
        $this->PgGame->fetch();

        $posi_pk = $this->PgGame->row['posi_pk'];
        $hero_pk = $this->PgGame->row['hero_pk'];
        $regist_dt = $this->PgGame->row['regist_dt'];
        $lord_pk = $this->PgGame->row['lord_pk'];

        if (!$hero_pk) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '영웅 해제 못함;해당 hero_pk 없음['.$hero_pk.']');
            return false;
        }

        // Uncommand
        $this->Hero->unsetCommand($hero_pk, true);

        // 삭제
        $this->PgGame->query('DELETE FROM medical_hero WHERE medi_hero_pk = $1', [$_medi_hero_pk]);

        // 영웅정보
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['hero', 'hero_base']);

        $this->PgGame->query('SELECT m_hero_pk, level FROM hero WHERE hero_pk = $1', [$hero_pk]);
        $this->PgGame->fetch();
        $m_hero_pk = $this->PgGame->row['m_hero_pk'];
        $level = $this->PgGame->row['level'];
        $m_hero_base_pk = $_M['HERO'][$m_hero_pk]['m_hero_base_pk'];

        $hero_name = $_M['HERO_BASE'][$m_hero_base_pk]['name']. ' (Lv.'. $level. ')';

        // 보고서
        $z_content = [];

        $z_content['regist_dt'] = $regist_dt;
        $z_content['end_dt'] = time();

        // from & to
        $z_from = ['posi_pk' => $posi_pk, 'posi_name' => $this->Troop->getPositionName($posi_pk)];
        $z_to = ['lord_name' => $hero_name];

        // title & summary
        $z_title = '';
        $z_summary = $hero_name;

        $this->Report->setReport($lord_pk, 'misc', 'hero_treatment_finish', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));
        // Log
        $this->classLog();
        $this->Log->setBuildingMedical($lord_pk, $posi_pk, 'InjuryHeroTreatmentFinish', $hero_pk);

        $this->Session->sqAppend('PUSH', ['INJURY_HERO_TREATMENT_FINISH' => true], null, $lord_pk, $posi_pk);

        return true;
    }

    // 의료원 파괴
    function doDemolish($_posi_pk): true
    {
        // CTimer
        $this->classTimer();

        // 부상병과 전체 삭제
        $this->deadAllInjuryArmy($_posi_pk);

        // 부상영웅 추출 (치료중)
        $this->PgGame->query('SELECT time_pk FROM medical_hero WHERE posi_pk = $1 AND status = $2', [$_posi_pk, 'T']);

        while ($this->PgGame->fetch()) {
            $time_pk = $this->PgGame->row['time_pk'];

            // 타이머 취소
            $this->Timer->cancel($time_pk);
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $time_pk);
        }

        // 부상영웅 상태변경
        $this->PgGame->query('UPDATE medical_hero SET status = $1, time_pk = $2 WHERE posi_pk = $3 AND status = $4', ['W', null, $_posi_pk, 'T']);

        return true;
    }

    // 영지 상실 시 부상영웅 처리
    function doMoveMedicalHero($_posi_pk, $_lord_pk, $_main_posi_pk): void
    {
        if ($_main_posi_pk) {
            $this->PgGame->query('UPDATE medical_hero SET posi_pk = $2 WHERE posi_pk = $1', [$_posi_pk, $_main_posi_pk]);
        } else {
            // 없을 경우는 방랑영주, 방랑영주가 됐을때는 치료 완료되고(삭제)
            $this->PgGame->query('DELETE FROM medical_hero WHERE posi_pk = $1', [$_posi_pk]);
        }
    }

    // 전체 부상병 사망 처리
    function deadAllInjuryArmy($_posi_pk): true
    {
        // 병력 포인트 재계산
        $this->PgGame->query('UPDATE medical_army SET		
worker = 0,infantry = 0,pikeman = 0,scout = 0, spearman = 0,
armed_infantry = 0, archer = 0, horseman = 0, armed_horseman = 0, transporter = 0, bowman = 0,
battering_ram = 0, catapult = 0, adv_catapult = 0, last_update_dt = now()
WHERE posi_pk = $1', [$_posi_pk]);

        $this->getInjuryArmy($_posi_pk);

        return true;
    }

    // 부상병 치료 (신규)
    function treatmentPre($_posi_pk, $_in_cast_pk, $_code, $_number, $_hero_pk): false|array
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['army', 'condition']);

        $this->classResource();
        $this->classGoldPop();

        if (!$_code) {
            $NsGlobal->setErrorMessage('Error Occurred. [22002]'); // 병력 정보가 없습니다.
            return false;
        }

        $this->PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND type = $2', [$_posi_pk, 'M']);
        $r = $this->PgGame->fetchOne();
        if (!$r) {
            $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, '200700']);
            $buil_level = $this->PgGame->fetchOne();

            // buil_pk 가 존재하지 않으면 부상병 넣기전에 만들어줘야함.
            $this->PgGame->query('INSERT INTO build (posi_pk, in_cast_pk, status, type, concurr_curr, concurr_max, queue_curr, queue_max, regist_dt, last_update_dt) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, now(), now())', [$_posi_pk, 13, 'I', 'M', 0, 1, 0, $buil_level-1]);
        }

        // 현재 정보
        $this->PgGame->query('SELECT '. $_code. ' FROM medical_army WHERE posi_pk = $1', [$_posi_pk]);
        $number = $this->PgGame->fetchOne();

        if ($this->PgGame->getNumRows() == 0) {
            $NsGlobal->setErrorMessage('Error Occurred. [22003]'); // 치료 불가
            return false;
        } else {
            $m_army_pk = $_M['ARMY_C'][$_code]['m_army_pk'];
            if (!$m_army_pk) {
                $NsGlobal->setErrorMessage('Error Occurred. [22004]'); // 정보가 없습니다.
                return false;
            }

            $b = ['m_army_pk' => $m_army_pk, 'build_number' => $_number, 'code' => $_code];
        }

        if ($number - $_number < 0) {
            $NsGlobal->setErrorMessage('Error Occurred. [22005]'); // 치료할 가능한 범위가 아닙니다.
            return false;
        }

        // 1. 현재 level1 demolish일 경우인가?
        $this->PgGame->query('SELECT status, level from building_in_castle WHERE posi_pk = $1 AND in_castle_pk = $2', [$_posi_pk, $_in_cast_pk]);
        if (! $this->PgGame->fetch()) {
            $NsGlobal->setErrorMessage('Error Occurred. [22006]'); // 다운그레이드 중 오류가 발생했습니다.
            return false;
        }
        $building = $this->PgGame->row;
        if($building['level'] == 1 && $building['status'] == 'D') {
            $NsGlobal->setErrorMessage('Error Occurred. [22007]'); // 다운그레이드 중에는 치료가 불가능합니다.
            return false;
        }

        // 1.1. 진행 가능한 상태 인가?
        $this->PgGame->query('SELECT buil_pk, concurr_curr, concurr_max, queue_curr, queue_max FROM build WHERE posi_pk = $1 AND in_cast_pk =  $2', [$_posi_pk, $_in_cast_pk]);
        if ($this->PgGame->fetch()) {
            $build = $this->PgGame->row;
        } else {
            $NsGlobal->setErrorMessage('Error Occurred. [22008]'); // 치료 불가
            return false;
        }

        // 현재 build가능 한것 검사가 아닌 큐 검사
        /*if ($build['queue_curr'] >= $build['queue_max'] && $building['level'] > 1) {
            $NsGlobal->setErrorMessage('치료 대기가 가득 찼습니다.');
            return false;
        } else if ($building['level'] == 1) {
            if ($build['concurr_curr'] >= $build['concurr_max']) {
                $NsGlobal->setErrorMessage('치료 대기가 불가능합니다.');
                return false;
            }
        }*/
        if ($build['concurr_curr'] >= $build['concurr_max']) {
            $NsGlobal->setErrorMessage($i18n->t('msg_already_medical_injury')); // 이미 치료 중인 부상병이 있습니다.
            return false;
        }

        // 2. m_condition 적용
        $m_medi_cond_pk = &$_M['ARMY'][$b['m_army_pk']]['m_medi_cond_pk'];

        $this->classCondition();
        $ret = $this->Condition->conditionCheck($_posi_pk, $m_medi_cond_pk, $_in_cast_pk, 'I', $_hero_pk);
        if (!$ret) {
            return false;
        }

        // 3. 영지 당 보유 병력수 제한
        $this->classArmy();
        if ($this->Army->getPositionArmy($_posi_pk) + $b['build_number'] > TROOP_ARMY_LIMIT) {
            $NsGlobal->setErrorMessage($i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])); // 영지당 보유할 수 있는 총 병력수를 {{1}}으로 제한되어 있습니다.
            return false;
        }

        //트랜잭션
        try {
            $this->PgGame->begin();

            // 3.4. 자원소모 (갱신 필요)
            $res = [
                'food' => (INT)$_M['COND'][$m_medi_cond_pk]['build_food'] * $b['build_number'],
                'horse' => (INT)$_M['COND'][$m_medi_cond_pk]['build_horse'] * $b['build_number'],
                'lumber' => (INT)$_M['COND'][$m_medi_cond_pk]['build_lumber'] * $b['build_number'],
                'iron' => (INT)$_M['COND'][$m_medi_cond_pk]['build_iron'] * $b['build_number'],
            ];
            $r = $this->Resource->decrease($_posi_pk, $res, null, 'army_pre');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_lack')); // 자원이 부족합니다.
            }

            // gold 소모
            $build_gold = $_M['COND'][$m_medi_cond_pk]['build_gold'] * $b['build_number'];

            $r = $this->GoldPop->decreaseGold($_posi_pk, $build_gold, null, 'army_pre');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
            }

            $this->PgGame->commit();
        } catch (Exception $e){
            // 실패, sq 무시
            $this->PgGame->rollback();

            // 에러 메시지 추가
            $NsGlobal->setErrorMessage($e->getMessage());

            //dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$_posi_pk.'];');

            return false;
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $NsGlobal->commitComplete();

        $this->getInjuryArmy($_posi_pk);

        // 빌드 등록 (갱신 필요)
        return [
            'code' => $_code,
            'm_army_pk' => $b['m_army_pk'],
            'build_number' => $b['build_number'],
            'buil_pk' => $build['buil_pk'],
            'build_time' => $_M['COND'][$m_medi_cond_pk]['build_time'] * $b['build_number'],
            'build_status' => ($build['concurr_curr'] >= $build['concurr_max']) ? 'I' : 'P'
        ];
    }

    function upgradePost($_posi_pk, $_code, $_build_number, $_buil_army_pk, $_time_pk = null): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army', 'condition']);

        //인구 증가 시켜주기
        $this->PgGame->query('SELECT '. $_code . ' FROM army WHERE posi_pk = $1', [$_posi_pk]);
        $curr_army = $this->PgGame->fetchOne();

        $r = $this->PgGame->query("UPDATE army SET $_code = $_code + $_build_number WHERE posi_pk = $1", [$_posi_pk]);
        if (!$r || $this->PgGame->getAffectedRows() == 0) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '병력증가 실패;posi_pk['.$_posi_pk.'];code['.$_code.'];build_number['.$_build_number,'];');
            return false;
        }

        // 병력 포인트 재계산
        $this->classArmy();
        $this->Army->calcArmyPoint();

        // 로그
        $this->classLog();
        $this->PgGame->query('SELECT buil_pk, m_army_pk, status, build_number, regist_dt, start_dt, build_time, build_time_reduce, end_dt FROM build_medical WHERE buil_medi_pk = $1', [$_buil_army_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        $log_description = "{$row['m_army_pk']}[curr[$curr_army];update[$_build_number];];";
        // 삭제
        $this->PgGame->query('DELETE FROM build_medical WHERE buil_medi_pk = $1', [$_buil_army_pk]);

        $this->Log->setArmy($this->Session->lord['lord_pk'], $_posi_pk, 'medical_complete', $log_description, $_buil_army_pk, $row['buil_pk'], $row['m_army_pk'], $row['regist_dt'], $row['start_dt'], $row['build_time'], $row['build_time_reduce'], $row['build_number'], $_time_pk);

        return true;
    }

    function cancel($_posi_pk, $_m_army_pk, $_build_number, $_buil_medi_pk = null, $_time_pk = null): true
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army', 'condition']);

        // 3. m_condition 적용
        $m_cond_medi_pk = &$_M['ARMY'][$_m_army_pk]['m_cond_medi_pk'];
        $code = &$_M['ARMY'][$_m_army_pk]['code'];

        // 자원 롤백
        $res = [
            'food' => intval($_M['COND'][$m_cond_medi_pk]['build_food']*0.3) * $_build_number,
            'horse' => intval($_M['COND'][$m_cond_medi_pk]['build_horse']*0.3) * $_build_number,
            'lumber' => intval($_M['COND'][$m_cond_medi_pk]['build_lumber']*0.3) * $_build_number,
            'iron' => intval($_M['COND'][$m_cond_medi_pk]['build_iron']*0.3) * $_build_number,
        ];

        $this->classResource();
        $this->classGoldPop();

        $r = $this->Resource->increase($_posi_pk, $res, null, 'medi_cancel');
        if (!$r) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '자원증가실패;posi_pk['.$_posi_pk.'];food['.$res['food'].'];horse['.$res['horse'].'];lumber['.$res['lumber'].'];iron['.$res['iron'].'];');
        }

        $build_gold = intval($_M['COND'][$m_cond_medi_pk]['build_gold']*0.3) * $_build_number;

        $r = $this->GoldPop->increaseGold($_posi_pk, $build_gold, null, 'medi_cancel');
        if (!$r) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '황금증가실패;posi_pk['.$_posi_pk.'];gold['.$_M['COND'][$m_cond_medi_pk]['demolish_gold'].']');
        }

        $population = $_M['ARMY'][$_m_army_pk]['need_population'] * $_build_number;
        $r = $this->GoldPop->increasePopulation($_posi_pk, $population);
        if (!$r) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '인구증가실패;posi_pk['.$_posi_pk.'];population['.$_M['ARMY'][$_m_army_pk]['need_population'].']');
        }

        // 부상병 롤백
        $r = $this->PgGame->query("UPDATE medical_army SET $code = $code + $2 WHERE posi_pk = $1", [$_posi_pk, $_build_number]);
        if (!$r) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '부상병증가실패;posi_pk['.$_posi_pk.'];code['.$code.'];build_number['.$_build_number.'];');
        }

        $this->getInjuryArmy($_posi_pk);

        // Log
        $this->classLog();
        $this->PgGame->query('SELECT buil_pk, m_army_pk, status, build_number, regist_dt, start_dt, build_time, build_time_reduce, end_dt FROM build_medical WHERE buil_medi_pk = $1', [$_buil_medi_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        // 삭제
        $this->PgGame->query('DELETE FROM build_medical WHERE buil_medi_pk = $1', [$_buil_medi_pk]);

        // 의료원 치료 로그
        $this->Log->setArmy($this->Session->lord['lord_pk'], $_posi_pk, 'medical_cancel', $_M['ARMY'][$_m_army_pk]['title'], $_buil_medi_pk, $row['buil_pk'], $_m_army_pk, $row['regist_dt'], $row['start_dt'], $row['build_time'], $row['build_time_reduce'], $_build_number, $_time_pk);

        return true;
    }

    // 부상병 해산 처리 (신규)
    function disperse($_posi_pk, $_code, $_disperse_number): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army', 'condition']);

        $this->classResource();
        $this->classGoldPop();

        // 3. m_condition 적용
        $m_army_pk = $_M['ARMY_C'][$_code]['m_army_pk'];
        $m_medi_cond_pk = &$_M['ARMY'][$m_army_pk]['m_medi_cond_pk'];

        // 각종 수치 환급

        // 자원 롤백
        $res = [
            'food' => intval($_M['COND'][$m_medi_cond_pk]['demolish_food']) * $_disperse_number,
            'horse' => intval($_M['COND'][$m_medi_cond_pk]['demolish_horse']) * $_disperse_number,
            'lumber' => intval($_M['COND'][$m_medi_cond_pk]['demolish_lumber']) * $_disperse_number,
            'iron' => intval($_M['COND'][$m_medi_cond_pk]['demolish_iron']) * $_disperse_number,
        ];

        try {
            $this->PgGame->begin();

            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            $r = $this->Resource->increase($_posi_pk, $res, null, 'army_disperse');
            if (!$r) {
                throw new Exception('Error Occurred. [22009]'); // 자원 증가에 실패하였습니다.
            }

            $build_gold = intval($_M['COND'][$m_medi_cond_pk]['demolish_gold']) * $_disperse_number;

            $r = $this->GoldPop->increaseGold($_posi_pk, $build_gold, null, 'army_disperse');
            if (!$r) {
                throw new Exception('Error Occurred. [22010]'); // 황금 증가에 실패하였습니다.
            }

            $population = $_M['ARMY'][$m_army_pk]['need_population'];
            $r = $this->GoldPop->increasePopulation($_posi_pk, $population * $_disperse_number);
            if (!$r) {
                throw new Exception('Error Occurred. [22011]'); // 인구 증가에 실패하였습니다.
            }

            // 병력 감소시키기
            $this->PgGame->query('SELECT '. $_code . ' FROM medical_army WHERE posi_pk = $1', [$_posi_pk]);
            $curr_army = $this->PgGame->fetchOne();
            if ($curr_army < $_disperse_number) {
                throw new Exception('Error Occurred. [22012]'); // 해산하려는 병력이 보유 부상병보다 많습니다.
            }

            $r = $this->PgGame->query('UPDATE medical_army SET '. $_code. ' = '. $_code. ' - ' . $_disperse_number . ' WHERE posi_pk = $1', [$_posi_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new Exception('Error Occurred. [22013]'); // 병력 해산에 실패하였습니다.
            }

            $this->PgGame->commit();
        } catch (Exception $e) {
            // 실패, sq 무시
            $this->PgGame->rollback();

            // 금, 자원, 인구 rollback
            //qbw_rollback_complete();

            // 에러 메시지 추가
            $NsGlobal->setErrorMessage($e->getMessage());

            //dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$_posi_pk.'];');

            return false;
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        $this->getInjuryArmy($_posi_pk);

        // 로그
        $this->classLog();
        $log_description = "{$m_army_pk}[curr[$curr_army];update[$_disperse_number];];";
        $this->Log->setArmy(null, $_posi_pk, 'injury_disperse', $log_description, null, null, $m_army_pk, null, null, null, null, $_disperse_number);

        return true;
    }

    function now($_posi_pk, $_in_cast_pk, $_code, $_number): bool
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['army', 'condition']);

        if (!$_code) {
            $NsGlobal->setErrorMessage('Error Occurred. [22014]'); // 병력 정보가 없습니다.
            return false;
        }

        $this->PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND type = $2', [$_posi_pk, 'M']);
        $r = $this->PgGame->fetchOne();
        if (!$r) {
            $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, '200700']);
            $buil_level = $this->PgGame->fetchOne();

            // buil_pk 가 존재하지 않으면 부상병 넣기전에 만들어줘야함.
            $this->PgGame->query('INSERT INTO build (posi_pk, in_cast_pk, status, type, concurr_curr, concurr_max, queue_curr, queue_max, regist_dt, last_update_dt) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, now(), now())', [$_posi_pk, 13, 'I', 'M', 0, 1, 0, $buil_level-1]);
        }

        // 현재 정보
        $this->PgGame->query('SELECT '. $_code. ' FROM medical_army WHERE posi_pk = $1', [$_posi_pk]);
        $number = $this->PgGame->fetchOne();

        if ($this->PgGame->getNumRows() == 0) {
            $NsGlobal->setErrorMessage('Error Occurred. [22015]'); // 치료 불가
            return false;
        } else {
            $m_army_pk = $_M['ARMY_C'][$_code]['m_army_pk'];
            if (!$m_army_pk) {
                $NsGlobal->setErrorMessage('Error Occurred. [22016]'); // 정보가 없습니다.
                return false;
            }

            $b = ['m_army_pk' => $m_army_pk, 'build_number' => $_number, 'code' => $_code];
        }

        if ($number - $_number < 0) {
            $NsGlobal->setErrorMessage('Error Occurred. [22017]'); // 치료할 가능한 범위가 아닙니다.
            return false;
        }

        // 1. 현재 level1 demolish일 경우인가?
        $this->PgGame->query('SELECT status, level from building_in_castle WHERE posi_pk = $1 AND in_castle_pk = $2', [$_posi_pk, $_in_cast_pk]);
        if (! $this->PgGame->fetch()) {
            $NsGlobal->setErrorMessage('Error Occurred. [22018]'); // 치료 중 오류가 발생했습니다.
            return false;
        }

        $building = $this->PgGame->row;
        if($building['level'] == 1 && $building['status'] == 'D') {
            $NsGlobal->setErrorMessage('Error Occurred. [22019]'); // 다운그레이드 중에는 치료가 불가능합니다.
            return false;
        }

        // 2. m_condition 적용
        $m_medi_cond_pk = &$_M['ARMY'][$b['m_army_pk']]['m_medi_cond_pk'];

        $this->classCondition();
        $ret = $this->Condition->conditionCheck($_posi_pk, $m_medi_cond_pk, $_in_cast_pk, 'I', null, true);
        if (!$ret) {
            return false;
        }

        // 3. 영지 당 보유 병력수 제한
        $this->classArmy();
        if ($this->Army->getPositionArmy($_posi_pk) + $b['build_number'] > TROOP_ARMY_LIMIT) {
            $NsGlobal->setErrorMessage($i18n->t('msg_territory_army_limit', [TROOP_ARMY_LIMIT])); // 영지당 보유할 수 있는 총 병력수를 {{1}}으로 제한되어 있습니다.
            return false;
        }

        $this->classResource();
        $this->classGoldPop();

        try {
            $this->PgGame->begin();
            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            // 3.4. 자원소모 (갱신 필요)
            $res = [
                'food' => intval($_M['COND'][$m_medi_cond_pk]['build_food']) * $b['build_number'],
                'horse' => intval($_M['COND'][$m_medi_cond_pk]['build_horse']) * $b['build_number'],
                'lumber' => intval($_M['COND'][$m_medi_cond_pk]['build_lumber']) * $b['build_number'],
                'iron' => intval($_M['COND'][$m_medi_cond_pk]['build_iron']) * $b['build_number'],
            ];

            $r = $this->Resource->decrease($_posi_pk, $res, null, 'army_pre');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_lack')); // 자원이 부족합니다.
            }

            //gold소모
            $build_gold = $_M['COND'][$m_medi_cond_pk]['build_gold'] * $b['build_number'];

            $r = $this->GoldPop->decreaseGold($_posi_pk, $build_gold, null, 'army_pre');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
            }

            // 즉시 치료에 의한 큐빅 소모
            $build_time = $_M['COND'][$m_medi_cond_pk]['build_time'] * $b['build_number'];
            $remain_qbig = Useful::getNeedQbig($build_time);

            $this->classCash();
            $qbig = $this->Cash->decreaseCash($this->Session->lord['lord_pk'], $remain_qbig, 'medical now');
            if (!$qbig) {
                throw new Exception($i18n->t('msg_qbig_lack')); // 큐빅이 부족합니다.
            }

            // 의료원 병력 빼기
            $ret = $this->PgGame->query('UPDATE medical_army SET '.$_code.' = '.$_code.' - $2 WHERE posi_pk = $1', [$_posi_pk, $b['build_number']]);
            if (!$ret) {
                throw new Exception('Error Occurred. [22020]'); // 오류 발생
            }

            // 영지 병력 추가하기 TODO 로그?
            $r = $this->PgGame->query('UPDATE army SET '. $_code. ' = '. $_code. '+ ' . $b['build_number'] . ' WHERE posi_pk = $1', [$_posi_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '병력증가 실패;posi_pk['.$_posi_pk.'];code['.$_code.'];build_number['.$b['build_number'],'];');
                return false;
            }

            // 병력 포인트 재계산
            $this->Army->calcArmyPoint();

            $this->PgGame->commit();
        } catch (Exception $e){
            // 실패, sq 무시
            $this->PgGame->rollback();
            $NsGlobal->setErrorMessage($e->getMessage());
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$_posi_pk.'];');
            return false;
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        $this->classQuest();
        $this->Quest->countCheckQuest($this->Session->lord['lord_pk'], 'EVENT_ARMY_CURE', ['value' => $b['build_number']]);

        // 후처리 Push
        $this->getInjuryArmy($_posi_pk);
        $this->Army->get($_posi_pk, [$_code]);
        $this->classBuildMedical();
        $this->Session->sqAppend('BUIL_IN_CAST', [$_in_cast_pk => ['current' => $this->BuildMedical->getCurrent($_posi_pk, $_in_cast_pk)]], null, $this->Session->lord['lord_pk'], $_posi_pk);

        return true;
    }
}