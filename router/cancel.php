<?php
global $app, $Render, $i18n;

// TODO 취소기능은 사용안함.

$app->post('/api/cancel/Construction', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;

    $GoldPop = new GoldPop($Session, $PgGame);
    $BuildConstruction = new BuildConstruction($Session, $PgGame);
    $Resource = new Resource($Session, $PgGame);

    if ($params['position_type'] == 'I') {
        $Bd_c = new Bdic($Session, $PgGame, $Resource, $GoldPop);
    } else if ($params['position_type'] == 'O') {
        $Bd_c = new Bdoc($Session, $PgGame, $Resource, $GoldPop);
    } else {
        throw new ErrorHandler('error', '취소불가 - not found position_type');
    }

    $PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND type = $2', [$params['posi_pk'], 'C']);
    $buil_pk = $PgGame->FetchOne();

    if (!$buil_pk) {
        throw new ErrorHandler('error', '취소불가 - not found buil_pk');
    }

    $PgGame->query('SELECT buil_cons_pk FROM build_construction WHERE buil_pk = $1 AND position_type = $2 AND position = $3 AND status = \'P\'', [$buil_pk, $params['position_type'], $params['castle_pk']]);
    $queue_pk = $PgGame->FetchOne();
    if (!$queue_pk) {
        throw new ErrorHandler('error', '취소불가 - not found queue_pk');
    }

    // 공통
    $PgGame->Query('SELECT time_pk FROM timer WHERE queue_type = $1 AND queue_pk = $2', [$params['type'], $queue_pk]);
    $time_pk = $PgGame->FetchOne();

    if (!$time_pk) {
        throw new ErrorHandler('error', '취소불가 - not found time_pk');
    }

    $_NS_SQ_REFRESH_FLAG = true;

    $Timer = new Timer($Session, $PgGame);
    $result_arr = $Timer->cancel($time_pk);

    try {
        $PgGame->begin();

        $result_arr = $BuildConstruction->cancel($result_arr['queue_pk']);
        if (!$result_arr) {
            throw new Exception('build_construction buil_cons_pk찾지 못함');
        }

        $ret = $Bd_c->cancel($params['posi_pk'], $result_arr['position'], $result_arr['hero_pk'], $result_arr['queue_pk'], $time_pk);
        if (!$ret) {
            throw new Exception('not found building_out_castle infomation');
        }

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/cancel/Technique', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Hero = new Hero($Session, $PgGame);
    $BuildTechnique = new BuildTechnique($Session, $PgGame, $Hero);
    $Technique = new Technique($Session, $PgGame, $Resource, $GoldPop);

    $PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2', [$params['posi_pk'], $params['in_cast_pk']]);
    $buil_pk = $PgGame->FetchOne();

    if (!$buil_pk) {
        throw new ErrorHandler('error', '취소불가 - not found buil_pk');
    }

    $PgGame->query('SELECT buil_tech_pk FROM build_technique WHERE buil_pk = $1 AND status = $2', [$buil_pk, 'P']);
    $queue_pk = $PgGame->FetchOne();

    if (!$queue_pk) {
        throw new ErrorHandler('error', '취소불가 - not found queue_pk');
    }

    // 공통
    $PgGame->Query('SELECT time_pk FROM timer WHERE queue_type = $1 AND queue_pk = $2', [$params['type'], $queue_pk]);
    $time_pk = $PgGame->FetchOne();

    if (!$time_pk) {
        throw new ErrorHandler('error', '취소불가 - not found time_pk');
    }

    $_NS_SQ_REFRESH_FLAG = true;

    $Timer = new Timer($Session, $PgGame);
    $result_arr = $Timer->cancel($time_pk);

    try {
        $PgGame->begin();

        $result_arr = $BuildTechnique->cancel($result_arr['queue_pk']);
        if (!$result_arr) {
            throw new Exception('build_technique의 buil_tech_pk찾지 못함');
        }

        $Technique->cancel($params['posi_pk'], $result_arr['m_tech_pk'], $result_arr['current_level'], $result_arr['hero_pk'], $result_arr['queue_pk'], $time_pk);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/cancel/Fortification', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Terr = new Territory($Session, $PgGame);
    $Timer = new Timer($Session, $PgGame);
    $BuildFortification = new BuildFortification($Session, $PgGame, $Timer);
    $Fortification = new Fortification($Session, $PgGame, $Resource, $GoldPop, $Terr);

    $PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2', [$params['posi_pk'], $params['in_cast_pk']]);
    $buil_pk = $PgGame->FetchOne();

    if (!$buil_pk) {
        throw new ErrorHandler('error', '취소불가 - not found buil_pk');
    }

    $PgGame->query('SELECT buil_fort_pk FROM build_fortification WHERE buil_pk = $1 AND status = $2', [$buil_pk, 'P']);
    $queue_pk = $PgGame->FetchOne();

    if (!$queue_pk) {
        throw new ErrorHandler('error', '취소불가 - not found queue_pk');
    }

    // 공통
    $PgGame->Query('SELECT time_pk FROM timer WHERE queue_type = $1 AND queue_pk = $2', [$params['type'], $queue_pk]);
    $time_pk = $PgGame->FetchOne();

    if (!$time_pk) {
        throw new ErrorHandler('error', '취소불가 - not found time_pk');
    }

    $_NS_SQ_REFRESH_FLAG = true;

    $result_arr = $Timer->cancel($time_pk);

    try {
        $PgGame->begin();

        $result_arr = $BuildFortification->cancel($result_arr['queue_pk']);
        if (!$result_arr) {
            throw new Exception('build_fortification의  buil_fort_pk찾지 못함');
        }

        $ret = $Fortification->cancel($params['posi_pk'], $result_arr['m_fort_pk'], $result_arr['build_number'], $result_arr['queue_pk'], $time_pk);
        if (!$ret) {
            throw new Exception('not enough wall vacancy');
        }

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/cancel/Army', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Timer = new Timer($Session, $PgGame);
    $BuildArmy = new BuildArmy($Session, $PgGame, $Timer);
    $Army = new Army($Session, $PgGame, $Resource, $GoldPop);

    $PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2', [$params['posi_pk'], $params['in_cast_pk']]);
    $buil_pk = $PgGame->FetchOne();

    if (!$buil_pk) {
        throw new ErrorHandler('error', '취소불가 - not found buil_pk');
    }

    $query_params = Array($buil_pk, 'P');
    $PgGame->query('SELECT buil_army_pk FROM build_army WHERE buil_pk = $1 AND status = $2', $query_params);
    $queue_pk = $PgGame->FetchOne();

    if (!$queue_pk) {
        throw new ErrorHandler('error', '취소불가 - not found queue_pk');
    }

    // 공통
    $PgGame->Query('SELECT time_pk FROM timer WHERE queue_type = $1 AND queue_pk = $2', [$params['type'], $queue_pk]);
    $time_pk = $PgGame->FetchOne();

    if (!$time_pk) {
        throw new ErrorHandler('error', '취소불가 - not found time_pk');
    }

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    $_NS_SQ_REFRESH_FLAG = true;

    $result_arr = $Timer->cancel($time_pk);

    try {
        $PgGame->begin();

        $result_arr = $BuildArmy->cancel($result_arr['queue_pk']);
        if (!$result_arr) {
            throw new Exception('build_army의  buil_army_pk찾지 못함');
        }

        $Army->cancel($params['posi_pk'], $result_arr['m_army_pk'], $result_arr['build_number'], $result_arr['queue_pk'], $time_pk);
        $BuildArmy->queue($result_arr['buil_pk'], $params['posi_pk']);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/cancel/ArmyIdle', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Timer = new Timer($Session, $PgGame);
    $BuildArmy = new BuildArmy($Session, $PgGame, $Timer);
    $Army = new Army($Session, $PgGame, $Resource, $GoldPop);

    $result_arr = $BuildArmy->queuecancel($params['queue_pk']);
    if (!$result_arr) {
        throw new ErrorHandler('error', '취소불가 - not found queue_pk');
    }

    $Army->cancel($params['posi_pk'], $result_arr['m_army_pk'], $result_arr['build_number'], $params['queue_pk']);

    return $Render->nsXhrReturn('success');
}));


$app->post('/api/cancel/Medical', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Timer = new Timer($Session, $PgGame);
    $BuildMedical = new BuildMedical($Session, $PgGame, $Timer);
    $Medical = new Medical($Session, $PgGame);

    $PgGame->query('SELECT buil_pk FROM build WHERE posi_pk = $1 AND in_cast_pk = $2', [$params['posi_pk'], $params['in_cast_pk']]);
    $buil_pk = $PgGame->FetchOne();

    if (!$buil_pk) {
        throw new ErrorHandler('error', '취소불가 - not found buil_pk');
    }

    $PgGame->query('SELECT buil_medi_pk FROM build_medical WHERE buil_pk = $1 AND status = $2', [$buil_pk, 'P']);
    $queue_pk = $PgGame->FetchOne();

    if (!$queue_pk) {
        throw new ErrorHandler('error', '취소불가 - not found queue_pk');
    }

    // 공통
    $PgGame->Query('SELECT time_pk FROM timer WHERE queue_type = $1 AND queue_pk = $2', [$params['type'], $queue_pk]);
    $time_pk = $PgGame->FetchOne();

    if (!$time_pk) {
        throw new ErrorHandler('error', '취소불가 - not found time_pk');
    }

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    $_NS_SQ_REFRESH_FLAG = true;

    $result_arr = $Timer->cancel($time_pk);

    try {
        $PgGame->begin();

        $result_arr = $BuildMedical->cancel($result_arr['queue_pk']);
        if (!$result_arr) {
            throw new Exception('build_medical의  buil_medi_pk찾지 못함');
        }

        $Medical->cancel($params['posi_pk'], $result_arr['m_army_pk'], $result_arr['build_number'], $result_arr['queue_pk'], $time_pk);
        $BuildMedical->queue($result_arr['buil_pk'], $params['posi_pk']);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/cancel/MedicalIdle', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Timer = new Timer($Session, $PgGame);
    $BuildMedical = new BuildMedical($Session, $PgGame, $Timer);
    $Medical = new Medical($Session, $PgGame);

    $result_arr = $BuildMedical->queuecancel($params['queue_pk']);
    if (!$result_arr) {
        throw new ErrorHandler('error', '취소불가 - not found queue_pk');
    }

    $Medical->cancel($params['posi_pk'], $result_arr['m_army_pk'], $result_arr['build_number'], $params['queue_pk']);

    return $Render->nsXhrReturn('success');
}));
