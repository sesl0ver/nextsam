<?php
global $app, $Render, $i18n;

$app->post('/api/heroFree/list', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('select posi_pk from building_in_castle where posi_pk = $1 and m_buil_pk = 200300 and level = 0', [$params['posi_pk']]);
    if ($PgGame->getNumRows()) {
        return $Render->nsXhrReturn('success');
    }

    $Hero = new Hero($Session, $PgGame);
    $limit_cnt = $Hero->getHerofreeOpencnt($params['posi_pk']);

    $PgGame->query('SELECT t1.hero_free_pk, t1.bid_cnt, t2.m_hero_pk, t2.level, t2.rare_type,
  t2.leadership_basic+t2.leadership_enchant+t2.leadership_plusstat AS leadership,
  t2.mil_force_basic+t2.mil_force_enchant+t2.mil_force_plusstat AS mil_force,
  t2.intellect_basic+t2.intellect_enchant+t2.intellect_plusstat AS intellect,
  t2.politics_basic+t2.politics_enchant+t2.politics_plusstat AS politics,
  t2.charm_basic+t2.charm_enchant+t2.charm_plusstat AS charm
FROM hero_free AS t1, hero AS t2 WHERE t1.hero_pk = t2.hero_pk AND t1.posi_stat_pk = (SELECT posi_stat_pk FROM position_region WHERE posi_regi_pk = (SELECT posi_regi_pk FROM position_area WHERE posi_area_pk = (SELECT posi_area_pk FROM position WHERE posi_pk = $1)))
ORDER BY t1.hero_free_pk DESC LIMIT $2', [$params['posi_pk'], $limit_cnt]);

    $pks = [];

    while ($PgGame->fetch()) {
        $r = &$PgGame->row;
        $ret[$r['hero_free_pk']] = $r;
        $pks[] = $r['hero_free_pk']; // bided 체크용
    }

    // my_bid_cnt 체크
    $PgGame->query('SELECT hero_free_pk FROM hero_free_bid WHERE posi_pk = $1 GROUP BY hero_free_pk', [$params['posi_pk']]);
    $PgGame->fetchAll();
    $my_bid_cnt = count($PgGame->rows);

    // bided 체크
    if (COUNT($pks) > 0) {
        $PgGame->query('SELECT hero_free_pk FROM hero_free_bid WHERE hero_free_pk IN ('. implode(',', $pks). ') AND posi_pk = $1', [$params['posi_pk']]);
        while ($PgGame->fetch()) {
            $ret[$PgGame->row['hero_free_pk']]['bidding'] = true;
        }
    }

    $result = [];

    // 리스트 저장
    $result['list'] = !isset($ret) ? [] : $ret;

    // 영지내 입찰 중인 횟수
    $PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$params['posi_pk'], PK_BUILDING_RECEPTIONHALL]);
    $level = $PgGame->fetchOne();

    $result['total_bid_cnt'] = $level;
    $result['my_bid_cnt'] = $my_bid_cnt;

    return $Render->nsXhrReturn('success', null, $result);
}));

$app->post('/api/heroFree/bid', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT hero_pk, bid_cnt FROM hero_free WHERE hero_free_pk = $1', [$params['hero_free_pk']]);
    if (!$PgGame->fetch()) {
        throw new ErrorHandler('error', 'Invalid request.');
    }

    $bid_cnt = $PgGame->row['bid_cnt'];

    $Hero = new Hero($Session, $PgGame);

    $bid_info = [];
    $bid_info['hero_info'] = $Hero->getFreeHeroInfo($PgGame->row['hero_pk']);
    $bid_info['bid_cnt'] = $bid_cnt;
    $bid_info['bidding'] = false;
    $bid_info['bidding_gold'] = 0;
    $bid_info['bidding_dt'] = null;
    $bid_info['bid_best'] = 0;
    $bid_info['bid_best_dt'] = null;
    $bid_info['bid_list'] = [];

    $PgGame->query('SELECT gold, date_part(\'epoch\', bid_dt)::integer as bid_dt, lord_pk FROM hero_free_bid WHERE hero_free_pk = $1 ORDER BY gold DESC, bid_dt', [$params['hero_free_pk']]);
    $PgGame->fetchAll();
    $bid_list_arr = $PgGame->rows;
    if (count($bid_list_arr) > 0) {
        $i = 0;
        foreach ($bid_list_arr as $k => $v) {
            // 내 입찰 정보
            if ($v['lord_pk'] == $Session->lord['lord_pk'] && $bid_info['bidding_gold'] < $v['gold']) {
                $bid_info['bidding'] = true;
                $bid_info['bidding_gold'] = $v['gold'];
                $bid_info['bidding_dt'] = (! isset($v['bid_dt'])) ? null : date('i분s초', $v['bid_dt']);
            }

            // 최고 입찰 정보
            if ($bid_info['bid_best'] < $v['gold']) {
                $bid_info['bid_best'] = $v['gold'];
                $bid_info['bid_best_dt'] = (! isset($v['bid_dt'])) ? null : date('i분s초', $v['bid_dt']);
            } else {
                $bid_info['bid_list'][] = ['gold' => $v['gold'], 'bid_dt' => date('i분s초', $v['bid_dt'])];
                $i ++;
                if ($i >= 3) {
                    break;
                }
            }
        }
    }

    /*$query_params = Array($params['hero_free_pk'], $params['posi_pk']);
    $PgGame->query('SELECT gold FROM hero_free_bid WHERE hero_free_pk = $1 AND posi_pk = $2', $query_params);
    if ($PgGame->fetch())
    {
        $heroInfo['bidding'] = true;
        $heroInfo['bidding_gold'] = $PgGame->row['gold'];
    }*/

    return $Render->nsXhrReturn('success', null, $bid_info);
}));

$app->post('/api/heroFree/bidding', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $already_row = false;

    // 입찰 마감 시간 확인
    $now_minutes = date('i');
    if ($now_minutes >= 0 && $now_minutes < 5) {
        throw new ErrorHandler('error', '이미 마감된 입찰 입니다.');
    }

    // 유효한 입찰 인지 검사
    $PgGame->query('SELECT hero_free_pk FROM hero_free WHERE hero_free_pk = $1', [$params['hero_free_pk']]);
    if (!$PgGame->fetchOne()) {
        throw new ErrorHandler('error', '이미 마감된 입찰 입니다.');
    }

    $PgGame->query('SELECT hero_free_bid_pk, gold, rebid_cnt FROM hero_free_bid WHERE hero_free_pk = $1 AND posi_pk = $2 FOR UPDATE', [$params['hero_free_pk'], $params['posi_pk']]);
    if ($PgGame->fetch()) {
        $already_row = $PgGame->row;
    }

    if ($already_row !== false) {
        $decr_gold = $params['bidding_gold'] - $already_row['gold'];
    } else {
        // my_bid_cnt 체크
        $PgGame->query('SELECT hero_free_pk FROM hero_free_bid WHERE posi_pk = $1 GROUP BY hero_free_pk', [$params['posi_pk']]);
        $PgGame->fetchAll();
        $my_bid_cnt = count($PgGame->rows);

        // 영지내 입찰 가능 횟수
        $PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$params['posi_pk'], PK_BUILDING_RECEPTIONHALL]);
        $total_bid_cnt = $PgGame->fetchOne();

        if ($my_bid_cnt >= $total_bid_cnt) {
            throw new ErrorHandler('error', '입찰 가능한 횟수를 초과하였습니다.');
        }

        $decr_gold = $params['bidding_gold'];
        $desc_type = 'bidding';
    }

    //  입찰전 황금 체크
    // 소유한 황금보다 많은가?
    $GoldPop = new GoldPop($Session, $PgGame);
    $curr_gold = $GoldPop->get($params['posi_pk']);
    if ($curr_gold['gold_curr'] < $decr_gold) {
        throw new ErrorHandler('error', '황금이 부족합니다.');
    }

    // 최소 입찰가가 맞는가?
    $PgGame->query('SELECT t1.level, t1.rare_type FROM hero t1, hero_free t2 WHERE t1.hero_pk = t2.hero_pk AND t2.hero_free_pk = $1', Array($params['hero_free_pk']));
    $PgGame->fetch();

    // 재 입찰이라면 최소 입찰액이 맞는지 체크
    global $_M;
    if ($already_row !== false) {
        $min_gold = $PgGame->row['level'] * ($_M['HERO_FREE_BID_GOLD_UNIT'][$PgGame->row['rare_type']]);
    } else {
        $min_gold = ($PgGame->row['level'] * 1000) * pow($PgGame->row['rare_type'], 3);
    }

    if ($min_gold > $decr_gold) {
        throw new ErrorHandler('error', '입찰 가능 금액보다 입찰금이 낮습니다.<br><br>※최고 입찰금 보다 높은 금액으로 입찰에 참여해 주세요.');
    }

    // 최대 입찰인지 체크해야함.
    $PgGame->query('SELECT count(hero_free_bid_pk) FROM hero_free_bid WHERE hero_free_pk = $1 AND gold >= $2', [$params['hero_free_pk'], $params['bidding_gold']]);
    $up_bid_cnt = $PgGame->fetchOne();
    if ($up_bid_cnt > 0) {
        throw new ErrorHandler('error',  '최소입찰금 보다 낮습니다.');
    }

    // 황금 차감
    $rebid_cnt = ($already_row !== false) ? ($already_row['rebid_cnt']+1) : 0;
    $GoldPop->decreaseGold($params['posi_pk'], $decr_gold, null, 'bidding');
    $PgGame->query('INSERT INTO hero_free_bid (hero_free_pk, posi_pk, lord_pk, gold, rebid_cnt, bid_dt) VALUES ($1, $2, $3, $4, $5, now())', [$params['hero_free_pk'], $params['posi_pk'], $Session->lord['lord_pk'], $params['bidding_gold'], $rebid_cnt]);

    if (! $already_row) {
        $PgGame->query('UPDATE hero_free SET bid_cnt = bid_cnt + 1 WHERE hero_free_pk = $1', [$params['hero_free_pk']]);
    }

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'bidding']);
    $Quest->countCheckQuest($Session->lord['lord_pk'], 'EVENT_HERO_FREE', ['value' => 1]);

    // Log
    $Log = new Log($Session, $PgGame);
    $Log->setBuildingReceptionhall($Session->lord['lord_pk'], $params['posi_pk'], 'bidding', $params['hero_free_pk'], $params['bidding_gold']);

    return $Render->nsXhrReturn('success');
}));
