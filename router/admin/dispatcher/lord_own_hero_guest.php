<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/lord_own_hero_guest', $Render->wrap(function (array $params) use ($Render, $i18n) {
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

    $PgGame->query('SELECT COUNT(hero_pk) FROM my_hero WHERE status = $2 AND lord_pk = $1', [$_SESSION['selected_lord']['lord_pk'], 'G']);

    $count = $PgGame->fetchOne();
    $total_page = ($count > 0) ? ceil($count/$limit) : 0;
    $page = ($page > $total_page) ? $total_page : $page;
    $offset_start = $limit * $page - $limit;
    $offset_start = ($offset_start < 0) ? 0 : $offset_start;

    $PgGame->query("SELECT hero.hero_pk, my_hero.m_offi_pk, hero.m_hero_pk, hero.yn_trade, hero.rare_type, hero.skill_exp, hero.enchant, my_hero.status_cmd, my_hero.cmd_type, hero.loyalty,
	t1.slot_pk1, t1.m_hero_skil_pk1, t1.main_slot_pk1, t1.slot_pk2, t1.m_hero_skil_pk2, t1.main_slot_pk2,
	t1.slot_pk3, t1.m_hero_skil_pk3, t1.main_slot_pk3, t1.slot_pk4, t1.m_hero_skil_pk4, t1.main_slot_pk4,
	t1.slot_pk5, t1.m_hero_skil_pk5, t1.main_slot_pk5, t1.slot_pk6, t1.m_hero_skil_pk6, t1.main_slot_pk6
FROM my_hero, hero, getmyheroesskillslot({$_SESSION['selected_lord']['lord_pk']}) as t1
WHERE my_hero.hero_pk = hero.hero_pk AND my_hero.hero_pk = t1.hero_pk AND my_hero.status = 'G' AND my_hero.lord_pk = $3
ORDER BY hero.hero_pk, hero.rare_type DESC
LIMIT $1 OFFSET $2", [$limit, $offset_start, $_SESSION['selected_lord']['lord_pk']]);
    $PgGame->fetchAll();

    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_page;
    $response->records = $count;
    $response->rows = [];

    function getOpenedSlotCount($exp) {
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

    $CODESET['HERO_STATUS_CMD'] = [
        'I' => '대기',
        'A' => '배속',
        'C' => '명령',
        'T' => '부상',
        'P' => '강화',
        'B' => '거래'
    ];

    $i = 0;
    $hero_list = $PgGame->rows;
    foreach ($hero_list as $v)
    {
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

        // $hero_name = $addTag1.$_M['HERO_BASE'][$_M['HERO'][$v['m_hero_pk']]['m_hero_base_pk']]['name'] . ' Lv.' . $_M['HERO'][$v['m_hero_pk']]['level'].$addTag2;
        $hero_name = $_M['HERO_BASE'][$_M['HERO'][$v['m_hero_pk']]['m_hero_base_pk']]['name'] . ' Lv.' . $_M['HERO'][$v['m_hero_pk']]['level'];

        $response->rows[$i]['cell'] = [$v['hero_pk'], $hero_name, $v['rare_type'], getUsedSlotCount($v['slot_pk1'], $v['slot_pk2'], $v['slot_pk3'], $v['slot_pk4'], $v['slot_pk5'], $v['slot_pk6']).' / '.getOpenedSlotCount($v['skill_exp']), $v['enchant'], $CODESET['HERO_STATUS_CMD'][$v['status_cmd']], $v['loyalty'], ($v['yn_trade'] == 'N' ? '가능' : '불가능')];
        $i++;
    }

    return $Render->view(json_encode($response));
}));

// 방출
$app->post('/admin/gm/api/changeHeroStatus/abandon', $Render->wrap(function (array $params) use ($Render, $i18n) {
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

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    $NsGlobal->requireMasterData([]);

    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        // status 가 G, status_cmd 가 I 인 상태에서만 가능
        $PgGame->query('SELECT status, status_cmd, cmd_type, posi_pk FROM my_hero WHERE hero_pk = $1', [$params['hero_pk']]);
        if (! $PgGame->fetch()) {
            // 영웅을 찾을 수 없음
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '영웅 방출 실패 (1)']));
        }
        $hero_info = $PgGame->row;

        if ($PgGame->row['status'] != 'G' || $PgGame->row['status_cmd'] != 'I') {
            // 방출 가능한 상태가 아님
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '영웅 방출 실패 (2)']));
        }

        // 오버랭크 영웅 타이머 삭제
        /*$query_params = Array('P', 'W','R', $params['hero_pk']);
        $PgGame->query('SELECT time_pk, (end_dt::abstime::integer - start_dt::abstime::integer) as reduce_time FROM timer WHERE status = $1 AND queue_status = $2 AND queue_type = $3 AND queue_pk = $4', $query_params);
        if ($PgGame->fetch())
        {
            $r = $Timer->speedup($PgGame->row['time_pk'], $PgGame->row['reduce_time']);
            if (!$r)
            {
                echo json_encode(Array('result' => 'fail', 'msg' => '영웅 방출 실패 (3)'));
                exit(1);
            }
        }*/

        $PgGame->query('DELETE FROM my_hero_skill_slot WHERE hero_pk = $1', [$params['hero_pk']]);
        $r = $PgGame->query('DELETE FROM my_hero WHERE hero_pk = $1', [$params['hero_pk']]);
        if (!$r || $PgGame->getAffectedRows() == 0) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '영웅 방출 실패 (3)']));
        }

        $PgGame->query('SELECT * FROM hero WHERE hero_pk = $1', [$params['hero_pk']]);
        $PgGame->fetch();
        $row = $PgGame->row;
        $r = $PgGame->query('DELETE FROM hero WHERE hero_pk = $1', [$params['hero_pk']]);
        if (!$r || $PgGame->getAffectedRows() == 0) {
            return $Render->view(json_encode(['result' => 'fail', 'msg' => '영웅 방출 실패 (4)']));
        }

        // Log
        $Log->setHero($_SESSION['selected_lord']['lord_pk'], $hero_info['posi_pk'], 'Abandon', $params['hero_pk'], $hero_info['status'], $hero_info['status_cmd'], $hero_info['cmd_type'], 'gm_abandon', $row['m_hero_pk']);
        $Log->setHeroData($row);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        return $Render->view(json_encode(['result' => 'fail', 'msg' => $e->getMessage()]));
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    return $Render->view(json_encode(['result' => 'ok']));
}));