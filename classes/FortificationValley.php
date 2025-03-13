<?php

class FortificationValley
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Resource $Resource;
    protected GoldPop $GoldPop;
    protected Timer $Timer;
    protected BuildFortification $BuildFortification;
    protected Territory $Terr;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame, Resource $Resource, GoldPop $GoldPop, Territory $Terr)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Resource = $Resource;
        $this->GoldPop = $GoldPop;
        $this->Terr = $Terr;
    }

    function classBuildFortification(): void
    {
        if (! isset($this->BuildFortification)) {
            $this->classTimer();
            $this->BuildFortification = new BuildFortification($this->Session, $this->PgGame, $this->Timer);
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

    function upgradePre($_main_posi_pk, $_posi_pk, $_code, $_number): false|array
    {
        global $NsGlobal, $_M, $i18n;
        $NsGlobal->requireMasterData(['fortification', 'condition']);

        if (! $_code) {
            $NsGlobal->setErrorMessage('Error Occurred. [16001]'); // 방어시설 정보가 없습니다.
            return false;
        }

        // 해당 영지에 이미 진행 중인 방어시설 설치가 있는지 체크
        $this->PgGame->query("SELECT time_pk FROM timer WHERE posi_pk = '$_main_posi_pk' AND description LIKE '$_posi_pk%'");
        $r = $this->PgGame->fetchOne();
        if (isset($r)) {
            $NsGlobal->setErrorMessage('Error Occurred. [16002]'); // 이미 방어시설을 설치 중인 자원지입니다.
            return false;
        }

        // 현재 정보
        $this->PgGame->query("SELECT $_code FROM fortification_valley WHERE posi_pk = $1", [$_posi_pk]);
        $number = $this->PgGame->fetchOne();

        if (!isset($number)) {
            // 새로 등록
            $r = $this->PgGame->query('INSERT INTO fortification_valley (posi_pk, last_update_dt) VALUES ($1, now())', [$_posi_pk]);
            if (!$r) {
                $NsGlobal->setErrorMessage('Error Occurred. [16003]'); // 오류가 발생했습니다. 다시 한번 시도하여 주십시오.
                return false;
            }

            $this->PgGame->query("SELECT $_code FROM fortification_valley WHERE posi_pk = $1", [$_posi_pk]);
            $number = $this->PgGame->fetchOne();
        }

        if ($this->PgGame->getNumRows() == 0) {
            $NsGlobal->setErrorMessage('Error Occurred. [16004]'); // 설치 불가
            return false;
        } else {
            $m_fort_pk = $_M['FORT_C'][$_code]['m_fort_pk'];
            if (!$m_fort_pk) {
                $NsGlobal->setErrorMessage('Error Occurred. [16005]'); // 정보가 없습니다.
                return false;
            }

            $b = ['m_fort_pk' => $m_fort_pk, 'build_number' => $_number, 'code' => $_code];
        }

        // 조건검사

        // 자신의 소유의 자원지 인가?
        $this->PgGame->query('SELECT posi_pk, type FROM position WHERE posi_pk = $1 AND lord_pk = $2', [$_posi_pk, $this->Session->lord['lord_pk']]);
        if ($this->PgGame->fetch()) {
            $position = $this->PgGame->row;
        } else {
            $NsGlobal->setErrorMessage('Error Occurred. [16006]'); // 자신의 소유지에만 설치할 수 있습니다.
            return false;
        }

        // 자원지 체크
        if (! in_array($position['type'], ['F', 'G', 'L', 'M', 'R'])) {
            $NsGlobal->setErrorMessage('Error Occurred. [16007]'); // 방어시설은 성벽 및 자원지에서 설치 가능합니다.
            return false;
        }

        // 1.1. 진행 가능한 상태 인가?
        $this->PgGame->query('SELECT buil_pk, concurr_curr, concurr_max, status FROM build WHERE posi_pk = $1 AND type =  $2', [$_main_posi_pk, 'W']);
        if ($this->PgGame->fetch()) {
            $build = $this->PgGame->row;
        } else {
            // build 가 없으면 생성 후 다시 정보를 가지고 옴.
            $r = $this->PgGame->query('INSERT INTO build (posi_pk, in_cast_pk, status, type, concurr_curr, concurr_max, queue_curr, queue_max, regist_dt, last_update_dt) VALUES ($1, 0, \'I\', \'W\', 0, 10, 0, 0, now(), now())', [$_main_posi_pk]);
            if (! $r) {
                $NsGlobal->setErrorMessage('Error Occurred. [16008]'); // 오류가 발생했습니다. 다시 한번 시도하여 주십시오.
                return false;
            }

            $this->PgGame->query('SELECT buil_pk, concurr_curr, concurr_max, status FROM build WHERE posi_pk = $1 AND type =  $2', [$_main_posi_pk, 'W']);
            if ($this->PgGame->fetch()) {
                $build = $this->PgGame->row;
            } else {
                // 다시 못 가져오면 설치불가
                $NsGlobal->setErrorMessage('Error Occurred. [16009]'); // 설치 불가
                return false;
            }
        }

        // 현재 build가능 한것 검사가 아닌 큐 검사
        if ($build['concurr_curr'] >= $build['concurr_max']) {
            $NsGlobal->setErrorMessage($i18n->t('msg_already_installed')); // 이미 설치중 입니다.
            return false;
        }

        // 2. m_condition 체크하지 않음.
        $m_cond_pk = &$_M['FORT'][$b['m_fort_pk']]['m_cond_pk'];

        // 설치 공간 체크
        if((MAX_VALLEY_FORT - $number) < ($_M['COND'][$m_cond_pk]['need_vacancy'] * $b['build_number'])) { // 최대 설치 공간. 레벨 10 기준.
            $NsGlobal->setErrorMessage($i18n->t('msg_not_enough_install_space')); // 설치할 공간이 부족합니다.
            return false;
        }

        //트랜잭션
        global $_NS_SQ_REFRESH_FLAG;
        try {
            $this->PgGame->begin();

            $_NS_SQ_REFRESH_FLAG = true;

            // 3.4. 자원소모 (갱신 필요)
            $res = [];
            $res['food'] = (INT)$_M['COND'][$m_cond_pk]['build_food'] * $b['build_number'];
            $res['horse'] = (INT)$_M['COND'][$m_cond_pk]['build_horse'] * $b['build_number'];
            $res['lumber'] = (INT)$_M['COND'][$m_cond_pk]['build_lumber'] * $b['build_number'];
            $res['iron'] = (INT)$_M['COND'][$m_cond_pk]['build_iron'] * $b['build_number'];

            $r = $this->Resource->decrease($_main_posi_pk, $res, null, 'fort_pre');
            if (! $r) {
                throw new Exception($i18n->t('msg_resource_lack')); // 자원이 부족합니다.
            }

            //gold소모
            $build_gold = (INT)$_M['COND'][$m_cond_pk]['build_gold'] * $b['build_number'];

            if ($build_gold && $build_gold > 0) {
                $r = $this->GoldPop->decreaseGold($_main_posi_pk, $build_gold, null, 'fort_pre');
                if (!$r) {
                    throw new Exception($i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
                }
            }

            $this->PgGame->commit();
        } catch (Exception $e) {
            // 실패, sq 무시
            $this->PgGame->rollback();

            // 에러 메시지 추가
            $NsGlobal->setErrorMessage($e->getMessage());

            //dubug_mesg남기기
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$_posi_pk.'];');

            return false;
        }

        // 빌드 등록 (갱신 필요)
        $result_arr = [];
        $result_arr['code'] = $_code;
        $result_arr['m_fort_pk'] = $b['m_fort_pk'];
        $result_arr['current_number'] = $number;
        $result_arr['build_number'] = $b['build_number'];
        $result_arr['buil_pk'] = $build['buil_pk'];
        $result_arr['build_time'] = $_M['COND'][$m_cond_pk]['build_time'] * $b['build_number'];
        $result_arr['build_status'] = $build['status'];

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        return $result_arr;
    }

    function upgradePost($_posi_pk, $_code, $_build_number, $_buil_fort_vall_pk, $_time_pk = null): bool
    {
        global $NsGlobal, $_M;
        $NsGlobal->requireMasterData(['fortification', 'condition']);

        $this->PgGame->query('SELECT '.$_code . ' FROM fortification_valley WHERE posi_pk = $1', [$_posi_pk]);
        $curr_fort = $this->PgGame->fetchOne();
        //인구 증가 시켜주기
        $r = $this->PgGame->query('UPDATE fortification_valley SET '. $_code. ' = '. $_code. ' + ' . $_build_number . ' WHERE posi_pk = $1', [$_posi_pk]);
        if (!$r || $this->PgGame->getAffectedRows() == 0) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'fortification_valley update failed.;posi_pk['.$_posi_pk.'];code['.$_code.'];build_number['.$_build_number.'];');
            return false;
        }

        // 로그
        $this->classLog();
        $description = $_code . '[curr['.$curr_fort.'];update['. $_build_number.'];];';
        $this->PgGame->query('SELECT buil_pk, m_fort_pk, status, build_number, regist_dt, start_dt, build_time, build_time_reduce, end_dt FROM build_fortification_valley WHERE buil_fort_vall_pk = $1', [$_buil_fort_vall_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;

        // 삭제
        $this->PgGame->query('DELETE FROM build_fortification_valley WHERE buil_fort_vall_pk = $1', [$_buil_fort_vall_pk]);

        $this->Log->setFortification($this->Session->lord['lord_pk'], $_posi_pk, 'complete', $description, $row['buil_pk'], $row['buil_pk'], $row['m_fort_pk'], $row['regist_dt'], $row['start_dt'], $row['build_time'], $row['build_time_reduce'], $row['build_number'], $_time_pk);

        return true;
    }

    function cancel($_posi_pk, $_m_fort_pk, $_build_number, $_buil_fort_vall_pk = null, $_time_pk = null): bool
    {
        global $NsGlobal, $_M;
        $NsGlobal->requireMasterData(['fortification', 'condition']);

        // 3. m_condition 적용
        $m_cond_pk = &$_M['FORT'][$_m_fort_pk]['m_cond_pk'];

        // 각종 수치 환급

        $b = $this->Terr->cancelusewallvacancy($_posi_pk, $_M['COND'][$m_cond_pk]['need_vacancy'] * $_build_number);
        if(!$b) {
            return false;
        }

        // 자원 롤백
        $res = [];
        $res['food'] = intval($_M['COND'][$m_cond_pk]['build_food']*0.3) * $_build_number;
        $res['horse'] = intval($_M['COND'][$m_cond_pk]['build_horse']*0.3) * $_build_number;
        $res['lumber'] = intval($_M['COND'][$m_cond_pk]['build_lumber']*0.3) * $_build_number;
        $res['iron'] = intval($_M['COND'][$m_cond_pk]['build_iron']*0.3) * $_build_number;

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
        $this->PgGame->query('SELECT buil_pk, m_fort_pk, status, build_number, regist_dt, start_dt, build_time, build_time_reduce, end_dt FROM build_fortification WHERE buil_fort_pk = $1', [$_buil_fort_vall_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        // 삭제
        $this->PgGame->query('DELETE FROM build_fortification WHERE buil_fort_pk = $1', [$_buil_fort_vall_pk]);

        $this->Log->setFortification($this->Session->lord['lord_pk'], $_posi_pk, 'cancel', $description, $_buil_fort_vall_pk, $row['buil_pk'], $row['m_fort_pk'], $row['regist_dt'], $row['start_dt'], $row['build_time'], $row['build_time_reduce'], $row['build_number'], $_time_pk);

        return true;
    }

    function cancelFortification($_posi_pk): bool
    {
        $this->PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND type = $2', [$_posi_pk, 'W']);
        $buil_pk = $this->PgGame->FetchOne();
        if (!$buil_pk) {
            return true;
        }

        $this->PgGame->query('SELECT buil_fort_vall_pk FROM build_fortification_valley WHERE buil_pk = $1 AND status = $2', [$buil_pk, 'P']);
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

    function disperse($_main_posi_pk, $_posi_pk, $_code, $_disperse_number): bool
    {
        global $NsGlobal, $_M;
        $NsGlobal->requireMasterData(['fortification', 'condition']);

        // 3. m_condition 적용
        $m_fort_pk = $_M['FORT_C'][$_code]['m_fort_pk'];
        $m_cond_pk = &$_M['FORT'][$m_fort_pk]['m_cond_pk'];

        // 각종 수치 환급

        // 자원 롤백
        $res = [];
        $res['food'] = intval($_M['COND'][$m_cond_pk]['demolish_food'])*$_disperse_number;
        $res['horse'] = intval($_M['COND'][$m_cond_pk]['demolish_horse']) * $_disperse_number;
        $res['lumber'] = intval($_M['COND'][$m_cond_pk]['demolish_lumber']) * $_disperse_number;
        $res['iron'] = intval($_M['COND'][$m_cond_pk]['demolish_iron']) * $_disperse_number;

        try {
            $this->PgGame->begin();

            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            $r = $this->Resource->increase($_main_posi_pk, $res, null, 'fort_disperse');
            if (!$r) {
                throw new Exception('Error Occurred. [16010]'); // 자원 증가에 실패하였습니다.
            }

            $build_gold = intval($_M['COND'][$m_cond_pk]['demolish_gold']) * $_disperse_number;

            $r = $this->GoldPop->increaseGold($_main_posi_pk, $build_gold, null, 'fort_disperse');
            if (!$r) {
                throw new Exception('Error Occurred. [16011]'); // 황금 증가에 실패하였습니다.
            }

            // 감소시키기
            $this->PgGame->query('SELECT '. $_code . ' FROM fortification_valley WHERE posi_pk = $1', [$_posi_pk]);
            $curr_fort = $this->PgGame->fetchOne();
            if ($curr_fort < $_disperse_number) {
                throw new Exception('Error Occurred. [16012]'); // 해체하려는 방어시설이 설치된 방어시설 보다 많습니다.
            }

            $r = $this->PgGame->query('UPDATE fortification_valley SET '. $_code. ' = '. $_code. ' - ' . $_disperse_number . ' WHERE posi_pk = $1', [$_posi_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new Exception('Error Occurred. [16013]'); // 방어시설 해체에 실패하였습니다.
            }

            $this->PgGame->commit();
        } catch (Exception $e) {
            // 실패, sq 무시
            $this->PgGame->rollback();

            // 금, 자원, 인구 rollback
            //qbw_rollback_complete();

            // 에러 메시지 추가
            $NsGlobal->setErrorMessage($e->getMessage());

            // dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$_posi_pk.'];');

            return false;
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        // $this->get($_posi_pk);

        // 로그
        $this->classLog();
        $this->Log->setFortification(null, $_posi_pk, 'desc_fort_disperse', $_M['FORT'][$m_fort_pk]['code'] . '[curr['.$curr_fort.'];update['. $_disperse_number.'];];', null, null, $m_fort_pk, null, null, null, null, $_disperse_number);

        return true;
    }
}