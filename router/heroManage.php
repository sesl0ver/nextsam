<?php
global $app, $Render, $i18n;

$app->post('/api/heroManage/list', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Hero = new Hero($Session, $PgGame);
    $HeroSkill = new HeroSkill($Session, $PgGame);

    $used_slot = 0;
    if ($params['type'] != 'skill') {
        $used_slot = $Hero->getMyHeroListCount($Session->lord['lord_pk'], ['G']);
    }

    if ($params['type'] == 'list_visit') {
        $total_count = $Hero->getMyHeroListCount($Session->lord['lord_pk'], ['C', 'S', 'V']);
        $list_num = HERO_LIST_PAGE_NUM;
    } else if ($params['type'] == 'list_guest') {
        $total_count = $used_slot;
        $list_num = HERO_LIST_PAGE_NUM;
    } else if ($params['type'] == 'list_appoint') {
        $total_count = $Hero->getMyHeroListCount($Session->lord['lord_pk'], ['A']);
        $list_num = HERO_LIST_PAGE_NUM;
    } else if ($params['type'] == 'list_territory') {
        $sel_posi_pk = $params['posi_pk'];
        if (isset($params['sel_posi_pk'])) {
            $sel_posi_pk = $params['sel_posi_pk'];
        }
        $total_count = $Hero->getMyTerritoryHeroListCount($Session->lord['lord_pk'], ['A'], $sel_posi_pk);
        $list_num = HERO_LIST_PAGE_NUM;
    } else if ($params['type'] == 'list_over_rank') {
        $total_count = $Hero->getMyOverRankHeroListCount($Session->lord['lord_pk'], ['C', 'S', 'V', 'G', 'A']);
        $list_num = HERO_LIST_PAGE_NUM;
    } else if ($params['type'] == 'common') {
        $total_count = $Hero->getMyHeroListCount($Session->lord['lord_pk'], ['G'], false, true);
        $list_num = 10;
    } else if ($params['type'] == 'special') {
        $total_count = $Hero->getMyHeroListCount($Session->lord['lord_pk'], ['G'], ($params['target'] == 'main'));
        $list_num = 10;
    } else if ($params['type'] == 'collection') {
        $total_count = $Hero->getCollectionHeroListCount();
        $used_slot = $total_count;
        $list_num = 16;
    } else if ($params['type'] == 'skill') {
        $total_count = $HeroSkill->getHeroSkillCount($Session->lord['lord_pk'], null, $params['order_by']);

        $list_num = HERO_SKILL_COMBINATION_LIST_NUM;
    } else {
        $total_count = $Hero->getMyHeroListCount($Session->lord['lord_pk'], ['C', 'S', 'V', 'G', 'A']);
        $list_num = HERO_LIST_PAGE_NUM;
    }

    $total_page = (INT)($total_count / $list_num);
    $total_page += ($total_count % $list_num > 0)? 1 : 0;

    $page_num = 1;
    if ($page_num < 1) {
        $page_num = 1;
    } else if ($page_num > $total_page) {
        $page_num = $total_page;
    }

    $order = $params['order_by'];
    $order_arr = ['name', 'level', 'rare', 'offi', 'leadership', 'mil_force', 'intellect', 'politics', 'charm', 'loyalty', 'posi_pk', 'infantry', 'pikeman', 'spearman', 'archer', 'horseman', 'siege'];
    $is_allow_order = in_array($order, $order_arr);
    if ($params['type'] != 'skill') {
        $order = (!$is_allow_order) ? 'rare' : $order;
    }

    $order_type = strtolower($params['order_type']);
    $order_type = ($order_type == 'asc') ? 'ASC' : 'DESC';

    $heroes = [];
    if ($page_num > 0) {
        if ($params['type'] == 'list_visit') {
            $heroes = $Hero->getMyVisitHeroList($Session->lord['lord_pk'], $page_num, $order, $order_type, $list_num);
        } else if ($params['type'] == 'list_guest') {
            $heroes = $Hero->getMyGuestHeroList($Session->lord['lord_pk'], $page_num, $order, $order_type, $list_num);
        } else if ($params['type'] == 'list_appoint') {
            $heroes = $Hero->getMyAppointHeroList($Session->lord['lord_pk'], $page_num, $order, $order_type, $list_num);
        } else if ($params['type'] == 'list_territory') {
            $sel_posi_pk = $params['posi_pk'];
            if (isset($params['sel_posi_pk'])) {
                $sel_posi_pk = $params['sel_posi_pk'];
            }
            $heroes = $Hero->getMyTerritoryHeroList($Session->lord['lord_pk'], $page_num, $order, $order_type, $list_num, $sel_posi_pk);
        } else if ($params['type'] == 'list_over_rank') {
            $heroes = $Hero->getMyOverRankHeroList($Session->lord['lord_pk'], $page_num, $order, $order_type, $list_num);
        } else if ($params['type'] == 'common') {
            $heroes = $Hero->getMyCommonCombiAvailHeroList($Session->lord['lord_pk'], $page_num, $order, $order_type, $list_num, 'common');
        } else if ($params['type'] == 'special') {
            if ($params['target'] == 'main')
                $heroes = $Hero->getMySpecialCombiAvailHeroList($Session->lord['lord_pk'], $page_num, $order, $order_type, $list_num);
            else
                $heroes = $Hero->getMyCommonCombiAvailHeroList($Session->lord['lord_pk'], $page_num, $order, $order_type, $list_num, 'special');
        } else if ($params['type'] == 'collection') {
            $heroes = $Hero->getCollectionHeroList($page_num, $order, $order_type, $list_num);
        } else if ($params['type'] == 'skill') {
            $heroes = $HeroSkill->getHeroSkillInfo($Session->lord['lord_pk'], $page_num, 'skill_combination', null, $order);
        } else {
            $heroes = $Hero->getMyAllHeroList($Session->lord['lord_pk'], $page_num, $order, $order_type, $list_num);
        }
    } else {
        $page_num = 1;
        $total_page = 1;
    }

    $appo_heroes_m_hero_pk = null;
    if ($params['type'] != 'collection' && $params['type'] != 'skill') {
        $appo_heroes_m_hero_pk = $Hero->getMyAppoHeroesMHeroPk($Session->lord['lord_pk']);
    }

    return $Render->nsXhrReturn('success', null, ['used_slot' => $used_slot, 'total_count' => $total_count, 'curr_page' => $page_num, 'total_page' => $total_page, 'order_by' => $order, 'order_type' => $order_type, 'hero_list' => $heroes, 'appo_heroes_m' => $appo_heroes_m_hero_pk]);
}));

$app->post('/api/heroManage/tobeGuest', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $_NS_SQ_REFRESH_FLAG, $NsGlobal;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        $Hero = new Hero($Session, $PgGame);
        $ret = $Hero->setGuest($params['hero_pk']);
        if (! $ret) {
            throw new Exception();
        }

        $PgGame->commit();
    } catch(Exception $e) {
        $PgGame->rollback();
        throw new ErrorHandler('error',  $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    // 콜렉션 체크 - 이벤트 종료로 주석처리
    // $HeroCollection->collectionHero($Session->lord['lord_pk'], $params['hero_pk']);

    $post_up_msg = false;

    $Item = new Item($Session, $PgGame);
    $Quest = new Quest($Session, $PgGame);
    /*if ($params['post_up'] == 'Y') { // TODO 소셜 사진첩 기능은 사용하지 않을 것이므로
        $PgGame->query('SELECT is_post_up FROM hero WHERE hero_pk = $1 AND is_post_up = $2', Array($params['hero_pk'], 'N'));
        if ($PgGame->fetch()) {
            shuffle($_M['ARTICLE_POST_REWARD']);
            $seed = rand(1, 20 * 1000 * 5);
            $prev = 0;
            $m_item_pk = 500001;
            $item_cnt = 1;
            foreach($_M['ARTICLE_POST_REWARD'] as $item_pk => $v) {
                if ($seed <= ($prev + $v['rate'])) {
                    $m_item_pk = $v['m_item_pk'];
                    $item_cnt = $v['item_cnt'];
                    break;
                } else {
                    $prev += $v['rate'];
                }
            }
            $Item->BuyItem($Session->lord['lord_pk'], $m_item_pk, $item_cnt, 'article_post');
            $PgGame->query('UPDATE hero SET is_post_up = $3, last_post_up_dt = now() WHERE hero_pk = $1 AND is_post_up = $2', Array($params['hero_pk'], 'N', 'Y'));
            $post_up_msg = "사진첩에 기념 이미지 등록이 완료되어 보상으로<br/>아이템 〃{$_M['ITEM'][$m_item_pk]['title']}〃이 지급되었습니다.";
            $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero_boast']);
        }
    }*/

    // 퀘스트 체크
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'guest']);

    global $_M;
    $NsGlobal->requireMasterData(['hero', 'hero_base']);

    $info = $Hero->getMyHeroInfo($params['hero_pk']);
    $rare = $info['rare_type'];
    $m_hero_base_pk = $_M['HERO'][$info['m_hero_pk']]['m_hero_base_pk'];
    $over_type = $_M['HERO_BASE'][$m_hero_base_pk]['over_type'];
    $yn_new_gacha = $_M['HERO_BASE'][$m_hero_base_pk]['yn_new_gacha'];
    $yn_modifier = $_M['HERO_BASE'][$m_hero_base_pk]['yn_modifier'];

    // TODO 왠지 return 데이터가 불필요해 보이는데...
    return $Render->nsXhrReturn('success', null, ['rare_type' => $rare, 'over_type' => $over_type, 'yn_new_gacha' => $yn_new_gacha, 'yn_modifier' => $yn_modifier, 'm_hero_pk' => $info['m_hero_pk'], 'hero_pk' => $params['hero_pk']]);
}));

$app->post('/api/heroManage/tobeAppoint', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Hero = new Hero($Session, $PgGame);
    $ret = $Hero->setAppoint($params['hero_pk'], $params['m_offi_pk'], $params['posi_pk']);

    if (!$ret) {
        // (재)등용 실패
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    $Session->sqAppend('HERO', [$params['hero_pk'] => $Hero->getMyHeroInfo($params['hero_pk'])]);

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'appoint', 'hero_pk' => $params['hero_pk']]);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/heroManage/changeOfficer', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['hero_pk']);
    $Session = new Session(true, true);
    $PgGame = new Pg('DEFAULT');

    $Hero = new Hero($Session, $PgGame);
    if(isset($params['chan_hero_pk'])) {
        $ret = $Hero->setSwapOfficer($params['hero_pk'], $params['chan_hero_pk'], $params['m_offi_pk'], $params['posi_pk']);
    } else {
        $ret = $Hero->setChangeOfficer($params['hero_pk'], $params['m_offi_pk'], $params['posi_pk']);
    }
    if (!$ret) {
        // 관직 교체 실패
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success', null, ['m_offi_pk' => $params['m_offi_pk'], 'need_redraw' => true, 'hero_info' => $Hero->getMyHeroInfo($params['hero_pk'])]);
}));

$app->post('/api/heroManage/tobeAbandon', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');
    $Hero = new Hero($Session, $PgGame);
    try {
        $PgGame->begin();
        $ret = $Hero->setAbandon($params['hero_pk']);
        if (!$ret) {
            throw new Exception('방출 실패');
        }
        $PgGame->commit();
    } catch(Exception $e) {
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    return $Render->nsXhrReturn('success', null, $params['hero_pk']);
}));

$app->post('/api/heroManage/combination', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $HeroCombination = new HeroCombination($Session, $PgGame);

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['hero', 'hero_base']);

    if (! isset($params['selected_hero'])) {
        throw new ErrorHandler('error', '선택된 영웅이 없습니다.');
    }

    $need_gold = 0;
    if ($params['type'] == 'common') {
        // 일반 조합 // 재료카드 4장이 필요함
        try {
            $hero_pk_arr = $HeroCombination->splitSelectedCard($params['selected_hero']);
            if (! $hero_pk_arr) {
                throw new Exception('배열분리 실패');
            }
            $hero_arr = $HeroCombination->getCombinationHeroInfoWithLoad($hero_pk_arr);
            if (!$hero_arr) {
                throw new Exception('잘못된 조합 대상 선정');
            }
            $combi_result = $HeroCombination->getApplyCombinationAttr($hero_arr['mate']);
            if (!$combi_result) {
                throw new Exception('조합 실패');
            }
        } catch(Exception $e) {
            throw new ErrorHandler('error', $e->getMessage(), true);
        }

        $Hero = new Hero($Session, $PgGame);
        try {
            $log_serial_num = time().'_'.$Session->lord['lord_pk'];
            $log_material_card_info = [];
            foreach($hero_arr['mate'] as $material_hero) {
                $t = $Hero->getMyHeroInfo($material_hero['hero_pk']);
                if (!$t) {
                    throw new Exception('영웅 조합에 실패 하였습니다. 다시 시도해 주시기 바랍니다.');
                }
                $log_material_card_info[] = ['hero_pk' => $t['hero_pk'], 'm_offi_pk' => $t['m_offi_pk'], 'posi_pk' => $t['posi_pk'], 'status' => $t['status'], 'status_cmd' => $t['status_cmd'], 'yn_lord' => $t['yn_lord'], 'leadership' => $t['leadership'], 'mil_force' => $t['mil_force'], 'intellect' => $t['intellect'], 'politics' => $t['politics'], 'charm' => $t['charm'], 'm_hero_pk' => $t['m_hero_pk'], 'enchant' => $t['enchant'], 'm_hero_skil_pk1' => $t['m_hero_skil_pk1'], 'm_hero_skil_pk2' => $t['m_hero_skil_pk2'], 'm_hero_skil_pk3' => $t['m_hero_skil_pk3'], 'm_hero_skil_pk4' => $t['m_hero_skil_pk4'], 'm_hero_skil_pk5' => $t['m_hero_skil_pk5'], 'm_hero_skil_pk6' => $t['m_hero_skil_pk6'], 'skill_exp' => $t['skill_exp'], 'slot_pk1' => $t['slot_pk1'], 'slot_pk2' => $t['slot_pk2'], 'slot_pk3' => $t['slot_pk3'], 'slot_pk4' => $t['slot_pk4'], 'slot_pk5' => $t['slot_pk5'], 'slot_pk6' => $t['slot_pk6'], 'main_slot_pk1' => $t['main_slot_pk1'], 'main_slot_pk2' => $t['main_slot_pk2'], 'main_slot_pk3' => $t['main_slot_pk3'], 'main_slot_pk4' => $t['main_slot_pk4'], 'main_slot_pk5' => $t['main_slot_pk5'], 'main_slot_pk6' => $t['main_slot_pk6']];
            }
        } catch(Exception $e) {
            throw new ErrorHandler('error', $e->getMessage(), true);
        }

        global $_NS_SQ_REFRESH_FLAG;
        try
        {
            $PgGame->begin();

            $_NS_SQ_REFRESH_FLAG = true;

            // 재료 카드 영웅 방출
            foreach($hero_arr['mate'] as $material_hero) {
                $ret = $Hero->setAbandon($material_hero['hero_pk'], 'combination');
                if (!$ret) {
                    throw new Exception($NsGlobal->getErrorMessage());
                }
            }

            // 군주 카드를 발급하지 않기 위해 지정된 전역 변수에 군주카드의 m_hero_base_pk를 적어놓음
            global $_not_m_hero_base_list;
            $_not_m_hero_base_list = [120000,120001,120002,120003,120004];
            // 새로운 영웅 발급
            // $hero_pk = $Hero->getNewHero('COMBI', $combi_result['level'], $combi_result['rare'], $combi_result['m_hero_base_pk'], null, $combi_result['forces'], $_null, 'combi_common', 'N', $combi_result['yn_new_gacha']);
            $hero_pk = $Hero->getNewHero('COMBI', $combi_result['level'], $combi_result['rare'], null, null, null, null, 'combi_common', 'N', $combi_result['yn_new_gacha'] ?? null);
            if (!$hero_pk)
            {
                throw new Exception('영웅 조합 실패');
            }

            // 나의 영입 대기 영웅으로
            $ret = $Hero->setMyHeroCreate($hero_pk, $Session->lord['lord_pk'], 'V', null, null, 'N', 'combination');
            if(!$ret) {
                throw new Exception('영웅 조합 실패');
            }
            $PgGame->commit();
        } catch(Exception $e) {
            $PgGame->rollback();
            throw new ErrorHandler('error', $e->getMessage(), true);
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        $hero_info = $Hero->getFreeHeroInfo($hero_pk);

        // 퀘스트 체크
        $Quest = new Quest($Session, $PgGame);
        $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'combi']);
        $Quest->countCheckQuest($Session->lord['lord_pk'], 'EVENT_HERO_COMBI', ['value' => 1]);

        // 재료 카드 로그 기록하기
        $Log = new Log($Session, $PgGame);
        foreach($log_material_card_info as $log_hero) {
            $Log->setHeroCombi($Session->lord['lord_pk'], $Session->getPosiPk(), $log_hero['hero_pk'], $log_hero['m_hero_pk'], 'common', $log_serial_num, 'material', $need_gold, json_encode($log_hero));
        }

        $t = $Hero->getMyHeroInfo($hero_info['hero_pk']);
        $t = ['combi_result' => $combi_result, 'hero_pk' => $t['hero_pk'], 'm_offi_pk' => $t['m_offi_pk'], 'posi_pk' => $t['posi_pk'], 'status' => $t['status'], 'status_cmd' => $t['status_cmd'], 'yn_lord' => $t['yn_lord'], 'leadership' => $t['leadership'], 'mil_force' => $t['mil_force'], 'intellect' => $t['intellect'], 'politics' => $t['politics'], 'charm' => $t['charm'], 'm_hero_pk' => $t['m_hero_pk'], 'enchant' => $t['enchant'], 'm_hero_skil_pk1' => $t['m_hero_skil_pk1'], 'm_hero_skil_pk2' => $t['m_hero_skil_pk2'], 'm_hero_skil_pk3' => $t['m_hero_skil_pk3'], 'm_hero_skil_pk4' => $t['m_hero_skil_pk4'], 'm_hero_skil_pk5' => $t['m_hero_skil_pk5'], 'm_hero_skil_pk6' => $t['m_hero_skil_pk6'], 'skill_exp' => $t['skill_exp'], 'slot_pk1' => $t['slot_pk1'], 'slot_pk2' => $t['slot_pk2'], 'slot_pk3' => $t['slot_pk3'], 'slot_pk4' => $t['slot_pk4'], 'slot_pk5' => $t['slot_pk5'], 'slot_pk6' => $t['slot_pk6'], 'main_slot_pk1' => $t['main_slot_pk1'], 'main_slot_pk2' => $t['main_slot_pk2'], 'main_slot_pk3' => $t['main_slot_pk3'], 'main_slot_pk4' => $t['main_slot_pk4'], 'main_slot_pk5' => $t['main_slot_pk5'], 'main_slot_pk6' => $t['main_slot_pk6']];

        $m_hero = $_M['HERO'][$t['m_hero_pk']];
        $m_hero_base = $_M['HERO_BASE'][$m_hero['m_hero_base_pk']];

        if ($m_hero_base['rare_type'] > 4) {
            // TODO 채팅 구현 후 주석해제 필요.
            // $Chat = new CChat($Session);
            // $Chat->send_announce_system_about_hero($Session->lord['lord_name']."님이 일반조합을 통해 ".$m_hero_base['name']." Lv.".$m_hero['level']." 영웅 카드를 획득하였습니다.");
        }

        $Log->setHeroCombi($Session->lord['lord_pk'], $Session->getPosiPk(), $t['hero_pk'], $t['m_hero_pk'], 'common', $log_serial_num, 'new_hero', $need_gold, json_encode($t));

        $result_arr = ['hero_info' => $hero_info, 'action' => 'common_combi', 'is_success' => 'N', 'r' => $combi_result];
    } else if ($params['type'] == 'special') {
        if (! isset($params['selected_star_hero'])) {
            throw new ErrorHandler('error', '선택된 메인 영웅이 없습니다.');
        }

        // $hero_info = [];
        $star_hero_pk = $params['selected_star_hero'];
        $hero_pk_arr = explode(':', $params['selected_hero']);

        $Hero = new Hero($Session, $PgGame);
        $star_hero_info = $Hero->getMyHeroStatus($star_hero_pk);
        // $star_m_hero_info = $_M['HERO'][$star_hero_info['m_hero_pk']];

        // 오버랭크 영웅 사용안함.
        /*$yn_incapacity = false;
        if ($star_m_hero_info['over_type'] == 'Y') {
            // 타이머가 존재하지 않으면 무능화된 영웅
            $PgGame->query("SELECT date_part('epoch', end_dt)::integer FROM timer WHERE queue_pk = $1 AND queue_type = $2 AND status = $3", [$star_hero_pk, 'R', 'P']);
            $over_dt = $PgGame->fetchOne();
            if (!$over_dt || $over_dt < $now) {
                $yn_incapacity = true;
            }
        }*/

        $log_material_card_info = [];

        $now = time();
        $Item = new Item($Session, $PgGame);
        global $_NS_SQ_REFRESH_FLAG;
        try {
            $log_serial_num = $now.'_'.$Session->lord['lord_pk'];

            $PgGame->begin();

            $_NS_SQ_REFRESH_FLAG = true;

            // 존재 및 소유 중인 영웅인지 체크
            $star_hero = $HeroCombination->getSpecialCombinationStarHero($star_hero_pk);
            if (!$star_hero) {
                throw new Exception('메인 영웅이 존재하지 않습니다.');
            }

            $mate_hero_arr = $HeroCombination->getSpecialCombinationMaterialHero($hero_pk_arr);
            if (!$mate_hero_arr) {
                throw new Exception('재료 영웅이 존재하지 않습니다.');
            }

            // 재료 영웅 로그를 위해 정리
            foreach($mate_hero_arr AS $material_hero) {
                $t = $Hero->getMyHeroInfo($material_hero['hero_pk']);
                if (!$t) {
                    throw new Exception('영웅 조합 실패');
                }
                $log_material_card_info[] = $t;
            }

            // 재료 영웅에 군주 영웅이 포함되어 있는지 체크
            $is_lord_card = $HeroCombination->isLordCard($hero_pk_arr);
            if ($is_lord_card) {
                throw new Exception('군주 카드를 재료로 사용할 수 없습니다.');
            }

            // 메인 영웅 레벨 체크 (20 이상으로는 레벨업 할 수 없음)
            if ($star_hero['level'] > 19) {
                throw new Exception('더 이상 레벨업 할 수 없는 영웅입니다.');
            }

            // 조합석 및 황금 정보 가져오기
            $reso_info = $HeroCombination->getSpecialCombinationInfo($star_hero['level'], $star_hero['rare_type']);
            if (!$reso_info) {
                throw new Exception('조합 진행 중 오류가 발생했습니다.');
            }

            // 골드 체크
            $GoldPop = new GoldPop($Session, $PgGame);
            $lord_gold = $GoldPop->get($params['posi_pk']); // 소지수
            $need_gold = $reso_info['gold'];

            if ($need_gold > $lord_gold) {
                throw new Exception('골드가 부족합니다.');
            }

            // 조합 진행
            $combi_result = $HeroCombination->doSpecialCombination($star_hero, $mate_hero_arr);
            if (!$combi_result) {
                throw new Exception('특수조합 진행 중 오류가 발생했습니다. (1)');
            }

            // 레벨업이 되었을 경우에만 차감
            $before = $combi_result['before'];
            $after = $combi_result['after'];
            $need_item_count = 0;
            $need_item_pk = 500708; // 특수조합석. 어차피 특수조합석만 사용하므로.
            if ($after['level'] > $before['level']) {
                $x = $after['level'] - $before['level'];
                for ($i = 1; $i <= $x; $i++) {
                    $_next = $before['level'] + $i - 1; // 실제로는 1레벨 전 아이템 테이블것이 필요.
                    $_lv = Useful::arrayFind($_M['HERO'], function ($_m_hero) use ($star_hero, $_next) {
                        return $_m_hero['m_hero_base_pk'] == $star_hero['m_hero_base_pk'] && $_m_hero['level'] == $_next;
                    });
                    $_cnt = $HeroCombination->getSpecialCombinationInfo($_lv['level'], $star_hero['rare_type']);
                    if (isset($_cnt['m_item_pk'])) {
                        $need_item_count += $_cnt['cnt'];
                    }
                }
                if ($need_item_count > 0) {
                    $item_cnt = $Item->getItemCount($Session->lord['lord_pk'], $need_item_pk); // 소지수
                    if ($need_item_count > $item_cnt) {
                        throw new Exception('특수조합석이 부족합니다.');
                    }
                }
            }
            // 새로운 능력치 적용
            $Hero->setNewStat($star_hero['hero_pk'], $star_hero['m_offi_pk']);

            // 재료카드 방출
            foreach($mate_hero_arr as $material_hero) {
                $ret = $Hero->setAbandon($material_hero['hero_pk'], 'special_combination');
                if (!$ret) {
                    throw new Exception('방출 실패');
                }
            }

            // 황금 소모
            if ($need_gold > 0) {
                $ret = $GoldPop->decreaseGold($params['posi_pk'], $need_gold, null, 'hero_combination_special');
                if (!$ret) {
                    throw new Exception('황금이 부족합니다.');
                }
            }

            // 조합석 소모
            if ($need_item_count > 0) {
                $ret = $Item->useItem($Session->getPosiPk(), $Session->lord['lord_pk'], $need_item_pk, $need_item_count, ['_yn_quest' => true]);
                if(!$ret) {
                    throw new Exception($NsGlobal->getErrorMessage());
                }
            }

            $PgGame->commit();
        } catch(Exception $e) {
            $PgGame->rollback();
            throw new ErrorHandler('error', $e->getMessage(), true);
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        // 조합 후 영웅 정보 가져오기
        // $hero_info = $Hero->getFreeHeroInfo($star_hero);
        $hero_info = $Hero->getMyHeroInfo($star_hero_pk);

        // 퀘스트 체크
        $Quest = new Quest($Session, $PgGame);
        $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero', 'hero_type' => 'special_combi']);

        // 푸시데이터
        if (isset($hero_info['m_offi_pk']) && $hero_info['m_offi_pk'] != null) {
            $Session->sqAppend('HERO', [$hero_info['hero_pk'] => $hero_info], null, $Session->lord['lord_pk'], $params['posi_pk']);
        }

        // 재료 영웅 로그 남기기
        $Log = new Log($Session, $PgGame);
        foreach($log_material_card_info as $log_hero) {
            $Log->setHeroCombi($Session->lord['lord_pk'], $Session->getPosiPk(), $log_hero['hero_pk'], $log_hero['m_hero_pk'], 'special', $log_serial_num, 'material', 0, json_encode($log_hero));
        }

        // 메인 영웅 로그 남기기
        // $t = $Hero->getMyHeroInfo($params['selected_star_hero']);
        // $t = ['hero_pk' => $t['hero_pk'], 'm_offi_pk' => $t['m_offi_pk'], 'posi_pk' => $t['posi_pk'], 'status' => $t['status'], 'status_cmd' => $t['status_cmd'], 'yn_lord' => $t['yn_lord'], 'leadership' => $t['leadership'], 'mil_force' => $t['mil_force'], 'intellect' => $t['intellect'], 'politics' => $t['politics'], 'charm' => $t['charm'], 'm_hero_pk' => $t['m_hero_pk'], 'enchant' => $t['enchant'], 'm_hero_skil_pk1' => $t['m_hero_skil_pk1'], 'm_hero_skil_pk2' => $t['m_hero_skil_pk2'], 'm_hero_skil_pk3' => $t['m_hero_skil_pk3'], 'm_hero_skil_pk4' => $t['m_hero_skil_pk4'], 'm_hero_skil_pk5' => $t['m_hero_skil_pk5'], 'm_hero_skil_pk6' => $t['m_hero_skil_pk6'], 'skill_exp' => $t['skill_exp'], 'slot_pk1' => $t['slot_pk1'], 'slot_pk2' => $t['slot_pk2'], 'slot_pk3' => $t['slot_pk3'], 'slot_pk4' => $t['slot_pk4'], 'slot_pk5' => $t['slot_pk5'], 'slot_pk6' => $t['slot_pk6'], 'main_slot_pk1' => $t['main_slot_pk1'], 'main_slot_pk2' => $t['main_slot_pk2'], 'main_slot_pk3' => $t['main_slot_pk3'], 'main_slot_pk4' => $t['main_slot_pk4'], 'main_slot_pk5' => $t['main_slot_pk5'], 'main_slot_pk6' => $t['main_slot_pk6']];
        $log_result_info = $combi_result;
        $log_result_info['item_info'] = ['m_item_pk' => 500708, 'need_cnt' => $need_item_count];

        $Log->setHeroCombi($Session->lord['lord_pk'], $Session->getPosiPk(), $log_result_info['after']['hero_pk'], $log_result_info['after']['m_hero_pk'], 'special', $log_serial_num, 'star_hero', $need_gold, json_encode($log_result_info));

        // $m_hero = $_M['HERO'][$t['m_hero_pk']];
        // $m_hero_base = $_M['HERO_BASE'][$m_hero['m_hero_base_pk']];

        // 채팅 메세지 (5레어 이상이거나 레벨이 11 이상인 영웅들만 출력)
        /*if (($before['level'] < $after['level']) && ($m_hero_base['rare_type'] > 4 || $m_hero['level'] > 10)) {
            // TODO 채팅 구현 후 주석해제 필요.
            // $Chat = new CChat($Session);
            // $Chat->send_announce_system_about_hero($Session->lord['lord_name']."님이 특수조합을 통해 ".$m_hero_base['name']." Lv.".$m_hero['level']." 영웅 카드로 업그레이드에 성공하였습니다.");
        }*/

        $result_arr = ['hero_info' => $hero_info, 'combi_result' => $combi_result];
    } else {
        throw new ErrorHandler('error', 'Invalid request.');
    }

    return $Render->nsXhrReturn('success', null, $result_arr);
}));

$app->post('/api/heroManage/usedOfficer', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT m_offi_pk FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $m_offi_pk = $PgGame->fetchOne();

    $Hero = new Hero($Session, $PgGame);
    $hero_list = $Hero->getMyAppoQueuHeroList($Session->lord['lord_pk']);

    $PgGame->query('SELECT m_offi_pk FROM my_hero WHERE lord_pk = $1 AND status = $2', [$Session->lord['lord_pk'], 'A']);
    $PgGame->fetchAll();

    return $Render->nsXhrReturn('success', null, ['hero_info' => $PgGame->rows, 'm_offi_pk' => $m_offi_pk, 'hero_list' => $hero_list]);
}));

$app->post('/api/heroManage/collectInfo', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 재료 리스트
    $Hero = new Hero($Session, $PgGame);
    $hero_meterial_list = $Hero->getCollectionHeroMaterialInfo($params['collect_pk']);

    // 하위 컬렉션 리스트
    $top_meterial_cnt = $Hero->getCollectionTopMaterialCnt($params['collect_pk']);
    // 상위 컬렉션 리스트
    $bottom_meterial_cnt = $Hero->getCollectionBottomMaterialCnt($params['collect_pk']);

    // TODO 나중에 오타 수정 해야겠다.
    return $Render->nsXhrReturn('success', null, ['m_hero_comb_coll_pk' => $params['collect_pk'], 'hero_list' => $hero_meterial_list, 'top_meterial_cnt' => $top_meterial_cnt, 'bottom_meterial_cnt' => $bottom_meterial_cnt]);
}));

$app->post('/api/heroManage/collectCombination', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $hero_list = explode(',', $params['collect_hero_arr']);

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['hero_collection_combi']);

    // 1. 영웅 개수가 맞는지 확인
    if (COUNT($hero_list) < $_M['HERO_COLL_COMB'][$params['m_hero_comb_pk']]['material_count']) {
        throw new ErrorHandler('error', '영웅 재료 개수 부족 (1)');
    }

    // 2. 해당 영웅들이 등용대기중인 데이터가 맞는지 확인
    $PgGame->query("SELECT a.hero_pk, c.m_hero_base_pk, b.level FROM my_hero a, hero b, m_hero c
WHERE a.lord_pk = $1 AND a.hero_pk IN ({$params['collect_hero_arr']}) AND a.status = $2 AND a.hero_pk = b.hero_pk AND b.m_hero_pk = c.m_hero_pk", [$Session->lord['lord_pk'], 'G']);
    if ($PgGame->fetchAll() < $_M['HERO_COLL_COMB'][$params['m_hero_comb_pk']]['material_count']) {
        throw new ErrorHandler('error', '영웅 재료 개수 부족 (2)');
    }

    $hero_info = $PgGame->rows;

    $hero_pks = [];
    foreach($hero_info AS $k => $v) {
        $hero_pks[] = $v['m_hero_base_pk'];
    }

    $Hero = new Hero($Session, $PgGame);

    try {
        $log_serial_num = time().'_'.$Session->lord['lord_pk'];
        $log_material_card_info = [];

        foreach($hero_list as $k => $v) {
            $t = $Hero->getMyHeroInfo($v);
            if (!$t) {
                throw new Exception('영웅 조합에 실패 하였습니다. 다시 시도해 주시기 바랍니다.');
            }
            $t = ['hero_pk' => $t['hero_pk'], 'm_offi_pk' => $t['m_offi_pk'], 'posi_pk' => $t['posi_pk'], 'status' => $t['status'], 'status_cmd' => $t['status_cmd'], 'yn_lord' => $t['yn_lord'], 'leadership' => $t['leadership'], 'mil_force' => $t['mil_force'], 'intellect' => $t['intellect'], 'politics' => $t['politics'], 'charm' => $t['charm'], 'm_hero_pk' => $t['m_hero_pk'], 'enchant' => $t['enchant'], 'm_hero_skil_pk1' => $t['m_hero_skil_pk1'], 'm_hero_skil_pk2' => $t['m_hero_skil_pk2'], 'm_hero_skil_pk3' => $t['m_hero_skil_pk3'], 'm_hero_skil_pk4' => $t['m_hero_skil_pk4'], 'm_hero_skil_pk5' => $t['m_hero_skil_pk5'], 'm_hero_skil_pk6' => $t['m_hero_skil_pk6'], 'skill_exp' => $t['skill_exp'], 'slot_pk1' => $t['slot_pk1'], 'slot_pk2' => $t['slot_pk2'], 'slot_pk3' => $t['slot_pk3'], 'slot_pk4' => $t['slot_pk4'], 'slot_pk5' => $t['slot_pk5'], 'slot_pk6' => $t['slot_pk6'], 'main_slot_pk1' => $t['main_slot_pk1'], 'main_slot_pk2' => $t['main_slot_pk2'], 'main_slot_pk3' => $t['main_slot_pk3'], 'main_slot_pk4' => $t['main_slot_pk4'], 'main_slot_pk5' => $t['main_slot_pk5'], 'main_slot_pk6' => $t['main_slot_pk6']];
            $log_material_card_info[] = $t;
        }
    } catch(Exception $e) {
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 3. 해당 컬렉션에 필요한 영웅들이 맞느지 확인

    $material_cnt = 0;

    for ($i = 1; $i <= 8; $i++) {
        if (in_array($_M['HERO_COLL_COMB'][$params['m_hero_comb_pk']]['material_' . $i], $hero_pks)) {
            $material_cnt++;
        }
    }

    if ($material_cnt < $_M['HERO_COLL_COMB'][$params['m_hero_comb_pk']]['material_count']) {
        throw new ErrorHandler('error', '영웅 재료 개수 부족 (3)');
    }

    // 조합레벨
    $HeroCombination = new HeroCombination($Session, $PgGame);
    $level = $HeroCombination->getCollectionCombinationLevel('NORMAL', $hero_info);

    if ($level < 1 || $level > 10) {
        throw new ErrorHandler('error', '조합 불가능한 레벨입니다.');
    }

    $need_gold = 0;
    global $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        // 1. 영웅 방출
        foreach($hero_info AS $k => $v) {
            $ret = $Hero->setAbandon($v['hero_pk'], 'collection_combi');
            if (!$ret) {
                throw new Exception('방출 실패');
            }
        }

        // 2. 영웅 지급
        $m_hero_base_pk = $_M['HERO_BASE'][$_M['HERO_COLL_COMB'][$params['m_hero_comb_pk']]['m_hero_base_pk']];
        $hero_pk = $Hero->getNewHero(null, $level, $m_hero_base_pk['rare_type'], $m_hero_base_pk['m_hero_base_pk'], $Session->lord['level'], null, null, 'collect_combi', $m_hero_base_pk['over_type'], $m_hero_base_pk['yn_new_gacha'], $m_hero_base_pk['yn_modifier']);

        if (!$hero_pk) {
            throw new Exception('hero_pk is null');
        }
        $ret = $Hero->setMyHeroCreate($hero_pk, $Session->lord['lord_pk'], 'V', null, null, 'N', 'collect_combi');
        if (!$ret) {
            throw new Exception('영웅 영입 실패');
        }

        $PgGame->commit();
    } catch(Exception $e) {
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    $hero_info = $Hero->getFreeHeroInfo($hero_pk);

    // 재료 카드 로그 기록하기
    $Log = new Log($Session, $PgGame);
    foreach($log_material_card_info as $log_hero) {
        $Log->setHeroCombi($Session->lord['lord_pk'], $Session->getPosiPk(), $log_hero['hero_pk'], $log_hero['m_hero_pk'], 'collection_combi', $log_serial_num, 'material', $need_gold, json_encode($log_hero));
    }

    // TODO 코드 정리가 좀 필요하겠다.
    $t = $Hero->getMyHeroInfo($hero_info['hero_pk']);
    $t = ['combi_result' => $hero_info, 'hero_pk' => $t['hero_pk'], 'm_offi_pk' => $t['m_offi_pk'], 'posi_pk' => $t['posi_pk'], 'status' => $t['status'], 'status_cmd' => $t['status_cmd'], 'yn_lord' => $t['yn_lord'], 'leadership' => $t['leadership'], 'mil_force' => $t['mil_force'], 'intellect' => $t['intellect'], 'politics' => $t['politics'], 'charm' => $t['charm'], 'm_hero_pk' => $t['m_hero_pk'], 'enchant' => $t['enchant'], 'm_hero_skil_pk1' => $t['m_hero_skil_pk1'], 'm_hero_skil_pk2' => $t['m_hero_skil_pk2'], 'm_hero_skil_pk3' => $t['m_hero_skil_pk3'], 'm_hero_skil_pk4' => $t['m_hero_skil_pk4'], 'm_hero_skil_pk5' => $t['m_hero_skil_pk5'], 'm_hero_skil_pk6' => $t['m_hero_skil_pk6'], 'skill_exp' => $t['skill_exp'], 'slot_pk1' => $t['slot_pk1'], 'slot_pk2' => $t['slot_pk2'], 'slot_pk3' => $t['slot_pk3'], 'slot_pk4' => $t['slot_pk4'], 'slot_pk5' => $t['slot_pk5'], 'slot_pk6' => $t['slot_pk6'], 'main_slot_pk1' => $t['main_slot_pk1'], 'main_slot_pk2' => $t['main_slot_pk2'], 'main_slot_pk3' => $t['main_slot_pk3'], 'main_slot_pk4' => $t['main_slot_pk4'], 'main_slot_pk5' => $t['main_slot_pk5'], 'main_slot_pk6' => $t['main_slot_pk6']];

    $m_hero = $_M['HERO'][$t['m_hero_pk']];
    $m_hero_base = $_M['HERO_BASE'][$m_hero['m_hero_base_pk']];

    if ($m_hero_base['rare_type'] > 4) {
        // TODO 채팅 구현 후 재확인
        // $Chat = new CChat($Session);
        // $Chat->send_announce_system_about_hero($Session->lord['lord_name']."님이 컬렉션조합을 통해 ".$m_hero_base['name']." Lv.".$m_hero['level']." 영웅 카드를 획득하였습니다.");
        // $Chat->send_announce_system_about_hero($text_pub_combi_hero);
    }

    $Log->setHeroCombi($Session->lord['lord_pk'], $Session->getPosiPk(), $t['hero_pk'], $t['m_hero_pk'], 'collection_combi', $log_serial_num, 'new_hero', $need_gold, json_encode($t));

    return $Render->nsXhrReturn('success', null, ['hero_info' => $hero_info, 'action' => 'collect_combi']);
}));


$app->post('/api/heroManage/materialHeroList', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 해당 영웅 m_hero_base_pk
    $PgGame->query('select m_hero_base_pk from my_hero a, hero b, m_hero c where a.lord_pk = $1 and a.hero_pk = $2 and a.status = $3 and a.hero_pk = b.hero_pk and b.m_hero_pk = c.m_hero_pk', [$Session->lord['lord_pk'], $params['hero_pk'], 'G']);
    $m_hero_base_pk = $PgGame->fetchOne();

    // 해당 영웅과 같은 영웅 찾기
    $hero_list = explode(',', $params['collect_hero_arr']);

    $PgGame->query('SELECT a.hero_pk FROM my_hero a, hero b, m_hero c WHERE a.lord_pk = $1
AND a.status = $2 AND a.hero_pk = b.hero_pk AND b.m_hero_pk = c.m_hero_pk
AND c.m_hero_base_pk = $3 AND a.hero_pk != $4 ORDER BY c.level', [$Session->lord['lord_pk'], 'G', $m_hero_base_pk, $params['hero_pk']]);
    $PgGame->fetchAll();
    $rows = $PgGame->rows;

    $hero_info = [];
    $cnt = 0;

    $Hero = new Hero($Session, $PgGame);
    foreach($rows AS $k => $v) {
        $hero_info[$cnt] = $Hero->getMyHeroInfo($v['hero_pk']);
        if (in_array($v['hero_pk'], $hero_list)) {
            $hero_info[$cnt]['change'] = '불가능';
        } else {
            $hero_info[$cnt]['change'] = '가능';
        }
        $cnt++;
    }

    return $Render->nsXhrReturn('success', null, ['hero_info' => $hero_info, 'hero_pk' => $params['hero_pk']]);
}));

$app->post('/api/heroManage/skillCombination', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 1. 조합에 필요한 개수가 맞는지 체크
    // $log_combi_skill = [];

    $skill_list = explode(',', $params['selected_skill']);
    $skill_cnt = count($skill_list);

    // $log_combi_skill['metetial_skill'] = $skill_list;

    if ($skill_cnt != 5) {
        throw new ErrorHandler('error', '기술 재료가 부족합니다.<br />기술 조합은 5개의 재료 기술이 필요합니다.');
    }
    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['hero_skill']);

    // 군주 스킬이 포함되어 있는지 체크
    foreach($skill_list AS $k => $v) {
        if ($_M['HERO_SKILL'][$v]['yn_lord_skill'] != 'N') {
            throw new ErrorHandler('error', '조합 불가능한 기술이 포함되어 있습니다.');
        }
    }

    // 2. 소지하고 있는 스킬이 맞는지 수량은 충분한지 체크
    $HeroSkill = new HeroSkill($Session, $PgGame);
    $skill_array = $HeroSkill->checkMySkill($skill_list, $params['selected_skill']);
    if (!$skill_array) {
        throw new ErrorHandler('error', '소유한 기술 재료가 부족합니다.');
    }

    // 3. 조합에 쓰일 스킬 정리. 타입 정리
    $combi_skill_type = [];
    $combi_skill_type['B'] = 0;
    $combi_skill_type['P'] = 0;
    $combi_skill_type['S'] = 0;
    $combi_skill_type['A'] = 0;
    $combi_skill_type['D'] = 0;

    $combi_skill_rare = [];

    $rare_point = 0;

    // 일단 레어도 평균으로
    foreach($skill_list AS $k => $v) {
        $m = $_M['HERO_SKILL'][$v];
        if ($m) {
            $rare_point += $m['rare'];
        }
    }

    $rare_point = $rare_point / count($skill_list);
    $rare_average = round($rare_point, 1);
    $rare_point = $rare_average;

    foreach($skill_list AS $k => $v) {
        $m = $_M['HERO_SKILL'][$v];
        if ($m) {
            if (! isset($combi_skill_type[$m['type']])) {
                $combi_skill_type[$m['type']] = 0;
            }
            if (! isset($combi_skill_type[$m['rare']])) {
                $combi_skill_rare[$m['rare']] = 0;
            }
            $combi_skill_type[$m['type']]++;
            $combi_skill_rare[$m['rare']]++;
        }

        // 같은 타입이 5개 일때
        if ($combi_skill_type[$m['type']] >= 5) {
            $rare_point += 0.1;
        }

        // 같은 레어도가 5개 일때
        if ($combi_skill_rare[$m['rare']] >= 5) {
            $rare_point += 0.2;
        }
    }

    // 같은 기술이 3개 이상 일 때
    foreach($skill_array AS $k => $v) {
        if ($v >= 3) {
            $rare_point += ($v * 0.1);
        }
    }

    // 레어도별 가중치
    if ($rare_average < 2) $rare_point += 0.4;
    else if ($rare_average < 3) $rare_point += 0.3;
    else if ($rare_average < 4) $rare_point += 0.2;
    else if ($rare_average < 5) $rare_point += 0.1;

    // 추가 보너스
    $rare_point = floor($rare_point + $HeroSkill->getBonusPoint());

    // 8 레어도를 초과하는 스킬은 존재하지 않으므로
    if ($rare_point > 8) {
        $rare_point = 8;
    }

    // 3. 스킬 조합
    global $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        // 조합 처리
        $combination_skill = $HeroSkill->getCombinationSkill($rare_point);
        if (!$combination_skill)
        {
            throw new Exception('기술 조합에 실패했습니다. (1)');
        }

        // $log_combi_skill['combination_skill'] = $combination_skill;

        // 조합재료 스킬 제거
        foreach ($skill_array AS $k => $v) {
            $ret = $HeroSkill->setDeleteHeroSkill($Session->lord['lord_pk'], $k, $v);
            if (!$ret) {
                throw new Exception('기술 조합에 실패했습니다. (2)');
            }
        }

        // 스킬 지급
        $HeroSkill->setHeroSkillRegist($Session->lord['lord_pk'], $combination_skill, 'skill_combination');

        $PgGame->commit();
    } catch(Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->nsXhrReturn('success', null, ['comb_skil_pk' => $combination_skill]);
}));