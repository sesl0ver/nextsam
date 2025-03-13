<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/lordSearch', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $arr_lord_keyword = explode("\n", $params['lord_name']);
    foreach($arr_lord_keyword as $k => $v) {
        if (!is_int($v)) {
            $arr_lord_keyword[$k] = '\''.$v.'\'';
        }
    }
    $lord_keyword = implode(",", $arr_lord_keyword);
    $server_pk = $params['server_pk'];

    if (count($arr_lord_keyword) < 1) {
        return $Render->view(json_encode(['result' => false, 'msg' => '군주명을 적어주세요!!']));
    }

    if (!strlen($server_pk)) {
        return $Render->view(json_encode(['result' => false, 'msg' => '서버를 선택해주세요.']));
    }

    $Gm = new Gm();
    $Gm->selectPgGame($server_pk);
    $PgGame = new Pg('SELECT');

    if ($params['search_type'] == 'lord_name') {
        $PgGame->query("SELECT lord_pk, lord_name FROM lord WHERE lord_name in ({$lord_keyword})");
    } else if ($params['search_type'] == 'lord_pk') {
        $PgGame->query("SELECT lord_pk, lord_name FROM lord WHERE lord_pk in ({$lord_keyword})");
    } else if ($params['search_type'] == 'web_id') {
        $PgGame->query("SELECT t1.lord_pk, t1.lord_name FROM lord t1, lord_web t2 WHERE t1.lord_pk = t2.lord_pk AND t2.web_id in ({$lord_keyword})");
    } else if ($params['search_type'] == 'uuid') {
        $PgCommon = new Pg('COMMON');
        $PgCommon->query("SELECT account_pk, uid FROM account WHERE uid in ({$lord_keyword})");
        $PgCommon->fetchAll();
        $pk = [];
        foreach ($PgCommon->rows as $row) {
            $pk[] = '\''.$row['account_pk'].'\'';
        }
        $account_pks = implode(',', $pk);
        if (! $account_pks) {
            return $Render->view(json_encode(['result' => false, 'msg' => '검색 결과가 없습니다.']));
        }
        $PgGame->query("SELECT t1.lord_pk, t1.lord_name FROM lord t1, lord_web t2 WHERE t1.lord_pk = t2.lord_pk AND t2.web_id in ({$account_pks})");
    }
    $PgGame->fetchAll();
    $result = $PgGame->rows;

    if (count($result) < 1) {
        return $Render->view(json_encode(['result' => false, 'msg' => '검색 결과가 없습니다.']));
    }

    return $Render->view(json_encode(['result' => true, 'd' => $result]));
}));

$app->post('/admin/gm/api/changeLordIntro', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $server_pk = $params['server_pk'];

    $Gm = new Gm();
    $Gm->selectPgGame($server_pk);
    $PgGame = new Pg('SELECT');

    $lord_intro = $params['lord_intro'];
    if (preg_match("/[^\x{AC00}-\x{D7A3}0-9a-zA-Z\s\n]/u", $lord_intro) > 0) {
        return $Render->view(json_encode(['result' => false, 'msg' => '군주 인사말은 오로지 한글, 영문, 숫자만 가능합니다.']));
    }
    if (iconv_strlen($lord_intro, 'UTF-8') > 200) {
        return $Render->view(json_encode(['result' => false, 'msg' => '군주 인사말은 200자 이내로 작성해야 합니다.']));
    }

    $PgGame->query('SELECT lord_intro FROM lord WHERE lord_pk = $1', [$_SESSION['selected_lord']['lord_pk']]);
    $prev_lord_intro = $PgGame->fetchOne();

    $PgGame->query('UPDATE lord SET lord_intro = $1 WHERE lord_pk = $2', [$lord_intro, $_SESSION['selected_lord']['lord_pk']]);

    if ($PgGame->getAffectedRows() != 1) {
        echo json_encode(['result' => 'fail', 'msg' => '군주 인사말 변경에 실패하였습니다.']);
        exit(1);
    }

    // GM툴 로그 기록
    $PgGm = new Pg('GM');
    $description = ['action' => 'change_lord_intro', 'selected_server' => $_SESSION['selected_server'], 'lord' => $_SESSION['selected_lord'], 'prev_lord_intro' => $prev_lord_intro, 'changed_lord_intro' => $lord_intro, 'cause' => $params['change_cause']];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'M', serialize($description)]);

    return $Render->view(json_encode(['result' => true, 'd' => []]));
}));

$app->post('/admin/gm/api/changeAllyIntro', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $server_pk = $params['server_pk'];

    $Gm = new Gm();
    $Gm->selectPgGame($server_pk);
    $PgGame = new Pg('SELECT');

    $alli_intro = $params['alli_intro'];
    if (preg_match("/[^\x{AC00}-\x{D7A3}0-9a-zA-Z\s\n]/u", $alli_intro) > 0) {
        return $Render->view(json_encode(['result' => false, 'msg' => '동맹 인사말은 오로지 한글, 영문, 숫자만 가능합니다.']));
    }
    if (iconv_strlen($alli_intro, 'UTF-8') > 200) {
        return $Render->view(json_encode(['result' => false, 'msg' => '동맹 인사말은 200자 이내로 작성해야 합니다.']));
    }

    $PgGame->query('SELECT alli_intro FROM lord WHERE lord_pk = $1', [$_SESSION['selected_lord']['lord_pk']]);
    $prev_alli_intro = $PgGame->fetchOne();

    $PgGame->query('UPDATE lord SET alli_intro = $1 WHERE lord_pk = $2', [$alli_intro, $_SESSION['selected_lord']['lord_pk']]);

    if ($PgGame->getAffectedRows() != 1) {
        return $Render->view(json_encode(['result' => false, 'msg' => '동맹 인사말 변경에 실패하였습니다.']));
    }

    // GM툴 로그 기록
    $PgGm = new Pg('GM');
    $description = ['action' => 'change_alli_intro', 'selected_server' => $_SESSION['selected_server'], 'lord' => $_SESSION['selected_lord'], 'prev_alli_intro' => $prev_alli_intro, 'changed_alli_intro' => $alli_intro, 'cause' => $params['change_cause']];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'M', serialize($description)]);

    return $Render->view(json_encode(['result' => true, 'd' => []]));
}));