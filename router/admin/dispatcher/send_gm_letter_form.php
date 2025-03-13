<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/send_gm_letter_form', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $PgGm = new Pg('GM');

    $PgGm->query('SELECT server_pk, server_name, db_ip, db_port, db_account, db_password FROM server');
    $PgGm->fetchAll();
    $server_list = $PgGm->rows;

    foreach ($server_list as $row) {
        define('GAME_'.$row['server_pk'].'_PGSQL_IP', $row['db_ip']);
        define('GAME_'.$row['server_pk'].'_PGSQL_PORT', $row['db_port']);
        define('GAME_'.$row['server_pk'].'_PGSQL_DB', 'qbegame');
        define('GAME_'.$row['server_pk'].'_PGSQL_USER', $row['db_account']);
        define('GAME_'.$row['server_pk'].'_PGSQL_PASS', $row['db_password']);
        define('GAME_'.$row['server_pk'].'_PGSQL_PERSISTENT', true);
    }

    $response = new stdClass();
    $response->rows = [];

    if ($params['search_lord_name'] == '') {
        return $Render->view(json_encode([]));
    }

    $i = 0;
    if ($params['target_server_type'] == 'all') {
        foreach($server_list as $v) {
            $PgGame = new Pg('GAME_'.$v['server_pk']);
            $PgGame->query("SELECT lord_pk, lord_name FROM lord WHERE lord_name ILIKE '%{$params['search_lord_name']}%'");
            $PgGame->fetchAll();
            foreach ($PgGame->rows as $row) {
                $response->rows[$i] = [];
                $response->rows[$i]['id'] = $v['server_pk'] . '_' . $row['lord_pk'];
                $response->rows[$i]['cell'] = [$v['server_name'], $row['lord_name'], $v['server_pk'], $row['lord_pk']];
                $i++;
            }
        }
    } else {
        $server_info = NULL;
        foreach($_SESSION['server_list'] as $v) {
            $server_info = ($v['server_pk'] == $params['target_server_pk']) ? $v : NULL;
            if ($server_info !== NULL) {
                break;
            }
        }

        // 서버에서만 찾는다면
        if ($server_info !== NULL) {
            $PgGame = new Pg('GAME_'.$server_info['server_pk']);
            $PgGame->query("SELECT lord_pk, lord_name FROM lord WHERE lord_name ILIKE '%{$params['search_lord_name']}%'");
            $PgGame->fetchAll();
            foreach ($PgGame->rows as $row) {
                $response->rows[$i] = [];
                $response->rows[$i]['id'] = $server_info['server_pk'] . '_' . $row['lord_pk'];
                $response->rows[$i]['cell'] = [$server_info['server_name'], $row['lord_name'], $server_info['server_pk'], $row['lord_pk']];
                $i++;
            }
        }
    }

    return $Render->view(json_encode($response));
}));



$app->post('/admin/gm/api/sendGmLetter', $Render->wrap(function (array $params) use ($Render, $i18n) {
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
    $PgGm = new Pg('GM');

    $PgGm->query('SELECT server_pk, server_name, db_ip, db_port, db_account, db_password FROM server');
    $PgGm->fetchAll();
    $server_list = $PgGm->rows;

    foreach ($server_list as $row) {
        define('GAME_'.$row['server_pk'].'_PGSQL_IP', $row['db_ip']);
        define('GAME_'.$row['server_pk'].'_PGSQL_PORT', $row['db_port']);
        define('GAME_'.$row['server_pk'].'_PGSQL_DB', 'qbegame');
        define('GAME_'.$row['server_pk'].'_PGSQL_USER', $row['db_account']);
        define('GAME_'.$row['server_pk'].'_PGSQL_PASS', $row['db_password']);
        define('GAME_'.$row['server_pk'].'_PGSQL_PERSISTENT', true);
    }

    if (iconv_strlen($params['cause'], 'utf-8') < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '변경 사유를 입력하여 주십시오.']));
    }

    if (iconv_strlen($params['subject'], 'utf-8') < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => 'GM 서신 제목을 입력해주십시오.']));
    }

    if (iconv_strlen($params['content'], 'utf-8') < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => 'GM 서신 내용을 입력해주십시오.']));
    }

    foreach($params['receiver_list'] as $v) {
        if (!is_array($v) && $v == 'all') {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '전체 발송 기능은 현재 지원하지 않습니다.']));
        }
    }

    $arr_letter = ['title' => $params['subject'], 'content' => $params['content'], 'type' => 'S'];

    foreach($params['receiver_list'] as $k => $v)
    {
        if (defined('GAME_'.$k.'_PGSQL_IP')) {
            $Session = new Session(false);
            $PgGame = new Pg('GAME_'.$k);
            $Letter = new Letter($Session, $PgGame);
            if (is_array($v)) {
                $lord_pk_arr = [];
                foreach($v as $lord_info) {
                    $lord_pk_arr[] = $lord_info['lord_pk'];
                }
                $Letter->sendLetter(2, $lord_pk_arr, $arr_letter, false, 'Y');
            }
        }
    }

    // 히스토리 기록
    $description = ['action' => 'send_gm_letter', 'letter_body' => $arr_letter, 'receiver_list' => $params['receiver_list'], 'cause' => $params['cause']];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'G', serialize($description)]);

    return $Render->view(json_encode([]));
}));