<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_search_etc', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    function getLogCheatType($str)
    {
        $BuildType = [
            'gm_change_lord_name' => '군주명 변경 (GM툴)',
            'gm_change_flag_name' => '깃발명 변경 (GM툴)',
            'gm_change_terr_name' => '영지명 변경 (GM툴)',
            'loyalty_incr_cheat' => '민심 증가 (치트툴)',
            'loyalty_decr_cheat' => '민심 감소 (치트툴)',
            'incr_population_cheat' => '인구 증가 (치트툴)',
            'decr_population_cheat' => '인구 감소 (치트툴)',
            'timer_reduce_cheat' => '즉시 완료 (치트툴)',
            'hero_stat_cheat' => '영웅 능력치 조정 (치트툴)',
            'occupation_reduce_cheat' => '점령 시작 (치트툴)',
            'occupation_end_cheat' => '점령 선포 종료 (치트툴)'
        ];

        return $BuildType[$str] ?? $str;
    }

    function getDescription($str)
    {
        $str = str_replace('prev_stat_leadership_basic', '이전 통솔', $str);
        $str = str_replace('prev_stat_mil_force_basic', '이전 무력', $str);
        $str = str_replace('prev_stat_intellect_basic', '이전 지력', $str);
        $str = str_replace('prev_stat_politics_basic', '이전 정치', $str);
        $str = str_replace('prev_stat_charm_basic', '이전 매력', $str);
        $str = str_replace('prev_stat_skill_exp', '이전 경험치', $str);

        $str = str_replace('modify_stat_leadership_basic', '<br />변경 통솔', $str);
        $str = str_replace('modify_stat_mil_force_basic', '변경 무력', $str);
        $str = str_replace('modify_stat_intellect_basic', '변경 지력', $str);
        $str = str_replace('modify_stat_politics_basic', '변경 정치', $str);
        $str = str_replace('modify_stat_charm_basic', '변경 매력', $str);


        $str = str_replace('posi_pk', '좌표', $str);
        $str = str_replace('status', '상태', $str);
        $str = str_replace('in_cast_pk', '내성', $str);
        $str = str_replace('out_cast_pk', '외성', $str);
        $str = str_replace('build_time', '건설시간', $str);

        return $str;
    }

    $PgLog = new Pg('LOG');
    $Gm = new Gm();

    $Gm->selectPgGame($params['target_server_pk']);
    $PgGame = new Pg('SELECT');

    $where_cnt = 1;

    $where = '';
    $query_params = Array();

    if (isset($params['search_start']) && isset($params['search_end'])) {
        $where .= " WHERE {$params['search_start']} <= date_part('epoch', log_date) AND {$params['search_end']} >= date_part('epoch', log_date)";
    }

    if (isset($params['lord_name']))
    {
        $PgGame->query("SELECT lord_pk FROM lord WHERE lord_name = $1::text", Array($params['lord_name']));
        $PgGame->fetch();
        $lord_pk = $PgGame->row['lord_pk'];


        if ($lord_pk && $lord_pk > 0)
        {
            $where .= (($where !== '') ? ' AND ' : ' WHERE ');
            $where .= 'lord_pk = $' . $where_cnt;
            $query_params[] = $lord_pk;
            $where_cnt = $where_cnt + 1;
        } else {
            return $Render->view(json_encode(Array('result'=> 'fail', 'msg'=> 'don`t search lord_name')));
        }
    }

    if (isset($params['offset']))
    {
        $where .= (($where !== '') ? ' AND ' : ' WHERE ');
        $where .= 'posi_pk = $' . $where_cnt;
        $query_params[] = $params['offset'];
        $where_cnt = $where_cnt + 1;
    }

    if (isset($params['search_type'])) {
        $where .= (($where_cnt > 1) ? ' AND ' : ' WHERE ');
        $where .= "type IN ('" . str_replace(',', "','", $params['search_type']) . "')";
    }

    $total_count_sql = "SELECT count(log_date) FROM log_etc{$where}";
    $PgLog->query($total_count_sql, $query_params);

    $count = $PgLog->fetchOne();
    $total_page = 1;
    $page = 1;

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    $sql = "SELECT date_part('epoch', log_date)::integer as log_date, lord_pk, posi_pk, type, description FROM log_etc{$where} order by log_date desc limit $limit offset {$offset}";

//echo $sql;
    $PgLog->query($sql, $query_params);

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = array();

    $i = 0;
    while ($PgLog->fetch()) {
        $response->rows[$i] = array();
        $response->rows[$i]['id'] = $PgLog->row['log_date'];
        $response->rows[$i]['cell'] = array(date('Y-m-d H:i:s', $PgLog->row['log_date']), $PgLog->row['lord_pk'], $PgLog->row['posi_pk'], getLogCheatType($PgLog->row['type']), getDescription($PgLog->row['description']));
        $i++;
    }

    $g_lord_name_array = array();

    foreach ($response->rows as &$v)
    {
        if (!isset($g_lord_name_array[$v['cell'][1]]))
        {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$v['cell'][1]]);
            $g_lord_name_array[$v['cell'][1]] = $PgGame->fetchOne();
        }
        $v['cell'][1] = $g_lord_name_array[$v['cell'][1]];
    }

    return $Render->view(json_encode($response));
}));
