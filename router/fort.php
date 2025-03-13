<?php
global $app, $Render, $i18n;

$app->post('/api/fort/upgrade', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['build_number', 'in_cast_pk', 'code']);
    $Session = new Session();
    if ($params['build_number'] < 0) {
        throw new ErrorHandler('error', $i18n->t('msg_need_amount')); // 개수를 입력하세요.
    }
    $PgGame = new Pg('DEFAULT');

    $Terr = new Territory($Session, $PgGame);
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Fortification = new Fortification($Session, $PgGame, $Resource, $GoldPop, $Terr);
    $Queue = new Queue($Session, $PgGame);

    // 성벽에 배치된 영웅 pk 가져오기
    $PgGame->query('SELECT assign_hero_pk FROM building_in_castle WHERE in_castle_pk = $1', [$params['in_cast_pk']]);
    $hero_pk = $PgGame->fetchOne();

    $result_arr = $Fortification->upgradePre($params['posi_pk'], $params['in_cast_pk'], $params['code'], $params['build_number'], $hero_pk);

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['fortification']);
    if (! $result_arr) {
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    //  단축 효과 적용
    $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
    $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);
    $ret = $Effect->getEffectedValue($params['posi_pk'], ['fort_build_time_decrease'], $result_arr['build_time']);

    $result_arr['build_time'] = intval($ret['value']);

    $description = $_M['FORT'][$result_arr['m_fort_pk']]['title'] . ' (' . $result_arr['build_number'] . ')';

    $Timer = new Timer($Session, $PgGame);
    $BuildFortification = new BuildFortification($Session, $PgGame, $Timer);
    $queue_pk = $BuildFortification->set($result_arr['buil_pk'], $result_arr['m_fort_pk'], $result_arr['build_number'], $result_arr['build_time']);
    $Session->sqAppend('QUEUE', [$queue_pk => $Queue->getData('fortification', $queue_pk)]);

    $Timer->set($params['posi_pk'], 'F', $queue_pk, 'U', $description, $result_arr['build_time'], $params['in_cast_pk']);

    //Log
    $log_description = $_M['FORT'][$result_arr['m_fort_pk']]['code'] . '[curr['.$result_arr['current_number'].'];build['. $result_arr['build_number'].'];];';
    $time_pk = $PgGame->currSeq('timer_time_pk_seq');
    $Log = new Log($Session, $PgGame);
    $Log->setFortification($Session->lord['lord_pk'], $params['posi_pk'], 'upgrade', $log_description, $queue_pk, $result_arr['buil_pk'], $result_arr['m_fort_pk'], null, null, $result_arr['build_time'], null, $result_arr['build_number'], $time_pk);

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/fort/current', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query("SELECT buil_fort_pk, status, m_fort_pk, priority, build_number, build_time, build_time_reduce, date_part('epoch', end_dt)::integer AS end_dt
FROM build_fortification WHERE buil_pk = (SELECT buil_pk FROM build WHERE posi_pk = \$1 AND in_cast_pk = \$2) AND status IN ('P', 'I') ORDER BY priority", [$params['posi_pk'], $params['in_cast_pk']]);

    $ret = ['concurr' => ['count' => 0], 'queue' => ['count' => 0]];

    while ($PgGame->fetch()) {
        $r = &$PgGame->row;

        if ($r['status'] == 'P') {
            $ret['concurr'][$r['buil_fort_pk']] = $r;
            $ret['concurr']['end_dt'] = $r['end_dt'];
            $ret['concurr']['count']++;
        } else {
            $ret['queue'][$r['buil_fort_pk']] = $r;
            $ret['queue']['count']++;
        }
    }

    $Troop = new Troop($Session, $PgGame);
    $army_info = $Troop->getFightingSpirit($params['posi_pk']);
    if ($army_info['fightingSpirit'] > 100)
        $army_info['fightingSpirit'] = 100;
    $ret['army']['fightingSpirit'] = $army_info['fightingSpirit'];

    // 총병력
    $army_pop = $army_info['armyPop'];
    $ret['army']['total_army'] = $army_pop['population'];

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['army']);

    // 아군 병력
    $army_arr = [];
    $PgGame->query('SELECT * FROM army WHERE posi_pk = $1', [$params['posi_pk']]);
    $PgGame->fetch();
    $r = $PgGame->row;
    foreach ($_M['ARMY_C'] AS $k => $v) {
        $army_arr[$k] = $r[$k];
    }

    $army_pop = $Troop->getArmyPop($army_arr);
    $ret['army']['my_army'] = $army_pop['population'];
    // 동맹군 병력
    $ret['army']['alli'] = $ret['army']['total_army'] - $ret['army']['my_army'];
    $Session->sqAppend('PUSH', ['TROOP_INFO' => $ret['army']], null, $Session->lord['lord_pk'], $params['posi_pk']);

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/fort/disperse', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['disperse_number', 'code']);
    $Session = new Session();
    if ($params['disperse_number'] < 0) {
        throw new ErrorHandler('error', $i18n->t('msg_need_amount')); // 개수를 입력하세요.
    }
    $PgGame = new Pg('DEFAULT');

    $Terr = new Territory($Session, $PgGame);
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Fortification = new Fortification($Session, $PgGame, $Resource, $GoldPop, $Terr);
    $ret = $Fortification->disperse($params['posi_pk'], $params['code'], $params['disperse_number']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));
