<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/lord_own_alliance', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    function getLevelTypeName($_level): string
    {
        if ($_level == 2) {
            $level_name = '부맹주';
        } else if ($_level == 3) {
            $level_name = '감찰';
        } else if ($_level == 4) {
            $level_name = '임원';
        } else if ($_level == 5) {
            $level_name = '동맹원';
        } else {
            $level_name = '맹주';
        }
        return $level_name;
    }

    function getAlliTypeName($_type)
    {
        if ($_type == 'H') {
            $result = '적대';
        } else if ($_type == 'F') {
            $result = '우호';
        }  else if ($_type == 'N') {
            $result = '중립';
        } else {
            $result = $_type;
        }
        return $result;
    }

    if (isset($params['mode'])) {
        $page = $params['page']; // get the requested page
        $limit = $params['rows']; // get how many rows we want to have into the grid

        $response = new stdClass();
        if ($params['mode'] === 'member_list') {
            $PgGame->query('SELECT count(lord_pk) FROM alliance_member WHERE alli_pk = $1', [$params['alli_pk']]);
            $count = $PgGame->fetchOne();
            if ($count < 1) {
                echo json_encode([]);
                exit;
            }

            $total_page = ceil($count/$limit);
            $page = ($page > $total_page) ? $total_page : $page;
            $offset_start = $limit * $page - $limit;

            $PgGame->query('SELECT lord_pk, level FROM alliance_member WHERE alli_pk = $1 ORDER BY level LIMIT $2 OFFSET $3', [$params['alli_pk'], $limit, $offset_start]);
            $PgGame->fetchAll();

            $response->page = $page;
            $response->total = $total_page;
            $response->records = $count;
            $response->rows = [];

            $i = 0;
            $result_list = $PgGame->rows;

            $g_lord_name_array = [];
            foreach($result_list as $v) {
                if (! isset($g_lord_name_array[$v['lord_pk']])) {
                    $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$v['lord_pk']]);
                    $g_lord_name_array[$v['lord_pk']] = $PgGame->fetchOne();
                }
                $response->rows[$i] = [];
                $response->rows[$i]['id'] = $v['lord_pk'];
                $response->rows[$i]['cell'] = [$g_lord_name_array[$v['lord_pk']], getLevelTypeName($v['level'])];
                $i++;
            }
        } else if ($params['mode'] == 'alliance_relation') {
            $PgGame->query('SELECT count(t1.rel_alli_pk) FROM alliance_relation t1, alliance t2 WHERE t1.rel_alli_pk = t2.alli_pk AND t1.alli_pk = $1', [$params['alli_pk']]);
            $count = $PgGame->fetchOne();
            if ($count < 1) {
                echo json_encode([]);
                exit;
            }

            $total_page = ceil($count/$limit);
            $page = ($page > $total_page) ? $total_page : $page;
            $offset_start = $limit * $page - $limit;

            $PgGame->query('SELECT t1.rel_alli_pk, t1.rel_type, date_part(\'epoch\', t1.regist_dt)::integer as regist_dt, t2.title, t2.master_lord_pk 
FROM alliance_relation t1, alliance t2 WHERE t1.rel_alli_pk = t2.alli_pk AND t1.alli_pk = $1 ORDER BY t1.regist_dt DESC LIMIT $2 OFFSET $3', [$params['alli_pk'], $limit, $offset_start]);
            $PgGame->fetchAll();

            $response = new stdClass();
            $response->page = $page;
            $response->total = $total_page;
            $response->records = $count;
            $response->rows = [];

            $i = 0;
            $result_list = $PgGame->rows;

            $g_lord_name_array = [];
            foreach($result_list as $v) {
                if (! isset($g_lord_name_array[$v['master_lord_pk']])) {
                    $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$v['master_lord_pk']]);
                    $g_lord_name_array[$v['master_lord_pk']] = $PgGame->fetchOne();
                }
                $response->rows[$i] = [];
                $response->rows[$i]['id'] = $v['rel_alli_pk'];
                $response->rows[$i]['cell'] = [date('Y-m-d H:i:s', $v['regist_dt']), getAlliTypeName($v['rel_type']), $v['title'], $g_lord_name_array[$v['master_lord_pk']]];
                $i++;
            }
        } else if ($params['mode'] == 'history') {
            $PgGame->query('SELECT count(alli_hist_pk) FROM alliance_history WHERE alli_pk = $1', [$params['alli_pk']]);
            $count = $PgGame->fetchOne();
            if ($count < 1) {
                echo json_encode([]);
                exit;
            }

            $total_page = ceil($count/$limit);
            $page = ($page > $total_page) ? $total_page : $page;
            $offset_start = $limit * $page - $limit;

            $PgGame->query('SELECT alli_hist_pk, date_part(\'epoch\', regist_dt)::integer as regist_dt, type, title FROM alliance_history
WHERE alli_pk = $1 ORDER BY regist_dt DESC LIMIT $2 OFFSET $3', [$params['alli_pk'], $limit, $offset_start]);
            $PgGame->fetchAll();

            $response = new stdClass();
            $response->page = $page;
            $response->total = $total_page;
            $response->records = $count;
            $response->rows = [];

            $i = 0;
            $result_list = $PgGame->rows;

            foreach($result_list as $v) {
                $response->rows[$i] = [];
                $response->rows[$i]['id'] = $v['alli_hist_pk'];
                $response->rows[$i]['cell'] = [date('Y-m-d H:i:s', $v['regist_dt']), $v['type'],  $v['title']];
                $i++;
            }
        } else if ($params['mode'] == 'war_history') {
            $PgGame->query('SELECT count(alli_war_hist_pk) FROM alliance_war_history WHERE alli_pk = $1', [$params['alli_pk']]);
            $count = $PgGame->fetchOne();
            if ($count < 1) {
                echo json_encode([]);
                exit;
            }

            $total_page = ceil($count/$limit);
            $page = ($page > $total_page) ? $total_page : $page;
            $offset_start = $limit * $page - $limit;

            $PgGame->query('SELECT alli_war_hist_pk, repo_pk, alli_pk, adve_alli_pk, date_part(\'epoch\', regist_dt)::integer as regist_dt, type, title FROM
alliance_war_history WHERE alli_pk = $1 ORDER BY regist_dt DESC LIMIT $2 OFFSET $3', [$params['alli_pk'], $limit, $offset_start]);
            $PgGame->fetchAll();

            $response = new stdClass();
            $response->page = $page;
            $response->total = $total_page;
            $response->records = $count;
            $response->rows = [];

            $i = 0;
            $result_list = $PgGame->rows;

            $g_alli_name_array = [];
            foreach($result_list as $v)
            {
                if (! isset($g_alli_name_array[$v['alli_pk']])) {
                    $PgGame->query('SELECT title FROM alliance WHERE alli_pk = $1', [$v['alli_pk']]);
                    $g_alli_name_array[$v['alli_pk']] = $PgGame->fetchOne();
                }
                if (! isset($g_alli_name_array[$v['adve_alli_pk']])) {
                    $PgGame->query('SELECT title FROM alliance WHERE alli_pk = $1', [$v['adve_alli_pk']]);
                    $g_alli_name_array[$v['adve_alli_pk']] = $PgGame->fetchOne();
                }
                $response->rows[$i] = [];
                $response->rows[$i]['id'] = $v['alli_war_hist_pk'];
                $response->rows[$i]['cell'] = [date('Y-m-d H:i:s', $v['regist_dt']),  $g_alli_name_array[$v['alli_pk']],  $g_alli_name_array[$v['adve_alli_pk']], $v['type'], $v['repo_pk'],  $v['title']];
                $i++;
            }
        }

        return $Render->view(json_encode($response));
    } else {
        $PgGame->query('SELECT alli_pk FROM lord WHERE lord_pk = $1', [$_SESSION['selected_lord']['lord_pk']]);
        $alli_pk = $PgGame->fetchOne();
        if (! $alli_pk) {
            return $Render->view(json_encode(['result' => 'ok']));
        } else {
            $PgGame->query('SELECT alli_pk, title, master_lord_pk, now_member_count, max_member_count, attack_point, defence_point, power, regist_dt FROM alliance WHERE alli_pk = $1', [$alli_pk]);
            $PgGame->fetch();
            $row = $PgGame->row;

            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$row['master_lord_pk']]);
            $row['master_lord_name'] =  $PgGame->fetchOne();
        }
        return $Render->view(json_encode(['result' => 'ok', 'alliance_info' => $row]));
    }
}));


