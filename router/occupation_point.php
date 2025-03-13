<?php
global $app, $Render, $i18n;

$app->post('/api/occupation/rank', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Redis = new RedisCache();

    $rankings = $Redis->zRange("ranking:alliance:occupation_point");
    $total_count = count($rankings);
    foreach ($rankings as &$rank) {
        $rank = json_decode($rank, true);
    }

    $my_alliance = null;
    if ($Session->lord['alli_pk']) {
        $PgGame->query("select l.alli_pk, a.title, a.lord_name, sum(op.point) as ally_point from lord as l left join alliance as a on l.alli_pk = a.alli_pk left join occupation_point as op on op.lord_pk = l.lord_pk where l.alli_pk = {$Session->lord['alli_pk']} group by l.alli_pk, a.title, a.lord_name;");
        $PgGame->fetch();
        $my_alliance = $PgGame->row;
    }

    if (date('w') == 2) {
        $end_time = strtotime("next wednesday") - time();
    } else {
        $end_time = strtotime("next tuesday") - time();
    }

    return $Render->nsXhrReturn('success', null, ['rankings' => $rankings, 'total_count' => $total_count, 'my' => $my_alliance, 'end_time' => $end_time]);
}));

$app->post('/api/occupation/my', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Lord = new Lord($Session, $PgGame);

    $PgGame->query('SELECT rank FROM occupation_personal_reward WHERE lord_pk = $1',[$Session->lord['lord_pk']]);
    $PgGame->fetchAll();
    $personal_reward = [];
    foreach ($PgGame->rows as $row) {
        $personal_reward[] = $row['rank'];
    }

    $now_time = Useful::microTimeFloat();
    $PgGame->query("select point, date_part('epoch', update_dt + interval '5 minutes')::integer as limit_dt from occupation_point where lord_pk = {$Session->lord['lord_pk']};");
    $PgGame->fetch();
    $my = $PgGame->row;
    if (is_array($my) && ($my['limit_dt'] - $now_time) <= 0) {
        $my = $Lord->refreshOccupationPoint($Session->lord['lord_pk']);
    }

    if (date('w') == 2) {
        $end_time = strtotime("next wednesday") - time();
    } else {
        $end_time = strtotime("next tuesday") - time();
    }

    return $Render->nsXhrReturn('success', null, ['point' => $my['point'] ?? 0, 'limit_dt' => $my['limit_dt'] ?? time(), 'reward' => $personal_reward, 'end_time' => $end_time]);
}));

$app->post('/api/occupation/personalReward', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Item = new Item($Session, $PgGame);

    $PgGame->query('SELECT count(*) FROM occupation_personal_reward WHERE lord_pk = $1 AND rank = $2',[$Session->lord['lord_pk'], $params['rank']]);
    if ($PgGame->fetchOne() > 0) {
        throw new ErrorHandler('error', $i18n->t('msg_already_received_reward')); // 이미 받은 보상입니다.
    }

    global $_M;
    $NsGlobal = NsGlobal::getInstance();
    $NsGlobal->requireMasterData(['occupation_reward']);

    $m = $_M['OCCUPATION_REWARD']['personal'][$params['rank']];

    // 보상 지급
    $reward_items = explode(',', $m['reward_item']);
    foreach ($reward_items as $reward_item) {
        [$m_item_pk, $item_count] = explode(':', $reward_item);
        $Item->BuyItem($Session->lord['lord_pk'], $m_item_pk, $item_count);
    }

    $PgGame->query('INSERT INTO occupation_personal_reward (lord_pk, rank) VALUES ($1, $2)',[$Session->lord['lord_pk'], $params['rank']]);

    $PgGame->query('SELECT rank FROM occupation_personal_reward WHERE lord_pk = $1',[$Session->lord['lord_pk']]);
    $PgGame->fetchAll();
    $personal_reward = [];
    foreach ($PgGame->rows as $row) {
        $personal_reward[] = $row['rank'];
    }

    return $Render->nsXhrReturn('success', null, ['reward' => $personal_reward]);
}));

