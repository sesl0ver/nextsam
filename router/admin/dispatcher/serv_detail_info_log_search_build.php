<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_search_build', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
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

    if (isset($params['lord_name'])) {
        $PgGame->query("SELECT lord_pk FROM lord WHERE lord_name = $1::text", [$params['lord_name']]);
        $lord_pk = $PgGame->fetchOne();
        if ($lord_pk && $lord_pk > 0) {
            $where .= ($where !== '') ? ' AND ' : ' WHERE ';
            $where .= 'lord_pk = $' . (count($query_params) + 1);
            $query_params[] = $lord_pk;
        }
    }

    if (isset($params['offset'])) {
        $where .= ($where !== '') ? ' AND ' : ' WHERE ';
        $where .= 'posi_pk = $' . (count($query_params) + 1);
        $query_params[] = $params['offset'];
    }

    if (isset($params['search_type'])) {
        $where .= ($where !== '') ? ' AND ' : ' WHERE ';
        $where .= "type IN ('" . str_replace(',', "','", $params['search_type']) . "')";
    }

    function getBuildingLevel($buil_info, $current_level): string
    {
        if(preg_match("/curr\[([0-9]*)\]/i",$buil_info)) {
            preg_match("/curr\[([0-9]*)\]/i", $buil_info, $matches1);
            preg_match("/update\[([0-9]*)\]/i", $buil_info, $matches2);
            $result = "Lv.".$matches1[1]." → Lv.".$matches2[1];
        } else {
            $result = "Lv.".$current_level;
        }
        return $result;
    }

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;
    $PgLog->query("SELECT log_date, web_id, lord_pk, posi_pk, m_buil_pk, type, m_hero_pk, date_part('epoch', start_dt)::integer as start_dt, buil_info, web_channel, server_index, current_level, position_type, position FROM log_construction$where order by log_date desc limit $limit offset $offset", $query_params);
    $PgLog->fetchAll();

    $_lord_name = [];
    $response = [];
    foreach ($PgLog->rows as $row) {
        if (! isset($_lord_name[$row['lord_pk']])) {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$row['lord_pk']]);
            $_lord_name[$row['lord_pk']] =  $PgGame->fetchOne();
        }
        $response[] = [$row['log_date'], $row['web_id'], $_lord_name[$row['lord_pk']], $row['posi_pk'], $row['m_buil_pk'], getBuildingLevel($row['buil_info'], $row['current_level']), $row['m_hero_pk'], $row['position_type'].':'.$row['position'], $row['type']];
    }
    return $Render->nsXhrReturn('success', null, $response);
}));