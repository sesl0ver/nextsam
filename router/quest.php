<?php
global $app, $Render, $i18n;

$app->post('/api/quest/reward', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // TODO 소셜 기능 하던 코드는 사용하지 않을 것이므로 걷어냈음.

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG, $_M;
    $NsGlobal->requireMasterData(['quest']);
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        $Quest = new Quest($Session, $PgGame);
        $ret = $Quest->rewardQuest($Session->lord['lord_pk'], $params['posi_pk'], $params['m_ques_pk']);
        if(! $ret) {
            throw new Exception($NsGlobal->getErrorMessage());
        }

        // 퀘스트 클리어에 대한 퀘스트 체크
        $clear_quest = $_M['QUES'][$params['m_ques_pk']];
        if ($clear_quest['type'] == 'event') {
            if ($clear_quest['goal_type'] != 'EVENT_QUEST_CLEAR') {
                $Quest->countCheckQuest($Session->lord['lord_pk'], 'EVENT_QUEST_CLEAR', ['value' => 1]);
            }
        } else if ($clear_quest['type'] == 'daily_event') {
            if ($clear_quest['goal_type'] != 'EVENT_DAILY_CLEAR') {
                $Quest->countCheckQuest($Session->lord['lord_pk'], 'EVENT_DAILY_CLEAR', ['value' => 1]);
            }
            if ($clear_quest['goal_type'] == 'EVENT_LOGIN') {
                $Quest->countCheckQuest($Session->lord['lord_pk'], 'EVENT_LOGIN_CHECK', ['value' => 1]);
            }
        }

        // 추천인 체크
        //$ret = $Quest->checkRecommend($params['m_ques_pk'], $Session->lord['lord_pk']);

        /*if ($ret) {
            // 값이 존재하면 외교서신
            $letter = Array();
            $letter['type'] = 'S';
            $letter['title'] = $ret['server_name'].'서버 '.$ret['reco_lord_name'].'님께서 축하의 선물을 보내셨습니다.';
            $letter['content'] = <<< EOF
{$ret['server_name']}서버 {$ret['reco_lord_name']}님께서 군주 {$ret['level']}등급 달성을 축하하며 강한 군주로
성장하기를 기대 하고 있습니다.

보상 : {$ret['item_title']}
EOF;
            $Letter->sendLetter(ADMINI_LORD_PK, Array($Session->lord['lord_pk']), $letter, true, 'Y');
        }*/

        // 보상 완료한 퀘스트 제거를 위해
        $Session->sqAppend('QUES', [$params['m_ques_pk'] => null], null, $Session->lord['lord_pk']);

        $PgGame->commit();
    } catch(Exception $e) {
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/quest/making', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['quest']);

    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'making', 'posi_pk' => $params['posi_pk'], 'm_ques_pk' => $params['m_ques_pk']]);

    $data_arr = ['m_ques_pk' => $params['m_ques_pk'], 'point_coin' => 0];
    if ($_M['QUES'][$params['m_ques_pk']]['goal_type'] == 'MAKING_COIN') {
        $PgGame->query('SELECT point_coin FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
        $data_arr['point_coin'] = $PgGame->fetchOne();
    }

    return $Render->nsXhrReturn('success', null, $data_arr);
}));

$app->post('/api/quest/completeCount', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // TODO 미사용
    $Quest = new Quest($Session, $PgGame);
    $quest_cnt = $Quest->getCompleteQuestCount($Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success', null, ['quest_cnt' => $quest_cnt]);
}));

$app->post('/api/quest/inviteClub', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'invite_club']);
    return $Render->nsXhrReturn('success');
}));

$app->post('/api/quest/assess', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Quest = new Quest($Session, $PgGame);

    // TODO 원래는 다른곳에 있었늕데 이쪽으로 옮김. - 나중에 따로 정리필요.
    // $MARKET_URL_SCHEME = [];
    // $MARKET_URL_SCHEME['AS'] = 'http://itunes.apple.com/kr/app/id702769902?mt=8';
    // $MARKET_URL_SCHEME['PS'] = 'market://details?id=com.patigames.socialsamgukji';
    // $MARKET_URL_SCHEME['OL'] = 'cstore://detail?CONTENT_TYPE=APPLICATION&P_TYPE=c&N_ID=81016D6A&P_ID=51200016612507&CAT_TYPE=GAME&IS_UPDATE=1';
    // $MARKET_URL_SCHEME['TS'] = 'tstore://PRODUCT_VIEW/0000643464/0';

    return $Render->nsXhrReturn('success'); // , null, ['url' => $MARKET_URL_SCHEME[$params['platform']]]
}));

$app->post('/api/quest/check', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 자원 체크하기
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'territory', 'posi_pk' => $params['posi_pk']]);

    if ($Session->lord['last_login_dt'] < mktime(0, 5, 1, date('m'), date('d'), date('Y'))) {
        $Session->sqAppend('NEXT_DAY', true, null, $Session->lord['lord_pk']);
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/quest/goalCheck', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Quest = new Quest($Session, $PgGame);
    $Item = new Item($Session, $PgGame);

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['quest']);
    if (isset($_M['QUES'][$params['m_ques_pk']])) {
        $m = $_M['QUES'][$params['m_ques_pk']];
        if (in_array($m['goal_type'], ['GIVE_ITEM', 'EXCHANGE_ITEM'])) {
            $condition_check = 0;
            for ($i = 1; $i <= $m['condition_count']; $i++) {
                $condition = explode(':', $m["condition_$i"]);
                $item_cnt = $Item->getItemCount($Session->lord['lord_pk'], $condition[0]);
                if ($item_cnt >= $condition[1]) {
                    $condition_check++;
                }
            }
            if ($condition_check == $m['condition_count']) {
                $Quest->completeQuest($Session->lord['lord_pk'], $params['m_ques_pk']);
            } else {
                $Quest->progressQuest($Session->lord['lord_pk'], $params['m_ques_pk']);
            }
        }
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/quest/timer', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');


    /*
    // 2013.01 접신 timer 이벤트 (접속시간)
    // 연속된 시간 체크가 어려워 1시간마다 js 에서 이 부분을 호출하고 퀘스트의 상태가 P인 첫번째 값을 C로 update 처리함.

    $sql = 'SELECT  m_ques_pk, status FROM my_quest WHERE lord_pk = '.$Session->lord['lord_pk'].' and m_ques_pk IN (600107, 600108, 600109) order by m_ques_pk asc';
    $query_param = Array();
    $PgGame->query($sql, $query_params);

    $status['600107'] = 'P';
    $status['600108'] = 'P';
    $status['600109'] = 'P';
    print_r($status);

    while ($PgGame->fetch()){

        $db_m_ques_pk = $PgGame->row['m_ques_pk'];
        $db_status = $PgGame->row['status'];

        if($db_status == 'C') {
            $status[$db_m_ques_pk] = $db_status;
        }
    }
    print_r($status);

    if($key = array_search('P', $status, TRUE)){
        //echo 'key='.$key;
        $Quest->conditionCheckQuest($Session->lord['lord_pk'], Array('quest_type' => 'timer', 'm_ques_pk' => $key));
    }

    return $Render->nsXhrReturn('success');
    exit;
    */

    return $Render->nsXhrReturn('success');
}));