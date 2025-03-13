<?php
global $app, $Render, $i18n;

$app->post('/api/technique/upgrade', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['technique']);

    $Hero = new Hero($Session, $PgGame);
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $BuildTechnique = new BuildTechnique($Session, $PgGame, $Hero);
    $Timer = new Timer($Session, $PgGame);
    $Technique = new Technique($Session, $PgGame, $Resource, $GoldPop);
    $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
    $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);
    $Log = new Log($Session, $PgGame);
    $Troop = new Troop($Session, $PgGame);
    $Queue = new Queue($Session, $PgGame);

    $result_arr = $Technique->upgradePre($params['posi_pk'], $params['in_cast_pk'], $params['code'], $params['hero_pk']);

    if (!$result_arr) {
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    if (isset($result_arr['concurr_max'])) {
        throw new ErrorHandler('error', 'Error Occurred. [38001]'); // 이미 개발중인 기술이 있습니다.
    }

    // 영웅 명령 효과
    $capacities = $Effect->getHeroCapacityEffects($params['hero_pk']);
    $applies = $Effect->getHeroAppliedCommandEffects(PK_CMD_TECHN, $capacities);
    $ret = $Effect->getEffectedValue($params['posi_pk'], ['tech_build_time_decrease'], $result_arr['build_time'], $applies['all']);

    if (($result_arr['build_time'] * 0.1) > intval($ret['value'])) {
        $result_arr['build_time'] = intval($result_arr['build_time'] * 0.1);
    } else {
        $result_arr['build_time'] = intval($ret['value']);
    }

    $description = $_M['TECH'][$result_arr['m_tech_pk']]['title']. ' (Lv.'. ($result_arr['level']+1). ') - ' . $Troop->getHeroDesc($params['hero_pk']);

    $queue_pk = $BuildTechnique->set($result_arr['buil_pk'], $params['hero_pk'], $result_arr['m_tech_pk'], $result_arr['level'], $result_arr['build_time']);
    $Session->sqAppend('QUEUE', [$queue_pk => $Queue->getData('technique', $queue_pk)]);

    $Timer->set($params['posi_pk'], 'T', $queue_pk, 'U', $description, $result_arr['build_time'], $params['in_cast_pk']);

    // Log
    $time_pk = $PgGame->currSeq('timer_time_pk_seq');
    $Log->setTechnique($Session->lord['lord_pk'], $params['posi_pk'], 'upgrade', $_M['TECH'][$result_arr['m_tech_pk']]['title'], $queue_pk, $result_arr['buil_pk'], $params['hero_pk'], $result_arr['m_tech_pk'], null, null, $result_arr['build_time'], null, $result_arr['level'], $time_pk);

    return $Render->nsXhrReturn('success', null, ['build_time' => $result_arr['build_time']]);
}));

$app->post('/api/technique/current', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query("SELECT buil_tech_pk, status, m_tech_pk, priority, current_level, build_time, build_time_reduce, date_part('epoch', end_dt)::integer AS end_dt
FROM build_technique WHERE buil_pk = (SELECT buil_pk FROM build WHERE posi_pk = \$1 AND in_cast_pk = \$2) AND status IN ('P', 'I') ORDER BY priority", [$params['posi_pk'], $params['in_cast_pk']]);

    $ret = ['concurr' => ['count' => 0], 'queue' => ['count' => 0]];

    while ($PgGame->fetch()) {
        $r = &$PgGame->row;
        if ($r['status'] == 'P') {
            $ret['concurr'][$r['buil_tech_pk']] = $r;
            $ret['concurr']['end_dt'] = $r['end_dt'];
            $ret['concurr']['count']++;
        } else {
            $ret['queue'][$r['buil_tech_pk']] = $r;
            $ret['queue']['count']++;
        }
    }

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/technique/now', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Log = new Log($Session, $PgGame);

    global $NsGlobal, $_M;

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Technique = new Technique($Session, $PgGame, $Resource, $GoldPop);

    $result = $Technique->now($params['posi_pk'], $params['code'], $params['in_cast_pk']);
    if (! $result) {
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    $description = $_M['TECH'][$result['m_tech_pk']]['title'] . '[curr['. $result['current_level'] . '];update['. $result['next_level'] . '];];';
    $Log->setTechnique($Session->lord['lord_pk'], $Session->lord['main_posi_pk'], 'instant', $description, 0, 0, 0, $result['m_tech_pk'], null, null, 0, 0, $result['current_level'], 0);

    return $Render->nsXhrReturn('success');
}));