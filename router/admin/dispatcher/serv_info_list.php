<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_info_list', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $page = $params['page']; // get the requested page

    $PgGm = new Pg('GM');

    $PgGm->query('SELECT server_pk, server_name, db_ip, db_port, memcached_server_ip, memcached_server_port, chat_server_ip, chat_server_start_port FROM server');
    $PgGm->fetchAll();
    $server_list = $PgGm->rows;

    $count = count($server_list);
    $total_page = 1;
    $page = ($page > $total_page) ? $total_page : $page;

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    $i = 0;
    foreach ($server_list as $row) {
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $row['server_pk'];
        $response->rows[$i]['cell'] = [$row['server_pk'], $row['server_name'], $row['db_ip'], $row['db_port'], $row['memcached_server_ip'], $row['memcached_server_port'], $row['chat_server_ip'], $row['chat_server_start_port']];
        $i++;
    }

    return $Render->view(json_encode($response));
}));