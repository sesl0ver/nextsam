<?php
global $app, $Render, $i18n;

$app->post('/api/heroEncounter/info', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT hero_enco_pk, status, cmd_hero_pk, date_part(\'epoch\', end_dt)::integer AS end_dt_ut, encounter_type, encounter_value, invitation_cnt, yn_invited, type, m_item_pk 
FROM hero_encounter WHERE hero_enco_pk = (SELECT last_hero_enco_pk FROM territory WHERE posi_pk = $1)', [$params['posi_pk']]);
    $PgGame->fetch();

    $ret['count'] = $PgGame->getNumRows();
    $ret['data'] = $PgGame->row;

    if ($ret['count'] > 0) {
        // 영웅이 탐색된 경우 영웅정보 제공
        if ($ret['data']['encounter_type'] == 'hero') {
            $Hero = new Hero($Session, $PgGame);
            $heroInfo = $Hero->getFreeHeroInfo($ret['data']['encounter_value']);
            $ret['hero'] = $heroInfo;
        }

        if ($ret['data']['invitation_cnt'] > 0) {
            $PgGame->query('SELECT status, send_gold, cmd_hero_pk, date_part(\'epoch\', end_dt)::integer AS end_dt_ut FROM hero_invitation WHERE hero_enco_pk = $1 AND status = $2 ORDER BY hero_invi_pk DESC LIMIT 1', [$ret['data']['hero_enco_pk'], 'P']);
            if ($PgGame->getNumRows() > 0) {
                $PgGame->fetch();
                $ret['invitation'] = $PgGame->row;
            }
        }
    }

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/heroEncounter/do', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 해당 영웅이 해당 영지의 영웅인지 확인
    $PgGame->query('SELECT hero_pk FROM my_hero WHERE hero_pk = $2 AND posi_pk = $1', [$params['posi_pk'], $params['hero_pk']]);
    if (!$PgGame->fetch()) {
        throw new ErrorHandler('error', $i18n->t('msg_not_territory_hero')); // 해당 영지의 영웅이 아닙니다.
    }

    // 이미 진행중인 탐색이 있는지 확인
    $PgGame->query('SELECT hero_enco_pk FROM hero_encounter WHERE posi_pk = $1 AND status = $2', [$params['posi_pk'], 'P']);
    if ($PgGame->fetch()) {
        throw new ErrorHandler('error', $i18n->t('msg_already_encounter')); // 이미 탐색이 진행 중 입니다.
    }

    // 탐색이 종료 됐는데, 영웅 상태가 탐색중인게 있을 경우 해당 영웅 탐색 상태 종료
    $PgGame->query('SELECT hero_pk FROM my_hero WHERE posi_pk = $1 AND status_cmd = $2 AND cmd_type= $3', [$params['posi_pk'], 'C', 'Encou']);
    $hero_pk = $PgGame->fetchOne();
    if ($hero_pk) {
        $PgGame->query('UPDATE my_hero SET status_cmd = $2, cmd_type= $3 WHERE hero_pk = $1', [$hero_pk, 'I', 'None']);
    }

    // encouont
    global $NsGlobal, $_NS_SQ_REFRESH_FLAG, $_M;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        $PgGame->query('SELECT last_hero_enco_pk FROM territory WHERE posi_pk = $1 FOR UPDATE', [$params['posi_pk']]);
        $last_hero_enco_pk = $PgGame->fetchOne();

        // 이미 진행중인 탐색이 있는지 확인
        $PgGame->query('SELECT hero_enco_pk FROM hero_encounter WHERE hero_enco_pk = $1 AND status = $2', [$last_hero_enco_pk, 'P']);
        if ($PgGame->fetch()) {
            throw new Exception($i18n->t('msg_already_encounter')); // 이미 탐색이 진행 중 입니다.
        }

        // 아이템 소모
        if (isset($params['m_item_pk'])) {
            $Item = new Item($Session, $PgGame);
            $ret = $Item->useItem($params['posi_pk'], $Session->lord['lord_pk'], $params['m_item_pk'], 1, ['_yn_quest' => true]);
            if(!$ret) {
                throw new Exception($NsGlobal->getErrorMessage());
            }
        }

        $build_time = $_M['ENCOUNTER_TYPE_BUILD_TIME'][$params['encounter_type']];
        // $build_time = 10; // 임시

        $Resource = new Resource($Session, $PgGame);
        $GoldPop = new GoldPop($Session, $PgGame);
        $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
        $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);
        $ret = $Effect->getEffectedValue($params['posi_pk'], ['hero_encounter_time_descrease'], $build_time);
        $build_time = $ret['value'];

        $end_dt = ' now() + Interval \'+'.$build_time.' second\'';

        // 영웅 할당
        $Hero = new Hero($Session, $PgGame);
        $ret = $Hero->setCommand($params['posi_pk'], $params['hero_pk'], 'C', 'Encou');
        if (!$ret) {
            throw new Exception('Can\'t command a hero ('. $NsGlobal->getErrorMessage(). ')');
        }

        // 기존 탐색 정보 삭제
        $PgGame->query('DELETE FROM hero_encounter WHERE posi_pk = $1', [$params['posi_pk']]);

        // 탐색 정보 저장
        $PgGame->query("INSERT INTO hero_encounter ( posi_pk, status, cmd_hero_pk, start_dt, cmd_time, cmd_time_reduce, end_dt, type, m_item_pk)
VALUES ( $1, $2, $3, now(), $4, 0, {$end_dt}, $5, $6)", [$params['posi_pk'], 'P', $params['hero_pk'], $build_time, $params['encounter_type'], $params['m_item_pk']]);
        $hero_enco_pk = $PgGame->currSeq('hero_encounter_hero_enco_pk_seq');

        $PgGame->query('UPDATE territory SET last_hero_enco_pk = $2 WHERE posi_pk = $1', [$params['posi_pk'], $hero_enco_pk]);

        // Timer
        $Timer = new Timer($Session, $PgGame);
        $Troop = new Troop($Session, $PgGame);
        $Timer->set($params['posi_pk'], 'E', $hero_enco_pk, 'E', $i18n->t('timer_hero_encounter_description', [$Troop->getHeroDesc($params['hero_pk'])]), $build_time, $params['in_cast_pk']);

        // Timer pk를 넣어 두기 (취소 요청시 처리를 위해서)
        $PgGame->query('UPDATE hero_encounter SET time_pk = $1 WHERE hero_enco_pk = $2', [$Timer->getTimePk(), $hero_enco_pk]);

        $Log = new Log($Session, $PgGame);
        $Log->setHeroEncounter($Session->lord['lord_pk'], $params['posi_pk'], 'start', $hero_enco_pk, $params['hero_pk'], null, null, null, null, null, null, $Timer->getTimePk(), $params['encounter_type'], $params['m_item_pk']);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'do']);

    return $Render->nsXhrReturn('success', null, ['build_time' => $build_time]);
}));

$app->post('/api/heroEncounter/cancel', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT hero_enco_pk, start_dt, cmd_hero_pk, start_dt, end_dt, encounter_type, encounter_value, invitation_cnt, yn_invited, time_pk, type, m_item_pk  
FROM hero_encounter WHERE hero_enco_pk = (SELECT last_hero_enco_pk FROM territory WHERE posi_pk = $1)', [$params['posi_pk']]);
    $PgGame->fetch();
    $row = $PgGame->row;
    $time_pk = $row['time_pk'];
    $cmd_hero_pk = $row['cmd_hero_pk'];

    $PgGame->query('DELETE FROM hero_encounter WHERE hero_enco_pk = $1', [$row['hero_enco_pk']]);

    // Timer 도 취소
    $Timer = new Timer($Session, $PgGame);
    $Timer->cancel($time_pk);

    // 영웅 명령해제
    if ($cmd_hero_pk) {
        $Hero = new Hero($Session, $PgGame);
        $Hero->unsetCommand($cmd_hero_pk);
        // Log
        $Log = new Log($Session, $PgGame);
        $Log->setHeroEncounter($Session->lord['lord_pk'], $params['posi_pk'], 'cancel', $row['hero_enco_pk'], $cmd_hero_pk, $row['start_dt'], $row['end_dt'], $row['encounter_type'], $row['encounter_value'], $row['invitation_cnt'], $row['yn_invited'], $row['time_pk'], $row['type'], $row['m_item_pk']);
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/heroEncounter/invitation', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 자바스크립트에서 아이템 미선택시 PHP 에서도 적용을 위해 null 처리
    if (! isset($params['send_item']) || $params['send_item'] == 'null') {
        $params['send_item'] = null;
    }


    $end_dt = ' now() + Interval \'+'.BUILD_TIME_HERO_INVITATION.' second\'';

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG, $_M;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        // 황금 차감
        $GoldPop = new GoldPop($Session, $PgGame);
        $ret = $GoldPop->decreaseGold($params['posi_pk'], $params['send_gold'], null, 'hero_invitation');
        if (!$ret) {
            throw new Exception($i18n->t('msg_own_resource_gold_lack'));
        }

        // 아이템 소모
        if ($params['send_item']) {
            $Item = new Item($Session, $PgGame);
            $ret = $Item->useItem($params['posi_pk'], $Session->lord['lord_pk'], $params['send_item'], 1, ['_yn_quest' => true]);
            if(!$ret) {
                throw new Exception($NsGlobal->getErrorMessage());
            }
        }

        // 영웅 할당
        $Hero = new Hero($Session, $PgGame);
        $ret = $Hero->setCommand($params['posi_pk'], $params['hero_pk'], 'C', 'Invit');
        if (!$ret) {
            throw new Exception($NsGlobal->getErrorMessage());
        }

        $r = $PgGame->query("INSERT INTO hero_invitation ( hero_enco_pk, status, send_gold, cmd_hero_pk, start_dt, cmd_time, cmd_time_reduce, end_dt, result_value, result_status, m_item_pk )
VALUES ( $1, $2, $3, $4, now(), $5, 0, {$end_dt}, 0, 'N', $6) ", [$params['hero_enco_pk'], 'P', $params['send_gold'], $params['hero_pk'], BUILD_TIME_HERO_INVITATION, $params['send_item']]);
        if (! $r) {
            throw new Exception('An error has occurred.'); // 데이터 처리 중 오류가 발생
        }

        $hero_invi_pk = $PgGame->currSeq('hero_invitation_hero_invi_pk_seq');

        $PgGame->query('UPDATE hero_encounter SET invitation_cnt = invitation_cnt + 1 WHERE hero_enco_pk = $1', [$params['hero_enco_pk']]);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    // Timer
    $Timer = new Timer($Session, $PgGame);
    $Troop = new Troop($Session, $PgGame);
    $Timer->set($params['posi_pk'], 'I', $hero_invi_pk, 'I', $i18n->t('timer_hero_invitation_description', [$Troop->getHeroDesc($params['hero_pk'])]), BUILD_TIME_HERO_INVITATION, $params['in_cast_pk']);

    // Timer pk를 넣어 두기 (취소 요청시 처리를 위해서)
    $PgGame->query('UPDATE hero_invitation SET time_pk = $1 WHERE hero_invi_pk = $2', [$Timer->getTimePk(), $hero_invi_pk]);

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'invitation']);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/heroEncounter/invitationCancel', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT hero_invi_pk, send_gold, time_pk, cmd_hero_pk, start_dt, end_dt FROM hero_invitation WHERE hero_enco_pk = $1 AND status = $2 ORDER BY hero_invi_pk DESC', [$params['hero_enco_pk'], 'P']);
    if ($PgGame->fetch()) {
        $row = $PgGame->row;
        $PgGame->query('UPDATE hero_encounter SET invitation_cnt = invitation_cnt - 1 WHERE hero_enco_pk = $1', [$params['hero_enco_pk']]);

        $PgGame->query('SELECT encounter_value FROM hero_encounter WHERE hero_enco_pk = $1', [$params['hero_enco_pk']]);
        $encounter_value = $PgGame->fetchOne();

        // Timer 도 취소
        $Timer = new Timer($Session, $PgGame);
        $Timer->cancel($row['time_pk']);

        // 금괴 반환
        $GoldPop = new GoldPop($Session, $PgGame);
        $GoldPop->increaseGold($params['posi_pk'], intval($row['send_gold']*0.3), null, 'invitation_cancel');

        // 영웅 명령해제
        if ($row['cmd_hero_pk']) {
            $Hero = new Hero($Session, $PgGame);
            $Hero->unsetCommand($row['cmd_hero_pk']);
        }

        $PgGame->query('DELETE FROM hero_invitation WHERE hero_invi_pk = $1', [$row['hero_invi_pk']]);

        $Log = new Log($Session, $PgGame);
        $Log->setHeroInvitation($Session->lord['lord_pk'], $params['posi_pk'], 'cancel', $row['cmd_hero_pk'], $row['hero_invi_pk'], $params['hero_enco_pk'], $row['start_dt'], $row['end_dt'], $row['send_gold'], null, 'Y', $row['time_pk'], null, $encounter_value);
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/heroEncounter/get', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT hero_enco_pk, start_dt, cmd_hero_pk, start_dt, end_dt, encounter_type, encounter_value, invitation_cnt, yn_invited, time_pk, type, m_item_pk  
FROM hero_encounter WHERE hero_enco_pk = (SELECT last_hero_enco_pk FROM territory WHERE posi_pk = $1)', [$params['posi_pk']]);
    $PgGame->fetch();

    $row = $PgGame->row;

    if ($row['encounter_type'] == 'hero' && $row['yn_invited'] == 'N') {
        throw new ErrorHandler('error', $i18n->t('msg_not_hero_invitation_error')); // 초빙에 성공하지 않은 영웅입니다.
    }

    $PgGame->query('UPDATE hero_encounter SET yn_invited  = $2 WHERE hero_enco_pk = $1', [$row['hero_enco_pk'], 'Y']);

    $PgGame->query('UPDATE territory SET last_hero_enco_pk = NULL WHERE posi_pk = $1', [$params['posi_pk']]);

    if ($row['encounter_type'] == 'gold') {
        $GoldPop = new GoldPop($Session, $PgGame);
        $r = $GoldPop->increaseGold($params['posi_pk'], $row['encounter_value'], null, 'encounter');
    } else if ($row['encounter_type'] == 'item') {

        $Item = new Item($Session, $PgGame);
        $arr[$row['encounter_value']]['item_count'] = 1;
        $Item->BuyItem($Session->lord['lord_pk'], $row['encounter_value'], 1, 'hero_get');

        global $NsGlobal, $_M;
        $NsGlobal->requireMasterData(['item']);

        // 아이템 체크 > 공지하는 아이템이면 채팅에다가 공지 Push - TODO 차후 채팅 구현 후 구현 필요.
        if ($_M['ITEM'][$row['encounter_value']]['notice_common'] == 'Y') {
            /*global $Chat;
            if (!$Chat) {
                require_once_classes(Array('CChat'));
                $Chat = new CChat();
            }
            $Chat->send_announce_system_about_item($Session->lord['lord_name']."님이 탐색을 통하여 ".$_M['ITEM'][$row['encounter_value']]['title']." 아이템을 획득하였습니다.");*/
        }

        //$Item->gainItem(군주, 아이템PK, 갯수, 사유);
    } else if ($row['encounter_type'] == 'hero') {
        $Hero = new Hero($Session, $PgGame);
        $Hero->setMyHeroCreate($row['encounter_value'], $Session->lord['lord_pk'], 'V', null, null, 'N', 'encounter');
    } else {
        $res = [];
        $res['food'] = 0;
        $res['horse'] = 0;
        $res['lumber'] = 0;
        $res['iron'] = 0;

        $res[$row['encounter_type']] = $row['encounter_value'];

        $Resource = new Resource($Session, $PgGame);
        $r = $Resource->increase($params['posi_pk'], $res, null, 'encounter');
    }

    $PgGame->query('DELETE FROM hero_encounter WHERE hero_enco_pk = $1', [$row['hero_enco_pk']]);

    // Log
    $Log = new Log($Session, $PgGame);
    $Log->setHeroEncounter($Session->lord['lord_pk'], $params['posi_pk'], 'get', $row['hero_enco_pk'], $row['cmd_hero_pk'], $row['start_dt'], $row['end_dt'], $row['encounter_type'], $row['encounter_value'], $row['invitation_cnt'], $row['yn_invited'], $row['time_pk'], $row['type'], $row['m_item_pk']);

    return $Render->nsXhrReturn('success');
}));
