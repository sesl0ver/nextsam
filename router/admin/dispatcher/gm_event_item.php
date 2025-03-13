<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/giveEventItem', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $arr_lord_name = explode("\n", $params['lord_name']) ?? null;
    $m_item_pk = $params['m_item_pk'] ?? null;
    $item_count = $params['item_count'] ?? null;
    $server_pk = $params['server_pk'] ?? null;

    if (count($arr_lord_name) < 1) {
        return $Render->view(json_encode(['result' => false, 'msg' => '군주명을 적어주세요.']));
    }

    if (!strlen($m_item_pk)) {
        return $Render->view(json_encode(['result' => false, 'msg' => '아이템 코드를 적어주세요.']));
    }

    if (!strlen($item_count)) {
        return $Render->view(json_encode(['result' => false, 'msg' => '아이템 개수를 적어주세요.']));
    }

    if (!strlen($server_pk)) {
        return $Render->view(json_encode(['result' => false, 'msg' => '서버를 선택해주세요.']));
    }

    if ($item_count < 1) {
        return $Render->view(json_encode(['result' => false, 'msg' => '아이템은 1개 이상 지급되어야 합니다.']));
    }

    $Gm = new Gm();
    $Gm->selectPgGame($server_pk);
    $PgGame = new Pg('SELECT');

    $Session = new Session(false);
    $Item = new Item($Session, $PgGame);
    $result = [];

    $PgLog = new Pg('SELECT_LOG');

    $i = 0;
    foreach($arr_lord_name AS $lord_name) {
        $_lord_name = preg_replace("/\s+/","", $lord_name);

        $PgGame->query('SELECT lord_pk, main_posi_pk FROM lord WHERE lord_name = $1', [$_lord_name]);
        $PgGame->fetch();
        if (!isset($PgGame->row['lord_pk'])) {
            $result[$i] = ['name' => $lord_name, 'text' => '없는 군주 명입니다.', 'date' => date('Y-m-d H:i:s')];
            continue;
        }
        $lord_pk = $PgGame->row['lord_pk'];
        $posi_pk = $PgGame->row['main_posi_pk'];

        $r = $Item->BuyItem($lord_pk, $m_item_pk, $item_count, 'event');
        if ($r) {
            $push_data = [];
            $push_data[$m_item_pk] = ['item_cnt' => $Item->getItemCount($lord_pk, $m_item_pk)];
            $Session->sqAppend('ITEM', $push_data, null, $lord_pk, $posi_pk);
            $result[$i] = ['name' => $lord_name, 'text' => '아이템 지급 성공', 'date' => date('Y-m-d H:i:s')];
        } else {
            $result[$i] = ['name' => $lord_name, 'text' => '아이템 지급 실패', 'date' => date('Y-m-d H:i:s')];
        }
        $i ++;
    }


    return $Render->view(json_encode(['result' => true, 'd' => $result]));
}));