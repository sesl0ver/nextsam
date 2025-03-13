<?php
global $app, $Render, $i18n;

$app->post('/api/storage/update', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    if (!isset($params['food']) || !isset($params['horse']) || !isset($params['lumber']) || !isset($params['iron'])) {
        throw new ErrorHandler('error', $i18n->t('msg_storage_max_rate')); // 창고의 저장비율은 합계가 100이 되어야 합니다.
    }

    if ($params['food'] < 0 || $params['horse'] < 0 || $params['lumber'] < 0 || $params['iron'] < 0) {
        throw new ErrorHandler('error', $i18n->t('msg_storage_min_rate')); // 저장비율에 0보다 작은 값을 할당할 수 없습니다.
    }

    if (($params['food'] + $params['horse'] + $params['lumber'] + $params['iron']) > 100 || ($params['food'] + $params['horse'] + $params['lumber'] + $params['iron']) < 100) {
        throw new ErrorHandler('error', $i18n->t('msg_storage_max_rate')); // 창고의 저장비율은 합계가 100이 되어야 합니다.
    }

    $Terr = new Territory($Session, $PgGame);

    $rArr = $Terr->changestoragepct($params['posi_pk'], $params['food'], $params['horse'], $params['lumber'], $params['iron']);
    if (!$rArr) {
        throw new ErrorHandler('error', 'Failed to change storage rate.');
    }

    $Resource = new Resource($Session, $PgGame);
    $GoldPoop = new GoldPop($Session, $PgGame);
    $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPoop);
    $FigureReCalc->resourceMax($params['posi_pk']);
    $Resource->save($params['posi_pk']);

    // Log
    $Log = new Log($Session, $PgGame);
    $Log->setBuildingStorage($Session->lord['lord_pk'], $params['posi_pk'], 'storage_rate', $params['food'].';'.$params['horse'].';'.$params['lumber'].';'.$params['iron']);

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'storage_rate', 'food' => $params['food'], 'horse' => $params['horse'], 'lumber' => $params['lumber'], 'iron' => $params['iron']]);

    return $Render->nsXhrReturn('success');
}));
