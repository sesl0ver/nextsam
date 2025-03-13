<?php
global $app, $Render, $i18n;

$app->post('/api/market/list', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 시장 확인
    $PgGame->query('SELECT m_buil_pk, level FROM building_in_castle WHERE posi_pk = $1 AND in_castle_pk = $2', [$params['posi_pk'], $params['in_cast_pk']]);
    $PgGame->fetch();
    if ($PgGame->row['m_buil_pk'] != PK_BUILDING_MARKET || $PgGame->row['level'] < 1) {
        return $Render->nsXhrReturn('success');
    }

    // list 보기
    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['building']);
    $build_level = $PgGame->row['level'];

    // 보여줘야 할 개수
    $my_list_cnt = $_M['BUIL'][PK_BUILDING_MARKET]['level'][$build_level]['variation_1'];

    $PgGame->query('SELECT date_part(\'epoch\', market_sale_up_dt)::integer FROM territory WHERE posi_pk = $1 limit 1', [$params['posi_pk']]);
    $register_dt = $PgGame->FetchOne();

    $now_dt = Useful::nowServerTime($PgGame);

    $regi_hour = date('G', $register_dt);
    $now_hour = date('G', $now_dt);
    $remain_dt = $now_dt - $register_dt;

    // 리스트 만들기
    if ($regi_hour != $now_hour || $remain_dt >= 3600) {
        // 전체 개수
        $max_cnt = $_M['BUIL'][PK_BUILDING_MARKET]['level']['10']['variation_1'];

        // 시장 목록 비율
        $rate = ['gold' => 0.3, 'food' => 0.1, 'horse' => 0.1, 'lumber' => 0.05, 'iron' => 0.05, 'cashitem' => 0.4];
        $market_list = [];
        foreach($rate AS $k => $v) {
            $market_list[$k] = $max_cnt * $v * (1/($max_cnt/$my_list_cnt));
        }

        $create_list = [];
        $remain_list = [];
        $cnt = 0;
        $list_cnt = 0;

        foreach($market_list AS $k => $v) {
            // 1이상일 경우
            if ($v >= 1) {
                // 상품 리스트 만들기
                for($i = 0; $i < floor($v); $i++) {
                    $create_list[$cnt] = ['sale_type' => $k];  // 종류
                    $cnt++;
                }
                $list_cnt += floor($v);
            }

            // 남은 리스트 만들기 위한....
            if ($v - floor($v) > 0) {
                $remain_list[$k] = $k;
            }
        }

        shuffle($create_list);

        // 리스트 남은 갯수
        $remain_list_cnt = $my_list_cnt - $list_cnt;

        shuffle($remain_list);

        for ($i = 0; $i < $remain_list_cnt; $i++) {
            $create_list[$cnt] = ['sale_type' => $remain_list[$i]];  // 종류
            $cnt++;
        }

        // 수량 결정하기
        $PgGame->query('SELECT level FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
        $lord_level = $PgGame->fetchOne();

        foreach($create_list AS &$v) {
            // 판매 수량
            $pay_type = 'R';
            $m_item_pk = null;

            if ($v['sale_type'] != 'cashitem') { // 캐쉬 아이템이 아닌 경우 (황금, 식량, 목재 등등)
                // 판매할 상품의 최소량 , 최대량
                $min_amount = $_M['MARKET_SALE_AMOUNT'][$lord_level][$v['sale_type']] - ($_M['MARKET_SALE_AMOUNT'][$lord_level][$v['sale_type']] / 2);
                $max_amount = $_M['MARKET_SALE_AMOUNT'][$lord_level][$v['sale_type']] + ($_M['MARKET_SALE_AMOUNT'][$lord_level][$v['sale_type']] / 2);

                // 판매량 결정 // 최대 최소 +- 50%
                $amount = rand($min_amount, $max_amount);

                $amount = floor($amount / 100) * 100;
                $v['sale_amount'] = $amount;

                // 지불할 자원 종류 결정
                if ($_M['QBIG_TRANSRATE_VALUE'][$v['sale_type']] < $amount) {
                    $value = rand(1, 100);
                    // 자원+큐빅 or 자원
                    $pay_type = ($value >= 70) ? 'Q' : 'R';
                }

                $qbig = 0;

                if ($pay_type == 'Q') {
                    // 페이타입에 맞게 지불가격 결정
                    $max_qbig = floor($amount / $_M['QBIG_TRANSRATE_VALUE'][$v['sale_type']]);

                    // 지불 큐빅 결정
                    $qbig = rand(1, ceil($max_qbig));
                    // 판매 상품이 자원인 경우 큐빅 제한 200
                    $qbig = ($qbig > 200) ? 200 : $qbig;
                    $qbig = ($qbig < 1)? 1 : $qbig;
                }

                // 지불할 자원 종류 결정 - 판매하는 자원과는 겹치지 않도록
                $arr_type = ['gold', 'food', 'horse', 'lumber', 'iron'];
                $arr_key = array_search($v['sale_type'], $arr_type);
                unset($arr_type[$arr_key]);
                shuffle($arr_type);
                $type = $arr_type[0];

                // 지불할 자원량 결정
                $v['pay_reso_amount'] = ($amount - ($qbig * $_M['QBIG_TRANSRATE_VALUE'][$v['sale_type']]));
                $v['pay_reso_amount'] *= ($_M['QBIG_TRANSRATE_VALUE'][$type] / $_M['QBIG_TRANSRATE_VALUE'][$v['sale_type']]);

            } else {	// 캐쉬 아이템인 경우
                $v['sale_amount'] = 1; // 캐쉬 아이템은 오로지 1개

                $NsGlobal->requireMasterData(['item']);

                // M_ITEM의 원소를 key 기준으로 역순 정렬하고 첫번째 원소의 key를 꺼내면 마지막으로 등록된 아이템의 pk가 된다
                $m_item = $_M['ITEM'];
                $m_item = array_filter($m_item, function ($m) {
                    return $m['yn_market_sale'] == 'Y';
                });

                // 아이템 결정
                do {
                    shuffle($m_item);
                    $m_item_pk = array_values($m_item)[0]['m_item_pk'];
                } while ($_M['ITEM'][$m_item_pk]['yn_market_sale'] != 'Y');

                $item_price = (INT)$_M['ITEM'][$m_item_pk]['price'] * (rand(100, 120) / 100);

                $r = rand(1, 100);
                if ($r == 1) {
                    // 1~5% 이하로 큐빅 책정
                    $qbig_pct = rand(1, 5);
                } else if ($r > 1 && $r <= 20) {
                    //15~80% 미만으로 가격 책정
                    $qbig_pct = rand(15, 79);
                } else { // if ($r > 20 && $r <= 100)
                    // 80~90%로 가격 설정
                    $qbig_pct = rand(80, 90);
                }
                $qbig = round($item_price * ($qbig_pct / 100));
                $qbig = ($qbig < 1) ? 1 : $qbig;

                // 현재 계산된 큐빅값이 캐시샵 판매가보다 높을 경우엔 -1 큐빅 해줌.
                if ((INT)$_M['ITEM'][$m_item_pk]['price'] <= $qbig) {
                    $qbig = (INT)$_M['ITEM'][$m_item_pk]['price'] - 1;
                }

                // 지불할 자원 종류 결정
                $arr_type = ['gold', 'food', 'horse', 'lumber', 'iron'];
                shuffle($arr_type);
                $type = $arr_type[0];

                // 큐빅 지불하고 남은 양을 자원으로 환산
                $v['pay_reso_amount'] = $_M['QBIG_TRANSRATE_VALUE'][$type] * ($item_price - $qbig);
                $v['pay_reso_amount'] = (INT)(floor($v['pay_reso_amount'] / 100)) * 100;

                $pay_type = 'Q';
            }

            $v['pay_type'] = $pay_type;
            $v['pay_cash_amount'] = $qbig;
            $v['m_item_pk'] = $m_item_pk;
            $v['pay_reso_type'] = $type;
        }

        //기존리스트 삭제
        $PgGame->query('DELETE FROM market WHERE posi_pk = $1', [$params['posi_pk']]);

        // DB로 저장 - TODO 비효율... Multiple Insert로 변경해야함.
        for($i = 0; $i < COUNT($create_list); $i++) {
            $PgGame->query('INSERT INTO market (posi_pk, sale_type, m_item_pk, sale_amount, pay_type, pay_reso_type, pay_reso_amount, pay_cash_amount, register_dt)
VALUES ($1, $2, $3, $4, $5, $6, $7, $8, now());', [$params['posi_pk'], $create_list[$i]['sale_type'], $create_list[$i]['m_item_pk'], (INT)$create_list[$i]['sale_amount'], $create_list[$i]['pay_type'], $create_list[$i]['pay_reso_type'], (INT)$create_list[$i]['pay_reso_amount'], (INT)$create_list[$i]['pay_cash_amount']]);
        }

        $PgGame->query('UPDATE territory SET market_sale_up_dt = now() WHERE posi_pk = $1', [$params['posi_pk']]);

        // TODO : 리스트가 정해진 갯수 이상으로 만들어질 경우 처리가 필요함.
    }

    $ret = [];

    $PgGame->query('SELECT sale_pk, sale_type, m_item_pk, sale_amount, pay_type, pay_reso_type, pay_reso_amount, pay_cash_amount FROM market WHERE posi_pk = $1 ORDER BY sale_type LIMIT $2', [$params['posi_pk'], $my_list_cnt]);
    while ($PgGame->fetch()) {
        $ret[$PgGame->row['sale_pk']] = $PgGame->row;
    }
    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/market/buy', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 시장 확인
    $PgGame->query('SELECT m_buil_pk, level FROM building_in_castle WHERE posi_pk = $1 AND in_castle_pk = $2', [$params['posi_pk'], $params['in_cast_pk']]);
    $PgGame->fetch();
    if ($PgGame->row['m_buil_pk'] != PK_BUILDING_MARKET || $PgGame->row['level'] < 1) {
        throw new ErrorHandler('error', 'Invalid request.'); // 잘못된 요청입니다.
    }

    global $NsGlobal, $_M, $_NS_SQ_REFRESH_FLAG;
    $NsGlobal->requireMasterData(['item']);

    // posi_pk : 구입을 원하는 시장이 위치한 영지 pk, sale_pk : 시장 판매 상품 pk

    // 선 검사 > 지불할 수 있는 조건이 충분한가? 1. 큐빅이 지불 대상이면 큐빅이 충분한지? 2. 자원은 충분한지?
    $PgGame->query('SELECT sale_type, m_item_pk, sale_amount, pay_type, pay_reso_type, pay_reso_amount, pay_cash_amount FROM market WHERE sale_pk = $1 AND posi_pk = $2', [$params['sale_pk'], $params['posi_pk']]);
    if (!$PgGame->fetch()) {
        throw new ErrorHandler('error', 'The product does not exist.'); // 해당 상품이 없습니다.
    }

    $sale_data = $PgGame->row;

    $Cash = new Cash($Session, $PgGame);
    $Item = new Item($Session, $PgGame);

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);

    try {
        $PgGame->begin();
        $_NS_SQ_REFRESH_FLAG = true;

        // 큐빅 지불 조건 검사
        if ($sale_data['pay_type'] == 'Q') {
            // 큐빅 검사
            $ret = $Cash->decreaseCash($Session->lord['lord_pk'], $sale_data['pay_cash_amount'], 'market');
            if(!$ret) {
                throw new Exception($i18n->t('msg_qbig_lack')); // 큐빅이 부족합니다.
            }
        }

        // 자원 지불 조건 검사
        if ($sale_data['pay_reso_type'] == 'gold') {
            $ret = $GoldPop->decreaseGold($params['posi_pk'], $sale_data['pay_reso_amount'], null, 'market_buy');
            if (! $ret) {
                throw new Exception($i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
            }
        } else {
            $res = [];
            $res[$sale_data['pay_reso_type']] = $sale_data['pay_reso_amount'];
            $ret = $Resource->decrease($params['posi_pk'], $res, null, 'market_buy');
            if (! $ret) {
                throw new Exception($i18n->t('msg_resource_lack')); // 자원이 부족합니다.
            }
        }

        // 지불 시행 / 판매하는 자원(또는 아이템) 증가 요청
        if ($sale_data['sale_type'] != 'cashitem') {
            // 자원을 판매하면 황금 또는 나머지 자원 증가
            if ($sale_data['sale_type'] != 'gold') {
                // 황금이 아닌 자원들
                $res = [];
                $res[$sale_data['sale_type']] = $sale_data['sale_amount'];

                $ret = $Resource->increase($params['posi_pk'], $res, null, 'market_buy');
                if (! $ret) {
                    throw new Exception('Resource increase failed.');
                }
            } else {
                // 황금을 증가
                $ret = $GoldPop->increaseGold($params['posi_pk'], $sale_data['sale_amount'], null, 'market_buy');
                if (!$ret) {
                    throw new Exception('Gold increase failed.');
                }
            }
        } else {
            // 아이템을 판매하면 해당 아이템 갯수 1 증가
            $ret = $Item->BuyItem($Session->lord['lord_pk'], $sale_data['m_item_pk'], 1, 'market');
            if (!$ret) {
                throw new Exception('Item payment failed.');
            }
        }

        // 지불 완료 / 구매한 판매 상품은 db에서 제거
        $ret = $PgGame->query('DELETE FROM market WHERE sale_pk = $1', [$params['sale_pk']]);
        if (!$ret) {
            throw new Exception('Product removal failed.');
        }

        //퀘스트 체크
        $Quest = new Quest($Session, $PgGame);
        $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'market','type' => 'buy']);
        $Quest->countCheckQuest($Session->lord['lord_pk'], 'EVENT_MARKET_BUY', ['value' => 1]);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    // Log
    $Log = new Log($Session, $PgGame);
    $Log->setBuildingMarket($Session->lord['lord_pk'], $params['posi_pk'], $sale_data['m_item_pk'], $sale_data['sale_type'], $sale_data['sale_amount'], $sale_data['pay_reso_type'], $sale_data['pay_reso_amount'], $sale_data['pay_cash_amount']);


    return $Render->nsXhrReturn('success');
}));

$app->post('/api/market/levelUpgrade', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 시장 확인
    $PgGame->query('SELECT m_buil_pk, level FROM building_in_castle WHERE posi_pk = $1 AND in_castle_pk = $2', [$params['posi_pk'], $params['in_cast_pk']]);
    $PgGame->fetch();
    if ($PgGame->row['m_buil_pk'] != PK_BUILDING_MARKET || $PgGame->row['level'] < 1) {
        throw new ErrorHandler('error', 'Invalid request.');
    }
    $build_level = $PgGame->row['level'];

    // 기존 갯수와 확인
    $PgGame->query('SELECT count(sale_pk) FROM market WHERE posi_pk = $1', [$params['posi_pk']]);
    $my_count = $PgGame->fetchOne();

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['building']);

    $my_list_cnt = $_M['BUIL'][[PK_BUILDING_MARKET]['level'][$build_level]]['variation_1'];
    if ($my_count >= $my_list_cnt) {
        return $Render->nsXhrReturn('success');
    }

    $pay_type = 'R';

    $arr_type = ['gold', 'food', 'horse', 'lumber', 'iron', 'cashitem'];
    shuffle($arr_type);
    $sale_type = $arr_type[0];

    $PgGame->query('SELECT level FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $lord_level = $PgGame->fetchOne();

    if ($sale_type != 'cashitem') {
        // 자원을 판매하면
        $min_amount = $_M['MARKET_SALE_AMOUNT'][$lord_level][$sale_type] - ($_M['MARKET_SALE_AMOUNT'][$lord_level][$sale_type] / 2);
        $max_amount = $_M['MARKET_SALE_AMOUNT'][$lord_level][$sale_type] + ($_M['MARKET_SALE_AMOUNT'][$lord_level][$sale_type] / 2);

        // 판매량 결정 // 최대 최소 +- 50%
        $sale_amount = rand($min_amount, $max_amount);

        $sale_amount = (INT)(floor($sale_amount / 100)) * 100;

        // 지불할 자원 종류 결정
        if ($_M['QBIG_TRANSRATE_VALUE'][$sale_type] < $sale_amount) {
            $value = rand(1, 100);
            // 자원+큐빅 or 자원
            $pay_type = ($value >= 70) ? 'Q' : 'R';
        }

        $qbig = 0;

        if ($pay_type == 'Q') {
            // 페이타입에 맞게 지불가격 결정
            $max_qbig = $sale_amount / $_M['QBIG_TRANSRATE_VALUE'][$sale_type];

            // 지불 큐빅 결정
            $qbig = rand(1, $max_qbig);
            // 판매 상품이 자원인 경우 큐빅 제한 200
            $qbig = ($qbig > 200) ? 200 : $qbig;
            $qbig = ($qbig < 1) ? 1 : $qbig;
        }

        // 지불할 자원 종류 결정 - 판매하는 자원과는 겹치지 않도록
        $arr_type = ['gold', 'food', 'horse', 'lumber', 'iron'];
        $arr_key = array_search($sale_type, $arr_type);
        unset($arr_type[$arr_key]);
        shuffle($arr_type);
        $pay_reso_type = $arr_type[0];

        // 지불할 자원량 결정
        $pay_reso_amount = ($sale_amount - ($qbig * $_M['QBIG_TRANSRATE_VALUE'][$sale_type]));
        $pay_reso_amount *= ($_M['QBIG_TRANSRATE_VALUE'][$pay_reso_type] / $_M['QBIG_TRANSRATE_VALUE'][$sale_type]);
    } else {
        // 아이템을 판매하면
        $sale_amount = 1; // 캐쉬 아이템은 오로지 1개

        $NsGlobal->requireMasterData(['item']);

        krsort($_M['ITEM']);
        // current($_M['ITEM']);
        $last_m_item_pk = key($_M['ITEM']);

        // 아이템 결정
        do {
            $m_item_pk = rand(500001, $last_m_item_pk);
        } while ($_M['ITEM'][$m_item_pk]['yn_market_sale'] != 'Y');

        $item_price = (INT)$_M['ITEM'][$m_item_pk]['price'] * (rand(100, 120) / 100);

        $r = rand(1, 100);
        if ($r == 1)
        {
            // 1~5% 이하로 큐빅 책정
            $qbig_pct = rand(1, 5);
        } else if ($r > 1 && $r <= 20) {
            //15~80% 미만으로 가격 책정
            $qbig_pct = rand(15, 79);
        } else if ($r > 20 && $r <= 100) {
            // 80~90%로 가격 설정
            $qbig_pct = rand(80, 90);
        }
        $qbig = round($item_price * ($qbig_pct / 100));
        $qbig = ($qbig < 1) ? 1 : $qbig;

        // 현재 계산된 큐빅값이 캐시샵 판매가보다 높을 경우엔 -1 큐빅 해줌.
        if ((INT)$_M['ITEM'][$m_item_pk]['price'] <= $qbig) {
            $qbig = (INT)$_M['ITEM'][$m_item_pk]['price'] - 1;
        }

        // 지불할 자원 종류 결정
        $arr_type = ['gold', 'food', 'horse', 'lumber', 'iron'];
        shuffle($arr_type);
        $pay_reso_type = $arr_type[0];

        // 큐빅 지불하고 남은 양을 자원으로 환산
        $pay_reso_amount = $_M['QBIG_TRANSRATE_VALUE'][$pay_reso_type] * ($item_price - $qbig);
        $pay_reso_amount = (INT)(floor($pay_reso_amount / 100)) * 100;
        $pay_type = 'Q';
    }

    // 만들어진 상품 1개를 DB로 저장
    $PgGame->query('INSERT INTO market (posi_pk, sale_type, m_item_pk, sale_amount, pay_type, pay_reso_type, pay_reso_amount, pay_cash_amount, register_dt)
VALUES ($1, $2, $3, $4, $5, $6, $7, $8, now())', [$params['posi_pk'], $sale_type, $m_item_pk, (INT)$sale_amount, $pay_type, $pay_reso_type, (INT)$pay_reso_amount, (INT)$qbig]);

    return $Render->nsXhrReturn('success');
}));