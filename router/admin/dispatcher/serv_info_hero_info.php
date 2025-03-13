<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/heroSearch', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $server_pk = $params['target_server_pk'] ?? null;

    $Gm = new Gm();
    $Gm->selectPgGame($server_pk);
    $PgGame = new Pg('SELECT');

    $PgGame->query('SELECT a.m_hero_pk, a.rare_type, a.level, b.lord_pk, b.posi_pk, b.status FROM hero as a left outer join my_hero as b on a.hero_pk = b.hero_pk WHERE a.hero_pk = $1', [$params['hero_pk']]);
    $PgGame->fetch();
    $hero_info = $PgGame->row;

    return $Render->nsXhrReturn('success', null, $hero_info);
}));