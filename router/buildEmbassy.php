<?php
global $app, $Render, $i18n;

$app->post('/api/embassy/list', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $rows = $Troop->getAllyCampTroops($params['posi_pk']);

    return $Render->nsXhrReturn('success', null, $rows);
}));

$app->post('/api/embassy/camp', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Territory = new Territory($Session, $PgGame);

    $PgGame->query('SELECT yn_alliance_camp FROM territory WHERE posi_pk = $1', [$params['posi_pk']]);
    $yn_alliance_camp = $PgGame->fetchOne();

    $next = ($yn_alliance_camp == 'Y') ? 'N' : 'Y';
    $next_message = ($yn_alliance_camp == 'Y') ? $i18n->t('msg_impossible_reinforcements') : $i18n->t('msg_available_reinforcements'); // 이제 지원군 주둔이 불가능합니다. : 이제 지원군 주둔이 가능합니다.

    $PgGame->query('UPDATE territory SET yn_alliance_camp = $1 WHERE posi_pk = $2', [$next, $params['posi_pk']]);

    $Territory->get($params['posi_pk'], ['yn_alliance_camp']);

    return $Render->nsXhrReturn('success', null, ['camp' => $next, 'message' => $next_message]);
}));