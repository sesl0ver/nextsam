<?php

class Army
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Resource $Resource;
    protected GoldPop $GoldPop;
    protected Cash $Cash;
    protected BuildArmy $BuildArmy;
    protected Condition $Condition;
    protected Quest $Quest;
    protected Timer $Timer;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame, Resource $_Resource, GoldPop $_GoldPop)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Resource = $_Resource;
        $this->GoldPop = $_GoldPop;
    }

    function classCondition(): void
    {
        if (! isset($this->Condition)) {
            $this->Condition = new Condition($this->Session, $this->PgGame);
        }
    }

    function classBuildArmy(): void
    {
        if (! isset($this->BuildArmy)) {
            $this->classTimer();
            $this->BuildArmy = new BuildArmy($this->Session, $this->PgGame, $this->Timer);
        }
    }

    function classQuest(): void
    {
        if (! isset($this->Quest)) {
            $this->Quest = new Quest($this->Session, $this->PgGame);
        }
    }

    function classTimer(): void
    {
        if (! isset($this->Timer)) {
            $this->Timer = new Timer($this->Session, $this->PgGame);
        }
    }

    function classCash(): void
    {
        if (! isset($this->Cash)) {
            $this->Cash = new Cash($this->Session, $this->PgGame);
        }
    }

    function classLog(): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function get($_posi_pk, $_codeArr = null, $_lord_pk = null): void
    {
        $z = ($_codeArr == null) ? '*' : implode(',', $_codeArr);
        $this->PgGame->query("SELECT $z FROM army WHERE posi_pk = $1", [$_posi_pk]);
        if ($this->PgGame->fetch()) {
            $army_list = [];
            foreach ($this->PgGame->row AS $k => $v) {
                $army_list[$k] = $v;
            }
            $this->Session->sqAppend('ARMY', $army_list, null, $_lord_pk, $_posi_pk);
        }
    }

    function upgradePre($_posi_pk, $_in_cast_pk, $_code, $_number): false|array
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['army', 'condition', 'building']);
        if (!$_code) {
            $NsGlobal->setErrorMessage('Error Occurred. [10001]'); // 병력 정보가 없습니다.
            return false;
        }

        // 현재 정보
        $this->PgGame->query('SELECT '. $_code. ' FROM army WHERE posi_pk = $1', [$_posi_pk]);
        $number = $this->PgGame->fetchOne(); // TODO 확인용인가?

        if ($this->PgGame->getNumRows() == 0) {
            $NsGlobal->setErrorMessage('Error Occurred. [10002]'); // 훈련불가
            return false;
        } else {
            $m_army_pk = $_M['ARMY_C'][$_code]['m_army_pk'];
            if (!$m_army_pk) {
                $NsGlobal->setErrorMessage('Error Occurred. [10003]'); // 정보가 없습니다.
                return false;
            }
            $b = ['m_army_pk' => $m_army_pk, 'build_number' => $_number, 'code' => $_code];
        }

        // 1. 현재 level1 demolish일 경우인가?
        $this->PgGame->query('SELECT status, level, assign_hero_pk from building_in_castle WHERE posi_pk = $1 AND in_castle_pk = $2', [$_posi_pk, $_in_cast_pk]);
        if (! $this->PgGame->fetch()) {
            $NsGlobal->setErrorMessage('Error Occurred. [10004]'); // 오류가 발생했습니다. 다시 시도하십시오.
            return false;
        }
        $building = $this->PgGame->row;
        if($building['level'] == 1) {
            if($building['status'] == 'D') {
                $NsGlobal->setErrorMessage('Error Occurred. [10005]'); // 다운그레이드 중에는 훈련이 불가능합니다.
                return false;
            }
        }

        // 훈련 가능한 최대치를 넘지 못함.
        $max_number = $_M['BUIL'][PK_BUILDING_ARMY]['level'][$building['level']]['variation_2'];
        if ($b['build_number'] > $max_number) {
            $b['build_number'] = $max_number;
        }

        // 1.1. 진행 가능한 상태 인가?
        $this->PgGame->query('SELECT buil_pk, concurr_curr, concurr_max, queue_curr, queue_max FROM build WHERE posi_pk = $1 AND in_cast_pk =  $2', [$_posi_pk, $_in_cast_pk]);
        if ($this->PgGame->fetch()) {
            $build = $this->PgGame->row;
        } else {
            $NsGlobal->setErrorMessage('Error Occurred. [10006]'); // 훈련불가
            return false;
        }

        // 현재 build 가능 한것 검사가 아닌 큐 검사
        /*if ($build['queue_curr'] >= $build['queue_max'] && $building['level'] > 1) {
            $NsGlobal->setErrorMessage('훈련대기가 가득 찼습니다.');
            return false;
        } else if ($building['level'] == 1) {
            if ($build['concurr_curr'] >= $build['concurr_max']) {
                $NsGlobal->setErrorMessage('훈련대기가 불가능합니다.');
                return false;
            }
        }*/
        // Queue 쌓이면 안되므로
        if ($build['concurr_curr'] >= $build['concurr_max']) {
            $NsGlobal->setErrorMessage($i18n->t('msg_already_performing_training')); // 이미 훈련을 진행 중인 훈련소 입니다.
            return false;
        }

        // 2. m_condition 적용
        $m_cond_pk = &$_M['ARMY'][$b['m_army_pk']]['m_cond_pk'];

        $this->classCondition();
        $ret = $this->Condition->conditionCheck($_posi_pk, $m_cond_pk, $_in_cast_pk, 'I', $building['assign_hero_pk']); // TODO hero_pk 변수 문제로 수정하였으니 차후 동작 확인 바람.
        if (!$ret) {
            return false;
        }

        // 3. 영지 당 보유 병력수 제한
        if ($this->getPositionArmy($_posi_pk) + $b['build_number'] > TROOP_ARMY_LIMIT) {
            $NsGlobal->setErrorMessage($i18n->t('msg_territory_army_limit', [TROOP_ARMY_LIMIT])); // 영지당 보유할 수 있는 총 병력수를 {{1}}으로 제한되어 있습니다.
            return false;
        }

        //트랜잭션
        try {
            $this->PgGame->begin();

            // 인구 검사
            $r = $this->GoldPop->get($_posi_pk);

            $number = $_M['COND'][$m_cond_pk]['need_population'] * $b['build_number'];

            if($number > $r['population_curr']) {
                throw new Exception($i18n->t('msg_need_population')); // 필요 주민수가 부족합니다.
            } else {
                $r = $this->GoldPop->decreasePopulation($_posi_pk, $number);
                if (!$r) {
                    throw new Exception($i18n->t('msg_need_population')); // 필요 주민수가 부족합니다.
                }
            }

            // 3.4. 자원소모 (갱신 필요)
            $res = [
                'food' => intval($_M['COND'][$m_cond_pk]['build_food']) * $b['build_number'],
                'horse' => intval($_M['COND'][$m_cond_pk]['build_horse']) * $b['build_number'],
                'lumber' => intval($_M['COND'][$m_cond_pk]['build_lumber']) * $b['build_number'],
                'iron' => intval($_M['COND'][$m_cond_pk]['build_iron']) * $b['build_number'],
            ];

            $r = $this->Resource->decrease($_posi_pk, $res, null, 'army_pre');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_lack')); // 자원이 부족합니다.
            }

            // gold 소모
            $build_gold = intval($_M['COND'][$m_cond_pk]['build_gold']) * $b['build_number'];
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
            // // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$_posi_pk.'];');

            return false;
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $NsGlobal->commitComplete();

        $build_status = ($build['concurr_curr'] >= $build['concurr_max']) ? 'I' : 'P';

        $this->classQuest();
        $this->Quest->conditionCheckQuest($this->Session->lord['lord_pk'], ['quest_type' => 'daily_army', 'm_ques_pk' => 600103]);

        // 빌드 등록 (갱신 필요)
        return [
            'code' => $_code,
            'm_army_pk' => $b['m_army_pk'],
            'build_number' => $b['build_number'],
            'buil_pk' => $build['buil_pk'],
            'build_time' => $_M['COND'][$m_cond_pk]['build_time'] * $b['build_number'],
            'build_status' => $build_status,
        ];
    }

    function upgradePost($_posi_pk, $_code, $_build_number, $_buil_army_pk, $_time_pk = null): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army', 'condition']);
        //인구 증가 시켜주기
        $this->PgGame->query("SELECT $_code FROM army WHERE posi_pk = $1", [$_posi_pk]);
        $curr_army = $this->PgGame->fetchOne();

        $r = $this->PgGame->query("UPDATE army SET $_code = $_code + $_build_number WHERE posi_pk = $1", [$_posi_pk]);
        if (!$r || $this->PgGame->getAffectedRows() == 0) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '병력증가 실패;posi_pk['.$_posi_pk.'];code['.$_code.'];build_number['.$_build_number,'];');
            return false;
        }

        // 병력 포인트 재계산
        $this->calcArmyPoint();

        // 로그
        $this->classLog();
        $this->PgGame->query('SELECT buil_pk, m_army_pk, status, build_number, regist_dt, start_dt, build_time, build_time_reduce, end_dt FROM build_army WHERE buil_army_pk = $1', [$_buil_army_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        // 삭제
        $this->PgGame->query('DELETE FROM build_army WHERE buil_army_pk = $1', [$_buil_army_pk]);
        $log_description = "{$row['m_army_pk']}[curr[$curr_army];update[$_build_number];];";
        $this->Log->setArmy($this->Session->lord['lord_pk'], $_posi_pk, 'training_complete', $log_description, $_buil_army_pk, $row['buil_pk'], $row['m_army_pk'], $row['regist_dt'], $row['start_dt'], $row['build_time'], $row['build_time_reduce'], $row['build_number'], $_time_pk);

        return true;
    }

    function cancel($_posi_pk, $_m_army_pk, $_build_number, $_buil_army_pk = null, $_time_pk = null): true
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army', 'condition']);

        // 3. m_condition 적용
        $m_cond_pk = &$_M['ARMY'][$_m_army_pk]['m_cond_pk'];

        // 자원 롤백
        $res = [
            'food' => intval($_M['COND'][$m_cond_pk]['build_food']*0.3) * $_build_number,
            'horse' => intval($_M['COND'][$m_cond_pk]['build_horse']*0.3) * $_build_number,
            'lumber' => intval($_M['COND'][$m_cond_pk]['build_lumber']*0.3) * $_build_number,
            'iron' => intval($_M['COND'][$m_cond_pk]['build_iron']*0.3) * $_build_number,
        ];

        $r = $this->Resource->increase($_posi_pk, $res, null, 'army_cancel');
        if (!$r) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '자원증가실패;posi_pk['.$_posi_pk.'];food['.$res['food'].'];horse['.$res['horse'].'];lumber['.$res['lumber'].'];iron['.$res['iron'].'];');
        }

        $build_gold = intval($_M['COND'][$m_cond_pk]['build_gold']*0.3) * $_build_number;

        $r = $this->GoldPop->increaseGold($_posi_pk, $build_gold, null, 'army_cancel');
        if (!$r) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '황금증가실패;posi_pk['.$_posi_pk.'];gold['.$_M['COND'][$m_cond_pk]['demolish_gold'].']');
        }

        $population = $_M['ARMY'][$_m_army_pk]['need_population'] * $_build_number;
        $r = $this->GoldPop->increasePopulation($_posi_pk, $population);
        if (!$r) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '인구증가실패;posi_pk['.$_posi_pk.'];population['.$_M['ARMY'][$_m_army_pk]['need_population'].']');
        }

        // Log
        $this->classLog();
        $this->PgGame->query('SELECT buil_pk, m_army_pk, status, build_number, regist_dt, start_dt, build_time, build_time_reduce, end_dt FROM build_army WHERE buil_army_pk = $1', [$_buil_army_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        // 삭제
        $this->PgGame->query('DELETE FROM build_army WHERE buil_army_pk = $1', [$_buil_army_pk]);

        $this->Log->setArmy($this->Session->lord['lord_pk'], $_posi_pk, 'cancel', $_M['ARMY'][$_m_army_pk]['title'], $_buil_army_pk, $row['buil_pk'], $_m_army_pk, $row['regist_dt'], $row['start_dt'], $row['build_time'], $row['build_time_reduce'], $_build_number, $_time_pk);

        return true;
    }

    // 공성병기 여부 판단
    function isSiege($_pk_or_code): bool
    {
        return in_array($_pk_or_code, ['battering_ram', 'catapult', 'adv_catapult', 410012, 410013, 410014]);
    }

    // 부대 생성용 병력 차출
    function useArmy($_posi_pk, $_army_arr, $_reason = ''): bool
    {
        global $_M_ARMY_C, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);

        $q_arr = [];
        $this->PgGame->query('SELECT * FROM army WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $currArmyArr = $this->PgGame->row;
        $log_description = '';

        foreach ($_army_arr AS $k => $v) {
            if ($v && $v < 0) {
                return false;
            }
            if ($v && $v > 0) {
                // 병력 유효 검사
                if ($currArmyArr[$k] - $v < 0 || $currArmyArr[$k] < 0) {
                    return false;
                }
                $q_arr[] = "$k = $k - $v";
                $log_description .= "{$_M_ARMY_C[$k]['m_army_pk']}[curr[{$currArmyArr[$k]}];update[$v];];";
            }
        }

        if (COUNT($q_arr) < 1) {
            return true;
        }

        // 병력 감소 시켜주기
        $q = implode(',', $q_arr);
        $r = $this->PgGame->query("UPDATE army SET $q WHERE posi_pk = $1", [$_posi_pk]);
        if (!$r || $this->PgGame->getAffectedRows() == 0) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '병력 감소 실패;update info['.$q.'];');
            return false;
        }

        if ($_reason !== '') {
            $log_description = "reason[$_reason];$log_description";
        }

        // 로그
        $this->classLog();
        $this->Log->setArmy(null, $_posi_pk, 'decrease_troop', $log_description);

        return true;
    }

    // 부대 병력 복귀
    function returnArmy($_posi_pk, $_army_arr, $_description = null, $_push = false): bool
    {
        global $_M_ARMY_C, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);

        $q_arr = [];
        $log_description = '';
        $this->PgGame->query('SELECT * FROM army WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $currArmyArr = $this->PgGame->row;
        foreach ($_army_arr AS $k => $v) {
            if ($v && $v > 0) {
                $q_arr[] = "$k = $k + $v";
                $log_description .= "{$_M_ARMY_C[$k]['m_army_pk']}[curr[{$currArmyArr[$k]}];update[$v];];";
            }
        }
        if (COUNT($q_arr) < 1) {
            return true;
        }

        // 병력 증가 시켜주기
        $q = implode(',', $q_arr);
        $r = $this->PgGame->query("UPDATE army SET $q WHERE posi_pk = $1", [$_posi_pk]);
        if (!$r || $this->PgGame->getAffectedRows() == 0) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '병력 증가 실패;update info['.$q.'];');
            return false;
        }

        // 병력 포인트 재계산
        $ret = $this->calcArmyPoint();
        if (!$ret) {
            return false;
        }

        // description 존재하면 이벤트
        $type = 'increase_troop';
        if ($_description !== null) {
            $type = $_description;
        }

        if ($_push && $this->Session->lord['lord_pk']) {
            $this->get($_posi_pk, null, $this->Session->lord['lord_pk']);
        }

        // 로그
        $this->classLog();
        $this->Log->setArmy(null, $_posi_pk, $type, $log_description); // TODO 병력 재편성 $_description 추가해야함.

        return true;
    }

    // 병력 포인트 재계산
    function calcArmyPoint(): bool
    {
        // 현재 세션에 등록된 lord가 가진 모든 영지의 army 테이블에서 병력 포인트를 계산하고 lord_point 테이블에 업데이트
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);

        $r = $this->PgGame->query("UPDATE lord_point SET
  army_point = ( SELECT
    SUM (({$_M['ARMY']['410001']['need_population']} * worker::bigint) +
      ({$_M['ARMY']['410002']['need_population']} * infantry::bigint) +
      ({$_M['ARMY']['410003']['need_population']} * pikeman::bigint) +
      ({$_M['ARMY']['410004']['need_population']} * spearman::bigint) +
      ({$_M['ARMY']['410005']['need_population']} * scout::bigint) +
      ({$_M['ARMY']['410006']['need_population']} * archer::bigint) +
      ({$_M['ARMY']['410007']['need_population']} * horseman::bigint) +
      ({$_M['ARMY']['410008']['need_population']} * transporter::bigint) +
      ({$_M['ARMY']['410009']['need_population']} * armed_infantry::bigint) +
      ({$_M['ARMY']['410010']['need_population']} * armed_horseman::bigint) +
      ({$_M['ARMY']['410011']['need_population']} * bowman::bigint) +
      ({$_M['ARMY']['410012']['need_population']} * battering_ram::bigint) +
      ({$_M['ARMY']['410013']['need_population']} * catapult::bigint) +
      ({$_M['ARMY']['410014']['need_population']} * adv_catapult::bigint))::bigint AS pop
  FROM position, army WHERE position.lord_pk = $1 AND position.posi_pk = army.posi_pk GROUP BY lord_pk )
WHERE lord_point.lord_pk = $1", [$this->Session->lord['lord_pk']]);
        if (!$r || $this->PgGame->getAffectedRows() == 0) {
            return false;
        }

        // 병력 포인트 퀘스트
        $this->PgGame->query('SELECT army_point FROM lord_point WHERE lord_pk = $1', [$this->Session->lord['lord_pk']]);
        $army_point = $this->PgGame->fetchOne();

        $this->classQuest();
        $this->Quest->conditionCheckQuest($this->Session->lord['lord_pk'], ['quest_type' => 'army_point','army_point' => $army_point]);

        // 병력 포인트 push
        $this->Session->sqAppend('PUSH', ['ARMY_POINT' => $army_point], null, $this->Session->lord['lord_pk']);

        return true;
    }

    // 병력 훈련 전체 취소
    function cancelArmy($_posi_pk): true
    {
        $this->PgGame->query('SELECT in_castle_pk FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_ARMY]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;
        foreach($rows AS $v) {
            $this->PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2', [$_posi_pk, $v['in_castle_pk']]);
            $buil_pk = $this->PgGame->FetchOne();
            if (!$buil_pk) {
                continue;
            }
            $this->PgGame->query('SELECT buil_army_pk FROM build_army WHERE buil_pk = $1 AND status = $2', [$buil_pk, 'P']);
            $queue_pk = $this->PgGame->FetchOne();
            if (!$queue_pk) {
                continue;
            }
            $this->PgGame->Query('SELECT time_pk FROM timer WHERE queue_type = $1 AND queue_pk = $2', ['A', $queue_pk]);
            $time_pk = $this->PgGame->FetchOne();
            if (!$time_pk) {
                continue;
            }

            $this->classTimer();
            $this->Timer->cancel($time_pk);

            $this->classBuildArmy();
            $this->BuildArmy->cancel($queue_pk);

            // 대기중인 queue 취소
            $this->PgGame->query('UPDATE build_army SET status = $2 WHERE buil_pk = $1 AND status = $3', [$buil_pk, 'C', 'I']);
            $this->PgGame->query('UPDATE build SET queue_curr = 0, status = $2 WHERE buil_pk = $1 AND queue_curr > 0', [$buil_pk, 'I']);
        }
        return true;
    }

    // 전체 병력 사망 처리
    function deadAllArmy($_posi_pk, $_description = null): bool
    {
        // 병력 포인트 재계산
        $this->PgGame->query('SELECT worker, infantry, pikeman, scout, spearman, armed_infantry, archer, horseman, armed_horseman, transporter, bowman, battering_ram, catapult, adv_catapult FROM army WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $curr_row = $this->PgGame->row;

        $ret = $this->PgGame->query('UPDATE army SET		
worker = 0,infantry = 0,pikeman = 0,scout = 0, spearman = 0, armed_infantry = 0, archer = 0, horseman = 0, armed_horseman = 0, transporter = 0, bowman = 0,
battering_ram = 0, catapult = 0, adv_catapult = 0, last_update_dt = now() WHERE posi_pk = $1', [$_posi_pk]);
        if (!$ret && $this->PgGame->getAffectedRows() != 1) {
            return false;
        }

        // 로그
        $this->classLog();
        $curr_army = 'curr[';
        foreach ($curr_row AS $k => $v) {
            $curr_army .= $k .'[' .$v .'];';
        }
        $ret = $this->calcArmyPoint();
        if (!$ret) {
            return false;
        }
        // description 존재하면 이벤트
        $type = (!$_description) ? 'decrease_army_all' : 'reshuffle_event_point';
        $this->Log->setArmy(null, $_posi_pk, $type, $curr_army.'];'.$_description);

        return true;
    }

    function disperse($_posi_pk, $_code, $_disperse_number): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army', 'condition']);

        // 3. m_condition 적용
        $m_army_pk = $_M['ARMY_C'][$_code]['m_army_pk'];
        $m_cond_pk = &$_M['ARMY'][$m_army_pk]['m_cond_pk'];

        // 자원 롤백
        $res = [
            'food' => intval($_M['COND'][$m_cond_pk]['demolish_food']) * $_disperse_number,
            'horse' => intval($_M['COND'][$m_cond_pk]['demolish_horse']) * $_disperse_number,
            'lumber' => intval($_M['COND'][$m_cond_pk]['demolish_lumber']) * $_disperse_number,
            'iron' => intval($_M['COND'][$m_cond_pk]['demolish_iron']) * $_disperse_number,
        ];

        try {
            $this->PgGame->begin();

            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            $r = $this->Resource->increase($_posi_pk, $res, null, 'army_disperse');
            if (!$r) {
                throw new Exception('Error Occurred. [10007]'); // 자원 증가에 실패
            }
            $build_gold = intval($_M['COND'][$m_cond_pk]['demolish_gold']) * $_disperse_number;

            $r = $this->GoldPop->increaseGold($_posi_pk, $build_gold, null, 'army_disperse');
            if (!$r) {
                throw new Exception('Error Occurred. [10008]'); // 황금 증가에 실패
            }

            $population = $_M['ARMY'][$m_army_pk]['need_population'];
            $r = $this->GoldPop->increasePopulation($_posi_pk, $population * $_disperse_number);
            if (!$r) {
                throw new Exception('Error Occurred. [10009]'); // 인구 증가에 실패
            }

            // 병력 감소시키기
            $this->PgGame->query("SELECT $_code FROM army WHERE posi_pk = $1", [$_posi_pk]);
            $curr_army = $this->PgGame->fetchOne();
            if ($curr_army < $_disperse_number) {
                throw new Exception('Error Occurred. [10010]'); // 해산하려는 병력이 보유병력보다 많습니다.
            }

            $r = $this->PgGame->query("UPDATE army SET $_code = $_code - $_disperse_number WHERE posi_pk = $1", [$_posi_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new Exception('Error Occurred. [10011]'); // 병력 감소에 실패
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

        // 병력 포인트 재계산
        $this->calcArmyPoint();

        $this->get($_posi_pk);

        // 로그
        $this->classLog();
        $log_description = "{$m_army_pk}[curr[$curr_army];update[$_disperse_number];];";
        $this->Log->setArmy(null, $_posi_pk, 'army_disperse', $log_description, null, null, $m_army_pk, null, null, null, null, $_disperse_number);

        return true;
    }

    function getPositionArmy($_posi_pk): int
    {
        // 영지 병력
        $this->PgGame->query('SELECT worker+infantry+pikeman+scout+spearman+armed_infantry+archer+horseman+armed_horseman+transporter+bowman+battering_ram+catapult+adv_catapult FROM army WHERE posi_pk = $1', [$_posi_pk]);
        $posi_army = $this->PgGame->fetchOne();

        // 주둔&이동중인 병력
        $this->PgGame->query('SELECT sum(army_worker+army_infantry+army_pikeman+army_scout+army_spearman+army_armed_infantry+army_archer+army_horseman+army_armed_horseman+army_transporter+army_bowman+army_battering_ram+army_catapult+army_adv_catapult) FROM troop WHERE src_posi_pk = $1', [$_posi_pk]);
        $posi_army += $this->PgGame->fetchOne();

        // 훈련 중인 병력수
        $this->PgGame->query('SELECT SUM(b.build_number) FROM build a, build_army b WHERE a.posi_pk = $1 AND a.type = $2 AND a.buil_pk = b.buil_pk', [$_posi_pk, 'A']);
        $posi_army += $this->PgGame->fetchOne();

        // 치료 중인 병력수
        $this->PgGame->query('SELECT SUM(b.build_number) FROM build a, build_medical b WHERE a.posi_pk = $1 AND a.type = $2 AND a.buil_pk = b.buil_pk', [$_posi_pk, 'M']);
        $posi_army += $this->PgGame->fetchOne();
        return $posi_army;
    }

    function now($_posi_pk, $_in_cast_pk, $_code, $_number): bool
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['army', 'condition']);

        if (!$_code) {
            $NsGlobal->setErrorMessage('Error Occurred. [10012]'); // 병력 정보가 없습니다.
            return false;
        }

        // 현재 정보
        $this->PgGame->query("SELECT $_code FROM army WHERE posi_pk = $1", [$_posi_pk]);
        $number = $this->PgGame->fetchOne(); // TODO 여기도 이러네...

        if ($this->PgGame->getNumRows() == 0) {
            $NsGlobal->setErrorMessage('Error Occurred. [10013]'); // 훈련불가
            return false;
        } else {
            $m_army_pk = $_M['ARMY_C'][$_code]['m_army_pk'];
            if (!$m_army_pk) {
                $NsGlobal->setErrorMessage('Error Occurred. [10014]'); // 정보가 없습니다.
                return false;
            }
            $b = ['m_army_pk' => $m_army_pk, 'build_number' => $_number, 'code' => $_code];
        }

        // 조건검사
        $this->PgGame->query('SELECT status, level from building_in_castle WHERE posi_pk = $1 AND in_castle_pk = $2', [$_posi_pk, $_in_cast_pk]);
        if (! $this->PgGame->fetch()) {
            $NsGlobal->setErrorMessage('Error Occurred. [10015]');
            return false;
        }
        $building = $this->PgGame->row;
        if($building['level'] == 1) {
            if($building['status'] == 'D') {
                $NsGlobal->setErrorMessage('Error Occurred. [10016]'); // 다운그레이드 중에는 훈련이 불가능
                return false;
            }
        }

        // Queue 체크 안함.

        // 2. m_condition 적용
        $m_cond_pk = &$_M['ARMY'][$b['m_army_pk']]['m_cond_pk'];

        $this->classCondition();
        $ret = $this->Condition->conditionCheck($_posi_pk, $m_cond_pk, $_in_cast_pk, 'I', null, true);
        if (!$ret) {
            return false;
        }

        // 3. 영지 당 보유 병력수 제한
        if ($this->getPositionArmy($_posi_pk) + $b['build_number'] > TROOP_ARMY_LIMIT) {
            $NsGlobal->setErrorMessage($i18n->t('msg_territory_army_limit', [TROOP_ARMY_LIMIT])); // 영지당 보유할 수 있는 총 병력수를 {{1}}으로 제한되어 있습니다.
            return false;
        }

        try {
            $this->PgGame->begin();
            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            // 인구 검사
            $r = $this->GoldPop->get($_posi_pk);

            $number = $_M['COND'][$m_cond_pk]['need_population'] * $b['build_number'];

            if($number > $r['population_curr']) {
                throw new Exception($i18n->t('msg_need_population')); // 필요 주민수가 부족
            } else {
                $r = $this->GoldPop->decreasePopulation($_posi_pk, $number);
                if (!$r) {
                    throw new Exception($i18n->t('msg_need_population')); // 필요 주민수가 부족
                }
            }

            // 3.4. 자원소모 (갱신 필요)
            $res = [
                'food' => intval($_M['COND'][$m_cond_pk]['build_food']) * $b['build_number'],
                'horse' => intval($_M['COND'][$m_cond_pk]['build_horse']) * $b['build_number'],
                'lumber' => intval($_M['COND'][$m_cond_pk]['build_lumber']) * $b['build_number'],
                'iron' => intval($_M['COND'][$m_cond_pk]['build_iron']) * $b['build_number'],
            ];
            $r = $this->Resource->decrease($_posi_pk, $res, null, 'army_pre');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_lack')); // 자원이 부족합니다.
            }

            // gold 소모
            $build_gold = intval($_M['COND'][$m_cond_pk]['build_gold']) * $b['build_number'];
            $r = $this->GoldPop->decreaseGold($_posi_pk, $build_gold, null, 'army_pre');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
            }

            // 즉시 훈련에 의한 큐빅 소모
            $build_time = $_M['COND'][$m_cond_pk]['build_time'] * $b['build_number'];
            $remain_qbig = Useful::getNeedQbig($build_time);

            $this->classCash();
            $qbig = $this->Cash->decreaseCash($this->Session->lord['lord_pk'], $remain_qbig, 'army now');
            if (!$qbig) {
                throw new Exception($i18n->t('msg_qbig_lack')); // 큐빅이 부족합니다.
            }

            $r = $this->PgGame->query("UPDATE army SET $_code = $_code + {$b['build_number']} WHERE posi_pk = $1", [$_posi_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '병력증가 실패;posi_pk['.$_posi_pk.'];code['.$_code.'];build_number['.$b['build_number'],'];');
                return false;
            }

            // 병력 포인트 재계산
            $this->calcArmyPoint();

            // 퀘스트 체크
            $this->classQuest();
            $this->Quest->conditionCheckQuest($this->Session->lord['lord_pk'], ['quest_type' => 'daily_army', 'm_ques_pk' => 600103]);
            $this->Quest->conditionCheckQuest($this->Session->lord['lord_pk'], ['quest_type' => 'army_recruit', 'army_code' => $_code, 'posi_pk' => $_posi_pk]);
            $this->Quest->countCheckQuest($this->Session->lord['lord_pk'], 'EVENT_TRAINING', ['value' => (INT)$b['build_number']]);

            $this->PgGame->commit();
        } catch (Exception $e){
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

        $this->Session->sqAppend('PUSH', ['PLAY_SOUND' => 'training_complete'], null, $this->Session->lord['lord_pk']);

        // 후처리 Push
        $this->get($_posi_pk, [$_code]);
        $this->classBuildArmy();
        $this->Session->sqAppend('BUIL_IN_CAST', [$_in_cast_pk => ['current' => $this->BuildArmy->getCurrent($_posi_pk, $_in_cast_pk)]], null, $this->Session->lord['lord_pk'], $_posi_pk);

        return true;
    }
}