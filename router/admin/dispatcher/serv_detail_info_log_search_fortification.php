<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_search_fortification', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['hero', 'hero_base']);
    $PgLog = new Pg('LOG');
    $Gm = new Gm();

    $Gm->selectPgGame($params['target_server_pk']);
    $PgGame = new Pg('SELECT');

    $where = '';
    $query_params = [];

    if (isset($params['search_start']) && isset($params['search_end'])) {
        $where .= " WHERE {$params['search_start']} <= date_part('epoch', log_date) AND {$params['search_end']} >= date_part('epoch', log_date)";
    }

    if (isset($params['lord_name']))
    {
        $PgGame->query("SELECT lord_pk FROM lord WHERE lord_name = $1::text", [$params['lord_name']]);
        if ($PgGame->fetch()) {
            $lord_pk = $PgGame->row['lord_pk'];
            $where .= (($where !== '') ? ' AND ' : ' WHERE ');
            $where .= 'lord_pk = $' . (count($query_params) + 1);
            $query_params[] = $lord_pk;
        }
    }

    if (isset($params['offset'])) {
        $where .= (($where !== '') ? ' AND ' : ' WHERE ');
        $where .= 'posi_pk = $' . (count($query_params) + 1);
        $query_params[] = $params['offset'];
    }

    if (isset($params['search_type'])) {
        $where .= (($where !== '') ? ' AND ' : ' WHERE ');
        $where .= "type IN ('" . str_replace(',', "','", $params['search_type']) . "')";
    }

    $total_count_sql = "SELECT count(log_date) FROM log_fortification{$where}";
    $PgLog->query($total_count_sql, $query_params);

    $count = $PgLog->fetchOne();
    $total_page = 1;
    $page = 1;

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    $PgLog->query("SELECT date_part('epoch', log_date)::integer as log_date, web_id, lord_pk, posi_pk, type, fort_info, m_fort_pk, build_number FROM log_fortification{$where} order by log_date desc limit $limit offset {$offset}", $query_params);
    $PgLog->fetchAll();

    function getDescription($fort_info, $_type): string
    {
        $result = '';
        if (preg_match("/build\[([0-9])\]/i", $fort_info)) {
            preg_match("/build\[([0-9])\]/i", $fort_info, $matches1);
            $result.= "설치: " . $matches1[1]. " ";
        }
        if (preg_match("/curr\[([0-9])\]/i", $fort_info)) {
            preg_match("/curr\[([0-9])\]/i", $fort_info, $matches1);
            $result.= "이전: " . $matches1[1]. " ";
        }
        if (preg_match("/update\[([0-9])\]/i", $fort_info)) {
            preg_match("/update\[([0-9])\]/i", $fort_info, $matches1);
            $result.= "변동: " . $matches1[1]. " ";
        }
        if (preg_match("/curr\[([0-9])\]/i", $fort_info) && preg_match("/update\[([0-9])\]/i", $fort_info)) {
            preg_match("/curr\[([0-9])\]/i", $fort_info, $matches1);
            preg_match("/update\[([0-9])\]/i", $fort_info, $matches2);
            if (in_array($_type, ['decr_fort_battle', 'desc_fort_disperse'])) {
                $result.= "현재: " . ((INT)$matches1[1] - (INT)$matches2[1]) . " ";
            } else {
                $result.= "현재: " . ((INT)$matches1[1] + (INT)$matches2[1]) . " ";
            }

        }
        return $result;
    }

    $_lord_name = [];
    $response = [];
    foreach ($PgLog->rows as $row) {
        if (! isset($_lord_name[$row['lord_pk']])) {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$row['lord_pk']]);
            $_lord_name[$row['lord_pk']] =  $PgGame->fetchOne();
        }
        $response[] = [$row['log_date'], $row['web_id'], $_lord_name[$row['lord_pk']], $row['posi_pk'], $row['m_fort_pk'], getDescription($row['fort_info'], $row['type']), $row['type']];
    }

    return $Render->nsXhrReturn('success', null, $response);
}));