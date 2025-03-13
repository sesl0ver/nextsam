<?php
session_start();
global $app, $Render, $i18n;

require_once __DIR__ . '/../../config/gm_config.php';

// Index
$app->get('/admin/gm', $Render->wrap(function (array $params) use ($Render, $i18n) {
    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['item', 'hero', 'hero_base', 'hero_skill']);
    $Gm = new Gm();

    $uri = $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

    if (!isset($_SESSION) || ! isset($_SESSION['gm_active']) || $_SESSION['gm_active'] !== true) {
        return $Render->redirect("//$uri/admin/gm/login", 302);
    }

    if (! isset($params['view'])) {
        $params['view'] = 'user_search';
    }

    // 서버 리스트
    $server_list_select_tag = '';
    if (count($_SESSION['server_list']) > 0) {
        $server_list_select_tag .= '<form name="quick_server_select" method="post" action="/admin/gm/serverSelect">';
        $server_list_select_tag .= "<select name='server_pk'>";
        $selected = (!isset($_SESSION['selected_server'])) ? " selected='selected'" : "";
        $server_list_select_tag .= "<option value=''{$selected}>서버 선택</option>";
        foreach($_SESSION['server_list'] as $v) {
            $selected = (isset($_SESSION['selected_server']) && $v['server_pk'] == $_SESSION['selected_server']['server_pk']) ? " selected='selected'" : "";
            $server_list_select_tag .= "<option value='{$v['server_pk']}'{$selected}>{$v['server_name']}</option>";
        }
        $server_list_select_tag .= "</select></form>";
    }

    // 선택 정보
    $selected_server = $_SESSION['selected_server']['server_name'] ?? '';
    $selected_lord = $_SESSION['selected_lord']['lord_name'] ?? '';
    $selected_terr = $_SESSION['selected_terr']['title'] ?? '';

    // 왼쪽 메뉴
    $LEFT_MENU = $Gm->getLeftMenu($server_list_select_tag, $selected_lord, $selected_terr);

    $len = count($LEFT_MENU);
    $key = -1;
    for($i = 0; $i < $len; $i++) {
        $obj = &$LEFT_MENU[$i];
        if (isset($params['view']) && $obj->view == $params['view']) {
            $key = $i;
            break;
        }
    }

    $opened_left_menu_idx = 0;
    if ($key !== -1) {
        $obj = &$LEFT_MENU[$key];
        do {
            $key = $obj->parentIdx;
            $obj = &$LEFT_MENU[$key];
        } while($obj->parentIdx != 0);

        foreach($LEFT_MENU as $k => $v) {
            if ($v->parentIdx == 0) {
                if ($k != $key) {
                    $opened_left_menu_idx += 1;
                } else {
                    break;
                }
            }
        }
    }

    $opened_left_menu_idx = ($opened_left_menu_idx == 0) ? 3 : $opened_left_menu_idx;
    $opened_left_menu_idx = $opened_left_menu_idx - 1;

    // 네비게이션
    $navi_str = "Location &gt; <a href='/admin/gm'>Home</a>";

    if (isset($_SESSION['selected_server'])) {
        $navi_str .= " &gt; [ {$_SESSION['selected_server']['server_name']} ] 서버";
    }

    if (isset($_SESSION['selected_lord']) && preg_match("/^(terr_){1}\w+$/", $params['view'])) {
        $navi_str .= " &gt; [ {$_SESSION['selected_lord']['lord_name']} ] 군주";
    }

    $post_navi_str = '';
    while($key > 0) {
        $obj = &$LEFT_MENU[$key];
        $text = strpos($obj->text, '서버 정보') ? '서버 정보' : $obj->text;
        $post_navi_str = " &gt; $text" . $post_navi_str;
        $key = $obj->parentIdx;
    }

    // View 페이지
    $require_file = __DIR__ . '/../../template/tools/tpl/' . $params['view'] . '.twig';
    if (file_exists($require_file)) {
        $require_file = '/tools/tpl/' . $params['view'] . '.twig';
    } else {
        $require_file = null;
    }

    $search_time_start = date('Y-m-d H:i', strtotime("-1 days", time()));
    $search_time_end = date('Y-m-d H:i', time());

    // 특정 페이지를 위해 추가.
    $view_data = $Gm->getViewData($params['view']);

    return $Render->template('/tools/gm_tool.twig', [
        '_M' => $_M,
        '_SESSION' => $_SESSION,
        'params' => $params,
        'time' => time(),
        'text_resource' => rawurlencode($i18n->getBundle()),
        'navi_path' => $navi_str . $post_navi_str,
        'server_list_select_tag' => $server_list_select_tag,
        'selected_lord' => $selected_lord,
        'draw_left_menu' => $Gm->drawLeftMenu($LEFT_MENU, 0, isset($_SESSION['selected_server']), isset($_SESSION['selected_lord']), isset($_SESSION['selected_terr'])),
        'opened_left_menu_idx' => $opened_left_menu_idx,
        'require_file' => $require_file,
        'search_time_start' => $search_time_start,
        'search_time_end' => $search_time_end,
        'view_data' => $view_data
    ]);
}));

// Index
$app->get('/admin/gm/change_pw', $Render->wrap(function (array $params) use ($Render, $i18n) {
    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['item', 'hero', 'hero_base', 'hero_skill']);
    $Gm = new Gm();

    $uri = $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

    if (!isset($_SESSION) || ! isset($_SESSION['gm_active']) || $_SESSION['gm_active'] !== true) {
        return $Render->redirect("//$uri/admin/gm/login", 302);
    }

    if (! isset($params['view'])) {
        $params['view'] = 'user_search';
    }

    // 서버 리스트
    $server_list_select_tag = '';
    if (count($_SESSION['server_list']) > 0) {
        $server_list_select_tag .= '<form name="quick_server_select" method="post" action="/admin/gm/serverSelect">';
        $server_list_select_tag .= "<select name='server_pk'>";
        $selected = (!isset($_SESSION['selected_server'])) ? " selected='selected'" : "";
        $server_list_select_tag .= "<option value=''{$selected}>서버 선택</option>";
        foreach($_SESSION['server_list'] as $v) {
            $selected = (isset($_SESSION['selected_server']) && $v['server_pk'] == $_SESSION['selected_server']['server_pk']) ? " selected='selected'" : "";
            $server_list_select_tag .= "<option value='{$v['server_pk']}'{$selected}>{$v['server_name']}</option>";
        }
        $server_list_select_tag .= "</select></form>";
    }

    // 선택 정보
    $selected_server = $_SESSION['selected_server']['server_name'] ?? '';
    $selected_lord = $_SESSION['selected_lord']['lord_name'] ?? '';
    $selected_terr = $_SESSION['selected_terr']['title'] ?? '';

    // 왼쪽 메뉴
    $LEFT_MENU = $Gm->getLeftMenu($server_list_select_tag, $selected_lord, $selected_terr);

    $len = count($LEFT_MENU);
    $key = -1;
    for($i = 0; $i < $len; $i++) {
        $obj = &$LEFT_MENU[$i];
        if (isset($params['view']) && $obj->view == $params['view']) {
            $key = $i;
            break;
        }
    }

    $opened_left_menu_idx = 0;
    if ($key !== -1) {
        $obj = &$LEFT_MENU[$key];
        do {
            $key = $obj->parentIdx;
            $obj = &$LEFT_MENU[$key];
        } while($obj->parentIdx != 0);

        foreach($LEFT_MENU as $k => $v) {
            if ($v->parentIdx == 0) {
                if ($k != $key) {
                    $opened_left_menu_idx += 1;
                } else {
                    break;
                }
            }
        }
    }

    $opened_left_menu_idx = ($opened_left_menu_idx == 0) ? 3 : $opened_left_menu_idx;
    $opened_left_menu_idx = $opened_left_menu_idx - 1;

    // 네비게이션
    $navi_str = "Location &gt; <a href='/admin/gm'>Home</a>";

    // View 페이지
    $require_file = __DIR__ . '/../../template/tools/tpl/' . $params['view'] . '.twig';
    if (file_exists($require_file)) {
        $require_file = '/tools/tpl/' . $params['view'] . '.twig';
    } else {
        $require_file = null;
    }

    $search_time_start = date('Y-m-d H:i', strtotime("-1 days", time()));
    $search_time_end = date('Y-m-d H:i', time());

    // 특정 페이지를 위해 추가.
    $view_data = $Gm->getViewData($params['view']);

    return $Render->template('/tools/gm_tool_pw.twig', [
        '_M' => $_M,
        '_SESSION' => $_SESSION,
        'params' => $params,
        'time' => time(),
        'text_resource' => rawurlencode($i18n->getBundle()),
        'navi_path' => $navi_str,
        'server_list_select_tag' => $server_list_select_tag,
        'selected_lord' => $selected_lord,
        'draw_left_menu' => $Gm->drawLeftMenu($LEFT_MENU, 0, isset($_SESSION['selected_server']), isset($_SESSION['selected_lord']), isset($_SESSION['selected_terr'])),
        'opened_left_menu_idx' => $opened_left_menu_idx,
        'require_file' => $require_file,
        'search_time_start' => $search_time_start,
        'search_time_end' => $search_time_end,
        'view_data' => $view_data
    ]);
}));

// Server
$app->post('/admin/gm/serverList', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        return $Render->view(json_encode(['result' => 'fail', 'msg' => 'You do not have permission.']));
    }

    return $Render->view(json_encode(['result' => 'ok', 'data' =>  $_SESSION['server_list']]));
}));

$app->post('/admin/gm/serverSelect', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $uri = $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

    if (!isset($_SESSION) || ! isset($_SESSION['gm_active']) || $_SESSION['gm_active'] !== true) {
        return $Render->redirect("//$uri/admin/gm/login", 302);
    }

    // 이미 사용 중인 서버 정보가 있다면 삭제
    if (isset($_SESSION['selected_server'])) {
        unset($_SESSION['selected_server']);
    }

    // 이미 사용 중인 군주 정보가 있다면 삭제
    if (isset($_SESSION['selected_lord'])) {
        unset($_SESSION['selected_lord']);
    }

    // 이미 사용 중인 영지 정보가 있다면 삭제
    if (isset($_SESSION['selected_terr'])) {
        unset($_SESSION['selected_terr']);
    }

    // 서버의 정보를 세션에 저장
    $len = count($_SESSION['server_list']);
    for($i = 0; $i < $len; $i++) {
        if ($_SESSION['server_list'][$i]['server_pk'] == $params['server_pk']) {
            $_SESSION['selected_server'] = $_SESSION['server_list'][$i];
            break;
        }
    }

    return $Render->redirect("//$uri/admin/gm", 302);
}));

// Login
$app->get('/admin/gm/login', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $uri = $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

    // 이미 로그인 중인데 로그인 요청이 왔다면
    if (isset($_SESSION['gm_active']) && $_SESSION['gm_active'] === true) {
        // 원래 봐야되는 페이지로 리다이렉트
        return $Render->redirect("//$uri/admin/gm", 302);
    }

    return $Render->template('/tools/gm_login.twig');
}));

$app->post('/admin/gm/login', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $PgGm = new Pg('GM');

    $uri = $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

    // 인자 확인
    if (! isset($params['gm_id']) || ! isset($params['gm_pw'])) {
        return $Render->redirect("//$uri/admin/gm/login", 302);
    }

    // 이미 로그인 중인데 로그인 요청이 왔다면
    if (isset($_SESSION['gm_active']) && $_SESSION['gm_active'] === true) {
        // 원래 봐야되는 페이지로 리다이렉트
        return $Render->redirect("//$uri/admin/gm", 302);
    }

    try {
        $PgGm->query('SELECT gm_pk, gm_id, gm_level FROM account WHERE gm_id = $1 AND gm_pw = $2', [$params['gm_id'], $params['gm_pw']]);
        $PgGm->fetch();
        $result = $PgGm->row;

        if ($result['gm_pk'] < 1) {
            // 로그인 실패했으므로 로그인페이지로 리다이렉트 시킴
            return $Render->redirect("//$uri/admin/gm/login", 302);
        } else {
            // 마지막 로그인 시각과 아이피를 업데이트
            $PgGm->query('UPDATE account SET last_logged_dt = now(), last_logged_ip = $1 WHERE gm_pk = $2', [Useful::getRealClientIp(), $result['gm_pk']]);

            // 세션에 로그인 했음을 등록하고 유저 권한 가져오기
            $_SESSION['gm_active'] = true;
            $_SESSION['gm_pk'] = $result['gm_pk'];
            $_SESSION['gm_id'] = $result['gm_id'];
            $_SESSION['gm_permit'] = [
                'NOTICE' => stristr($result['gm_level'], 'NOTICE') !== false,
                'BLOCK' => stristr($result['gm_level'], 'BLOCK') !== false,
                'LOG' => stristr($result['gm_level'], 'LOG') !== false,
                'CHEAT' => stristr($result['gm_level'], 'CHEAT') !== false,
                'EDIT' => stristr($result['gm_level'], 'EDIT') !== false,
                'SMONITOR' => stristr($result['gm_level'], 'SMONITOR') !== false,
                'SCOMMAND' => stristr($result['gm_level'], 'SCOMMAND') !== false,
                'ENQUINARY' => stristr($result['gm_level'], 'ENQUINARY') !== false,
                'PGGM' => stristr($result['gm_level'], 'PGGM') !== false
            ];
            $_SESSION['gm_login_time'] = time();
            $_SESSION['gm_expire'] = time() + session_cache_expire();

            // 히스토리 기록
            $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'L', serialize(['action' => 'login'])]);

            if (! isset($_SESSION['server_list'])) {
                $sql = 'SELECT server_pk, server_name FROM server ORDER BY orderno';
                $PgGm->query($sql);
                $PgGm->fetchAll();
                $i = 0;
                $server_list = [];
                foreach ($PgGm->rows as $row) {
                    $server_list[$i++] = [
                        'server_pk' => $row['server_pk'],
                        'server_name' => $row['server_name']
                    ];
                }
                if ($i == 0) {
                    session_destroy(); // 로그인 실패
                    return $Render->redirect("//$uri/admin/gm/login", 302);
                }
                $_SESSION['server_list'] = $server_list;
            }
        }

    } catch (Throwable $e) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => $e->getMessage()]));
    }

    // 여기까지 왔으면 로그인 성공으로 판단하고 리다이렉트 시킴
    return $Render->redirect("//$uri/admin/gm", 302);
}));

$app->get('/admin/gm/logout', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $uri = $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

    session_destroy(); // 모든세션 날려버림

    return $Render->redirect("//$uri/admin/gm/login", 302);
}));

$app->post('/admin/gm/changePassword', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $PgGm = new Pg('GM');

    // 로그인 되지 않은 경우 변경 할 수 없음
    if (!isset($_SESSION['gm_pk']) || !isset($_SESSION['gm_id'])) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => 'illegal request']));
    }

    // 인자 확인
    if (!isset($params['prev_pw']) || !isset($params['new_pw']) || !isset($params['cf_pw'])) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => 'illegal request']));
    }

    // 변경할 비밀번호와 비밀번호 확인 입력이 일치하지 않는다면
    if (strcmp($params['new_pw'], $params['cf_pw']) !== 0) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => 'not_matched_new_password']));
    }

    $PgGm->query('SELECT gm_pw FROM account WHERE gm_pk = $1', [$_SESSION['gm_pk']]);
    $prev_pw = $PgGm->fetchOne();

    // 이전 비밀번호가 없거나 또는 보내온 이전 패스워드와 일치하지 않는다면
    if (!$prev_pw || strcmp($prev_pw, $params['prev_pw']) !== 0) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => 'prev_pw_not_matched']));
    }

    // 이전 비밀번호와 새로운 비밀번호가 일치한다면
    if (strcmp($prev_pw, $params['new_pw']) === 0) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => 'prev_pw_equal_new_pw']));
    }

    // 이전 비밀번호가 일치하고 새로운 비밀번호과 확실하다면 업데이트
    $PgGm->query('UPDATE account SET gm_pw = $1 WHERE gm_pk = $2', [$params['new_pw'], $_SESSION['gm_pk']]);

    // 히스토리 기록
    $description = ['action' => 'change_pw'];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'L', serialize($description)]);

    return $Render->view(json_encode(['result' => 'ok', 'msg' => 'change_pw_ok']));
}));

// GM툴 호출용

// 군주 선택 요청
$app->post('/admin/gm/api/selectLord', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    // 이미 사용 중인 영지 정보가 있다면 삭제
    if (isset($_SESSION['selected_terr'])) {
        unset($_SESSION['selected_terr']);
    }

    if (preg_match("/^[\d]+$/", $params['server_pk'])) {
        if (isset($_SESSION['selected_server']) && $_SESSION['selected_server']['server_pk'] != $params['server_pk']) {
            // 이미 선택 중인 서버 정보와 지금 필요한 서버 정보가 다르다면 지운다.
            unset($_SESSION['selected_server']);
        }
    }

    // 선택 중이던 서버의 정보가 지워졌거나 없으면 선택
    if (! isset($_SESSION['selected_server'])) {
        // 서버의 정보를 세션에 저장
        $len = count($_SESSION['server_list']);
        for($i = 0; $i < $len; $i++) {
            if ($_SESSION['server_list'][$i]['server_pk'] == $params['server_pk']) {
                $_SESSION['selected_server'] = $_SESSION['server_list'][$i];
                break;
            }
        }

        if (! isset($_SESSION['selected_server'])) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => 'do not find server pk']));
        }
    }

    $Gm = new Gm();
    $Gm->selectPgGame($params['server_pk']);
    $PgGame = new Pg('SELECT');

    if (isset($_SESSION['selected_lord']))
    {
        // 이미 사용 중인 군주 정보가 있다면 삭제
        unset($_SESSION['selected_lord']);
    }

    // 군주명 찾아오기
    $PgGame->query('SELECT lord_name, main_posi_pk, num_slot_guest_hero FROM lord WHERE lord_pk = $1', [$params['lord_pk']]);
    $PgGame->fetch();
    $lord_name = $PgGame->row['lord_name'];
    $main_posi_pk = $PgGame->row['main_posi_pk'];
    $num_slot_guest_hero = $PgGame->row['num_slot_guest_hero'];

    if (! $lord_name) {
        echo json_encode(['result' => 'fail', 'msg' => 'do not find lord']);
        exit(1);
    }

    // 해당 군주가 가진 영지 목록(영지명, posi_pk) 가져오기 - 이제는 영지는 1개 뿐이므로 단일 쿼리로 변경. 20231011 송누리
    $PgGame->query('SELECT position.posi_pk, territory.title FROM position, territory WHERE position.posi_pk = territory.posi_pk AND position.lord_pk = $1', [$params['lord_pk']]);
    $PgGame->fetch();
    $selected_territory = $PgGame->row;

    // 선택된 군주의 정보 세션에 저장
    $_SESSION['selected_lord'] = [
        'lord_pk' => $params['lord_pk'],
        'main_posi_pk' => $main_posi_pk,
        'lord_name' => $lord_name,
        'territory' => [$selected_territory], // 원래 배열이었으므로
        'num_slot_guest_hero' => $num_slot_guest_hero
    ];
    $_SESSION['selected_terr'] = $selected_territory;

    return $Render->view(json_encode(['result' => 'ok', 'msg' => 'select_server_ok', 'selected_server_name' => $_SESSION['selected_server']['server_name'], 'selected_lord_name' => $_SESSION['selected_lord']['lord_name']]));
}));

// grid dispatcher
$router_files = scandir(__DIR__ . '/dispatcher/');
foreach ($router_files as $router_file) {
    if (str_ends_with($router_file, '.php')) {
        require_once __DIR__ . "/dispatcher/$router_file";
    }
}


