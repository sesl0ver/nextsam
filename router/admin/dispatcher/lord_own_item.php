<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/lord_own_item', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $page = $params['page']; // get the requested page
    $limit = $params['rows']; // get how many rows we want to have into the grid

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $PgGame->query('SELECT COUNT(my_item_pk) FROM my_item WHERE lord_pk = $1', [$_SESSION['selected_lord']['lord_pk']]);
    $count = $PgGame->fetchOne();

    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;
    $offset_start = ($offset_start < 0) ? 0 : $offset_start;

    $PgGame->query('SELECT my_item.my_item_pk, m_item.title, my_item.item_pk, m_item.type, \'-\' AS log_code, m_item.price, my_item.item_cnt FROM my_item, m_item
WHERE my_item.item_pk = m_item.m_item_pk AND my_item.lord_pk = $3 ORDER BY m_item.orderno DESC LIMIT $1 OFFSET $2', [$limit, $offset_start, $_SESSION['selected_lord']['lord_pk']]);

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    $i = 0;
    while ($PgGame->fetch()) {
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $PgGame->row['my_item_pk'];
        $response->rows[$i]['cell'] = [$PgGame->row['my_item_pk'], $PgGame->row['title'], $PgGame->row['item_pk'], $PgGame->row['type'], $PgGame->row['log_code'], $PgGame->row['price'], $PgGame->row['item_cnt']];
        $i++;
    }

    return $Render->view(json_encode($response));
}));



$app->post('/admin/gm/api/increaseItem', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $checkPermit = $Gm->checkGMPermission(['EDIT']);
    if (is_string($checkPermit)) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => "{$checkPermit} 권한이 없습니다."]));
    }
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');
    $PgGm = new Pg('GM');
    $Session = new Session(false);
    $Item = new Item($Session, $PgGame);
    $Quest = new Quest($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    if (!is_array($params['incr_item_pk']) || !is_array($params['incr_item_count']) || count($params['incr_item_pk']) != count($params['incr_item_count'])) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '지급하고자하는 아이템을 선택해주십시오.']));
    }

    foreach($params['incr_item_count'] as $v) {
        if (!preg_match('/^\d+$/', $v) || $v < 1) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '지급할 수량을 입력하여 주십시오.']));
        }
    }

    if (!preg_match('/^\d+$/', $params['lord_pk']) || $params['lord_pk'] < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '지급할 유저를 지정하여 주십시오.']));
    }

    if (iconv_strlen($params['incr_item_cause'], 'utf-8') < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '지급 사유를 입력하여 주십시오.']));
    }

    // JS 에서는 2개를 합쳐서 보낼 수 없어서 따로 보냈으므로 여기서 합쳐놓고 작업을 시작함
    $incr_item_list = [];
    foreach($params['incr_item_pk'] as $k => $v) {
        $incr_item_list[$k] = ['m_item_pk' => $v, 'item_count' => $params['incr_item_count'][$k]];
    }

    $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$params['lord_pk']]);
    if (!$PgGame->fetch()) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '존재하지 않는 유저에게 아이템을 지급할 수 없습니다.']));
    }
    $lord_name = $PgGame->row['lord_name'];

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['item']);
    foreach($incr_item_list as $v) {
        $PgGame->query('SELECT my_item_pk FROM my_item WHERE lord_pk = $1 AND item_pk = $2', [$params['lord_pk'], $v['m_item_pk']]);
        if (!$PgGame->fetch()) {
            $PgGame->query('INSERT INTO my_item (lord_pk, item_pk, item_cnt) VALUES($1, $2, $3)', [$params['lord_pk'], $v['m_item_pk'], $v['item_count']]);
        } else {
            $t = $PgGame->row;
            $PgGame->query('UPDATE my_item SET item_cnt = item_cnt + $1 WHERE my_item_pk = $2', [$v['item_count'], $t['my_item_pk']]);
        }
        $Session->sqAppend('LORD', ['new_item_update' => $_M['ITEM'][$v['m_item_pk']]['display_type']], null, $params['lord_pk']);
        $Item->getItem($params['lord_pk'], $v['m_item_pk']);
        $Quest->conditionCheckQuest($params['lord_pk'], ['quest_type' => 'give_item', 'm_item_pk' => $v['m_item_pk']]);
        $Log->setItem($params['lord_pk'], null, 'buy', 'gm_give', $v['m_item_pk'], null, $v['item_count']);
    }

    /*$PgGame->query('UPDATE lord SET unread_item_last_up_dt = now() WHERE lord_pk = $1 returning date_part(\'epoch\', unread_item_last_up_dt)::integer as unread_item_last_up_dt', [$params['lord_pk']]);
    $unread_item_last_up_dt = $PgGame->fetchOne();

    // LP 입력
    $Session->sqAppend('LORD', ['unread_item_last_up_dt' => $unread_item_last_up_dt], null, $params['lord_pk']);*/

    $description = ['action' => 'gm_give_item', 'selected_server' => $_SESSION['selected_server'], 'lord' => ['lord_pk' => $params['lord_pk'], 'lord_name' => $lord_name, 'incr_item_list' => $incr_item_list, 'cause' => $params['incr_item_cause']]];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'I', serialize($description)]);

    return $Render->view(json_encode(['result' => 'ok', 'incr_item_list' => $incr_item_list]));
}));

$app->post('/admin/gm/api/decreaseItem', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $checkPermit = $Gm->checkGMPermission(['EDIT']);
    if (is_string($checkPermit)) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => "{$checkPermit} 권한이 없습니다."]));
    }
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');
    $PgGm = new Pg('GM');
    $Session = new Session(false);
    $Item = new Item($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    if (!is_array($params['decr_item_pk'])) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '회수하고자하는 아이템을 선택해주십시오.']));
    }

    foreach($params['decr_item_count'] as $v) {
        if (!preg_match('/^\d+$/', $v) || $v < 1) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '회수할 수량을 입력하여주십시오.']));
        }
    }

    if (!preg_match('/^\d+$/', $params['lord_pk']) || $params['lord_pk'] < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '회수할 유저를 지정하여 주십시오.']));
    }

    if (iconv_strlen($params['decr_item_cause']) < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '회수 사유를 입력하여 주십시오.']));
    }

    $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$params['lord_pk']]);
    if (!$PgGame->fetch()) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '존재하지 않는 유저에게 아이템을 회수할 수 없습니다.']));
    }
    $lord_name = $PgGame->row['lord_name'];

    $decr_item_list = [];
    foreach($params['decr_item_pk'] as $k => $v) {
        $decr_item_list[$k] = ['m_item_pk' => $v, 'item_count' => $params['decr_item_count'][$k]];
    }

    foreach($decr_item_list as &$v) {
        $PgGame->query('SELECT my_item_pk, item_cnt FROM my_item WHERE lord_pk = $1 AND item_pk = $2', [$params['lord_pk'], $v['m_item_pk']]);
        if ($PgGame->fetch()) {
            $t = $PgGame->row;
            $decr_amt = $t['item_cnt'] - $v['item_count'];
            $decr_amt = ($decr_amt < 1) ? 0 : $decr_amt;
            if ($decr_amt < 1) {
                $PgGame->query('DELETE FROM my_item WHERE my_item_pk = $1', [$t['my_item_pk']]);
                $push_data = [];
                $push_data[$v['m_item_pk']] = ['item_cnt' => 0];
                $Session->sqAppend('ITEM', $push_data, null, $params['lord_pk']);
            } else {
                $PgGame->query('UPDATE my_item SET item_cnt = $1 WHERE my_item_pk = $2', [$decr_amt, $t['my_item_pk']]);
                $Item->getItem($params['lord_pk'], $v['m_item_pk']);
            }
            $r_decr_amt = ($t['item_cnt'] < $v['item_count']) ? $t['item_cnt'] : $v['item_count'];
            $Log->setItem($params['lord_pk'], null, 'use', 'gm_del', $v['m_item_pk'], null, $r_decr_amt);

            $v['item_count'] = $r_decr_amt;
        }
    }

    // 게임 서버에 로그 남기기
    $description = ['action' => 'gm_del_item', 'selected_server' => $_SESSION['selected_server'], 'lord' => ['lord_pk' => $params['lord_pk'], 'lord_name' => $lord_name, 'decr_item_list' => $decr_item_list, 'cause' => $params['decr_item_cause']]];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'I', serialize($description)]);

    return $Render->view(json_encode(['result' => 'ok', 'decr_item_list' => $decr_item_list]));
}));

$app->post('/admin/gm/api/ownItem', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['item']);

    $Gm = new Gm();
    $checkPermit = $Gm->checkGMPermission(['EDIT']);
    if (is_string($checkPermit)) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => "{$checkPermit} 권한이 없습니다."]));
    }
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    if (!preg_match('/^\d+$/', $params['lord_pk']) || $params['lord_pk'] < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '회수할 유저를 지정하여 주십시오.']));
    }

    $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$params['lord_pk']]);
    if (!$PgGame->fetch()) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '존재하지 않는 유저에게 아이템을 회수할 수 없습니다.']));
    }

    $PgGame->query('SELECT my_item_pk, item_pk, item_cnt FROM my_item WHERE lord_pk = $1', [$params['lord_pk']]);
    $PgGame->fetchAll();

    $item_arr = [];

    foreach($PgGame->rows as $v) {
        if (!isset($item_arr[$_M['ITEM'][$v['item_pk']]['display_type']])) {
            $item_arr[$_M['ITEM'][$v['item_pk']]['display_type']] = [];
        }
        $t_obj = $v;
        $t_obj['title'] = $_M['ITEM'][$v['item_pk']]['title'];
        $item_arr[$_M['ITEM'][$v['item_pk']]['display_type']][] = $t_obj;
    }

    return $Render->view(json_encode(['result' => 'ok', 'own_list' => $item_arr]));
}));
