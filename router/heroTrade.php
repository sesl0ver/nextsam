<?php
global $app, $Render, $i18n;

// TODO 사용하지 않는 API

$app->post('/api/heroTrade/sellList', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 리스트 총개수
    $HeroTrade = new HeroTrade($Session, $PgGame);
    $list_cnt = $HeroTrade->getTradePossibleHeroCount($Session->lord['lord_pk']);

    // 리스트 불러오기
    $hero_info = $HeroTrade->getTradePossibleHero($Session->lord['lord_pk'], $params['page_num'], $params['order_by'], $params['order_type']);

    return $Render->nsXhrReturn('success', null, ['list_cnt' => $list_cnt, 'hero_info' => $hero_info]);
}));

$app->post('/api/heroTrade/sell', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    if ($Session->lord['level'] < 4) {
        throw new ErrorHandler('error', '영웅 거래는 군주등급  4등급 이상만 가능합니다.');
    }

    if ($params['min_value'] == $params['max_value']) {
        throw new ErrorHandler('error', '최소 입찰가와 즉시 구매가가 같습니다.');
    }

    if ($params['max_value'] > 0) {
        if ($params['min_value'] > $params['max_value']) {
            throw new ErrorHandler('error', '최소 입찰가가 즉시 구매가보다 큽니다.');
        }
    }

    if ($params['min_value'] < 0 || $params['max_value'] < 0) {
        throw new ErrorHandler('error', '마이너스 값은 불가합니다. 가격 책정을 다시 해주시기 바랍니다.');
    }

    if ($params['min_value'] < 1000) {
        throw new ErrorHandler('error', '최소 입찰가는 1,000이상입니다.');
    }

    if ($params['min_value'] >= 900000000) {
        throw new ErrorHandler('error', '최소 입찰가는 900,000,000 미만 입니다.');
    }

    if ($params['max_value'] > 900000000) {
        throw new ErrorHandler('error', '즉시 구매가는 최대 900,000,000 입니다.');
    }

    if ($params['sale_period'] != 6 && $params['sale_period'] != 24 && $params['sale_period'] != 72) {
        throw new ErrorHandler('error', '판매기간을 확인해 주세요.');
    }

    $HeroSkill = new HeroSkill($Session, $PgGame);
    if ($HeroSkill->getEquipLordHeroSkill($params['hero_pk'])) {
        throw new ErrorHandler('error', '군주 기술을 장착한 영웅은 거래가 불가능 합니다.');
    }

    // 트랜잭션
    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        $user_ip_addr = $_SERVER['REMOTE_ADDR'];
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $user_ip_addr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $user_ip_addr = trim($user_ip_addr[0]);
            $user_ip_addr = (!$user_ip_addr) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $user_ip_addr;
        }
        $HeroTrade = new HeroTrade($Session, $PgGame);
        $ret = $HeroTrade->sellHeroRegist($Session->lord['lord_pk'], $params['hero_pk'], $params['min_value'], $params['max_value'], $params['sale_period'], $params['password'], $user_ip_addr);
        if (!$ret) {
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

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'trade_sell']);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/heroTrade/list', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 리스트 총 개수
    $HeroTrade = new HeroTrade($Session, $PgGame);
    $total_count = $HeroTrade->getTradeListTotalCount($params['type'], $Session->lord['lord_pk'], $params['rare'], $params['level'], $params['name_type'], $params['name']);

    $page_cnt = ($params['type'] == 'all') ? HERO_TRADE_LIST_COUNT : HERO_TRADE_SELL_LIST_COUNT;
    $total_page = (INT)($total_count / $page_cnt);
    $total_page += ($total_count % $page_cnt > 0)? 1 : 0;
    $page_num = $params['page_num'];
    if ($page_num < 1) {
        $page_num = 1;
    } else if ($page_num > $total_page) {
        $page_num = $total_page;
    }

    $order = $params['order_by'];
    $order_arr = ['name', 'rare', 'level', 'enchant', 'leadership', 'mil_force', 'intellect', 'politics', 'charm', 'end_dt', 'now_value', 'max_value'];
    $is_allow_order = in_array($order, $order_arr);
    $order = (!$is_allow_order) ? 'rare' : $order;

    $order_type = strtolower($params['order_type']);
    $order_type = ($order_type == 'asc') ? 'ASC' : 'DESC';

    $heroes = [];
    if ($page_num > 0) {
        if ($params['type'] == 'all') {
            $heroes = $HeroTrade->getTradeList($Session->lord['lord_pk'], $page_num, $order, $order_type, $page_cnt, $params['rare'], $params['level'], $params['name_type'], $params['name']);
        } else if ($params['type'] == 'bid') {
            $heroes = $HeroTrade->getMyTradeBidList($Session->lord['lord_pk'], $page_num, $order, $order_type, $page_cnt);
        } else if ($params['type'] == 'sale') {
            $heroes = $HeroTrade->getMyTradeSellList($Session->lord['lord_pk'], $page_num, $order, $order_type, $page_cnt);
        }
    } else {
        $page_num = 1;
        $total_page = 1;
    }

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'curr_page' => $page_num, 'order_by' => $order, 'order_type' => $order_type, 'hero_list' => $heroes]);
}));

$app->post('/api/heroTrade/bidCount', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $HeroTrade = new HeroTrade($Session, $PgGame);
    $bid_count = $HeroTrade->getTradeBidCount($Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success', null, ['bid_count' => $bid_count]);
}));

$app->post('/api/heroTrade/bid', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');


    // 해당 영웅이 판매 중인지 확인
    // 입찰 가격 확인
    // 거래 목록 확인할때 select for update 사용
    // 트랜잭션 처리(황금 차감과 구매에...
    // 입찰자 등급확인

    if ($Session->lord['level'] < 2)  {
        throw new ErrorHandler('error', '영웅 거래는 군주 2등급 이상만 가능합니다.');
    }

    // 영웅거래 체결중..
    /*
    $now_minutes = date('i');

    if ($now_minutes >=30 && $now_minutes <= 35)
    {
        throw new ErrorHandler('error', '매시 30분부터 5분간<br /><br />영웅 거래 체결 중인 관계로<br /><br />입찰이 불가능 합니다.');
        exit;
    }
    */

    // 재입찰인지 확인후 재입찰이 아닐경우만..
    $HeroTrade = new HeroTrade($Session, $PgGame);
    $bid_value = $HeroTrade->getMyBidInfo($Session->lord['lord_pk'], $params['hero_trad_pk']);
    if (!$bid_value) {
        if ($HeroTrade->getTradeBidCount($Session->lord['lord_pk']) >= HERO_TRADE_MAX_BID_COUNT) {
            throw new ErrorHandler('error', '최대 구매 횟수를 초과 하였습니다.');
        }
    }

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        // 현재 입찰액과 비교하여 1000보다 클경우만 입찰 가능
        $trade_info = $HeroTrade->getHeroTradeInfo($params['hero_trad_pk']);

        if ($trade_info['trade_complete'] == 'Y') {
            throw new Exception('이미 판매가 완료된 영웅입니다.');
        }

        // TODO 차후 테스트시 확인 필요.
        if ($Session->web_channel != CONF_TEST_CHANNEL)
        {
            if (HERO_TRADE_TEST != 'test') {
                $user_ip_addr = $_SERVER['REMOTE_ADDR'];
                if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
                    $user_ip_addr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                    $user_ip_addr = trim($user_ip_addr[0]);
                    $user_ip_addr = (!$user_ip_addr) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $user_ip_addr;
                }
                if ($user_ip_addr == $trade_info['sell_ip']) {
                    throw new Exception('판매자와 동일한 IP에서는 입찰이 불가능 합니다.');
                }
            }
        }

        if ($trade_info['yn_sale'] == 'N') {
            throw new Exception('판매 등록이 완료되지 않은 영웅입니다.');
        }

        if ($trade_info['end_dt_ut'] <= time()) {
            throw new Exception('입찰이 마감된 영웅입니다.');
        }

        if ($trade_info['lord_pk'] == $Session->lord['lord_pk']) {
            throw new Exception('본인이 판매 중인 영웅은 입찰이 불가능 합니다.');
        }

        if ($trade_info['min_value'] > $params['bid_value']) {
            throw new Exception('최소 입찰가보다 입찰참여 금액이 작습니다.');
        }

        if ($trade_info['max_value'] > $params['bid_value']) {
            if (($trade_info['now_value'] + 1000) > $params['bid_value']) {
                throw new Exception('<strong>입찰 가격을 확인해 주세요.</strong><br /><br />이전 입찰 가격보다 1,000이상 큰 가격만 입찰이 가능합니다.');
            }
        }

        $commission = $HeroTrade->getCommission($trade_info['sale_period'], ($params['bid_value'] - $bid_value));

        $GoldPop = new GoldPop($Session, $PgGame);
        $decr_gold = ($params['bid_value'] - $bid_value) + $commission;
        $r = $GoldPop->decreaseGold($params['posi_pk'], $decr_gold, null, 'trade_bid');
        if (!$r) {
            throw new Exception('황금이 부족하여 입찰에 참여할 수 없습니다.');
        }

        // 입찰
        $ret = $HeroTrade->setHeroTradeBid($Session->lord['lord_pk'], $params['hero_trad_pk'], $params['bid_value'], $trade_info, $bid_value, $params['posi_pk']);
        if(!$ret) {
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

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'trade_bid']);
}));

$app->post('/api/heroTrade/bidInfo', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT yn_sale, trade_complete FROM hero_trade WHERE hero_trad_pk = $1', [$params['hero_trad_pk']]);
    $PgGame->fetch();
    $row = $PgGame->row;

    if ($row['yn_sale'] == 'N') {
        throw new ErrorHandler('error', '판매 중인 영웅이 아닙니다.');
    }

    if ($row['trade_complete'] == 'Y') {
        throw new ErrorHandler('error', '판매 완료된 영웅입니다.');
    }

    $HeroTrade = new HeroTrade($Session, $PgGame);
    $now_value = $HeroTrade->getHeroTradeNowValue($params['hero_trad_pk']);
    $bid_value = $HeroTrade->getHeroTradeMyBidValue($Session->lord['lord_pk'], $params['hero_trad_pk']);

    return $Render->nsXhrReturn('success', null, ['now_value' => $now_value, 'bid_value' => $bid_value]);
}));

$app->post('/api/heroTrade/saleInfo', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT lord_pk, now_value, password FROM hero_trade WHERE hero_trad_pk = $1', [$params['hero_trad_pk']]);
    $PgGame->fetch();
    $row = $PgGame->row;
    if ($Session->lord['lord_pk'] != $row['lord_pk']) {
        throw new ErrorHandler('error', '판매 중인 영웅이 아닙니다.');
    }

    return $Render->nsXhrReturn('success', null, ['now_value' => $row['now_value'], 'lord_pk' => $row['lord_pk'], 'password' => $row['password']]);
}));

$app->post('/api/heroTrade/saleCancel', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        $HeroTrade = new HeroTrade($Session, $PgGame);
        $trade_info = $HeroTrade->getHeroTradeInfo($params['hero_trad_pk']);

        if ($Session->lord['lord_pk'] != $trade_info['lord_pk']) {
            throw new Exception('판매 중인 영웅이 아닙니다.');
        }

        if ($trade_info['end_dt_ut'] <= time() && $trade_info['yn_sale'] == 'Y') {
            throw new Exception('입찰 처리 중인 영웅입니다. 입찰 처리 중에는 판매취소가 불가능 합니다.');
        }

        if ($trade_info['now_value']) {
            throw new Exception('판매취소가 불가능합니다.<br /><br />입찰자가 있을 경우 취소가 불가능 합니다.');
        }

        // 영웅 상태 변경 및 판매취소
        $r = $HeroTrade->setHeroTradeSellcancel($Session->lord['lord_pk'], $trade_info['hero_pk'], $params['hero_trad_pk'], $trade_info);
        if (!$r) {
            throw new Exception('판매취소에 실패 하였습니다.');
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

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/heroTrade/bidInfoPassword', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT password FROM hero_trade WHERE hero_trad_pk = $1', [$params['hero_trad_pk']]);
    if ($params['password'] != $PgGame->fetchOne()) {
        throw new ErrorHandler('error', '거래암호가 일치하지 않습니다.');
    }

    $HeroTrade = new HeroTrade($Session, $PgGame);
    $now_value = $HeroTrade->getHeroTradeNowValue($params['hero_trad_pk']);
    $bid_value = $HeroTrade->getHeroTradeMyBidValue($Session->lord['lord_pk'], $params['hero_trad_pk']);

    return $Render->nsXhrReturn('success', null, ['now_value' => $now_value, 'bid_value' => $bid_value]);
}));

$app->post('/api/heroTrade/gold', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT gold FROM hero_trade_gold WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $curr_gold = $PgGame->fetchOne();
    $curr_gold = $curr_gold ?: 0;

    return $Render->nsXhrReturn('success', null, ['curr_gold' => $curr_gold]);
}));

$app->post('/api/heroTrade/goldGet', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        $PgGame->query('SELECT gold FROM hero_trade_gold WHERE lord_pk = $1 FOR UPDATE', [$Session->lord['lord_pk']]);
        $curr_gold = $PgGame->fetchOne();

        if (!is_numeric($params['gold'])) {
            throw new Exception('숫자만 입력 가능합니다.');
        }

        if ($params['gold'] > $curr_gold) {
            throw new Exception('보유 황금보다 입출 황금이 커서 인출이 불가능 합니다.');
        }

        $HeroTrade = new HeroTrade($Session, $PgGame);
        $r = $HeroTrade->decrHeroTradeGold($Session->lord['lord_pk'], $params['gold']);
        if (!$r) {
            throw new Exception('황금 인출 실패 하였습니다.');
        }

        $GoldPop = new GoldPop($Session, $PgGame);
        $r = $GoldPop->increaseGold($params['posi_pk'], $params['gold'], null, 'hero_trad_gold_get');
        if (!$r) {
            throw new Exception('황금 인출 실패 하였습니다.');
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

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/heroTrade/search', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 리스트 총 개수
    $HeroTrade = new HeroTrade($Session, $PgGame);
    $total_count = $HeroTrade->getTradeListTotalCount($params['type'], $Session->lord['lord_pk'], $params['rare'], $params['level'], $params['name_type'], $params['name']);

    $page_cnt = HERO_TRADE_LIST_COUNT;
    $total_page = (INT)($total_count / $page_cnt);
    $total_page += ($total_count % $page_cnt > 0)? 1 : 0;

    $page_num = $params['page_num'];

    if ($page_num < 1) {
        $page_num = 1;
    } else if ($page_num > $total_page) {
        $page_num = $total_page;
    }

    $order = $params['order_by'];
    $order_arr = ['name', 'rare', 'level', 'enchant', 'leadership', 'mil_force', 'intellect', 'politics', 'charm'];
    $is_allow_order = in_array($order, $order_arr);
    $order = (!$is_allow_order) ? 'rare' : $order;

    $order_type = strtolower($params['order_type']);
    $order_type = ($order_type == 'asc') ? 'ASC' : 'DESC';

    $heroes = [];
    if ($page_num > 0) {
        $heroes = $HeroTrade->getTradeList($Session->lord['lord_pk'], $page_num, $order, $order_type, $page_cnt, $params['rare'], $params['level'], $params['name_type'], $params['name']);
    } else {
        $page_num = 1;
        $total_page = 1;
    }

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'curr_page' => $page_num, 'order_by' => $order, 'order_type' => $order_type, 'hero_list' => $heroes]);
}));
