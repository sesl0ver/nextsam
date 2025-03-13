<?php
global $app, $Render, $i18n;

$app->post('/api/heroSkill/skillSelected', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $HeroSkill = new HeroSkill($Session, $PgGame);
    $r = $HeroSkill->setHeroSkillRegist($Session->lord['lord_pk'], $params['m_hero_skil_pk'], 'get_box', $params['my_hero_skil_box_pk']);
    if (!$r) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/heroSkill/heroSkillList', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // $page_num = ($params['page_num'] ?? 0) > 0 ? $params['page_num'] : 1;
    $HeroSkill = new HeroSkill($Session, $PgGame);
    $total_count = $HeroSkill->getHeroSkillCount($Session->lord['lord_pk'], $params['type']);
    // $page_cnt = ($params['open_type'] == 'my_item') ? HERO_SKILL_MY_ITEM_LIST_NUM : HERO_SKILL_LIST_NUM;
    // $total_page = (INT)($total_count / $page_cnt);
    // $total_page += ($total_count % $page_cnt > 0)? 1 : 0;

    /*if ($page_num < 1) {
        $page_num = 1;
    } else if ($page_num > $total_page) {
        $page_num = $total_page;
    }*/

    $hero_skill_list = [];
    if ($total_count > 0) {
        $hero_skill_list = $HeroSkill->getHeroSkillInfo($Session->lord['lord_pk'], $params['open_type'], $params['type'], $params['order_type'], $params['order_by']);
    }

    return $Render->nsXhrReturn('success', null, ['hero_skill_list' => $hero_skill_list, 'total_count' => $total_count]);
    // return $Render->nsXhrReturn('success', null, ['hero_skill_list' => $hero_skill_list, 'total_count' => $total_count, 'total_page' => $total_page, 'curr_page' => $page_num]);
}));

$app->post('/api/heroSkill/heroSkillAll', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $HeroSkill = new HeroSkill($Session, $PgGame);
    $hero_skill_list = $HeroSkill->getHeroSkillAll($Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success', null, $hero_skill_list);
}));



$app->post('/api/heroSkill/heroSkillExp', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 배속
    $HeroSkill = new HeroSkill($Session, $PgGame);
    $HeroSkill->updateAssignSkillExp($Session->lord['lord_pk']);

    // 외주 주둔
    $HeroSkill->updateCampSkillExp($Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/heroSkill/mySkillBoxList', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $HeroSkill = new HeroSkill($Session, $PgGame);
    $ret = $HeroSkill->getMyHeroSkillBoxList($Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/heroSkill/myHeroIdle', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Hero = new Hero($Session, $PgGame);
    $total_count = $Hero->getMyIdleHeroListCount($Session->lord['lord_pk']);
    $list_num = 13; // 한페이지에 보일 영웅 수

    $page_num = $params['page_num'];
    $total_page = (INT)($total_count / $list_num);
    $total_page += ($total_count % $list_num > 0)? 1 : 0;
    if ($page_num < 1) {
        $page_num = 1;
    } else if ($page_num > $total_page) {
        $page_num = $total_page;
    }

    $order = $params['order_by'];
    $order_arr = ['name', 'level', 'rare', 'leadership', 'mil_force', 'intellect', 'politics', 'charm', 'loyalty', 'posi_pk', 'infantry', 'pikeman', 'spearman', 'archer', 'horseman', 'siege'];
    $is_allow_order = in_array($order, $order_arr);

    $order_type = strtolower($params['order_type']);
    $order_type = ($order_type == 'asc') ? 'ASC' : 'DESC';

    $heroes = $Hero->getMyIdleHeroes($Session->lord['lord_pk'], $page_num, $list_num, $order, $order_type);

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'curr_page' => $page_num, 'hero_list' => $heroes, 'order_by' => $order, 'order_type' => $order_type]);
}));

$app->post('/api/heroSkill/getMyHeroData', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['hero_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Hero = new Hero($Session, $PgGame);
    $ret = $Hero->getMyHeroInfo($params['hero_pk']);

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/heroSkill/equip', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $HeroSkill = new HeroSkill($Session, $PgGame);
    $ret = $HeroSkill->setHeroSkillEquip($Session->lord['lord_pk'], $params['hero_pk'], $params['my_hero_skil_pk'], $params['curr_posi_pk']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'equip']);

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/heroSkill/delete', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $HeroSkill = new HeroSkill($Session, $PgGame);
    $ret = $HeroSkill->setDeleteEquipSkill($Session->lord['lord_pk'], $params['curr_posi_pk'], $params['hero_pk'], $params['slot_pk']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/heroSkill/unEquip', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $HeroSkill = new HeroSkill($Session, $PgGame);
    $ret = $HeroSkill->setUnequipSkill($Session->lord['lord_pk'], $params['curr_posi_pk'], $params['hero_pk'], $params['slot_pk']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'unequip']);

    return $Render->nsXhrReturn('success', null, $ret);
}));

$app->post('/api/heroSkill/useMedal', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Quest = new Quest($Session, $PgGame);

    $Item = new Item($Session, $PgGame);
    if (!$Item->getItemCount($Session->lord['lord_pk'], $params['m_item_pk'])) {
        throw new ErrorHandler('error', $i18n->t('msg_use_empty_item')); // 해당 공적패가 없습니다.
    }

    //군주 2등급 이상 공적패 포상 가능하게 수정
    if ($Session->lord['level'] < 2) {
        throw new ErrorHandler('error', $i18n->t('msg_prize_medal_need_lord_level')); // 군주  2등급 이상 공적패 포상이 가능합니다.
    }

    global $_M, $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();
        $_NS_SQ_REFRESH_FLAG = true;

        $ret = $Item->useItem($params['posi_pk'], $Session->lord['lord_pk'], $params['m_item_pk'], 1, ['_use_type' => 'use_medal', '_hero_pk' => $params['hero_pk']]);
        if (!$ret) {
            throw new Exception($NsGlobal->getErrorMessage());
        }

        $HeroSkill = new HeroSkill($Session, $PgGame);
        $ret = $HeroSkill->setHeroSkillExp($Session->lord['lord_pk'], $params['posi_pk'], $params['hero_pk'], $_M['HERO_SKILL_EXP_MEDAL'][$params['m_item_pk']], "PrizeMedal");
        if (!$ret) {
            throw new Exception('Experience value did not increase.'); // 증가할 경험치가 없음
        }

        // 메달의 갯수가 변동되었으므로 퀘스트 재검사.
        $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'give_item', 'm_item_pk' => $params['m_item_pk']]);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    $ret = $_M['HERO_SKILL_EXP_MEDAL'][$params['m_item_pk']];

    return $Render->nsXhrReturn('success', null, $ret);
}));
