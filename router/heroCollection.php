<?php
global $app, $Render, $i18n;

$app->post('/api/heroCollection/get', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $HeroCollection = new HeroCollection($Session, $PgGame);
    $hero_collection = $HeroCollection->getHeroCollectionInfo($Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success', null, $hero_collection);
}));

// TODO 사용안함
$app->post('/api/heroCollection/reward', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $HeroCollection = new HeroCollection($Session, $PgGame);
    $ret = $HeroCollection->rewardCollection($Session->lord['lord_pk'], $_POST['m_hero_coll_pk']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success', null, ['mesg' => $i18n->t('msg_item_received', [$ret]), 'm_hero_coll_pk' =>$_POST['m_hero_coll_pk']]);
}));

$app->post('/api/heroCollection/addReward', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $HeroCollection = new HeroCollection($Session, $PgGame);
    $ret = $HeroCollection->additionalReward($Session->lord['lord_pk']);
    if (! $ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/heroCollection/getRanking', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $HeroCollection = new HeroCollection($Session, $PgGame);
    $total_count = $HeroCollection->getHeroCollectionTotalCount();
    $collection_ranking = $HeroCollection->getHeroCollectionRanking($total_count, $params['page']);

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'total_page' => $collection_ranking['total_page'], 'page' => $collection_ranking['page'], 'ranking' => $collection_ranking['ranking']]);
}));
