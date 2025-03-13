<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_search_medical', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }


    function getLogMediType($str)
    {
        $MediType = [
            'ArmyTreatment' => '병력 치료',
            'InjuryHeroTreatment' => '영웅 치료',
            'InjuryHeroTreatmentFinish' => '영웅 치료 완료'
        ];

        return $MediType[$str] ?? $str;
    }

    function getDescription($str)
    {
        $desc = [
            'W' => '경상',
            'E' => '중상',
            'F' => '치명상',
            ];

        return $desc[$str] ?? str_replace(';', '<br />', $str);
    }


    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['hero', 'hero_base']);

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
            return $Render->view(json_encode(Array('result' => 'fail', 'msg' => 'don`t search lord_name')));
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

    $total_count_sql = "SELECT count(log_date) FROM log_building_medical{$where}";
    $PgLog->query($total_count_sql, $query_params);

    $count = $PgLog->fetchOne();
    $total_page = 1;
    $page = 1;

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    $sql = "SELECT date_part('epoch', log_date)::integer as log_date, web_id, lord_pk, posi_pk, type, hero_pk, gold, description FROM log_building_medical{$where} order by log_date desc limit $limit offset {$offset}";

//echo $sql;
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
        $response->rows[$i]['cell'] = [date('Y-m-d H:i:s', $PgLog->row['log_date']), $PgLog->row['web_id'], $PgLog->row['lord_pk'], $PgLog->row['posi_pk'], getLogMediType($PgLog->row['type']), $PgLog->row['gold'], $PgLog->row['hero_pk'], $PgLog->row['description']];
        $i++;
    }


    $g_lord_name_array = [];


    function getHeroName($value, $Db)
    {
        global $_M_HERO_BASE, $_M_HERO;

        if (!$value) {
            $result = '';
        } else {
            $Db->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', array($value));
            $m_hero_pk = $Db->fetchOne();

            $result = $_M_HERO_BASE[$_M_HERO[$m_hero_pk]['m_hero_base_pk']]['name'] . ' Lv.' . $_M_HERO[$m_hero_pk]['level'];
        }

        return $result;
    }

    foreach ($response->rows as &$v)
    {
        if (!isset($g_lord_name_array[$v['cell'][2]]))
        {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$v['cell'][2]]);
            $g_lord_name_array[$v['cell'][2]] = $PgGame->fetchOne();
        }
        $v['cell'][2] = $g_lord_name_array[$v['cell'][2]];
        if (isset($v['cell'][6])){
            $v['cell'][6] = $_M['HERO_BASE'][$_M['HERO'][$v['cell'][6]]['m_hero_base_pk']]['name'] . ' Lv.' . $_M['HERO'][$v['cell'][6]]['level'];
        }
        else {
            $v['cell'][6] = '';
        }
        $v['cell'][7] = getDescription($v['cell'][7]);
    }

    return $Render->view(json_encode($response));
}));