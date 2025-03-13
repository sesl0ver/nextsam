<?php
global $app, $Render, $i18n;

$app->post('/api/medical/current', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['in_cast_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Timer = new Timer($Session, $PgGame);
    $BuildMedical = new BuildMedical($Session, $PgGame, $Timer);
    $ret = $BuildMedical->getCurrent($params['posi_pk'], $params['in_cast_pk']);

    $Medical = new Medical($Session, $PgGame);
    $row = $Medical->getInjuryArmy($params['posi_pk']);
    $ret['injury_army'] = $row;

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/medical/queue', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Timer = new Timer($Session, $PgGame);
    $BuildMedical = new BuildMedical($Session, $PgGame, $Timer);
    $ret = $BuildMedical->getCurrent($params['posi_pk'], $params['in_cast_pk']);

    return $Render->nsXhrReturn('success', null, $ret);
}));

// 치료
$app->post('/api/medical/treatment', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['build_number', 'in_cast_pk', 'code']);
    $Session = new Session();
    if ($params['build_number'] < 0) {
        throw new ErrorHandler('error', $i18n->t('msg_need_medical_army_number')); // 치료 인원수를 입력하세요.
    }
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal, $_M, $_NS_SQ_REFRESH_FLAG;
    $NsGlobal->requireMasterData(['army']);

    $Medical = new Medical($Session, $PgGame);
    $Queue = new Queue($Session, $PgGame);

    // 성벽에 배치된 영웅 pk 가져오기
    $PgGame->query('SELECT assign_hero_pk FROM building_in_castle WHERE in_castle_pk = $1', [$params['in_cast_pk']]);
    $hero_pk = $PgGame->fetchOne();

    $result_arr = $Medical->treatmentPre($params['posi_pk'], $params['in_cast_pk'], $params['code'], $params['build_number'], $hero_pk);
    if (!$result_arr) {
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    // 영웅 명령 효과
    $PgGame->query('SELECT assign_hero_pk FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$params['posi_pk'], PK_BUILDING_MEDICAL]);
    $hero_pk = $PgGame->fetchOne();

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
    $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);
    $capacities = $Effect->getHeroCapacityEffects($hero_pk);
    $applies = $Effect->getHeroAppliedCommandEffects(PK_CMD_TREAT, $capacities);
    $ret = $Effect->getEffectedValue($params['posi_pk'], ['treatment_time_decrease'], $result_arr['build_time'], $applies['all']);
    $result_arr['build_time'] = intval($ret['value']);

    $Timer = new Timer($Session, $PgGame);
    $BuildMedical = new BuildMedical($Session, $PgGame, $Timer);
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        $queue_pk = $BuildMedical->set($result_arr['buil_pk'], $result_arr['m_army_pk'], $result_arr['build_number'], $result_arr['build_time'], $result_arr['build_status']);

        if (! $queue_pk) {
            throw new Exception('An error has occurred, please try again. (1)');
        }

        // 의료원은 미리 병력 빼줌
        $ret = $PgGame->query('UPDATE medical_army SET '.$result_arr['code'].' = '.$result_arr['code'].' - $2 WHERE posi_pk = $1', [$params['posi_pk'], $result_arr['build_number']]);
        if (! $ret) {
            throw new Exception('An error has occurred, please try again. (2)');
        }

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();

        // 에러 메시지 추가
        // qbw_error($e->getMessage());

        //dubug_mesg남기기
        // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$params['posi_pk'].'];hero_pk['.$params['hero_pk'].'];');

        throw new ErrorHandler('error', $e->getMessage());
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    $description = "{$result_arr['m_army_pk']}:{$result_arr['build_number']}";

    $Session->sqAppend('QUEUE', [$queue_pk => $Queue->getData('medical', $queue_pk)]);

    $Quest = new Quest($Session, $PgGame);
    $Log = new Log($Session, $PgGame);
    $log_description = "{$result_arr['m_army_pk']}[{$result_arr['build_number']}];";
    if($result_arr['build_status'] == 'P') {
        $Timer->set($params['posi_pk'], 'M', $queue_pk, 'U', $description, $result_arr['build_time'], $params['in_cast_pk']);

        $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'medical', 'type' => 'army']);

        //Log
        $time_pk = $PgGame->currSeq('timer_time_pk_seq');
        $Log->setArmy($Session->lord['lord_pk'], $params['posi_pk'], 'medical', $log_description, $queue_pk, $result_arr['buil_pk'], $result_arr['m_army_pk'], null, null, $result_arr['build_time'], null, $result_arr['build_number'], $time_pk);
    } else {
        $Log->setArmy($Session->lord['lord_pk'], $params['posi_pk'], 'medical_queue', $log_description, $queue_pk, $result_arr['buil_pk'], $result_arr['m_army_pk'], null, null, $result_arr['build_time'], null, $result_arr['build_number']);
    }

    $Medical->getInjuryArmy($params['posi_pk']);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/medical/disperse', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['disperse_number', 'code']);
    $Session = new Session();
    if ($params['disperse_number'] < 0) {
        throw new ErrorHandler('error', $i18n->t('msg_need_army_number')); // 인원수를 입력하세요.
    }
    $PgGame = new Pg('DEFAULT');

    $Medical = new Medical($Session, $PgGame);
    $ret = $Medical->disperse($params['posi_pk'], $params['code'], $params['disperse_number']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/medical/now', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['build_number', 'in_cast_pk', 'code']);
    $Session = new Session();
    if ($params['build_number'] < 0) {
        throw new ErrorHandler('error', $i18n->t('msg_need_medical_army_number')); // 치료 인원수를 입력하세요.
    }
    $PgGame = new Pg('DEFAULT');

    $Medical = new Medical($Session, $PgGame);
    $ret = $Medical->now($params['posi_pk'], $params['in_cast_pk'], $params['code'], $params['build_number']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));