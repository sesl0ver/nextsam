<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/counsel_customer', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $page = $params['page']; // get the requested page
    $limit = $params['rows'];

    $PgGm = new Pg('GM');
    $PgCommon = new Pg('COMMON');

    $PgGm->query('SELECT server_pk, server_name FROM server');
    $PgGm->fetchAll();
    $server_list = [];
    foreach ($PgGm->rows as $row) {
        $server_list[$row['server_pk']] = $row['server_name'];
    }

    $query_params = [];
    $total_count_sql = "SELECT coun_pk, acco_pk, subject, email, is_conclude, date_part('epoch', send_dt)::integer as send_dt FROM counsel WHERE coun_pk > 0";
    if (isset($params['list_type'])) {
        $query_params[] = $params['list_type'];
        $total_count_sql.= ' AND is_conclude = $'.count($query_params);
    }
    if (isset($params['list_type2'])) {
        $query_params[] = $params['list_type2'];
        $total_count_sql.= ' AND type = $'.count($query_params);
    }
    if (isset($params['search_keyword'])) {
        $query_params[] = '%'.$params['search_keyword'].'%';
        $total_count_sql.= ' AND subject like $'.count($query_params).' OR content like $'.count($query_params);
    }
    $PgCommon->query($total_count_sql, $query_params);
    $count = $PgCommon->fetchAll();

    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    $i = 0;
    foreach ($PgCommon->rows as $row) {
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $row['coun_pk'];
        $response->rows[$i]['cell'] = [$row['coun_pk'], $row['acco_pk'], $row['subject'], $row['email'], $row['acco_pk'], $row['is_conclude'], date('Y-m-d H:i:s', $row['send_dt'])];
        $i++;
    }

    return $Render->view(json_encode($response));
}));