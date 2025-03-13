<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/lord_own_hero_visit', $Render->wrap(function (array $params) use ($Render, $i18n) {
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

    $PgGame->query('SELECT COUNT(hero_pk) FROM my_hero WHERE status = $2 AND lord_pk = $1', [$_SESSION['selected_lord']['lord_pk'], 'V']);

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
WHERE my_hero.hero_pk = hero.hero_pk AND my_hero.hero_pk = t1.hero_pk AND my_hero.status = 'V' AND my_hero.lord_pk = $3
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