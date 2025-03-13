<?php
global $app, $Render, $i18n;

$app->post('/api/report/list', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['type']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Report = new Report($Session, $PgGame);
    $total_count = $Report->getReportTotalCount($Session->lord['lord_pk'], $params['type']);

    // 하나도 없는 경우
    if ($total_count == 0) {
        return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'list' => []]);
    }

    $total_page = (INT)($total_count / REPORT_LETTER_PAGE_NUM);
    $total_page += ($total_count % REPORT_LETTER_PAGE_NUM > 0)? 1 : 0;

    $page_num = $params['page_num'];

    if ($page_num < 1) {
        $page_num = 1;
    } else if ($page_num > $total_page) {
        $page_num = $total_page;
    }

    $rows = $Report->getReportList($Session->lord['lord_pk'], $params['type'], $page_num);

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'total_page' => $total_page, 'curr_page' => $page_num, 'list' => $rows]);
}));

$app->post('/api/report/view', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Report = new Report($Session, $PgGame);
    $row = $Report->getReport($params['repo_pk']);
    if (! $row) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }
    $Report->getUnreadCount($Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success', null, $row);
}));

$app->post('/api/report/battleJson', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Report = new Report($Session, $PgGame);
    $data = $Report->getReportBattleJson($params['repo_pk']);

    if (!$data) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    // 승패 사운드 추가를 위해 소스 개선
    $content_battle_json = json_decode($data['content_battle_json'], true);
    $content_battle_json['content_json'] = json_decode($data['content_json']);

    return $Render->nsXhrReturn('success', null, $content_battle_json);
}));

$app->post('/api/report/setRead', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $repo_pk_arr = explode(',', $params['repo_pk_list']);
    if (!is_array($repo_pk_arr) || count($repo_pk_arr) < 1) {
        throw new ErrorHandler('error', 'Error Occurred. [34001]'); // 올바르지 않은 보고서 목록
    }
    foreach($repo_pk_arr as $repo_pk) {
        if (!preg_match('/^[\d]+$/', $repo_pk)) {
            throw new ErrorHandler('error', 'Error Occurred. [34002]'); // 올바르지 않은 보고서 목록
        }
    }
    $Report = new Report($Session, $PgGame);
    $Report->setRead($repo_pk_arr, $Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/report/remove', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $repo_pk_arr = explode(',', $params['repo_pk_list']);
    if (!is_array($repo_pk_arr) || count($repo_pk_arr) < 1) {
        throw new ErrorHandler('error', 'Error Occurred. [34003]'); // 올바르지 않은 보고서 목록이 전달
    }
    foreach($repo_pk_arr as $repo_pk) {
        if (!preg_match('/^[\d]+$/', $repo_pk)) {
            throw new ErrorHandler('error', 'Error Occurred. [34004]'); // 올바르지 않은 보고서 목록이 전달
        }
    }
    $Report = new Report($Session, $PgGame);
    $Report->removeReport($repo_pk_arr);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/report/relatedReport', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Report = new Report($Session, $PgGame);
    $row = $Report->getReportToPosition($params['to_posi_pk']);
    return $Render->nsXhrReturn('success', null, $row);
}));
