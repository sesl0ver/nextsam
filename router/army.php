<?php
global $app, $Render, $i18n;

$app->post('/api/army/upgrade', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['build_number', 'in_cast_pk', 'code']);
    $Session = new Session();
    if ($params['build_number'] < 0) {
        throw new ErrorHandler('error', $i18n->t('msg_plz_enter_number_training_army')); // 훈련 인원수를 입력하세요.
    }
    $PgGame = new Pg('DEFAULT');

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Queue = new Queue($Session, $PgGame);

    global $NsGlobal, $_M;

    $Army = new Army($Session, $PgGame, $Resource, $GoldPop);
    $result_arr = $Army->upgradePre($params['posi_pk'], $params['in_cast_pk'], $params['code'], $params['build_number']);
    if (! $result_arr) {
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    // 단축 효과 적용 - 제련, 주조 처리 (병과 유형)
    if ($Army->isSiege($params['code'])) {
        $effect_types = ['siege_army_build_time_decrease', 'all_army_build_time_decrease_re'];
        $effect_type = 'siege_army_build_time_decrease';
    } else {
        $effect_types = ['normal_army_build_time_decrease', 'army_build_time_decrease_re', 'all_army_build_time_decrease_re'];
        $effect_type = 'normal_army_build_time_decrease';
    }

    $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
    $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);
    $ret = $Effect->getEffectedValue($params['posi_pk'], $effect_types, $result_arr['build_time']);

    $add_decrease = 0;

    // 훈련소 영웅배속 효과 적용 (훈련소 별로 적용을 위해 별도 처리)
    $PgGame->query('SELECT SUM(effect_value) AS sum FROM territory_hero_assign t1, m_hero_assign_effect t2 WHERE t1.m_hero_assi_pk = t2.m_hero_assi_pk AND t1.posi_pk = $1  AND t1.in_cast_pk = $2 AND t2.effect_type = $3', [$params['posi_pk'], $params['in_cast_pk'], 'avg_army_build_time_decrease']);
    if ($PgGame->fetch()) {
        $add_pct = $PgGame->row['sum'];
    } else {
        throw new ErrorHandler('error', $i18n->t('msg_not_found_assign_hero_error')); // 배속된 영웅 정보를 찾을 수 없습니다.
    }

    // 훈련소 영웅배속 기술 효과 적용
    $add_skl_pct = 0;
    $PgGame->query('SELECT SUM(effect_value) AS sum FROM territory_hero_skill t1, m_hero_skill t2 WHERE t1.m_hero_skil_pk = t2.m_hero_skil_pk AND t1.posi_pk = $1  AND t1.in_cast_pk = $2 AND t2.effect_type = $3', [$params['posi_pk'], $params['in_cast_pk'], $effect_type]);
    if ($PgGame->fetch()) {
        $add_skl_pct = $PgGame->row['sum'];
    }

    $add_decrease = intval($result_arr['build_time'] * ($add_pct + $add_skl_pct) * 0.01);

    // 효과중 아이템과 태학 기술만 적용
    $add_decrease += intval($result_arr['build_time'] * ($ret['effected_values']['item'] + $ret['effected_values']['tech']) * 0.01);
    if (($result_arr['build_time'] * 0.1) > (intval($result_arr['build_time'] - $add_decrease))) {
        $result_arr['build_time'] = intval($result_arr['build_time'] * 0.1);
    } else {
        $result_arr['build_time'] = $result_arr['build_time'] - $add_decrease;
    }

    $NsGlobal->requireMasterData(['army']);

    $description = "{$result_arr['m_army_pk']}:{$result_arr['build_number']}";

    $Timer = new Timer($Session, $PgGame);
    $BuildArmy = new BuildArmy($Session, $PgGame, $Timer);

    $queue_pk = $BuildArmy->set($result_arr['buil_pk'], $result_arr['m_army_pk'], $result_arr['build_number'], $result_arr['build_time'], $result_arr['build_status']);

    $Session->sqAppend('QUEUE', [$queue_pk => $Queue->getData('army', $queue_pk)]);

    $Log = new Log($Session, $PgGame);
    $log_description = "{$result_arr['m_army_pk']}[{$result_arr['build_number']}];";
    if($result_arr['build_status'] == 'P') {
        $Timer->set($params['posi_pk'], 'A', $queue_pk, 'U', $description, $result_arr['build_time'], $params['in_cast_pk']);

        //Log
        $time_pk = $PgGame->currSeq('timer_time_pk_seq');
        $Log->setArmy($Session->lord['lord_pk'], $params['posi_pk'], 'training', $log_description, $queue_pk, $result_arr['buil_pk'], $result_arr['m_army_pk'], null, null, $result_arr['build_time'], null, $result_arr['build_number'], $time_pk);
    } else {
        $Log->setArmy($Session->lord['lord_pk'], $params['posi_pk'], 'training_queue', $log_description, $queue_pk, $result_arr['buil_pk'], $result_arr['m_army_pk'], null, null, $result_arr['build_time'], null, $result_arr['build_number']);
    }

    $Army->get($params['posi_pk']);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/army/current', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['in_cast_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Timer = new Timer($Session, $PgGame);
    $BuildArmy = new BuildArmy($Session, $PgGame, $Timer);
    $ret = $BuildArmy->getCurrent($params['posi_pk'], $params['in_cast_pk']);

    if ($ret) {
        $Session->sqAppend('BUIL_IN_CAST', [$_POST['in_cast_pk'] => ['current'  => $ret]], null, $Session->lord['lord_pk'], $params['posi_pk']);
    }

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/army/disperse', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['disperse_number', 'code']);
    $Session = new Session();
    if ($params['disperse_number'] < 0) {
        throw new ErrorHandler('error', $i18n->t('msg_plz_enter_number_army')); // 병력수를 입력하세요.
    }
    $PgGame = new Pg('DEFAULT');

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);

    $Army = new Army($Session, $PgGame, $Resource, $GoldPop);
    $ret = $Army->disperse($params['posi_pk'], $params['code'], $params['disperse_number']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/army/now', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['build_number', 'in_cast_pk', 'code']);
    $Session = new Session();
    if ($params['build_number'] < 0) {
        throw new ErrorHandler('error', $i18n->t('msg_plz_enter_number_army')); // 병력수를 입력하세요.
    }
    $PgGame = new Pg('DEFAULT');

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);

    $Army = new Army($Session, $PgGame, $Resource, $GoldPop);

    $result = $Army->now($params['posi_pk'], $params['in_cast_pk'], $params['code'], $params['build_number']);
    if (! $result) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/army/queue', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['in_cast_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Timer = new Timer($Session, $PgGame);
    $BuildArmy = new BuildArmy($Session, $PgGame, $Timer);
    $ret = $BuildArmy->getQueueCurrent($params['posi_pk'], $params['in_cast_pk']);

    return $Render->nsXhrReturn('success', null, $ret);
}));