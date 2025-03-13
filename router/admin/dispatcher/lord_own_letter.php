<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/lord_own_letter', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $page = $params['page']; // get the requested page
    $limit = $params['rows'];

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $PgGame->query('SELECT COUNT(lett_pk) FROM letter WHERE to_lord_pk = $1 OR from_lord_pk = $1', [$_SESSION['selected_lord']['lord_pk']]);
    $count = $PgGame->fetchOne();

    if ($count < 1) {
        return $Render->view(json_encode([]));
    }

    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;

    $PgGame->query("SELECT lett_pk, title, type, to_lord_pk, from_lord_pk, date_part('epoch', send_dt)::integer as send_dt, yn_read, yn_to_delete, yn_from_delete
FROM letter WHERE to_lord_pk = $1 OR from_lord_pk = $1 ORDER BY send_dt DESC LIMIT $2 OFFSET $3", [$_SESSION['selected_lord']['lord_pk'], $limit, $offset_start]);
    $PgGame->fetchAll();
    $letter_list = $PgGame->rows;

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    function getTypeStr($_type): string
    {
        return ($_type == 'N') ? '외교서신' : '시스템';
    }


    $i = 0;

    $g_lord_name_array = [];
    foreach ($letter_list as $v)
    {
        if (! isset($g_lord_name_array[$v['to_lord_pk']])) {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$v['to_lord_pk']]);
            $g_lord_name_array[$v['to_lord_pk']] = $PgGame->fetchOne();
        }
        if (! isset($g_lord_name_array[$v['from_lord_pk']])) {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$v['from_lord_pk']]);
            $g_lord_name_array[$v['from_lord_pk']] = $PgGame->fetchOne();
        }
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $v['lett_pk'];
        $response->rows[$i]['cell'] = [$v['lett_pk'], getTypeStr($v['type']), htmlspecialchars($v['title']), $g_lord_name_array[$v['to_lord_pk']], $g_lord_name_array[$v['from_lord_pk']], date('Y-m-d H:i:s', $v['send_dt']), $v['yn_read'], $v['yn_to_delete'], $v['yn_from_delete']];
        $i++;
    }

    return $Render->view(json_encode($response));
}));

$app->post('/admin/gm/api/viewLordLetter', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $PgGame->query('SELECT title, content, to_lord_pk, from_lord_pk FROM letter WHERE lett_pk = $1', [$params['lett_pk']]);

    if (! $PgGame->fetch()) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '선택된 서신이 없습니다.']));
    }

    function getLordName($lord_pk, $PgGame): string
    {
        $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$lord_pk]);
        return $PgGame->fetchOne();
    }

    $letter_info = $PgGame->row;
    $letter_info['to_lord_pk'] = getLordName($letter_info['to_lord_pk'], $PgGame);
    $letter_info['from_lord_pk'] = getLordName($letter_info['from_lord_pk'], $PgGame);
    $letter_info['content'] = strip_tags($letter_info['content']);

    return $Render->view(json_encode($letter_info));
}));




