<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_info_qbig_modify', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    if (!isset($params['lord_name'])) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '군주명을 입력해주십시오.']));
    }

    $PgGame->query('SELECT lord_pk, lord_name, cash FROM lord WHERE lord_name = $1', [$params['lord_name']]);
    if (!$PgGame->fetch()) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '해당하는 군주명을 찾을 수 없습니다.']));
    }

    return $Render->view(json_encode(['result' => 'ok', 'info' => $PgGame->row]));
}));

$app->post('/admin/gm/api/modifyQbig', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $checkPermit = $Gm->checkGMPermission(['EDIT', 'PGGM']);
    if (is_string($checkPermit)) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => "{$checkPermit} 권한이 없습니다."]));
    }
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');
    $Session = new Session(false);
    $Cash = new Cash($Session, $PgGame);

    if (!isset($params['action']) || ($params['action'] != 'incr' && $params['action'] != 'decr')) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '큐빅에 대한 액션이 정해지지 않았으므로 진행할 수 없습니다.']));
    }

    if (!isset($params['lord_pk'])) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '큐빅을 지급 또는 회수할 대상 군주를 선택해주십시오.']));
    }

    if (!isset($params['amount']) || ($params['amount'] + 0) < 0) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '큐빅을 지급 또는 회수할 양을 정해주십시오.']));
    }

    if (!isset($params['cause']) || iconv_strlen($params['cause'], 'utf-8') < 1) {
        echo json_encode(['result' => 'fail', 'msg' => '큐빅을 지급 또는 회수하는 사유를 입력해주십시오.']);
        exit(1);
    }

    try
    {
        $PgGame->query('SELECT lord_pk, lord_name, main_posi_pk, cash FROM lord WHERE lord_pk = $1', [$params['lord_pk']]);
        if (!$PgGame->fetch())
        {
            echo json_encode(['result' => 'fail', 'msg' => '존재하지 않는 군주입니다.']);
            exit(1);
        }

        $lord_info = $PgGame->row;

        if ($params['action'] == 'incr')
        {
            $Cash->increaseCash($params['lord_pk'], $params['amount'], 'gm_give_cash');
        }
        else
        {
            if ($lord_info['cash'] - $params['amount'] < 0)
            {
                echo json_encode(['result' => 'fail', 'msg' => '소지한 큐빅량보다 많이 회수할 수 없습니다.']);
                exit(1);
            }
            $Cash->decreaseCash($params['lord_pk'], $params['amount'], 'gm_withdraw_cash');
        }

    }
    catch(Exception $e)
    {
        echo json_encode(['result' => 'fail', 'msg' => '큐빅을 지급 또는 회수하는 도중에 문제가 생겼습니다.']);
        exit(1);
    }

    $PgGame->query('SELECT cash FROM lord WHERE lord_pk = $1', [$lord_info['lord_pk']]);
    $cash = $PgGame->fetchOne();

    $Session->sqAppend('CASH', ['qbig' => $cash], null, $lord_info['lord_pk'], $lord_info['main_posi_pk']);

    // 히스토리 기록
    $PgGm = new Pg('GM');
    $description = ['action' => 'gm_modify_qbig', 'selected_server' => $_SESSION['selected_server'], 'lord' => ['lord_pk' => $params['lord_pk'], 'lord_name' => $lord_info['lord_name']], 'modify_action' => $params['action'], 'amount' => $params['amount'], 'cause' => $params['cause']];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'I', serialize($description)]);

    return $Render->view(json_encode(['result' => 'ok']));
}));