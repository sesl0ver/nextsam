<?php
global $app, $Render, $i18n;

// demolish (철거), dynamite (폭파) 는 사실상 API 사용을 안하므로 컨버전에서 제외함. 필요시 원본 코드 확인.
$app->post('/api/build', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $BuildConstruction = new BuildConstruction($Session, $PgGame);
    $Timer = new Timer($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
    $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);
    $Hero = new Hero($Session, $PgGame);
    $Queue = new Queue($Session, $PgGame);

    $check_type = ($params['castle_type'] === 'bdic');
    $position_type = ($check_type) ? 'I' : 'O';
    $Bd_c = ($check_type) ? new Bdic($Session, $PgGame, $Resource, $GoldPop) : new Bdoc($Session, $PgGame, $Resource, $GoldPop);
    $in_cast_pk = 0;
    $out_cast_pk = 0;

    $m_buil_pk = $params['m_buil_pk'];
    if (in_array($m_buil_pk, [201300, 201400, 201500])) {
        $out_cast_pk = $params['castle_pk'];
    } else {
        $in_cast_pk = $params['castle_pk'];
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['building']);
    if ($position_type != $_M['BUIL'][$params['m_buil_pk']]['type']) {
        return $Render->nsXhrReturn('error', 'Position type mismatch');
    }

    $result_arr = $Bd_c->upgradePre($params['posi_pk'], $params['castle_pk'], ($params['action'] === 'build') ? $params['m_buil_pk'] : null, $params['hero_pk']);
    if (!$result_arr) {
        return $Render->nsXhrReturn('error',  $NsGlobal->getErrorMessage(), $NsGlobal->getErrorData());
    }

    // 영웅 명령 효과
    $capacities = $Effect->getHeroCapacityEffects($params['hero_pk']);
    $applies = $Effect->getHeroAppliedCommandEffects(PK_CMD_CONST, $capacities);
    $ret = $Effect->getEffectedValue($params['posi_pk'], ['cons_build_time_decrease'], $result_arr['build_time'], $applies['all']);

    $result_arr['build_time'] = (($result_arr['build_time'] * 0.1) > intval($ret['value'])) ? intval($result_arr['build_time'] * 0.1) : intval($ret['value']);

    $desc_level = $result_arr['level']+1;
    $m_buil_title = $_M['BUIL'][$result_arr['m_buil_pk']]['title'];
    $description = "{$result_arr['m_buil_pk']}:$desc_level|{$Hero->getHeroDesc($params['hero_pk'])}";

    $queue_pk = $BuildConstruction->set($result_arr['buil_pk'], $params['hero_pk'], $result_arr['m_buil_pk'], $position_type, $params['castle_pk'], $result_arr['level'], $result_arr['build_time']);
    $Session->sqAppend('QUEUE', [$queue_pk => $Queue->getData('construction', $queue_pk)]);

    $Timer->set($params['posi_pk'], 'C', $queue_pk, 'U', $description, $result_arr['build_time'], $in_cast_pk, null, $out_cast_pk);

    // Log
    $time_pk = $PgGame->currSeq('timer_time_pk_seq');
    $Log->setConstruction($Session->lord['lord_pk'], $params['posi_pk'], 'upgrade', $m_buil_title, $queue_pk, $result_arr['buil_pk'], $params['hero_pk'], $result_arr['m_buil_pk'], null, null, $result_arr['build_time'], null, $position_type, $params['castle_pk'], $result_arr['level'], $time_pk);

    return $Render->nsXhrReturn('success', null, ['build_time' => $result_arr['build_time']]);
}));

$app->post('/api/build/now', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Lord = new Lord($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['building']);
    $BuildConstruction = new BuildConstruction($Session, $PgGame);

    $result = $BuildConstruction->now($params['posi_pk'], $params['castle_pk'], $params['m_buil_pk']);
    if (!$result) {
        global $NsGlobal;
        return $Render->nsXhrReturn('error', $NsGlobal->getErrorMessage(), $NsGlobal->getErrorData());
    }
    // Log
    $description = $_M['BUIL'][$result['m_buil_pk']]['title'] . '[curr['. $result['current_level'] . '];update['. ($result['next_level']) . '];];';
    $Log->setConstruction($Session->lord['lord_pk'], $params['posi_pk'], 'instant', $description, 0, 0, 0, $result['m_buil_pk'], null, null, 0, null, $result['position_type'], $params['castle_pk'], $result['current_level'], 0);

    // 패키지 구매 창
    $Lord->checkPackage($Session->lord['lord_pk'], 'construction', $params['m_buil_pk'], $result['next_level']);

    return $Render->nsXhrReturn('success');
}));

// 건물 이동
$app->post('/api/build/move', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Bdic = new Bdic($Session, $PgGame, $Resource, $GoldPop);
    $Bdoc = new Bdoc($Session, $PgGame, $Resource, $GoldPop);
    $Log = new Log($Session, $PgGame);

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['building']);

    // 건물 이동이 불가능한 건물 (대전, 성벽)

    // 내성 기준 테스트
    $a = $params['main_cast_pk'];
    $b = $params['sub_cast_pk'];
    $lord_pk = $Session->lord['lord_pk'];
    $posi_pk = $Session->lord['main_posi_pk'];

    if ($params['castle_type'] !== 'bdoc') {
        $PgGame->query('SELECT m_buil_pk, in_castle_pk, status, assign_hero_pk FROM building_in_castle WHERE posi_pk = $3 and in_castle_pk IN ($1, $2)', [$a, $b, $posi_pk]);
    } else {
        $PgGame->query('SELECT m_buil_pk, out_castle_pk, status FROM building_out_castle WHERE posi_pk = $3 and out_castle_pk IN ($1, $2)', [$a, $b, $posi_pk]);
    }
    $PgGame->fetchAll();
    $rows = $PgGame->rows;
    $pk_check = []; // 건물 존재 유무
    $build_update = []; // build 테이블 업데이트 유무
    foreach ($rows as $row) {
        if ($row['status'] !== 'N') {
            throw new ErrorHandler('error', $i18n->t('msg_building_move_status_error')); // 건설/업그레이드 중인 건물은 이동 할 수 없습니다.
        }

        if ($params['castle_type'] !== 'bdoc') {
            if (isset($row['assign_hero_pk']) && $row['assign_hero_pk'] > 0) {
                throw new ErrorHandler('error', $i18n->t('msg_building_move_assign_hero_error')); // 영웅이 배속이 되어있는 건물은 이동 할 수 없습니다.
            }

            if ($row['m_buil_pk'] === PK_BUILDING_ARMY) { // 훈련
                if ($Timer->checkTimer($posi_pk, ['A'], $row['in_castle_pk']) > 0) {
                    throw new ErrorHandler('error', $i18n->t('msg_building_move_command_error')); // 명령 수행 중인 건물은 건물 이동이 불가능합니다.
                }
                $build_update[$row['in_castle_pk']] = 'A';
            } else if ($row['m_buil_pk'] === PK_BUILDING_TECHNIQUE) { // 연구
                if ($Timer->checkTimer($posi_pk, ['T']) > 0) {
                    throw new ErrorHandler('error', $i18n->t('msg_building_move_command_error')); // 명령 수행 중인 건물은 건물 이동이 불가능합니다.
                }
                $build_update[$row['in_castle_pk']] = 'T';
            } else if ($row['m_buil_pk'] === PK_BUILDING_RECEPTIONHALL && $Timer->checkTimer($posi_pk, ['E', 'I']) > 0) { // 탐색, 초빙
                throw new ErrorHandler('error', '명령 수행 중인 건물은 건물 이동이 불가능합니다.');
            } else if ($row['m_buil_pk'] === PK_BUILDING_MEDICAL) { // 치료
                if ($Timer->checkTimer($posi_pk, ['M']) > 0) {
                    throw new ErrorHandler('error', $i18n->t('msg_building_move_command_error')); // 명령 수행 중인 건물은 건물 이동이 불가능합니다.
                }
                $build_update[$row['in_castle_pk']] = 'M';
            }
            $pk_check[$row['in_castle_pk']] = $row['m_buil_pk'];
        } else {
            $pk_check[$row['out_castle_pk']] = $row['m_buil_pk'];
        }

    }

    try {
        $PgGame->begin();

        if ($params['castle_type'] !== 'bdoc') {
            if (isset($pk_check[$a]) && isset($pk_check[$b])) {
                $PgGame->query("UPDATE building_in_castle SET in_castle_pk = $3 WHERE posi_pk = $1 AND in_castle_pk = $2", [$posi_pk, $a, 0]); // A 를 임시 0 으로
                $PgGame->query("UPDATE building_in_castle SET in_castle_pk = $3 WHERE posi_pk = $1 AND in_castle_pk = $2", [$posi_pk, $b, $a]); // B 를 A 로
                $PgGame->query("UPDATE building_in_castle SET in_castle_pk = $3 WHERE posi_pk = $1 AND in_castle_pk = $2", [$posi_pk, 0, $b]); // A 를 B 로
            }  else if (isset($pk_check[$a]) && !isset($pk_check[$b])) {
                $PgGame->query("UPDATE building_in_castle SET in_castle_pk = $3 WHERE posi_pk = $1 AND in_castle_pk = $2", [$posi_pk, $a, $b]); // A 를 B 로
            }  else if (!isset($pk_check[$a]) && isset($pk_check[$b])) {
                $PgGame->query("UPDATE building_in_castle SET in_castle_pk = $3 WHERE posi_pk = $1 AND in_castle_pk = $2", [$posi_pk, $b, $a]); // B 를 A 로
            }

            // build 테이블 업데이트 필요. A, M, T (훈련, 치료, 연구)
            if (isset($build_update[$a]) && isset($build_update[$b])) { // 둘다 Queue 값이 존재하면
                $PgGame->query("UPDATE build SET in_cast_pk = $4 WHERE posi_pk = $1 AND type = $2 AND in_cast_pk = $3", [$posi_pk, $build_update[$a], $a, 0]); // A 를 임시 0 으로
                $PgGame->query("UPDATE build SET in_cast_pk = $4 WHERE posi_pk = $1 AND type = $2 AND in_cast_pk = $3", [$posi_pk, $build_update[$b], $b, $a]); // B 를 A 로
                $PgGame->query("UPDATE build SET in_cast_pk = $4 WHERE posi_pk = $1 AND type = $2 AND in_cast_pk = $3", [$posi_pk, $build_update[$a], 0, $b]); // A 를 B 로
            } else if (isset($build_update[$a]) && !isset($build_update[$b])) { // A만 Queue 값이 존재하면
                $PgGame->query("UPDATE build SET in_cast_pk = $4 WHERE posi_pk = $1 AND type = $2 AND in_cast_pk = $3", [$posi_pk, $build_update[$a], $a, $b]); // A 를 B 로
            } else if (!isset($build_update[$a]) && isset($build_update[$b])) { // B만 Queue 값이 존재하면
                $PgGame->query("UPDATE build SET in_cast_pk = $4 WHERE posi_pk = $1 AND type = $2 AND in_cast_pk = $3", [$posi_pk, $build_update[$b], $b, $a]); // B 를 A 로
            }
            // 건물이 바뀌는 부분 Push
            $Bdic->updatePosition($posi_pk, $a, ! isset($pk_check[$b]), $lord_pk);
            $Bdic->updatePosition($posi_pk, $b, ! isset($pk_check[$a]), $lord_pk);
        } else {
            if (isset($pk_check[$a]) && isset($pk_check[$b])) {
                $PgGame->query("UPDATE building_out_castle SET out_castle_pk = $3 WHERE posi_pk = $1 AND out_castle_pk = $2", [$posi_pk, $a, 0]); // A 를 임시 0 으로
                $PgGame->query("UPDATE building_out_castle SET out_castle_pk = $3 WHERE posi_pk = $1 AND out_castle_pk = $2", [$posi_pk, $b, $a]); // B 를 A 로
                $PgGame->query("UPDATE building_out_castle SET out_castle_pk = $3 WHERE posi_pk = $1 AND out_castle_pk = $2", [$posi_pk, 0, $b]); // A 를 B 로
            }  else if (isset($pk_check[$a]) && !isset($pk_check[$b])) {
                $PgGame->query("UPDATE building_out_castle SET out_castle_pk = $3 WHERE posi_pk = $1 AND out_castle_pk = $2", [$posi_pk, $a, $b]); // A 를 B 로
            }  else if (!isset($pk_check[$a]) && isset($pk_check[$b])) {
                $PgGame->query("UPDATE building_out_castle SET out_castle_pk = $3 WHERE posi_pk = $1 AND out_castle_pk = $2", [$posi_pk, $b, $a]); // B 를 A 로
            }

            // 건물이 바뀌는 부분 Push
            $Bdoc->updatePosition($posi_pk, $a, ! isset($pk_check[$b]));
            $Bdoc->updatePosition($posi_pk, $b, ! isset($pk_check[$a]));
        }

        $PgGame->commit();
    } catch (Throwable $e) {
        $PgGame->rollback();
        print_r($e->getMessage());
    }

    return $Render->nsXhrReturn('success');
}));