<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/userKickAll', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $checkPermit = $Gm->checkGMPermission(['SCOMMAND']);
    if (is_string($checkPermit)) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => "{$checkPermit} 권한이 없습니다."]));
    }

    if (!isset($params['cause']) || iconv_strlen($params['cause']) < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '삭제 사유를 입력해주십시오.']));
    }

    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $Session = new Session(false);

    $PgGame->query('SELECT lord_pk, last_sid FROM lord WHERE is_logon = $1', ['Y']);
    $PgGame->fetchAll();
    $lord_info = $PgGame->rows;
    foreach($lord_info as $v) {

        $last_sid = $v['last_sid'];
        $lord_pk = $v['lord_pk'];
        $Session->sqAppend('KICK', null, $last_sid, $lord_pk);
    }
    // lp로 푸시하고 잠시 기둘
    sleep(5);
    foreach($lord_info as $v) {
        $last_sid = $v['last_sid'];
        $lord_pk = $v['lord_pk'];

        // 멤캐시에서 유저 정보 삭제
        if ($last_sid) {
            $_LP_LOCK = $last_sid. '_LP_LOCK';
            $_LP_LOCK_FAIL = $last_sid. '_LP_LOCK_FAIL';
            $_SQ_CNT = $last_sid. '_SQ_CNT';
            $_SQ_SEQ = $last_sid. '_SQ_SEQ';
            $_SQ_LAST = $last_sid. '_SQ_LAST';
            $_SQ_SEQ_READ = $last_sid. '_SQ_SEQ_READ';
            $_SQ_GET_CNT = $last_sid. '_SQ_GET_CNT';
            $_LP_LATEST = $last_sid. '_LP_LATEST';

            // 가져올 SQ가 있으면 삭제
            $cnt = $Session->Cache->get($_SQ_CNT);

            if ($cnt > 0) {
                $sq_seq = $Session->Cache->get($_SQ_SEQ); // 현재 SEQ
                $sq_seq_read = $Session->Cache->get($_SQ_SEQ_READ); // 이전까지 읽은 SEQ

                for ($i = $sq_seq_read+1, $read_cnt = 0; $i <= $sq_seq; $i++) {
                    // 읽을 SQ 키
                    $key = $last_sid. '_SQ_'. $i;
                    $Session->Cache->del($key);
                }
            }

            // 쓰레기 SQ 삭제
            $key = $last_sid. '_SQ_';
            $Session->Cache->del($key);
            $key = $last_sid. '_SQ_None';
            $Session->Cache->del($key);

            // 세션 삭제
            $key = $last_sid;
            $Session->Cache->del($key);
            $key = $last_sid. '_LATEST';
            $Session->Cache->del($key);

            $key = $last_sid. '_POSI_PK';
            $posi_pk = $Session->Cache->get($key); // 현재 posi_pk
            $Session->Cache->del($key);

            $Session->Cache->del($lord_pk);
            $Session->Cache->del($lord_pk. '_'. $posi_pk);

            $Session->Cache->del($_LP_LOCK);
            $Session->Cache->del($_LP_LOCK_FAIL);
            $Session->Cache->del($_SQ_CNT);
            $Session->Cache->del($_SQ_SEQ);
            $Session->Cache->del($_SQ_LAST); // TODO 안지워주고 있길래 임의로 추가한건데... 문제가되면 삭제하자.
            $Session->Cache->del($_SQ_SEQ_READ);
            $Session->Cache->del($_SQ_GET_CNT);
            $Session->Cache->del($_LP_LATEST);
        }

        $PgGame->query('UPDATE lord SET is_logon = $1, last_sid = $2, last_logout_dt = now() WHERE lord_pk = $3', ['N', null, $lord_pk]);
        $PgGame->query('UPDATE lord_login SET logout_dt = now() WHERE lord_pk = $1 AND login_sid = $2', [$lord_pk, $last_sid]);
    }

    // 히스토리 기록
    $PgGm = new Pg('GM');
    $description = ['action' => 'all_user_kick', 'selected_server' => $_SESSION['selected_server'], 'cause' => $params['cause']];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'K', serialize($description)]);

    return $Render->view(json_encode(['result' => 'ok']));
}));
