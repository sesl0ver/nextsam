<?php
global $app, $Render, $i18n;

$app->post("/api/alliance/make", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    if (! $params['in_cast_pk']) {
        throw new ErrorHandler('error',$i18n->t('msg_plz_construct_embassy')); // '대사관을 건설하여야 동맹 개설이 가능합니다.'
    }

    // 문자열 입력 검사
    if (preg_match("/[^\x{AC00}-\x{D7A3}0-9a-zA-Z]/u", $params['title']) > 0) {
        throw new ErrorHandler('error', $i18n->t('msg_alliance_title_confine_language')); // 동맹명은 오로지 한글, 영문, 숫자만 사용해야합니다.
    }

    if (iconv_strlen($params['title'], 'UTF-8') < 1) {
        throw new ErrorHandler('error', $i18n->t('msg_plz_input_alliance_name')); // '동맹명을 입력해 주십시오.'
    } else if (iconv_strlen($params['title'], 'UTF-8') < 2) {
        throw new ErrorHandler('error', $i18n->t('msg_min_count_alliance_name')); // '동맹명은 최소 2글자를 사용해야합니다.'
    } else if (iconv_strlen($params['title'], 'UTF-8') > 6) {
        throw new ErrorHandler('error', $i18n->t('msg_max_count_alliance_name')); // 동맹명은 최대 6글자까지 사용할 수 있습니다.
    }

    $GoldPop = new GoldPop($Session, $PgGame);

    $ret = $GoldPop->decreaseGold($params['posi_pk'], MAKE_ALLIANCE_GOLD, null, 'make_ally');
    if (!$ret) {
        throw new ErrorHandler('error', $i18n->t('msg_resource_gold_lack')); // '황금이 부족합니다.'
    }

    $Alliance = new Alliance($Session, $PgGame);

    $ret = $Alliance->make($Session->lord['lord_pk'], $params['posi_pk'], $params['in_cast_pk'], $params['title']);
    if (!$ret) {
        $ret = $GoldPop->increaseGold($params['posi_pk'], MAKE_ALLIANCE_GOLD, null, 'make_ally_fail');
        global $NsGlobal;
        if (!$ret) {
            throw new ErrorHandler('error', $i18n->t('msg_alliance_create', '1001'), true); // 동맹 개설 실패 후 황금증가 실패
        }
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success', null, $params['title']);
}));


$app->post("/api/alliance/searchLord[/{page_num:[0-9]+}]", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['lord_name']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal;

    $page_num = (! isset($args['page_num'])) ? 1 : $args['page_num'];
    $page_num = $page_num > 0 ? $page_num : 1;
    $page_type = (! isset($params['page_type'])) ? DEFAULT_PAGE_TYPE : $params['page_type'];

    $Alliance = new Alliance($Session, $PgGame);

    $ret = $Alliance->getLordList($Session->lord['lord_pk'], $params['lord_name'], $page_num, $page_type);
    if (! $ret) {
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    $total_count = $Alliance->getLordListTotalCount($params['lord_name'], $Session->lord['lord_pk']);

    $page_cnt = $page_type == 'alliance_active' ? ALLIANCE_ACTIVE_PAGE_NUM : DEFAULT_PAGE_NUM;
    $total_page = (INT)($total_count / $page_cnt);
    $total_page += ($total_count % $page_cnt > 0)? 1 : 0;

    $page_num = $page_num < 1 ? 1 : min($page_num, $total_page);

    return $Render->nsXhrReturn('success', null, ['list' => $ret, 'total_count' => $total_count, 'total_page' => $total_page, 'curr_page' => $page_num]);
}));

$app->post("/api/alliance/invite", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['lord_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal;

    $Alliance = new Alliance($Session, $PgGame);

    $ret = $Alliance->inviteAllianceMember($params['lord_pk']);
    if (!$ret)  {
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }
    return $Render->nsXhrReturn('success');
}));

$app->post("/api/alliance/info", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (! isset($alli_pk)) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->myAllianceInfo($alli_pk);

    if (isset($params['type']) && $params['type'] != 'change') {
        $ret['introduce'] = (! isset($ret['introduce'])) ? '-' : nl2br($ret['introduce']);
        $ret['notice'] = (! isset($ret['notice'])) ? '-' : nl2br($ret['notice']);
    }

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post("/api/alliance/relationList", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->allianceRelationInfo($alli_pk);

    return $Render->nsXhrReturn('success', null, ['list' => $ret]);
}));

$app->post("/api/alliance/memberList", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['order_type']);
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $page_num = (! isset($args['page_num'])) ? 1 : $args['page_num'];
    $page_num = $page_num > 0 ? $page_num : 1;
    $page_type = (! isset($params['page_type'])) ? DEFAULT_PAGE_TYPE : $params['page_type'];

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->getAllianceMembers($alli_pk, $Session->lord['lord_pk'], $params['order_type'], $page_num, $page_type);

    return $Render->nsXhrReturn('success', null, ['list' => $ret]);
}));

$app->post("/api/alliance/heroInfo", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['hero_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->getLordHeroInfo($params['hero_pk']);
    if(! $ret) {
        throw new ErrorHandler('error', $i18n->t('msg_not_found_lord_information')); // '해당 군주 정보가 없습니다.'
    }

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post("/api/alliance/warReport", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->getWarHistoryList($alli_pk);

    return $Render->nsXhrReturn('success', null, ['list' => $ret]);
}));

$app->post("/api/alliance/activityList[/{page_num:[0-9]+}]", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $page_num = (! isset($args['page_num'])) ? 1 : $args['page_num'];
    $page_num = $page_num > 0 ? $page_num : 1;

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->getAllianceHistory($alli_pk, $page_num);

    $total_count = $Alliance->getAllianceHistoryTotalCount($alli_pk);

    $total_page = (INT)($total_count / DEFAULT_PAGE_NUM);
    $total_page += ($total_count % DEFAULT_PAGE_NUM > 0)? 1 : 0;

    $page_num = $page_num < 1 ? 1 : min($page_num, $total_page);

    return $Render->nsXhrReturn('success', null, ['list' => $ret, 'total_count' => $total_count, 'total_page' => $total_page, 'curr_page' => $page_num]);
}));

$app->post("/api/alliance/rank[/{page_num:[0-9]+}]", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['type']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $page_num = (! isset($args['page_num'])) ? 1 : $args['page_num'];
    $page_num = $page_num > 0 ? $page_num : 1;

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->getAllianceRanking($params['type'], $page_num);

    $total_count = $Alliance->getAllianceRankingTotalCount();

    $total_page = (INT)($total_count / DEFAULT_PAGE_NUM);
    $total_page += ($total_count % DEFAULT_PAGE_NUM > 0)? 1 : 0;

    $page_num = $page_num < 1 ? 1 : min($page_num, $total_page);

    return $Render->nsXhrReturn('success', null, ['list' => $ret, 'total_count' => $total_count, 'total_page' => $total_page, 'curr_page' => $page_num]);
}));

$app->post("/api/alliance/joinList", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->getJoinList();

    return $Render->nsXhrReturn('success', null, ['list' => $ret]);
}));

$app->post("/api/alliance/joinAccess", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['lord_pk']); // TODO lord_name?
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->joinAccess($params['lord_pk'], $alli_pk);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    $Lord = new Lord($Session, $PgGame);
    $lord_info = $Lord->getLordInfo($params['lord_pk']);
    $Chat = new Chat($Session, $PgGame);
    $Chat->updateChatSession($lord_info, 'alli_pk', $alli_pk);

    return $Render->nsXhrReturn('success');
}));

$app->post("/api/alliance/joinRefuse", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['lord_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }

    $Alliance = new Alliance($Session, $PgGame);
    $Alliance->refuseJoin($params['lord_pk'], $alli_pk);

    return $Render->nsXhrReturn('success');
}));

$app->post("/api/alliance/levelChange", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['lord_pk', 'level']);
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->changeMemberLevel($Session->lord['lord_pk'], $params['lord_pk'], $params['level']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    $Lord = new Lord($Session, $PgGame);
    $lord_name = $Lord->getLordName($params['lord_pk']);

    return $Render->nsXhrReturn('success', null, ['lord_name' => $lord_name, 'level' => $params['level']]);
}));

$app->post("/api/alliance/masterTransfer", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['lord_pk']);
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->masterTransfer($Session->lord['lord_pk'], $params['lord_pk'], $alli_pk);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }
    $Lord = new Lord($Session, $PgGame);
    $lord_name = $Lord->getLordName($params['lord_pk']);
    $Chat = new Chat($Session, $PgGame);

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post("/api/alliance/expulsion", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['lord_pk']);
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->memberExpulsion($Session->lord['lord_pk'], $params['lord_pk'], $alli_pk);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    $Lord = new Lord($Session, $PgGame);
    $lord_info = $Lord->getLordInfo($params['lord_pk']);
    $Chat = new Chat($Session, $PgGame);
    $Chat->updateChatSession($lord_info, 'alli_pk', null);

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post("/api/alliance/resignation", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->memberResignation($Session->lord['lord_pk'], $alli_pk);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post("/api/alliance/dropOut", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['posi_pk']);
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->memberDropout($Session->lord['lord_pk'], $alli_pk, $params['posi_pk']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    $Chat = new Chat($Session, $PgGame);
    $Chat->updateChatSession($Session->lord, 'alli_pk', null);

    return $Render->nsXhrReturn('success', null, ['lord_name' => $Session->lord['lord_name']]);
}));

$app->post("/api/alliance/closeDown", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');
    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->memberClosedown($alli_pk, $Session->lord['lord_pk']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post("/api/alliance/otherInfo", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $lord_pk = (isset($params['lord_pk'])) ? $params['lord_pk'] : 0;
    $alli_pk = (isset($params['alli_pk'])) ? $params['alli_pk'] : 0;
    if ($lord_pk !== 0) {
        $Lord = new Lord($Session, $PgGame);
        $alli_pk = $Lord->getAlliPk($lord_pk);

        if ( $alli_pk == null ){
            return $Render->nsXhrReturn('error', $i18n->t('msg_dont_join_alliance'));
        }
    }
    $ret = [];
    if ($alli_pk !== 0) {
        $Alliance = new Alliance($Session, $PgGame);
        $ret = $Alliance->allianceInfo($alli_pk);

        $ret['introduce'] = (isset($ret['introduce'])) ? nl2br($ret['introduce']) : '-';
        $ret['diplomacy'] = $Alliance->getAllianceDiplomacy($Session->lord['alli_pk'], $alli_pk);

        if ($Session->lord['alli_pk'] != $alli_pk) {
            unset($ret['notice']); // 타동맹의 공지는 볼수 없게
        }
    }

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post("/api/alliance/joinRequest", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (isset($alli_pk)) {
        throw new ErrorHandler('error', $i18n->t('msg_already_join_alliance')); // 이미 가입된 동맹이 있습니다.
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->joinRequest($params['alli_pk'], $Session->lord['lord_pk']);
    if (! $ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post("/api/alliance/changeInfo", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['title', 'introduce', 'notice']);
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    if (preg_match("/[^\x{AC00}-\x{D7A3}0-9a-zA-Z]/u", $params['title']) > 0) {
        throw new ErrorHandler('error', $i18n->t('msg_alliance_title_confine_language')); // 동맹명은 오로지 한글, 영문, 숫자만 사용해야합니다.
    }

    if (iconv_strlen($params['title'], 'UTF-8') < 1) {
        throw new ErrorHandler('error', $i18n->t('msg_plz_input_alliance_name')); // '동맹명을 입력해 주십시오.'
    } else if (iconv_strlen($params['title'], 'UTF-8') < 2) {
        throw new ErrorHandler('error', $i18n->t('msg_min_count_alliance_name')); // '동맹명은 최소 2글자를 사용해야합니다.'
    } else if (iconv_strlen($params['title'], 'UTF-8') > 6) {
        throw new ErrorHandler('error', $i18n->t('msg_max_count_alliance_name')); // 동맹명은 최대 6글자까지 사용할 수 있습니다.
    }

    $introduce = Useful::convertNotAllowHtmlChar($params['introduce']);
    $notice = Useful::convertNotAllowHtmlChar($params['notice']);

    if (iconv_strlen($introduce, 'UTF-8') > 200) {
        throw new ErrorHandler('error', $i18n->t('msg_max_count_alliance_intro', [iconv_strlen($introduce, 'UTF-8')]));
    }

    if (iconv_strlen($notice, 'UTF-8') > 500) {
        throw new ErrorHandler('error', $i18n->t('msg_max_count_alliance_notice', [iconv_strlen($notice, 'UTF-8')]));
    }

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->changeInfo($alli_pk, $Session->lord['lord_pk'], strip_tags($params['title']), $introduce, $notice);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success', null, ['title' => strip_tags($params['title']), 'introduce' => nl2br($introduce), 'notice' => nl2br($notice)]);
}));

$app->post("/api/alliance/search", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->getAllianceDefault();

    return $Render->nsXhrReturn('success', null, ['list' => $ret, 'total_count' => 30, 'total_page' => 1, 'curr_page' => 1]);
}));

$app->post("/api/alliance/searchAlliance", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['alliance_title']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->getAllianceList($params['alliance_title']);

    return $Render->nsXhrReturn('success', null, ['list' => $ret]);
}));

$app->post("/api/alliance/allianceRelation", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['alli_pk', 'relation_type']);
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');
    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->setAllianceRelation($Session->lord['lord_pk'], $alli_pk, $params['alli_pk'], $params['relation_type']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post("/api/alliance/sendAllianceLetter", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['title', 'content']);
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $title = Useful::convertNotAllowHtmlChar($params['title']);
    $content = Useful::convertNotAllowHtmlChar($params['content']);

    if (iconv_strlen($title, 'UTF-8') > 25) {
        throw new ErrorHandler('error', $i18n->t('msg_max_count_alliance_letter_subject', [iconv_strlen($title, 'UTF-8')]));
    } else if (iconv_strlen($title, 'UTF-8') < 1) {
        throw new ErrorHandler('error', $i18n->t('msg_plz_input_letter_subject'));
    }

    if (iconv_strlen($content, 'UTF-8') > 500) {
        throw new ErrorHandler('error', $i18n->t('msg_max_count_alliance_letter_content', [iconv_strlen($content, 'UTF-8')]));
    } else if (iconv_strlen($content, 'UTF-8') < 1) {
        throw new ErrorHandler('error', $i18n->t('msg_plz_input_letter_content'));
    }

    $Alliance = new Alliance($Session, $PgGame);
    $ret = $Alliance->sendAllianceLetter($Session->lord['lord_pk'], $alli_pk, $title, $content);
    if (! $ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));

$app->post("/api/alliance/level", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $level = $Alliance->getAllianceMemberLevel($Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success', null, $level);
}));

$app->post("/api/alliance/getTitle", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $Alliance->get($Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success');
}));

$app->post("/api/alliance/getRelation", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $alli_pk = $Session->lord['alli_pk'];
    if (!$alli_pk) {
        throw new ErrorHandler('error', $i18n->t('msg_dont_join_alliance'));
    }
    $PgGame = new Pg('DEFAULT');

    $Alliance = new Alliance($Session, $PgGame);
    $Alliance->getRelation($Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success');
}));

// 기존에는 세션 기준으로 업데이트 하였으나 타인에 의한 갱신시 문제가 있어 DB 기준으로 변경함. 20230725 송누리
$app->post("/api/alliance/updateAlliance", $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 동맹 위치 갱신
    $Alliance = new Alliance($Session, $PgGame);
    $alli_pk = $Alliance->getAlliancePK($Session->lord['lord_pk']);
    if ($alli_pk !== 0) {
        $Alliance->getAlliancePosition($Session->lord['lord_pk'], $alli_pk, $params['posi_pk']);
    } else {
        $Session->sqAppend('ALLI', [], null, $Session->lord['lord_pk'], $params['posi_pk']);
    }

    // 군주 정보 갱신
    $Lord = new Lord($Session, $PgGame);
    $Lord->get($Session->lord['lord_pk']);
    $Session->setLoginReload();

    return $Render->nsXhrReturn('success', null, ['alli_pk' => $alli_pk]);
}));