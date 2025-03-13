<?php
global $app, $Render, $i18n;

$app->post('/api/coupon/use', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $PgCommon = new Pg('COMMON');
    $Item = new Item($Session, $PgGame);

    $coupon_code = $params['code'] ?? '';
    if ($coupon_code == '') {
        return $Render->nsXhrReturn('error', $i18n->t('msg_need_coupon_code'));
    }

    try {
        // 군주 정보 확인
        $PgGame->query('SELECT web_id FROM lord_web WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
        $account_pk = $PgGame->fetchOne();

        // 1. 시리얼 쿠폰인지 확인하기
        $PgCommon->query('SELECT coupon_pk FROM coupon_use WHERE coupon_code = $1 and account_pk = 0', [$coupon_code]);
        $coupon_pk = $PgCommon->fetchOne();
        if (! $coupon_pk) {
            // 시리얼 쿠폰 아님. 단일 쿠폰 확인.
            $PgCommon->query('SELECT coupon_pk, coupon_code, coupon_count, coupon_use, start_date, end_date, item_data FROM coupon WHERE coupon_code = $1', [$coupon_code]);
            $PgCommon->fetch();
            if ($PgCommon->getNumRows() < 1) {
                return $Render->nsXhrReturn('error', $i18n->t('msg_not_exist_coupon_code')); // 존재하지 않는 쿠폰입니다.
            }
            $row = $PgCommon->row;
            $coupon_pk = $row['coupon_pk'];
            if (strtotime($row['start_date']) > time()) {
                return $Render->nsXhrReturn('error', $i18n->t('msg_prepare_coupon_code')); // 아직 준비 중인 쿠폰입니다.
            }
            if ((strtotime($row['end_date']) < time()) || ($row['coupon_use'] >= $row['coupon_count'])) {
                return $Render->nsXhrReturn('error', $i18n->t('msg_expire_coupon_code')); // 이미 만료된 쿠폰입니다.
            }
            // 단일 쿠폰인 경우 이미 사용한 쿠폰인지 확인
            $PgCommon->query('SELECT count(coupon_pk) FROM coupon_use WHERE coupon_code = $1 and account_pk = $2', [$coupon_code, $account_pk]);
            if ($PgCommon->fetchOne() > 0) {
                return $Render->nsXhrReturn('error', $i18n->t('msg_used_coupon_code')); // 이미 사용된 쿠폰입니다.
            }

            // 아이템 지급
            $item_data = json_decode($row['item_data'], true);

            // 쿠폰 사용 등록 처리
            $PgCommon->query('INSERT INTO coupon_use (coupon_pk, coupon_code, account_pk, use_date) VALUES ($1, $2, $3, now())', [$coupon_pk, $coupon_code, $account_pk]);
        } else {
            $PgCommon->query('SELECT count(coupon_pk) FROM coupon_use WHERE coupon_pk = $1 and account_pk = $2', [$coupon_pk, $account_pk]);
            if ($PgCommon->fetchOne() > 0) {
                return $Render->nsXhrReturn('error', $i18n->t('msg_used_coupon_code')); // 이미 사용된 쿠폰입니다.
            }
            // 시리얼 쿠폰!
            $PgCommon->query('SELECT coupon_code, coupon_count, coupon_use, start_date, end_date, item_data FROM coupon WHERE coupon_pk = $1', [$coupon_pk]);
            $PgCommon->fetch();
            if ($PgCommon->getNumRows() < 1) {
                return $Render->nsXhrReturn('error', $i18n->t('msg_not_exist_coupon_code')); // 존재하지 않는 쿠폰입니다.
            }
            $row = $PgCommon->row;
            if (strtotime($row['start_date']) > time()) {
                return $Render->nsXhrReturn('error', $i18n->t('msg_prepare_coupon_code')); // 아직 준비 중인 쿠폰입니다.
            }
            if ((strtotime($row['end_date']) < time()) || ($row['coupon_use'] >= $row['coupon_count'])) {
                return $Render->nsXhrReturn('error', $i18n->t('msg_expire_coupon_code')); // 이미 만료된 쿠폰입니다.
            }

            // 아이템 지급
            $item_data = json_decode($row['item_data'], true);

            // 쿠폰 사용 처리
            $PgCommon->query('UPDATE coupon_use SET account_pk = $3, use_date = now() WHERE coupon_pk = $1 and coupon_code = $2', [$coupon_pk, $coupon_code, $account_pk]);
        }

        // 쿠폰 사용 처리
        $PgCommon->query('UPDATE coupon SET coupon_use = coupon_use + 1 WHERE coupon_pk = $1', [$coupon_pk]);

        // 아이템이 존재하면 지급함.
        if (count($item_data) > 0) {
            foreach ($item_data as $m_item_pk => $count) {
                $Item->BuyItem($Session->lord['lord_pk'], $m_item_pk, $count);
            }
        }
    } catch (Throwable $e) {
        return $Render->nsXhrReturn('error', $e->getMessage());
    }

    return $Render->nsXhrReturn('success', null, $item_data);
}));
