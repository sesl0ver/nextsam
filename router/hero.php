<?php
global $app, $Render, $i18n;

$app->post('/api/hero/assign', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Hero = new Hero($Session, $PgGame);

    $PgGame->query('SELECT posi_pk FROM my_hero WHERE hero_pk = $1', [$params['hero_pk']]);
    $posi_pk = $PgGame->fetchOne();
    if ($posi_pk != $params['posi_pk']) {
        throw new ErrorHandler('error', $i18n->t('msg_hero_assign_other_error')); // 영웅배속불가 (명령 영지의 소속 장수가 아닙니다.)
    }

    $PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND in_castle_pk = $2', [$params['posi_pk'], $params['castle_pk']]);
    $level = $PgGame->fetchOne();
    if ($level < 1) {
        throw new ErrorHandler('error', $i18n->t('msg_hero_assign_building_error')); // 영웅배속불가 (건물 레벨 1이상 필요)
    }

    // 이미 배속된 영웅이 있으면 자동 해제
    global $_NS_SQ_REFRESH_FLAG, $NsGlobal;
    try {
        $PgGame->begin();
        $_NS_SQ_REFRESH_FLAG = true;

        $Resource = new Resource($Session, $PgGame);
        $GoldPop = new GoldPop($Session, $PgGame);
        $Bdic = new Bdic($Session, $PgGame, $Resource, $GoldPop);

        $assign_hero_pk = $Bdic->heroUnAssign($params['posi_pk'], $params['castle_pk']);
        if (! $assign_hero_pk) {
            // 배속된 영웅이 없을 경우도 있고 해제시 오류가 발생한 경우.
            throw new Exception($NsGlobal->getErrorMessage());
        }
        if (is_numeric($assign_hero_pk)) {
            $Hero->unsetCommand($assign_hero_pk);
        }
        $Hero->setCommand($params['posi_pk'], $params['hero_pk'], 'A');
        $Bdic->heroAssign($params['posi_pk'], $params['castle_pk'], $params['hero_pk']);

        $PgGame->commit();
    } catch (Exception $e) {
        $PgGame->rollback();

        //dubug_mesg남기기
        // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$params['posi_pk'].'];hero_pk['.$params['hero_pk'].'];cast_pk['.$params['castle_pk'].'];');

        return $Render->nsXhrReturn('error', $e->getMessage());
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/hero/unAssign', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Hero = new Hero($Session, $PgGame);

    global $_NS_SQ_REFRESH_FLAG, $NsGlobal;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        $Resource = new Resource($Session, $PgGame);
        $GoldPop = new GoldPop($Session, $PgGame);
        $Bdic = new Bdic($Session, $PgGame, $Resource, $GoldPop);

        $assign_hero_pk = $Bdic->heroUnAssign($params['posi_pk'], $params['castle_pk']);
        if (!$assign_hero_pk) {
            throw new Exception($NsGlobal->getErrorMessage());
        }

        $Hero->unsetCommand($assign_hero_pk);

        $PgGame->commit();
    } catch (Exception $e) {
        $PgGame->rollback();

        //dubug_mesg남기기
        // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$base['src_posi_pk'].'];');

        return $Render->nsXhrReturn('error', $e->getMessage());
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/hero/assignWall', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Hero = new Hero($Session, $PgGame);
    $col = 'wall_'. $params['position']. '_hero_pk';

    $PgGame->query('SELECT '. $col. ' FROM territory WHERE posi_pk = $1', [$params['posi_pk']]);
    $assign_hero_pk = $PgGame->fetchOne();

    global $_NS_SQ_REFRESH_FLAG, $NsGlobal;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        if ($assign_hero_pk){
            $Hero->unsetCommand($assign_hero_pk);
        }

        $Hero->setCommand($params['posi_pk'], $params['hero_pk'], 'A');

        $PgGame->query("UPDATE territory SET $col = $1 WHERE posi_pk = $2", [$params['hero_pk'], $params['posi_pk']]);

        $Territory = new Territory($Session, $PgGame);
        $Territory->get($params['posi_pk'], [$col]);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();

        //dubug_mesg남기기
        // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$base['src_posi_pk'].'];');

        return $Render->nsXhrReturn('error', $e->getMessage());
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/hero/unAssignWall', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Hero = new Hero($Session, $PgGame);

    $col = 'wall_'. $params['position']. '_hero_pk';
    $PgGame->query('SELECT '. $col. ' FROM territory WHERE posi_pk = $1', [$params['posi_pk']]);
    $assign_hero_pk = $PgGame->fetchOne();

    if ($assign_hero_pk) {
        $ret = $Hero->unSetCommand($assign_hero_pk);
        if (!$ret) {
            throw new ErrorHandler('error', 'Error Occurred. [28001]'); // 배속 해제 불가
        }

        $PgGame->query('UPDATE territory SET '. $col. ' = $1 WHERE posi_pk = $2', [null, $params['posi_pk']]);

        $Territory = new Territory($Session, $PgGame);
        $Territory->get($params['posi_pk'], [$col]);
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/hero/dismiss', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Hero = new Hero($Session, $PgGame);

    $Hero->setDismiss($params['hero_pk']);

    return $Render->nsXhrReturn('success', null, ['type' => 'dismiss']);
}));

// 일일 영웅 지급 TODO 사용안함
$app->post('/api/hero/dailyHero', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Hero = new Hero($Session, $PgGame);

    $today = mktime(0, 5, 1);
    $now_hour = date('G');
    $now_minutes = date('i');

    if ($now_hour == 0 && $now_minutes <= 10) {
        return $Render->nsXhrReturn('failed', '자정(0시0분)부터 0시10분까지 10분간<br /><br />지급이 불가능 합니다.');
    }

    $PgGame->query('SELECT date_part(\'epoch\', last_daily_hero_dt)::integer as last_daily_hero_dt FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $last_daily_hero_dt = $PgGame->fetchOne();

    if ($last_daily_hero_dt >= $today) {
        return $Render->nsXhrReturn('failed', '이미 영웅을 지급 받으셨습니다.');
    }

    global $_NS_SQ_REFRESH_FLAG, $NsGlobal;
    try {
        $PgGame->query('BEGIN');

        $_NS_SQ_REFRESH_FLAG = true;

        $hero_pk = $Hero->getNewHero('DAILY_HERO', null, null, null, $Session->lord['level'], null, null, 'daily_hero'); // DAILY_HERO
        if (!$hero_pk) {
            throw new Exception('영웅 지급 중 오류가 발생했습니다.(1)');
        }

        $ret = $Hero->setMyHeroCreate($hero_pk, $Session->lord['lord_pk'], 'V', null, null, 'N', 'daily_hero');
        if (!$ret) {
            throw new Exception('영웅 지급 중 오류가 발생했습니다.(2)');
        }

        //지급 후 시간 업데이트
        $PgGame->query('UPDATE lord SET last_daily_hero_dt = now() WHERE lord_pk = $1', [$Session->lord['lord_pk']]);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->query('ROLLBACK');

        //dubug_mesg남기기
        // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';daily hero error');

        return $Render->nsXhrReturn('error', $e->getMessage());
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    $hero_info = $Hero->getFreeHeroInfo($hero_pk);
    $PgGame->query('SELECT date_part(\'epoch\', last_daily_hero_dt)::integer FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $last_daily_hero_dt = $PgGame->fetchOne();

    return $Render->nsXhrReturn('success', null, ['hero' => $hero_info, 'last_daily_hero_dt' => $last_daily_hero_dt]);
}));

/**
 * 신규 영웅 픽업 API
 * 기존에 불필요한 영웅상자는 모두 제외. 오버랭크, 진노 등...
 */
$app->post('/api/hero/pickup', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Lord = new Lord($Session, $PgGame);
    $Hero = new Hero($Session, $PgGame);
    $Item = new Item($Session, $PgGame);
    $Cash = new Cash($Session, $PgGame);
    $Quest = new Quest($Session, $PgGame);

    global $_M, $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    $NsGlobal->requireMasterData(['hero', 'item', 'pickup']);
    $pickup_type = $params['pickup_type'];
    $_type = $params['type'];
    $m_pickup = $_M['PICK'][$pickup_type];
    if (! isset($m_pickup)) {
        throw new ErrorHandler('error', 'Invalid request. (1)', true);
    }
    $m_item_pk = $m_pickup['m_item_pk'];

    if ($m_pickup['start_date'] != null) {
        $now_time = Useful::nowServerTime();
        $start_time = strtotime($m_pickup['start_date']);
        $end_time = strtotime($m_pickup['end_date']);

        if ($now_time < $start_time || $now_time >= $end_time) {
            throw new ErrorHandler('error', $i18n->t('msg_not_ongoing_pickup')); // 현재 진행 중인 영웅 모집이 아닙니다.
        }
    }

    $need_item_use = !!$m_pickup['m_item_pk']; // 아이템을 사용?
    $need_qbig_use = !$m_pickup['need_qbig'] == 0; // 큐빅을 사용?
    // 사용가능한 재화가 없는 경우는 없음.
    if (! $need_item_use && ! $need_qbig_use) {
        throw new ErrorHandler('error', 'Invalid request. (2)', true);
    }

    $log_qbig_use = 0;
    // 필요재화
    $pickup_count = ($_type == 'multiple') ? 10 : 1;
    $need_item_count = ($_type == 'multiple') ? $m_pickup['item_count'] * $pickup_count : $m_pickup['item_count']; // 10회 뽑기에 경우 * 10
    $need_qbig = $m_pickup['need_qbig'] * $pickup_count;
    if ($need_item_use) {
        $item_count = $Item->getItemCount($Session->lord['lord_pk'], $m_item_pk);
        // 아이템이 부족한데 큐빅으로 구매가 불가능한 경우
        if ($item_count < $need_item_count) {
            if (! $need_qbig_use) {
                throw new ErrorHandler('error', $i18n->t('msg_hero_pickup_need_ticket')); // 영웅 모집에 필요한 아이템이 부족합니다.
            }
        } else {
            $need_qbig_use = false; // 아이템이 충분하면 큐빅은 사용하지 않음.
        }
    }
    if ($need_qbig_use) { // 큐빅 사용이 필요한 경우
        $qbig = $Cash->get($Session->lord['lord_pk']);
        if ($qbig < $need_qbig) {
            // 큐빅이 부족한 경우
            throw new ErrorHandler('error', $i18n->t('msg_hero_pickup_need_qbig')); // 영웅 모집에 필요한 큐빅이 부족합니다.
        }
        $log_qbig_use = $need_qbig;
    }

    // 기본값 정의
    // $lord_level = $Session->lord['level'];
    // $m_hero_base_pk = null;
    // $forces = null; // 진영
    $acquired_type = $m_pickup['acquired_type'] == '' ? 'NORMAL' : $m_pickup['acquired_type']; // 없으면 무조건 NORMAL
    $select_level = 1; // 기본 레벨
    $select_rare = null;
    $upper_rare = 1; // 획득 최대 레어
    $create_reason = $m_pickup['pickup_type']; // 영웅이 어디로 부터 영입되었는지 픽업 번호로 구분
    $pickup_type = $m_pickup['pickup_type']; // 로그에도 픽업 번호로 기록하여 구분

    try {
        $PgGame->begin();
        $_NS_SQ_REFRESH_FLAG = true;

        $user_pickup_count = $Hero->getPickupCount($pickup_type); // TODO DB에 저장하고 가져오는 과정이 필요.

        // 영웅 pickup - 마스터데이터
        $select_m_hero_pks = [];
        $log_pickup_count = [];
        $log_pickup_pity = [];
        for ($i = 0; $i < $pickup_count; $i++) {
            $user_pickup_count++; // 이번 픽업 회차 추가해줌.
            $log_pickup_count[] = $user_pickup_count; // 로그용
            $pity_mode = $m_pickup['pity_limit'] > 0 && $user_pickup_count >= $m_pickup['pity_limit']; // 확정인지? limit = 0인 경우 천장은 없다.
            $log_pickup_pity[] = ($pity_mode) ? 'pity' : 'none'; // 로그용
            if ($pity_mode) {
                // 확정모드인 경우 영웅확정가챠인지 먼저 확인
                $select_level = $m_pickup['pity_level']; // 확정 픽업인 경우 픽업 기준으로 변경.
                if ($m_pickup['pity_hero'] !== '') {
                    // 영웅 확정인 경우 배열 확인
                    $pity_heroes = json_decode($m_pickup['pity_hero'], true); // 배열이므로
                    shuffle($pity_heroes); // 랜덤으로 돌려주고
                    $select_rare = $m_pickup['pity_rare']; // 확정 레어로 덮어씀.
                    $select_m_hero_pks[] = $Hero->getRandomHeroPickup($select_rare, $select_level, ['m_hero_base_pk' => $pity_heroes[0]]); // 무조건 지정한 영웅을 찾아옴.
                } else {
                    // 영웅 확정이 아닌 경우 지정된 레벨과 레어도 기준으로 픽업 - 레어도를 따로 지정 안했다면 직접 찾아옴.
                    $select_rare = $Hero->getRandomRare($select_level, $acquired_type);
                    if ($m_pickup['pity_rare'] != 0 && $select_rare < $m_pickup['pity_rare']) { // 찾아온 레어도가 pity_rare 보다 낮다면
                        $select_rare = $m_pickup['pity_rare']; // 확정 레어로 덮어씀.
                    };
                    $select_m_hero_pks[] = $Hero->getRandomHeroPickup($select_rare, $select_level);
                }
                // $Hero->initPickupCount($pickup_type); // 확정 지급 후 초기화
                $user_pickup_count = 0;
            } else {
                $select_rare = $Hero->getRandomRare($select_level, $acquired_type); // 따로 확률 테이블이 없는 경우 pity 기준으로
                if ($select_rare > $upper_rare) {
                    $upper_rare = $select_rare;
                }
                // 마지막 뽑기 차례에 최대 뽑기 레어도가 특정 레어도 이하라면 최소한 특정 레어도 1회는 지급해 줌. (10연차 보너스)
                if ($i == 9 && $upper_rare < $m_pickup['upper_rare']) {
                    $select_rare = $m_pickup['upper_rare'];
                }
                $select_m_hero_pks[] = $Hero->getRandomHeroPickup($select_rare, $select_level);
                if ($m_pickup['pity_hero'] === '' && $m_pickup['pity_rare'] > 0 && $m_pickup['pity_rare'] <= $select_rare) {
                    // 영웅 확정이 아닌 경우 특정 레어보다 높다면 확정 pickup 카운트를 초기화 시켜줌.
                    $user_pickup_count = 0;
                } else if ($m_pickup['pity_hero'] !== '' && in_array($m_pickup['pity_hero'], $select_m_hero_pks)) {
                    // 영웅 확정인 경우 확정 영웅이 먼저 나온 경우 초기화
                    $user_pickup_count = 0;
                }
            }
        }

        if ($need_qbig_use) {
            // 큐빅을 사용하는 경우
            $qbig = $Cash->decreaseCash($Session->lord['lord_pk'], $need_qbig, "pickup $pickup_type $_type");
            if (!$qbig) {
                throw new Exception($i18n->t('msg_qbig_lack')); // 큐빅이 부족합니다.
            }
        } else {
            // 아이템이 충분한 경우
            $Item->useItem($params['posi_pk'], $Session->lord['lord_pk'], $m_item_pk, $need_item_count, ['_yn_quest' => true]);
        }

        // 영웅 생성 - INSERT INTO hero
        $pickup_heroes = $Hero->createFreeHeroMultiple($select_m_hero_pks, $create_reason);
        $hero_pks = array_map(function ($row) { return $row['hero_pk']; }, $pickup_heroes);

        // 생성한 영웅 지급 - INSERT INTO my_hero , hero_pickup[pickup_type[$pickup_type],type[$_type],pickup_count[$user_pickup_count]];
        $hero_infos = $Hero->createMyHeroMultiple($hero_pks, 'V', ['lord_pk' => $Session->lord['lord_pk'], 'log_qbig_use' => $log_qbig_use, 'pickup_type' => $pickup_type, 'type' => $_type, 'log_pickup_count' => $log_pickup_count, 'log_pickup_pity' => $log_pickup_pity]);

        // 패키지 구매 창
        foreach ($pickup_heroes as $_hero) {
            $Lord->checkPackage($Session->lord['lord_pk'], 'hero', '', $_hero['rare_type']);
        }

        // 영웅 지급 후 픽업 업데이트 - 확정 픽업(천장)이 있는 경우만
        if ($m_pickup['pity_limit'] > 0) {
            $Hero->updatePickupCount($pickup_type, $user_pickup_count);
        }

        $Quest->countCheckQuest($Session->lord['lord_pk'], 'EVENT_HERO_PICKUP', ['value' => $pickup_count]);

        $PgGame->commit();
    } catch (Throwable $e) {
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage());
    }

    $Hero->setUnreadHeroCnt($Session->lord['lord_pk']);

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success', null, ['heroes' => $hero_infos]);
}));