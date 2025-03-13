<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_info_inspect', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $PgGame->query('SELECT access_allow_ip FROM m_preference LIMIT 1');
    $allow_ip = $PgGame->fetchOne();
    $allow_ip_arr = explode(';', $allow_ip);

    $response = new stdClass();
    $response->page = 1;
    $response->total = 1;
    $response->records = count($allow_ip_arr);
    $response->rows = [];

    $i = 0;
    foreach ($allow_ip_arr as $ip) {
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $i;
        $response->rows[$i]['cell'] = [$ip];
        $i++;
    }

    return $Render->view(json_encode($response));
}));

$app->post('/admin/gm/api/serverInspect', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $checkPermit = $Gm->checkGMPermission(['SCOMMAND']);
    if (is_string($checkPermit)) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => "{$checkPermit} 권한이 없습니다."]));
    }
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $PgGame->query('SELECT m_pref_pk, inspect FROM m_preference LIMIT 1');
    $PgGame->fetch();
    $m_pref_pk = $PgGame->row['m_pref_pk'];
    $inspect = $PgGame->row['inspect'];
    if ($params['action'] == 'on' || $params['action'] == 'off') {
        $inspect = ($params['action'] == 'on') ? 'Y' : 'N';
        $PgGame->query('UPDATE m_preference SET inspect = $1 WHERE m_pref_pk = $2', [$inspect, $m_pref_pk]);

        $Cache = new Cache('SESSION');
        $Cache->set('__SERVER_MAINTENANCE', $inspect);

        // 히스토리 기록
        $PgGm = new Pg('GM');
        $description = ['action' => 'do_inspect', 'selected_server' => $_SESSION['selected_server'], 'do_action' => $params['action']];
        $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'I', serialize($description)]);
    }

    $user_ip_addr = Useful::getRealClientIp();

    return $Render->view(json_encode(['result' => 'ok', 'inspect' => $inspect, 'current_ip' => $user_ip_addr]));
}));

$app->post('/admin/gm/api/manageAllowIp', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $checkPermit = $Gm->checkGMPermission(['SCOMMAND']);
    if (is_string($checkPermit)) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => "{$checkPermit} 권한이 없습니다."]));
    }
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    if ($params['action'] == 'add' || $params['action'] == 'del') {
        try {
            $PgGame->begin();

            $PgGame->query('SELECT access_allow_ip FROM m_preference LIMIT 1');
            if (!$PgGame->fetch()) {
                throw new Exception('don`t select');
            }
            $allow_ip_arr = explode(';', $PgGame->row['access_allow_ip']);

            foreach($allow_ip_arr as $k => $v) {
                if ($v == "") {
                    unset($allow_ip_arr[$k]);
                } else if ($params['action'] == 'add' && $v == $params['ip']) {
                    throw new Exception('duplicate ip');
                } else if ($params['action'] == 'del' && $v == $params['ip']) {
                    unset($allow_ip_arr[$k]);
                }
            }
            if ($params['action'] == 'add') {
                $allow_ip_arr[] = $params['ip'];
            }
            $str = implode(';', $allow_ip_arr);
            $PgGame->query('UPDATE m_preference SET access_allow_ip = $1', [$str]);

            $Cache = new Cache('SESSION');
            $Cache->set('__SERVER_MAINTENANCE_ACCESS_ALLOW_IP', $str);

            $PgGame->commit();
        } catch(Exception $e) {
            $PgGame->rollback();

            echo json_encode(['result' => 'fail', 'msg' => '허용할 ip 주소를 업데이트하지 못했습니다.']);
            exit(1);
        }
    } else {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => 'invalid request']));
    }

    // 히스토리 기록
    $PgGm = new Pg('GM');
    $description = ['action' => 'inspect_allow_ip', 'selected_server' => $_SESSION['selected_server'], 'do_action' => $params['action'], 'ip' => $params['ip']];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'I', serialize($description)]);

    return $Render->view(json_encode(['result' => 'ok']));
}));


