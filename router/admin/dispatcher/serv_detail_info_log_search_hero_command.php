<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_search_hero_command', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['hero', 'hero_base']);

    $Gm = new Gm();

    $Gm->selectPgGame($params['target_server_pk']);
    $PgGame = new Pg('SELECT');
    $PgLog = new Pg('SELECT_LOG');

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

    if (isset($params['offset']))
    {
        $where .= (($where !== '') ? ' AND ' : ' WHERE ');
        $where .= 'posi_pk = $' . (count($query_params) + 1);
        $query_params[] = $params['offset'];
    }

    if (isset($params['search_type']))
    {
        $where .= (($where !== '') ? ' AND ' : ' WHERE ');
        $where .= "status_cmd IN ('" . str_replace(',', "','", $params['search_type']) . "')";
    }

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    $PgLog->query("SELECT log_date, web_id, lord_pk, posi_pk, type, hero_pk, m_hero_pk, status, status_cmd, cmd_type, description, web_channel, server_index FROM log_hero_command{$where} order by log_date desc limit $limit offset {$offset}", $query_params);
    $PgLog->fetchAll();

    $_lord_name = [];
    $response = [];
    foreach ($PgLog->rows as $row) {
        if (! isset($_lord_name[$row['lord_pk']])) {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$row['lord_pk']]);
            $_lord_name[$row['lord_pk']] =  $PgGame->fetchOne();
        }
        $response[] = [$row['log_date'], $row['web_id'], $_lord_name[$row['lord_pk']], $row['posi_pk'], $row['hero_pk'], $row['m_hero_pk'], $row['status_cmd'], $row['cmd_type'], $row['description'], $row['type']];
    }
    return $Render->nsXhrReturn('success', null, $response);
    //[date('Y-m-d H:i:s', $PgLog->row['log_date']), $PgLog->row['lord_pk'], $PgLog->row['posi_pk'], getLogHeroType($PgLog->row['type']), $PgLog->row['m_hero_pk'], getStatus($PgLog->row['status']), getDescription($PgLog->row['description']), getStatusCmd($PgLog->row['status_cmd']), getStatusCmdType($PgLog->row['cmd_type'])];
}));