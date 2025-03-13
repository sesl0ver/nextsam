<?php
global $app, $Render, $i18n;

$app->post('/api/setting/get', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Quest = new Quest($Session, $PgGame);

    // 퀘스트 확인
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'game_evaluate']);

    // 옵션 정보
    $PgGame->query('SELECT sound_bgm, sound_effect, volume_bgm, volume_effect, counsel_action, counsel_connect, building_title, alert_effect_ally, alert_effect_enemy FROM game_option WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    if (!$PgGame->fetch()) {
        throw new ErrorHandler('error', 'Error Occurred. [35001]'); // 게임 설정 로딩 중 오류 발생
    }

    $setting_data = $PgGame->row;
    $Session->sqAppend('LORD', ['setting' => $setting_data]);

    $PgGame->row['language'] = (isset($_SESSION['lang'])) ? $_SESSION['lang'] : CONF_I18N_DEFAULT_LANGUAGE;
    $PgGame->row['web_version'] = CONF_WEB_VERSION;

    return $Render->nsXhrReturn('success', null, $setting_data);
}));

$app->post('/api/setting/set', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $i18n = new i18n();

    $reload_game = false;
    if ($_SESSION['lang'] != $params['lang']) {
        $i18n->setLang($params['lang']);
        $reload_game = true;
    }

    $pn_night_srt = 21;
    $pn_night_end = 6;

    $buttons = ['sound_bgm', 'sound_effect', 'building_title', 'counsel_connect', 'counsel_action'];

    $query_param = [$Session->lord['lord_pk']];

    foreach ($buttons AS $k) {
        $query_param[] = ($params[$k] == 'Y') ? 'Y' : 'N';
    }
    $query_param[] = $params['volume_bgm'];
    $query_param[] = $params['volume_effect'];

    $PgGame->query('UPDATE game_option SET sound_bgm = $2, sound_effect = $3, building_title = $4, counsel_connect = $5, counsel_action = $6, volume_bgm = $7, volume_effect = $8,  update_dt = now() WHERE lord_pk = $1 RETURNING sound_bgm, sound_effect, volume_bgm, volume_effect, counsel_action, counsel_connect, building_title, alert_effect_ally, alert_effect_enemy', $query_param);

    $PgGame->fetch();
    $setting_data = $PgGame->row;
    $Session->sqAppend('LORD', ['setting' => $setting_data]);

    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'game_option']);

    // 로그 기록
    // $Log = new Log($Session, $PgGame);
    // $Log->setEtc($Session->lord['lord_pk'], $params['posi_pk'], 'game_option', json_encode($setting_data));

    $setting_data['restart'] = $reload_game;

    return $Render->nsXhrReturn('success', null, $setting_data);
}));

// 세팅 메뉴 이외에서 1개의 설정을 변경할 필요가 있을 경우
$app->post('/api/setting/update', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $query_param = [$Session->lord['lord_pk']];
    $buttons = ['alert_effect_ally', 'alert_effect_enemy'];
    $column = '';
    foreach ($buttons AS $k) {
        if (isset($params[$k])) {
            $column = $k;
            $query_param[] = ($params[$k] == 'Y') ? 'Y' : 'N';
        }
    }
    $PgGame->query("UPDATE game_option SET {$column} = $2, update_dt = now() WHERE lord_pk = $1 RETURNING {$column}", $query_param);
    $PgGame->fetch();
    $setting_data = $PgGame->row;
    $Session->sqAppend('LORD', ['setting' => $setting_data]);

    // 로그 기록
    // $Log = new Log($Session, $PgGame);
    // $Log->setEtc($Session->lord['lord_pk'], $params['posi_pk'], 'game_option', json_encode($setting_data));

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/setting/setToken', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // TODO 토큰을 이렇게 갱신하는게 맞나? - 옵션용으로만 사용해서 그런가?
    $PgGame->query('UPDATE game_option SET pn_token = $1, pn_token_up_dt = now() WHERE lord_pk = $2', [$params['token'], $Session->lord['lord_pk']]);

    return $Render->nsXhrReturn('success');
}));
