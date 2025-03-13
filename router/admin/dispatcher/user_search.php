<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/user_search', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $page = $params['page']; // get the requested page
    $limit = $params['rows']; // get how many rows we want to have into the grid

    $Gm = new Gm();
    $Gm->selectPgGame($params['target_server_pk']);
    $PgGame = new Pg('SELECT');

    $response = new stdClass();
    $response->page = $page;

    $count = 0;
    if ($params['type'] == 'web_id') {
        $PgGame->query("SELECT COUNT(lord_pk) FROM lord_web WHERE web_id LIKE '%{$params['search']}%' ");
        $count = $PgGame->fetchOne();
    } else if ($params['type'] == 'lord_name') {
        $PgGame->query("SELECT COUNT(lord.lord_pk) FROM lord, lord_web WHERE lord.lord_pk = lord_web.lord_pk AND lower(lord_name_lower) LIKE lower('%{$params['search']}%')");
        $count = $PgGame->fetchOne();
    } else if ($params['type'] == 'lord_pk') {
        $PgGame->query("SELECT COUNT(lord.lord_pk) FROM lord, lord_web WHERE lord.lord_pk = lord_web.lord_pk AND lord.lord_pk = {$params['search']}");
        $count = $PgGame->fetchOne();
    } else if ($params['type'] == 'posi_pk') {
        $PgGame->query('SELECT COUNT(a.posi_pk) FROM position AS a LEFT OUTER JOIN territory AS b ON a.posi_pk = b.posi_pk
    LEFT OUTER JOIN lord AS c ON a.lord_pk = c.lord_pk, lord_web AS d WHERE a.lord_pk = d.lord_pk AND a.posi_pk = $1 LIMIT 1', [$params['search']]);
        $count = $PgGame->fetchOne();
    } else if ($params['type'] == 'territory') {
        $PgGame->query("SELECT COUNT(lord.lord_pk) FROM lord, position, territory, lord_web WHERE position.posi_pk = territory.posi_pk AND
            lord.lord_pk = position.lord_pk AND lord.lord_pk = lord_web.lord_pk AND lower(territory.title_lower) LIKE lower('%{$params['search']}%')");
        $count = $PgGame->fetchOne();
    } else if ($params['type'] == 'udid') {
        $total_count_sql = "SELECT COUNT(lord.lord_pk) FROM lord, lord_web WHERE lord.lord_pk = lord_web.lord_pk AND udid = '{$params['search']}' ";
        $PgGame->query($total_count_sql);
    }

    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;
    $offset_start = ($offset_start < 0) ? 0 : $offset_start;

    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    if ($params['type'] == 'web_id') {
        $PgGame->query("SELECT t1.lord_pk, t2.web_id, t1.udid, t1.lord_name, date_part('epoch', t1.regist_dt)::integer as regist_dt, date_part('epoch', t1.last_login_dt)::integer as last_login_dt, t1.is_logon FROM lord t1, lord_web t2 WHERE t1.lord_pk = t2.lord_pk AND t2.web_id LIKE '%{$params['search']}%'  ORDER BY t1.lord_pk DESC LIMIT $1 OFFSET $2", [$limit, $offset_start]);
    } else if ($params['type'] == 'lord_name') {
        $PgGame->query("SELECT t1.lord_pk, t2.web_id, t1.udid, t1.lord_name, date_part('epoch', t1.regist_dt)::integer as regist_dt, date_part('epoch', t1.last_login_dt)::integer as last_login_dt, t1.is_logon FROM lord t1, lord_web t2 WHERE t1.lord_pk = t2.lord_pk AND lower(t1.lord_name_lower) LIKE lower('%{$params['search']}%') ORDER BY t1.lord_pk DESC LIMIT $1 OFFSET $2", [$limit, $offset_start]);
    } else if ($params['type'] == 'lord_pk') {
        $PgGame->query("SELECT t1.lord_pk, t2.web_id, t1.udid, t1.lord_name, date_part('epoch', t1.regist_dt)::integer as regist_dt, date_part('epoch', t1.last_login_dt)::integer as last_login_dt, t1.is_logon FROM lord t1, lord_web t2 WHERE t1.lord_pk = t2.lord_pk AND t1.lord_pk = {$params['search']}  ORDER BY t1.lord_pk DESC LIMIT $1 OFFSET $2", [$limit, $offset_start]);
    } else if ($params['type'] == 'posi_pk') {
        $posi_pk = $params['search'];
        $PgGame->query('SELECT c.lord_pk, d.web_id, c.udid, c.lord_name, c.is_logon, b.title AS terr_title, a.posi_pk, a.type, a.level FROM lord_web AS d, position AS a
            LEFT OUTER JOIN territory AS b ON a.posi_pk = b.posi_pk LEFT OUTER JOIN lord AS c ON a.lord_pk = c.lord_pk WHERE c.lord_pk = d.lord_pk AND a.posi_pk = $1 LIMIT 1', [$posi_pk]);
    } else if ($params['type'] == 'territory') {
        $PgGame->query("SELECT lord.lord_pk, lord_web.web_id, lord.udid, lord_web.web_channel, lord.lord_name, territory.title AS terr_title, position.posi_pk, lord.is_logon FROM lord, position, territory, lord_web
WHERE position.posi_pk = territory.posi_pk AND lord.lord_pk = position.lord_pk AND lord.lord_pk = lord_web.lord_pk AND lower(territory.title_lower) LIKE lower('%{$params['search']}%')
ORDER BY lord_pk DESC LIMIT $1 OFFSET $2", [$limit, $offset_start]);
    } else if ($params['type'] == 'udid') {
        $PgGame->query("SELECT t1.lord_pk, t2.web_id, t1.udid, t1.lord_name, date_part('epoch', t1.regist_dt)::integer as regist_dt, date_part('epoch', t1.last_login_dt)::integer as last_login_dt, t1.is_logon FROM lord t1, lord_web t2 WHERE t1.lord_pk = t2.lord_pk AND t1.udid = '{$params['search']}'  ORDER BY t1.lord_pk DESC LIMIT $1 OFFSET $2", [$limit, $offset_start]);
    }

    $i = 0;
    if ($params['type'] == 'posi_pk') {
        $M_POSITION_AREA = ['L' => '저수지', 'D' => '불모지', 'M' => '광산', 'F' => '산림', 'G' => '초원', 'N' => '황건적', 'A' => '평지', 'R' => '농경지', 'E' => '평지', 'T' => '군주영지'];

        while ($PgGame->fetch()) {
            if ($PgGame->row['type'] == 'T') {
                // 군주의 영지인 경우
                $response->rows[$i] = [];
                $response->rows[$i]['id'] = $PgGame->row['lord_pk'];
                $response->rows[$i]['cell'] = [$PgGame->row['lord_pk'], $PgGame->row['web_id'], $PgGame->row['lord_name'], $PgGame->row['terr_title'], $PgGame->row['posi_pk'], (($PgGame->row['is_logon'] == 'Y') ? '로그인 중' : '로그아웃'), $PgGame->row['udid']];
            } else {
                if (isset($PgGame->row['lord_pk'])) {
                    // 군주가 점령한 자원지라면
                    $response->rows[$i] = [];
                    $response->rows[$i]['id'] = $PgGame->row['lord_pk'];
                    $response->rows[$i]['cell'] = [$PgGame->row['lord_pk'], $PgGame->row['web_id'], $PgGame->row['lord_name'], ($M_POSITION_AREA[$PgGame->row['type']] . ' Lv.' . $PgGame->row['level']), $PgGame->row['posi_pk'], (($PgGame->row['is_logon'] == 'Y') ? '로그인 중' : '로그아웃'), $PgGame->row['udid']];
                }
            }
            $i++;
        }
    } else if ($params['type'] == 'territory') {
        while ($PgGame->fetch()) {
            $response->rows[$i] = [];
            $response->rows[$i]['id'] = $PgGame->row['lord_pk'];
            $response->rows[$i]['cell'] = [$PgGame->row['lord_pk'], $PgGame->row['web_id'], $PgGame->row['lord_name'], $PgGame->row['terr_title'], $PgGame->row['posi_pk'], (($PgGame->row['is_logon'] == 'Y') ? '로그인 중' : '로그아웃'), $PgGame->row['udid']];
            $i++;
        }
    } else {
        while ($PgGame->fetch()) {
            $response->rows[$i] = [];
            $response->rows[$i]['id'] = $PgGame->row['lord_pk'];
            $response->rows[$i]['cell'] = [$PgGame->row['lord_pk'], $PgGame->row['web_id'], $PgGame->row['lord_name'], date('Y-m-d H:i:s', $PgGame->row['regist_dt']), date('Y-m-d H:i:s', $PgGame->row['last_login_dt']), (($PgGame->row['is_logon'] == 'Y') ? '로그인 중' : '로그아웃'), $PgGame->row['udid']];
            $i++;
        }
    }

    return $Render->view(json_encode($response));
}));