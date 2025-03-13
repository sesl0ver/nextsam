<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/buffDelete', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($params['target_server_pk']);
    $PgGame = new Pg('SELECT');
    $Session = new Session(false);
    $Timer = new Timer($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    $response = [];

    // 버프 존재 유무
    if (! isset($params['buff_pk'])) {
        $response['rstate'] = 'fail';
        $response['msg'] = '존재하지 않는 버프입니다.';
        return $Render->view(json_encode($response));
    }

    // 이제부터 영지는 1곳일 것이므로
    $buff_pk = $params['buff_pk']; // terr_item_buff_pk
    $time_pk = $params['time_pk']; // time_pk

    try {
        $PgGame->begin();

        // 타이머에서 삭제
        $PgGame->query('SELECT time_pk, posi_pk, (date_part(\'epoch\', end_dt)::integer - date_part(\'epoch\', now())::integer) AS reduce_dt FROM timer WHERE time_pk = $1', [$time_pk]);
        if ($PgGame->fetch()) {
            $_time_pk = $PgGame->row['time_pk'];
            $_posi_pk = $PgGame->row['posi_pk'];
            $timer_info = $Timer->getRecord($_time_pk);
            // 타이머 제거
            $PgGame->query('DELETE FROM timer WHERE time_pk = $1', [$_time_pk]);

            $decr_timer_info = '';
            foreach($timer_info as $k => $v) {
                $decr_timer_info .= $k . '[' . $v . ']';
            }

            // 버프제거
            $PgGame->query('DELETE FROM territory_item_buff WHERE terr_item_buff_pk = $1', [$buff_pk]);

            // lord_pk 알아오기
            $PgGame->query('SELECT lord_pk FROM lord WHERE lord.main_posi_pk = $1', [$_posi_pk]);
            $_lord_pk = $PgGame->fetchOne();

            $time_list = [];
            $time_list[$_time_pk]['status'] = 'C';
            $Session->sqAppend('TIME', $time_list, null, $_lord_pk, $_posi_pk);

            $Log->setEtc($_SESSION['selected_lord']['lord_pk'], $_posi_pk, 'timer_reduce_cheat', $decr_timer_info);
        } else { // 타이머가 존재하지 않는다면
            // 버프 테이블에서만 삭제, 로그는 남기지 않음.
            $PgGame->query('DELETE FROM territory_item_buff WHERE terr_item_buff_pk = $1', [$buff_pk]);
        }

        $PgGame->commit();
    } catch (Exception $e) {
        $PgGame->rollback();
        $response['rstate'] = 'fail';
        $response['msg'] = $e->getMessage();
        return $Render->view(json_encode($response));
    }

    return $Render->view(json_encode($response));
}));