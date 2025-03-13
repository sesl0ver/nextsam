<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_detail_info_integrate_notice', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $page = $params['page']; // get the requested page
    $limit = $params['rows']; // get how many rows we want to have into the grid

    $PgCommon = new Pg('COMMON');

    $PgCommon->query('SELECT COUNT(noti_pk) FROM notice WHERE noti_end_dt > now()');

    $count = $PgCommon->fetchOne();
    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;

    $offset_start = ($offset_start < 0) ? 0 : $offset_start;
    $PgCommon->query('SELECT noti_pk, title, content, noti_type, date_part(\'epoch\', noti_start_dt)::integer as noti_start_dt, date_part(\'epoch\', noti_end_dt)::integer as noti_end_dt,
ordernum, date_part(\'epoch\', regist_dt)::integer as regist_dt, date_part(\'epoch\', last_up_dt)::integer as last_up_dt FROM notice WHERE noti_end_dt > now() ORDER BY ordernum, noti_start_dt, regist_dt LIMIT $1 OFFSET $2', [$limit, $offset_start]);

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    $i = 0;
    while ($PgCommon->fetch()) {
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $PgCommon->row['noti_pk'];
        $response->rows[$i]['cell'] = [
            $PgCommon->row['noti_pk'], $PgCommon->row['title'], $PgCommon->row['content'], $PgCommon->row['noti_type'],
            date('Y-m-d H:i:s', $PgCommon->row['noti_start_dt']), date('Y-m-d H:i:s', $PgCommon->row['noti_end_dt']),
            $PgCommon->row['ordernum']
        ];
        $i++;
    }

    return $Render->view(json_encode($response));
}));

$app->post('/admin/gm/api/checkNoticeCache', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $PgCommon = new Pg('COMMON');

    $PgCommon->query('SELECT server_pk, display_name, memcached_ip, memcached_port FROM server');
    $PgCommon->fetchAll();

    $rows = [];
    foreach ($PgCommon->rows as $row) {
        define('COMMON_'. $row['server_pk']. '_MEMCACHED_IP', $row['memcached_ip']);
        define('COMMON_'. $row['server_pk']. '_MEMCACHED_PORT', $row['memcached_port']);
        define('COMMON_'. $row['server_pk']. '_MEMCACHED_PERSISTENT', false);
        $Cache = new Cache('COMMON_'. $row['server_pk']);
        $is_cached = $Cache->get('__NOTICE_INFO__') ? 'Yes' : 'No';

        $rows[$row['server_pk']]['server_pk'] = $row['server_pk'];
        $rows[$row['server_pk']]['display_name'] = $row['display_name'];
        $rows[$row['server_pk']]['memcached_ip'] = $row['memcached_ip'];
        $rows[$row['server_pk']]['memcached_port'] = $row['memcached_port'];
        $rows[$row['server_pk']]['is_cached'] = $is_cached;
    }

    return $Render->view(json_encode(['result' => 'ok', 'rows' => $rows]));
}));