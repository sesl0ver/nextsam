<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/send_gm_letter_list', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    function getDescription($obj, $type)
    {
        $obj = unserialize($obj);

        if($obj['action'] == 'send_gm_letter') {
            $subject = $obj['letter_body']['title'];
            $content = $obj['letter_body']['content'];
            $str = '';
            if(is_array($obj['receiver_list'])) {
                foreach($obj['receiver_list'] as $k => $v) {
                    if ($str !== '') {
                        $str .= ' , ';
                    }
                    foreach($_SESSION['server_list'] as $value) {
                        if ($k == $value['server_pk']) {
                            $str .= $value['server_name'];
                        }
                    }
                    $str .= '[';
                    foreach($v as $key => $value) {
                        if ($key > 0) {
                            $str .= ',';
                        }
                        $str .= " {$value['lord_name']} ";
                    }
                    $str .= ']';
                }
            }

            if($type == 1) {
                return $str;
            } else if($type == 2) {
                return $subject;
            } else if($type == 3) {
                return htmlspecialchars($content);
            }
        }
        return json_encode($obj);
    }

    $page = $params['page']; // get the requested page
    $limit = $params['rows']; // get how many rows we want to have into the grid

    $PgGm = new Pg('GM');
    $PgGm->query('SELECT server_pk, server_name, db_ip, db_port, db_account, db_password FROM server');
    $PgGm->fetchAll();
    $server_list = $PgGm->rows;

    $PgGm->query('SELECT count(log_pk) FROM gm_log where type = $1', ['G']);
    $count = $PgGm->fetchOne();

    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;

    $offset_start = ($offset_start < 0) ? 0 : $offset_start;
    $PgGm->query("SELECT date_part('epoch', regist_dt)::integer as regist_dt, gm_id, description FROM gm_log where type=$3 order by regist_dt desc LIMIT $1 OFFSET $2", [$limit, $offset_start, 'G']);

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    $i = 0;
    while ($PgGm->fetch()) {
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $PgGm->row['regist_dt'];
        $response->rows[$i]['cell'] = [date('Y-m-d H:i:s', $PgGm->row['regist_dt']), $PgGm->row['description'], $PgGm->row['description'], $PgGm->row['gm_id'], $PgGm->row['description']];
        $i++;
    }

    foreach($response->rows as &$v) {
        $v['cell'][1] = getDescription($v['cell'][1],1);
        $v['cell'][2] = getDescription($v['cell'][2],2);
        $v['cell'][4] = getDescription($v['cell'][4],3);
        $v['cell'][4] = nl2br($v['cell'][4]);
    }

    return $Render->view(json_encode($response));
}));