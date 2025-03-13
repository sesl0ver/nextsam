<?php
global $app, $Render, $i18n;

$app->post('/api/troop/withdrawal', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $r = $Troop->setStatusWithdrawal($params['troo_pk']);
    if (!$r) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));

// TODO recall_ally 포함
$app->post('/api/troop/recall', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // position.type 에 따라서 다르게 처리
    // 1. 영지 (동맹국의 영지)
    //  - 걍 떠남
    // 2. 이외 모두
    //  - 소유권을 NPC에 넘김
    //  - 자원지의 경우 상실 처리 (평지는 effect는 없음)
    //  - triptime (병과로 재계산 필요) 을 회군시간으로 설정하고 src_posi_pk 에만 timer 등록

    $PgGame->query('SELECT posi_pk, lord_pk, type FROM position WHERE posi_pk = (SELECT dst_posi_pk FROM troop WHERE troo_pk = $1)', [$params['troo_pk']]);
    $PgGame->fetch();
    $info = $PgGame->row;

    global $NsGlobal;

    $Troop = new Troop($Session, $PgGame);
    $Report = new Report($Session, $PgGame);
    if ($info['lord_pk'] == $Session->lord['lord_pk']) {
        // 내 자원지 - TODO 왜 막아놨지?
        //$Troop->lossOwnershipValley($info['lord_pk'], $info['posi_pk']);
        //$Troop->setStatusRecall($params['troo_pk']);
    } else {
        // 동맹국의 영지
        $row = $Troop->getTroop($params['troo_pk']);

        // 보고서
        $z_content = [];

        // hero
        $z_content['hero'][] = ['pk' => $row['captain_hero_pk'], 'm_pk' => $Troop->getHeroMasterDataPK($row['captain_hero_pk']), 'type' => 'captain'];
        if ($row['director_hero_pk']) {
            $z_content['hero'][] = ['pk' => $row['director_hero_pk'], 'm_pk' => $Troop->getHeroMasterDataPK($row['director_hero_pk']), 'type' => 'director'];
        }
        if ($row['staff_hero_pk']) {
            $z_content['hero'][] = ['pk' => $row['staff_hero_pk'], 'm_pk' => $Troop->getHeroMasterDataPK($row['staff_hero_pk']), 'type' => 'staff'];
        }

        // army
        foreach ($row AS $k => $v) {
            if (str_starts_with($k, 'army_')) {
                $z_content['army'][substr($k,5)] = $v;
            }
        }

        // from & to
        $z_from = ['posi_pk' => $row['src_posi_pk'], 'posi_name' => $row['from_position']];
        $z_to = ['posi_pk' => $row['dst_posi_pk'], 'posi_name' => $row['to_position']];

        $z_title = '';
        $z_summary = '';

        $Report->setReport($row['dst_lord_pk'], 'recall', 'ally_troop_recall', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));
    }

    $r = $Troop->setStatusRecall($params['troo_pk']);
    if (!$r) {
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    if ($info['type'] == 'P' && $info['lord_pk'] == $Session->lord['lord_pk']) {
        $Troop->lossOwnershipPoint($Session->lord['lord_pk'], $info['posi_pk'], 'Y');
        $Troop->setNpcPoint($info['posi_pk']);
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/troop/listMini', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    global $NsGlobal, $_M;
    $Troop = new Troop($Session, $PgGame);

    $rows = [];
    $rows['camp_army'] = 0;
    // 주둔 부대 목록
    $rows['troop_list'] = $Troop->getMyCampTroopsDstPosi($params['target_posi_pk']);

    // 주둔 부대 병력
    $army_arr = [];
    if (COUNT($rows['troop_list'])) {
        $NsGlobal->requireMasterData(['army']);
        $ret = $Troop->getCampArmy($params['target_posi_pk']);

        foreach ($_M['ARMY_C'] AS $k => $v) {
            $army_arr[$k] = $ret['army_' . $k];
        }

        $army_pop = $Troop->getArmyPop($army_arr);
        $rows['camp_army'] = $army_pop['population'];
    }

    // 병력수
    return $Render->nsXhrReturn('success', null, $rows);
}));

$app->post('/api/troop/view', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['troo_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT src_lord_pk ,dst_lord_pk ,src_posi_pk ,dst_posi_pk ,status ,cmd_type ,from_position ,to_position ,distance ,triptime ,camptime ,hour_food ,captain_hero_pk ,director_hero_pk ,staff_hero_pk ,reso_gold ,reso_food ,reso_horse ,reso_lumber ,reso_iron ,army_worker ,army_infantry ,army_pikeman ,army_scout ,army_spearman ,army_armed_infantry ,army_archer ,army_horseman ,army_armed_horseman ,army_transporter ,army_bowman ,army_battering_ram ,army_catapult ,army_adv_catapult , date_part(\'epoch\', start_dt)::integer as start_dt, move_time, date_part(\'epoch\', arrival_dt)::integer as arrival_dt, date_part(\'epoch\', withdrawal_dt)::integer as withdrawal_dt, use_item_pk FROM troop WHERE troo_pk = $1', [$params['troo_pk']]);
    if (!$PgGame->fetch()) {
        throw new ErrorHandler('error', '부대 정보를 찾을 수 없습니다.');
    }

    $ret_data = $PgGame->row;
    $ret_data['herodata'] = [];

    $Hero = new Hero($Session, $PgGame);

    // 현재 view 영지에 있는 영웅이 아니면 데이터 추출
    if ($params['posi_pk'] != $PgGame->row['src_posi_pk']) {
        $ret_data['herodata']['captain'] = $Hero->getFreeHeroInfo($ret_data['captain_hero_pk']);
        if ($ret_data['director_hero_pk']) {
            $ret_data['herodata']['director'] = $Hero->getFreeHeroInfo($ret_data['director_hero_pk']);
        }
        if ($ret_data['staff_hero_pk']) {
            $ret_data['herodata']['staff'] = $Hero->getFreeHeroInfo($ret_data['staff_hero_pk']);
        }
    }

    $PgGame->query('SELECT type FROM position WHERE posi_pk = $1', [$ret_data['dst_posi_pk']]);
    $ret_data['type'] = $PgGame->fetchOne();

    return $Render->nsXhrReturn('success', null, $ret_data);
}));

$app->post('/api/troop/valleyView', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 소속 : from_position : $Troop->getPositionName($params['posi_pk']);
    // 거리 : distance : getDistance($params['posi_pk'], $params['valley_posi_pk']);
    // 소요시간 : triptime : 모르겠다.

    $Troop = new Troop($Session, $PgGame);

    $ret_data = [];
    $ret_data['from_position'] = $Troop->getPositionName($params['posi_pk']);
    $ret_data['dst_posi_pk'] = $params['valley_posi_pk'];

    return $Render->nsXhrReturn('success', null, $ret_data);
}));

$app->post('/api/troop/campValley', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $row = $Troop->getTroop($params['troo_pk']);

    if (isset($row['src_time_pk'])) {
        $Troop->cancelTimer($row['src_time_pk']);
    }

    if (isset($row['dst_time_pk'])) {
        $Troop->cancelTimer($row['dst_time_pk']);
    }

    if (! $Troop->setStatusCampValley($params['troo_pk'], $row)) {
        $Troop->setStatusRecall($params['troo_pk']);
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/troop/campList', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $rows = $Troop->getMyCampTroopList($params['camp_posi_pk']);

    return $Render->nsXhrReturn('success', null, $rows);
}));

$app->post('/api/troop/camp', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $row = $Troop->getTroop($params['troo_pk']);

    if (isset($row['src_time_pk'])) {
        $Troop->cancelTimer($row['src_time_pk']);
    }

    if (isset($row['dst_time_pk'])) {
        $Troop->cancelTimer($row['dst_time_pk']);
    }

    if (! $Troop->setStatusCamp($params['troo_pk'], $row)) {
        $Troop->setStatusRecall($params['troo_pk']);
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/troop/enemyView', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 부대 찾기
    if (! isset($params['troo_pk']) || $params['troo_pk'] < 1) {
        throw new ErrorHandler('error', 'Invalid request.');
    }

    $Hero = new Hero($Session, $PgGame);

    $PgGame->query('SELECT captain_hero_pk, captain_desc, director_hero_pk, director_desc, staff_hero_pk, staff_desc,
army_worker, army_infantry, army_pikeman, army_scout, army_spearman, army_armed_infantry, army_archer,
army_horseman, army_armed_horseman, army_transporter, army_bowman, army_battering_ram, army_catapult, army_adv_catapult
FROM troop WHERE troo_pk = $1', [$params['troo_pk']]);

    if (!$PgGame->fetch()) {
        throw new ErrorHandler('error', '현재 진군 중이 아닌 부대입니다.');
    }

    $enemy_info = $PgGame->row;

    $PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$enemy_info['captain_hero_pk']]);

    $enemy_info['captain'] = $PgGame->fetchOne();

    if ($enemy_info['director_hero_pk'] != null) {
        $PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$enemy_info['director_hero_pk']]);
        $enemy_info['director'] = $PgGame->fetchOne();
    }

    if ($enemy_info['staff_hero_pk'] != null) {
        $PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$enemy_info['staff_hero_pk']]);
        $enemy_info['staff'] = $PgGame->fetchOne();
    }

    $army_info = [];
    foreach($enemy_info as $k => $v) {
        if (preg_match('/^army_/', $k) > 0) {
            $army_info[$k] = $v;
            unset($enemy_info[$k]);
        }
    }

    $Troop = new Troop($Session, $PgGame);
    $enemy_info['army'] = $Troop->getNumberToTextDesc($army_info);

    return $Render->nsXhrReturn('success', null, $enemy_info);
}));

$app->post('/api/troop/getRaidList', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $total_count = $Troop->getRaidListCount($Session->lord['lord_pk'], $params['type']);
    $list_num = RAID_LIST_PAGE_NUM; // 한페이지 당 5개

    $total_page = (INT)($total_count / $list_num);
    $total_page += ($total_count % $list_num > 0)? 1 : 0;

    $page_num = $params['page_num'];

    if ($page_num < 1) {
        $page_num = 1;
    } else if ($page_num > $total_page) {
        $page_num = $total_page;
    }

    $order = $params['order_by'];

    $order_type = strtolower($params['order_type']);
    $order_type = ($order_type == 'asc') ? 'ASC' : 'DESC';

    if ($page_num < 1) {
        $page_num = 1;
        $total_page = 1;
    }

    $list = $Troop->getRaidList($Session->lord['lord_pk'], $page_num, $order, $order_type, $list_num, $params['type']);

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'curr_page' => $page_num, 'total_page' => $total_page, 'order_by' => $order, 'order_type' => $order_type, 'list' => $list]);
}));

$app->post('/api/troop/getRaidRanking', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $list = $Troop->getRaidRanking($params['raid_troo_pk']);

    return $Render->nsXhrReturn('success', null, $list);
}));

$app->post('/api/troop/raidRequest', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $ret = $Troop->setRaidRequest($params['raid_troo_pk'], $Session->lord['lord_pk'], $params['to_lord_pk']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/troop/raidRequestItem', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $ret = $Troop->getRaidRequestItem($params['raid_troo_pk'], $params['from_lord_pk'], $Session->lord['lord_pk']);
    if (!$ret) {
        global $NsGlobal;
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));
