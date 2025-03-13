<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_log_search_hero_trade', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $g_lord_name_array = [];
    function getLordName($lord_pk, $Db)
    {
        global $g_lord_name_array;
        if (!$g_lord_name_array[$lord_pk]) {
            $Db->query('SELECT lord_name FROM lord WHERE lord_pk = $1', array($lord_pk));
            $g_lord_name_array[$lord_pk] = $Db->fetchOne();
        }
        return (!$g_lord_name_array[$lord_pk]) ? '-' : $g_lord_name_array[$lord_pk];
    }

    function getDescription($desc, $_hero_name, $_sale_period, $_password = null)
    {
        if (!$desc)
            return;

        $descArr = [
            'stat' => [
                'enchant' => '강화',
                'leadership' => '통솔',
                'mil_force' => '무력',
                'intellect' => '지력',
                'politics' => '정치',
                'charm' => '매력'
            ],
            'skil' => [
                'm_hero_skil_pk_1' => '스킬슬롯1',
                'm_hero_skil_pk_2' => '스킬슬롯2',
                'm_hero_skil_pk_3' => '스킬슬롯3',
                'm_hero_skil_pk_4' => '스킬슬롯4',
                'm_hero_skil_pk_5' => '스킬슬롯5',
                'm_hero_skil_pk_6' => '스킬슬롯6'
            ]
        ];

        $arr = explode(';', $desc);

        $result = [];
        $result['stat'] = '';
        $result['skil'] = '';

        global $_M;

        $m_skil_exp = $_M['HERO_SKILL_EXP'];
        ksort($m_skil_exp);

        $m_hero_skill = $_M['HERO_SKILL'];

        foreach ($arr as $v) {
            $t = explode(':', $v);
            if (array_key_exists($t[0], $descArr['stat'])) {
                if (strlen($t[1]) > 0) {
                    $result['stat'] .= $descArr['stat'][$t[0]] . ':' . $t[1] . ', ';
                }
            } else if (array_key_exists($t[0], $descArr['skil'])) {
                if (strlen($t[1]) > 0) {
                    if ($t[0] == 'skill_exp') {
                        $opened_slot_count = 0;
                        foreach ($m_skil_exp as $slot_count => $e) {
                            if ($t[1] < $e['exp']) {
                                $opened_slot_count = $slot_count;
                                break;
                            }
                        }

                        $result['skil'] .= $descArr['skil'][$t[0]] . ':' . $opened_slot_count . '<br />';
                    } else {
                        $result['skil'] .= $descArr['skil'][$t[0]] . ':' . $m_hero_skill[$t[1]]['title'] . ' Lv.' . $m_hero_skill[$t[1]]['rare'] . ' (' . $m_hero_skill[$t[1]]['use_slot_count'] . ')<br />';
                    }
                }
            }
        }

        return $_hero_name . '<br /><br />' . $result['stat'] . '<br/><br/>' . $result['skil'] . '<br/>판매시간 : ' . $_sale_period . '시간<br/>패스워드 : ' . $_password;
    }

    function getLogHeroTradeType($str)
    {
        $HeroTradeType = [
            'init_trade_bid_cnt' => '구매 횟수 초기화',
            'init_hero_bid_count_item' => '구매 횟수 초기화 아이템',
            'buy_now' => '즉시 구매',
            'bid' => '입찰 참여',
            'add_bid' => '추가 입찰',
            'bid_success' => '입찰 성공',
            'bid_failure' => '입찰 실패',
            'incr_gold' => '황금 증가',
            'decr_gold' => '황금 감소',
            'sell_failure' => '판매 실패',
            'sell_hero_regist' => '판매 등록',
            'sell_hero_cancel' => '판매 취소',
            'gm_modify_gold' => 'GM툴 황금 보유량 수정'
        ];

        return $HeroTradeType[$str] ?? $str;
    }

    global $_M, $NsGlobal;
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

    $total_count_sql = "SELECT count(log_date) FROM log_hero_trade{$where}";
    $PgLog->query($total_count_sql, $query_params);

    $count = $PgLog->fetchOne();
    $total_page = 1;
    $page = 1;

    $limit = 500;
    $offset = ($params['list_offset'] - 1) * $limit;

    $sql = "SELECT date_part('epoch', log_date)::integer as log_date, lord_pk, posi_pk, type, hero_pk, hero_name, hero_level, min_value, max_value, gold, commission, hero_trad_pk, hero_trad_bid_pk, sale_lord_pk, sale_period, password, description, web_channel, server_index FROM log_hero_trade{$where} order by log_date desc limit $limit offset {$offset}";

//echo $sql;
    $PgLog->query($sql, $query_params);

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    $i = 0;

    $PgLog->fetchAll();

    $rows = $PgLog->rows;

    if (is_array($rows) && count($rows) > 0)
    {
        foreach ($rows as $row)
        {
            $response->rows[$i] = [];
            $response->rows[$i]['id'] = $row['hero_trad_pk'];
            //$response->rows[$i]['cell'] = Array(date('Y-m-d H:i:s', $PgLog->row['log_date']), $PgLog->row['lord_pk'], $PgLog->row['posi_pk'], getLogEtcType($PgLog->row['type']), $PgLog->row['m_hero_pk'], date('Y-m-d H:i:s', $PgLog->row['start_dt']), date('Y-m-d H:i:s', $PgLog->row['end_dt']), $PgLog->row['description']);
            $response->rows[$i]['cell'] = [
                date('Y-m-d H:i:s', $row['log_date']),
                $row['hero_trad_pk'],
                $row['hero_trad_bid_pk'],
                getLogHeroTradeType($row['type']),
                $row['posi_pk'],
                $row['lord_pk'],
                $row['sale_lord_pk'],
                ($row['type'] == 'sell_hero_regist' || $row['type'] == 'sell_hero_cancel') ? '-' : number_format($row['gold']) . '<br />(수수료:' . number_format($row['commission']) . ')',
                number_format($row['min_value']) . '<br />' . number_format($row['max_value']),
                getDescription($row['description'], ((!$row['hero_name'] || !$row['hero_level']) ? '-' : ($row['hero_name'] . ' Lv.' . $row['hero_level'])) . ' (' . $row['hero_pk'] . ')', $row['sale_period'], $row['password'])
            ];
            $i++;
        }
    }

    //? 같은로직이 있어서, 함수는 별도로 제거하지 않음.
    foreach ($response->rows as &$v) {
        $v['cell'][5] = getLordName($v['cell'][5], $PgGame);
        $v['cell'][6] = getLordName($v['cell'][6], $PgGame);
    }

    return $Render->view(json_encode($response));
}));
