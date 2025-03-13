<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/lord_own_hero_appointed', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $page = $params['page']; // get the requested page
    $limit = $params['rows']; // get how many rows we want to have into the grid

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['officer', 'hero', 'hero_base', 'building', 'hero_skill_exp']);

    $PgGame->query('SELECT COUNT(hero_pk) FROM my_hero WHERE m_offi_pk IS NOT NULL AND lord_pk = $1', [$_SESSION['selected_lord']['lord_pk']]);

    $count = $PgGame->fetchOne();
    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;

    $PgGame->query("SELECT hero.hero_pk, my_hero.m_offi_pk, hero.m_hero_pk, hero.yn_trade,
	territory.title AS terr_title, my_hero.posi_pk, position.type AS posi_type, position.level AS posi_level,
	hero.rare_type, hero.skill_exp, hero.enchant, my_hero.status_cmd, my_hero.cmd_type, hero.loyalty,
	t1.slot_pk1, t1.m_hero_skil_pk1, t1.main_slot_pk1, t1.slot_pk2, t1.m_hero_skil_pk2, t1.main_slot_pk2,
	t1.slot_pk3, t1.m_hero_skil_pk3, t1.main_slot_pk3, t1.slot_pk4, t1.m_hero_skil_pk4, t1.main_slot_pk4,
	t1.slot_pk5, t1.m_hero_skil_pk5, t1.main_slot_pk5, t1.slot_pk6, t1.m_hero_skil_pk6, t1.main_slot_pk6
FROM my_hero, hero, position, territory, getmyheroesskillslot({$_SESSION['selected_lord']['lord_pk']}) as t1
WHERE my_hero.hero_pk = hero.hero_pk AND my_hero.hero_pk = t1.hero_pk AND my_hero.posi_pk = position.posi_pk AND
	position.posi_pk = territory.posi_pk AND my_hero.lord_pk = $3 ORDER BY hero.hero_pk, hero.rare_type DESC LIMIT $1 OFFSET $2", [$limit, $offset_start, $_SESSION['selected_lord']['lord_pk']]);
    $PgGame->fetchAll();

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    function getOpenedSlotCount($exp)
    {
        global $_M;
        $hero_skill_exp = array_reverse($_M['HERO_SKILL_EXP']);
        $cnt = 0;
        if (count($hero_skill_exp) > 0) {
            foreach($hero_skill_exp as $v) {
                // $k 소켓 숫자 / $v 마스터데이터
                if ($exp >= $v['exp']) {
                    $cnt = $v['level'];
                    break;
                }
            }
        }
        return $cnt;
    }

    function getUsedSlotCount($slot1, $slot2, $slot3, $slot4, $slot5, $slot6): int
    {
        $arr = [$slot1, $slot2, $slot3, $slot4, $slot5, $slot6];
        $cnt = 0;
        foreach($arr as $v) {
            if ($v > 0) {
                ++$cnt;
            }
        }
        return $cnt;
    }

    $_M['M_POSITION_AREA']['TYPE'] = [
        'L' => '저수지',
        'D' => '불모지',
        'M' => '광산',
        'F' => '산림',
        'G' => '초원',
        'N' => '황건적',
        'A' => '평지',
        'R' => '농경지',
        'E' => '평지',
        'T' => '군주영지'
    ];
    $CODESET['HERO_STATUS_CMD'] = [
        'I' => '대기',
        'A' => '배속',
        'C' => '명령',
        'T' => '부상',
        'P' => '강화'
    ];
    $CODESET['HERO_CMD_TYPE'] = [
        'None' => '없음',
        'Const' => '건설',
        'Encou' => '탐색',
        'Invit' => '초빙',
        'Techn' => '개발',
        'Scout' => '정찰',
        'Trans' => '수송',
        'Reinf' => '지원',
        'Attac' => '공격',
        'Preva' => '보급',
        'Camp' => '주둔',
        'Recal' => '회군'
    ];

    $i = 0;
    $hero_list = $PgGame->rows;
    foreach ($hero_list as $v) {
        $response->rows[$i] = [];
        $response->rows[$i]['id'] = $v['hero_pk'];

        /*$addTag1 = '';
        $addTag2 = '';
        if ($_M['HERO_BASE'][$_M['HERO'][$v['m_hero_pk']]['m_hero_base_pk']]['yn_modifier'] == 'Y')
        {
            $addTag1 = '<span style="color: #ff0000;">';
            $addTag2 = '</span>';
        }
        else if ($_M['HERO_BASE'][$_M['HERO'][$v['m_hero_pk']]['m_hero_base_pk']]['over_rank'] == 'Y')
        {
            $addTag1 = '<span style="color: #ffaa00;">';
            $addTag2 = '</span>';
        }
        else if ($_M['HERO_BASE'][$_M['HERO'][$v['m_hero_pk']]['m_hero_base_pk']]['yn_new_gacha'] == 'Y')
        {
            $addTag1 = '<span style="color: #FF96ED;">';
            $addTag2 = '</span>';
        }*/

        if ($v['posi_type'] == 'T') {
            // 군주의 영지인 경우
            $offi_title = $_M['OFFI'][$v['m_offi_pk']]['title'];
            $employment_fee = $_M['OFFI'][$v['m_offi_pk']]['employment_fee'];
            $hero_name = $_M['HERO_BASE'][$_M['HERO'][$v['m_hero_pk']]['m_hero_base_pk']]['name'] . ' Lv.' . $_M['HERO'][$v['m_hero_pk']]['level'];
            $terr_title = $v['terr_title'] . ' (' . $v['posi_pk'] . ')';
            $status_detail = '-';

            if ($v['status_cmd'] == 'A')
            {
                // 배속 중인 위치 찾기
                $PgGame->query('SELECT m_buil_pk FROM building_in_castle WHERE posi_pk = $1 AND assign_hero_pk = $2', [$v['posi_pk'], $v['hero_pk']]);
                $m_buil_pk = $PgGame->fetchOne();
                if ($m_buil_pk > 0) {
                    $status_detail = $_M['BUIL'][$m_buil_pk]['title'];
                } else {
                    // 건물 pk가 없으면 현재로서는 성벽 뿐
                    $status_detail = '성벽';
                }
            } else if ($v['status_cmd'] == 'C') {
                $status_detail = $CODESET['HERO_CMD_TYPE'][$v['cmd_type']];
            }

        } else {
            // 군주가 점령한 자원지라면
            $offi_title = $_M['OFFI'][$v['m_offi_pk']]['title'];
            $employment_fee = $_M['OFFI'][$v['m_offi_pk']]['employment_fee'];
            $hero_name = $_M['HERO_BASE'][$_M['HERO'][$v['m_hero_pk']]['m_hero_base_pk']]['name'] . ' Lv.' . $_M['HERO'][$v['m_hero_pk']]['level'];
            $terr_title = ($_M['M_POSITION_AREA']['TYPE'][$v['posi_type']] . ' Lv.' . $v['posi_level'] . ' (' . $v['posi_pk'] . ')');
            $status_detail = '-';
        }

        // $hero_name = $addTag1.$hero_name.$addTag2;

        $response->rows[$i]['cell'] = [$v['hero_pk'], $offi_title, $hero_name, $terr_title, $v['rare_type'], getUsedSlotCount($v['slot_pk1'], $v['slot_pk2'], $v['slot_pk3'], $v['slot_pk4'], $v['slot_pk5'], $v['slot_pk6']).' / '.getOpenedSlotCount($v['skill_exp']), $v['enchant'], $CODESET['HERO_STATUS_CMD'][$v['status_cmd']], $status_detail, $v['loyalty'], $employment_fee, ($v['yn_trade'] == 'N' ? '가능' : '불가능')];
        $i++;
    }

    return $Render->view(json_encode($response));
}));

$app->post('/admin/gm/api/getHeroSlotInfo', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['officer', 'hero', 'hero_base', 'building', 'hero_skill', 'hero_skill_exp']);

    if (! isset($params['hero_pk'])) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '선택된 영웅이 없습니다.']));
    }

    function getOpenedSlotCount($exp)
    {
        global $_M;

        $hero_skill_exp = $_M['HERO_SKILL_EXP'];
        $hero_skill_exp = array_reverse($hero_skill_exp);

        $cnt = 0;
        if (count($hero_skill_exp) > 0) {
            foreach($hero_skill_exp as $k => $v) {
                // $k 소켓 숫자 / $v 마스터데이터
                if ($exp >= $v['exp']) {
                    $cnt = $v['level'];
                    break;
                }
            }
        }
        return $cnt;
    }

    function getUsedSlotCount($slot1, $slot2, $slot3, $slot4, $slot5, $slot6): int
    {
        $arr = [$slot1, $slot2, $slot3, $slot4, $slot5, $slot6];
        $cnt = 0;
        foreach($arr as $v) {
            if ($v > 0) {
                ++$cnt;
            }
        }
        return $cnt;
    }

    $PgGame->query("SELECT t1.hero_pk, t1.m_offi_pk, t1.leadership, t1.mil_force, t1.intellect, t1.politics, t1.charm,
 t2.m_hero_pk, t2.enchant, t1.status, t1.status_cmd, t2.loyalty,
 t2.leadership_basic, t2.leadership_enchant, t2.leadership_plusstat, t2.leadership_skill,
 t2.mil_force_basic, t2.mil_force_enchant, t2.mil_force_plusstat,  t2.mil_force_skill,
 t2.intellect_basic, t2.intellect_enchant, t2.intellect_plusstat, t2.intellect_skill,
 t2.politics_basic, t2.politics_enchant, t2.politics_plusstat,  t2.politics_skill,
 t2.charm_basic, t2.charm_enchant, t2.charm_plusstat, t2.charm_skill,
 t2.m_hero_skil_pk_1, t2.m_hero_skil_pk_2, t2.m_hero_skil_pk_3, t2.m_hero_skil_pk_4, t2.skill_exp,
 t3.slot_pk1, t3.m_hero_skil_pk1, t3.main_slot_pk1, t3.slot_pk2, t3.m_hero_skil_pk2, t3.main_slot_pk2,
 t3.slot_pk3, t3.m_hero_skil_pk3, t3.main_slot_pk3, t3.slot_pk4, t3.m_hero_skil_pk4, t3.main_slot_pk4,
 t3.slot_pk5, t3.m_hero_skil_pk5, t3.main_slot_pk5, t3.slot_pk6, t3.m_hero_skil_pk6, t3.main_slot_pk6, t1.yn_lord
FROM my_hero AS t1, hero AS t2, getmyheroskillslot({$params['hero_pk']}) AS t3
WHERE t1.hero_pk = t2.hero_pk AND t1.hero_pk = $1", [$params['hero_pk']]);

    if (!$PgGame->fetch()) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '선택된 영웅이 없습니다.']));
    }

    $hero_info = $PgGame->row;
    $hero_info['name'] = $_M['HERO_BASE'][$_M['HERO'][$hero_info['m_hero_pk']]['m_hero_base_pk']]['name'] . ' Lv.' . $_M['HERO'][$hero_info['m_hero_pk']]['level'];
    $hero_info['officer'] = $_M['OFFI'][$hero_info['m_offi_pk']]['title'] ?? '';
    $hero_info['stat_plus_leadership'] = $_M['OFFI'][$hero_info['m_offi_pk']]['stat_plus_leadership'] ?? 0;
    $hero_info['stat_plus_mil_force'] = $_M['OFFI'][$hero_info['m_offi_pk']]['stat_plus_mil_force'] ?? 0;
    $hero_info['stat_plus_intellect'] = $_M['OFFI'][$hero_info['m_offi_pk']]['stat_plus_intellect'] ?? 0;
    $hero_info['stat_plus_politics'] = $_M['OFFI'][$hero_info['m_offi_pk']]['stat_plus_politics'] ?? 0;
    $hero_info['stat_plus_charm'] = $_M['OFFI'][$hero_info['m_offi_pk']]['stat_plus_charm'] ?? 0;

    if ($hero_info['m_hero_skil_pk1'] > 0) {
        $hero_info['m_hero_skil_title_1'] = $_M['HERO_SKILL'][$hero_info['m_hero_skil_pk1']]['title'] . ' Lv.' . $_M['HERO_SKILL'][$hero_info['m_hero_skil_pk1']]['rare'];
    }
    if ($hero_info['m_hero_skil_pk2'] > 0) {
        $hero_info['m_hero_skil_title_2'] = $_M['HERO_SKILL'][$hero_info['m_hero_skil_pk2']]['title'] . ' Lv.' . $_M['HERO_SKILL'][$hero_info['m_hero_skil_pk2']]['rare'];
    }
    if ($hero_info['m_hero_skil_pk3'] > 0) {
        $hero_info['m_hero_skil_title_3'] = $_M['HERO_SKILL'][$hero_info['m_hero_skil_pk3']]['title'] . ' Lv.' . $_M['HERO_SKILL'][$hero_info['m_hero_skil_pk3']]['rare'];
    }
    if ($hero_info['m_hero_skil_pk4'] > 0) {
        $hero_info['m_hero_skil_title_4'] = $_M['HERO_SKILL'][$hero_info['m_hero_skil_pk4']]['title'] . ' Lv.' . $_M['HERO_SKILL'][$hero_info['m_hero_skil_pk4']]['rare'];
    }
    if ($hero_info['m_hero_skil_pk5'] > 0) {
        $hero_info['m_hero_skil_title_5'] = $_M['HERO_SKILL'][$hero_info['m_hero_skil_pk5']]['title'] . ' Lv.' . $_M['HERO_SKILL'][$hero_info['m_hero_skil_pk5']]['rare'];
    }
    if ($hero_info['m_hero_skil_pk6'] > 0) {
        $hero_info['m_hero_skil_title_6'] = $_M['HERO_SKILL'][$hero_info['m_hero_skil_pk6']]['title'] . ' Lv.' . $_M['HERO_SKILL'][$hero_info['m_hero_skil_pk6']]['rare'];
    }

    $hero_info['slot_info'] = getUsedSlotCount($hero_info['slot_pk1'], $hero_info['slot_pk2'], $hero_info['slot_pk3'], $hero_info['slot_pk4'], $hero_info['slot_pk5'], $hero_info['slot_pk6']) . ' / ' . getOpenedSlotCount($hero_info['skill_exp']);
    $hero_info['used_slot'] = getUsedSlotCount($hero_info['slot_pk1'], $hero_info['slot_pk2'], $hero_info['slot_pk3'], $hero_info['slot_pk4'], $hero_info['slot_pk5'], $hero_info['slot_pk6']);
    $hero_info['opened_slot'] = getOpenedSlotCount($hero_info['skill_exp']);

    $hero_info['lord_enchant'] = 0;

    if ($hero_info['yn_lord'] == 'Y') {
        $PgGame->query('SELECT lord_enchant FROM lord WHERE lord_pk = $1', [$_SESSION['selected_lord']['lord_pk']]);
        $hero_info['lord_enchant'] = $PgGame->fetchOne();
        $hero_info['leadership_enchant'] = $hero_info['leadership_enchant'] - ($hero_info['lord_enchant'] * 3);
        $hero_info['mil_force_enchant'] = $hero_info['mil_force_enchant'] - ($hero_info['lord_enchant'] * 3);
        $hero_info['intellect_enchant'] = $hero_info['intellect_enchant'] - ($hero_info['lord_enchant'] * 3);
        $hero_info['politics_enchant'] = $hero_info['politics_enchant'] - ($hero_info['lord_enchant'] * 3);
        $hero_info['charm_enchant'] = $hero_info['charm_enchant'] - ($hero_info['lord_enchant'] * 3);
    }

    return $Render->view(json_encode($hero_info));
}));

$app->post('/admin/gm/api/changeHeroStatus/unAssign', $Render->wrap(function (array $params) use ($Render, $i18n) {
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
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Bdic = new Bdic($Session, $PgGame, $Resource, $GoldPop);
    $Hero = new Hero($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    global $_M, $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    $NsGlobal->requireMasterData(['army']);

    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        // 영웅 영지 및 배속 정보 알아오기
        $PgGame->query('SELECT t1.posi_pk, t2.in_cast_pk FROM my_hero t1, territory_hero_assign t2 WHERE t1.hero_pk = $1 AND t1.hero_pk = t2.hero_pk AND t1.status_cmd = $2', [$params['hero_pk'],'A']);
        $PgGame->fetch();
        $assign_info = $PgGame->row;
        if (! isset($assign_info['posi_pk']) || ! isset($assign_info['in_cast_pk'])) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '배속 해제 실패 (1)']));
        }

        if ($assign_info['in_cast_pk'] == 1) {
            echo json_encode(['result' => 'fail', 'msg' => '대전영웅 배속 해제 불가']);
        }

        $assign_hero_pk = $Bdic->heroUnassign($assign_info['posi_pk'], $assign_info['in_cast_pk']);
        if (!$assign_hero_pk) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '배속 해제 실패 (2)']));
        }

        $ret = $Hero->unsetCommand($assign_hero_pk);
        if (!$ret) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '배속 해제 실패 (3)']));
        }

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        return $Render->view(json_encode(['result' => 'fail', 'msg' => $e->getMessage()]));
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    if ($assign_info['in_cast_pk'] == 2) {
        $Troop = new Troop($Session, $PgGame);
        $ret = [];

        $army_info = $Troop->getFightingSpirit($assign_info['posi_pk']);
        if ($army_info['fightingSpirit'] > 100)
            $army_info['fightingSpirit'] = 100;
        $ret['fightingSpirit'] = $army_info['fightingSpirit'];

        // 총병력
        $armyPop = $army_info['armyPop'];
        $ret['total_army'] = $armyPop['population'];

        // 아군 병력
        $armyArr = [];
        $PgGame->query('SELECT * FROM army WHERE posi_pk = $1', [$assign_info['posi_pk']]);
        $PgGame->fetch();
        $r = $PgGame->row;
        foreach ($_M['ARMY_C'] AS $k => $v) {
            $armyArr[$k] = $r[$k];
        }

        $armyPop = $Troop->getArmyPop($armyArr);
        $ret['my_army'] = $armyPop['population'];
        // 동맹군 병력
        $ret['alli'] = $ret['total_army'] - $ret['my_army'];

        $Session->sqAppend('PUSH', ['TROOP_INFO' => $ret], null, $_SESSION['selected_lord']['lord_pk'], $assign_info['posi_pk']);
    }

    return $Render->view(json_encode(['result' => 'ok']));
}));


// 해임
$app->post('/admin/gm/api/changeHeroStatus/dismiss', $Render->wrap(function (array $params) use ($Render, $i18n) {
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
    $Session = new Session(false);
    $Lord = new Lord($Session, $PgGame);
    $Session->lord = $Lord->getLordInfo($_SESSION['selected_lord']['lord_pk']);
    $Log = new Log($Session, $PgGame);

    // status 가 A, status_cmd 가 I 인 상태에서만 가능
    $PgGame->query('SELECT status, status_cmd, yn_lord, posi_pk FROM my_hero WHERE hero_pk = $1', [$params['hero_pk']]);
    $PgGame->fetch();
    $status_row = $PgGame->row;
    if (! isset($status_row)) {
        // 영웅을 찾을 수 없음
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '해임실패 (1)']));
    }

    if ($status_row['status'] != 'A' || $status_row['status_cmd'] != 'I' || $status_row['yn_lord'] == 'Y') // 군주카드 해임 불가
    {
        // 명령 가능한 상태가 아님
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '해임실패 (2)']));
    }

    // lord.num_slot_guest_hero 넘치나 검사
    $PgGame->query('SELECT COUNT(hero_pk) AS cnt FROM my_hero WHERE lord_pk = $1 AND status = $2', [$Session->lord['lord_pk'], 'G']);
    $guest_cnt = $PgGame->fetchOne();

    if ($guest_cnt >= $_SESSION['selected_lord']['num_slot_guest_hero']) {
        // 빈 슬롯 없음
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '해임실패 (3)']));
    }

    global $_M, $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    $NsGlobal->requireMasterData(['hero']);

    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        // 상태변경 - status 를 G로 last_dismiss_dt 를 현재로 m_offi_pk 와 posi_pk 를 NULL 로...
        $PgGame->query('UPDATE my_hero SET status = $1, last_dismiss_dt = now(), m_offi_pk = $2, posi_pk = $3 WHERE hero_pk = $4', ['G', null, null, $params['hero_pk']]);

        // 해임시 충성도 10 감소
        $PgGame->query('UPDATE hero SET loyalty = loyalty - 10 WHERE hero_pk = $1', [$params['hero_pk']]);
        $PgGame->query('UPDATE hero SET loyalty = 0 WHERE hero_pk = $1 AND loyalty < 0', [$params['hero_pk']]); // 충성도 최소는 0

        $PgGame->query('UPDATE lord SET num_appoint_hero = ( SELECT COUNT(*) FROM my_hero WHERE lord_pk = $1 AND status = $2 ) WHERE lord_pk = $1', [$_SESSION['selected_lord']['lord_pk'], 'A']);

        $Hero = new Hero($Session, $PgGame);

        // 능력치 재계산
        $Hero->setNewStat($params['hero_pk']);

        // 카드덱에서 제거하기
        $Session->sqAppend('HERO', [$params['hero_pk'] => ['status' => 'NULL']], null, $Session->lord['lord_pk'], $Session->lord['main_posi_pk']);

        // 영향력 감소
        $PgGame->query('SELECT level, create_reason FROM hero WHERE hero_pk = $1', [$params['hero_pk']]);
        $PgGame->fetch();
        $level = $PgGame->row['level'];

        if ($PgGame->row['create_reason'] != 'regist') {
            $Lord->decreasePower($Session->lord['lord_pk'], $_M['HERO_APPOINT_POWER'][$level]['total_power']);
        }

        // Log
        $Log->setHero($_SESSION['selected_lord']['lord_pk'], $status_row['posi_pk'], 'Dismiss', $params['hero_pk'], 'G', null, null, 'gmtool_dismiss');

        $PgGame->commit();
    } catch (Exception $e) {
        $PgGame->rollback();
        return $Render->view(json_encode(['result' => 'fail', 'msg' => $e->getMessage()]));
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->view(json_encode(['result' => 'ok']));
}));

$app->post('/admin/gm/api/changeHeroStatus/assignCityHall', $Render->wrap(function (array $params) use ($Render, $i18n) {
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
    $Session = new Session(false);
    $Lord = new Lord($Session, $PgGame);
    $Session->lord = $Lord->getLordInfo($_SESSION['selected_lord']['lord_pk']);
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Bdic = new Bdic($Session, $PgGame, $Resource, $GoldPop);
    $Hero = new Hero($Session, $PgGame);

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;

    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        // 영웅 영지 및 배속 정보 알아오기
        $PgGame->query('SELECT t1.posi_pk FROM my_hero t1 WHERE t1.hero_pk = $1 AND t1.status_cmd = $2', [$params['hero_pk'],'I']);
        $PgGame->fetch();
        $assign_info = $PgGame->row;
        if (! isset($assign_info['posi_pk'])) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '대전 배속 실패 (1)']));
        }

        $assign_hero_pk = $Bdic->heroUnassign($assign_info['posi_pk'], 1);
        if ($assign_hero_pk) {
            $ret = $Hero->unsetCommand($assign_hero_pk);
            if (!$ret) {
                return $Render->view(json_encode(['result' => 'fail', 'msg' => '대전 배속 실패 (2)']));
            }
        } else {
            // 배속된 영웅이 없을 경우도 있고 이 경우는 해제에서 에러가 난 경우 임.
            if ($NsGlobal->getErrorMessage()) {
                echo json_encode(['result' => 'fail', 'msg' => $NsGlobal->getErrorMessage()]);
            }
        }

        $ret = $Hero->setCommand($assign_info['posi_pk'], $params['hero_pk'], 'A');
        if (!$ret) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '대전 배속 실패 (3)']));
        }

        $ret = $Bdic->heroAssign($assign_info['posi_pk'], 1, $params['hero_pk']);
        if (!$ret) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '대전 배속 실패 (4)']));
        }

        $PgGame->commit();
    } catch (Exception $e) {
        $PgGame->rollback();
        return $Render->view(json_encode(['result' => 'fail', 'msg' => $e->getMessage()]));
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->view(json_encode(['result' => 'ok']));
}));

$app->post('/admin/gm/api/changeHeroEnchant', $Render->wrap(function (array $params) use ($Render, $i18n) {
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
    $Session = new Session(false);
    $Lord = new Lord($Session, $PgGame);
    $Session->lord = $Lord->getLordInfo($_SESSION['selected_lord']['lord_pk']);
    $Hero = new Hero($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    if (!isset($params['lord_pk']) || $params['lord_pk'] < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '영웅을 소유한 군주가 지정되지 않았습니다.']));
    }

    if (!isset($params['hero_pk']) || $params['hero_pk'] < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '강화 수치를 수정할 영웅이 지정되지 않았습니다.']));
    }

    if (!isset($params['cause']) || iconv_strlen($params['cause'], 'utf-8') < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '강화 수치 변경 사유를 입력해주십시오.']));
    }

    if (!isset($params['leadership']) || $params['leadership'] < 0 || $params['leadership'] > 50) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '통솔 강화 수치가 올바르지 않습니다.']));
    }

    if (!isset($params['mil_force']) || $params['mil_force'] < 0 || $params['mil_force'] > 50) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '무력 강화 수치가 올바르지 않습니다.']));
    }

    if (!isset($params['intellect']) || $params['intellect'] < 0 || $params['intellect'] > 50) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '지력 강화 수치가 올바르지 않습니다.']));
    }

    if (!isset($params['politics']) || $params['politics'] < 0 || $params['politics'] > 50) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '정치 강화 수치가 올바르지 않습니다.']));
    }

    if (!isset($params['charm']) || $params['charm'] < 0 || $params['charm'] > 50) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '매력 강화 수치가 올바르지 않습니다.']));
    }

    $enchant_count = $params['leadership'] + $params['mil_force'] + $params['intellect'] + $params['politics'] + $params['charm'];

    if ($enchant_count > 50) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '최대 강화 수치는 합계 50까지 입니다.']));
    }

    if ($params['lord_enchant'] > 10) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '최대 각성 수치는 10까지 입니다.']));
    }

    if ($params['lord_enchant'] < 0) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '최소 각성 수치는 0입니다.']));
    }

    $hero_enchant_count = $params['hero_enchant'];

    if ($hero_enchant_count > 10) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '최대 강화 수치는 10까지 입니다.']));
    }

    if ($hero_enchant_count < 0) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '최대 강화 수치는 10까지 입니다.']));
    }
    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;

    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        $PgGame->query('SELECT lord_enchant FROM lord WHERE lord_pk = $1', [$params['lord_pk']]);
        $prev_lord_enchant = $PgGame->fetchOne();

        $PgGame->query('UPDATE lord SET lord_enchant = $2 WHERE lord_pk = $1', [$params['lord_pk'] ,$params['lord_enchant']]);

        $PgGame->query('SELECT t2.hero_pk, t2.m_offi_pk, t1.lord_name, t1.lord_enchant FROM lord t1, my_hero t2 WHERE t1.lord_pk = t2.lord_pk AND t1.lord_pk = $1 AND t2.hero_pk = $2', [$params['lord_pk'], $params['hero_pk']]);
        if (!$PgGame->fetch()) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '지정된 영웅을 찾을 수 없습니다.']));
        }

        $m_offi_pk = $PgGame->row['m_offi_pk'];
        $lord_name = $PgGame->row['lord_name'];
        $lord_enchant = $PgGame->row['lord_enchant'];

        $prev_hero_info = $Hero->getMyHeroInfo($params['hero_pk']);

        $PgGame->query('UPDATE hero SET leadership_enchant = $2, mil_force_enchant = $3, intellect_enchant = $4, politics_enchant = $5, charm_enchant = $6, enchant = $7 WHERE hero_pk = $1',
            [$params['hero_pk'], (($params['leadership']) + ($lord_enchant * 3)), (($params['mil_force']) + ($lord_enchant * 3)), (($params['intellect']) + ($lord_enchant * 3)),
                (($params['politics']) + ($lord_enchant * 3)), (($params['charm']) + ($lord_enchant * 3)), $hero_enchant_count]);


        $Hero->setNewStat($params['hero_pk'], $m_offi_pk);
        $hero_info = $Hero->getMyHeroInfo($params['hero_pk']);

        $log_description = "gm_enchant[leadership[{$params['leadership']}],mil_force[{$params['mil_force']}],intellect[{$params['intellect']}],politics[{$params['politics']}],charm[{$params['charm']}],lord_enchant[$lord_enchant]]";
        $Log->setHero($params['lord_pk'], $hero_info['posi_pk'], 'gm_enchant', $hero_info['hero_pk'], $hero_info['status'], $hero_info['status_cmd'], $hero_info['cmd_type'], $log_description);

        $Session->sqAppend('HERO', [$hero_info['hero_pk'] => $hero_info], null, $params['lord_pk'], $hero_info['posi_pk']);
        $Session->sqAppend('LORD', ['lord_enchant' => $lord_enchant], null, $params['lord_pk']);

        $PgGm = new Pg('GM');

        $temp = [];
        $temp['prev'] = [
            'leadership' => intval(($prev_hero_info['leadership_enchant']) - $prev_lord_enchant),
            'mil_force' => intval(($prev_hero_info['mil_force_enchant']) - $prev_lord_enchant),
            'intellect' => intval(($prev_hero_info['intellect_enchant']) - $prev_lord_enchant),
            'politics' => intval(($prev_hero_info['politics_enchant']) - $prev_lord_enchant),
            'charm' => intval(($prev_hero_info['charm_enchant']) - $prev_lord_enchant),
            'lord_enchant' => intval($prev_lord_enchant)
        ];
        $temp['change'] = [
            'leadership' => intval($params['leadership'] - $lord_enchant),
            'mil_force' => intval($params['mil_force'] - $lord_enchant),
            'intellect' => intval($params['intellect'] - $lord_enchant),
            'politics' => intval($params['politics'] - $lord_enchant),
            'charm' => intval($params['charm'] - $lord_enchant),
            'lord_enchant' => intval($lord_enchant)
        ];
        $description = ['action' => 'gm_enchant', 'selected_server' => $_SESSION['selected_server'], 'lord' => ['lord_pk' => $params['lord_pk'], 'lord_name' => $lord_name], 'cause' => $params['cause'], 'hero_pk' => $params['hero_pk'], 'm_hero_pk' => $hero_info['m_hero_pk'], 'enchant_info' => $temp];
        $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'I', serialize($description)]);

        $PgGame->commit();
    } catch (Exception $e) {
        $PgGame->rollback();
        return $Render->view(json_encode(['result' => 'fail', 'msg' => $e->getMessage()]));
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->view(json_encode(['result' => 'ok', 'hero_pk' => $params['hero_pk']]));
}));


$app->post('/admin/gm/api/changeHeroLoyalty', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    if (intval($params['hero_loyalty']) < 0) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '충성도는 0보다 작게 수정할 수 없습니다.']));
    }
    if (intval($params['hero_loyalty']) > 100) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '충성도는 100보다 크게 수정할 수 없습니다.']));
    }
    if (iconv_strlen($params['change_cause']) < 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '변경 사유를 입력하여 주십시오.']));
    }

    $Gm = new Gm();
    $checkPermit = $Gm->checkGMPermission(['EDIT']);
    if (is_string($checkPermit)) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => "{$checkPermit} 권한이 없습니다."]));
    }
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');
    $Session = new Session(false);
    $Lord = new Lord($Session, $PgGame);
    $Session->lord = $Lord->getLordInfo($_SESSION['selected_lord']['lord_pk']);
    $Hero = new Hero($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    $PgGame->query('SELECT loyalty FROM hero WHERE hero_pk = $1', [$params['hero_pk']]);
    $origin_hero_loyalty = $PgGame->fetchOne();

    // 있으므로 update
    $PgGame->query('UPDATE hero SET loyalty = $1 WHERE hero_pk = $2', [$params['hero_loyalty'], $params['hero_pk']]);
    if ($PgGame->getAffectedRows() != 1) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '충성도 수정에 실패하였습니다.']));
    }

    $hero_info = $Hero->getMyHeroInfo($params['hero_pk']);
    $Session->sqAppend('HERO', [$hero_info['hero_pk'] => $hero_info], null, $_SESSION['selected_lord']['lord_pk']);

    $description = 'prev['.($origin_hero_loyalty).'];change['.($params['hero_loyalty']).'];';

    $Log = new Log($Session, $PgGame);
    $Log->setHero($_SESSION['selected_lord']['lord_pk'], $hero_info['posi_pk'], 'gm_loyalty', $hero_info['hero_pk'], $hero_info['status'], $hero_info['status_cmd'], $hero_info['cmd_type'], $description);

    // GM 로그 기록
    $PgGm = new Pg('GM');
    // 히스토리 기록
    $description = [
        'action' => 'change_hero_loyalty',
        'selected_server' => $_SESSION['selected_server'],
        'lord' => $_SESSION['selected_lord'],
        'hero_pk' => $params['hero_pk'],
        'changed_hero_loyalty' => $params['hero_loyalty'],
        'prev_hero_loyalty' => $origin_hero_loyalty,
        'cause' => $params['change_cause']
    ];
    $query_params = [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'P', serialize($description)];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', $query_params);

    return $Render->view(json_encode(['result' => 'ok', 'hero_pk' => $params['hero_pk']]));
}));

