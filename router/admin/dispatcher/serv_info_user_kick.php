<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_info_user_kick', $Render->wrap(function (array $params) use ($Render, $i18n) {
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

    $total_count_sql = "SELECT COUNT(lord_pk) FROM lord WHERE lord_name LIKE '%{$params['search']}%'";
    $PgGame->query($total_count_sql);

    $count = $PgGame->fetchOne();
    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;
    $offset_start = ($offset_start < 0)? 0 : $offset_start;

    $offset_start = ($offset_start < 0) ? 0 : $offset_start;
    $PgGame->query("SELECT lord_pk, lord_name, date_part('epoch', regist_dt)::integer as regist_dt, date_part('epoch', last_login_dt)::integer as last_login_dt, is_user_blocked, is_logon FROM lord WHERE lord_name LIKE '%{$params['search']}%' ORDER BY lord_pk DESC LIMIT $1 OFFSET $2", [$limit, $offset_start]);

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    $i = 0;
    while ($PgGame->fetch()) {
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $PgGame->row['lord_pk'];
        $response->rows[$i]['cell'] = [$PgGame->row['lord_pk'], $PgGame->row['lord_name'], date('Y-m-d H:i:s', $PgGame->row['regist_dt']), date('Y-m-d H:i:s', $PgGame->row['last_login_dt']), (($PgGame->row['is_user_blocked'] == 'N') ? '정상' : '블럭 중'), (($PgGame->row['is_logon'] == 'N') ? '로그아웃' : '로그인 중')];
        $i++;
    }

    return $Render->view(json_encode($response));
}));

$app->post('/admin/gm/api/userKick', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $checkPermit = $Gm->checkGMPermission(['BLOCK']);
    if (is_string($checkPermit)) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => "{$checkPermit} 권한이 없습니다."]));
    }
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $PgGame->query('SELECT last_sid FROM lord WHERE lord_pk = $1', [$params['lord_pk']]);
    $last_sid = $PgGame->fetchOne();
    if (! isset($last_sid)) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '현재 로그인 중인 유저가 아닙니다.']));
    }

    $Session = new Session(false);
    $Cache = new Cache();

    $Session->sqAppend('KICK', null, $last_sid);

    // lp로 푸시하고 잠시 대기
    sleep(1);

    // 멤캐시에서 유저 정보 삭제
    $_LP_LOCK = $last_sid. '_LP_LOCK';
    $_LP_LOCK_FAIL = $last_sid. '_LP_LOCK_FAIL';
    $_SQ_CNT = $last_sid. '_SQ_CNT';
    $_SQ_SEQ = $last_sid. '_SQ_SEQ';
    $_SQ_LAST = $last_sid. '_SQ_LAST';
    $_SQ_SEQ_READ = $last_sid. '_SQ_SEQ_READ';
    $_SQ_GET_CNT = $last_sid. '_SQ_GET_CNT';
    $_LP_LATEST = $last_sid. '_LP_LATEST';

    // 가져올 SQ가 있으면 삭제
    $cnt = $Cache->get($_SQ_CNT);

    if ($cnt > 0) {
        $sq_seq = $Cache->get($_SQ_SEQ); // 현재 SEQ
        $sq_seq_read = $Cache->get($_SQ_SEQ_READ); // 이전까지 읽은 SEQ
        for ($i = $sq_seq_read+1, $read_cnt = 0; $i <= $sq_seq; $i++) {
            // 읽을 SQ 키
            $key = $last_sid. '_SQ_'. $i;
            $Cache->del($key);
        }
    }

    // 쓰레기 SQ 삭제
    $key = $last_sid. '_SQ_';
    $Cache->del($key);
    $key = $last_sid. '_SQ_None';
    $Cache->del($key);

    // 세션 삭제
    $key = $last_sid;
    $Cache->del($key);
    $key = $last_sid. '_LATEST';
    $Cache->del($key);

    $key = $last_sid. '_POSI_PK';
    $posi_pk = $Cache->get($key); // 현재 posi_pk
    $Cache->del($key);

    $Cache->del($params['lord_pk']);
    $Cache->del($params['lord_pk']. '_'. $posi_pk);

    $Cache->del($_LP_LOCK);
    $Cache->del($_LP_LOCK_FAIL);
    $Cache->del($_SQ_CNT);
    $Cache->del($_SQ_SEQ);
    $Cache->del($_SQ_LAST); // TODO 안지워주고 있길래 임의로 추가한건데... 문제가되면 삭제하자.
    $Cache->del($_SQ_SEQ_READ);
    $Cache->del($_SQ_GET_CNT);
    $Cache->del($_LP_LATEST);

    $PgGame->query('UPDATE lord SET is_logon = $1, last_sid = $2, last_logout_dt = now() WHERE lord_pk = $3', ['N', null, $params['lord_pk']]);
    $PgGame->query('UPDATE lord_login SET logout_dt = now() WHERE lord_pk = $1 AND login_sid = $2', [$params['lord_pk'], $last_sid]);

    $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$params['lord_pk']]);

    $lord_name = $PgGame->fetchOne();

    // 히스토리 기록
    $PgGm = new Pg('GM');
    $description = ['action' => 'user_kick', 'selected_server' => $_SESSION['selected_server'], 'lord' => ['lord_pk' => $params['lord_pk'], 'lord_name' => $lord_name]];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'K', serialize($description)]);

    return $Render->view(json_encode(['result' => 'ok']));
}));