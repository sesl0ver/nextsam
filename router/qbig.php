<?php
global $app, $Render, $i18n;

$app->post('/api/qbig/payment', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['uuid', 'store_type', 'prod_id', 'receipt_id', 'request_id', 'signature']);
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $PgCommon = new Pg('COMMON');

    $product_id = $params['prod_id']; // 상품 ID
    $bill_charge_no = $params['receipt_id']; // 영수증 ID
    $store_type = $params['store_type']; // 상점 구분
    $uuid = $params['uuid']; // 유니크 유저 아이디

    // 시그니쳐 검증. signature = md5(SECRET_KEY + #: + REQUEST_ID + #: + uuid);
    $signature = md5(CONF_PLATFORM_SECRET_KEY . '#'. $params['request_id'] . '#' . $params['uuid']);
    if ($signature !== $params['signature']) {
        throw new ErrorHandler('error', 'Signature Verification Failed.', true);
    }

    // 중복 영수증 검증.
    $PgGame->query('SELECT count(qbi_pac_pk) FROM qbig_pack WHERE bill_chargeno = $1', [$bill_charge_no]);
    if ($PgGame->fetchOne() > 0) {
        throw new ErrorHandler('error', 'This is the receipt that has already been paid.', true);
    }

    // 군주 캐릭터 생성 여부 검증
    $PgCommon->query('SELECT account_pk FROM account WHERE uid = $1', [$params['uuid']]);
    if (! $PgCommon->fetch()) {
        throw new ErrorHandler('error', 'No account matching uuid was found.');
    }
    $account_pk = $PgCommon->row['account_pk'];
    $PgGame->query('select l.lord_pk, l.main_posi_pk, l.cash from lord_web w left join lord l on w.lord_pk = l.lord_pk where web_id = $1', [$account_pk]);
    if (! $PgGame->fetch()) {
        throw new ErrorHandler('error', 'An account that did not create a Lord Character.');
    }
    $lord_pk = $PgGame->row['lord_pk'];

    $Cash = new Cash($Session, $PgGame);

    try {
        $PgGame->begin();

        // 큐빅 지급
        $Cash->chargeCash($store_type, $product_id, $bill_charge_no, $lord_pk);

        $PgGame->commit();
    } catch (Throwable $e) {
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    return $Render->nsXhrReturn('success');
}));
