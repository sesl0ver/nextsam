<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_search_salary', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    //require_once_caches(array('army', 'fortification', 'technique', 'building'));


    function getLogSalaryType($str): string
    {
        $SalaryType = [
            'hero_salary' => '급여 지급'
        ];

        return $SalaryType[$str] ?? $str;
    }


    function get_build_descriptioin($type, $m_pk, $qty, $desc): string
    {
        global $_M_TECH, $_M_ARMY, $_M_FORT, $_M_BUIL;

        $type_prefix = substr($type, 0, 5);

        if ($type_prefix == 'army_' || $type_prefix == 'fort_') {
            $str = getLogBuildType($type) . ' : ' . $_M_ARMY[$m_pk]['title'] . ' ' . $qty;
        } else if ($type_prefix == 'tech_') {
            $str = getLogBuildType($type) . ' : ' . $_M_TECH[$m_pk]['title'] . ' Lv.' . $qty;
        } else if ($type_prefix == 'build') {
            $loc = preg_split(':', $desc);
            $loc_where = ($loc[0] == 'I') ? '내성' : '외성';
            $loc_num = $loc[1];
            $str = getLogBuildType($type) . ' : ' . $_M_BUIL[$m_pk]['title'] . ' Lv.' . $qty . ' , 위치 : ' . $loc_where . ' ' . $loc_num;
        } else {
            $str = $desc;
        }
        return $str;
    }

    $g_buil_name_array = array();
    function getBuilName($m_buil_pk, $Db)
    {
        global $g_buil_name_array;
        if (!isset($g_buil_name_array[$m_buil_pk])){
            $Db->query('SELECT title FROM m_building WHERE m_buil_pk = $1', array($m_buil_pk));
            $g_buil_name_array[$m_buil_pk] = $Db->fetchOne();
        }
        return $g_buil_name_array[$m_buil_pk];
    }

    function getBuilLevel($buil_info, $current_level): string
    {
        if (preg_match("/curr\[([0-9])\]/i", $buil_info)) {
            preg_match("/curr\[([0-9])\]/i", $buil_info, $matches1);
            preg_match("/update\[([0-9])\]/i", $buil_info, $matches2);

            $result = "Lv." . $matches1[1] . " → Lv." . $matches2[1];
        } else {
            $result = "Lv." . $current_level;
        }
        return $result;
    }

    function getBuilPosition($position_type, $position): string
    {
        $posi_type = ($position_type == "I") ? "내성" : "외성";

        $result = $posi_type . " " . $position;

        return $result;
    }

    global $NsGlobal;
    $NsGlobal->requireMasterData(['item', 'army', 'fortification', 'building']);

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
        $where .= (($where !== '') ? ' AND ' : ' WHERE ');
        $where .= "type IN ('" . str_replace(',', "','", $params['search_type']) . "')";
        $where_cnt = $where_cnt + 1;
    }

    $total_count_sql = "SELECT count(log_date) FROM log_hero_salary $where ";
    $PgLog->query($total_count_sql, $query_params);

    $count = $PgLog->fetchOne();
    $total_page = 1;
    $page = 1;

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    $sql = "SELECT date_part('epoch', log_date)::integer as log_date, web_id, lord_pk, posi_pk, type, gold FROM log_hero_salary $where  order by log_date desc limit $limit offset $offset";

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
        $response->rows[$i]['cell'] = array(date('Y-m-d H:i:s', $PgLog->row['log_date']), $PgLog->row['web_id'], $PgLog->row['lord_pk'], $PgLog->row['posi_pk'], getLogSalaryType($PgLog->row['type']), $PgLog->row['gold']);
        $i++;
    }


    $g_lord_name_array = [];

    foreach ($response->rows as &$v) {
        if (!isset($g_lord_name_array[$v['cell'][2]])) {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$v['cell'][2]]);
            $g_lord_name_array[$v['cell'][2]] = $PgGame->fetchOne();
        }
        $v['cell'][2] = $g_lord_name_array[$v['cell'][2]];
    }

    return $Render->view(json_encode($response));
}));
