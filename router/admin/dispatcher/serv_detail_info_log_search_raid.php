<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_search_raid', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    function getRaidType($str)
    {
        $RaidType = [
            'discovery' => '요새 발견',
            'set_point' => '공격 보너스',
            'clear' => '섬멸 완료',
            'request' => '도움요청',
            'request_item' => '아이템지원'
        ];

        return $RaidType[$str] ?? $str;
    }

    function getDiscoveryType($str)
    {
        $DiscoveryType = [
            'level_up' => '황건적 요새 섬멸',
            'encounter' => '탐색',
            'suppress' => '황건적 토벌령',
            'territory_npc' => '황건적 성'
        ];

        return $DiscoveryType[$str] ?? $str;
    }

    function getRewardType($str)
    {
        $RewardType = [
            'discovery' => '발견자',
            'annihiler' => '섬멸자',
            'helper1' => '기여도1',
            'helper2' => '기여도2'
        ];

        return $RewardType[$str] ?? $str;
    }

    $g_lord_name_array = [];
    function getLordName($lord_pk, $Db)
    {
        global $g_lord_name_array;
        if (!$g_lord_name_array[$lord_pk]) {
            $Db->query('SELECT lord_name FROM lord WHERE lord_pk = $1', array($lord_pk));
            $g_lord_name_array[$lord_pk] = $Db->fetchOne();
        }
        return $g_lord_name_array[$lord_pk];
    }

    function getDescription($_type, $_desc, $Db)
    {
        global $_M_ITEM;

        $_result = '';
        if ($_type == 'discovery') {
            $descArr = json_decode($_desc, true);

            $_result .= '요새번호 : ' . $descArr['raid_troo_pk'] . ', ';
            $_result .= '확률값 : ' . $descArr['rate'] . '%, ';
            $_result .= '결정값 : ' . $descArr['random_key'] . ', ';
            $_result .= '요새레벨 : ' . $descArr['target_level'] . ', ';
            $_result .= '발견방법 : ' . getDiscoveryType($descArr['discovery_type']);
        } else if ($_type == 'set_point') {
            $descArr = json_decode($_desc, true);

            $_result .= '요새번호 : ' . $descArr['raid_troo_pk'] . ', ';
            $_result .= '획득점수 : ' . number_format($descArr['point']) . ', ';
            $_result .= '섬멸여부 : ' . ($descArr['att_success'] ? '성공' : '실패');
        } else if ($_type == 'clear') {
            $descArr = json_decode($_desc, true);

            $_result .= '요새번호 : ' . $descArr['raid_troo_pk'] . ', ';
            $_result .= '요새레벨 : ' . $descArr['target_level'] . '<br /><br />';

            $_result .= '◆ 보상 군주<br />' . $descArr['reward_lord_pk'];
            foreach ($descArr['reward_lord_pk_arr'] as $k => $v) {
                if ($v)
                    $_result .= getRewardType($k) . ' : ' . $v['lord_name'] . ' (' . $v['lord_pk'] . ')<br />';
            }
        } else if ($_type == 'request') {
            $descArr = json_decode($_desc, true);

            $_result .= '요새번호 : ' . $descArr['raid_troo_pk'] . ', ';
            $_result .= '보낸군주 : ' . getLordName($descArr['from_lord_pk'], $Db) . ', ';
            $_result .= '받는군주 : ' . getLordName($descArr['to_lord_pk'], $Db) . ', ';
            $_result .= '타입 : ' . $descArr['type'];
        } else if ($_type == 'request_item') {
            $descArr = json_decode($_desc, true);

            $_result .= '요새번호 : ' . $descArr['raid_troo_pk'] . ', ';
            $_result .= '보낸군주 : ' . getLordName($descArr['from_lord_pk'], $Db) . ', ';
            $_result .= '받는군주 : ' . getLordName($descArr['to_lord_pk'], $Db) . ', ';
            $_result .= '아이템 : ' . $_M_ITEM[$descArr['m_item_pk']]['title'] . ', ';
            $_result .= '갯수 : ' . $descArr['item_cnt'] . '개';
        } else {
            $_result = $_desc;
        }

        return $_result;
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
        }
        else {
            return $Render->view(json_encode(Array('result'=> 'fail', 'msg'=> 'don`t search lord_name')));
        }
    }

    if (isset($params['search_type'])) {
        $where .= (($where_cnt > 1) ? ' AND ' : ' WHERE ');
        $where .= "type IN ('" . str_replace(',', "','", $params['search_type']) . "')";
    }

    $total_count_sql = "SELECT count(log_date) FROM log_raid_battle{$where}";
    $PgLog->query($total_count_sql, $query_params);

    $count = $PgLog->fetchOne();
    $total_page = 1;
    $page = 1;

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    $sql = "SELECT date_part('epoch', log_date)::integer as log_date, lord_pk, posi_pk, type, description FROM log_raid_battle{$where} order by log_date desc limit $limit offset {$offset}";

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
        $response->rows[$i]['cell'] = [date('Y-m-d H:i:s', $PgLog->row['log_date']), $PgLog->row['lord_pk'], $PgLog->row['type'], getDescription($PgLog->row['type'], $PgLog->row['description'], $PgGame)];
        $i++;
    }

    foreach ($response->rows as &$v) {
        $v['cell'][1] = getLordName($v['cell'][1], $PgGame);
        $v['cell'][2] = getRaidType($v['cell'][2], $PgGame);
    }

    return $Render->view(json_encode($response));
}));
