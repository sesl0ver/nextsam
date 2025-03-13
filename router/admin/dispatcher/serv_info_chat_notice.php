<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/chatNoticeList', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $PgGm = new Pg('GM');

    $PgGm->query("SELECT noti_pk, message, array_to_json(target_server) as target_server, start_dt, end_dt, regist_dt, next_dt, repeat, repeat_time, repeat_count, active, used FROM gmtool_notice ORDER BY regist_dt DESC");
    $PgGm->fetchAll();
    foreach ($PgGm->rows as &$v) {
        $target_server = json_decode($v['target_server'], true);
        $v['target_server'] = '';
        foreach ($target_server as $server_pk) {
            $index = array_search($server_pk, array_column($_SESSION['server_list'], 'server_pk'));
            if ($v['target_server'] !== '') {
                $v['target_server'] .= ', ';
            }
            $v['target_server'] .= $_SESSION['server_list'][$index]['server_name'];
        }
        $v['start_dt'] = strtotime($v['start_dt']) * 1000;
        $v['end_dt'] = strtotime($v['end_dt']) * 1000;
        $v['next_dt'] = strtotime($v['next_dt']) * 1000;
        $v['regist_dt'] = strtotime($v['regist_dt']) * 1000;
    }

    return $Render->view(json_encode(['result' => 'ok', 'list' => $PgGm->rows]));
}));

$app->post('/admin/gm/api/addChatNotice', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $PgGm = new Pg('GM');
    $message = strip_tags($params['notice_message']);
    $PgGm->query("INSERT INTO gmtool_notice (message, target_server, start_dt, end_dt, next_dt, repeat, repeat_time, active) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)",
                                    [$message, '{'.implode(',', $params['target_server']).'}', date('Y-m-d H:i:s', $params['start_time']), date('Y-m-d H:i:s', $params['end_time']), date('Y-m-d H:i:s', $params['start_time']), $params['repeat_notice'], $params['repeat_notice_time'], $params['notice_active']]);

    // 1. 태그, 링크가 포함되서는 안됨.
    // 2. 색깔 넣기 가능하게?
    // 3. 줄바꿈시에는 어떻게?

    return $Render->view(json_encode(['result' => 'ok']));
}));

$app->post('/admin/gm/api/deleteChatNotice', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $PgGm = new Pg('GM');
    $PgGm->query('DELETE FROM gmtool_notice WHERE noti_pk = $1 and active = false', [$params['pk']]);

    return $Render->view(json_encode(['result' => 'ok']));
}));

$app->post('/admin/gm/api/modifyChatNotice', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $PgGm = new Pg('GM');
    $PgGm->query('SELECT noti_pk, message, array_to_json(target_server) as target_server, start_dt, end_dt, next_dt, regist_dt, repeat, repeat_time, repeat_count, active, used FROM gmtool_notice WHERE noti_pk = $1', [$params['pk']]);
    $PgGm->fetch();
    $PgGm->row['target_server'] = json_decode($PgGm->row['target_server'], true);
    $PgGm->row['start_dt'] = strtotime($PgGm->row['start_dt']) * 1000;
    $PgGm->row['end_dt'] = strtotime($PgGm->row['end_dt']) * 1000;
    $PgGm->row['next_dt'] = strtotime($PgGm->row['next_dt']) * 1000;
    $PgGm->row['regist_dt'] = strtotime($PgGm->row['regist_dt']) * 1000;

    return $Render->view(json_encode(['result' => 'ok', 'row' => $PgGm->row]));
}));

$app->post('/admin/gm/api/updateChatNotice', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $PgGm = new Pg('GM');
    $message = strip_tags($params['notice_message']);
    $PgGm->query('UPDATE gmtool_notice SET message = $2, target_server = $3, start_dt = $4, end_dt = $5, next_dt = $6, repeat = $7, repeat_time = $8, active = $9, used = false WHERE noti_pk = $1'
                                    , [$params['pk'], $message, '{'.implode(',', $params['target_server']).'}', date('Y-m-d H:i:s', $params['start_time']), date('Y-m-d H:i:s', $params['end_time']), date('Y-m-d H:i:s', $params['start_time']), $params['repeat_notice'], $params['repeat_notice_time'], $params['notice_active']]);

    return $Render->view(json_encode(['result' => 'ok']));
}));