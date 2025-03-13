<?php
global $app, $Render, $i18n;

// TODO 사용하지 않는 API

$app->post('/api/heroGachapon/list', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $rare = $params['rare'];
    $page_num = $params['page_num'];
    $m_item_pk = $params['m_item_pk'];

    $Hero = new Hero($Session, $PgGame);
    if ($params['type'] == 'collect') {
        $total_count = $Hero->getCollectionTotalCount($rare, $m_item_pk);
    } else {
        if ($m_item_pk == 500532) {
            $total_count = $Hero->getNewGachaponEventTotalCount($rare);
            $lord_buy_count = $Hero->getNewGachaponEventBuyCount($rare);
            $card_total = $Hero->getNewGachaponEventCardTotal($rare);
        } else {
            $total_count = $Hero->getGachaponEventTotalCount($rare);
            $lord_buy_count = $Hero->getGachaponEventBuyCount($rare);
            $card_total = $Hero->getGachaponEventCardTotal($rare);
        }
    }

    // 영웅 데이터가 하나도 없을 경우.
    if ($total_count == 0) {
        return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'lord_max_count' => GACHAPON_LORD_BUY_LIMIT_CNT, 'lord_buy_count' => $lord_buy_count, 'curr_page' => $page_num, 'list' => []]);
    }

    if ($m_item_pk == 500532) {
        $total_page = (INT)($total_count / NEW_GACHAPON_LIST_PAGE_NUM);
        $total_page += ($total_count % NEW_GACHAPON_LIST_PAGE_NUM > 0)? 1 : 0;
    } else {
        $total_page = (INT)($total_count / GACHAPON_LIST_PAGE_NUM);
        $total_page += ($total_count % GACHAPON_LIST_PAGE_NUM > 0)? 1 : 0;
    }

    if ($page_num < 1) {
        $page_num = 1;
    } else if ($page_num > $total_page) {
        $page_num = $total_page;
    }

    $list = [];

    if ($page_num > 0) {
        if ($m_item_pk == 500532) {
            $offset_num = (($page_num - 1) * NEW_GACHAPON_LIST_PAGE_NUM);
        } else {
            $offset_num = (($page_num - 1) * GACHAPON_LIST_PAGE_NUM);
        }

        if ($params['type'] == 'collect') {
            $query_params = [$params['m_item_pk'], GACHAPON_LIST_PAGE_NUM, $offset_num, 'N'];

            $rare_sql = '';
            if ($rare > 0) {
                $rare_sql = ' AND c.rare_type = $5';
                $query_params[] = $rare;
            }

            $PgGame->query("SELECT b.m_hero_pk FROM  m_hero_collection_combi_item a, m_hero_collection_combi b, m_hero_base c
WHERE a.m_item_pk = $1 AND a.m_hero_comb_coll_pk = b.m_hero_comb_coll_pk
AND b.m_hero_base_pk = c.m_hero_base_pk  AND c.yn_new_gacha = $4
{$rare_sql} ORDER BY a.orderno DESC, b.m_hero_pk LIMIT $2 OFFSET $3", $query_params);
        } else {
            if ($m_item_pk == 500532) {
                $offset_num = (($page_num - 1) * NEW_GACHAPON_LIST_PAGE_NUM);
                $query_params = [NEW_GACHAPON_LIST_PAGE_NUM, $offset_num];
            } else {
                $query_params = [GACHAPON_LIST_PAGE_NUM, $offset_num];
            }
            $rare_sql = '';
            if ($rare > 0) {
                $rare_sql = ' WHERE orderno = $3';
                $query_params[] = $rare;
            }
            if ($m_item_pk == 500532) {
                $sql = "SELECT m_hero_pk, gach_event_default_count, gach_event_buy_count, lord_name FROM new_gachapon_event{$rare_sql} ORDER BY orderno DESC, m_hero_pk LIMIT $1 OFFSET $2";
            } else {
                $sql = "SELECT m_hero_pk, gach_event_default_count, gach_event_buy_count, lord_name FROM gachapon_event{$rare_sql} ORDER BY orderno DESC, m_hero_pk LIMIT $1 OFFSET $2";
            }
            $PgGame->query($sql, $query_params);
        }
        $PgGame->fetchAll();
        $list = $PgGame->rows;
    } else {
        $page_num = 1;
        $total_page = 1;
    }

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'lord_max_count' => GACHAPON_LORD_BUY_LIMIT_CNT, 'lord_buy_count' => $lord_buy_count, 'card_total' => $card_total, 'curr_page' => $page_num, 'list' => $list]);
}));