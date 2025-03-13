<?php
global $app, $Render, $i18n;

$app->post('/api/heroDetail/prize', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session(true, true);
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT date_part(\'epoch\', last_prize_dt)::integer as last_up_dt, yn_lord FROM my_hero WHERE hero_pk = $1', [$params['hero_pk']]);
    $PgGame->Fetch();
    $remain_dt = time() - $PgGame->row['last_up_dt'];

    if($remain_dt < 14400) { //4시간
        return $Render->nsXhrReturn('success', $i18n->t('msg_remain_time'), ['type' => 'remain', 'remain_dt' => $remain_dt]);
    }

    if ($PgGame->row['yn_lord'] == 'Y') {
        throw new ErrorHandler('error', $i18n->t('msg_hero_prize_lord_error'));
    }

    $PgGame->query('SELECT loyalty FROM hero WHERE hero_pk = $1', [$params['hero_pk']]);
    if ($PgGame->FetchOne() >= 100) {
        throw new ErrorHandler('error', $i18n->t('msg_hero_max_loyalty')); // 충성도가 100인 영웅은 포상 할 수 없습니다.
    }

    $GoldPop = new GoldPop($Session, $PgGame);
    $r = $GoldPop->decreaseGold($Session->getPosiPk(), $params['gold'], null, 'hero_prize');
    if (!$r) {
        throw new ErrorHandler('error', $i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
    }

    $Hero = new Hero($Session, $PgGame);
    $loyalty = $Hero->setHeroPrize($params['hero_pk'], $params['gold']);
    if (!$loyalty) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'prize']);

    return $Render->nsXhrReturn('success', null, ['type' => 'prize', 'loyalty' => $loyalty, 'remain_dt' => 0, 'need_redraw' => true, 'hero_info' => $Hero->getMyHeroInfo($params['hero_pk'])]);
}));

$app->post('/api/heroDetail/enchant', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Quest = new Quest($Session, $PgGame);

    $PgGame->query('SELECT status, status_cmd, yn_lord FROM my_hero WHERE hero_pk = $1', [$params['hero_pk']]);
    $PgGame->fetch();
    if ($PgGame->row['status'] != 'A' && $PgGame->row['status_cmd'] != 'I') {
        throw new ErrorHandler('error', $i18n->t('msg_hero_enchant_status_idle')); // 대기중 상태에서만 강화를 할 수 있습니다.
    }

    $PgGame->query('SELECT enchant, rare_type FROM hero WHERE hero_pk = $1', [$params['hero_pk']]);
    $PgGame->Fetch();
    $enchant_cnt = $PgGame->row['enchant'];
    $rare_type = $PgGame->row['rare_type'];

    if ($enchant_cnt >= 10) {
        throw new ErrorHandler('error', $i18n->t('msg_hero_enchant_max')); // 더 이상 강화를 할 수 없습니다.
    }

    // 아이템
    $enchant_item = 500085; // 영석
    $Item = new Item($Session, $PgGame);
    $item_cnt = $Item->getItemCount($Session->lord['lord_pk'], $enchant_item);

    $Hero = new Hero($Session, $PgGame);
    $need_item_cnt = $Hero->getEnchantNeedItem($enchant_cnt, $rare_type);
    $need_price = $Hero->getEnchantNeedPrice($enchant_cnt, $rare_type);

    if ($item_cnt < $need_item_cnt) {
        throw new ErrorHandler('error', $i18n->t('msg_hero_enchant_item_lack')); // 보유 영석 아이템이 부족 합니다.
    }

    // $hero_posi_pk 값이 유효한지 검사. (자신의 영지인지)
    /*$PgGame->query('SELECT posi_pk FROM position WHERE lord_pk = $1 AND posi_pk = $2', [$Session->lord['lord_pk'], $params['posi_pk']]);
    if ($PgGame->fetch()) {
        $hero_posi_pk = $params['posi_pk'];
    } else {
        // 유효하지 않다면 영웅 기준으로
        $PgGame->query('SELECT posi_pk FROM my_hero WHERE hero_pk = $1', [$params['hero_pk']]);
        $hero_posi_pk = $PgGame->fetchOne();
    }*/
    $hero_posi_pk = $params['posi_pk']; // 어차피 소유영지는 1개로 고정이므로 20231116 송누리

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        $ret = $Item->useItem($hero_posi_pk, $Session->lord['lord_pk'], $enchant_item, $need_item_cnt, ['_yn_quest' => true]);
        if(!$ret) {
            throw new Exception($NsGlobal->getErrorMessage());
        }


        //보조아이템
        if (isset($params['item_pk']) && $params['item_pk'] != 'null') {
            $ret = $Item->useItem($hero_posi_pk, $Session->lord['lord_pk'], $params['item_pk'], 1, ['_yn_quest' => true]);
            if(!$ret) {
                throw new Exception($NsGlobal->getErrorMessage());
            }
        }

        //황금 차감
        $GoldPop = new GoldPop($Session, $PgGame);
        $r = $GoldPop->decreaseGold($Session->getPosiPk(), $need_price, null, 'hero_enchant'); // ($enchant_cnt + 1) * 10000 (이전에 사용한 공식)
        if (!$r) {
            throw new Exception($i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
        }

        $ret = $Hero->setHeroEnchant($params['hero_pk'], $enchant_cnt, $params['item_pk']);
        if (!$ret) {
            throw new Exception($i18n->t('msg_hero_status_not_idle')); // 영웅이 대기상태가 아닙니다.
        }

        $Quest->countCheckQuest($Session->lord['lord_pk'], 'EVENT_HERO_ENHANCE', ['value' => 1]);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/heroDetail/enchantInit', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session(true, true);
    $PgGame = new Pg('DEFAULT');

    // 강화 초기화 아이템 사용
    $PgGame->query('SELECT status, status_cmd, cmd_type FROM my_hero WHERE hero_pk = $1 AND lord_pk = $2 AND posi_pk = $3', [$params['hero_pk'], $Session->lord['lord_pk'], $params['posi_pk']]);
    if (!$PgGame->fetch()) {
        throw new ErrorHandler('error',$i18n->t('msg_not_exist_hero')); // 존재하지 않는 영웅입니다.
    }

    $hero_status = $PgGame->row;

    global $NsGlobal, $_M, $_NS_SQ_REFRESH_FLAG;
    $NsGlobal->requireMasterData(['item']);

    $m_item_pk = 500135;

    if ($PgGame->row['status'] != 'A' && $PgGame->row['status_cmd'] != 'I') {
        throw new ErrorHandler('error', $i18n->t('msg_hero_enchant_item_use_idle', [$i18n->t("item_title_$m_item_pk")]));
    }

    $PgGame->query('SELECT enchant FROM hero WHERE hero_pk = $1', [$params['hero_pk']]);
    $enchant_cnt = $PgGame->FetchOne();

    if ($enchant_cnt < 1) {
        throw new ErrorHandler('error',$i18n->t('msg_hero_enchant_item_use_init', [$i18n->t("item_title_$m_item_pk")]));
    }

    try {
        $PgGame->begin();
        $_NS_SQ_REFRESH_FLAG = true;

        $Item = new Item($Session, $PgGame);
        $ret = $Item->useItem($params['posi_pk'], $Session->lord['lord_pk'], $m_item_pk, 1, ['_hero_pk' => $params['hero_pk']]);
        if(! $ret) {
            throw new Exception($NsGlobal->getErrorMessage());
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

    $Log = new Log($Session, $PgGame);
    $Log->setHeroEnchant($Session->lord['lord_pk'], $params['posi_pk'], 'enchant_init', $params['hero_pk'], $hero_status['status'], $hero_status['status_cmd'], $hero_status['cmd_type'], $enchant_cnt, 500135);

    $Hero = new Hero($Session, $PgGame);

    return $Render->nsXhrReturn('success', null, ['hero_info' => $Hero->getMyHeroInfo($params['hero_pk'])]);
}));

$app->post('/api/heroDetail/getHeroGroup', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $group_type_arr = ['C', 'D', 'S', 'E', 'B', 'T'];
    if (!in_array($params['group_type'], $group_type_arr)) {
        throw new ErrorHandler('error','Invalid request.'); // 올바르지 않은 그룹을 요청
    }

    $Hero = new Hero($Session, $PgGame);
    return $Render->nsXhrReturn('success', null, $Hero->getHeroGroupByType($params['group_type'], $Session->lord['lord_pk']));
}));

$app->post('/api/heroDetail/setHeroGroup', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal;
    $group_type_arr = ['C', 'D', 'S', 'E', 'B', 'T'];
    if (! in_array($params['group_type'], $group_type_arr)) {
        throw new ErrorHandler('error','Invalid request.'); // 올바르지 않은 그룹을 요청
    }
    if ($params['group_order'] < 1 || $params['group_order'] > 9) {
        throw new ErrorHandler('error','Invalid request.');
    }

    $Hero = new Hero($Session, $PgGame);
    $ret = $Hero->setHeroGroup($params['hero_pk'], $Session->lord['lord_pk'], $params['group_type'], $params['group_order']);
    if ($ret > 0) {
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage()); // 해당 영웅에게 그룹을 지정하는데 실패
    }

    $hero_info = $Hero->getMyHeroInfo($params['hero_pk']);
    if($hero_info['posi_pk'] == $Session->getPosiPk()) {
        $Session->sqAppend('HERO', [$hero_info['hero_pk'] => $hero_info], null, $Session->lord['lord_pk'], $hero_info['posi_pk']);
    }

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'group']);

    return $Render->nsXhrReturn('success', null, ['hero_info' => $hero_info]);
}));

$app->post('/api/heroDetail/unsetHeroGroup', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Hero = new Hero($Session, $PgGame);
    $ret = $Hero->unsetHeroGroup($params['hero_pk'], $Session->lord['lord_pk']);
    if ($ret > 0) {
        throw new ErrorHandler('error','An error has occurred.'); // 해당 영웅의 그룹을 지정 해제하는데 실패
    }

    $hero_info = $Hero->getMyHeroInfo($params['hero_pk']);
    if($hero_info['posi_pk'] == $Session->getPosiPk()) {
        $Session->sqAppend('HERO', [$hero_info['hero_pk'] => $hero_info], null, $Session->lord['lord_pk'], $hero_info['posi_pk']);
    }

    return $Render->nsXhrReturn('success', null, ['hero_info' => $hero_info]);
}));