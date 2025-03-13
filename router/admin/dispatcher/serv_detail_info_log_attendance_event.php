<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_attendance_event', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    function getDescription($desc)
    {
        global $_M_ITEM;

        $item = preg_replace('/^attendance:[0-9];m_item_pk:([0-9]*)$/i', '\\1', $desc);

        $desc = preg_replace('/^attendance:([0-9]);/i', '출석횟수: \\1회, ', $desc);
        $desc = preg_replace('/m_item_pk:[0-9]*/i', '지급아이템: ', $desc);
        $desc .= $_M_ITEM[$item]['title'];

        return $desc;
    }

    global $NsGlobal;
    $NsGlobal->requireMasterData(['item']);

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

    $where .= (($where !=='') ? ' AND ' : ' WHERE ');
    $where .= "type IN ('event')";

    $total_count_sql = "SELECT count(log_date) FROM log_etc{$where}";
    $PgLog->query($total_count_sql, $query_params);

    $count = $PgLog->fetchOne();
    $total_page = 1;
    $page = 1;

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    $sql = "SELECT date_part('epoch', log_date)::integer log_date, lord_pk, description FROM log_etc{$where} order by log_date desc limit $limit offset {$offset}";

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
        $response->rows[$i]['cell'] = array(date('Y-m-d H:i:s', $PgLog->row['log_date']), $PgLog->row['lord_pk'], $PgLog->row['lord_pk'], $PgLog->row['description']);
        $i++;
    }

    $g_lord_name_array = [];

    foreach ($response->rows as &$v)
    {
        if (!isset($g_lord_name_array[$v['cell'][2]]))
        {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$v['cell'][2]]);
            $g_lord_name_array[$v['cell'][2]] = $PgGame->fetchOne();
        }
        $v['cell'][2] = $g_lord_name_array[$v['cell'][2]];
        $v['cell'][3] = getDescription($v['cell'][3]);
    }

    return $Render->view(json_encode($response));
}));