<?php
global $app, $Render, $i18n;

$app->post('/api/lord/rankingCheck', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Lord = new Lord($Session, $PgGame);
    $Lord->getRank($Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/lord/loginReload', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $Session->setLoginReload();
    return $Render->nsXhrReturn('success');
}));

$app->post('/api/lord/findLord', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    if (preg_match("/[^\x{AC00}-\x{D7A3}0-9a-zA-Z]/u", $params['lord_name']) > 0) {
        throw new ErrorHandler('error', $i18n->t('msg_lord_name_confine_language')); // 군주명은 오로지 한글, 영문, 숫자만 가능합니다.
    }

    if (iconv_strlen($params['lord_name'], 'UTF-8') < 1) {
        throw new ErrorHandler('error', $i18n->t('msg_plz_input_lord_name')); // 군주명을 입력해 주십시오.
    } else if (iconv_strlen($params['lord_name'], 'UTF-8') < 2) {
        throw new ErrorHandler('error', $i18n->t('msg_change_lord_name_min')); // 군주명은 최소 2글자를 사용합니다.
    } else if (iconv_strlen($params['lord_name'], 'UTF-8') > 6) {
        throw new ErrorHandler('error', $i18n->t('msg_change_lord_name_max')); // 군주명은 최대 6글자까지 사용합니다.
    }

    $PgGame->query('SELECT lord_pk FROM lord WHERE lord_name = $1', [$params['lord_name']]);
    $data = $PgGame->fetchOne();

    return $Render->nsXhrReturn('success', null, ['lord_pk' => (($data > 0) ? $data : null)]);
}));

$app->post('/api/lord/getLordIntro', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT lord_intro, alli_intro FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $PgGame->fetch();
    $data = ['lord_intro' => (!$PgGame->row['lord_intro'] ? '' : $PgGame->row['lord_intro']), 'alli_intro' => (!$PgGame->row['alli_intro'] ? '' : $PgGame->row['alli_intro'])];

    return $Render->nsXhrReturn('success', null, $data);
}));

$app->post('/api/lord/setLordIntro', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $lord_intro = Useful::convertNotAllowHtmlChar($params['lord_intro']);

    $lord_intro_length = iconv_strlen($lord_intro, 'UTF-8');
    if ($lord_intro_length > 200) {
        throw new ErrorHandler('error', $i18n->t('msg_lord_intro_length_alert', [$lord_intro_length])); // 군주 인사말은 200자 이내로 작성해야 합니다.
    }

    $PgGame->query('UPDATE lord SET lord_intro = $1 WHERE lord_pk = $2', [$lord_intro, $Session->lord['lord_pk']]);
    if ($PgGame->getAffectedRows() != 1) {
        throw new ErrorHandler('error', 'Error Occurred. [31001]'); // 군주 소개를 변경에 실패
    }

    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'lord_introduce_change']);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/lord/setAllianceIntro', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $alli_pk = null;


    $alli_intro = Useful::convertNotAllowHtmlChar($params['alli_intro']);

    $alli_intro_length = iconv_strlen($alli_intro, 'UTF-8');
    if ($alli_intro_length > 200) {
        throw new ErrorHandler('error', $i18n->t('msg_max_count_alliance_intro', [$alli_intro_length])); // 동맹 인사말은 200자 이내로 작성해야 합니다.
    }

    $PgGame->query('SELECT alli_pk FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $alli_pk = $PgGame->fetchOne();

    if ( $alli_pk == null ){
        throw new ErrorHandler('error', $i18n->t('msg_no_alliance_lord')); // 소속된 동맹이 없습니다.
    }

    $PgGame->query('UPDATE lord SET alli_intro = $1 WHERE lord_pk = $2', [$alli_intro, $Session->lord['lord_pk']]);
    if ($PgGame->getAffectedRows() != 1) {
        throw new ErrorHandler('error', 'Error Occurred. [31002]'); // 동맹 소개를 변경에 실패
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/lord/getAllianceIntro', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 군주 인사말 및 군주카드
    $PgGame->query('SELECT alli_intro FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $data['alli_intro'] = $PgGame->fetchOne();

    return $Render->nsXhrReturn('success', null, $data);
}));

$app->post('/api/lord/getLordInfo', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');


    $get_lord_pk = (! isset($params['lord_pk'])) ? $Session->lord['lord_pk'] : $params['lord_pk'];

    // 포인트 가져오기
    $Lord = new Lord($Session, $PgGame);
    $data = $Lord->getLordPoint($get_lord_pk);

    // 군주 인사말 및 군주카드
    $PgGame->query('SELECT lord_pk, main_posi_pk, lord_name, alli_pk, lord_pic, level, position_cnt, power, lord_intro FROM lord WHERE lord_pk = $1', [$get_lord_pk]);
    $PgGame->fetch();
    $lord_info = $PgGame->row;
    $data['lord_pk'] = $lord_info['lord_pk'];
    $data['lord_name'] = $lord_info['lord_name'];
    $data['alli_pk'] = $lord_info['alli_pk'];
    $data['lord_intro'] = (!$lord_info['lord_intro'] ? '' : $lord_info['lord_intro']);
    $data['level'] = $lord_info['level'];
    $data['position_cnt'] = $lord_info['position_cnt'];
    $data['power'] = $lord_info['power'];
    $data['lord_pic'] = (!$lord_info['lord_pic'] ? '1' : $lord_info['lord_pic']);
    $data['posi_pk'] = (isset($lord_info['alli_pk']) && $lord_info['alli_pk'] == $Session->lord['alli_pk']) ? $lord_info['main_posi_pk'] : '-';

    // 랭킹정보
    $PgGame->query('SELECT count(lord_pk) FROM lord WHERE power > $1', [$data['power']]);
    $data['rank'] = $PgGame->fetchOne() + 1;

    /* // 내개인 동맹 여부
    $query_params = Array($Session->lord['lord_pk'], $get_lord_pk, 'Y');
    $PgGame->query('SELECT memb_lord_pk FROM alliance_member WHERE lord_pk = $1 AND memb_lord_pk = $2 AND type = $3', $query_params);
    $data['alliance'] = $PgGame->fetchOne();

    if ($data['alliance'])
    {
        $query_params = Array($data['alliance'], 'T');
        $PgGame->query('SELECT b.posi_pk, b.title FROM position a, territory b WHERE a.lord_pk = $1 AND a.type = $2 AND a.posi_pk = b.posi_pk', $query_params);
        while($PgGame->fetch())
        {
            $data['alli_position'][] = $PgGame->row;
        }
    }

    // 개인 동맹 정보
    $query_params = Array($get_lord_pk, 'Y');
    $PgGame->query('SELECT COUNT(lord_pk) FROM alliance_member WHERE lord_pk = $1 AND type = $2', $query_params);
    $data['alliance_member'] = $PgGame->fetchOne();*/

    // 일반 동맹 정보
    $PgGame->query('SELECT alli_pk, title FROM alliance WHERE alli_pk = $1', [$data['alli_pk']]);
    $PgGame->fetch();
    $data['alliance'] = $PgGame->row;
    // 동맹 등급 가져오기
    if (is_array($data['alliance'])) {
        $PgGame->query('SELECT level FROM alliance_member WHERE lord_pk = $1 AND alli_pk = $2', [$data['lord_pk'], $data['alli_pk']]);
        $data['alliance']['level'] = $PgGame->fetchOne();
        // 플레이어 동맹 등급
        if ($Session->lord['alli_pk']) {
            $PgGame->query('SELECT level FROM alliance_member WHERE lord_pk = $1 AND alli_pk = $2', [$Session->lord['lord_pk'], $Session->lord['alli_pk']]);
            $data['my_alliance_grade'] = $PgGame->fetchOne();
            $PgGame->query('SELECT 1 FROM alliance WHERE master_lord_pk = $1 AND alli_pk = $2', [$Session->lord['lord_pk'], $Session->lord['alli_pk']]);
            $data['alliance']['master_lord'] = $PgGame->fetchOne();
            $PgGame->query('SELECT A.alli_intro from lord A LEFT JOIN alliance B ON A.lord_pk = B.master_lord_pk WHERE B.alli_pk = $1', [$Session->lord['alli_pk']]);
            $data['alliance']['alli_intro'] = $PgGame->fetchOne();
        }
    }

    return $Render->nsXhrReturn('success', null, $data);
}));

$app->post('/api/lord/roamerLoad', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['uuid']);
    $PgGame = new Pg('DEFAULT');
    $PgCommon = new Pg('COMMON');

    $Auth = new Auth($PgCommon, $params['uuid'], $params['lc'], $params['token'], $params['platform']);

    try {
        $Auth->getAccount();
    } catch (Throwable $e) {
        throw new ErrorHandler('error', $e->getMessage());
    }

    if ($Auth->need_membership) {
        throw new ErrorHandler('error', $i18n->t('msg_app_retry_please')); // 앱을 종료 후 다시 시도해 주세요.
    }

    $account_id = $Auth->account_info['account_pk'];
    $web_channel = $Auth->lc;

    $PgGame->query('SELECT lord_pk FROM lord_web WHERE web_id = $1 AND web_channel = $2', [$account_id, $web_channel]);
    if ($PgGame->fetch()) {
        $lord_pk = $PgGame->row['lord_pk'];
    } else {
        throw new ErrorHandler('error', $i18n->t('msg_unregistered_user')); // 등록되지 않은 유저 입니다.
    }

    $Session = new Session(true, true);

    // 유저 블럭 확인 (어차피 블럭시 session 을 없애기 때문에 시작시만 체크해도 됨.)
    if (! isset($params['lc']) || $params['lc'] != 0) {
        $PgGame->query('SELECT is_user_blocked, blocked_cause FROM lord WHERE lord_pk = $1', [$lord_pk]);
        $PgGame->fetch();
        if ($PgGame->row['is_user_blocked'] === 'Y') {
            // 유저 블록 상태면 페이지 이동(아직 시간은 안씀)
            $_add_data['state'] = 'block';
            $_add_data['message'] = $PgGame->row['blocked_cause'];
            return $Render->nsXhrReturn('success', null, $_add_data);
        }
    }

    // 로그인 처리 시작
    $Lord = new Lord($Session, $PgGame);
    $result = $Lord->getLogin($lord_pk);
    if ($result !== 1) {
        throw new ErrorHandler('error', 'Login failed. (err1)');
    }

    // 군주 정보
    $PgGame->query('SELECT main_posi_pk, lord_name, power, lord_hero_pk FROM lord WHERE lord_pk = $1', [$lord_pk]);
    $PgGame->fetch();
    $row = $PgGame->row;

    // 방랑 군주가 아닌 유저가 요청 할 수 없음
    if (isset($row['main_posi_pk'])) {
        throw new ErrorHandler('error', $i18n->t('msg_not_wandering_lord')); // 방랑군주가 아닙니다.
    }

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Bdic = new Bdic($Session, $PgGame, $Resource, $GoldPop);
    $Hero = new Hero($Session, $PgGame);
    $Power = new Power($Session, $PgGame);
    $Territory = new Territory($Session, $PgGame);
    $Item = new Item($Session, $PgGame);
    $Letter = new Letter($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    $lord_name = $row['lord_name'];
    $set_area = 1;
    $title = 'New Territory';

    /*if (!$Session->Cache->conn)
    {
        throw new ErrorHandler('error', '서버 접속에 실패하였습니다. 다시 시도하여 주십시오.');
        exit();
    }*/

    try {
        $PgGame->begin();

        $PgGame->query('select posi_stat_pk from m_preference');
        $posi_stat_pk = $PgGame->fetchOne();

        // 영지 등록 프로시저 호출
        $PgGame->query('select * from getposition($1, $2, $3)', [$posi_stat_pk, $lord_pk, $set_area]);
        $posi_pk = $PgGame->fetchOne();

        if (!$posi_pk) {
            throw new Exception( 'Error Occurred. [31003]'); // 영지 생성에 실패하였습니다.
        }

        $PgGame->query('select * from createterritory($1, $2, $3, $4)', [$lord_pk, $posi_pk, $title, iconv_substr($lord_name, 0, 4, 'utf-8')]);
        $r = $PgGame->fetchOne();

        if ($r == 1) {
            $PgGame->query('UPDATE lord SET main_posi_pk = $1, roamer_inactive = $3 WHERE lord_pk = $2', [$posi_pk, $lord_pk, 'N']);

            $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
            $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc); // TODO ? 사용안하네?
            $FigureReCalc->createTerritory($posi_pk);

            $PgGame->query("UPDATE my_hero SET status = 'A', status_cmd = 'I', posi_pk = $1 WHERE lord_pk = $2 AND status = 'G' AND posi_pk IS NULL AND m_offi_pk IS NOT NULL", [$posi_pk, $lord_pk]);
            $Hero->setCommand($posi_pk, $row['lord_hero_pk'], 'A');
            $Bdic->heroAssign($posi_pk, 1, $row['lord_hero_pk'], true);

            $power = $Power->getQuestPower($lord_pk) + $Power->getHeroPower($lord_pk) + $Power->getLordTechniquePower($lord_pk);

            $PgGame->query('UPDATE lord SET power = $3, num_appoint_hero = ( SELECT COUNT(*) FROM my_hero WHERE lord_pk = $1 AND status = $2 ) WHERE lord_pk = $1', [$lord_pk, 'A', $power]);

            // 개척 영지 보호
            $Territory->setTruceStatus($posi_pk, 'D', 500106);

            $Session->setLoginReload();

            if ($row['power'] > 0) {
                $_title = $i18n->t('letter_roamer_lord_subject'); // 군주님의 새로운 도전을 응원합니다.
                // 군주님의 새로운 도전을 진심으로 응원합니다.<br/>기존에 보유하셨던 영웅과 태학기술의 업그레이드는 그대로 유지됩니다.<br/>빠른 시일내에 재기하시길 진심으로 바랍니다.<br/><br/>(정착 지원상자는 보유하고 있을 경우나 영향력이 0인 경우는 추가 지급되지 않습니다.)
                $_content = $i18n->t('letter_roamer_lord_content_no_item');

                // 정착 지원 아이템 발급
                $PgGame->query('SELECT my_item_pk FROM my_item WHERE item_pk = $1 AND lord_pk = $2', [500134, $lord_pk]);
                if (!$PgGame->fetch()) {
                    // // "군주님의 새로운 도전을 진심으로 응원합니다.<br/>기존에 보유하셨던 영웅과 태학기술의 업그레이드는 그대로 유지됩니다.<br/><br/>추가로 안정적인 정착을 위해 아래 아이템을 지원해 드리오니 빠른 시일내에 재기하시길 진심으로 바랍니다.<br/><br/>지원 아이템은 보물창고 -> 패키지에서 확인하실 수 있습니다.<br/><br/>지원 아이템 : 정착 지원 상자<br/><br/>(단, 이미 정착 지원 상자를 보유하고 계실 경우 추가 지급되지 않습니다.)"
                    $_content = $i18n->t('letter_roamer_lord_content');
                    $rp = $Item->BuyItem($lord_pk, 500134, 1, 'roamer_lord_support');
                    if (!$rp) {
                        throw new Exception('Error Occurred. [31004]'); // 정착 지원 아이템 지급 중 오류가 발생
                    }
                }

                $Letter->sendLetter(2, [$lord_pk], ['title' => $_title, 'content' => $_content, 'type' => 'S'], true);
            }

            $Log->setTerritory($lord_pk, $posi_pk, 'roamer_lord_create_territory');
        } else {
            throw new Exception('Error Occurred. [31005]'); // 영지 생성에 실패
        }

        $PgGame->commit();
    } catch(Exception $e) {
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    return $Render->nsXhrReturn('success', null, ['posi_pk' => $posi_pk]);
}));
