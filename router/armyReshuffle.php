<?php
global $app, $Render, $i18n;

// TODO 컨버전은 해놓았으나 사용하지 않는 API로 확인됨.

$app->post('/api/armyReshuffle/get', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 이미 재편성을 수행하였는지 체크.
    $PgGame->query('SELECT yn_army_point FROM my_event WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $yn_army_point = $PgGame->fetchOne();
    if (! isset($yn_army_point)) {
        $PgGame->query('INSERT INTO my_event (lord_pk) values ($1)', [$Session->lord['lord_pk']]);
        $PgGame->query('SELECT yn_army_point FROM my_event WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
        $yn_army_point = $PgGame->fetchOne();
    }
    if ($yn_army_point == 'N') {
        throw new ErrorHandler('error', '병력 재편성을 먼저 해주세요.');
    }

    // 병력 포인트 받아오기
    $PgGame->query('SELECT army_point FROM my_event WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $army_point = $PgGame->fetchOne();

    if ($army_point == 0) {
        throw new ErrorHandler('error', '재편성할 병력이 없거나 이미 재편성을 완료하였습니다.');
    }

    return $Render->nsXhrReturn('success', null, ['army_point' => $army_point]);
}));



$app->post('/api/armyReshuffle/exchangePoint', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 이미 재편성을 수행하였는지 체크.
    $PgGame->query('SELECT yn_army_point FROM my_event WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $yn_army_point = $PgGame->fetchOne();
    if (! isset($yn_army_point)) {
        $PgGame->query('INSERT INTO my_event (lord_pk) values ($1)', [$Session->lord['lord_pk']]);
        $PgGame->query('SELECT yn_army_point FROM my_event WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
        $yn_army_point = $PgGame->fetchOne();
    }
    if ($yn_army_point == 'Y') {
        throw new ErrorHandler('error', '병력 재편성이 이미 완료되었습니다.');
    }

    //전체 영지를 체크하기 위해 모든 영지의 posi_pk를 알아옴.
    $PgGame->query('SELECT posi_pk FROM position WHERE lord_pk = $1 AND type = $2', [$Session->lord['lord_pk'], 'T']);
    $all_posi_pk = [];

    // 배열로 저장 - 문자열이기 때문에 ''로 감싸줌.
    while ($PgGame->fetch()) {
        $all_posi_pk[] = "'$PgGame->row['posi_pk']'";
    }

    $all_posi_pk_str = implode(',', $all_posi_pk);

    // 출병 중인 병력이 있다면
    $PgGame->query('SELECT count(src_lord_pk) FROM troop WHERE src_lord_pk = $1', [$Session->lord['lord_pk']]);
    $ret = $PgGame->fetchOne();
    if ($ret) {
        throw new ErrorHandler('error', '출병중이거나 외부 주둔중인 부대가 있을때는 포인트로 환산이 불가능합니다.');
    }

    // 병력 포인트로 환산하기!!
    $PgGame->query("SELECT posi_pk, ((adv_catapult * 25) + (archer * 4) + (armed_horseman * 8) + (armed_infantry * 6) + (battering_ram * 15) + 
                                               (bowman * 10) + (catapult * 20) + (horseman * 6) + infantry + (pikeman * 2) + scout + (spearman * 2) +
                                               (transporter * 5) + worker) as point FROM army WHERE posi_pk IN ($all_posi_pk_str)");
    $PgGame->fetchAll();
    $pointArr = $PgGame->rows;

    $point = 0;
    foreach ($pointArr as $army) {
        $point += $army['point'];
    }

    // 병력 포인트 환산후 차감
    if ($point < 1) {
        throw new ErrorHandler('error', '포인트로 환산 가능한 병력이 존재하지 않습니다.');
    }

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Army = new Army($Session, $PgGame, $Resource, $GoldPop);

    try {
        $PgGame->begin();
        $_NS_SQ_REFRESH_FLAG = true;

        // 포인트 지급
        $PgGame->query('UPDATE my_event SET army_point = $2, yn_army_point = $3 WHERE lord_pk = $1', [$Session->lord['lord_pk'], $point, 'Y']);
        if ($PgGame->getAffectedRows() == 0) {
            throw new Exception('병력 재편성 중 오류가 발생했습니다. 다시 시도해주세요.');
        }

        foreach($pointArr as $army) {
            $ret = $Army->deadAllArmy($army['posi_pk'], 'point['.$army['point'].'];');
            if (!$ret) {
                throw new Exception('병력 재편성 중 오류가 발생했습니다. 다시 시도해주세요.');
            }
        }

        $PgGame->commit();
    } catch(Exception $e) {
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    $Army->get($params['posi_pk']);

    return $Render->nsXhrReturn('success', null, ['point' => $point]);
}));



$app->post('/api/armyReshuffle/exchangeArmy', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 이미 재편성을 수행하였는지 체크.
    $PgGame->query('SELECT yn_army_point FROM my_event WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $yn_army_point = $PgGame->fetchOne();
    if (! isset($yn_army_point)) {
        $PgGame->query('INSERT INTO my_event (lord_pk) values ($1)', [$Session->lord['lord_pk']]);
        $PgGame->query('SELECT yn_army_point FROM my_event WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
        $yn_army_point = $PgGame->fetchOne();
    }

    // 포인트 체크
    $check_point = ['worker' => 1,'pikeman' => 2,'scout' => 1,'horseman' => 6,'armed_infantry' => 6,'bowman' => 10,'catapult' => 20,
                    'infantry' => 1,'spearman' => 2,'archer' => 4,'transporter' => 5,'armed_horseman' => 8,'battering_ram' => 15,'adv_catapult' => 25];
    $army_dic = [];
    $sum_point = 0;
    foreach($params as $k => $v) {
        if(! isset($check_point[$k])) {
            continue;
        }
        $sum_point += ($v * $check_point[$k]);
        $army_dic[$k] = ($v * $check_point[$k]);
    }

    if ($sum_point < 1) {
        throw new ErrorHandler('error', '병력 재편성 중 오류가 발생했습니다. 다시 시도해주세요.');
    }

    // 병력 포인트 받아오기
    $PgGame->query('SELECT army_point FROM my_event WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $army_point = $PgGame->fetchOne();
    if ($army_point != $sum_point) {
        throw new ErrorHandler('error', '병과 편성 포인트에 맞춰 재편성을 진행하세요.');
    }

    // 병력 재편성 후 포인트 차감
    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Army = new Army($Session, $PgGame, $Resource, $GoldPop);

    try {
        $PgGame->begin();
        $_NS_SQ_REFRESH_FLAG = true;

        $army_arr = [];
        $army_arr['worker'] = $params['worker'];
        $army_arr['infantry'] = $params['infantry'];
        $army_arr['scout'] = $params['scout'];
        $army_arr['pikeman'] = $params['pikeman'];
        $army_arr['spearman'] = $params['spearman'];
        $army_arr['transporter'] = $params['transporter'];
        $army_arr['horseman'] = $params['horseman'];
        $army_arr['archer'] = $params['archer'];
        $army_arr['armed_infantry'] = $params['armed_infantry'];
        $army_arr['armed_horseman'] = $params['armed_horseman'];
        $army_arr['bowman'] = $params['bowman'];
        $army_arr['catapult'] = $params['catapult'];
        $army_arr['battering_ram'] = $params['battering_ram'];
        $army_arr['adv_catapult'] = $params['adv_catapult'];

        $ret = $Army->returnArmy($Session->lord['main_posi_pk'], $army_arr, 'reshuffle_event_army');
        if (!$ret) {
            throw new Exception('병력 재편성 중 오류가 발생했습니다. 다시 시도해주세요.');
        }

        // 포인트 차감
        $PgGame->query('UPDATE my_event SET army_point = $2, last_event_dt = now() WHERE lord_pk = $1', [$Session->lord['lord_pk'], 0]);
        if ($PgGame->getAffectedRows() == 0) {
            throw new Exception('병력 재편성 중 오류가 발생했습니다. 다시 시도해주세요.');
        }

        $PgGame->commit();
    } catch(Exception $e) {
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    $Army->get($Session->lord['main_posi_pk']);

    return $Render->nsXhrReturn('success');
}));