<?php
global $app, $Render, $i18n;

$app->post('/api/item/buy', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['item_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Item = new Item($Session, $PgGame);
    $Cash = new Cash($Session, $PgGame);

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['item']);

    if ($_M['ITEM'][$params['item_pk']]['yn_sell'] == 'N') {
        throw new ErrorHandler('error', 'Error Occurred. [29001]'); // 구매 불가능한 아이템
    }

    if ($params['count'] < 0) {
        throw new ErrorHandler('error', $i18n->t('msg_need_buy_item_amount')); // 구매 갯수를 입력하세요.
    }

    // 구매 제한이 있는 아이템에 경우 횟수 체크
    if (! $Item->checkLimitBuy($params['item_pk'], $params['count'])) {
        throw new ErrorHandler('error', '해당 아이템은 더 이상 구매 할 수 없습니다.');
    }

    global $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();
        $_NS_SQ_REFRESH_FLAG = true;

        // 캐쉬 차감하기
        $ret = $Cash->decreaseCash($Session->lord['lord_pk'], $_M['ITEM'][$params['item_pk']]['price'] * $params['count'], 'item buy');
        if (!$ret) {
            $NsGlobal->setErrorLogging(false);
            throw new Exception($i18n->t('msg_qbig_lack')); // 큐빅이 부족합니다.
        }

        // 아이템 지급
        $r = $Item->BuyItem($Session->lord['lord_pk'], $params['item_pk'], $params['count'], 'buy_item');
        if (!$r) {
            // 해당하는 에러를 노출 시키기 위해
            throw new Exception($NsGlobal->getErrorMessage());
        }

        // 구매 제한 아이템 등록
        $Item->updateLimitBuy($params['item_pk'], $params['count']);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), $NsGlobal->getErrorLogging());
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success', null, ['cash' => $ret, 'item_pk' => $params['item_pk'], 'item_count' => $params['count']]);
}));

$app->post('/api/item/use', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['item_pk', 'action']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Event = new Event($PgGame);

    $Item = new Item($Session, $PgGame);
    $Cash = new Cash($Session, $PgGame);

    $item_pk = $params['item_pk'];
    $flag = (! isset($params['flag'])) ? null : $params['flag'];
    $lord_name = (! isset($params['lord_name'])) ? null : $params['lord_name'];
    $card_type = (! isset($params['card_type'])) ? null : $params['card_type'];
    $state = (! isset($params['state'])) ? null : $params['state'];
    $item_cnt = (! isset($params['item_cnt'])) ? 1 : $params['item_cnt'];

    global $NsGlobal, $_M, $_NS_SQ_REFRESH_FLAG;
    $NsGlobal->requireMasterData(['item']);

    $ret = null;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        if ($params['action'] == 'use_item') {
            $ret = $Item->useItem($params['posi_pk'], $Session->lord['lord_pk'], $item_pk, $item_cnt, [
                '_flag' => $flag,
                '_before_lord_name' => $Session->lord['lord_name'],
                '_lord_name' => $lord_name,
                '_card_type' => $card_type,
                '_use_type' => $params['use_type'] ?? null,
                '_state' => $state,
            ]);
            if(! $ret) {
                throw new Exception($NsGlobal->getErrorMessage());
            }
        } else if ($params['action'] == 'buy_use_item') { // 구매 바로 사용시 $item_cnt 는 무조건 1
            $_to_buy_item = $item_pk;
            $_to_item_cnt = 0;
            $_to_buy_type = 'now_use';
            $_to_noti = true;
            // 스킬 상자에서 열쇠 구매 후 즉시 사용을 위한 예외임
            if ($_to_buy_item == '500115') {
                $_to_buy_item = 500118;
                $_to_item_cnt = 1;
                $_to_buy_type = 'skill_box_item_use:500115';
                $_to_noti = false;
            } else if ($_to_buy_item == '500116') {
                $_to_buy_item = 500119;
                $_to_item_cnt = 1;
                $_to_buy_type = 'skill_box_item_use:500116';
                $_to_noti = false;
            } else if ($_to_buy_item == '500117') {
                $_to_buy_item = 500120;
                $_to_item_cnt = 1;
                $_to_buy_type = 'skill_box_item_use:500117';
                $_to_noti = false;
            } else if ($_to_buy_item == HERO_SKILL_BATTLE_COPPER_BOX) {
                $_to_buy_item = HERO_SKILL_BATTLE_COPPER_KEY;
                $_to_item_cnt = 1;
                $_to_buy_type = 'skill_box_item_use:'.HERO_SKILL_BATTLE_COPPER_BOX;
                $_to_noti = false;
            } else if ($_to_buy_item == HERO_SKILL_BATTLE_SILVER_BOX) {
                $_to_buy_item = HERO_SKILL_BATTLE_SILVER_KEY;
                $_to_item_cnt = 1;
                $_to_buy_type = 'skill_box_item_use:'.HERO_SKILL_BATTLE_SILVER_BOX;
                $_to_noti = false;
            } else if ($_to_buy_item == HERO_SKILL_BATTLE_GOLD_BOX) {
                $_to_buy_item = HERO_SKILL_BATTLE_GOLD_KEY;
                $_to_item_cnt = 1;
                $_to_buy_type = 'skill_box_item_use:'.HERO_SKILL_BATTLE_GOLD_BOX;
                $_to_noti = false;
            }/* else if ($_to_buy_item == HERO_SKILL_BATTLE_SPECIAL_BOX) {
			    $_to_buy_item = HERO_SKILL_BATTLE_SPECIAL_KEY;
			    $_to_item_cnt = 1;
			    $_to_buy_type = 'skill_box_item_use:'.HERO_SKILL_BATTLE_SPECIAL_KEY;
			    $_to_noti = false;
		    }*/

            if ($_M['ITEM'][$_to_buy_item]['yn_sell'] == 'N') {
                throw new Exception('Error Occurred. [29002]'); // 구매 불가능한 아이템
            }
            //캐쉬 차감하기
            $cash_result = $Cash->decreaseCash($Session->lord['lord_pk'], $_M['ITEM'][$_to_buy_item]['price'], 'item_buy_use');
            if(! $cash_result) {
                throw new Exception($i18n->t('msg_qbig_lack')); // 큐빅이 부족합니다.
            }

            // 아이템 지급
            $ret = $Item->BuyItem($Session->lord['lord_pk'], $_to_buy_item, 1, $_to_buy_type, $_to_noti);
            if (!$ret) {
                throw new Exception($NsGlobal->getErrorMessage()); // 아이템 사용 실패 'Error Occurred. [29003]'
            }

            // 아이템 사용하기
            $ret = $Item->useItem($params['posi_pk'], $Session->lord['lord_pk'], $item_pk, $_to_item_cnt, ['_flag' => $flag, '_lord_name' => $lord_name]);
            if(! $ret) {
                throw new Exception($NsGlobal->getErrorMessage()); // 아이템 사용 실패
            }
        } else if ($params['action'] == 'time_event') {
            $check_types = ['event_enchant', 'event_cure', 'event_troop', 'event_army_build', 'event_cons_build', 'event_encounter', 'event_tech_build'];
            if (! in_array($_M['ITEM'][$item_pk]['use_type'], $check_types)) {
                throw new Exception('Error Occurred. [29005]'); // 버프 아이템 사용 실패
            }

            if (strtotime($_M['TIME_BUFF']['end_date']) <= time()) {
                throw new Exception($i18n->t('msg_item_use_failed_event_end')); // 종료된 이벤트입니다.
            }

            $Event->checkMyEvent($Session->lord['lord_pk']);

            $PgGame->query('SELECT time_buff_count FROM my_event WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
            $count = (INT)$PgGame->fetchOne();

            // 최대 사용 횟수 체크
            if ($count >= $_M['TIME_BUFF']['max_count']) {
                throw new Exception($i18n->t('msg_limit_use_count_time_buff')); // 이미 사용 회수를 모두 소모하였습니다.
            }

            $PgGame->query('UPDATE my_event SET time_buff_count = time_buff_count + 1 WHERE lord_pk = $1 RETURNING time_buff_count', [$Session->lord['lord_pk']]);
            $PgGame->fetch();
            $Session->sqAppend('EVENT', $PgGame->row, null, $Session->lord['lord_pk']);
        }
        $buff_time = $_M['ITEM'][$item_pk]['buff_time'];
        $use_type = $_M['ITEM'][$item_pk]['use_type'];

        //버프 사용하기
        if ($buff_time && $use_type) {
            $ret = $Item->useBuffItem($params['posi_pk'], $item_pk);
            if (!$ret) {
                throw new Exception('Error Occurred. [29006]'); // 버프 아이템 사용 실패
            }
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

    return $Render->nsXhrReturn('success', null, $ret);
}));
