<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_search_temp', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    function getLogTempType($str)
    {
        $TempType = array(
            'FightingSpirit' => '전투 사기'
        );

        return $TempType[$str] ?? $str;
    }

    function getHeroInfo($str, $Db)
    {
        global $_M_HERO_BASE, $_M_HERO;

        $hero_arr = array('captain' => '주장', 'director' => '부장', 'staff' => '참모');
        $obj = (array)json_decode($str);
        $cnt = 0;
        $result = '';
        foreach ($hero_arr as $k => $v) {
            if (isset($obj[$k . '_hero_pk'])) {
                if (!isset($obj[$k . '_desc'])) {
                    $Db->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', array($obj[$k . '_hero_pk']));
                    $m_hero_pk = $Db->fetchOne();

                    $hero = $_M_HERO_BASE[$_M_HERO[$m_hero_pk]['m_hero_base_pk']]['name'] . ' (Lv.' . $_M_HERO[$m_hero_pk]['level'] . ')';

                    if ($cnt > 0) {
                        $result .= ' / ';
                    }

                    $result .= $v . ' : ' . $hero;
                } else {
                    if ($cnt > 0) {
                        $result .= ' / ';
                    }
                    $result .= $v . ' : ' . $obj[$k . '_desc'];
                }
                $cnt++;
            }
        }
        return $result;
    }

    function getArmyInfo($str)
    {
        global $_M_ARMY, $_M_ARMY_C;
        $result_str = '';
        $army_arr = (array)json_decode($str);
        $count = 0;
        foreach ($army_arr as $k => $v) {
            if ($v > 0) {
                if ($count > 0) {
                    $result_str .= ' , ';
                }
                $count++;
                $codeset = $k;
                if (substr($codeset, 0, 5) == 'army_') {
                    $codeset = substr($codeset, 5);
                }
                $result_str .= $_M_ARMY_C[$codeset]['title'] . ' ' . $v;
            }
        }
        return $result_str;
    }

    function getResourceInfo($str)
    {
        $type_arr = array(
            'food' => '식량',
            'horse' => '우마',
            'lumber' => '목재',
            'iron' => '철강',
            'round_food' => '왕복 소요 식량',
            'round_gold' => '왕복 소요 황금',
            'presence_food' => '주둔 소요 식량',
            'hour_food' => '시간당 식량 소모량'
        );
        $obj = (array)json_decode($str);
        $result = '';
        $cnt = 0;
        foreach ($obj as $k => $v) {
            if (isset($type_arr[$k])) {
                if ($cnt > 0) {
                    $result .= ' , ';
                }
                $result .= $type_arr[$k] . ' : ' . $v;
                $cnt++;
            }
        }
        return $result;
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['hero', 'hero_base', 'army']);

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

    $total_count_sql = "SELECT count(log_date) FROM log_temp{$where}";
    $PgLog->query($total_count_sql, $query_params);

    $count = $PgLog->fetchOne();
    $total_page = 1;
    $page = 1;

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    $sql = "SELECT date_part('epoch', log_date)::integer as log_date, lord_pk, posi_pk, type, col1, col2, col3, col4, col5, col6, col7, col8, col9, col10, col11 FROM log_temp{$where} order by log_date desc limit $limit offset {$offset}";

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
        $response->rows[$i]['cell'] = array(date('Y-m-d H:i:s', $PgLog->row['log_date']), $PgLog->row['lord_pk'], $PgLog->row['posi_pk'], getLogTempType($PgLog->row['type']), $PgLog->row['col1'], $PgLog->row['col2'], $PgLog->row['col3'], $PgLog->row['col4'], $PgLog->row['col5'], $PgLog->row['col6'], ($PgLog->row['col2'] + $PgLog->row['col7']), $PgLog->row['col8'], $PgLog->row['col9'], $PgLog->row['col10'], $PgLog->row['col11']);
        $i++;
    }

    $g_lord_name_array = [];

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
