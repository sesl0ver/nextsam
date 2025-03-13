<?php
global $app, $Render, $i18n;

$app->post('/api/recalc/goldPop', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['posi_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $GoldPop = new GoldPop($Session, $PgGame);

    $GoldPop->get($params['posi_pk']);

    // 클라이언트와 서버간 시간차 보정
    $now = Useful::nowServerTime($PgGame);
    $Session->sqAppend('REDUCE', ['sTime' => $now]);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/recalc/loyalty', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['posi_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $GoldPop = new GoldPop($Session, $PgGame);

    $GoldPop->get($params['posi_pk']);

    $PgGame->query('SELECT loyalty FROM territory WHERE posi_pk = $1', [$params['posi_pk']]);
    $PgGame->fetch();
    $r = &$PgGame->row;

    $Session->sqAppend('TERR', $r);

    return $Render->nsXhrReturn('success');
}));