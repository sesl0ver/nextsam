<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_search_qbig', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $PgLog = new Pg('LOG');
    $Gm = new Gm();

    $Gm->selectPgGame($params['target_server_pk']);
    $PgGame = new Pg('SELECT');

    $where = '';
    $query_params = [];

    if (isset($params['search_start']) && isset($params['search_end'])) {
        $where .= " WHERE {$params['search_start']} <= date_part('epoch', log_date) AND {$params['search_end']} >= date_part('epoch', log_date)";
    }

    if (isset($params['lord_name']) && $params['lord_name']) {
        $PgGame->query("SELECT lord_pk FROM lord WHERE lord_name = $1::text", [$params['lord_name']]);
        $lord_pk = $PgGame->fetchOne();
        if ($lord_pk && $lord_pk > 0) {
            $where .= ($where !== '') ? ' AND ' : ' WHERE ';
            $where .= 'lord_pk = $' . (count($query_params) + 1);
            $query_params[] = $lord_pk;
        }

        // 큐빅흐름
        $PgGame->query("SELECT 100 as cash_regist, cash as remain_cash, use_cash FROM lord WHERE lord_name = $1", [$params['lord_name']]);
        $PgGame->fetch();
        $qbig_row = $PgGame->row;

        print_r($qbig_row);

        // 전체 충전 큐빅
        $PgGame->query("SELECT sum(b.qbig_buy) AS buy_total, sum(b.qbig_bonus) AS bonus FROM m_qbig_pack b, qbig_pack a, lord c WHERE a.store_type = b.store_type AND a.pack_type = b.pack_type AND a.lord_pk = c.lord_pk AND c.lord_name = $1", [$params['lord_name']]);
        $PgGame->fetch();

        $qbig_row['incr_cash'] = $PgGame->row['buy_total'];
        $qbig_row['desc_spent'] = $qbig_row['use_cash'];
        $qbig_row['bonus_cash'] = $PgGame->row['bonus'];
    }

    if (isset($params['offset']))
    {
        $where .= (($where !== '') ? ' AND ' : ' WHERE ');
        $where .= 'posi_pk = $' . (count($query_params) + 1);
        $query_params[] = $params['offset'];
    }

    if (isset($params['search_type'])) {
    $where .= (($where !== "") ? ' AND ' : ' WHERE ');
        $where .= "type IN ('" . str_replace(',', "','", $params['search_type']) . "')";
    }

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    $PgLog->query("SELECT log_date, web_id, lord_pk, level, type, price, before_cash, after_cash, reason, bill_qbig FROM log_qbig{$where} order by log_date desc limit $limit offset {$offset}", $query_params);
    $PgLog->fetchAll();

    /*if ($qbig_row) {
        // $response->lord_cash = $qbig_row; ??
    }*/

    $_lord_name = [];
    $response = [];
    foreach ($PgLog->rows as $row) {
        if (! isset($_lord_name[$row['lord_pk']])) {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$row['lord_pk']]);
            $_lord_name[$row['lord_pk']] =  $PgGame->fetchOne();
        }
        $response[] = [$row['log_date'], $row['web_id'], $_lord_name[$row['lord_pk']], $row['level'], $row['type'], $row['price'], $row['before_cash'], $row['after_cash'], $row['bill_qbig'], $row['reason']];
    }
    return $Render->nsXhrReturn('success', null, $response);
}));