<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/gm_log', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['item', 'hero_skill']);

    $page = $params['page']; // get the requested page
    $limit = $params['rows']; // get how many rows we want to have into the grid

    $PgGm = new Pg('GM');
    $Gm = new Gm();

    $PgGm->query('SELECT COUNT(log_pk) FROM gm_log');

    $count = $PgGm->fetchOne();
    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;

    $offset_start = ($offset_start < 0) ? 0 : $offset_start;
    $PgGm->query('SELECT log_pk, date_part(\'epoch\', regist_dt)::integer as regist_dt, type, gm_id, description FROM gm_log ORDER BY log_pk DESC LIMIT $1 OFFSET $2', [$limit, $offset_start]);

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    $i = 0;
    while ($PgGm->fetch()) {
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $PgGm->row['log_pk'];
        $response->rows[$i]['cell'] = [date('Y-m-d일 H:i:s', $PgGm->row['regist_dt']), $PgGm->row['gm_id'], $Gm->gmLogDescription($PgGm->row['description'])];
        $i++;
    }

    return $Render->view(json_encode($response));
}));