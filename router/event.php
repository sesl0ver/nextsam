<?php
global $app, $Render, $i18n;

$app->post('/api/event/attendance', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Event = new Event($PgGame);

    // 아이템
    $reward = [];
    $reward[] = false; // 0일은 없으니 빈값
    $reward[] = ['reward_type' => 'item', 'pk' => 500802, 'value' => 10]; // 01일차 : 위조된 지원령
    $reward[] = ['reward_type' => 'item', 'pk' => 500544, 'value' => 3]; // 02일차 : 자원팩 1
    $reward[] = ['reward_type' => 'item', 'pk' => 500802, 'value' => 10]; // 03일차 : 위조된 지원령
    $reward[] = ['reward_type' => 'item', 'pk' => 500810, 'value' => 1]; // 04일차 : 생산량 증가 아이템 보따리
    $reward[] = ['reward_type' => 'hero', 'pk' => 120159, 'value' => 1]; // 05일차 : 4성 영웅 정보
    $reward[] = ['reward_type' => 'item', 'pk' => 500102, 'value' => 1]; // 06일차 : 건설허가서(3일)
    $reward[] = ['reward_type' => 'item', 'pk' => 500061, 'value' => 1]; // 07일차 : 행운의 주화
    $reward[] = ['reward_type' => 'item', 'pk' => 500085, 'value' => 1]; // 08일차 : 영석
    $reward[] = ['reward_type' => 'item', 'pk' => 500802, 'value' => 20]; // 09일차 : 위조된 지원령
    $reward[] = ['reward_type' => 'hero', 'pk' => 120092, 'value' => 1]; // 10일차 : 4성 영웅 유우
    $reward[] = ['reward_type' => 'item', 'pk' => 500164, 'value' => 3]; // 11일차 : 즉시회군
    $reward[] = ['reward_type' => 'item', 'pk' => 500114, 'value' => 1]; // 12일차 : 기술주머니
    $reward[] = ['reward_type' => 'item', 'pk' => 500810, 'value' => 2]; // 13일차 : 생산량 증가 아이템 보따리
    $reward[] = ['reward_type' => 'item', 'pk' => 500103, 'value' => 2]; // 14일차 : 우수 영웅 모집 티켓
    $reward[] = ['reward_type' => 'hero', 'pk' => 120010, 'value' => 1]; // 15일차 : 4성 영웅 마운록
    $reward[] = ['reward_type' => 'item', 'pk' => 500036, 'value' => 10]; // 16일차 : 일반 지원령
    $reward[] = ['reward_type' => 'item', 'pk' => 500165, 'value' => 3]; // 17일차 : 난민 즉시 수용
    $reward[] = ['reward_type' => 'item', 'pk' => 500015, 'value' => 1]; // 18일차 : 평화서약
    $reward[] = ['reward_type' => 'item', 'pk' => 500103, 'value' => 2]; // 19일차 : 우수 영웅 모집 티켓
    $reward[] = ['reward_type' => 'hero', 'pk' => 120328, 'value' => 1]; // 20일차 : 5성 영웅 서서
    $reward[] = ['reward_type' => 'item', 'pk' => 500036, 'value' => 10]; // 21일차 : 일반 지원령
    $reward[] = ['reward_type' => 'item', 'pk' => 500811, 'value' => 1]; // 22일차 : 전투 아이템 보따리
    $reward[] = ['reward_type' => 'item', 'pk' => 500085, 'value' => 2]; // 23일차 : 영석
    $reward[] = ['reward_type' => 'item', 'pk' => 500103, 'value' => 2]; // 24일차 : 우수 영웅 모집 티켓
    $reward[] = ['reward_type' => 'hero', 'pk' => 120144, 'value' => 1]; // 25일차 : 5성 영웅 고순
    $reward[] = ['reward_type' => 'item', 'pk' => 500038, 'value' => 3]; // 26일차 : 삼급 지원령
    $reward[] = ['reward_type' => 'item', 'pk' => 500116, 'value' => 1]; // 27일차 : 고급기술상자
    $reward[] = ['reward_type' => 'item', 'pk' => 500708, 'value' => 1]; // 28일차 : 특수 조합석
    $reward[] = ['reward_type' => 'item', 'pk' => 500119, 'value' => 1]; // 29일차 : 고급기술열쇠
    $reward[] = ['reward_type' => 'hero', 'pk' => 120007, 'value' => 1]; // 30일차 : 6성 영웅 대교

    // my_event 에 데이터가 존재하는지 먼저 확인
    $Event->checkMyEvent($Session->lord['lord_pk']);

    // 출석 이벤트 데이터 가져오기
    $PgGame->query('SELECT attendance_cnt, last_attendance_dt::date, (now() - interval \'1 days\')::date as attendance_dt, now()::date as today_dt FROM my_event WHERE lord_pk = $1', Array($Session->lord['lord_pk']));
    $PgGame->fetch();

    $today = $PgGame->row['today_dt'];
    $cnt = $PgGame->row['attendance_cnt'];
    $last_attendance_dt = $PgGame->row['last_attendance_dt'];
    $attendance_dt = $PgGame->row['attendance_dt'];

    if ($today != $last_attendance_dt) {
        // 연속 출석 실패시 초기화
        // if ($last_attendance_dt != $attendance_dt) {
        //     $cnt = 1;
        // } else {
        //     $cnt = $cnt + 1;
        //     if ($cnt > count($reward)) {
        //         $cnt = count($reward);
        //     }
        // }
        $cnt = $cnt + 1;
        /*if ($cnt >= count($reward)) {
            $cnt = 1; // 첫날로 돌아감.
        }*/

        // 모두 지급되면 마무리
        if ($cnt < count($reward)) {
            // 출석 처리 및 아이템 지급
            global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
            try {
                $PgGame->begin();
                $_NS_SQ_REFRESH_FLAG = true;

                // 출석 처리
                $PgGame->query('UPDATE my_event SET attendance_cnt = $1, last_attendance_dt = now() WHERE lord_pk = $2 RETURNING attendance_cnt, date_part(\'epoch\', last_attendance_dt)::integer as last_attendance_dt', [$cnt, $Session->lord['lord_pk']]);
                $PgGame->fetch();
                $Session->sqAppend('EVENT', $PgGame->row, null, $Session->lord['lord_pk']);

                // 보상 지급
                if ($reward[$cnt]['reward_type'] === 'hero') { // 영웅 지급
                    global $_M;
                    $NsGlobal->requireMasterData(['hero_base']);
                    $Hero = new Hero($Session, $PgGame);
                    $m_hero_base = $_M['HERO_BASE'][$reward[$cnt]['pk']];
                    $hero_pk = $Hero->getNewHero('FREE', $reward[$cnt]['value'], $m_hero_base['rare_type'], $reward[$cnt]['pk'], null, null, null, 'attendance_event');
                    $Hero->setMyHeroCreate($hero_pk, $Session->lord['lord_pk'], 'V', null, null, 'N', "attendance_event");
                } else { // 아이템 지급
                    $Item = new Item($Session, $PgGame);
                    $ret = $Item->buyItem($Session->lord['lord_pk'], $reward[$cnt]['pk'], $reward[$cnt]['value'], 'attendance_event');
                    if (! $ret) {
                        throw new Exception($NsGlobal->getErrorMessage());
                    }
                }

                $PgGame->commit();
            } catch(Exception $e) {
                $PgGame->rollback();
                throw new ErrorHandler('error', $e->getMessage(), true);
            }

            $_NS_SQ_REFRESH_FLAG = false;
            $NsGlobal->commitComplete();
        }
    }

    return $Render->nsXhrReturn('success', null, ['cnt' => $cnt, 'reward_list' => $reward]);
}));

$app->post('/api/event/timeBuff', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $_M;
    $PgGame->query('SELECT time_buff_count FROM my_event WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $PgGame->fetch();
    $Session->sqAppend('EVENT', [
        ...$PgGame->row,
        'time_buff_max' => $_M['TIME_BUFF']['max_count'],
        'time_buff_end' => strtotime($_M['TIME_BUFF']['end_date']),
    ], null, $Session->lord['lord_pk']);


    return $Render->nsXhrReturn('success');
}));

$app->post('/api/event/treasure', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Event = new Event($PgGame);
    $Item = new Item($Session, $PgGame);

    global $_M, $NsGlobal;
    $items = $Item->getItemList($Session->lord['lord_pk'], $_M['TREASURE_EVENT']['material_item']);
    // 이벤트 기간내 인지
    $today_time = date('Y-m-d H:i:s');
    if ($today_time < $_M['TREASURE_EVENT']['start_date'] || $today_time >= $_M['TREASURE_EVENT']['end_date']) {
        throw new Exception($i18n->t('msg_item_use_failed_event_end'));
    }
    // 재료가 충분하니 아이템 지급
    if (! $Event->checkTreasureEvent($items)) {
        throw new Exception($i18n->t('msg_not_enough_material_items'));
    }

    // 우선 재료아이템 차감
    foreach ($items as $_item_pk => $_item_cnt) {
        $Item->useItem($Session->lord['main_posi_pk'], $Session->lord['lord_pk'], $_item_pk, 1, ['_use_type' => 'treasure_event']);
    }

    // 보상 아이템 지급. 특정 아이템을 이용하여 랜덤 지급.
    $result = $Item->setRandomItem($Session->lord['lord_pk'], $_M['TREASURE_EVENT']['reward_item'], 1);
    if (isset($result['err'])) {
        throw new ErrorHandler('error', 'Error Occurred. [41001]');
    }
    // 어차피 1개만 나오므로 TODO 그럼왜 setRandomItem 구조는 저러냐...
    $item_pk = 0;
    $item_cnt = 0;
    foreach ($result['item'] as $_item_pk => $_item_cnt) {
        $item_pk = $_item_pk;
        $item_cnt = $_item_cnt;
    }
    return $Render->nsXhrReturn('success', null, ['acquire' => true, 'm_item_pk' => $item_pk, 'item_count' => $item_cnt]);
}));


// TODO 사용안함.
/*$app->post('/api/event/getLimitEventItem', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['even_limi_date_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $even_limi_date_pk = $params['even_limi_date_pk'];

    // 진행 중인 이벤트 인지 체크
    $PgGame->query('SELECT m_item_pk, item_cnt FROM event_limited_date WHERE start_dt <= now() AND end_dt > now() AND even_limi_date_pk = $1', [$even_limi_date_pk]);
    $PgGame->fetch();
    $eventInfo = $PgGame->row;
    if (!$eventInfo['m_item_pk']) {
        throw new ErrorHandler('error', '이미 종료된 이벤트 입니다.');
    }

    // 진행 중인 이벤트라면  이미 지급 받은 아이템인지 체크
    $PgGame->query('SELECT lord_pk FROM event_gift_lord_info WHERE lord_pk = $1 AND even_limi_date_pk = $2', [$Session->lord['lord_pk'], $even_limi_date_pk]);
    if ($PgGame->fetchOne()) {
        throw new ErrorHandler('error', '이미 수령한 아이템입니다.');
    }

    // 지급 받은 기록이 없다면 지급 시작
    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();
        $_NS_SQ_REFRESH_FLAG = true;

        // 아이템 지급
        $Item = new Item($Session, $PgGame);
        $ret = $Item->BuyItem($Session->lord['lord_pk'], $eventInfo['m_item_pk'], $eventInfo['item_cnt'], 'limit_event');
        if (!$ret) {
            throw new Exception($NsGlobal->getErrorMessage());
        }

        // 아이템 지급
        $ret = $PgGame->query('INSERT INTO event_gift_lord_info (lord_pk, even_limi_date_pk) VALUES ($1, $2)', [$Session->lord['lord_pk'], $even_limi_date_pk]);
        if (!$ret) {
            throw new Exception('오류가 발생했습니다. (ERR_3)');
        }
        $PgGame->commit();
    } catch(Exception $e) {
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success');
}));*/