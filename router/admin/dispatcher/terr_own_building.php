<?php
global $app, $Render, $i18n;

//로드 - 보유 건설 목록 조회
$app->post('/admin/gm/api/terr_own_building', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    function getBuildStatus($str) :string {
        $build_status = [
            'N' => '일반',
            'C' => '건설 중',
            'U' => '업그레이드 중',
            'D' => '다운그레이드 중'
        ];

        return $build_status[$str] ?? $str;
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['building']);

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $posi_pk = $_SESSION['selected_terr']['posi_pk'];

    if ( empty($posi_pk) )
    {
        return $Render->view(json_encode(['result' => false, 'message'=>'Is exists an error in [selected_terr][posi_pk]', 'data' => null]));
    }


    $sql = "SELECT in_castle_pk, m_buil_pk, status, level
            FROM building_in_castle where posi_pk = $1
            ORDER BY m_buil_pk ASC, level DESC";

    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetchAll();
    $in_castle = $PgGame->rows;

    $sql = "SELECT out_castle_pk, m_buil_pk, status, level
            FROM building_out_castle where posi_pk = $1
            ORDER BY m_buil_pk ASC, level DESC";

    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetchAll();
    $out_castle = $PgGame->rows;

    // 성내 건설 정보 갈무리
    foreach($in_castle as $k => &$v) {
        $v['title'] = $_M['BUIL'][$v['m_buil_pk']]['title'];
        $v['status'] = getBuildStatus($v['status']);
    }

    // 성외 건설 정보 갈무리
    foreach($out_castle as $k => &$v) {
        $v['title'] = $_M['BUIL'][$v['m_buil_pk']]['title'];
        $v['status'] = getBuildStatus($v['status']);
    }

    return $Render->view(json_encode(['in' => $in_castle, 'out' => $out_castle]));
}));



