<?php
global $app, $Render, $i18n;

//로드 - 보유 건설 목록 조회
$app->post('/admin/gm/api/terr_own_hero', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    function getOpenedSlotCount($exp)
    {
        global $_M;

        $hero_skill_exp = $_M['HERO_SKILL_EXP'];
        $hero_skill_exp = array_reverse($hero_skill_exp);

        $cnt = 0;
        if (count($hero_skill_exp) > 0)
        {
            foreach($hero_skill_exp as $k => $v)
            {
                // $k 소켓 숫자 / $v 마스터데이터
                if ($exp >= $v['exp'])
                {
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
        foreach($arr as $v)
        {
            if ($v > 0)
            {
                ++$cnt;
            }
        }
        return $cnt;
    }

    function getPositionAreaType($str) : string{
        $position_area_type = [
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

        return $position_area_type[$str] ?? $str;
    }

    function getHeroStatusType($str):string{

        $hero_status_type = [
            'I' => '대기',
            'A' => '배속',
            'C' => '명령',
            'T' => '부상',
            'P' => '강화'
        ];

        return $hero_status_type[$str] ?? $str;
    }

    function getHeroCommandType ($str) : string
    {
        $hero_cmd_type = [
            'None' => '없음',
            'Const' => '건설',
            'Encou' => '탐색',
            'Invit' => '초빙',
            'Techn' => '연구',
            'Scout' => '정찰',
            'Trans' => '수송',
            'Reinf' => '지원',
            'Attac' => '공격',
            'Preva' => '보급',
            'Camp' => '주둔',
            'Recal' => '회군'
        ];

        return $hero_cmd_type[$str] ?? $str;
    }

    $page = $params['page'];
    $limit = $params['rows'];

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['hero', 'hero_base', 'officer', 'building', 'hero_skill', 'hero_skill_exp']);

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $sql = "SELECT COUNT(hero_pk) FROM my_hero WHERE m_offi_pk IS NOT NULL AND lord_pk = $1 AND posi_pk = $2";
    $query_params = [$_SESSION['selected_lord']['lord_pk'], $_SESSION['selected_terr']['posi_pk']];

    $PgGame->query($sql, $query_params);
    $count = $PgGame->fetchOne();

    $total_page = ($count > 0 ) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;

    $sql = "SELECT  hero.hero_pk, my_hero.m_offi_pk, hero.m_hero_pk, hero.yn_trade,
	                territory.title AS terr_title, my_hero.posi_pk, position.type AS posi_type, position.level AS posi_level,
	                hero.rare_type, hero.skill_exp, hero.enchant, my_hero.status_cmd, my_hero.cmd_type, hero.loyalty,
                    t1.slot_pk1, t1.m_hero_skil_pk1, t1.main_slot_pk1, t1.slot_pk2, t1.m_hero_skil_pk2, t1.main_slot_pk2,
                    t1.slot_pk3, t1.m_hero_skil_pk3, t1.main_slot_pk3, t1.slot_pk4, t1.m_hero_skil_pk4, t1.main_slot_pk4,
                    t1.slot_pk5, t1.m_hero_skil_pk5, t1.main_slot_pk5, t1.slot_pk6, t1.m_hero_skil_pk6, t1.main_slot_pk6
            FROM    my_hero, hero, position, territory, getmyheroesskillslot({$_SESSION['selected_lord']['lord_pk']}) as t1
            WHERE   my_hero.hero_pk = hero.hero_pk AND
                    my_hero.hero_pk = t1.hero_pk AND
                    my_hero.posi_pk = position.posi_pk AND
                    position.posi_pk = territory.posi_pk AND
                    my_hero.lord_pk = $3 AND
                    my_hero.posi_pk = $4
            ORDER BY my_hero.m_offi_pk ASC, hero.rare_type DESC
            LIMIT $1 OFFSET $2";

    $query_params = [$limit, $offset_start, $_SESSION['selected_lord']['lord_pk'], $_SESSION['selected_terr']['posi_pk']];
    $PgGame->query($sql, $query_params);
    $PgGame->fetchAll();
    $hero_list = $PgGame->rows;

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];


    foreach($hero_list as $k => $v)
    {
        $offi_title = ''; $employment_fee = ''; $hero_name = ''; $terr_title = ''; $status_detail = '-';
        $addTag1 = ''; $addTag2 = '';

        $_row = [];
        $_row['id'] = $v['hero_pk'];

        if ( $_M['HERO_BASE'][$_M['HERO'][$v['m_hero_pk']]['m_hero_base_pk']]['yn_modifier'] == 'Y'){
            $addTag1 = '<span style="color:#FF0000;">';
            $addTag2 = '</span>';
        }
        else if (isset($_M['HERO_BASE'][$_M['HERO'][$v['m_hero_pk']]['m_hero_base_pk']]['over_rank']) && $_M['HERO_BASE'][$_M['HERO'][$v['m_hero_pk']]['m_hero_base_pk']]['over_rank'] == 'Y') {
            $addTag1 = '<span style="color:#FFAA00;">';
            $addTag2 = '</span>';
        }
        else if ($_M['HERO_BASE'][$_M['HERO'][$v['m_hero_pk']]['m_hero_base_pk']]['yn_new_gacha'] == 'Y')
        {
            $addTag1 = '<span style="color:#FF96ED;">';
            $addTag2 = '</span>';
        }

        if ( $v['posi_type'] == 'T')
        {
            $offi_title = $_M['OFFI'][$v['m_offi_pk']]['title'];
            $employment_fee = $_M['OFFI'][$v['m_offi_pk']]['employment_fee'];
            $hero_name = $_M['HERO_BASE'][$_M['HERO'][$v['m_hero_pk']]['m_hero_base_pk']]['name'] . ' Lv.' . $_M['HERO'][$v['m_hero_pk']]['level'];
            $terr_title = $v['terr_title'] . ' (' . $v['posi_pk'] . ')';

            if ($v['status_cmd'] == 'A')
            {
                $sql = "SELECT m_buil_pk FROM building_in_castle WHERE posi_pk = $1 AND assign_hero_pk = $2";
                $query_params = [$v['posi_pk'], $v['hero_pk']];
                $PgGame->query($sql, $query_params);
                $m_buil_pk = $PgGame->fetchOne();

                $status_detail= ($m_buil_pk > 0 ) ? $_M['BUIL'][$m_buil_pk]['title'] : '성벽';
            }
            else if ($v['status_cmd'] == 'C' ){
                $status_detail = getHeroCommandType($v['cmd_type']);
            }
        }
        else { // 군주가 점령한 자원지라면
            $offi_title = $_M['OFFI'][$v['m_offi_pk']]['title'];
            $employment_fee = $_M['OFFI'][$v['m_offi_pk']]['employment_fee'];
            $hero_name = $_M['HERO_BASE'][$_M['HERO'][$v['m_hero_pk']]['m_hero_base_pk']]['name'] . ' Lv.' . $_M['HERO'][$v['m_hero_pk']]['level'];
            $terr_title = (getPositionAreaType($v['posi_type']) . ' Lv.' . $v['posi_level'] . ' (' . $v['posi_pk'] . ')');
        }

        $hero_name = $addTag1.$hero_name.$addTag2;
        $_row['cell'] = [$v['hero_pk'], $offi_title, $hero_name, $terr_title, $v['rare_type'], getUsedSlotCount($v['slot_pk1'], $v['slot_pk2'], $v['slot_pk3'], $v['slot_pk4'], $v['slot_pk5'], $v['slot_pk6']).' / '.getOpenedSlotCount($v['skill_exp']), $v['enchant'], getHeroStatusType($v['status_cmd']), $status_detail, $v['loyalty'], $employment_fee, ($v['yn_trade']== 'N' ? '가능' : '불가능')];
        $response->rows[] = $_row;
    }

    return $Render->view(json_encode($response));
}));

$app->post('/admin/gm/api/setHeroSkill/Equip', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $lord_name = $_SESSION['selected_lord']['lord_name'];
    $posi_pk = $_SESSION['selected_lord']['main_posi_pk'];

    global $_M, $_M_HERO_SKILL, $NsGlobal;
    $NsGlobal->requireMasterData(['hero_skill']);

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');
    $Session = new Session(false);
    $Hero = new Hero($Session, $PgGame);
    $HeroSkill = new HeroSkill($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    $hero_info = $Hero->getMyHeroInfo($params['hero_pk']);

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

    $open_slot_cnt = $HeroSkill->getHeroSkillOpenSlotCount($hero_info['skill_exp']);
    $used_slot_cnt = getUsedSlotCount($hero_info['slot_pk1'], $hero_info['slot_pk2'], $hero_info['slot_pk3'], $hero_info['slot_pk4'], $hero_info['slot_pk5'], $hero_info['slot_pk6']);
    $need_slot_cnt = $_M_HERO_SKILL[$params['m_hero_skil_pk']]['use_slot_count'];

    if ($used_slot_cnt + $_M_HERO_SKILL[$params['m_hero_skil_pk']]['use_slot_count'] > $open_slot_cnt) {
        return $Render->view(json_encode(['result' => false, 'msg' => '잔여 슬롯이 부족합니다.']));
    }

    $possible_cnt = 0;
    $possible_equip = false;
    $equip_slot = [];
    for ($i = 1; $i <= $open_slot_cnt; $i++) {
        if (!$hero_info['m_hero_skil_pk' . $i]) {
            $equip_slot[$possible_cnt] = $i;
            $possible_cnt++;
            if ($need_slot_cnt == $possible_cnt) {
                $possible_equip = true;
                $i = $open_slot_cnt + 1;
            }
        } else {
            $possible_cnt = 0;
        }
    }

    if (!$possible_equip) {
        return $Render->view(json_encode(['result' => false, 'msg' => '필요 슬롯 개수 만큼 연속된 장착 가능 슬롯이 필요합니다.']));
    }

    for ($i = $equip_slot[0]; $i < $equip_slot[0] + COUNT($equip_slot); $i++) {
        $PgGame->query('INSERT INTO my_hero_skill_slot (hero_pk, slot_pk, m_hero_skil_pk, main_slot_pk) VALUES ($1, $2, $3, $4)', [$params['hero_pk'], $i, $params['m_hero_skil_pk'], $equip_slot[0]]);
    }

    if ($_M['HERO_SKILL'][$params['m_hero_skil_pk']]['type'] == 'D') {
        $HeroSkill->setHeroSkillPlusStat($params['hero_pk'], $params['m_hero_skil_pk'], $params['lord_pk']);
    }

    $Session->sqAppend('HERO', [$params['hero_pk'] => $Hero->getMyHeroInfo($params['hero_pk'])], null, $_SESSION['selected_lord']['lord_pk']);

    $Log->setHeroSkill($params['lord_pk'], $posi_pk, $params['hero_pk'], 'GMSkillEquip', $params['m_hero_skil_pk'], null, $equip_slot[0], null, null, $hero_info['m_hero_pk']);

    $PgGm = new Pg('GM');

    $description = [
        'action' => 'gm_equip_skill',
        'selected_server' => $_SESSION['selected_server'],
        'lord' => ['lord_pk' => $params['lord_pk'], 'lord_name' => $lord_name],
        'cause' => $params['cause'],
        'hero_pk' => $params['hero_pk'],
        'm_hero_skil_pk' => $params['m_hero_skil_pk']
    ];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'I', serialize($description)]);

    return $Render->view(json_encode(['result' => true, 'hero_pk' => $params['hero_pk']]));
}));


$app->post('/admin/gm/api/setHeroSkill/unEquip', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $lord_name = $_SESSION['selected_lord']['lord_name'];
    $posi_pk = $_SESSION['selected_lord']['main_posi_pk'];

    global $_M, $_M_HERO_SKILL, $NsGlobal;
    $NsGlobal->requireMasterData(['hero_skill']);

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');
    $Session = new Session(false);
    $Hero = new Hero($Session, $PgGame);
    $HeroSkill = new HeroSkill($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    $hero_info = $Hero->getMyHeroInfo($params['hero_pk']);

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

    $PgGame->query('SELECT main_slot_pk, m_hero_skil_pk FROM my_hero_skill_slot WHERE hero_pk = $1 AND slot_pk = $2', [$params['hero_pk'], $params['slot_pk']]);
    $PgGame->fetch();
    if (! $PgGame->row) {
        return $Render->view(json_encode(['result' => false, 'msg' => '해제할 기술이 존재하지 않습니다.']));
    }
    $row = $PgGame->row;


    try {
        $PgGame->query('BEGIN');
        global $_QBW_SQ_REFRESH_FLAG;
        $_QBW_SQ_REFRESH_FLAG = true;

        // 스킬 장착 해제
        $PgGame->query('DELETE FROM my_hero_skill_slot WHERE hero_pk = $1 AND main_slot_pk = $2', [$params['hero_pk'], $row['main_slot_pk']]);

        // 스탯 변경이 있을 경우
        if ($_M['HERO_SKILL'][$row['m_hero_skil_pk']]['type'] == 'D') {
            $HeroSkill->setHeroSkillMinusStat($params['hero_pk'], $row['m_hero_skil_pk'], $params['lord_pk']);
        }

        $Session->sqAppend('HERO', [$params['hero_pk'] => $Hero->getMyHeroInfo($params['hero_pk'])], null, $_SESSION['selected_lord']['lord_pk']);

        // 스킬 리스트에 추가
        $HeroSkill->setHeroSkillRegist($params['lord_pk'], $row['m_hero_skil_pk'], 'gm_unequip');

        $PgGame->query('COMMIT');
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->query('ROLLBACK');
        return $Render->view(json_encode(['result' => false, 'msg' => '오류가 발생했습니다.']));
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_QBW_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    $PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$params['hero_pk']]);
    $m_hero_pk = $PgGame->fetchOne();

    // Log
    $Log->setHeroSkill($params['lord_pk'], $posi_pk, $params['hero_pk'], 'GM_UnequipSkill', $row['m_hero_skil_pk'], HERO_SKILL_UNEQUIP, $params['slot_pk'], null, null, $m_hero_pk);

    $PgGm = new Pg('GM');

    $description = [
        'action' => 'gm_unequip_skill',
        'selected_server' => $_SESSION['selected_server'],
        'lord' => ['lord_pk' => $params['lord_pk'], 'lord_name' => $lord_name],
        'cause' => $params['cause'],
        'hero_pk' => $params['hero_pk'],
        'm_hero_skil_pk' => $params['m_hero_skil_pk']
    ];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'I', serialize($description)]);

    return $Render->view(json_encode(['result' => true, 'hero_pk' => $params['hero_pk']]));
}));
