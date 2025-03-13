<?php
global $app, $Render, $i18n;

$app->post('/api/position/list', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT t1.posi_pk, t2.title FROM position t1, territory t2
WHERE t1.posi_pk = t2.posi_pk AND t1.lord_pk = $1 ORDER BY t2.title::BYTEA, t1.posi_pk', [$Session->lord['lord_pk']]);
    $PgGame->fetchAll();

    return $Render->nsXhrReturn('success', null, $PgGame->rows);
}));

$app->post('/api/position/change', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();

    // LP/SQ 초기화
    $r = $Session->sqInit($params['npp'], true);
    if (!$r) {
        throw new ErrorHandler('ign', 'Error Occurred. [33001]'); // 영지 이동 중 오류 발생
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/position/founding', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 영지슬롯 확인
    $PgGame->query('SELECT level, position_cnt FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $PgGame->fetch();
    $r = $PgGame->row;

    global $_M;
    if ($_M['LORD_GRADE_TERRITORY_COUNT'][$r['level']] <= $r['position_cnt']) {
        throw new ErrorHandler('error', $i18n->t('msg_territory_limit_count')); // 영지 보유 한계를 초과하였습니다.
    }

    $PgGame->query('SELECT count(lord_pk) FROM position WHERE lord_pk = $1 AND type = $2', [$Session->lord['lord_pk'], 'T']);
    $position_cnt = $PgGame->fetchOne();
    if ($_M['LORD_GRADE_TERRITORY_COUNT'][$r['level']] <= $position_cnt) {
        throw new ErrorHandler('error', $i18n->t('msg_territory_limit_count')); // 영지 보유 한계를 초과하였습니다.
    }

    // target_posi_pk 확인 - 소유 평지 인지
    $PgGame->query('SELECT lord_pk, type FROM position WHERE posi_pk = $1', [$params['target_posi_pk']]);
    $PgGame->fetch();
    $r =& $PgGame->row;

    if ($r['lord_pk'] != $Session->lord['lord_pk']) {
        throw new ErrorHandler('error', $i18n->t('msg_mismatched_lord_information')); // 소유 정보가 일치하지 않습니다.
    }

    if ($r['type'] != 'E' && $r['type'] != 'A') {
        throw new ErrorHandler('error', $i18n->t('msg_no_field')); // 평지가 아닙니다.
    }

    // 주둔부대 확인
    $PgGame->query('SELECT troo_pk FROM troop WHERE status = $1 AND src_lord_pk = $2 AND dst_posi_pk = $3', ['C', $Session->lord['lord_pk'], $params['target_posi_pk']]);
    $troo_pk = $PgGame->fetchOne();
    if (! $troo_pk) {
        throw new ErrorHandler('error', 'Error Occurred. [33002]'); // 주둔부대 정보를 찾을 수 없습니다.
    }

    // 트랜젹션 시작
    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();
        $_NS_SQ_REFRESH_FLAG = true;

        // 아이템 소모
        $Item = new Item($Session, $PgGame);
        $ret = $Item->useItem($params['posi_pk'], $Session->lord['lord_pk'], '500083', 1, ['_yn_quest' => true]);
        if(!$ret) {
            throw new Exception('Error Occurred. [33003]'); // 필요 아이템이 부족하여 영지 건설에 실패
        }

        // 황금 차감
        $GoldPop = new GoldPop($Session, $PgGame);
        $r = $GoldPop->decreaseGold($params['posi_pk'], 10000, null, 'terr_founding');
        if (! $r) {
            throw new Exception('Error Occurred. [33004]'); // 황금 부족하여 영지 건설에 실패
        }

        // 자원 차감
        $res = [];
        $res['food'] = 10000;
        $res['horse'] = 10000;
        $res['lumber'] = 10000;
        $res['iron'] = 10000;

        $Resource = new Resource($Session, $PgGame);
        $r = $Resource->decrease($params['posi_pk'], $res, null, 'terr_founding');
        if (! $r) {
            throw new Exception('Error Occurred. [33005]'); // 자원이 부족하여 영지 건설에 실패
        }

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();


    // flag
    $PgGame->query('SELECT flag FROM territory WHERE posi_pk = $1', [$params['posi_pk']]);
    $flag = $PgGame->fetchOne();

    // createterritory 프로시저
    $Army = new Army($Session, $PgGame, $Resource, $GoldPop);
    $Log = new Log($Session, $PgGame);
    $Territory = new Territory($Session, $PgGame);
    if ($Territory->createterritory($Session->lord['lord_pk'], $params['target_posi_pk'], $flag)) {
        // 영지 건설 영향력 추가
        $power = 500 - 50; // 영지 건설 500, 기존 자원지 50 차감
        $Lord = new Lord($Session, $PgGame);
        $Lord->increasePower($Session->lord['lord_pk'], $power, $params['target_posi_pk']);

        // 영지 초기값
        $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
        $FigureReCalc->createTerritory($params['target_posi_pk']);

        // 주둔부대 처리
        $Troop = new Troop($Session, $PgGame);
        $row = $Troop->getTroop($troo_pk);

        // 인솔 영웅들 임무 해제
        $Hero = new Hero($Session, $PgGame);
        $Hero->unsetCommand($row['captain_hero_pk']);
        if ($row['director_hero_pk'])
            $Hero->unsetCommand($row['director_hero_pk']);
        if ($row['staff_hero_pk'])
            $Hero->unsetCommand($row['staff_hero_pk']);

        // 부대를 편입
        // 영웅 (성주, 기타)
        $Hero->setTerritory($row['captain_hero_pk'], $row['dst_posi_pk']);
        if ($row['director_hero_pk'])
            $Hero->setTerritory($row['director_hero_pk'], $row['dst_posi_pk']);
        if ($row['staff_hero_pk'])
            $Hero->setTerritory($row['staff_hero_pk'], $row['dst_posi_pk']);

        // 성주 할당
        $r = $Hero->setCommand($row['dst_posi_pk'], $row['captain_hero_pk'], 'A');
        if ($r) {
            $Bdic = new Bdic($Session, $PgGame, $Resource, $GoldPop);
            $Bdic->heroAssign($row['dst_posi_pk'], 1, $row['captain_hero_pk']);
        }

        // 자원
        if ($row['reso_food'] > 0 || $row['reso_horse'] > 0 || $row['reso_lumber'] > 0 || $row['reso_iron'] > 0) {
            $zArr = ['food' => $row['reso_food'], 'horse' => $row['reso_horse'], 'lumber' => $row['reso_lumber'], 'iron' => $row['reso_iron']];
            $r = $Resource->increase($row['dst_posi_pk'], $zArr, $row['src_lord_pk'], 'terr_founding');
            if (!$r) {
                // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '영지 건설시 자원 편입 오류'); TODO 로그
            }
        }

        // 황금
        if ($row['reso_gold'] > 0) {
            $r = $GoldPop->increaseGold($row['dst_posi_pk'], $row['reso_gold'], $row['src_lord_pk'], 'terr_founding');
            if (!$r) {
                // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, '영지 건설시 황금 편입 오류'); TODO 로그
            }
        }

        // 병력
        $army_arr = [];
        foreach ($row AS $k => $v) {
            if (str_starts_with($k, 'army_')) {
                $army_arr[substr($k, 5)] = $v;
            }
        }

        $Army->returnArmy($row['dst_posi_pk'], $army_arr);

        // 퀘스트
        $Quest = new Quest($Session, $PgGame);
        $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'battle', 'type' => 'new_territory']);

        // 부대삭제
        $Troop->removeTroop($troo_pk);

        // 밸리삭제
        $PgGame->query('DELETE FROM territory_valley WHERE posi_pk = $1 AND valley_posi_pk = $2', [$params['posi_pk'], $params['target_posi_pk']]);

        $Log->setTerritory($Session->lord['lord_pk'], $params['target_posi_pk'], 'new_territory');
    } else {
        // 영지 생성 실패시, 병력과 자원, 아이템 모두 return
        // 1.아이템 지급
        $arr = [];
        $arr['500083']['item_count'] = 1;
        $Item->setGiveItem($arr, $Session->lord['lord_pk'], false, 'TerrFoundingFail');

        // 2. 민병 지급 - TODO 이제 민병 차출 안하지 않나?
        $army_arr = ['worker' => 400];
        $Army->returnArmy($params['posi_pk'], $army_arr);
        $Army->get($params['posi_pk']);

        // 3. 황금 지급
        $GoldPop->increaseGold($params['posi_pk'], 10000, $Session->lord['lord_pk'], 'terr_founding_fail');

        // 4. 자원 지급
        $res = [];
        $res['food'] = 10000;
        $res['horse'] = 10000;
        $res['lumber'] = 10000;
        $res['iron'] = 10000;

        $Resource->increase($params['posi_pk'], $res, $Session->lord['lord_pk'], 'terr_founding_fail');

        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    $Session->setLoginReload();

    // PUSH
    $Session->sqAppend('PUSH', ['MULTI' => true]);

    // 개척영지 보호모드
    $Territory->setTruceStatus($params['target_posi_pk'], 'D', 500106);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/position/abandon', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // Validation 검사를 위해...
    $params['posi_pk'] = $params['target_posi_pk'];

    $PgGame->query('SELECT lord_pk FROM position WHERE posi_pk = $1', [$params['posi_pk']]);
    $owner_lord_pk = $PgGame->fetchOne();

    if ($Session->lord['main_posi_pk'] == $params['posi_pk']) {
        throw new ErrorHandler('error', $i18n->t('msg_main_territory_abandon_fail')); // 군주가 있는 영지는 포기 할 수 없습니다.
    } else if ($owner_lord_pk != $Session->lord['lord_pk']) {
        throw new ErrorHandler('error', $i18n->t('msg_mismatched_lord_information')); // 소유정보가 일치하지 않습니다.
    }

    $Territory = new Territory($Session, $PgGame);
    if (!$Territory->giveupterritory($Session->lord['lord_pk'], $params['posi_pk'])) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    $Session->setLoginReload();

    // PUSH
    $Session->sqAppend('PUSH', ['MULTI' => true]);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/position/giveUp', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // Validation 검사를 위해...
    $params['posi_pk'] = $params['target_posi_pk'];

    $PgGame->query('SELECT lord_pk, type FROM position WHERE posi_pk = $1', [$params['posi_pk']]);
    $PgGame->fetch();
    $row = $PgGame->row;

    $owner_lord_pk = $row['lord_pk'];
    $type = $row['type'];

    if ($owner_lord_pk != $Session->lord['lord_pk']) {
        throw new ErrorHandler('error', $i18n->t('msg_mismatched_lord_information')); // 소유정보가 일치하지 않습니다.
    }

    if ($type == 'T') {
        throw new ErrorHandler('error', $i18n->t('msg_no_valley')); // 외부 자원지가 아닙니다.
    }

    $Troop = new Troop($Session, $PgGame);
    if (!$Troop->lossOwnershipValley($Session->lord['lord_pk'], $params['posi_pk'])) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    $PgGame->query('SELECT troo_pk FROM troop WHERE dst_posi_pk = $1', [$params['posi_pk']]);

    while ($PgGame->fetch()) {
        $Troop->setStatusRecall($PgGame->row['troo_pk']);
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/position/cardDeckReload', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // TODO 이게 왜 position 에 와있지?
    $Hero = new Hero($Session, $PgGame);
    $heroes = $Hero->getMyAppoHeroes($Session->lord['lord_pk'], $params['posi_pk']);
    $Session->sqAppend('HERO', $heroes);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/position/terrMoveState', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $avail_move = 0;
    $agree_move = 0;

    $PgGame->query("SELECT t3.posi_area_pk, COUNT(t3.posi_area_pk) as cnt FROM position_region t2, position_area t3, position t4
WHERE t2.posi_regi_pk = t3.posi_regi_pk AND t3.posi_area_pk = t4.posi_area_pk AND t2.posi_stat_pk = $1 and t4.type IN ('E', 'A')
GROUP BY t3.posi_area_pk", [$params['posi_stat_pk']]);
    $PgGame->fetchAll();
    $area_arr = $PgGame->rows;
    if (count($area_arr) > 0) {
        foreach($area_arr as $row) {
            if ($row['cnt'] >= 10) {
                ++$avail_move;
            }
            if ($row['cnt'] > 110) {
                ++$agree_move;
            }
        }
    }
    return $Render->nsXhrReturn('success', null, ['avail_area_count' => $avail_move, 'agree_area_count' => $agree_move]);
}));

$app->post('/api/position/terrMoveArea', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('select count(posi_pk) from position where type IN (\'E\',\'A\') AND posi_area_pk = $1', [$params['posi_area_pk']]);
    $avail_move = $PgGame->fetchOne();
    $avail_move = (!$avail_move) ? 0 : $avail_move;
    return $Render->nsXhrReturn('success', null, ['avail_earth_count' => $avail_move]);
}));

$app->post('/api/position/terrMoveTerr', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 영지명 , 좌표 , 대전 배속 영웅 m_hero_pk , population , territory.loyalty
    $PgGame->query('SELECT t1.posi_pk, t1.title, t2.m_hero_pk, t1.loyalty, t1.population_curr FROM territory t1, hero t2
WHERE posi_pk IN (SELECT posi_pk FROM position WHERE lord_pk = $1) AND t1.lord_hero_pk = t2.hero_pk', [$Session->lord['lord_pk']]);
    $PgGame->fetchAll();
    return $Render->nsXhrReturn('success', null, ['my_posi_list' => ((!$PgGame->rows || count($PgGame->rows) < 1) ? [] : $PgGame->rows)]);
}));
