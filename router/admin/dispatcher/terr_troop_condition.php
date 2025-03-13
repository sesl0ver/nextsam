<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/terr_troop_condition', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $PgGame->query('SELECT t1.troo_pk, t1.src_lord_pk, t1.dst_lord_pk, t1.src_posi_pk, t1.dst_posi_pk, t1.from_position, t1.to_position, t1.camptime, t2.lord_name, t1.start_dt, t1.arrival_dt
FROM troop t1, lord t2 WHERE t1.dst_posi_pk = $1 AND t1.src_lord_pk = t2.lord_pk', [$_SESSION['selected_terr']['posi_pk']]);
    $PgGame->fetchAll();

    return $Render->view(json_encode(['result' => 'ok', 'rows' => $PgGame->rows]));
}));



