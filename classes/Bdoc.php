<?php


// Building Out Castle
class Bdoc
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Resource $Resource;
    protected GoldPop $GoldPop;
    protected Timer $Timer;
    protected Condition $Condition;
    protected Item $Item;
    protected BuildConstruction $BuildConstruction;
    protected Hero $Hero;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame, Resource $_Resource, GoldPop $_GoldPop)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
        $this->Resource = $_Resource;
        $this->GoldPop = $_GoldPop;
    }

    function classTimer(): void
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

    function classBuildConstruction(): void
    {
        if (! isset($this->BuildConstruction)) {
            $this->BuildConstruction = new BuildConstruction($this->Session, $this->PgGame);
        }
    }

    function classHero(): void
    {
        if (! isset($this->Hero)) {
            $this->Hero = new Hero($this->Session, $this->PgGame);
        }
    }

    function classCondition(): void
    {
        if (! isset($this->Condition)) {
            $this->Condition = new Condition($this->Session, $this->PgGame);
        }
    }

    function classItem(): void
    {
        if (! isset($this->Item)) {
            $this->Item = new Item($this->Session, $this->PgGame);
        }
    }

    function getPositions($_posi_pk): bool
    {
        $castle_list = [];
        $this->PgGame->query('SELECT out_castle_pk, m_buil_pk, status, level, buil_hero_pk FROM building_out_castle WHERE posi_pk = $1', [$_posi_pk]);
        while($this->PgGame->fetch()) {
            $castle_list[$this->PgGame->row['out_castle_pk']]['m_buil_pk'] = (INT)$this->PgGame->row['m_buil_pk'];
            $castle_list[$this->PgGame->row['out_castle_pk']]['status'] = $this->PgGame->row['status'];
            $castle_list[$this->PgGame->row['out_castle_pk']]['level'] = (INT)$this->PgGame->row['level'];
            $castle_list[$this->PgGame->row['out_castle_pk']]['buil_hero_pk'] = (INT)$this->PgGame->row['buil_hero_pk'];
        }
        return $this->Session->sqAppend('BUIL_OUT_CAST', $castle_list, null, $this->Session->lord['lord_pk'], $_posi_pk);
    }

    function updatePosition($_posi_pk, $_castle_pk, $_set_null = false): bool
    {
        if ($_set_null) {
            return $this->Session->sqAppend('BUIL_OUT_CAST', [$_castle_pk => ['status' => 'NULL']], null, $this->Session->lord['lord_pk'], $_posi_pk);
        } else {
            $castle_list = [];
            $this->PgGame->query('SELECT m_buil_pk, status, level, buil_hero_pk FROM building_out_castle WHERE posi_pk = $1 AND out_castle_pk = $2', [$_posi_pk, $_castle_pk]);
            while($this->PgGame->fetch()) {
                $castle_list[$_castle_pk]['m_buil_pk'] = (INT)$this->PgGame->row['m_buil_pk'];
                $castle_list[$_castle_pk]['status'] = $this->PgGame->row['status'];
                $castle_list[$_castle_pk]['level'] = (INT)$this->PgGame->row['level'];
                $castle_list[$_castle_pk]['buil_hero_pk'] = (INT)$this->PgGame->row['buil_hero_pk'];
            }
            return $this->Session->sqAppend('BUIL_OUT_CAST', $castle_list, null, $this->Session->lord['lord_pk'], $_posi_pk);
        }
    }

    function upgradePre($_posi_pk, $_castle_pk, $_m_buil_pk, $_hero_pk): false|array
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['building', 'condition']);

        // 트랜잭션
        try {
            $this->PgGame->begin();
            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            // 현재 정보
            $this->PgGame->query('SELECT m_buil_pk, status, level FROM building_out_castle WHERE posi_pk = $1 AND out_castle_pk = $2 FOR UPDATE', [$_posi_pk, $_castle_pk]);
            if ($this->PgGame->fetch()) {
                $b = $this->PgGame->row;
            } else if ($_m_buil_pk) {
                $b = ['m_buil_pk' => $_m_buil_pk, 'status' => 'N', 'level' => 0];
            } else {
                throw new ErrorHandler('error', 'Error Occurred. [12001]'); // 건물 정보가 없습니다.
            }

            // 1. 상태와 레벨 제한 검사
            if ($b['status'] != 'N' || $b['level'] >= $_M['BUIL'][$b['m_buil_pk']]['max_level']) {
                throw new ErrorHandler('error', 'Error Occurred. [12002]'); // 이미 건설 중이거나 최고 레벨입니다.
            }

            // 2.1. 중복 건설 체크
            if ($b['level'] < 1) {
                $this->PgGame->query('SELECT COUNT(m_buil_pk) AS cnt FROM building_out_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, $b['m_buil_pk']]);
                $c = $this->PgGame->fetchOne();
                if ($_M['BUIL'][$b['m_buil_pk']]['yn_duplication'] == 'N' && $c > 0) {
                    throw new ErrorHandler('error', 'msg_already_construction'); // 이미 건설 되어있습니다.
                } else { // 다중 건설 가능
                    // 대전 레벨에 따른 건설 수 제한.
                    $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_CITYHALL]);
                    $level = $this->PgGame->fetchOne();

                    $limit_count = (INT)$_M['BUIL'][PK_BUILDING_CITYHALL]['level'][$level]['variation_1'] / 3; // variation_1 은 현재 대전 레벨의 최대 건설 부지수이므로 자원건물 당 제한을 위해 3 으로 나눠줌. 20230913 송누리
                    if ($limit_count > $_M['BUILD_LIMIT_COUNT'][$b['m_buil_pk']]) { // 최대 건설을 넘을 수 없음.
                        $limit_count = $_M['BUILD_LIMIT_COUNT'][$b['m_buil_pk']];
                    }
                    if ($c >= $limit_count) {
                        throw new ErrorHandler('error', 'msg_construction_max'); // 더 이상 건설할 수 없는 건물입니다.
                    }
                }
            }

            // 2.2. 진행 가능한 상태 인가?
            $this->PgGame->query('SELECT buil_pk, concurr_curr, concurr_max FROM build WHERE posi_pk = $1 AND in_cast_pk =  1 FOR UPDATE', [$_posi_pk]);
            if ($this->PgGame->fetch()) {
                $build = $this->PgGame->row;
            } else {
                throw new ErrorHandler('error', 'Error Occurred. [12003]'); // 건설 정보 받기 실패.
            }

            if ($build['concurr_curr'] >= $build['concurr_max']) {
                if ($build['concurr_curr'] == 1) {
                    throw new ErrorHandler('error', $i18n->t('msg_construction_max_queue')); // 건설은 기본적으로 1개가 가능하며<br />"건설허가서" 아이템을 사용하여 건설을<br />동시에 3개까지 진행할 수 있습니다.
                } else {
                    throw new ErrorHandler('error', $i18n->t('msg_construction_max_queue_error', [$build['concurr_max']])); // 동시 건설은 최대 {{1}}개까지 가능합니다.<br/><br/>동시 건설 제한을 초과하였습니다.
                }
            }

            // 3. m_condition 적용
            $m_cond_pk = &$_M['BUIL'][$b['m_buil_pk']]['level'][$b['level']+1]['m_cond_pk'];

            $this->classCondition();
            $ret = $this->Condition->conditionCheck($_posi_pk, $m_cond_pk, $_castle_pk, 'O', $_hero_pk);
            if (!$ret) {
                throw new ErrorHandler('error', $i18n->t('msg_check_prerequisites') . '<br />'. $NsGlobal->getErrorMessage()); // 선행 조건을 확인해 주시기 바랍니다.
            }

            $this->classHero();
            $ret = $this->Hero->setCommand($_posi_pk, $_hero_pk, 'C', 'Const');
            if (!$ret) {
                throw new ErrorHandler('error', 'Error Occurred. [12004]'); // 영웅 할당이 실패
            }

            // is need_gold
            if ($_M['COND'][$m_cond_pk]['build_gold']) {
                $r = $this->GoldPop->decreaseGold($_posi_pk, $_M['COND'][$m_cond_pk]['build_gold'], null, 'build_pre');
                if (!$r) {
                    throw new ErrorHandler('error', $i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
                }
            }

            // is need_population
            if ($_M['COND'][$m_cond_pk]['need_population']) {
                $r = $this->GoldPop->decreasePopulation($_posi_pk, $_M['COND'][$m_cond_pk]['need_population']);
                if (!$r) {
                    throw new ErrorHandler('error', $i18n->t('msg_need_population')); // 필요인구가 부족합니다.
                }
            }

            // 3.4. 자원소모 (갱신 필요)
            $res = [
                'food' => $_M['COND'][$m_cond_pk]['build_food'],
                'horse' => $_M['COND'][$m_cond_pk]['build_horse'],
                'lumber' => $_M['COND'][$m_cond_pk]['build_lumber'],
                'iron' => $_M['COND'][$m_cond_pk]['build_iron'],
            ];
            $r = $this->Resource->decrease($_posi_pk, $res, null, 'build_pre');
            if (!$r) {
                throw new ErrorHandler('error', $i18n->t('msg_resource_lack')); // 자원이 부족합니다.
            }

            // 3.5. 아이템 소모 (갱신 필요)
            if ($_M['COND'][$m_cond_pk]['m_item_pk']) {
                $this->classItem();
                $ret = $this->Item->useItem($_posi_pk, $this->Session->lord['lord_pk'], $_M['COND'][$m_cond_pk]['m_item_pk'], 1, ['_yn_quest' => true]);
                if(!$ret) {
                    throw new ErrorHandler('error', $i18n->t('msg_need_item', [$i18n->t("item_title_{$_M['COND'][$m_cond_pk]['m_item_pk']}")])); // 필요 아이템이 부족합니다.
                }
            }

            // 건물정보 갱신 (업굴중) (갱신 필요) - 최초 건설
            if ($_m_buil_pk) {
                $r = $this->PgGame->query('INSERT INTO building_out_castle (posi_pk, out_castle_pk, m_buil_pk, status, level, build_dt, last_levelup_dt, last_update_dt, buil_hero_pk) VALUES ($1, $2, $3, \'U\', 0, now(), now(), now(), $4)', [$_posi_pk, $_castle_pk, $_m_buil_pk, $_hero_pk]);
            } else {
                $r = $this->PgGame->query('UPDATE building_out_castle SET status = $4, buil_hero_pk = $3 WHERE posi_pk = $1 AND out_castle_pk = $2', [$_posi_pk, $_castle_pk, $_hero_pk, 'U']);
            }

            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new ErrorHandler('error', 'Error Occurred. [12005]'); // 건설이 실패 하였습니다. 다시 시도하여 주시기 바랍니다.
            }
            $this->updatePosition($_posi_pk, $_castle_pk);

            $this->PgGame->commit();
        } catch (ErrorHandler $e) {
            // 실패, sq 무시
            $this->PgGame->rollback();

            //dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$_posi_pk.'];');

            throw new ErrorHandler('error', $e->getMessage(), true);
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        // 빌드 등록 (갱신 필요)
        return [
            'buil_pk' => $build['buil_pk'],
            'm_buil_pk' => $b['m_buil_pk'],
            'level' => $b['level'],
            'build_time' => $_M['COND'][$m_cond_pk]['build_time'],
        ];
    }

    function upgradePost($_posi_pk, $_castle_pk, $_buil_cons_pk, $_time_pk = null): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['building', 'condition']);

        // 현재 정보
        $this->PgGame->query('SELECT m_buil_pk, level FROM building_out_castle WHERE posi_pk = $1 AND out_castle_pk = $2', [$_posi_pk, $_castle_pk]);
        if ($this->PgGame->fetch()) {
            $b = $this->PgGame->row;
        } else {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '건물정보찾기 실패;posi_pk['.$_posi_pk.'];in_castle_pk['.$_castle_pk.'];');
            return false;
        }

        // 건물정보 갱신 (업굴중) (갱신 필요)
        $r = $this->PgGame->query('UPDATE building_out_castle SET status = \'N\', level = level+1, buil_hero_pk = null WHERE posi_pk = $1 AND out_castle_pk = $2 AND status = \'U\'', [$_posi_pk, $_castle_pk]);
        if (!$r || $this->PgGame->getAffectedRows() == 0) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'building_out_castle update failed.;posi_pk['.$_posi_pk.'];out_castle_pk['.$_castle_pk.'];');
            return false;
        }

        $this->updatePosition($_posi_pk, $_castle_pk);

        // LOG
        $this->classLog();
        $description = $_M['BUIL'][$b['m_buil_pk']]['title'] . '[curr['. $b['level'] . '];update['. ($b['level'] + 1) . '];];';
        $this->PgGame->query('SELECT buil_pk, cmd_hero_pk, m_buil_pk, status, position_type, position, current_level, regist_dt, start_dt, build_time, build_time_reduce, end_dt FROM build_construction WHERE buil_cons_pk = $1', [$_buil_cons_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        // 삭제
        $this->PgGame->query('DELETE FROM build_construction WHERE buil_cons_pk = $1', [$_buil_cons_pk]);

        $this->Log->setConstruction($this->Session->lord['lord_pk'], $_posi_pk, 'complete', $description, $_buil_cons_pk, $row['buil_pk'], $row['cmd_hero_pk'], $row['m_buil_pk'], $row['regist_dt'], $row['start_dt'], $row['build_time'], $row['build_time_reduce'], $row['position_type'], $row['position'], $row['current_level'], $_time_pk);

        return true;
    }

    function demolishPre($_posi_pk, $_castle_pk): false|array
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['building', 'condition']);

        // 현재 정보
        $this->PgGame->query('SELECT m_buil_pk, status, level FROM building_out_castle WHERE posi_pk = $1 AND out_castle_pk = $2', [$_posi_pk, $_castle_pk]);
        if ($this->PgGame->fetch()) {
            $b = $this->PgGame->row;
        } else {
            return false;
        }

        // 1. 상태와 레벨 제한 검사
        if ($b['status'] != 'N' || $_M['BUIL'][$b['m_buil_pk']]['yn_demolish'] == 'N') {
            throw new ErrorHandler('error', 'Error Occurred. [12006]'); // 이미 건설 중 이거나 다운그레이드 불가능한 건물입니다.
        }

        // 2.2. 진행 가능한 상태 인가?
        $this->PgGame->query('SELECT buil_pk, concurr_curr, concurr_max FROM build WHERE posi_pk = $1 AND in_cast_pk =  1', [$_posi_pk]);
        if ($this->PgGame->fetch()) {
            $build = $this->PgGame->row;
        } else {
            throw new ErrorHandler('error', 'Error Occurred. [12007]'); // 다운그레이드 불가능한 상태입니다.
        }

        if ($build['concurr_curr'] >= $build['concurr_max']) {
            if ($build['concurr_curr'] == 1) {
                throw new ErrorHandler('error', $i18n->t('msg_construction_max_queue')); // 건설은 기본적으로 1개가 가능하며<br />"건설허가서" 아이템을 사용하여 건설을<br />동시에 3개까지 진행할 수 있습니다.<br /><br /><small>1회에 한하여 퀘스트를 통하여 무료로 획득할 수 있습니다.</small>
            } else {
                throw new ErrorHandler('error', $i18n->t('msg_construction_max_queue_error', [$build['concurr_max']])); // 동시 건설은 최대 {{1}}개까지 가능합니다.<br/><br/>동시 건설 제한을 초과하였습니다.
            }
        }

        $this->PgGame->query('UPDATE building_out_castle SET status = \'D\' WHERE posi_pk = $1 AND out_castle_pk = $2', [$_posi_pk, $_castle_pk]);
        $this->updatePosition($_posi_pk, $_castle_pk);

        $m_cond_pk = &$_M['BUIL'][$b['m_buil_pk']]['level'][$b['level']]['m_cond_pk'];

        // 빌드 등록 (갱신 필요)
        return [
            'buil_pk' => $build['buil_pk'],
            'm_buil_pk' => $b['m_buil_pk'],
            'level' => $b['level'],
            'demolish_time' => $_M['COND'][$m_cond_pk]['demolish_time'],
        ];
    }

    function demolishPost($_posi_pk, $_castle_pk, $_dynamite = false, $_buil_cons_pk = null, $_time_pk = null): bool
    {
        global $_M, $_z_m_build_pk, $NsGlobal;
        $NsGlobal->requireMasterData(['building', 'condition']);

        // 현재 정보
        $this->PgGame->query('SELECT m_buil_pk, level FROM building_out_castle WHERE posi_pk = $1 AND out_castle_pk = $2', [$_posi_pk, $_castle_pk]);
        if ($this->PgGame->fetch()) {
            $b = $this->PgGame->row;
        } else {
            return false;
        }

        $_z_m_build_pk = $b['m_buil_pk'];

        // 폭파가 아닐때만
        if (!$_dynamite) {
            $m_cond_pk = &$_M['BUIL'][$b['m_buil_pk']]['level'][$b['level']]['m_cond_pk'];

            // 각종 수치 환급
            if ($_M['COND'][$m_cond_pk]['demolish_gold']) {
                $r = $this->GoldPop->increaseGold($_posi_pk, $_M['COND'][$m_cond_pk]['demolish_gold'], null, 'build_demolish');
                if (!$r) {
                    //return false;
                    // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '황금증가실패;posi_pk['.$_posi_pk.'];gold['.$_M['COND'][$m_cond_pk]['demolish_gold'].']');
                }
            }

            $res = [
                'food' => $_M['COND'][$m_cond_pk]['demolish_food'],
                'horse' => $_M['COND'][$m_cond_pk]['demolish_horse'],
                'lumber' => $_M['COND'][$m_cond_pk]['demolish_lumber'],
                'iron' => $_M['COND'][$m_cond_pk]['demolish_iron'],
            ];
            $r = $this->Resource->increase($_posi_pk, $res, null, 'build_demolish');
            if (!$r) {
                // return false;
                // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '자원증가실패;posi_pk['.$_posi_pk.'];food['.$res['food'].'];horse['.$res['horse'].'];lumber['.$res['lumber'].'];iron['.$res['iron'].'];');
            }
        }

        // 건물 레벨 상승에 따른 효과 처리 (인구, 창고, 성벽, 자원(전답,목장,숲) 등의 재계산)
        //$_M['BUIL'][$b['m_buil_pk']['update_type']

        // 건물정보 갱신 (업굴중) (갱신 필요)
        if ($b['level'] > 1) {
            $r = $this->PgGame->query('UPDATE building_out_castle SET status = \'N\', level = level-1 WHERE posi_pk = $1 AND out_castle_pk = $2 AND status = \'D\'', [$_posi_pk, $_castle_pk]);
            $set_null = false;
        } else {
            $r = $this->PgGame->query('DELETE FROM building_out_castle WHERE posi_pk = $1 AND out_castle_pk = $2 AND status = \'D\'', [$_posi_pk, $_castle_pk]);
            $set_null = true;
        }
        if (!$r || $this->PgGame->getAffectedRows() == 0) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '건물정보 갱신 실패;posi_pk['.$_posi_pk.'];castle_pk['.$_castle_pk.']');
            return false;
        }

        $this->updatePosition($_posi_pk, $_castle_pk, $set_null);

        // LOG
        if (!$_dynamite) {
            $this->classLog();
            $description = $_M['BUIL'][$b['m_buil_pk']]['title'] . '[curr['. $b['level'] . '];update['. ($b['level'] - 1) . '];];';
            $this->PgGame->query('SELECT buil_pk, cmd_hero_pk, m_buil_pk, status, position_type, position, current_level, regist_dt, start_dt, build_time, build_time_reduce, end_dt FROM build_construction WHERE buil_cons_pk = $1', [$_buil_cons_pk]);
            $this->PgGame->fetch();
            $row = $this->PgGame->row;
            // 삭제
            $this->PgGame->query('DELETE FROM build_construction WHERE buil_cons_pk = $1', [$_buil_cons_pk]);

            $this->Log->setConstruction($this->Session->lord['lord_pk'], $_posi_pk, 'demolish_complete', $description, $_buil_cons_pk, $row['buil_pk'], $row['cmd_hero_pk'], $row['m_buil_pk'], $row['regist_dt'], $row['start_dt'], $row['build_time'], $row['build_time_reduce'], $row['position_type'], $row['position'], $row['current_level'], $_time_pk);
        }
        return true;
    }

    function dynamite($_posi_pk, $_castle_pk): bool
    {
        // 레벨1 만들기
        $this->PgGame->query('UPDATE building_out_castle SET status = \'D\', level = 1 WHERE posi_pk = $1 AND out_castle_pk = $2', [$_posi_pk, $_castle_pk]);
        // demolishPost 호출
        return $this->demolishPost($_posi_pk, $_castle_pk, true);
    }

    // TODO 위쪽 함수 몇개랑 이거랑 트랜잭션 처리 하는게 차라리 낫지 않나?
    function cancel($_posi_pk, $_castle_pk, $hero_pk = null, $_build_pk = null, $_time_pk = null): bool
    {
        global $_M, $_z_m_build_pk, $NsGlobal;
        $NsGlobal->requireMasterData(['building', 'condition']);

        // 현재 정보
        $this->PgGame->query('SELECT m_buil_pk, level, status FROM building_out_castle WHERE posi_pk = $1 AND out_castle_pk = $2', [$_posi_pk, $_castle_pk]);
        if ($this->PgGame->fetch()){
            $b = $this->PgGame->row;
        } else {
            return false;
        }

        if ($b['level'] == 0) { // 최초 건설의 취소
            $r = $this->PgGame->query('DELETE FROM building_out_castle WHERE posi_pk = $1 AND out_castle_pk = $2 AND status = \'U\'', [$_posi_pk, $_castle_pk]);
            $set_null = true;
        } else {
            $r = $this->PgGame->query('UPDATE building_out_castle SET status = \'N\', buil_hero_pk = null WHERE posi_pk = $1 AND out_castle_pk = $2 AND status IN (\'U\', \'D\')', [$_posi_pk, $_castle_pk]);
            $set_null = false;
        }
        if (!$r || $this->PgGame->getAffectedRows() == 0) {
            return false;
        }
        $this->updatePosition($_posi_pk, $_castle_pk, $set_null);

        // 3. m_condition 적용
        $m_cond_pk = &$_M['BUIL'][$b['m_buil_pk']]['level'][$b['level']+1]['m_cond_pk'];

        if ($b['status'] == 'U') {
            // 각종 수치 환급
            if ($_M['COND'][$m_cond_pk]['build_gold']) {
                $r = $this->GoldPop->increaseGold($_posi_pk, intval($_M['COND'][$m_cond_pk]['build_gold']*0.3), null, 'build_cancel');
                if (!$r) {
                    //return false;
                    // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '황금증가실패;posi_pk['.$_posi_pk.'];gold['.$_M['COND'][$m_cond_pk]['demolish_gold'].']');
                }
            }
            // 자원 롤백
            $res = [
                'food' => intval($_M['COND'][$m_cond_pk]['build_food']*0.3),
                'horse' => intval($_M['COND'][$m_cond_pk]['build_horse']*0.3),
                'lumber' => intval($_M['COND'][$m_cond_pk]['build_lumber']*0.3),
                'iron' => intval($_M['COND'][$m_cond_pk]['build_iron']*0.3),
            ];
            $r = $this->Resource->increase($_posi_pk, $res, null, 'build_cancel');
            if (!$r) {
                // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '자원증가실패;posi_pk['.$_posi_pk.'];food['.$res['food'].'];horse['.$res['horse'].'];lumber['.$res['lumber'].'];iron['.$res['iron'].'];');
            }
        }

        // LOG
        $this->classLog();
        $this->PgGame->query('SELECT buil_pk, cmd_hero_pk, m_buil_pk, status, position_type, position, current_level, regist_dt, start_dt, build_time, build_time_reduce, end_dt FROM build_construction WHERE buil_cons_pk = $1', [$_build_pk]);
        $this->PgGame->fetch();
        $row = $this->PgGame->row;
        // 삭제
        $this->PgGame->query('DELETE FROM build_construction WHERE buil_cons_pk = $1', [$_build_pk]);

        $this->Log->setConstruction($this->Session->lord['lord_pk'], $_posi_pk, 'cancel', null, $_build_pk, $row['buil_pk'], $row['cmd_hero_pk'], $row['m_buil_pk'], $row['regist_dt'], $row['start_dt'], $row['build_time'], $row['build_time_reduce'], $row['position_type'], $row['position'], $row['current_level'], $_time_pk);

        return true;
    }

    function cancelBdoc($_posi_pk): true
    {
        // 1. 현재 건설중인 건물 찾기 - 찾은 후 하나씩 취소 해주기
        $this->PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND type = $2', [$_posi_pk, 'C']);
        $buil_pk = $this->PgGame->FetchOne();
        if (!$buil_pk) {
            return true;
        }

        $this->PgGame->query('SELECT buil_cons_pk, position FROM build_construction WHERE buil_pk = $1 AND position_type = $2 AND status = $3', [$buil_pk, 'O', 'P']);
        $this->PgGame->FetchAll();
        $rows = $this->PgGame->rows;
        foreach($rows AS $v) {
            $this->PgGame->Query('SELECT time_pk FROM timer WHERE queue_type = $1 AND queue_pk = $2 AND status = $3', ['C', $v['buil_cons_pk'], 'P']);
            $time_pk = $this->PgGame->FetchOne();
            if (!$time_pk) {
                continue;
            }
            $this->classTimer();
            $this->Timer->cancel($time_pk);

            $this->classBuildConstruction();
            $this->BuildConstruction->cancel($v['buil_cons_pk']);

            // 현재 정보
            $this->PgGame->query('SELECT level FROM building_out_castle WHERE posi_pk = $1 AND out_castle_pk = $2', [$_posi_pk, $v['position']]);
            if ($this->PgGame->fetch()) {
                if ($this->PgGame->row['level'] == 0) { // 최초 건설의 취소
                    $this->PgGame->query('DELETE FROM building_out_castle WHERE posi_pk = $1 AND out_castle_pk = $2 AND status = \'U\'', [$_posi_pk, $v['position']]);
                } else {
                    $this->PgGame->query('UPDATE building_out_castle SET status = \'N\', buil_hero_pk = null WHERE posi_pk = $1 AND out_castle_pk = $2 AND status IN (\'U\', \'D\')', [$_posi_pk, $v['position']]);
                }
            }
        }
        return true;
    }
}