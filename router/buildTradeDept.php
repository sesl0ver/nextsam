<?php
global $app, $Render, $i18n;

$app->post('/api/tradeDept/list', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 시세 목록
    $PgGame->query('SELECT trade_type, unit_price, reso_amount FROM trade_price_list WHERE trade_type = \'B\' AND reso_type = $1 ORDER BY unit_price DESC LIMIT 5', [$params['type']]);

    $cnt = 0;
    $price_list = [];

    while($PgGame->fetch()) {
        $price_list[$cnt++] = $PgGame->row;
    }

    $PgGame->query('SELECT trade_type, unit_price, reso_amount FROM trade_price_list WHERE trade_type = \'O\' AND reso_type = $1 ORDER BY unit_price LIMIT 5', [$params['type']]);

    while($PgGame->fetch()) {
        $price_list[$cnt++] = $PgGame->row;
    }

    return $Render->nsXhrReturn('success', null, $price_list);
}));

// 구매 주문
$app->post('/api/tradeDept/bid', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 최대 주문 건수 검사
    $PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$params['posi_pk'], PK_BUILDING_TRADEDEPT]);
    $level = $PgGame->fetchOne();

    $PgGame->query('SELECT count(offe_pk) FROM trade_offer WHERE posi_pk = $1', [$params['posi_pk']]);
    $trade_cnt = $PgGame->fetchOne();

    $PgGame->query('SELECT count(bid_pk) FROM trade_bid WHERE posi_pk = $1', [$params['posi_pk']]);
    $trade_cnt = $trade_cnt + $PgGame->fetchOne();

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['building']);

    if ($trade_cnt >= $_M['BUIL'][PK_BUILDING_TRADEDEPT]['level'][$level]['variation_1']) {
        throw new ErrorHandler('error', $i18n->t('msg_trade_bid_max')); // 최대 주문건수를 넘어 추가주문이 불가합니다.
    }

    $PgGame->query('SELECT COUNT(offe_pk) FROM trade_offer WHERE posi_pk = $1 AND reso_type = $2', [$params['posi_pk'], $params['type']]);
    if ($PgGame->fetchOne() > 0) {
        throw new ErrorHandler('error', $i18n->t('msg_trade_bid_same_resource')); // 판매 중인 동일 자원이 있으면 구매가 불가능 합니다
    }

    if ($params['bid_amount'] < 1) {
        throw new ErrorHandler('error',  $i18n->t('msg_trade_buy_need_amount')); // 구매 수량을 입력해주세요.
    }

    if ($params['bid_amount'] < 1000) {
        throw new ErrorHandler('error',  $i18n->t('msg_trade_bid_min')); // 구매수량이 1,000 이상일 경우만 구매 가능합니다.
    }

    if ($params['unit_price'] <= 0) {
        throw new ErrorHandler('error',  $i18n->t('msg_trade_buy_need_price')); // 구매 단가를 입력해주세요.
    }

    if (strpos($params['unit_price'], '.')) {
        $params['unit_price'] = floatval(substr(strval($params['unit_price']), 0, (strpos($params['unit_price'], '.') + 2)));
    }

    $total_price = ceil($params['bid_amount'] * $params['unit_price']);
    $commission = ceil($total_price * 0.1);

    // 골드 소모
    $GoldPop = new GoldPop($Session, $PgGame);
    $r = $GoldPop->decreaseGold($params['posi_pk'], $total_price + $commission, null, 'bid');
    if (! $r) {
        throw new ErrorHandler('error',  $i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
    }

    $remaind_price = 0;
    $total_deal_amount = 0;

    // 주문하기
    $r = $PgGame->query('SELECT posi_pk, lord_pk, unit_price, deal_amount FROM updatetradebid (\'' .$params['posi_pk'] . '\', \''.$params['type'].'\', '.(INT)$params['bid_amount']. ','.(DOUBLE)$params['unit_price'].','.$total_price.','.$Session->lord['lord_pk'].')');
    if (!$r) {
        $GoldPop->increaseGold($params['posi_pk'], $total_price + $commission, null, 'bid_fail');
        throw new ErrorHandler('error',  'Bid registration failed.');
    }
    $PgGame->fetchAll();
    $rows = $PgGame->rows;

    $Troop = new Troop($Session, $PgGame);
    $Report = new Report($Session, $PgGame);
    $Log = new Log($Session, $PgGame);
    foreach($rows AS $k => $v) {
        // 판매자에게 gold지급
        $GoldPop->increaseGold($v['posi_pk'], ceil($v['unit_price'] * $v['deal_amount']), $v['lord_pk'], 'bid');

        // 보고서
        $z_content = [];

        // reso
        $z_content['reso_type'] = $params['type'];
        $z_content['reso_amount'] = $v['deal_amount'];
        $z_content['gold_amount'] = ceil($v['unit_price'] * $v['deal_amount']);

        // from & to
        $z_from = ['posi_pk' => $v['posi_pk'], 'posi_name' => $Troop->getPositionName($v['posi_pk'])];
        $z_to = ['posi_pk' => '-', 'posi_name' => "-:{$i18n->t('other_territory')}:0"];

        // title & summary
        $z_title = '';
        $z_summary = '';
        $Report->setReport($v['lord_pk'], 'move', 'shipping_sale', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));

        $Session->sqAppend('PUSH', ['TRADE_COMPLETE_DELIVERY' => true], null, $Session->lord['lord_pk'], $params['posi_pk']);

        // Log
        $Log->setBuildingTradedept($v['lord_pk'], $v['posi_pk'], 'offer', $params['type'], $params['bid_amount'], $v['unit_price'], $v['deal_amount'], 'bid lord_pk['. $Session->lord['lord_pk']. '];bid posi['. $params['posi_pk']. ']; gold['. ceil($v['unit_price']*$v['deal_amount']) . '];');

        // 판매자 주문현황 업데이트
        $Session->sqAppend('PUSH', ['TRADE_LIST_UPDATE' => true], null, $v['lord_pk'], $v['posi_pk']);

        $remaind_price += ($params['unit_price'] - $v['unit_price']) * $v['deal_amount'];
        $total_deal_amount += $v['deal_amount'];
    }

    // 차액 환불
    if ($remaind_price > 0) {
        $r = $GoldPop->increaseGold($params['posi_pk'], (int)$remaind_price, null, 'trade_remaind_price');
        if (!$r) {
            // TODO 오류 로그
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'can not gold increase remaind_price;lord['. $Session->lord['lord_pk']. ';posi_pk['.$params['posi_pk'].'];remaind_price['. $remaind_price. '];');
        }
    }

    $deli_list = [];
    $cnt = 0;

    $PgGame->query('SELECT deli_pk, offe_posi_pk, offe_lord_pk, total_price FROM trade_delivery WHERE bid_posi_pk = $1 AND end_dt is null', [$params['posi_pk']]);

    while ($PgGame->fetch()) {
        $deli_list[$cnt] = $PgGame->row;
        $cnt++;
    }

    $Timer = new Timer($Session, $PgGame);
    for($i = 0; $i < COUNT($deli_list); $i++) {
        $deli_pk = $deli_list[$i]['deli_pk'];
        $description = 'deli_pk:' . $deli_pk;

        //타이머 등록
        $Timer->set($params['posi_pk'], 'S', $deli_pk, 'D', $description, 1800);

        //상태 변경
        $query_params = [$deli_pk];
        $PgGame->query('UPDATE trade_delivery SET end_dt = now() + Interval \'+1800 second\' WHERE deli_pk = $1', $query_params);
    }

    // Log
    $Log->setBuildingTradedept($Session->lord['lord_pk'], $params['posi_pk'], 'bid', $params['type'], $params['bid_amount'], $params['unit_price'], $total_deal_amount);

    //퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'tradeDept','type' => 'buy']);

    return $Render->nsXhrReturn('success');
}));

// 판매 주문
$app->post('/api/tradeDept/offer', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$params['posi_pk'], PK_BUILDING_TRADEDEPT]);
    $level = $PgGame->fetchOne();

    $PgGame->query('SELECT count(offe_pk) FROM trade_offer WHERE posi_pk = $1', [$params['posi_pk']]);
    $trade_cnt = $PgGame->fetchOne();

    $PgGame->query('SELECT count(bid_pk) FROM trade_bid WHERE posi_pk = $1', [$params['posi_pk']]);
    $trade_cnt = $trade_cnt + $PgGame->fetchOne();

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['building']);

    if ($trade_cnt >= $_M['BUIL'][PK_BUILDING_TRADEDEPT]['level'][$level]['variation_1']) {
        throw new ErrorHandler('error',  $i18n->t('msg_trade_bid_max')); // 최대 주문건수를 넘어 추가주문이 불가합니다.
    }

    if ($params['offer_amount'] < 1) {
        throw new ErrorHandler('error',  $i18n->t('msg_trade_sell_need_amount')); // 판매 수량을 입력해주세요.
    }

    if ($params['offer_amount'] < 1000) {
        throw new ErrorHandler('error',  $i18n->t('msg_trade_sell_min')); // 판매수량이 1,000 이상일 경우만 판매 가능합니다.
    }

    if ($params['unit_price'] <= 0) {
        throw new ErrorHandler('error',  $i18n->t('msg_trade_sell_need_price')); // 판매 단가을 입력해주세요.
    }

    $PgGame->query('SELECT COUNT(bid_pk) FROM trade_bid WHERE posi_pk = $1 AND reso_type = $2', [$params['posi_pk'], strtoupper(substr($params['type'], 0, 1))]);
    if ($PgGame->fetchOne() > 0) {
        throw new ErrorHandler('error',  $i18n->t('msg_trade_sell_same_resource')); // 구매 중인 동일 자원이 있으면 판매가 불가능 합니다
    }

    if (strpos($params['unit_price'], '.')) {
        $params['unit_price'] = floatval(substr(strval($params['unit_price']), 0, (strpos($params['unit_price'], '.') + 2)));
    }

    $total_price = ceil($params['offer_amount'] * $params['unit_price']);
    $commission = ceil($total_price * 0.1);
    $type = strtoupper(substr($params['type'], 0, 1));

    // 자원 소모
    global $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        $Resource = new Resource($Session, $PgGame);
        $r = $Resource->decrease($params['posi_pk'], [$params['type'] => $params['offer_amount']], null, 'offer');
        if (! $r) {
            throw new Exception($i18n->t('msg_resource_lack')); // 자원이 부족합니다.
        }

        // 골드 소모
        $GoldPop = new GoldPop($Session, $PgGame);
        $r = $GoldPop->decreaseGold($params['posi_pk'], $commission, null, 'offer_commission');
        if (!$r) {
            throw new Exception($i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
        }

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error',  $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    // 판매하기
    $r = $PgGame->query('SELECT posi_pk, lord_pk, unit_price, deal_amount FROM updatetradeoffer (\'' .$params['posi_pk'] . '\', \''.$type.'\', '.(INT)$params['offer_amount']. ','.(DOUBLE)$params['unit_price'].','.$total_price.','.$Session->lord['lord_pk'].')');
    if (!$r) {
        $GoldPop->increaseGold($params['posi_pk'], $commission, null, 'offer_fail');
        $Resource->increase($params['posi_pk'], [$params['type'] => $params['offer_amount']], null, 'offer_fail');
        throw new ErrorHandler('error', 'Error Occurred. [27001]');
    }
    $PgGame->fetchAll();
    $rows = $PgGame->rows;

    $Troop = new Troop($Session, $PgGame);
    $Report = new Report($Session, $PgGame);
    $Log = new Log($Session, $PgGame);
    foreach($rows AS $k => $v) {
        $r = $GoldPop->increaseGold($params['posi_pk'], ceil($v['unit_price']*$v['deal_amount']), null, 'offer');
        if (!$r) {
            // TODO 오류 로그
            // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'can not gold increase offer;posi_pk['.$params['posi_pk'].'];gold['.$v['deal_price'].'];');
        }

        // 보고서
        $z_content = [];

        // reso
        $z_content['reso_type'] = $params['type'];
        $z_content['reso_amount'] = $v['deal_amount'];
        $z_content['gold_amount'] = ceil($v['unit_price'] * $v['deal_amount']);

        // from & to
        $z_from = ['posi_pk' => $params['posi_pk'], 'posi_name' => $Troop->getPositionName($params['posi_pk'])];
        $z_to = ['posi_pk' => '-', 'posi_name' => "-:{$i18n->t('other_territory')}:0"];

        // title & summary
        $z_title = '';
        $z_summary = '';
        $Report->setReport($Session->lord['lord_pk'], 'move', 'shipping_sale', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));

        // Log
        $Log->setBuildingTradedept($v['lord_pk'], $v['posi_pk'], 'bid', $type, $params['offer_amount'], $v['unit_price'], $v['deal_amount'], 'offer lord_pk['. $Session->lord['lord_pk']. '];offer posi['. $params['posi_pk']. ']; gold['.ceil($v['unit_price'] * $v['deal_amount']).'];');

        // 환급 (구매자는 더 싸게 살수 있다.) - TODO ?

    }

    $deli_list = [];
    $cnt = 0;

    $PgGame->query('SELECT deli_pk, bid_posi_pk, bid_lord_pk FROM trade_delivery WHERE offe_posi_pk = $1 AND end_dt is null', [$params['posi_pk']]);

    while ($PgGame->fetch()) {
        $deli_list[$cnt] = $PgGame->row;
        $cnt++;
    }

    $Timer = new Timer($Session, $PgGame);
    $Quest = new Quest($Session, $PgGame);
    for($i = 0; $i < COUNT($deli_list); $i++) {
        $deli_pk = $deli_list[$i]['deli_pk'];
        $description = 'deli_pk:' . $deli_pk;

        //타이머 등록
        $Timer->set($deli_list[$i]['bid_posi_pk'], 'S', $deli_pk, 'D', $description, 1800, null, $deli_list[$i]['bid_lord_pk']);

        //상태 변경
        $PgGame->query('UPDATE trade_delivery SET end_dt = now() + Interval \'+1800 second\' WHERE deli_pk = $1', [$deli_pk]);

        //구매자 배송현황 업데이트
        $Session->sqAppend('PUSH', ['TRADE_LIST_UPDATE' => true], null, $deli_list[$i]['bid_lord_pk'], $deli_list[$i]['bid_posi_pk']);

        //퀘스트 체크
        $Quest->conditionCheckQuest($deli_list[$i]['bid_lord_pk'], ['quest_type' => 'tradeDept','type' => 'buy']);
    }

    // Log
    $Log->setBuildingTradedept($Session->lord['lord_pk'], $params['posi_pk'], 'offer', $params['type'], $params['offer_amount'], $params['unit_price']);

    //퀘스트 체크
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'tradeDept','type' => 'sell']);


    return $Render->nsXhrReturn('success');
}));

$app->post('/api/tradeDept/orderList', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 주문 현황
    $PgGame->query('SELECT bid_pk, reso_type, bid_amount, unit_price, deal_amount, \'B\' as trade_type FROM trade_bid WHERE posi_pk = $1', [$params['posi_pk']]);

    $cnt = 0;
    $order_list = [];

    while($PgGame->fetch()) {
        $order_list[$cnt++] = $PgGame->row;
    }

    $PgGame->query('SELECT offe_pk, reso_type, offer_amount, unit_price, deal_amount, \'O\' as trade_type FROM trade_offer WHERE posi_pk = $1', [$params['posi_pk']]);

    while($PgGame->fetch()) {
        $order_list[$cnt++] = $PgGame->row;
    }

    return $Render->nsXhrReturn('success', null, $order_list);
}));

$app->post('/api/tradeDept/deliveryList', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 배송 현황
    $PgGame->query('SELECT deli_pk, reso_type, deal_amount, unit_price, date_part(\'epoch\', end_dt)::integer as end_dt FROM trade_delivery WHERE bid_posi_pk = $1', [$params['posi_pk']]);

    $delivery_list = [];

    while($PgGame->fetch()) {
        $delivery_list[$PgGame->row['deli_pk']] = $PgGame->row;
    }

    return $Render->nsXhrReturn('success', null, $delivery_list);
}));

$app->post('/api/tradeDept/cancelBid', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;

    $GoldPop = new GoldPop($Session, $PgGame);
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        // 구매 취소
        $PgGame->query('SELECT reso_type, bid_amount, deal_amount, deal_price, unit_price, total_price FROM trade_bid WHERE bid_pk = $1 AND posi_pk = $2 FOR UPDATE', [$params['bid_pk'], $params['posi_pk']]);
        $r = $PgGame->fetch();
        if (!$r) {
            throw new Exception('This purchase order does not exist.');
        }
        $row = $PgGame->row;

        // 지금까지 이미 구매된 황금 빼고 나머지 황금을 돌려받음
        $r = $GoldPop->increaseGold($params['posi_pk'], ceil($row['deal_amount']*$row['unit_price']), null, 'cancel_bid');
        if (!$r) {
            throw new Exception('Error Occurred. [27002]');
        }

        // db에서 구매 요청한 정보 삭제
        $PgGame->query('DELETE FROM trade_bid WHERE bid_pk = $1', [$params['bid_pk']]);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error',  $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    //시세표 업데이트
    $PgGame->query('SELECT updatetradepricelist (\'B\', \''.$row['reso_type'].'\', '.(DOUBLE)$row['unit_price']. ','.(INT)($row['deal_amount']*-1).')');

    // Log
    $Log = new Log($Session, $PgGame);
    $Log->setBuildingTradedept($Session->lord['lord_pk'], $params['posi_pk'], 'cancel_bid', $row['reso_type'], $row['bid_amount'], $row['unit_price'], $row['deal_amount'], 'gold['.(ceil($row['deal_amount']*$row['unit_price']).'];'));

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/tradeDept/cancelOffer', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    $Resource = new Resource($Session, $PgGame);
    try {
        $PgGame->begin();

        $PgGame->query('SELECT reso_type, offer_amount, deal_amount, deal_price, unit_price, total_price FROM trade_offer WHERE offe_pk = $1 AND posi_pk = $2 FOR UPDATE', [$params['offe_pk'], $params['posi_pk']]);
        $r = $PgGame->fetch();
        if (!$r) {
            throw new Exception('This sales order does not exist.');
        }
        $row = $PgGame->row;

        $reso_type = '';
        // 이미 판매된 자원은 빼고 나머지 자원을 돌려받음
        if ($row['deal_amount'] > 0) {
            if ($row['reso_type'] == 'F') {
                $reso_type = 'food';
            } else if ($row['reso_type'] == 'H') {
                $reso_type = 'horse';
            } else if ($row['reso_type'] == 'L') {
                $reso_type = 'lumber';
            } else if ($row['reso_type'] == 'I') {
                $reso_type = 'iron';
            }

            $r = $Resource->increase($params['posi_pk'], [$reso_type => $row['deal_amount']], null,'cancel_offer');
            if (! $r) {
                throw new Exception('Error Occurred. [27003]');
            }
        }

        // db에서 구매 요청한 정보 삭제
        $PgGame->query('DELETE FROM trade_offer WHERE offe_pk = $1', [$params['offe_pk']]);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error',  $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    //시세표 업데이트 - 이미 판매된 양을 제외하고 아직 판매되지 않은 것을 시세표에서 차감
    $PgGame->query('SELECT updatetradepricelist (\'O\', \''.$row['reso_type'].'\', '.(DOUBLE)$row['unit_price']. ','.(INT)($row['deal_amount']*-1).')');

    // Log
    $Log = new Log($Session, $PgGame);
    $Log->setBuildingTradedept($Session->lord['lord_pk'], $params['posi_pk'], 'cancel_offer', $reso_type, $row['offer_amount'], $row['unit_price'], $row['deal_amount']);

    return $Render->nsXhrReturn('success');
}));