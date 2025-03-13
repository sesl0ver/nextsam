<?php
global $app, $Render, $i18n;

$app->post('/api/letter/receiveList', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Letter = new Letter($Session, $PgGame);
    $total_count = $Letter->getLetterTotalCount($Session->lord['lord_pk'], 'receive');

    // 받은 서신이 하나도 없는 경우 - TODO 중복코드를 하나로 합칠수 있을 듯?
    if ($total_count < 1) {
        return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'letter_list' => []]);
    }

    $total_page = (INT)($total_count / REPORT_LETTER_PAGE_NUM);
    $total_page += ($total_count % REPORT_LETTER_PAGE_NUM > 0)? 1 : 0;

    $page_num = $params['page_num'];

    if ($page_num < 1) {
        $page_num = 1;
    } else if ($page_num > $total_page) {
        $page_num = $total_page;
    }

    $ret = false;
    if ($page_num > 0) {
        $ret = $Letter->getReceiveLetter($Session->lord['lord_pk'], 'N', $page_num);
    } else {
        $page_num = 1;
        $total_page = 1;
    }

    $ret = (!$ret || !count($ret)) ? [] : $ret;

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'total_page' => $total_page, 'curr_page' => $page_num, 'letter_list' => $ret]);
}));

$app->post('/api/letter/sendList', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Letter = new Letter($Session, $PgGame);
    $total_count = $Letter->getLetterTotalCount($Session->lord['lord_pk'], 'send');

    // 받은 서신이 하나도 없는 경우
    if ($total_count < 1) {
        return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'letter_list' => []]);
    }

    $total_page = (INT)($total_count / REPORT_LETTER_PAGE_NUM);
    $total_page += ($total_count % REPORT_LETTER_PAGE_NUM > 0)? 1 : 0;

    $page_num = $params['page_num'];

    if ($page_num < 1) {
        $page_num = 1;
    } else if ($page_num > $total_page) {
        $page_num = $total_page;
    }

    $ret = false;
    if ($page_num > 0) {
        $ret = $Letter->getSendLetter($Session->lord['lord_pk'], 'N', $page_num);
    } else {
        $page_num = 1;
        $total_page = 1;
    }

    $ret = (!$ret || !count($ret)) ? [] : $ret;

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'total_page' => $total_page, 'curr_page' => $page_num, 'letter_list' => $ret]);
}));

$app->post('/api/letter/systemList', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Letter = new Letter($Session, $PgGame);
    $total_count = $Letter->getLetterTotalCount($Session->lord['lord_pk'], 'system');

    // 받은 서신이 하나도 없는 경우
    if ($total_count == 0) {
        return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'letter_list' => []]);
    }

    $total_page = (INT)($total_count / REPORT_LETTER_PAGE_NUM);
    $total_page += ($total_count % REPORT_LETTER_PAGE_NUM > 0)? 1 : 0;

    $page_num = $params['page_num'];

    if ($page_num < 1) {
        $page_num = 1;
    } else if ($page_num > $total_page) {
        $page_num = $total_page;
    }

    $ret = false;
    if ($page_num > 0) {
        $ret = $Letter->getReceiveLetter($Session->lord['lord_pk'], 'S', $page_num);
    } else {
        $page_num = 1;
        $total_page = 1;
    }

    $ret = (!$ret || !count($ret)) ? [] : $ret;

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'total_page' => $total_page, 'curr_page' => $page_num, 'letter_list' => $ret]);
}));

$app->post('/api/letter/readCheck', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $lett_pk_arr = explode(',', $params['lett_pk_list']);
    if (!is_array($lett_pk_arr) || count($lett_pk_arr) < 1) {
        throw new ErrorHandler('error', 'Error Occurred. [30001]'); // 올바르지 않은 서신 목록이 전달
    }
    foreach($lett_pk_arr as $lett_pk) {
        if (!preg_match('/^[\d]+$/', $lett_pk)) {
            throw new ErrorHandler('error', 'Error Occurred. [30002]'); // 올바르지 않은 서신 목록이 전달
        }
    }
    $Letter = new Letter($Session, $PgGame);
    $Letter->setReadLetter($lett_pk_arr);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/letter/read', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Letter = new Letter($Session, $PgGame);
    $ret = $Letter->readLetter($params['lett_pk'], $params['lett_type']);

    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/letter/remove', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $lett_pk_arr = explode(',', $params['lett_pk_list']);
    if (!is_array($lett_pk_arr) || count($lett_pk_arr) < 1) {
        throw new ErrorHandler('error', 'Error Occurred. [30003]'); // 올바르지 않은 서신 목록이 전달
    }
    foreach($lett_pk_arr as $lett_pk) {
        if (!preg_match('/^[\d]+$/', $lett_pk)) {
            throw new ErrorHandler('error', 'Error Occurred. [30004]'); // 올바르지 않은 서신 목록이 전달
        }
    }
    $Letter = new Letter($Session, $PgGame);
    $Letter->deleteLetter($lett_pk_arr, $params['lett_type']);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/letter/findLordName', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Redis = new RedisCache();

    $Letter = new Letter($Session, $PgGame);
    $row = $Letter->getLordInfoByLordName($params['lord_name']);

    if (! $row && !is_array($row)) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }
    // $row['rank_power'] = 0;
    $ranking = [];
    $list = $Redis->zRange('ranking:lord:power');
    foreach ($list as $data) {
        $ranking[] = json_decode($data, true); // array_search($row['lord_name'], );
    }
    $key = array_search($row['lord_pk'], array_column($ranking, 'lord_pk'));
    if (! $key) {
        $row['rank_power'] = 0;
    } else {
        $row['rank_power'] = $ranking[$key]['rank_power'];
    }

    return $Render->nsXhrReturn('success', null, [
        0 => $row
    ]);
}));

$app->post('/api/letter/findLordPk', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Letter = new Letter($Session, $PgGame);
    $ret = $Letter->getLordInfoByLordPK($params['lord_pk']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/letter/sendLetter', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');


    $title = Useful::convertNotAllowHtmlChar($params['title']);
    $content = Useful::convertNotAllowHtmlChar($params['content']);

    // CLetter 클래스는 주는 데로 받아서 저장하므로 사용자는 태그를 입력하지 못하도록 여기서 바꿔서 넘김
    $title_length = iconv_strlen($title, 'UTF-8');
    if ($title_length > 25) {
        // 외교 서신 제목 글자수 제한 30
        throw new ErrorHandler('error', $i18n->t('msg_letter_max_title_error', [$title_length])); // 제목은 한글 기준 25자를 초과할 수 없습니다.
    } else if ($title_length < 1) {
        throw new ErrorHandler('error', $i18n->t('msg_letter_empty_title_error')); // 제목을 입력해주십시오.
    }

    $content_length = iconv_strlen($content, 'UTF-8');
    if ($content_length > 500) {
        // 외교 서신 내용 글자수 제한 500
        throw new ErrorHandler('error', $i18n->t('msg_letter_max_content_error', [$content_length])); // 내용은 한글 기준 500자를 초과할 수 없습니다.
    } else if ($content_length  < 1) {
        throw new ErrorHandler('error', $i18n->t('msg_letter_empty_content_error')); // 내용을 입력해주십시오.
    }

    $receive_lord_pk_arr = explode(',', $params['receiver_lord_pk_list']);
    if (!is_array($receive_lord_pk_arr) || count($receive_lord_pk_arr) < 1) {
        // lord_pk는 array 여야함에게 서신을 보낼 수 없습니다.
        throw new ErrorHandler('error', 'Error Occurred. [30005]'); // 올바르지 않은 대상
    }

    foreach($receive_lord_pk_arr as $v) {
        if (!intval($v)) {
            throw new ErrorHandler('error', 'Error Occurred. [30006]'); // 올바르지 않은 대상
        }
    }

    if (count($receive_lord_pk_arr) > 5 && !$params['alliance']) {
        // lord_pk array의 길이가 5 이상 일 수 없음
        throw new ErrorHandler('error', $i18n->t('msg_letter_max_user_error')); // 동시에 발송 가능한 인원인 5명을 초과했습니다.
    }

    $Letter = new Letter($Session, $PgGame);
    $ret = $Letter->sendLetter($Session->lord['lord_pk'], $receive_lord_pk_arr, ['title' => $title, 'content' => $content, 'type' => 'N']);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/letter/getReward', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');


    $Letter = new Letter($Session, $PgGame);
    $ret = $Letter->getRewardList($params['lett_pk']);
    global $NsGlobal;
    if (! $ret) {
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    // 보상 수령
    $lord_pk = $Session->lord['lord_pk'];
    $posi_pk = $params['posi_pk'];

    $Item = new Item($Session, $PgGame);
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Cash = new Cash($Session, $PgGame);
    $Army = new Army($Session, $PgGame, $Resource, $GoldPop);
    $Terr = new Territory($Session, $PgGame);
    $Fortification = new Fortification($Session, $PgGame, $Resource, $GoldPop, $Terr);
    $Hero = new Hero($Session, $PgGame);
    $HeroSkill = new HeroSkill($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    global $_NS_SQ_REFRESH_FLAG, $_M;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        foreach ($ret AS $type => $rewards) {
            if (is_array($rewards)) {
                switch ($type) {
                    case 'item':
                        $reward_list = [];
                        foreach ($rewards AS $reward) {
                            $data = explode(':', $reward);
                            $reward_list[$data[0]]['item_count'] = $data[1];
                        }

                        $Item->setGiveItem($reward_list, $lord_pk, true, 'letter_reward');
                        break;
                    case 'resource':
                        $resource_list = [];
                        $gold_reward = 0;
                        $pop_reward = 0;
                        foreach ($rewards AS $reward) {
                            $data = explode(':', $reward);
                            if ($data[0] === 'gold') {
                                $gold_reward = $data[1];
                            } else if ($data[0] === 'population') {
                                $pop_reward = $data[1];
                            } else {
                                $resource_list[$data[0]] = $data[1];
                            }
                        }

                        if (count($resource_list) > 0) {
                            $Resource->increase($posi_pk, $resource_list, $lord_pk, 'letter_reward');
                        }

                        if ($gold_reward > 0) {
                            $GoldPop->increaseGold($posi_pk, $gold_reward, $lord_pk, 'letter_reward');
                        }

                        if ($pop_reward > 0) {
                            $GoldPop->increasePopulation($posi_pk, $pop_reward, $lord_pk);
                        }
                        break;
                    case 'qbig':
                        $qbig_reward = 0;
                        foreach ($rewards AS $reward) {
                            $data = explode(':', $reward);
                            $qbig_reward = $data[1];
                        }
                        if ($qbig_reward > 0) {
                            $Cash->increaseCash($lord_pk, $qbig_reward, 'letter_reward');
                        }
                        break;
                    case 'army':
                        $army_list = [];
                        foreach ($rewards AS $reward) {
                            $data = explode(':', $reward);
                            $army_list[$data[0]] = $data[1];
                        }

                        // 회군 프로세스를 이용하여 보상 지급
                        $Army->returnArmy($posi_pk, $army_list, 'letter_reward', true);
                        break;
                    case 'fort':
                        $fort_list = [];
                        foreach ($rewards AS $reward) {
                            $data = explode(':', $reward);
                            $fort_list[$data[0]] = $data[1];
                        }

                        // 없으면 새로 만들어야지...
                        $Fortification->increase($posi_pk, $fort_list, 'letter_reward', true);
                        break;
                    case 'hero':
                        $NsGlobal->requireMasterData(['hero', 'hero_base']);

                        foreach ($rewards AS $reward) {
                            $data = explode(':', $reward);

                            $hero_pk = $Hero->getNewHero('NORMAL', 1, $_M['HERO_BASE'][$data[0]]['rare_type'], $data[0], null, null, null, 'letter_reward'); // DAILY_HERO
                            if (! $hero_pk) {
                                throw new Exception('Error Occurred. [30007]'); // 서신 보상 지급 중 오류가 발생했습니다.(1)
                            }

                            $ret = $Hero->setMyHeroCreate($hero_pk, $lord_pk, 'V', null, null, 'N', 'letter_reward');
                            if (!$ret) {
                                throw new Exception('Error Occurred. [30008]'); // 서신 보상 지급 중 오류가 발생했습니다.(2)
                            }

                            //지급 후 시간 업데이트
                            $PgGame->query('UPDATE lord SET last_daily_hero_dt = now() WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
                        }
                        break;
                    case 'skill':
                        foreach ($rewards AS $reward) {
                            $data = explode(':', $reward);
                            $HeroSkill->setHeroSkillRegist($lord_pk, $data[0], 'letter_reward');
                        }
                        break;
                    default :
                        break;
                }
            }
        }

        // 지급 완료 후 보상 지급 완료 처리
        $r = $PgGame->query('UPDATE letter SET item_dt = now() WHERE lett_pk = $1', [$params['lett_pk']]);
        if (! $r) {
            throw new Exception('Error Occurred. [30009]'); // 서신 보상 지급 중 오류가 발생했습니다. (3)
        }

        // 일단 ETC 로그로 남김.
        // $Log->setEtc($lord_pk, $posi_pk, 'letter_reward', json_encode($ret));

        $PgGame->commit();
    } catch (Exception $e) {
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success', null, ['message' => $i18n->t('msg_reward_complete')]); // 보상을 획득하였습니다.
}));
