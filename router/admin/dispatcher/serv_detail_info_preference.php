<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/preferenceInfo', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');
    $PgCommon = new Pg('COMMON');

    $preference = [];

    $PgGame->query("SELECT t1.m_pref_pk, t1.posi_regi_pk, t2.state_name, t1.inspect, t1.forcemap_ver, date_part('epoch', t1.forcemap_upt_dt)::integer as forcemap_upt_dt
FROM m_preference t1, position_state t2 WHERE t1.posi_stat_pk=t2.posi_stat_pk ORDER BY t1.m_pref_pk");
    $PgGame->fetchAll();

    $preference['info'] = $PgGame->rows;

    $PgGame->query('SELECT posi_stat_pk, state_name, open_orderno, ru_max, ru_curr FROM position_state ORDER BY open_orderno');
    $PgGame->fetchAll();
    $preference['state'] = $PgGame->rows;

    $PgGame->query('SELECT t1.posi_regi_pk, t2.state_name, t1.open_orderno, t1.ru_max, t1.ru_curr FROM position_region t1, position_state t2
WHERE t1.posi_stat_pk = t2.posi_stat_pk AND t2.state_name = $1 ORDER BY t1.posi_stat_pk, t1.open_orderno', [$params['stats']]);
    $PgGame->fetchAll();

    $preference['region'] = $PgGame->rows;

    return $Render->view(json_encode($preference));
}));