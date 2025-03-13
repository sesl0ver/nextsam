<?php

class Fortification
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Resource|null $Resource;
    protected GoldPop|null $GoldPop;
    protected Territory|null $Territory;
    protected BuildFortification $BuildFortification;
    protected Condition $Condition;
    protected Timer $Timer;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame, Resource $_Resource = null, GoldPop $_GoldPop = null, Territory $_Territory = null)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Resource = $_Resource;
        $this->GoldPop = $_GoldPop;
        $this->Territory = $_Territory;
    }

    function classBuildFortification(): void
    {
        if (! isset($this->BuildFortification)) {
            $this->classTimer();
            $this->BuildFortification = new BuildFortification($this->Session, $this->PgGame, $this->Timer);
        }
    }

    function classCondition(): void
    {
        if (! isset($this->Condition)) {
            $this->Condition = new Condition($this->Session, $this->PgGame);
        }
    }

    protected function classTimer (): void
    {
        if (! isset($this->Timer)) {
            $this->Timer = new Timer($this->Session, $this->PgGame);
        }
    }

    function classLog(): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function get($_posi_pk, $_code_arr = null, $_lord_pk = null): void
    {
        $z = ($_code_arr == null) ? '*' : implode(',', $_code_arr);
        $this->PgGame->query("SELECT $z FROM fortification WHERE posi_pk = $1", [$_posi_pk]);
        if ($this->PgGame->fetch()) {
            $forts = [];
            foreach ($this->PgGame->row AS $k => $v) {
                $forts[$k] = $v;
            }
            $this->Session->sqAppend('FORT', $forts, null, $_lord_pk, $_posi_pk);
        }
    }

    function upgradePre($_posi_pk, $_in_cast_pk, $_code, $_number, $_hero_pk): false|array
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['fortification', 'condition']);

        if (!$_code) {
            $NsGlobal->setErrorMessage('Error Occurred. [15001]'); // 방어시설 정보가 없습니다.
            return false;
        }
        // 현재 정보
        $this->PgGame->query("SELECT $_code FROM fortification WHERE posi_pk = $1", [$_posi_pk]);
        $number = $this->PgGame->fetchOne(); // TODO 음...
        if ($this->PgGame->getNumRows() == 0) {
            $NsGlobal->setErrorMessage('설치 불가');
            return false;
        } else {
            $m_fort_pk = $_M['FORT_C'][$_code]['m_fort_pk'];
            if (!$m_fort_pk) {
                $NsGlobal->setErrorMessage('Error Occurred. [15002]'); // 정보가 없습니다.
                return false;
            }
            $b = ['m_fort_pk' => $m_fort_pk, 'build_number' => $_number, 'code' => $_code];
        }

        // 1.1. 진행 가능한 상태 인가?
        $this->PgGame->query('SELECT buil_pk, concurr_curr, concurr_max, status FROM build WHERE posi_pk = $1 AND in_cast_pk =  $2', [$_posi_pk, $_in_cast_pk]);
        if ($this->PgGame->fetch()) {
            $build = $this->PgGame->row;
        } else {
            $NsGlobal->setErrorMessage('Error Occurred. [15003]'); // 설치 불가
            return false;
        }

        // 현재 build 가능 한것 검사가 아닌 큐 검사
        if ($build['concurr_curr'] >= $build['concurr_max']) {
            $NsGlobal->setErrorMessage($i18n->t('msg_already_installed')); // 이미 설치중 입니다.
            return false;
        }

        // 2. m_condition 적용
        $m_cond_pk = &$_M['FORT'][$b['m_fort_pk']]['m_cond_pk'];

        $this->classCondition();
        $ret = $this->Condition->conditionCheck($_posi_pk, $m_cond_pk, $_in_cast_pk, 'I', $_hero_pk);
        if (!$ret) {
            return false;
        }

        // 수송병기 공간 에러처리
        if(!$this->Territory->useWallVacancy($_posi_pk, $_M['COND'][$m_cond_pk]['need_vacancy'] * $b['build_number'])) {
            $NsGlobal->setErrorMessage($i18n->t('msg_not_enough_install_space')); // 설치할 공간이 부족합니다.
            return false;
        }

        //트랜잭션
        try {
            $this->PgGame->begin();

            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            // 자원소모 (갱신 필요)
            $res = [
                'food' => (INT)$_M['COND'][$m_cond_pk]['build_food'] * $b['build_number'],
                'horse' => (INT)$_M['COND'][$m_cond_pk]['build_horse'] * $b['build_number'],
                'lumber' => (INT)$_M['COND'][$m_cond_pk]['build_lumber'] * $b['build_number'],
                'iron' => (INT)$_M['COND'][$m_cond_pk]['build_iron'] * $b['build_number'],
            ];

            $r = $this->Resource->decrease($_posi_pk, $res, null, 'fort_pre');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_lack')); // 자원이 부족합니다.
            }

            // gold 소모
            $build_gold = (INT)$_M['COND'][$m_cond_pk]['build_gold'] * $b['build_number'];

            if ($build_gold && $build_gold > 0) {
                $r = $this->GoldPop->decreaseGold($_posi_pk, $build_gold, null, 'fort_pre');
                if (!$r) {
                    throw new Exception($i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
                }
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

        // 빌드 등록 (갱신 필요)
        return [
            'code' => $_code,
            'm_fort_pk' => $b['m_fort_pk'],
            'current_number' => $number,
            'build_number' => $b['build_number'],
            'buil_pk' => $build['buil_pk'],
            'build_time' => $_M['COND'][$m_cond_pk]['build_time'] * $b['build_number'],
            'build_status' => $build['status'],
        ];
    }

    function upgradePost($_posi_pk, $_code, $_build_number, $_buil_fort_pk, $_time_pk = null): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['fortification', 'condition']);

        $this->PgGame->query("SELECT $_code FROM fortification WHERE posi_pk = $1", [$_posi_pk]);
        $curr_fort = $this->PgGame->fetchOne();
        //인구 증가 시켜주기
        $r = $this->PgGame->query("UPDATE fortification SET $_code = $_code + $_build_number WHERE posi_pk = $1", [$_posi_pk]);
        if (!$r || $this->PgGame->getAffectedRows() == 0) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'fortification update failed.;posi_pk['.$_posi_pk.'];code['.$_code.'];build_number['.$_build_number.'];');
            return false;
        }

        // 로그
        $description = $_code . '[curr['.$curr_fort.'];update['. $_build_number.'];];';
        $this->PgGame->query('SELECT buil_pk, m_fort_pk, status, build_number, regist_dt, start_dt, build_time, build_time_reduce, end_dt FROM build_fortification WHERE buil_fort_pk = $1', [$_buil_fort_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        // 삭제
        $this->PgGame->query('DELETE FROM build_fortification WHERE buil_fort_pk = $1', [$_buil_fort_pk]);

        $this->classLog();
        $this->Log->setFortification($this->Session->lord['lord_pk'], $_posi_pk, 'complete', $description, $_buil_fort_pk, $row['buil_pk'], $row['m_fort_pk'], $row['regist_dt'], $row['start_dt'], $row['build_time'], $row['build_time_reduce'], $row['build_number'], $_time_pk);

        return true;
    }

    function cancel($_posi_pk, $_m_fort_pk, $_build_number, $_buil_fort_pk = null, $_time_pk = null): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['fortification', 'condition']);

        // 3. m_condition 적용
        $m_cond_pk = &$_M['FORT'][$_m_fort_pk]['m_cond_pk'];

        // 각종 수치 환급
        $b = $this->Territory->cancelUseWallVacancy($_posi_pk, $_M['COND'][$m_cond_pk]['need_vacancy'] * $_build_number);
        if(!$b) {
            return false;
        }

        // 자원 롤백
        $res = [
            'food' => intval($_M['COND'][$m_cond_pk]['build_food']*0.3) * $_build_number,
            'horse' => intval($_M['COND'][$m_cond_pk]['build_horse']*0.3) * $_build_number,
            'lumber' => intval($_M['COND'][$m_cond_pk]['build_lumber']*0.3) * $_build_number,
            'iron' => intval($_M['COND'][$m_cond_pk]['build_iron']*0.3) * $_build_number,
        ];
        $d = $this->Resource->increase($_posi_pk, $res, null, 'cancel_fort');
        if (!$d) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '자원증가실패;posi_pk['.$_posi_pk.'];food['.$res['food'].'];horse['.$res['horse'].'];lumber['.$res['lumber'].'];iron['.$res['iron'].'];');
        }

        $build_gold = intval($_M['COND'][$m_cond_pk]['build_gold']*0.3) * $_build_number;

        $r = $this->GoldPop->increaseGold($_posi_pk, $build_gold, null, 'cancel_fort');
        if (!$r) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '황금증가실패;posi_pk['.$_posi_pk.'];gold['.$build_gold.']');
        }

        // Log
        $this->classLog();
        $description = $_M['FORT'][$_m_fort_pk]['title'] . '[curr['.$b['curr_cnt'].'];after['. $b['update_cnt'].'];];';
        $this->PgGame->query('SELECT buil_pk, m_fort_pk, status, build_number, regist_dt, start_dt, build_time, build_time_reduce, end_dt FROM build_fortification WHERE buil_fort_pk = $1', [$_buil_fort_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        // 삭제
        $this->PgGame->query('DELETE FROM build_fortification WHERE buil_fort_pk = $1', [$_buil_fort_pk]);

        $this->Log->setFortification($this->Session->lord['lord_pk'], $_posi_pk, 'cancel', $_M['FORT'][$_m_fort_pk]['title'] . ';' . $description, $_buil_fort_pk, $row['buil_pk'], $row['m_fort_pk'], $row['regist_dt'], $row['start_dt'], $row['build_time'], $row['build_time_reduce'], $row['build_number'], $_time_pk);

        return true;
    }

    function cancelFortification($_posi_pk): bool
    {
        $this->PgGame->query('SELECT in_castle_pk FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_WALL]);
        $in_cast_pk = $this->PgGame->fetchOne();

        $this->PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2', [$_posi_pk, $in_cast_pk]);
        $buil_pk = $this->PgGame->FetchOne();
        if (!$buil_pk) {
            return true;
        }

        $this->PgGame->query('SELECT buil_fort_pk FROM build_fortification WHERE buil_pk = $1 AND status = $2', [$buil_pk, 'P']);
        $queue_pk = $this->PgGame->FetchOne();
        if (!$queue_pk) {
            return false;
        }

        $this->PgGame->Query('SELECT time_pk FROM timer WHERE queue_type = $1 AND queue_pk = $2', ['F', $queue_pk]);
        $time_pk = $this->PgGame->FetchOne();

        $this->classTimer();
        $this->Timer->cancel($time_pk);

        $this->classBuildFortification();
        $this->BuildFortification->cancel($queue_pk);

        return true;
    }

    function demolishAllFortification($_posi_pk): true
    {
        $this->PgGame->query('SELECT trap, abatis, tower FROM fortification WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $curr_row = $this->PgGame->row;

        $this->PgGame->query('UPDATE fortification SET trap = 0, abatis = 0, tower = 0, last_update_dt = now() WHERE posi_pk = $1', [$_posi_pk]);

        // 로그
        $this->classLog();
        $curr_row_log = 'curr[';
        foreach ($curr_row AS $k => $v) {
            $curr_row_log .= $k .'[' .$v .'];';
        }
        $curr_row_log .= ']';
        $this->Log->setFortification(null, $_posi_pk, 'desc_fort_deadAll', $curr_row_log);

        return true;
    }

    function disperse($_posi_pk, $_code, $_disperse_number): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['fortification', 'condition']);

        // 3. m_condition 적용
        $m_fort_pk = $_M['FORT_C'][$_code]['m_fort_pk'];
        $m_cond_pk = &$_M['FORT'][$m_fort_pk]['m_cond_pk'];

        // 각종 수치 환급

        // 자원 롤백
        $res = [
            'food' => intval($_M['COND'][$m_cond_pk]['demolish_food'])*$_disperse_number,
            'horse' => intval($_M['COND'][$m_cond_pk]['demolish_horse']) * $_disperse_number,
            'lumber' => intval($_M['COND'][$m_cond_pk]['demolish_lumber']) * $_disperse_number,
            'iron' => intval($_M['COND'][$m_cond_pk]['demolish_iron']) * $_disperse_number,
        ];

        try {
            $this->PgGame->begin();

            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            $r = $this->Resource->increase($_posi_pk, $res, null, 'fort_disperse');
            if (!$r) {
                throw new Exception('Error Occurred. [15004]'); // 자원 증가에 실패하였습니다.
            }

            $build_gold = intval($_M['COND'][$m_cond_pk]['demolish_gold']) * $_disperse_number;

            $r = $this->GoldPop->increaseGold($_posi_pk, $build_gold, null, 'fort_disperse');
            if (!$r) {
                throw new Exception('Error Occurred. [15005]'); // 황금 증가에 실패하였습니다.
            }

            // 설치공간
            $need_vacancy = $_M['COND'][$m_cond_pk]['need_vacancy'];
            $r = $this->PgGame->query('UPDATE territory set wall_vacancy_curr = wall_vacancy_curr - $2 WHERE posi_pk = $1', [$_posi_pk, $need_vacancy * $_disperse_number]);
            if (!$r) {
                throw new Exception('Error Occurred. [15006]'); // 설치공간 해제에 실패하였습니다.
            }

            $this->PgGame->query('SELECT wall_vacancy_curr FROM territory WHERE posi_pk = $1', [$_posi_pk]);
            $wall_vacancy_curr = $this->PgGame->fetchOne();
            $this->Session->sqAppend('TERR', ['wall_vacancy_curr' => $wall_vacancy_curr], null, $this->Session->lord['lord_pk'], $_posi_pk);

            // 병력 감소시키기
            $this->PgGame->query("SELECT $_code FROM fortification WHERE posi_pk = $1", [$_posi_pk]);
            $curr_fort = $this->PgGame->fetchOne();
            if ($curr_fort < $_disperse_number) {
                throw new Exception('Error Occurred. [15007]'); // 해체하려는 방어시설이 설치된 방어시설 보다 많습니다.
            }

            $r = $this->PgGame->query("UPDATE fortification SET $_code = $_code - $_disperse_number WHERE posi_pk = $1", [$_posi_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new Exception('Error Occurred. [15008]'); // 방어시설 해체에 실패하였습니다.
            }
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

        $this->PgGame->query('COMMIT');

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        $this->get($_posi_pk);

        // 로그
        $this->classLog();
        $this->Log->setFortification(null, $_posi_pk, 'desc_fort_disperse', $_M['FORT'][$m_fort_pk]['code'] . '[curr['.$curr_fort.'];update['. $_disperse_number.'];];', null, null, $m_fort_pk, null, null, null, null, $_disperse_number);

        return true;
    }

    function increase($_posi_pk, $_fort_arr, $_description, $_push = false): bool
    {
        $q_arr = [];
        $desc = '';
        $this->PgGame->query('SELECT trap, abatis, tower FROM fortification WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $curr_fort_arr = $this->PgGame->row;

        foreach ($_fort_arr AS $k => $v) {
            if ($v && $v > 0) {
                $q_arr[] = "$k = $k + $v";
                $desc .= $k . ';curr[' .$curr_fort_arr[$k].'];incr['. $v .'];';
            }
        }

        if (COUNT($q_arr) < 1) {
            return true;
        }

        $q = implode(',', $q_arr);

        $r = $this->PgGame->query('UPDATE fortification SET '. $q. ' WHERE posi_pk = $1', [$_posi_pk]);
        if (!$r || $this->PgGame->getAffectedRows() == 0)
        {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '수성병기 증가 실패;update info['.$q.'];');
            return false;
        }

        if ($_push) {
            if ($this->Session->lord['lord_pk']) {
                $this->get($_posi_pk);
            }
        }

        /*if ($_description) {
            // TODO 로그 남기기가 난감하네 (구조가...) - 왜 이런 주석이...
        }*/
        return true;
    }
}