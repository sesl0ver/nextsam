<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_search_point', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    function getPointType($str)
    {
        $PointType = [
            'reward_skill' => '스킬 획득',
            'npc_bonus' => 'NPC 보너스',
            'reward_reso' => '자원 획득',
            'reward_item' => '아이템 획득',
            'point_loss' => '요충지 상실',
            'occu_bonus' => '점령 보너스 ',
            'occu_point' => '점령 포인트',
            'npc_kill' => 'NPC Kill',
            'reward_army' => '병력 획득',
            'point_acquired' => '요충지 점령',
            'reward_hero' => '영웅 획득',
            'battle_defence' => '방어 전투',
            'battle_attack' => '원정 전투'
        ];

        return $PointType[$str] ?? $str;
    }

    function getDescription($_type, $_desc)
    {
        if ($_type == 'reward_skill') {
            global $_M_ITEM;

            if (str_starts_with($_desc, 'item:')) {
                $result = preg_replace('/^item:\[([0-9]*)\].*/', '\\1', $_desc);
                $result = $_M_ITEM[$result]['title'];
                $count = preg_replace('/^item:\[[0-9]*\];count:\[([0-9]*)\]\;/', '\\1', $_desc);
            }

            $_reuslt = $result . ' ' . $count . '개';
        } else if ($_type == 'npc_bonus') {
            $_desc = str_replace('Y', '보너스 포인트 획득', $_desc);
            $_reuslt = $_desc;
        } else if ($_type == 'reward_reso' || $_type == 'reward_item') {
            global $_M_ITEM;

            if (preg_match('/^item:/', $_desc)) {
                $result = preg_replace('/^item:\[([0-9]*)\].*/', '\\1', $_desc);
                $result = $_M_ITEM[$result]['title'];
                $count = preg_replace('/^item:\[[0-9]*\];count:\[([0-9]*)\]\;/', '\\1', $_desc);
            }

            $_reuslt = $result . ' ' . $count . '개';
        } else if ($_type == 'point_loss') {
            $_reuslt = $_desc;
        } else if ($_type == 'occu_bonus' || $_type == 'occu_point') {
            $_desc = str_replace('prev', '이전', $_desc);
            $_desc = str_replace('change', '변경', $_desc);
            $_desc = str_replace('after', '이후', $_desc);

            $_reuslt = $_desc;
        } else if ($_type == 'npc_kill') {
            $_desc = str_replace('prev', '이전', $_desc);
            $_desc = str_replace('bonus_point', '보너스 포인트', $_desc);
            $_desc = str_replace('after', '이후', $_desc);
            $_reuslt = $_desc;
        } else if ($_type == 'reward_army') {
            global $_M_ITEM;

            if (str_starts_with($_desc, 'item:')) {
                $result = preg_replace('/^item:\[([0-9]*)\].*/', '\\1', $_desc);
                $result = $_M_ITEM[$result]['title'];
                $count = preg_replace('/^item:\[[0-9]*\];count:\[([0-9]*)\]\;/', '\\1', $_desc);
            }

            $_reuslt = $result . ' ' . $count . '개';
        } else if ($_type == 'point_acquired') {
            $_reuslt = $_desc;
        } else if ($_type == 'reward_hero') {
            global $_M_HERO, $_M_HERO_BASE;

            if (str_starts_with($_desc, 'hero_pk:')) {
                $_hero_pk = preg_replace('/^hero_pk:\[([0-9]*)\];m_hero_pk:\[[0-9]*\]\;/', '\\1', $_desc);

                $_m_hero_pk = preg_replace('/^hero_pk:\[[0-9]*\];m_hero_pk:\[([0-9]*)\]\;/', '\\1', $_desc);
                $_m_hero = 'Lv.' . $_M_HERO[$_m_hero_pk]['level'] . ' ' . $_M_HERO_BASE[$_M_HERO[$_m_hero_pk]['m_hero_base_pk']]['name'];
            }

            $_reuslt = $_m_hero . ' (' . $_hero_pk . ')';
        } else {
            $_reuslt = $_desc;
        }

        return $_reuslt;
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['item', 'hero', 'hero_base']);

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

    $total_count_sql = "SELECT count(log_date) FROM log_point{$where}";
    $PgLog->query($total_count_sql, $query_params);

    $count = $PgLog->fetchOne();
    $total_page = 1;
    $page = 1;

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    $sql = "SELECT date_part('epoch', log_date)::integer as log_date, lord_pk, point_posi_pk, posi_pk, type, description FROM log_point{$where} order by log_date desc limit $limit offset {$offset}";

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
        $response->rows[$i]['cell'] = [date('Y-m-d H:i:s', $PgLog->row['log_date']), $PgLog->row['point_posi_pk'], $PgLog->row['lord_pk'], $PgLog->row['posi_pk'], getPointType($PgLog->row['type']), getDescription($PgLog->row['type'], $PgLog->row['description'])];
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
