<?php
global $app, $Render, $i18n;

$app->post('/api/package/list', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Lord = new Lord($Session, $PgGame);

    $Lord->checkPackageDate($Session->lord['lord_pk']);
    $PgGame->query('SELECT m_package_pk, buy_count, date_part(\'epoch\', create_date) as create_date, date_part(\'epoch\', end_date) as end_date FROM my_package WHERE lord_pk = $1 and sold_out = 0 ORDER BY end_date', [$Session->lord['lord_pk']]);
    $PgGame->fetchAll();

    $list = [];
    $m_package_pk = (isset($params['m_package_pk'])) ? $params['m_package_pk'] : null;
    foreach ($PgGame->rows as $row) {
        $list[$row['m_package_pk']] = $row;
    }
    $Session->sqAppend('PUSH', ['PACKAGE_LIST' => ['first_popup' => false, 'm_package_pk' => $m_package_pk, 'list' => $list]], null, $Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/package/buy', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Cash = new Cash($Session, $PgGame);
    $Item = new Item($Session, $PgGame);

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['package']);
    $m_package = $_M['PACKAGE'][$params['m_package_pk']];

    try {
        // 1. 패키지 아이템 지급
        $Item->BuyItem($Session->lord['lord_pk'], $m_package['reward_item'], 1);

        // 2. 캐시 차감.
        $qbig = $Cash->decreaseCash($Session->lord['lord_pk'], $m_package['price'], "package_buy[{$params['m_package_pk']}]");
        if (! $qbig) {
            throw new Exception($i18n->t('msg_qbig_lack')); // 큐빅이 부족합니다.
        }

        // 정보 업데이트
        $PgGame->query('SELECT buy_count FROM my_package WHERE lord_pk = $1 and m_package_pk = $2', [$Session->lord['lord_pk'], $params['m_package_pk']]);
        $buy_count = $PgGame->fetchOne() + 1;

        $sold_out = ($buy_count >= $m_package['buy_limit']) ? 1 : 0; // 품절 확인
        $PgGame->query('UPDATE my_package SET buy_count = $3, sold_out = $4 WHERE lord_pk = $1 and m_package_pk = $2', [$Session->lord['lord_pk'], $params['m_package_pk'], $buy_count, $sold_out]);

        $Session->sqAppend('PUSH', ['PACKAGE_NOTICE' => ['m_package_pk' => null]], null, $Session->lord['lord_pk']);
    } catch (Throwable $e) {
        throw new ErrorHandler('error', $e->getMessage());
    }

    return $Render->nsXhrReturn('success');
}));
