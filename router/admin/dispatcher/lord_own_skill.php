<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/lord_own_skill', $Render->wrap(function (array $params) use ($Render, $i18n) {
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

    function getTypeName($type): string
    {
        return match ($type) {
            'S' => '특수',
            'D' => '성장',
            'P' => '수행',
            'A' => '배속',
            default => '-',
        };
    }

    $PgGame->query('SELECT COUNT(my_hero_skil_pk) FROM my_hero_skill WHERE lord_pk = $1', [$_SESSION['selected_lord']['lord_pk']]);
    $count = $PgGame->fetchOne();
    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;
    $offset_start = ($offset_start < 0) ? 0 : $offset_start;

    $PgGame->query('SELECT my_hero_skill.my_hero_skil_pk, m_hero_skill.title, m_hero_skill.rare, my_hero_skill.m_hero_skil_pk, m_hero_skill.type, \'-\' AS log_code,
        m_hero_skill.use_slot_count, my_hero_skill.skill_cnt FROM my_hero_skill, m_hero_skill WHERE my_hero_skill.m_hero_skil_pk = m_hero_skill.m_hero_skil_pk AND my_hero_skill.lord_pk = $1
        ORDER BY m_hero_skill.ordno DESC LIMIT $2 OFFSET $3', [$_SESSION['selected_lord']['lord_pk'], $limit, $offset_start]);

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    $i = 0;
    while ($PgGame->fetch()) {
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $PgGame->row['my_hero_skil_pk'];
        $response->rows[$i]['cell'] = [$PgGame->row['my_hero_skil_pk'], $PgGame->row['title'].' Lv.'.$PgGame->row['rare'], $PgGame->row['m_hero_skil_pk'], getTypeName($PgGame->row['type']), $PgGame->row['log_code'], $PgGame->row['use_slot_count'], $PgGame->row['skill_cnt']];
        $i++;
    }

    return $Render->view(json_encode($response));
}));

$app->post('/admin/gm/api/increaseSkill', $Render->wrap(function (array $params) use ($Render, $i18n) {
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
    $Log = new Log($Session, $PgGame);

    if (!is_array($params['incr_skill_pk']) || !is_array($params['incr_skill_count']) || count($params['incr_skill_pk']) != count($params['incr_skill_count'])) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '지급하고자하는 스킬을 선택해주십시오.']));
    }

    foreach($params['incr_skill_count'] as $v) {
        if (!preg_match('/^\d+$/', $v) || $v < 1) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '지급할 수량을 입력하여 주십시오.']));
        }
    }

    if (!preg_match('/^\d+$/', $params['lord_pk']) || $params['lord_pk'] < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '지급할 유저를 지정하여 주십시오.']));
    }

    if (iconv_strlen($params['incr_skill_cause'], 'utf-8') < 1)  {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '지급 사유를 입력하여 주십시오.']));
    }

    // js 에서는 2개를 합쳐서 보낼 수 없어서 따로 보냈으므로 여기서 합쳐놓고 작업을 시작함
    $incr_skill_list = [];
    foreach($params['incr_skill_pk'] as $k => $v) {
        $incr_skill_list[$k] = ['m_hero_skil_pk' => $v, 'skill_cnt' => $params['incr_skill_count'][$k]];
    }

    $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$params['lord_pk']]);
    if (! $PgGame->fetch()) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '존재하지 않는 유저에게 아이템을 지급할 수 없습니다.']));
    }
    $lord_name = $PgGame->row['lord_name'];

    foreach($incr_skill_list as $v) {
        $PgGame->query('SELECT my_hero_skil_pk FROM my_hero_skill WHERE lord_pk = $1 AND m_hero_skil_pk = $2', [$params['lord_pk'], $v['m_hero_skil_pk']]);
        if (!$PgGame->fetch()) {
            $PgGame->query('INSERT INTO my_hero_skill (lord_pk, m_hero_skil_pk, skill_cnt) VALUES($1, $2, $3)', [$params['lord_pk'], $v['m_hero_skil_pk'], $v['skill_cnt']]);
        } else {
            $t = $PgGame->row;
            $PgGame->query('UPDATE my_hero_skill SET skill_cnt = skill_cnt + $1 WHERE my_hero_skil_pk = $2', [$v['skill_cnt'], $t['my_hero_skil_pk']]);
        }
    }

    // 히스토리 기록
    $description = ['action' => 'gm_give_skill', 'selected_server' => $_SESSION['selected_server'], 'lord' => ['lord_pk' => $params['lord_pk'], 'lord_name' => $lord_name, 'incr_skill_list' => $incr_skill_list, 'cause' => $params['incr_skill_cause']]];
    $query_params = [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'I', serialize($description)];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', $query_params);

    return $Render->view(json_encode(['result' => 'ok', 'incr_item_list' => $incr_skill_list]));
}));

$app->post('/admin/gm/api/decreaseSkill', $Render->wrap(function (array $params) use ($Render, $i18n) {
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
    $Log = new Log($Session, $PgGame);

    if (!is_array($params['decr_skill_pk'])) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '회수하고자하는 스킬을 선택해주십시오.']));
    }

    foreach($params['decr_skill_count'] as $v) {
        if (!preg_match('/^\d+$/', $v) || $v < 1) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '회수할 수량을 입력하여주십시오.']));
        }
    }

    if (!preg_match('/^\d+$/', $params['lord_pk']) || $params['lord_pk'] < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '회수할 유저를 지정하여 주십시오.']));
    }

    if (iconv_strlen($params['decr_skill_cause']) < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '회수 사유를 입력하여 주십시오.']));
    }

    $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$params['lord_pk']]);
    if (!$PgGame->fetch()) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '존재하지 않는 유저에게 스킬을 회수할 수 없습니다.']));
    }
    $lord_name = $PgGame->row['lord_name'];

    $decr_skill_list = [];
    foreach($params['decr_skill_pk'] as $k => $v) {
        $decr_skill_list[$k] = ['m_hero_skil_pk' => $v, 'skill_count' => $params['decr_skill_count'][$k]];
    }

    foreach($decr_skill_list as &$v) {
        $PgGame->query('SELECT my_hero_skil_pk, skill_cnt FROM my_hero_skill WHERE lord_pk = $1 AND m_hero_skil_pk = $2', [$params['lord_pk'], $v['m_hero_skil_pk']]);
        if ($PgGame->fetch()) {
            $t = $PgGame->row;
            $decr_amt = $t['skill_cnt'] - $v['skill_count'];
            $decr_amt = ($decr_amt < 1) ? 0 : $decr_amt;
            if ($decr_amt < 1) {
                $PgGame->query('DELETE FROM my_hero_skill WHERE my_hero_skil_pk = $1', [$t['my_hero_skil_pk']]);
            } else {
                $PgGame->query('UPDATE my_hero_skill SET skill_cnt = $1 WHERE my_hero_skil_pk = $2', [$decr_amt, $t['my_hero_skil_pk']]);
            }
            $r_decr_amt = ($t['skill_cnt'] < $v['skill_count']) ? $t['skill_cnt'] : $v['skill_count'];
            $v['skill_count'] = $r_decr_amt;
        }
    }

    // 게임 서버에 로그 남기기
    $description = ['action' => 'gm_del_skill', 'selected_server' => $_SESSION['selected_server'], 'lord' => ['lord_pk' => $params['lord_pk'], 'lord_name' => $lord_name, 'decr_skill_list' => $decr_skill_list, 'cause' => $params['decr_skill_cause']]];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'I', serialize($description)]);

    return $Render->view(json_encode(['result' => 'ok', 'decr_item_list' => $decr_skill_list]));
}));

$app->post('/admin/gm/api/ownSkill', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['hero_skill']);

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

    $PgGame->query('SELECT my_hero_skil_pk, m_hero_skil_pk, skill_cnt FROM my_hero_skill WHERE lord_pk = $1', [$params['lord_pk']]);
    $PgGame->fetchAll();

    $skill_arr = [];

    foreach($PgGame->rows as $v) {

        if (!isset($skill_arr[$_M['HERO_SKILL'][$v['m_hero_skil_pk']]['type']]))
        {
            $skill_arr[$_M['HERO_SKILL'][$v['m_hero_skil_pk']]['type']] = Array();
        }
        $t_obj = $v;
        $t_obj['title'] = $_M['HERO_SKILL'][$v['m_hero_skil_pk']]['title'] . ' Lv.' . $_M['HERO_SKILL'][$v['m_hero_skil_pk']]['rare'];
        $skill_arr[$_M['HERO_SKILL'][$v['m_hero_skil_pk']]['type']][] = $t_obj;
    }

    return $Render->view(json_encode(['result' => 'ok', 'own_list' => $skill_arr]));
}));