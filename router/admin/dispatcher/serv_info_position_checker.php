<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_info_position_checker', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $PgGame->query('SELECT posi_pk FROM position WHERE TYPE = $1 AND posi_pk NOT IN (SELECT posi_pk FROM territory WHERE posi_pk = position.posi_pk) order by posi_pk', ['T']);
    $count = $PgGame->fetchAll();
    $rows = $PgGame->rows;

    return $Render->view(json_encode(['count' => $count, 'rows' => $rows]));
}));

$app->post('/admin/gm/api/positionCheck', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $PgGame->query('update position set lord_pk = null, type = \'E\', level = level_default, last_update_dt = now() where posi_pk IN (SELECT posi_pk FROM position WHERE TYPE = \'T\' AND posi_pk NOT IN (SELECT posi_pk FROM territory WHERE posi_pk = position.posi_pk))');

    $cnt = $PgGame->getAffectedRows();
    if ($cnt <= 0) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '수정할 오류가 없습니다.']));
    }

    return $Render->view(json_encode(['result' => 'ok', 'msg' => $cnt.'개의 영지가 오류 해결 되었습니다.']));
}));
