<?php
global $app, $Render, $i18n;

$app->post('/api/member/create', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['id', 'pw', 'pw_verify']);

    $PgCommon = new Pg('COMMON');
    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;

    $_NS_SQ_REFRESH_FLAG = true;

    try {
        if (! isset($params['id']) || strlen($params['id']) < 6) {
            throw new Exception('id is required.');
        }

        // 1. 영문과 숫자. '_' 만 사용가능.
        if (! preg_match('/^[0-9a-z_-]+$/', $params['id'])) {
            throw new Exception('id can only be lowercase alphabet, numeric, and underscore(_).');
        }

        if ($params['id'] === $params['pw']) {
            throw new Exception('The ID and password cannot be the same.');
        }

        // 2. 패스워드는 8자 이상 입력해야함
        if (! isset($params['pw']) || strlen($params['pw']) < 8 || ! isset($params['pw_verify']) || strlen($params['pw_verify']) < 8) {
            throw new Exception('Please enter a password of at least 8 characters.');
        }

        if ($params['pw'] != $params['pw_verify']) {
            throw new Exception('Failed to verify password.');
        }

        $PgCommon->begin();

        $PgCommon->query('SELECT COUNT(member_pk) FROM ns_member WHERE id = $1', [$params['id']]);
        if ($PgCommon->fetchOne() <> 0) {
            throw new Exception('id already create.');
        }

        $token = md5(time());

        // pgp_sym_encrypt pgp_sym_decrypt
        $PgCommon->query('INSERT INTO ns_member (id, pw, token, mailling) VALUES ($1, crypt($4, gen_salt(\'md5\')), $2, $3)', [$params['id'], $token, 'N', $params['pw']]);

        $member_pk = $PgCommon->currSeq('ns_member_member_pk_seq');
        if (!$member_pk) {
            throw new Exception('currSeq error');
        }

        $PgCommon->query('SELECT token FROM ns_member WHERE member_pk = $1', [$member_pk]);
        $token = $PgCommon->fetchOne();

        $PgCommon->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgCommon->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success', null, ['lc' => '1', 'tk' => $token]);
}));


$app->post('/api/member/login', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['id', 'pw']);

    $PgCommon = new Pg('COMMON');

    try {
        // 1. 영문과 숫자. '_' 만 사용가능.
        if (! preg_match('/^[0-9a-z_-]+$/', $params['id'])) {
            throw new Exception('id can only be lowercase alphabet, numeric, and underscore(_).');
        }

        // 2. 패스워드는 8자 이상 입력해야함
        if (! isset($params['pw']) || strlen($params['pw']) < 8) {
            throw new Exception('Please enter a password of at least 8 characters.');
        }

        $PgCommon->query("SELECT token, member_pk, withdraw, date_part('epoch', withdraw_dt)::integer FROM ns_member WHERE id = $1 and pw = crypt($2, pw)", [$params['id'], $params['pw']]);
        if (! $PgCommon->fetch()) {
            throw new Exception('Authentication failed.');
        }

        if ($PgCommon->row['withdraw'] == 'Y') {
            if ($params['withdraw_cancel']) {
                $PgCommon->query('UPDATE ns_member SET withdraw = $1 WHERE member_pk = $2', ['N', $PgCommon->row['member_pk']]);
            } else {
                return $Render->nsXhrReturn('success', null, ['lc' => 'withdraw', 'tk' => date('Y/m/d H:i', $PgCommon->row['withdraw_dt'])]);
            }
        }

        $PgCommon->query('INSERT INTO qb_member_login (memb_pk, login_ip, platform) VALUES($1, $2, $3)', [$PgCommon->row['member_pk'], $_SERVER['REMOTE_ADDR'], $params['platform']]);
    } catch (Exception $e) {
        return $Render->nsXhrReturn('error', $e->getMessage());
    }

    return $Render->nsXhrReturn('success', null, ['lc' => '1', 'tk' => $PgCommon->row['token']]);
}));