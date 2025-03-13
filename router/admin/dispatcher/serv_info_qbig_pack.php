<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_info_qbig_pack', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $LOG_Db = new Pg('LOG');
    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $Db = new Pg('SELECT');

    $where = '';
    $where_cnt = 1;
    $where_params = [];
    $g_lord_name_array = [];

    $limit = 500;

    if (!isset($params['action']) || $params['action'] == 'search_lord_name') {
        $Db->query('SELECT lord_pk FROM lord WHERE lord_name = $1', [$params['search']]);

        if (!$Db->fetch()) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '존재하지 않는 군주명입니다.']));
        }

        $lord_pk = $Db->row['lord_pk'];

        $total_count_sql = "SELECT COUNT(lord_pk) FROM qbig_pack WHERE lord_pk = $1";
        $Db->query($total_count_sql, [$lord_pk]);

        $count = $Db->fetchOne();
        $total_page = 1;
        $page = 1;
        $offset_start = ($params['list_offset'] - 1 ) * $limit;

        $list_sql = "SELECT qbi_pac_pk, lord_pk, store_type, pack_type, buy_qbig, date_part('epoch', buy_dt)::integer as buy_dt, bill_chargeno, yn_refund FROM qbig_pack WHERE lord_pk = $3 ORDER BY buy_dt DESC LIMIT $1 OFFSET $2";
        $offset_start = ($offset_start < 0) ? 0 : $offset_start;
        $query_params = array($limit, $offset_start, $lord_pk);
        $Db->query($list_sql, $query_params);

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_page;
        $response->records = $count;
        $response->rows = array();

        $i = 0;
        while ($Db->fetch())
        {
            $refund = '-';
            if ($Db->row['yn_refund'] == 'Y')
                $refund = '환불됨';

            $response->rows[$i] = array();
            $response->rows[$i]['id'] = $Db->row['qbi_pac_pk'];
            $response->rows[$i]['cell'] = array
            (
                $_SESSION['selected_server']['server_name'],
                $params['search'],
                $Db->row['store_type'],
                $Db->row['pack_type'],
                $Db->row['buy_qbig'],
                date('Y-m-d H:i:s', $Db->row['buy_dt']),
                $Db->row['bill_chargeno'],
                $refund
            );
            $i++;
        }

        return $Render->view(json_encode($response));
    }
    else if ($params['action'] == 'search_bill_chargeno')
    {
        $lord_pk = false;
        $Db->query('SELECT lord_pk FROM lord WHERE lord_name = $1', [$params['lord_name']]);
        if ($Db->fetch()) {
            $lord_pk = $Db->row['lord_pk'];
        }

        $bill_chargeno = $params['search'];

        $total_count_sql = "SELECT COUNT(lord_pk) FROM qbig_pack WHERE bill_chargeno LIKE '%$1%'";
        $Db->query($total_count_sql, array($bill_chargeno));

        $count = $Db->fetchOne();
        $total_page = 1;
        $page = 1;
        $offset_start = max(0, $limit * $page - $limit);

        $list_sql = "SELECT qbi_pac_pk, lord_pk, store_type, pack_type, buy_qbig, data_part('epoch', buy_dt)::integer as buy_dt, bill_chargeno, yn_refund FROM qbig_pack WHERE bill_chargeno LIKE '%{$bill_chargeno}%'";

        if ($lord_pk)
            $list_sql .= " AND lord_pk = {$lord_pk}";

        $list_sql .= " ORDER BY buy_dt DESC LIMIT $1 OFFSET $2";

        $offset_start = ($offset_start < 0) ? 0 : $offset_start;
        $query_params = array($limit, $offset_start);
        $Db->query($list_sql, $query_params);

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_page;
        $response->records = $count;
        $response->rows = array();

        $i = 0;
        while ($Db->fetch())
        {
            $response->rows[$i] = array();
            $response->rows[$i]['id'] = $Db->row['qbi_pac_pk'];
            $response->rows[$i]['cell'] = array
            (
                $_SESSION['selected_server']['server_name'],
                $Db->row['lord_pk'],
                $Db->row['store_type'],
                $Db->row['pack_type'],
                $Db->row['buy_qbig'],
                date('Y-m-d H:i:s', $Db->row['buy_dt']),
                $Db->row['bill_chargeno'],
                $Db->row['yn_refund']
            );
            $i++;
        }

        foreach ($response->rows as &$v)
        {
            if (!isset($g_lord_name_array[$v['cell'][1]]))
            {
                $Db->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$v['cell'][1]]);
                $g_lord_name_array[$v['cell'][1]] = $Db->fetchOne();
            }
            $v['cell'][1] = getLordName($v['cell'][1], $Db);
        }

        return $Render->view(json_encode($response));
    }
    else if ($params['action'] == 'get_qbig_flow')
    {
        $sql = "SELECT lord_pk, lord_name, 100 cash_regist, cash remain_cash, use_cash FROM lord WHERE lord_name = $1";

        $Db->query($sql, array($params['search']));
        if (!$Db->fetch())
        {
            return $Render->view(json_encode(array('result' => 'fail', 'msg' => '해당 정보를 찾을 수 없습니다.')));
        }
        $qbig_flow = $Db->row;

        // 전체 충전 큐빅
        $sql = "SELECT sum(b.qbig_buy) AS buy_total, sum(b.qbig_bonus) AS bonus FROM m_qbig_pack b, qbig_pack a, lord c WHERE a.store_type = b.store_type AND a.pack_type = b.pack_type AND a.lord_pk = c.lord_pk AND c.lord_name = $1";

        $Db->query($sql, array($params['search']));
        $Db->fetch();

        $qbig_flow['incr_cash'] = $Db->row['buy_total'];
        $qbig_flow['desc_spent'] = $qbig_flow['use_cash'];
        $qbig_flow['bonus_cash'] = $Db->row['bonus'];

        return $Render->view(json_encode(array('info' => $qbig_flow)));
    }
}));
