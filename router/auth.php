<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

global $app, $Render, $i18n;

$app->post('/api/auth/connect', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session(false);
    $PgCommon = new Pg('COMMON');

    $platform = (CONF_ONLY_PLATFORM_MODE !== true) ? 'TEST' : $params['platform'];
    $Auth = new Auth($PgCommon, $params['uuid'], $params['lc'], $params['token'], $platform);
    $Auth->getAccount();
    if ($Auth->need_membership) {
        try {
            $PgCommon->begin();

            $Auth->createMembership();

            $PgCommon->commit();
        } catch (Throwable $e) {
            $PgCommon->rollback();
            throw new ErrorHandler('error', $e->getMessage(), true);
        }
    }

    return $Render->nsXhrReturn('success', null, [
        'status' => $Auth->status,
        'id' => $Auth->id,
    ]);
}));

$app->post('/api/auth/signupGuest', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!$params['uuid']) {
        throw new ErrorHandler('error', $i18n->t('msg_not_found_uuid_retry_please')); // uuid 를 찾을 수 없습니다. 앱을 재시작 또는 문의 바랍니다.
    }
    $PgCommon = new Pg('COMMON');
    $Auth = new Auth($PgCommon, $params['uuid'], $params['lc'], $params['token'], $params['platform']);

    try {
        $PgCommon->begin();

        $Auth->createGuest();

        $PgCommon->commit();
    } catch (Throwable $e) {
        // 실패, sq 무시
        $PgCommon->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }
    return $Render->nsXhrReturn('success');
}));

$app->map(['GET', 'POST'], '/api/auth/requestToken', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['uuid', 'platform', 'request_id', 'signature']);

    // 시그니쳐 검증. signature = md5(SECRET_KEY + #: + REQUEST_ID + #: + uuid);
    $signature = md5(CONF_PLATFORM_SECRET_KEY . '#'. $params['request_id'] . '#' . $params['uuid']);
    if ($signature !== $params['signature']) {
        throw new ErrorHandler('error', 'Signature Verification Failed.', true);
    }

    $PgCommon = new Pg('COMMON');

    try {
        $i = 0;
        while (true) {
            if ($i > 2) {
                // 3회 시도후 성공하지 못하면 오류
                throw new ErrorHandler('error', 'An error occurred while creating a user account.');
            }
            $Auth = new Auth($PgCommon, $params['uuid'], '2', null, $params['platform']);
            $Auth->getAccount();
            if ($Auth->need_membership) {
                $PgCommon->begin();

                $Auth->createPlatformUser();

                $PgCommon->commit();
            } else {
                // 맴버가 확인되면 멈춤
                break;
            }
            $i++;
        }
    } catch (Throwable $e) {
        $PgCommon->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    $salt = Useful::uniqId();
    $payload = [
        "iat" => time(),
        "nbf" => time(),
        "exp" => time() + 86400,
        "uid" => $params['uuid'],
        "platform" => $params['platform']
    ];
    $token = JWT::encode($payload, $salt, 'HS256');
    $Auth->setPlatformToken($Auth->account_info['account_pk'], $salt);

    return $Render->nsXhrReturn('success', null, [
        'token' => $token
    ]);
}));