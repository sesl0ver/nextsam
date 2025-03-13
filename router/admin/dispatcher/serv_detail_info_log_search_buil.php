<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_search_buil', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    function getLogBuildType ($str): string
    {
        $BuildType = [
            'upgrade' => '건물 건설/업그레이드',
            'complete' => '건물 건설/업그레이드 완료',
            'demolish' => '건물 다운그레이드',
            'demolish_complete' => '건물 다운그레이드 완료',
            'dynamite' => '건물 철거',
            'cancel' => '건물 건설/업그레이드 취소',
            'build_cancel_D' => '건물 다운그레이드 취소'
        ];
        return $BuildType[$str] ?? $str;
    }

    function getBuilLevel($buil_info, $current_level): string
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

    function getBuilPosition($position_type, $position): string
    {
        $posi_type = ($position_type == "I") ? "내성" : "외성";
        return $posi_type." ".$position;
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['hero', 'hero_base', 'building']);

    $PgLog = new Pg('LOG');
    $Gm = new Gm();

    $Gm->selectPgGame($params['target_server_pk']);
    $PgGame = new Pg('SELECT');

    $where_cnt = 1;

    $where = '';
    $query_params = [];

    if (isset($params['search_start']) && isset($params['search_end'])) {
        $where .= " WHERE {$params['search_start']} <= date_part('epoch', log_date) AND {$params['search_end']} >= date_part('epoch', log_date)";
    }

    if (isset($params['lord_name'])) {
        $PgGame->query("SELECT lord_pk FROM lord WHERE lord_name = $1::text", [$params['lord_name']]);
        $PgGame->fetch();
        $lord_pk = $PgGame->row['lord_pk'];

        if ($lord_pk && $lord_pk > 0) {
            $where .= ($where !== '') ? ' AND ' : ' WHERE ';
            $where .= 'lord_pk = $' . $where_cnt;
            $query_params[] = $lord_pk;
            $where_cnt = $where_cnt + 1;
        } else {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => 'don`t search lord_name']));
        }
    }

    if (isset($params['offset'])) {
        $where .= ($where !== '') ? ' AND ' : ' WHERE ';
        $where .= 'posi_pk = $' . $where_cnt;
        $query_params[] = $params['offset'];
        $where_cnt = $where_cnt + 1;
    }

    if (isset($params['search_type'])) {
        $where .= ($where !== '') ? ' AND ' : ' WHERE ';
        $where .= "type IN ('" . str_replace(',', "','", $params['search_type']) . "')";
    }

    $total_count_sql = "SELECT count(log_date) FROM log_construction{$where}";
    $PgLog->query($total_count_sql, $query_params);

    $count = $PgLog->fetchOne();
    $total_page = 1;
    $page = 1;

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    $sql = "SELECT date_part('epoch', log_date)::integer as log_date, web_id, lord_pk, posi_pk, m_buil_pk, type, m_hero_pk, date_part('epoch', start_dt)::integer as start_dt, buil_info, web_channel, server_index, current_level, position_type, position FROM log_construction{$where} order by log_date desc limit $limit offset {$offset}";

    $PgLog->query($sql, $query_params);

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    $i = 0;
    while ($PgLog->fetch()) {
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $PgLog->row['log_date'];
        $response->rows[$i]['cell'] = [date('Y-m-d H:i:s', $PgLog->row['log_date']), $PgLog->row['web_id'], $PgLog->row['lord_pk'], $PgLog->row['posi_pk'], $PgLog->row['m_buil_pk'], $PgLog->row['m_hero_pk'],  getBuilLevel($PgLog->row['buil_info'], $PgLog->row['current_level']), getBuilPosition($PgLog->row['position_type'], $PgLog->row['position']), getLogBuildType($PgLog->row['type'])];
        $i++;
    }

    $g_lord_name_array = [];
    foreach($response->rows as &$v) {
        if (! isset($g_lord_name_array[$v['cell'][2]])) {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$v['cell'][2]]);
            $g_lord_name_array[$v['cell'][2]] = $PgGame->fetchOne();
        }
        $v['cell'][2] = $g_lord_name_array[$v['cell'][2]];
        if ($v['cell'][4] > 0) {
            $v['cell'][4] = $_M['BUIL'][$v['cell'][4]]['title'];
        }
        if ($v['cell'][5] > 0) {
            $v['cell'][5] = $_M['HERO_BASE'][$_M['HERO'][$v['cell'][5]]['m_hero_base_pk']]['name'] . ' Lv.' . $_M['HERO'][$v['cell'][5]]['level'];
        }
    }

    if(isset($params['sql_to_xls'])) {
        return $Render->view(json_encode($response->rows));
    } else {
        return $Render->view(json_encode($response));
    }
}));