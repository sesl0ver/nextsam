<?php

class BuildConstruction
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Quest $Quest;
    protected Bdic $Bdic;
    protected Bdoc $Bdoc;
    protected Condition $Condition;
    protected Lord $Lord;
    protected Item $Item;
    protected Hero $Hero;
    protected Territory $Territory;
    protected Cash $Cash;
    protected Resource $Resource;
    protected GoldPop $GoldPop;
    protected FigureReCalc $FigureReCalc;
    protected Effect $Effect;
    protected Technique $Technique;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
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

    protected function classCondition (): void
    {
        if (! isset($this->Condition)) {
            $this->Condition = new Condition($this->Session, $this->PgGame);
        }
    }

    function classLord(): void
    {
        if (! isset($this->Lord)) {
            $this->Lord = new Lord($this->Session, $this->PgGame);
        }
    }

    function classTerritory(): void
    {
        if (! isset($this->Territory)) {
            $this->Territory = new Territory($this->Session, $this->PgGame);
        }
    }

    function classHero(): void
    {
        if (! isset($this->Hero)) {
            $this->Hero = new Hero($this->Session, $this->PgGame);
        }
    }

    function classItem(): void
    {
        if (! isset($this->Item)) {
            $this->Item = new Item($this->Session, $this->PgGame);
        }
    }

    function classCash(): void
    {
        if (! isset($this->Cash)) {
            $this->Cash = new Cash($this->Session, $this->PgGame);
        }
    }

    function classGoldPop(): void
    {
        if (! isset($this->GoldPop)) {
            $this->GoldPop = new GoldPop($this->Session, $this->PgGame);
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

    function classTechnique(): void
    {
        if (! isset($this->Technique)) {
            $this->classResource();
            $this->classGoldPop();
            $this->Technique = new Technique($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classQuest(): void
    {
        if (! isset($this->Quest)) {
            $this->Quest = new Quest($this->Session, $this->PgGame);
        }
    }

    function set($_buil_pk, $_cmd_hero_pk, $_m_buil_pk, $_position_type, $_position, $_current_level, $_build_time): false|int
    {
        $r = $this->PgGame->query("INSERT INTO build_construction (buil_pk, cmd_hero_pk, m_buil_pk, status, priority, position_type, position, current_level, description, regist_dt, start_dt, build_time, build_time_reduce, end_dt)
VALUES ($1, $2, $3, 'P', 1, $4, $5, $6, '-', now(), now(), $7, 0, now() + interval '$_build_time second')", [$_buil_pk, $_cmd_hero_pk, $_m_buil_pk, $_position_type, $_position, $_current_level, $_build_time]);
        if (!$r) {
            return false;
        }
        $queue_pk = $this->PgGame->currSeq('build_construction_buil_cons_pk_seq');
        $this->PgGame->query('UPDATE build SET status = $2, concurr_curr = concurr_curr + 1, last_update_dt = now() WHERE buil_pk = $1', [$_buil_pk, 'P']);

        // 동시 건설 퀘스트 체크
        $this->PgGame->query('SELECT concurr_curr FROM build WHERE buil_pk = $1 AND in_cast_pk = $2', [$_buil_pk, 1]);
        if ($this->PgGame->fetchOne() >= 2) {
            $this->classQuest();
            $this->Quest->conditionCheckQuest($this->Session->lord['lord_pk'], ['quest_type' => 'build_duplication']);
        }
        return $queue_pk;
    }

    function now($_posi_pk, $_castle_pk, $_m_buil_pk): bool|array
    {
        global $_M, $NsGlobal, $i18n;
        $this->classBdic();
        $this->classBdoc();
        $this->classEffect();
        $NsGlobal->requireMasterData(['building', 'condition']);

        if (! $_M['BUIL'][$_m_buil_pk]['type']) {
            $NsGlobal->setErrorMessage('Error Occurred. [13001]'); // 포지션 타입 오류
            return false;
        }

        $_position_type = $_M['BUIL'][$_m_buil_pk]['type'];

        switch ($_position_type) {
            case 'I' : // 인캐슬
                // 현재 정보
                $this->PgGame->query('SELECT m_buil_pk, status, level FROM building_in_castle WHERE posi_pk = $1 AND in_castle_pk = $2 FOR UPDATE', [$_posi_pk, $_castle_pk]);
                if ($this->PgGame->fetch()) {
                    $b = $this->PgGame->row;
                } else if ($_m_buil_pk) {
                    $b = ['m_buil_pk' => $_m_buil_pk, 'status' => 'N', 'level' => 0];
                } else {
                    $NsGlobal->setErrorMessage('Error Occurred. [13002]'); // 건물 건설 시 오류 발생 (1)
                    // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '건물정보찾기 실패;posi_pk['.$_posi_pk.'];in_castle_pk['.$_castle_pk.'];');
                    return false;
                }
                break;
            default : // 아웃캐슬
                // 현재 정보
                $this->PgGame->query('SELECT m_buil_pk, status, level FROM building_out_castle WHERE posi_pk = $1 AND out_castle_pk = $2 FOR UPDATE', [$_posi_pk, $_castle_pk]);
                if ($this->PgGame->fetch()) {
                    $b = $this->PgGame->row;
                } else if ($_m_buil_pk) {
                    $b = ['m_buil_pk' => $_m_buil_pk, 'status' => 'N', 'level' => 0];
                } else {
                    $NsGlobal->setErrorMessage('Error Occurred. [13003]'); // 건물 건설 시 오류 발생 (2)
                    // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '건물정보찾기 실패;posi_pk['.$_posi_pk.'];out_castle_pk['.$_castle_pk.'];');
                    return false;
                }
                break;
        }

        if (! $_m_buil_pk) {
            $_m_buil_pk = $b['m_buil_pk'];
        }

        // 1. 상태와 레벨 제한 검사
        if ($b['status'] != 'N' || $b['level'] >= $_M['BUIL'][$b['m_buil_pk']]['max_level']) {
            $NsGlobal->setErrorMessage($i18n->t('msg_already_construction_or_max_level')); // 이미 건설 중이거나 최고 레벨입니다.
            return false;
        }

        // 2.1. 중복 건설 체크
        if ($b['level'] < 1) {
            $query_string = match ($_position_type) {
                'I' => 'SELECT COUNT(m_buil_pk) AS cnt FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2',
                default => 'SELECT COUNT(m_buil_pk) AS cnt FROM building_out_castle WHERE posi_pk = $1 AND m_buil_pk = $2',
            };
            $this->PgGame->query($query_string, [$_posi_pk, $b['m_buil_pk']]);
            $c = $this->PgGame->fetchOne();
            if ($_M['BUIL'][$b['m_buil_pk']]['yn_duplication'] == 'N') {
                if ($c > 0) {
                    $NsGlobal->setErrorMessage($i18n->t('msg_already_construction')); // 이미 건설 되어있습니다.
                    return false;
                }
            } else {
                $limit_count = ($_position_type === 'I') ? $_M['BUILD_LIMIT_COUNT'][$b['m_buil_pk']] : 0;
                if ($_position_type !== 'I') { // 내성이 아니라면 (외성이라면)
                    // 대전 레벨에 따른 건설 수 제한.
                    $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_CITYHALL]);
                    $level = $this->PgGame->fetchOne();
                    $limit_count = (INT)$_M['BUIL'][PK_BUILDING_CITYHALL]['level'][$level]['variation_1'] / 3; // variation_1 은 현재 대전 레벨의 최대 건설 부지수이므로 자원건물 당 제한을 위해 3 으로 나눠줌. 20230913 송누리
                    if ($limit_count > $_M['BUILD_LIMIT_COUNT'][$b['m_buil_pk']]) { // 최대 건설을 넘을 수 없음.
                        $limit_count = $_M['BUILD_LIMIT_COUNT'][$b['m_buil_pk']];
                    }
                }
                if ($c >= $limit_count) {
                    $NsGlobal->setErrorMessage($i18n->t('msg_construction_max')); // 더 이상 건설할 수 없는 건물입니다.
                    return false;
                }
            }
        }

        // 캐시에 의한 즉시 건설 이므로 동시건설 체크는 하지 않음.

        // 3. m_condition 체크
        $m_cond_pk = &$_M['BUIL'][$b['m_buil_pk']]['level'][$b['level']+1]['m_cond_pk'];

        $this->classCondition();
        $ret = $this->Condition->conditionCheck($_posi_pk, $m_cond_pk, $_castle_pk, $_position_type, null, true);
        if (!$ret) {
            $NsGlobal->setErrorMessage($i18n->t('msg_check_prerequisites') . '<br />'. $NsGlobal->getErrorMessage()); // 선행 조건을 확인해 주시기 바랍니다.
            return false;
        }

        // 영웅 할당 없음.

        // 트랜젝션 시작
        try {
            $this->PgGame->begin();
            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            // is need_gold
            if ($_M['COND'][$m_cond_pk]['build_gold']) {
                $r = $this->GoldPop->decreaseGold($_posi_pk, $_M['COND'][$m_cond_pk]['build_gold'], null, 'build_pre');
                if (!$r) {
                    throw new Exception($i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
                }
            }

            // is need_population
            if ($_M['COND'][$m_cond_pk]['need_population']) {
                $r = $this->GoldPop->decreasePopulation($_posi_pk, $_M['COND'][$m_cond_pk]['need_population']);
                if (!$r) {
                    throw new Exception($i18n->t('msg_need_population')); // 필요인구가 부족합니다.
                }
            }

            // 3.4. 자원소모 (갱신 필요)
            $res = [
                'food' => $_M['COND'][$m_cond_pk]['build_food'],
                'horse' => $_M['COND'][$m_cond_pk]['build_horse'],
                'lumber' => $_M['COND'][$m_cond_pk]['build_lumber'],
                'iron' => $_M['COND'][$m_cond_pk]['build_iron'],
            ];
            $this->classResource();
            $r = $this->Resource->decrease($_posi_pk, $res, null, 'build_pre');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_lack')); // 자원이 부족합니다.
            }

            // 3.5. 아이템 소모 (갱신 필요)
            if ($_M['COND'][$m_cond_pk]['m_item_pk']) {
                $this->classItem();
                $ret = $this->Item->useItem($_posi_pk, $this->Session->lord['lord_pk'], $_M['COND'][$m_cond_pk]['m_item_pk'], 1, ['_yn_quest' => true]);
                if(!$ret) {
                    throw new Exception($i18n->t('msg_need_item', [$i18n->t("item_title_{$_M['COND'][$m_cond_pk]['m_item_pk']}")])); // 필요 아이템이 부족합니다.
                }
            }

            // 즉시 건설에 의한 큐빅 소모
            $build_time = $_M['COND'][$m_cond_pk]['build_time'];
            $remain_qbig = Useful::getNeedQbig($build_time);

            $this->classCash();
            $qbig = $this->Cash->decreaseCash($this->Session->lord['lord_pk'], $remain_qbig, 'build now');
            if (!$qbig) {
                throw new Exception($i18n->t('msg_qbig_lack')); // 큐빅이 부족합니다.
            }

            // 건물 건설 완료 후 정보 등록.
            $next_level = $b['level'] + 1;
            switch ($_position_type) {
                case 'I':
                    if ($b['level'] < 1) {
                        $r = $this->PgGame->query('INSERT INTO building_in_castle (posi_pk, in_castle_pk, m_buil_pk, status, level, build_dt, last_levelup_dt, last_update_dt) VALUES ($1, $2, $3, $4, $5, now(), now(), now())', [$_posi_pk, $_castle_pk, $_m_buil_pk, 'N', $next_level]);
                    } else {
                        $r = $this->PgGame->query('UPDATE building_in_castle SET status = $4, level = $3, last_levelup_dt = now(), last_update_dt = now() WHERE posi_pk = $1 AND in_castle_pk = $2', [$_posi_pk, $_castle_pk, $next_level, 'N']);
                    }
                    break;
                default :
                    if ($b['level'] < 1) {
                        $r = $this->PgGame->query('INSERT INTO building_out_castle (posi_pk, out_castle_pk, m_buil_pk, status, level, build_dt, last_levelup_dt, last_update_dt) VALUES ($1, $2, $3, $4, $5, now(), now(), now())', [$_posi_pk, $_castle_pk, $_m_buil_pk, 'N', $next_level]);
                    } else {
                        $r = $this->PgGame->query('UPDATE building_out_castle SET status = $4, level = $3, last_levelup_dt = now(), last_update_dt = now() WHERE posi_pk = $1 AND out_castle_pk = $2', [$_posi_pk, $_castle_pk, $next_level, 'N']);
                    }
                    break;
            }
            if (!$r) {
                throw new Exception('Error Occurred. [13004]'); // 건물 건설 중 오류가 발생했습니다. (3)
            }

            // 수치 변경전 저장하기 - 자원/인구/황금
            if ($_m_buil_pk == PK_BUILDING_STORAGE || $_m_buil_pk == PK_BUILDING_FOOD || $_m_buil_pk == PK_BUILDING_HORSE || $_m_buil_pk == PK_BUILDING_LUMBER) {
                // 창고 , 자원 - 창고가 있는 이유는 자원이 창고의 저장공간을 넘을 수 있어서...
                $this->classResource();
                $this->Resource->save($_posi_pk);
            } else if ($_m_buil_pk == PK_BUILDING_COTTAGE) { // 민가
                $this->classGoldPop();
                $this->GoldPop->save($_posi_pk);
            } else if ($_m_buil_pk == PK_BUILDING_TECHNIQUE) {
                $this->classTechnique();
                $this->Technique->updateTerritoryTechnique($this->Session->lord['lord_pk'], $_posi_pk, $b['level'] + 1);
            }

            // 건물 건설 후처리
            if ($b['level'] == 0) {
                $build_table = match ($_m_buil_pk) {
                    PK_BUILDING_ARMY => ['type' => 'A', 'concurr_max' => 1, 'queue_max' => 0],
                    PK_BUILDING_TECHNIQUE => ['type' => 'T', 'concurr_max' => 1, 'queue_max' => 1],
                    PK_BUILDING_MEDICAL => ['type' => 'M', 'concurr_max' => 1, 'queue_max' => 0],
                    default => null,
                };
                if ($build_table != null) {
                    $this->PgGame->query('INSERT INTO build (posi_pk, in_cast_pk, status, type, concurr_curr, concurr_max, queue_curr, queue_max, regist_dt, last_update_dt) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, now(), now())', [$_posi_pk, $_castle_pk, 'I', $build_table['type'], 0, $build_table['concurr_max'], 0, $build_table['queue_max']]);
                }
            }

            // 영향력 증가
            $this->classLord();
            $this->Lord->increasePower($this->Session->lord['lord_pk'], $_M['BUIL'][$_m_buil_pk]['level'][$b['level'] + 1]['increase_power'], $_posi_pk);

            // 건물 효과 갱신
            if ($_m_buil_pk) {
                $update_type = $_M['BUIL'][$_m_buil_pk]['update_type'];
                if ($update_type != 'NULL') {
                    $this->classFigureReCalc();
                    $this->FigureReCalc->dispatcher($_posi_pk, $update_type, ['in_castle_pk' => $_castle_pk, 'status' => 'U', 'level' => $b['level']]);
                }
            }

            // 건설 완료 후 정보 업데이트
            switch ($_position_type) {
                case 'I':
                    $this->classBdic();
                    $this->Bdic->updatePosition($_posi_pk, $_castle_pk);
                    break;
                default :
                    $this->classBdoc();
                    $this->Bdoc->updatePosition($_posi_pk, $_castle_pk);
                    break;
            }

            if ($b['m_buil_pk'] == PK_BUILDING_CITYHALL && $b['level'] == 4) {
                $this->PgGame->query('SELECT status_truce, truce_type FROM territory WHERE posi_pk = $1', [$_posi_pk]);
                $this->PgGame->fetch();
                if ($this->PgGame->row['status_truce'] == 'Y' && $this->PgGame->row['truce_type'] == 'B') {
                    $this->classTerritory();
                    $this->Territory->finishTruceStatus($_posi_pk, 500105);
                }
            }

            $this->PgGame->commit();
        } catch (Exception $e) {
            $this->PgGame->rollback();
            $NsGlobal->setErrorMessage($e->getMessage());
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$_posi_pk.'];');
            return false;
        }

        $this->Session->sqAppend('PUSH', ['PLAY_SOUND' => 'construct_complete'], null, $this->Session->lord['lord_pk']);
        $this->Session->sqAppend('PUSH', [
            'PLAY_EFFECT' => [
                'type' => 'construction',
                'castle_type' => $_position_type,
                'castle_pk' => $_castle_pk,
            ]
        ], null, $this->Session->lord['lord_pk']);

        // 퀘스트 확인
        $this->classQuest();
        $this->Quest->conditionCheckQuest($this->Session->lord['lord_pk'], ['quest_type' => 'buil_upgrade','m_buil_pk' => $_m_buil_pk, 'level' => $b['level'], 'position_type' => $_position_type, 'posi_pk' => $_posi_pk]);
        if ($_m_buil_pk == PK_BUILDING_FOOD || $_m_buil_pk == PK_BUILDING_HORSE || $_m_buil_pk == PK_BUILDING_LUMBER) {
            $this->Quest->conditionCheckQuest($this->Session->lord['lord_pk'], ['quest_type' => 'territory', 'posi_pk' => $_posi_pk]);
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        /*switch ($_position_type) {
            case 'I' :
                $this->Bdic->getPositions($_posi_pk);
                break;
            default :
                $this->Bdoc->getPositions($_posi_pk);
                break;
        }*/

        return [
            'm_buil_pk' => $_m_buil_pk,
            'position_type' => $_position_type,
            'castle_type' => $_position_type,
            'castle_pk' => $_castle_pk,
            'current_level' => $b['level'],
            'next_level' => $next_level
        ];
    }

    function finish($_queue_pk): false|array
    {
        $this->PgGame->query('SELECT buil_pk, position_type, position, cmd_hero_pk, start_dt, build_time FROM build_construction WHERE buil_cons_pk = $1', [$_queue_pk]);
        if (!$this->PgGame->fetch()) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '영웅 해제 못함;build_construction buil_cons_pk찾지 못함');
            return false;
        }

        $r = $this->PgGame->row;

        $this->PgGame->query('UPDATE build_construction SET status = $2 WHERE buil_cons_pk = $1', [$_queue_pk, 'F']);
        $this->PgGame->query('UPDATE build SET concurr_curr = concurr_curr - 1, last_update_dt = now() WHERE buil_pk = $1 AND concurr_curr > 0', [$r['buil_pk']]);
        $this->PgGame->query('UPDATE build SET status = $2 WHERE buil_pk = $1 AND concurr_curr = 0', [$r['buil_pk'], 'I']);

        if ($r['cmd_hero_pk']) {
            $this->classHero();
            $this->Hero->unsetCommand($r['cmd_hero_pk'], true, $r['build_time']);
        }

        return ['position_type' => $r['position_type'], 'position' => $r['position'], 'hero_pk' => $r['cmd_hero_pk'], 'start_dt' => $r['start_dt']];
    }

    function cancel($_queue_pk): false|array
    {
        $this->PgGame->query('SELECT buil_pk, position_type, position, cmd_hero_pk FROM build_construction WHERE buil_cons_pk = $1', [$_queue_pk]);
        if (!$this->PgGame->fetch()) {
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '영웅 해제 못함;build_construction buil_cons_pk찾지 못함');
            return false;
        }

        $r = $this->PgGame->row;

        $this->PgGame->query('UPDATE build_construction SET status = $2 WHERE buil_cons_pk = $1', [$_queue_pk, 'C']);
        $this->PgGame->query('UPDATE build SET concurr_curr = concurr_curr - 1, last_update_dt = now() WHERE buil_pk = $1', [$r['buil_pk']]);

        $this->PgGame->query('UPDATE build SET status = $2 WHERE buil_pk = $1 AND concurr_curr = 0', [$r['buil_pk'], 'I']);

        $this->classHero();
        $this->Hero->unsetCommand($r['cmd_hero_pk']);

        return ['position_type' => $r['position_type'], 'position' => $r['position'], 'hero_pk' => $r['cmd_hero_pk'], 'queue_pk' => $_queue_pk];
    }
}