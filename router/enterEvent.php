<?php
global $app, $Render, $i18n;

// TODO 일단 사용하지 않아서 컨버전하지 않음. 필요시 코드 확인 후 컨버전 필요.
$app->post('/api/enterEvent/', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    return $Render->nsXhrReturn('success');
}));
