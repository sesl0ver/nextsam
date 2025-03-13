<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_friend_gift', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $g_mainid_array = [];
    $g_lordname_array = [];
    function getLordName($_acco_pk, $CommonDb, $Db)
    {
        global $g_mainid_array, $g_lordname_array;
        $_main_apps_id = '';
        if (!$g_mainid_array[$_acco_pk]) {
            $CommonDb->query('SELECT main_apps_id FROM account WHERE acco_pk = $1', [$_acco_pk]);
            $g_mainid_array[$_acco_pk] = $CommonDb->fetchOne();
        }
        $_main_apps_id = $g_mainid_array[$_acco_pk];

        if (!$g_lordname_array[$_main_apps_id]) {
            $Db->query('SELECT t2.lord_name FROM lord_web t1, lord t2 WHERE t1.lord_pk=t2.lord_pk and t1.web_id = $1', [$_main_apps_id]);
            $g_lordname_array[$_main_apps_id] = $Db->fetchOne();
        }

        return $g_lordname_array[$_main_apps_id];
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['item']);

    $PgLog = new Pg('LOG');
    $CommonDb = new Pg('COMMON');
    $Gm = new Gm();

    $Gm->selectPgGame($params['target_server_pk']);
    $PgGame = new Pg('SELECT');

    $where_cnt = 1;
    $_search_acco_pk = 0;

    $where = '';
    $query_params = Array();

    if (isset($params['search_start']) && isset($params['search_end'])) {
        $where .= " WHERE {$params['search_start']} <= date_part('epoch', regist_dt) AND {$params['search_end']} >= date_part('epoch', regist_dt)";
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

    if ($_search_acco_pk > 0) {
        if ($params['gift_type'] == 'to') {
            $where .= (($where !== '') ? ' AND ' : ' WHERE ');
            $where .= 'to_acco_pk = $' . $where_cnt;
            $query_params[] = $_search_acco_pk;
        } elseif ($params['gift_type'] == 'from') {
            $where .= (($where !== '') ? ' AND ' : ' WHERE ');
            $where .= 'from_acco_pk = $' . $where_cnt;
            $query_params[] = $_search_acco_pk;
        } else {
            $where .= (($where !== '') ? ' AND ' : ' WHERE ');
            $where .= 'to_acco_pk = $' . $where_cnt . ' OR from_acco_pk = $' . $where_cnt;
            $query_params[] = $_search_acco_pk;
        }
        $where_cnt = $where_cnt + 1;
    }

    if (isset($params['search_type'])) {
        $where .= (($where !== '') ? ' AND ' : ' WHERE ');
        $where .= "m_item_pk IN ('" . str_replace(',', "','", $params['search_type']) . "')";
    }

    $total_count_sql = "SELECT count(gift_pk) FROM log_friend_gift{$where}";
    $PgLog->query($total_count_sql, $query_params);

    $count = $PgLog->fetchOne();
    $total_page = 1;
    $page = 1;

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    $sql = "SELECT date_part('epoch', regist_dt)::integer as regist_dt, from_acco_pk, to_acco_pk, m_item_pk, item_cnt, from_name, description FROM log_friend_gift{$where} order by regist_dt desc limit $limit offset {$offset}";

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
        $response->rows[$i]['id'] = $PgLog->row['regist_dt'];
        $response->rows[$i]['cell'] = array(date('Y-m-d H:i:s', $PgLog->row['regist_dt']), $PgLog->row['from_acco_pk'], $PgLog->row['to_acco_pk'], $PgLog->row['m_item_pk'], $PgLog->row['item_cnt'], $PgLog->row['type'], $PgLog->row['description']);
        $i++;
    }

    foreach ($response->rows as &$v) {
        $v['cell'][1] = getLordName($v['cell'][1], $CommonDb, $PgGame) . '(' . $v['cell'][1] . ')';
        $v['cell'][2] = getLordName($v['cell'][2], $CommonDb, $PgGame) . '(' . $v['cell'][2] . ')';
        $v['cell'][3] = $_M['ITEM'][$v['cell'][3]]['title'];
        if ($v['cell'][5] == 'gift') {
            $v['cell'][5] = '친구선물';
        } elseif ($v['cell'][3] == 'request') {
            $v['cell'][5] = '요청선물';
        }
    }

    return $Render->view(json_encode($response));
}));
