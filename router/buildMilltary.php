<?php
global $app, $Render, $i18n;

$app->post('/api/military/myMoveTroops', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $rows = $Troop->getMyMoveTroops($params['posi_pk']);

    return $Render->nsXhrReturn('success', null, $rows);
}));

$app->post('/api/military/enemyMarchTroops', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $rows = $Troop->getEnemyMarchTroops($params['posi_pk']);

    return $Render->nsXhrReturn('success', null, $rows);
}));

$app->post('/api/military/allyCampArmy', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $rows = $Troop->getAllyCampArmy($params['posi_pk']);

    return $Render->nsXhrReturn('success', null, $rows);
}));

$app->post('/api/military/myCampTroops', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $rows = $Troop->getMyCampTroops($params['posi_pk']);

    return $Render->nsXhrReturn('success', null, $rows);
}));

$app->post('/api/military/gate', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT status_gate FROM territory WHERE posi_pk = $1', [$params['posi_pk']]);
    $status_gate = $PgGame->fetchOne();

    $Item = new Item($Session, $PgGame);
    if ($status_gate == 'O') {
        $next = 'C';
        $next_message = $i18n->t('msg_castle_gate_close'); // 성문을 폐쇄하였습니다.<br /><br />(성문 개방 효과를 받을 수 없게되며, 성문 폐쇄 상태에선 방어부대가 출전하지 않습니다.)

        $Item->delGateBuff($Session->lord['lord_pk'], $params['posi_pk']);
    } else {
        $next = 'O';
        $next_message = $i18n->t('msg_castle_gate_open'); // 성문을 개방하였습니다.<br /><br />(성문 개방 효과를 받게되며, 성문 개방 상태에선 방어부대가 출전해 항전합니다.);

        $Item->setGateBuff($Session->lord['lord_pk'], $params['posi_pk']);
    }

    $PgGame->query('UPDATE territory SET status_gate = $1 WHERE posi_pk = $2', [$next, $params['posi_pk']]);

    $Territory = new Territory($Session, $PgGame);
    $Territory->get($params['posi_pk'], ['status_gate']);

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'castle_gate', 'open' => $next]);

    return $Render->nsXhrReturn('success', null, $next_message);
}));

$app->post('/api/military/enemyMarchInfo', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['troo_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    if ($params['troo_pk'] < 1) {
        throw new ErrorHandler('error', 'Invalid request.');
    }

    $PgGame->query('SELECT captain_hero_pk, captain_desc,director_hero_pk, director_desc, staff_hero_pk, staff_desc,
	army_worker, army_infantry, army_pikeman, army_scout, army_spearman, army_armed_infantry, army_archer,
	army_horseman, army_armed_horseman, army_transporter, army_bowman, army_battering_ram, army_catapult, army_adv_catapult
FROM troop WHERE troo_pk = $1', [$params['troo_pk']]);

    if (! $PgGame->fetch()) {
        throw new ErrorHandler('error', '현재 진군 중이 아닌 부대입니다.');
    }

    $enemy_info = $PgGame->row;

    $PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$enemy_info['captain_hero_pk']]);
    $enemy_info['captain'] = $PgGame->fetchOne();

    if ($enemy_info['director_hero_pk'] != null) {
        $PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$enemy_info['director_hero_pk']]);
        $enemy_info['director'] = $PgGame->fetchOne();
    }

    if ($enemy_info['staff_hero_pk'] != null) {
        $PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$enemy_info['staff_hero_pk']]);
        $enemy_info['staff'] = $PgGame->fetchOne();
    }

    $army_info = [];
    foreach($enemy_info as $k => $v) {
        if (preg_match('/^army_/', $k) > 0) {
            $army_info[$k] = $v;
            unset($enemy_info[$k]);
        }
    }
    $Troop = new Troop($Session, $PgGame);
    $enemy_info['army'] = $Troop->getNumberToTextDesc($army_info);

    return $Render->nsXhrReturn('success', null, $enemy_info);
}));