<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/attendance_event', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $PgGame->query('SELECT t2.attendance_cnt AS cnt, date_part(\'epoch\', t2.last_attendance_dt)::integer AS dt FROM lord t1, my_event t2 WHERE t1.lord_pk = t2.lord_pk AND t1.lord_pk = $1', [$_SESSION['selected_lord']['lord_pk']]);
    $PgGame->fetch();
    $row = $PgGame->row;
    $row['dt'] = date('Y-m-d h:i:s', $row['dt']);

    return $Render->view(json_encode(['result' => 'ok', 'attendance_info' => $row]));
}));


$app->post('/admin/gm/api/updateAttendance', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $checkPermit = $Gm->checkGMPermission(['EDIT']);
    if (is_string($checkPermit)) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => "{$checkPermit} 권한이 없습니다."]));
    }
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');
    $PgGm = new Pg('GM');

    $query_params = [$_SESSION['selected_lord']['lord_pk']];
    if ($params['action'] == 'init') {
        $PgGame->query('UPDATE my_event SET attendance_cnt = 0, last_attendance_dt = last_attendance_dt - interval \'1 days\' WHERE lord_pk = $1', $query_params);
    } else if ($params['action'] == 'yesterday') {
        $PgGame->query('UPDATE my_event SET last_attendance_dt = last_attendance_dt - interval \'1 days\' WHERE lord_pk = $1', $query_params);
    } else if ($params['action'] == 'plus') {
        $PgGame->query('UPDATE my_event SET attendance_cnt = attendance_cnt + 1, last_attendance_dt = now() WHERE lord_pk = $1', $query_params);
    } else if ($params['action'] == 'minus') {
        $PgGame->query('UPDATE my_event SET attendance_cnt = attendance_cnt - 1, last_attendance_dt = now() WHERE lord_pk = $1', $query_params);
    }

    $description = ['action' => 'attendance_event', 'selected_server' => $_SESSION['selected_server'], 'lord' => ['lord_pk' => $_SESSION['selected_lord']['lord_pk'], 'lord_name' => $_SESSION['selected_lord']['lord_name']], 'type' => $_POST['action']];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'M', serialize($description)]);

    return $Render->view(json_encode(['result' => 'ok']));
}));



