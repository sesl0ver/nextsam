<?php
global $app, $Render, $i18n;

$app->get('/api/server/list', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $PgCommon = new Pg('COMMON');

    $server_list = [];
    $servers_config = [];

    $auth_ips = [];
    $auth_ips[] = '__empty__';
    $auth_ips[] = '192.168.0.203'; // 개발망
    // $auth_ips[] = '61.35.101.131';

    $ip = trim(Useful::getRealClientIp());
    $query_param = null;
    if ($ip && in_array($ip, $auth_ips)) {
        // 개발자용 모든 서버리스트 보임.
        $query_string = 'SELECT server_pk, server_pre_url, display_name, flag_new, flag_recommend, db_ip, db_port, db_account, db_password FROM server ORDER BY order_num DESC';
    } else {
        // 유저용 visible 값이 y 인 경우만 보임.
        $append_string = '';
        if (CONF_TEST_SERVER_PK_ONLY) {
            $append_string = ' AND server_pk = \'test\'';
        }
        $query_string = "SELECT server_pk, server_pre_url, display_name, flag_new, flag_recommend, db_ip, db_port, db_account, db_password FROM server WHERE visible = $1{$append_string} ORDER BY order_num DESC";
        $query_param = ['Y'];
    }
    $PgCommon->query($query_string, $query_param);
    $select_server_pk = null;
    while ($PgCommon->fetch()) {
        $row = $PgCommon->row;
        if (! $select_server_pk) {
            $select_server_pk = $row['server_pk'];
        }
        $server_list[] = ['server_pk' => $row['server_pk'], 'server_pre_url' => $row['server_pre_url'], 'display_name' => $row['display_name'], 'flag_new' => $row['flag_new'], 'flag_recommend' => $row['flag_recommend']];
        $servers_config[$row['server_pk']] = $row;
    }

    $_add_data = [];
    $_add_data['list'] = $server_list;
    $_add_data['lord'] = null;

    /* ************************************************** */

    if (! isset($params['uuid'])) {
        return $Render->nsXhrReturn('failed', $i18n->t('msg_not_found_uuid_retry_please'));
    }

    $platform = (CONF_ONLY_PLATFORM_MODE !== true) ? 'TEST' : $params['platform'];
    $Auth = new Auth($PgCommon, $params['uuid'], $params['lc'], $params['token'], $platform);
    $Auth->getAccount();
    if ($Auth->need_membership !== false) {
        return $Render->nsXhrReturn('failed', $i18n->t('msg_app_retry_please'));
    }

    $account_id = $Auth->account_info['account_pk'];
    $web_channel = $Auth->lc;

    /* ************************************************** */

    if (isset($params['select_server_pk'])) {
        $select_server_pk = $params['select_server_pk'];
    }

    $prefix = 'GAME';
    define($prefix . '_PGSQL_IP', $servers_config[$select_server_pk]['db_ip']);
    define($prefix . '_PGSQL_PORT', $servers_config[$select_server_pk]['db_port']);
    define($prefix . '_PGSQL_DB', 'qbegame');
    define($prefix . '_PGSQL_USER', $servers_config[$select_server_pk]['db_account']);
    define($prefix . '_PGSQL_PASS', $servers_config[$select_server_pk]['db_password']);
    define($prefix . '_PGSQL_PERSISTENT', false);
    $PgGame = new Pg($prefix);

    $PgGame->query('SELECT t1.lord_name, t1.power FROM lord t1, lord_web t2 WHERE t1.lord_pk = t2.lord_pk AND t2.web_id = $1 AND t2.web_channel = $2', [$account_id, $web_channel]);
    if ($PgGame->fetch()) {
        $_add_data['lord'] = $PgGame->row;
    }
    $_add_data['select_server_pk'] = $select_server_pk;

    return $Render->nsXhrReturn('success', null, $_add_data);
}));

$app->get('/api/server/time', $Render->wrap(function (array $params) use ($Render) {
    // Authorize
    $Session = new Session();

    $now = Useful::nowServerTime();

    $Session->sqAppend('REDUCE', ['sTime' => $now]);

    return $Render->nsXhrReturn('success'); // TODO 세션 제작 후 return 값은 지워야함.
}));