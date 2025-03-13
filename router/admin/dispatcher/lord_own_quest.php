<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/lord_own_quest', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $page = $params['page']; // get the requested page
    $limit = 10000;

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    function getStatus($_status)
    {
        if ($_status == 'C')
            $r = '완료';
        else if ($_status == 'P')
            $r = '진행 중';
        else
            $r = $_status;

        return $r;
    }

    function setDate($_datetime): string
    {
        return date('Y-m-d H:i:s', $_datetime);
    }

    $response = new stdClass();
    if ($params['mode'] == 'general' || $params['mode'] == 'making') {
        if ($params['mode'] == 'general') {
            $mode = "'general', 'daily', 'lord_upgrade', 'friend'";
        } else {
            $mode = "'making'";
        }

        $PgGame->query("SELECT count(t1.m_ques_pk) FROM my_quest t1, m_quest t2 WHERE t1.m_ques_pk = t2.m_ques_pk AND t1.lord_pk = $1 AND
	t1.reward_status = 'N' AND t1.invisible = 'N' AND t2.type IN ($mode)", [$_SESSION['selected_lord']['lord_pk']]);
        $ret['total_cnt'] = $PgGame->fetchOne();

        $PgGame->query("SELECT t1.m_ques_pk, t1.status, t1.reward_status, t2.main_title, t2.sub_title,
	        date_part('epoch', t1.start_dt)::integer as start_dt, date_part('epoch', t1.last_up_dt)::integer as last_up_dt
            FROM my_quest t1, m_quest t2 WHERE t1.m_ques_pk = t2.m_ques_pk AND t1.lord_pk = $1 AND t1.reward_status = 'N' AND
	        t1.invisible = 'N' AND t2.type IN ($mode) ORDER BY t2.main_title, t1.m_ques_pk, t1.start_dt", [$_SESSION['selected_lord']['lord_pk']]);
        $PgGame->fetchAll();
        $rows = $PgGame->rows;

        $ret['list'] = $rows;

        $count = $ret['total_cnt'];

        $total_page = ($count > 0) ? ceil($count/$limit) : 0;
        $page = ($page > $total_page) ? $total_page : $page;

        $response->page = $page;
        $response->total = $total_page;
        $response->records = $count;
        $response->rows = [];

        $i = 0;
        foreach($ret['list'] as $v) {
            $response->rows[$i] = [];
            $response->rows[$i]['id'] = $v['m_ques_pk'];
            $response->rows[$i]['cell'] = [$v['m_ques_pk'], $v['main_title'], $v['sub_title'], getStatus($v['status']), $v['reward_status'], setDate($v['start_dt']), setDate($v['last_up_dt'])];
            $i++;
        }
    } else if ($params['mode'] == 'clear') {
        $PgGame->query("SELECT count(t1.m_ques_pk) FROM my_quest t1, m_quest t2 WHERE t1.m_ques_pk = t2.m_ques_pk AND t1.lord_pk = $1 AND t1.reward_status = 'N' AND t1.invisible = 'Y'", [$_SESSION['selected_lord']['lord_pk']]);
        $ret['total_cnt'] = $PgGame->fetchOne();

        $PgGame->query("SELECT t1.m_ques_pk, t1.status, t1.reward_status, t2.main_title, t2.sub_title,
date_part('epoch', t1.start_dt)::integer as start_dt, date_part('epoch', t1.last_up_dt)::integer as last_up_dt
FROM my_quest t1, m_quest t2
WHERE t1.m_ques_pk = t2.m_ques_pk AND t1.lord_pk = $1 AND t1.reward_status = 'N' AND t1.invisible = 'Y' ORDER BY t2.main_title, t1.m_ques_pk, t1.start_dt", [$_SESSION['selected_lord']['lord_pk']]);
        $PgGame->fetchAll();
        $rows = $PgGame->rows;

        $ret['list'] = $rows;

        $count = $ret['total_cnt'];
        $limit = 10000;

        $total_page = ($count > 0) ? ceil($count/$limit) : 0;
        $page = ($page > $total_page) ? $total_page : $page;

        $response->page = $page;
        $response->total = $total_page;
        $response->records = $count;
        $response->rows = [];

        $i = 0;
        foreach($ret['list'] as $v) {
            $response->rows[$i] = [];
            $response->rows[$i]['id'] = $v['m_ques_pk'];
            $response->rows[$i]['cell'] = [$v['m_ques_pk'], $v['main_title'], $v['sub_title'], getStatus($v['status']), $v['reward_status'], setDate($v['start_dt']), setDate($v['last_up_dt'])];
            $i++;
        }
    }

    return $Render->view(json_encode($response));
}));