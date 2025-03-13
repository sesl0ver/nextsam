<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/lord_own_quest_progress', $Render->wrap(function (array $params) use ($Render, $i18n) {
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

    $PgGame->query("SELECT count(my_ques_pk) FROM my_quest where status = 'P' AND lord_pk = $1 AND invisible='N'", [$_SESSION['selected_lord']['lord_pk']]);

    $count = $PgGame->fetchOne();
    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;

    $PgGame->query("SELECT my_ques_pk, m_ques_pk, date_part('epoch', start_dt)::integer as start_dt FROM my_quest where status = 'P' AND lord_pk = $1 AND invisible='N' ORDER BY m_ques_pk LIMIT $2 OFFSET $3", [$_SESSION['selected_lord']['lord_pk'], $limit, $offset_start]);
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
        $response->rows[$i]['cell'] = [$v['my_ques_pk'], $v['m_ques_pk'], $_M['QUES'][$v['m_ques_pk']]['main_title'], $_M['QUES'][$v['m_ques_pk']]['sub_title'], $_M['QUES'][$v['m_ques_pk']]['description_reward'], setDate($v['start_dt'])];
        $i++;
    }

    return $Render->view(json_encode($response));
}));



$app->post('/admin/gm/api/changeQuestState', $Render->wrap(function (array $params) use ($Render, $i18n) {
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
    $Lord = new Lord($Session, $PgGame);
    $Session->lord = $Lord->getLordInfo($_SESSION['selected_lord']['lord_pk']);
    $Quest = new Quest($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['quest']);

    function delete_sub_quest($_lord_pk, $_m_ques_pk): void
    {
        $PgGame = new Pg('SELECT');
        $PgGame->query('SELECT my_quest.my_ques_pk, my_quest.m_ques_pk FROM my_quest, m_quest WHERE my_quest.m_ques_pk = m_quest.m_ques_pk AND my_quest.lord_pk = $1 AND m_quest.sub_precondition = $2', [$_lord_pk, $_m_ques_pk]);
        while($PgGame->fetch()) {
            $now_quest_info = $PgGame->row;
            $PgGame->query('DELETE FROM my_quest WHERE lord_pk = $1 AND m_ques_pk = $2', [$_lord_pk, $now_quest_info['m_ques_pk']]);
            delete_sub_quest($_lord_pk, $now_quest_info['m_ques_pk']);
        }
    }

    if (!isset($params['lord_pk']))
    {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '선택된 군주 없이 진행할 수 없습니다.']));
    }

    if (!isset($params['selected_my_quest_pk']))
    {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '선택된 퀘스트 없이 진행할 수 없습니다.']));
    }

    if (!isset($params['cause']) || iconv_strlen($params['cause']) < 1)
    {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '변경 사유를 입력해주십시오.']));
    }

    $PgGame->query('SELECT my_ques_pk, m_ques_pk FROM my_quest WHERE lord_pk = $1 AND my_ques_pk = $2', [$params['lord_pk'], $params['selected_my_quest_pk']]);
    if (!$PgGame->fetch()) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '올바르지 않은 퀘스트 정보이므로 진행할 수 없습니다.']));
    }

    $quest_info = $PgGame->row;

    $PgGame->query('SELECT main_posi_pk FROM lord WHERE lord_pk = $1', [$params['lord_pk']]);
    if (!$PgGame->fetch()) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '올바르지 않은 군주 정보이므로 진행할 수 없습니다.']));
    } else {
        if (! isset($PgGame->row['main_posi_pk'])) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '메인 영지가 없는 군주는 퀘스트 변경 작업을 수행할 수 없습니다.']));
        }
    }
    $lord_info = $PgGame->row;

    if ($params['now_state'] == 'progress') {
        // 진행중인 퀘스트를
        if ($params['change_state'] == 'non_reward') {
            $Quest->completeQuest($params['lord_pk'], $quest_info['m_ques_pk']);
        }
        else if ($params['change_state'] == 'rewarded') {
            $Quest->completeQuest($params['lord_pk'], $quest_info['m_ques_pk']);
            $Quest->rewardQuest($params['lord_pk'], $lord_info['main_posi_pk'], $quest_info['m_ques_pk']);
        } else {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '오류 발생.']));
        }
    } else if ($params['now_state'] == 'non_reward') {
        if ($params['change_state'] == 'progress') {
            $PgGame->query('UPDATE my_quest SET status = $1, invisible = $2, reward_status = $3 WHERE lord_pk = $4 AND m_ques_pk = $5', Array('P', 'N', 'N', $params['lord_pk'], $quest_info['m_ques_pk']));

            $PgGame->query('SELECT m_ques_pk, status, reward_status FROM my_quest WHERE lord_pk = $1 AND invisible = $2', Array($params['lord_pk'], 'N'));

            $ques = [];

            while($PgGame->fetch()) {
                $ques[$PgGame->row['m_ques_pk']] = $PgGame->row;
            }

            $Session->sqAppend('QUES', $ques, null, $params['lord_pk']);
            $Quest->setChanged($params['lord_pk']);
        } else if ($params['change_state'] == 'rewarded') {
            $Quest->rewardQuest($params['lord_pk'], $lord_info['main_posi_pk'], $quest_info['m_ques_pk']);
        } else {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '오류 발생.']));
        }
    }
    else if ($params['now_state'] == 'rewarded')
    {
        delete_sub_quest($params['lord_pk'], $quest_info['m_ques_pk']);
        if ($params['change_state'] == 'progress') {
            $PgGame->query('UPDATE my_quest SET status = $1, invisible = $2, reward_status = $3 WHERE lord_pk = $4 AND m_ques_pk = $5', ['P', 'N', 'N', $params['lord_pk'], $quest_info['m_ques_pk']]);
        } else if ($params['change_state'] == 'non_reward') {
            $PgGame->query('UPDATE my_quest SET status = $1, invisible = $2, reward_status = $3 WHERE lord_pk = $4 AND m_ques_pk = $5', ['P', 'N', 'N', $params['lord_pk'], $quest_info['m_ques_pk']]);
            $Quest->completeQuest($params['lord_pk'], $quest_info['m_ques_pk']);
        }  else {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '오류 발생.']));
        }
        $PgGame->query('SELECT m_ques_pk, status, reward_status FROM my_quest WHERE lord_pk = $1 AND invisible = $2', [$params['lord_pk'], 'N']);

        $ques = [];

        while($PgGame->fetch()) {
            $ques[$PgGame->row['m_ques_pk']] = $PgGame->row;
        }

        $Session->sqAppend('QUES', $ques, null, $params['lord_pk']);
        $Quest->setChanged($params['lord_pk']);
    } else {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '오류 발생.']));
    }

    $_quest_state = ['progress' => '진행 중인 퀘스트', 'non_reward' => '보상 미완료 퀘스트', 'rewarded' => '보상 완료 퀘스트'];

    $_now_state = $_quest_state[$params['now_state']];
    $_changed_state = $_quest_state[$params['change_state']];
    $_selected_quest_title = $_M['QUES'][$quest_info['m_ques_pk']]['sub_title'];

    // 히스토리 기록
    $description = [
        'action' => 'change_quest_state',
        'selected_server' => $_SESSION['selected_server'],
        'lord' => $_SESSION['selected_lord'],
        'now_state' => $_now_state,
        'changed_state' => $_changed_state,
        'selected_quest_title' => $_selected_quest_title,
        'selected_m_ques_pk' => $quest_info['m_ques_pk'],
        'cause' => $params['cause']
    ];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'M', serialize($description)]);

    return $Render->view(json_encode(['result' => 'ok']));
}));


