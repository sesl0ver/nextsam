<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_ccu', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $query_params = [];
    $where = '';

    if (isset($params['search_start']) && isset($params['search_end'])) {
        $where .= " WHERE {$params['search_start']} <= date_part('epoch', dt) AND {$params['search_end']} >= date_part('epoch', dt)";
    }

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;
    $PgGame->query("SELECT dt, ccu FROM gmtool_ccu$where order by dt desc limit $limit offset $offset", $query_params);
    $PgGame->fetchAll();

    $response = [];
    foreach ($PgGame->rows as $row) {
        $response[] = [$row['dt'], $row['ccu']];
    }
    return $Render->nsXhrReturn('success', null, $response);
}));



