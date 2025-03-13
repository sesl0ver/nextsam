<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/lord_own_quest_rewarded', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $page = $params['page']; // get the requested page
    $limit = $params['rows'];

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $PgGame->query("SELECT count(my_ques_pk) FROM my_quest where  (status = 'C' AND reward_status = 'Y' AND lord_pk = $1)
or (status = 'P' AND reward_status = 'N' AND lord_pk = $1 AND invisible='Y' AND (m_ques_pk>='600101' AND m_ques_pk<='600111'))", [$_SESSION['selected_lord']['lord_pk']]);

    $count = $PgGame->fetchOne();
    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;

    $PgGame->query("SELECT my_ques_pk, m_ques_pk, date_part('epoch', start_dt)::integer as start_dt, date_part('epoch', last_up_dt)::integer as last_up_dt FROM my_quest where  (status = 'C' AND reward_status = 'Y' AND lord_pk = $1)
or (status = 'P' AND reward_status = 'N' AND lord_pk = $1 AND invisible='Y' AND (m_ques_pk>='600101' AND m_ques_pk<='600111')) ORDER BY m_ques_pk LIMIT $2 OFFSET $3", [$_SESSION['selected_lord']['lord_pk'], $limit, $offset_start]);
    $PgGame->fetchAll();
    $rows = $PgGame->rows;

    $ret['list'] = $rows;

    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    function setDate($_datetime): string
    {
        return date('Y-m-d H:i:s', $_datetime);
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['quest']);

    $i = 0;
    foreach($ret['list'] as $v) {
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $v['my_ques_pk'];
        $response->rows[$i]['cell'] = [$v['my_ques_pk'], $v['m_ques_pk'], $_M['QUES'][$v['m_ques_pk']]['main_title'], $_M['QUES'][$v['m_ques_pk']]['sub_title'], $_M['QUES'][$v['m_ques_pk']]['description_reward'], setDate($v['start_dt']), setDate($v['last_up_dt'])];
        $i++;
    }

    return $Render->view(json_encode($response));
}));


