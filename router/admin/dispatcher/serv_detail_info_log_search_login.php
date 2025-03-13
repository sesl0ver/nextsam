<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_search_login', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();

    $Gm->selectPgGame($params['target_server_pk']);
    $PgGame = new Pg('SELECT');

    $where_cnt = 1;

    $where = '';
    $query_params = [];

    if (isset($params['search_start']) && isset($params['search_end'])) {
        $where .= " WHERE {$params['search_start']} <= date_part('epoch', login_dt) AND {$params['search_end']} >= date_part('epoch', login_dt)";
    }

    if (isset($params['lord_name'])) {
        $PgGame->query("SELECT lord_pk FROM lord WHERE lord_name = $1::text", [$params['lord_name']]);
        $lord_pk = $PgGame->fetchOne();
        if ($lord_pk && $lord_pk > 0) {
            $where .= ($where !== '') ? ' AND ' : ' WHERE ';
            $where .= 'lord_pk = $' . (count($query_params) + 1);
            $query_params[] = $lord_pk;
        }
    }

    if (isset($params['udid'])) {
        $where .= (($where !== '') ? " AND " : "WHERE ");
        $where .= ("b.udid = $". $where_cnt);
        $query_params[] = $params['udid'];
    }

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    // 오프셋 버튼이 없는 상태이므로, 임의로 넣지는 않았음.
    $PgGame->query("SELECT lord_pk, login_dt, logout_dt, platform, login_ip, login_agent, uuid FROM lord_login $where order by login_dt desc limit $limit offset $offset", $query_params);
    $PgGame->fetchAll();

    $_lord_name = [];
    $response = [];
    foreach ($PgGame->rows as $row) {
        if (! isset($_lord_name[$row['lord_pk']])) {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$row['lord_pk']]);
            $_lord_name[$row['lord_pk']] =  $PgGame->fetchOne();
        }
        $response[] = [$row['lord_pk'], $row['uuid'], $_lord_name[$row['lord_pk']], $row['login_dt'], $row['logout_dt'], $row['platform'], $row['login_ip'], $row['login_agent']];
    }
    return $Render->nsXhrReturn('success', null, $response);
}));