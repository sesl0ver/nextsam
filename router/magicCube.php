<?php
global $app, $Render, $i18n;

$app->post('/api/magicCube/roll', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG, $_M;
    $NsGlobal->requireMasterData(['item', 'item_magiccube']);

    $Item = new Item($Session, $PgGame);
    $Cash = new Cash($Session, $PgGame);

    // 매직 큐브의 가격은 행운의 주화 가격
    $magic_cube_use_qbig = (INT)$_M['ITEM'][500061]['price'];

    // 큐빅이 8큐빅 미만이고 행운의 주화가 없다면 실행 중지
    $curr_cnt = $Item->getItemCount($Session->lord['lord_pk'], 500061);
    $curr_cash = $Cash->get($Session->lord['lord_pk'], false);
    if ($curr_cnt < 1 && $curr_cash < $magic_cube_use_qbig) {
        throw new ErrorHandler('error', $i18n->t('msg_need_item', [$i18n->t('item_title_500061')])); // 행운의 주화가 부족합니다.
    }

    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        // 아이템 차감
        $ret = $Item->useItem($params['posi_pk'], $Session->lord['lord_pk'], 500061, 1, ['_yn_quest' => true, '_use_type' => 'magiccube']); // 매직큐브아이템
        if(!$ret) {
            // 캐쉬 차감하기
            $ret = $Cash->decreaseCash($Session->lord['lord_pk'], $magic_cube_use_qbig, 'magiccube');
            if(!$ret) {
                throw new Exception($i18n->t('msg_qbig_lack')); // 큐빅이 부족합니다.
            }

            // 아이템 지급
            $ret = $Item->BuyItem($Session->lord['lord_pk'], 500061, 1, 'now_use');
            if(!$ret) {
                throw new Exception('Error Occurred. [32001]'); // 아이템 지급 중 오류가 발생
            }
        }

        $query_params = [$Session->lord['lord_pk']];

        if ($params['action'] == 'start') {
            // 매직큐브 사용횟수 초기화
            $ret = $PgGame->query('UPDATE lord SET magiccube_count = 1, yn_magiccube_doublechance = $2 WHERE lord_pk = $1', [$Session->lord['lord_pk'], 'N']);
        } else {
            // 매직큐브 사용횟수 증가
            $ret = $PgGame->query('UPDATE lord SET magiccube_count = magiccube_count + 1 WHERE lord_pk = $1', $query_params);
        }
        if (! $ret) {
            throw new Exception('Error Occurred. [32002]'); // 매직큐브 진행 중 오류가 발생
        }

        // 더블찬스여부
        $PgGame->query('SELECT yn_magiccube_doublechance FROM lord WHERE lord_pk = $1', $query_params);
        $double_chance = $PgGame->fetchOne();

        $item_cnt = ($double_chance == 'Y') ? 2 : 1;

        $MAGICCUBE_ITEM_ORIGIN = $_M['ITEM_MAGICCUBE']; // 매직큐브 마스터 데이터 원본 보관 , 필요성이?...
        $PgGame->query('SELECT m_item_pk FROM m_item WHERE magiccube_rate > 0 AND magiccube_left_count < 1 ORDER BY m_item_pk');
        if ($PgGame->fetchAll() > 0) {
            // 매직큐브로 줄 수 있는 아이템 갯수가 남지 않은 아이템이 하나라도 있다면
            foreach($PgGame->rows as $v) {
                // 매직큐브 마스터 데이터에서 지워버림
                unset($_M['ITEM_MAGICCUBE'][$v['m_item_pk']]);
            }
        }

        if (count($_M['ITEM_MAGICCUBE']) < 1) {
            // 매직큐브로 줄 수 있는 아이템이 하나도 없다면 마스터 데이터 원복하고 당첨 아이템은 전체 아이템에서 결정
            $_M['ITEM_MAGICCUBE'] = $MAGICCUBE_ITEM_ORIGIN;
        }

        // rand로 생성할 최대 범위 구하기
        $range_random_key_limit = 0;
        foreach($_M['ITEM_MAGICCUBE'] as $k => $v) {
            $range_random_key_limit += $v['magiccube_rate'];
        }

        $range_prev = 1;
        $next = 0;
        $range_random_key = rand(1, $range_random_key_limit);
        $select_item_pk = false;

        foreach($_M['ITEM_MAGICCUBE'] as $k => $v) {
            $next = $range_prev + $v['magiccube_rate'];
            if ($range_random_key >= $range_prev && $range_random_key <= $next) {
                $select_item_pk = $k; // 당첨 아이템이 결정 됨
                break;
            }
            $range_prev = $next;
        }
        $select_item_pk = (!$select_item_pk) ? 500061 : $select_item_pk;

        // 매직큐브 일일 허용량 차감
        $ret = $PgGame->query('UPDATE m_item SET magiccube_left_count = magiccube_left_count - 1 WHERE m_item_pk = $1', [$select_item_pk]);
        if (!$ret) {
            throw new Exception('Error Occurred. [32003]'); // 매직큐브 진행 중 오류가 발생
        }

        // 아이템 지급
        $ret = $Item->BuyItem($Session->lord['lord_pk'], $select_item_pk, $item_cnt, 'magiccube');
        if (!$ret) {
            throw new Exception('Error Occurred. [32004]'); // 아이템 지급에 실패
        }

        // 당첨 아이템 외 15개 아이템 결정
        $item_list = [];
        $cnt = 0;

        // M_ITEM의 원소를 key 기준으로 역순 정렬하고 첫번째 원소의 key를 꺼내면 마지막으로 등록된 아이템의 pk가 된다
        krsort($_M['ITEM']);
        $last_m_item_pk = key($_M['ITEM']);

        while(COUNT($item_list) < 15 ) {
            do {
                $m_item_pk = rand(500001, $last_m_item_pk);
            } while ($m_item_pk == $select_item_pk || !isset($_M['ITEM'][$m_item_pk]) || $_M['ITEM'][$m_item_pk]['magiccube_rate'] == 0);
            if (!in_array($m_item_pk, $item_list) && $select_item_pk != $item_list && in_array($m_item_pk, $_M['ITEM'][$m_item_pk])) {
                $item_list[$cnt++] = $m_item_pk;
            }
        }

        $item_list[$cnt++] = $select_item_pk;
        shuffle($item_list);

        // 더블 찬스 여부 결정
        $double_chance = rand(1, 100);
        if ($double_chance <= 3) {
            $ret = $PgGame->query('UPDATE lord SET yn_magiccube_doublechance = \'Y\' WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
            if (!$ret) {
                throw new Exception('Error Occurred. [32005]'); // 매직큐브 진행 중 오류가 발생
            }
            $Log = new Log($Session, $PgGame);
            $Log->setItem($Session->lord['lord_pk'], $params['posi_pk'], 'buy', 'magiccube', 0, 0, 0, 'double chance');
            $double_chance = 'Y';
        } else {
            $ret = $PgGame->query('UPDATE lord SET yn_magiccube_doublechance = \'N\' WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
            if (!$ret) {
                throw new Exception('Error Occurred. [32006]'); // 매직큐브 진행 중 오류가 발생
            }
            $double_chance = 'N';
        }

        $item_cnt = $Item->getItemCount($Session->lord['lord_pk'], 500061);

        if ($_M['ITEM'][$select_item_pk]['notice_magiccube'] == 'Y')
        {
            // TODO 채팅 알림을 보낼 것인지
            /*global $Chat;
            if (!$Chat)
            {
                require_once_classes(Array('CChat'));
                $Chat = new CChat();
            }

            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', Array($row['src_lord_pk']));
            $lord_name = $PgGame->fetchOne();

            $Chat->send_announce_system_about_item($Session->lord['lord_name']."님이 매직큐브를 통해 ".$_M['ITEM'][$select_item_pk]['title']." 아이템을 획득하였습니다. 축하합니다!", (($double_chance == 'Y')?7000:4000));*/
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

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'magic_cube', 'type' => 'use']);

    return $Render->nsXhrReturn('success', null, ['select_item' => $select_item_pk, 'item_list' => $item_list, 'double_chance' => $double_chance, 'item_cnt' => $item_cnt]);
}));


// TODO 사용하지 않는 API
$app->post('/api/magicCube/eventRoll', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG, $_M;
    $NsGlobal->requireMasterData(['item', 'item_magiccube_evt']);

    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;
        // 아이템 차감
        if ($params['action'] == 'continuously') {
            $ret = $Item->useItem($params['posi_pk'], $Session->lord['lord_pk'], 500534, 1, null, null, null, null, null, null, true); // 매직큐브아이템
            if(! $ret) {
                throw new Exception('아이템 사용 중 오류가 발생하였습니다. 다시 시도해 주십시오.');
            }
        }

        if ($params['action'] == 'start') {
            // 매직큐브 사용횟수 초기화
            $ret = $PgGame->query('UPDATE lord SET magiccube_count = 1, yn_magiccube_doublechance = $2 WHERE lord_pk = $1', Array($Session->lord['lord_pk'], 'N'));
        } else {
            // 매직큐브 사용횟수 증가
            $ret = $PgGame->query('UPDATE lord SET magiccube_count = magiccube_count + 1 WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
        }
        if (!$ret) {
            throw new Exception('매직큐브 진행 중 오류가 발생했습니다. 다시 시도해주세요.');
        }

        $item_cnt = 1;
        $MAGICCUBE_ITEM_ORIGIN = $_M['ITEM_MAGICCUBE_EVT']; // 매직큐브 마스터 데이터 원본 보관 , 필요성이?...

        $PgGame->query('SELECT m_item_pk FROM m_item WHERE magiccube_rate > 0 AND magiccube_left_count < 1 ORDER BY m_item_pk');
        if ($PgGame->fetchAll() > 0) {
            // 매직큐브로 줄 수 있는 아이템 갯수가 남지 않은 아이템이 하나라도 있다면
            foreach($PgGame->rows as $v) {
                // 매직큐브 마스터 데이터에서 지워버림
                unset($_M['ITEM_MAGICCUBE_EVT'][$v['m_item_pk']]);
            }
        }

        if (count($_M['ITEM_MAGICCUBE_EVT']) < 1) {
            // 매직큐브로 줄 수 있는 아이템이 하나도 없다면 마스터 데이터 원복하고 당첨 아이템은 전체 아이템에서 결정
            $_M['ITEM_MAGICCUBE_EVT'] = $MAGICCUBE_ITEM_ORIGIN;
        }

        // rand로 생성할 최대 범위 구하기
        $range_random_key_limit = 0;
        foreach($_M['ITEM_MAGICCUBE_EVT'] as $k => $v) {
            $range_random_key_limit += $v['magiccube_rate'];
        }

        $range_prev = 1;
        $next = 0;
        $range_random_key = rand(1, $range_random_key_limit);
        $select_item_pk = false;

        foreach($_M['ITEM_MAGICCUBE_EVT'] as $k => $v) {
            $next = $range_prev + $v['magiccube_rate'];
            if ($range_random_key >= $range_prev && $range_random_key <= $next) {
                $select_item_pk = $k; // 당첨 아이템이 결정 됨
                break;
            }
            $range_prev = $next;
        }

        $select_item_pk = (!$select_item_pk) ? 500534 : $select_item_pk;

        // 매직큐브 일일 허용량 차감
        $ret = $PgGame->query('UPDATE m_item SET magiccube_left_count = magiccube_left_count - 1 WHERE m_item_pk = $1', [$select_item_pk]);
        if (! $ret) {
            throw new Exception('매직큐브 진행 중 오류가 발생했습니다. 다시 시도해주세요.');
        }

        // 아이템 지급
        $ret = $Item->BuyItem($Session->lord['lord_pk'], $select_item_pk, $item_cnt, 'magiccube_evt');
        if (!$ret)
        {
            throw new Exception('아이템 지급에 실패했습니다.');
        }

        // 당첨 아이템 외 15개 아이템 결정
        $item_list = [];
        $cnt = 0;

        // M_ITEM의 원소를 key 기준으로 역순 정렬하고 첫번째 원소의 key를 꺼내면 마지막으로 등록된 아이템의 pk가 된다
        krsort($_M['ITEM']);
        $last_m_item_pk = key($_M['ITEM']);

        while(COUNT($item_list) < 15 ) {
            do {
                $m_item_pk = rand(500001, $last_m_item_pk);
            } while ($m_item_pk == $select_item_pk || !isset($_M['ITEM'][$m_item_pk]) || $_M['ITEM'][$m_item_pk]['magiccube_rate'] == 0);
            if (!in_array($m_item_pk, $item_list) && $select_item_pk != $item_list && in_array($m_item_pk, $_M['ITEM'][$m_item_pk])) {
                $item_list[$cnt++] = $m_item_pk;
            }
        }

        $item_list[$cnt++] = $select_item_pk;
        shuffle($item_list);

        // 더블 찬스 발생 안함.
        $query_params = [$Session->lord['lord_pk']];
        $ret = $PgGame->query('UPDATE lord SET yn_magiccube_doublechance = \'N\' WHERE lord_pk = $1', $query_params);
        if (!$ret) {
            throw new Exception('매직큐브 진행 중 오류가 발생했습니다. 다시 시도해주세요.');
        }
        $double_chance = 'N';

        $item_cnt = $Item->getItemCount($Session->lord['lord_pk'], 500534);

        // 퀘스트 체크
        $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'magic_cube', 'type' => 'use']);

        if ($_M['ITEM'][$select_item_pk]['notice_magiccube'] == 'Y') {
            // TODO 채팅 알림 여부
            /*global $Chat;
            if (!$Chat)
            {
                require_once_classes(Array('CChat'));
                $Chat = new CChat();
            }

            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', Array($row['src_lord_pk']));
            $lord_name = $PgGame->fetchOne();

            $Chat->send_announce_system_about_item($Session->lord['lord_name']."님이 2013 복주머니를 통해 ".$_M['ITEM'][$select_item_pk]['title']." 아이템을 획득하였습니다. 축하합니다!", (($double_chance == 'Y')?7000:4000));*/
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

    return $Render->nsXhrReturn('success', null, ['select_item' => $select_item_pk, 'item_list' => $item_list, 'double_chance' => $double_chance, 'item_cnt' => $item_cnt]);
}));
