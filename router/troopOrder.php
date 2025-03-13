<?php
global $app, $Render, $i18n;

$app->post('/api/troopOrder', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['src_posi_pk', 'dst_posi_pk', 'cmd_type', 'captain_hero_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);

    $Item = new Item($Session, $PgGame);
    $Army = new Army($Session, $PgGame, $Resource, $GoldPop);
    $Hero = new Hero($Session, $PgGame);
    $Troop = new Troop($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['army', 'item']);

    // 군사령부 레벨에 따른 동시 출정 수 제한
    $PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$params['src_posi_pk'], PK_BUILDING_MILITARY]);
    $level = ($PgGame->getNumRows() != 1) ? 0 : $PgGame->fetchOne();

    if (!$level) {
        throw new ErrorHandler('error', $i18n->t('msg_going_to_war_require_building')); // 부대 출병은 군사령부 건설 후 가능합니다.
    }

    if ($Troop->getMyTroopsCnt($params['src_posi_pk']) >= $level) {
        throw new ErrorHandler('error', $i18n->t('msg_going_to_war_limit_troop')); // 동시 출정(주둔 포함) 부대 수는 군사령부 레벨까지만 가능 합니다.
    }

    $status = 'M';
    $cmd_type = $params['cmd_type'];

    // src / dst 기초 정보
    if (! isset($params['raid_troo_pk'])) {
        $dst_posi = $Troop->getPositionRelation($params['dst_posi_pk']);
    } else {
        // TODO 섬멸전 사용안함.
        // 섬멸전 공격 가능한지 체크
        $result = $Troop->checkRaidNpcTroop($params['raid_troo_pk'], $Session->lord['lord_pk']);
        if (!$result) {
            throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
        }

        // 섬멸전의 경우
        $raid_troop = $Troop->getRaidNpcTroop($params['raid_troo_pk']);

        $dst_posi = [];
        $dst_posi['lord_pk'] = NPC_TROOP_LORD_PK;
        $dst_posi['alli_pk'] = null;
        $dst_posi['name'] = '황건적 요새';
        $dst_posi['lord_name'] = '황건적';
        $dst_posi['lord_level'] = null;
        $dst_posi['lord_position_cnt'] = null;
        $dst_posi['lord_name_withLevel'] = '황건적';
        $dst_posi['type'] = 'S';
        $dst_posi['level'] = $raid_troop['target_level'];
        $dst_posi['relation'] = 'NPC';
        $dst_posi['my_camp_troop'] = 'N';
        $dst_posi['truce'] = 'N';
        $dst_posi['truce_type'] = '';
        $dst_posi['my_troo_pk'] = null;
        $dst_posi['power'] = null;
    }

    // 요충지 TODO 차후 요충지 업데이트시 확인 필요
    if ($dst_posi['type'] == 'P') {
        if (CONF_POINT_BATTLE_ALWAYS_POSSIBLE != 'Y') { // 요일 제한
            if (date('N') == 2 || date('N') == 3) { // 화요일과 수요일은 전투 안됨
                throw new ErrorHandler('error', '전투가 불가능한 요일 입니다.<br /><br />전투 가능 시간<br />(매주 목요일 10시00분00초 ~ 다음주 월요일 23시59분59초)');
            }
        }

        if ($cmd_type == 'T' || $cmd_type == 'P') {
            throw new ErrorHandler('error', '요충지는 수송과 보급이 불가능 합니다.');
        }

        if ($dst_posi['lord_pk'] != $Session->lord['lord_pk'] && $cmd_type == 'R') {
            throw new ErrorHandler('error', '요충지로 지원은 본인만 가능합니다.');
        }

        if ($dst_posi['lord_pk'] == $Session->lord['lord_pk'] && ($cmd_type == 'A' || $cmd_type == 'S')) {
            throw new ErrorHandler('error', '본인의 영지로 공격/정찰은 불가 합니다.');
        }
    } else if ($dst_posi['type'] == 'S') {
        if ($cmd_type != 'A') {
            throw new ErrorHandler('error', '황건적 요새는 공격만 가능합니다.');
        }
    }

    // 보호모드
    $PgGame->query('SELECT status_truce, truce_type FROM territory WHERE posi_pk = $1', [$params['src_posi_pk']]);
    $PgGame->fetch();
    $truce_info = $PgGame->row;

    // 방어측 보호 모드 확인
    if ($cmd_type == 'S' || $cmd_type == 'A') {
        if ($dst_posi['truce'] == 'Y' && $dst_posi['type'] != 'P') {
            throw new ErrorHandler('error', $i18n->t('msg_going_to_war_peace_mode')); // 상대방이 보호 모드 중에는 작전명령을 수행 할 수 없습니다.
        }
    }

    // 명령 가능여부 검사 (truce 체크 포함)
    $cmd_type_possible = false;
    $clear_protect_mode = false;

    $cmd_type_fulltext = '';
    if ($cmd_type == 'T') {	// 수송
        $cmd_type_fulltext = 'Trans';
        // 내 영지
        if ($dst_posi['type'] == 'T' && $dst_posi['relation'] == 'MIME') {
            $cmd_type_possible = true;
        }

        //다른 영지
        if ($dst_posi['type'] == 'T' && $dst_posi['relation'] != 'NPC' && $dst_posi['relation'] != 'MIME') {
            $cmd_type_possible = true;
        }

        if ($dst_posi['type'] == 'P') {
            $cmd_type_possible = false;
        }
    } else if ($cmd_type == 'P') {	// 보급
        $cmd_type_fulltext = 'Preva';
        // 내 주둔군
        if ($dst_posi['my_camp_troop'] =='Y') {
            $cmd_type_possible = true;
        }

        // 동맹 영지
        if ($dst_posi['type'] == 'T' && $dst_posi['relation'] == 'ALLY') {
            $cmd_type_possible = true;
        }

        if ($dst_posi['type'] == 'P') {
            $cmd_type_possible = false;
        }

        $ret = $Troop->checkPrevalence($params['dst_posi_pk'] ,$params['reso_food']);
        if (!$ret) {
            throw new ErrorHandler('error', 'Error Occurred. [38002]'); // 외부 주둔 부대는 1년 이상 주둔할 수 없습니다.
        }
    } else if ($cmd_type == 'R') {	// 지원
        $cmd_type_fulltext = 'Reinf';
        // 내 영지/자원지
        if ($dst_posi['relation'] == 'MIME') {
            $cmd_type_possible = true;
        }

        // 다른 영지
        if ($dst_posi['type'] == 'T' && $dst_posi['relation'] != 'NPC' && $dst_posi['relation'] != 'MIME') {
            $cmd_type_possible = true;
        }

    } else if ($cmd_type == 'S') {	// 정찰
        $cmd_type_fulltext = 'Scout';
        // NPC/LORD/ALLY_H , 보호모드 제외
        if ($dst_posi['truce'] == 'N' && ($dst_posi['relation'] == 'NPC' || $dst_posi['relation'] == 'LORD' || $dst_posi['relation'] == 'ALLY_H')) {
            $cmd_type_possible = true;
        }

        if ($dst_posi['truce'] == 'N' && ($dst_posi['relation'] == 'NPC' || $dst_posi['relation'] == 'LORD' || $dst_posi['relation'] == 'ALLY_H')) {
            $cmd_type_possible = true;
        }

        // TODO 요충지 업데이트시 확인
        if ($dst_posi['type'] == 'P') {
            $PgGame->query('SELECT ((self_recall_dt + interval \'' . POSITION_POINT_ATTACK_LIMIT .' hours\') - now()) FROM ranking_point WHERE posi_pk = $1 AND lord_pk = $2', [$params['dst_posi_pk'], $Session->lord['lord_pk']]);
            $left_dt = $PgGame->fetchOne();
            if ($left_dt > 0) {
                $left_dt = substr($left_dt, 0, 8);
                throw new ErrorHandler('error', '요충지를 포기한 경우는<br /><br />'.POSITION_POINT_ATTACK_LIMIT.'시간 경과 후 다시 공격/정찰 할 수 있습니다.<br /><br />(남은 시간 : '.$left_dt.')');
            }
            $cmd_type_possible = true;
        }
    } else if ($cmd_type == 'A') {	// 공격
        $cmd_type_fulltext = 'Attac';
        // NPC/LORD/ALLY_H , 보호모드 제외
        // 1. 타 군주에 대한 공격
        if ($truce_info['status_truce'] == 'Y' && $dst_posi['relation'] != 'NPC') {
            /*if ($truce_info['truce_type'] == 'M') {
                throw new ErrorHandler('error', '이동 후 보호 모드 중에는<br />'.intval($_M['ITEM']['500108']['buff_time'] / 3600).'시간 동안 작전명령을 수행 할 수 없습니다.');
            } else if ($truce_info['truce_type'] == 'B') {
                throw new ErrorHandler('error', '초보자 보호 모드 중에는 작전명령을 수행 할 수 없습니다.');
            } else if ($truce_info['truce_type'] == 'D') {
                throw new ErrorHandler('error', '개척 후 보호 모드 중에는<br/>'.intval($_M['ITEM']['500106']['buff_time'] / 3600).'시간 동안 작전명령을 수행 할 수 없습니다.');
            } else if ($truce_info['truce_type'] == 'I') {
                throw new ErrorHandler('error', '평화 서약 아이템 사용 중에는<br/>'.intval($_M['ITEM']['500015']['buff_time'] / 3600).'시간 동안 작전명령을 수행 할 수 없습니다.');
            } else {
                throw new ErrorHandler('error', '보호 모드 중에는 작전명령을 수행 할 수 없습니다.');
            }*/
            $clear_protect_mode = true;
        }

        // 2. NPC에 대한 공격
        /*if (($truce_info['status_truce'] == 'Y' || $truce_info['status_truce'] == 'M') && $dst_posi['relation'] == 'NPC') {
            throw new ErrorHandler('error', '이동 후 보호 모드 중에는<br />'.intval($_M['ITEM']['500108']['buff_time'] / 3600).'시간 동안 작전명령을 수행 할 수 없습니다.');
        }*/

        if ($dst_posi['truce'] == 'N' && ($dst_posi['relation'] == 'NPC' || $dst_posi['relation'] == 'LORD' || $dst_posi['relation'] == 'ALLY_H')) {
            $cmd_type_possible = true;
        }

        // TODO 요충지 업데이트시 확인
        if ($dst_posi['type'] == 'P') {
            $PgGame->query('SELECT ((self_recall_dt + interval \'' . POSITION_POINT_ATTACK_LIMIT .' hours\') - now()) FROM ranking_point WHERE posi_pk = $1 AND lord_pk = $2 AND self_recall_dt < (self_recall_dt + interval \'' . POSITION_POINT_ATTACK_LIMIT .' hours\')', [$params['dst_posi_pk'], $Session->lord['lord_pk']]);
            $left_dt = $PgGame->fetchOne();
            if ($left_dt > 0) {
                $left_dt = substr($left_dt, 0, 8);
                throw new ErrorHandler('error', '요충지를 포기한 경우는<br /><br />'.POSITION_POINT_ATTACK_LIMIT.'시간 경과 후 다시 공격/정찰 할 수 있습니다.<br /><br />(남은 시간 : '.$left_dt.')');
            }
            $cmd_type_possible = true;
        }
    }

    if (! $cmd_type_possible) {
        throw new ErrorHandler('error', $i18n->t('msg_unable_execute_command')); // 작전명령을 수행 할 수 없습니다.
    }

    // 보호모드 즉시 완료 처리
    if ($clear_protect_mode) {
        $Territory = new Territory($Session, $PgGame);
        $Territory->finishTimer($params['src_posi_pk']);
        $Territory->clearTruceStatus($params['src_posi_pk']);
        $PgGame->query('UPDATE lord SET truce_up_dt = null WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
        $Session->sqAppend('PUSH', ['TRUCE_UPDATE' => ['status' => 'F']], null, $Session->lord['lord_pk'], $Session->lord['main_posi_pk']);
    }

//////////////////////////////////////////////////

    $base = [];

    $base['src_lord_pk'] = $Session->lord['lord_pk'];
    $base['dst_lord_pk'] = $dst_posi['lord_pk'];
    $base['src_posi_pk'] = $params['src_posi_pk'];
    $base['dst_posi_pk'] = $params['dst_posi_pk'];
    $base['src_alli_pk'] = $Session->lord['alli_pk'];
    $base['dst_alli_pk'] = $dst_posi['alli_pk'];
    $base['dst_type'] = $dst_posi['type'];

    $base['from_position'] = $Troop->getPositionName($base['src_posi_pk']);

    if ($dst_posi['type'] == 'S') {
        $base['to_position'] = 'Lv.'.$dst_posi['level'].' 황건적 요새';
    } else {
        $base['to_position'] = $Troop->getPositionName($base['dst_posi_pk'], $dst_posi);
    }

    // 환산 병력에 의한 부대의 인구수 및 시간당 식량 소모량 등
    $army_arr = [];
    foreach ($_M['ARMY_C'] AS $k => $v) {
        $army_arr[$k] = $params['army_'. $k];
    }

    // need_population, need_food(hour_food), capacity, troop_speed
    $armyPop = $Troop->getArmyPop($army_arr, $base['src_posi_pk']);

    // 요충지 병력 제한
    if ($armyPop['population'] > TROOP_ARMY_LIMIT) { // TROOP_ARMY_LIMIT = 30만
        throw new ErrorHandler('error', $i18n->t('msg_going_to_war_limit_army')); // 출정 시 최대 30만까지 출정이 가능 합니다.
    }
    $base['hour_food'] = $armyPop['need_food'];
    $params['reso_food'] = intval($params['reso_food']);
    if ($armyPop['capacity'] < ($params['reso_food'] + $params['reso_horse'] + $params['reso_lumber'] + $params['reso_iron'] + $params['reso_gold'])) {
        throw new ErrorHandler('error', $i18n->t('msg_troop_limit_resource')); // 부대의 최대 수송력이 부족합니다.
    }

    // 거리 계산
    if ($dst_posi['type'] == 'S') {
        $base['distance'] = 2; // 섬멸전은 무조건 2리
    } else {
        $base['distance'] = $Troop->getDistance($base['src_posi_pk'], $base['dst_posi_pk']);
    }

    // 부대의 이동시간
    $base['triptime'] = intval($Troop->getMoveTime($base['src_posi_pk'], $cmd_type, $base['distance'], $armyPop, $params['captain_hero_pk'], $params['select_item_pk'], $dst_posi['alli_status'] ?? false)); // 편도 이동시간
    $base['camptime'] = 0;
    if (isset($params['camp_time_h']) || isset($params['camp_time_m'])) {
        $base['camptime'] = $params['camp_time_h'] * 3600 + $params['camp_time_m'] * 60;
    }

    // 식량 정보
    list($base['round_food'], $base['presence_food']) = $Troop->getNeedFood($cmd_type, $base['triptime'], $base['camptime'], $base['hour_food'], $army_arr, $dst_posi);
    $base['round_food'] = intval($base['round_food']);
    $base['round_gold'] = 0;
    $base['presence_food'] = intval($base['presence_food']);

    if ($dst_posi['type'] == 'S') {
        // 섬멸전 이라면 필요 식량이 0
        $base['round_food'] = 0;
        $base['presence_food'] = 0;
    }

    // 부대사기
    $base['fighting_spirit'] = 0; // ?
    $base['troop_type'] = 'U'; // 유저
    $base['troop_desc'] = $i18n->t('troop_title_description', [$base['from_position'], $_M['CODESET']['TROOP_CMD_TYPE'][$cmd_type]]); // {{1}} 영지의 {{2}} 부대
    $base['troop_quest_npc_attack'] = 0;

    $move_time = $base['triptime']+$base['camptime'];

    global $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        // 아이템 소모
        if ($params['select_item_pk']) {
            $base['use_item_pk'] = $params['select_item_pk'];

            $ret = $Item->useItem($base['src_posi_pk'], $base['src_lord_pk'], $base['use_item_pk'], 1, ['_yn_quest' => true]);
            if(!$ret) {
                throw new Exception($NsGlobal->getErrorMessage() ?? 'Error Occurred. [38003]'); // 아이템 사용실패
            }
        } else {
            $base['use_item_pk'] = null;
        }

        // 병력 차감
        if (! $Army->useArmy($base['src_posi_pk'], $army_arr)) {
            throw new Exception('Error Occurred. [38004]'); // 병력 차출중 오류 발생
        }
        $Army->get($base['src_posi_pk']);

        // 영웅 명령 배속
        $hero = [];

        if (isset($params['captain_hero_pk'])) {
            $ret = $Hero->setCommand($base['src_posi_pk'], $params['captain_hero_pk'], 'C', $cmd_type_fulltext);
            if (!$ret) {
                throw new Exception($NsGlobal->getErrorMessage());
            }
            $hero['captain_hero_pk'] = $params['captain_hero_pk'];
            $hero['captain_desc'] = $Troop->getHeroDesc($params['captain_hero_pk']);
        }

        if (isset($params['director_hero_pk'])) {
            $ret = $Hero->setCommand($base['src_posi_pk'], $params['director_hero_pk'], 'C', $cmd_type_fulltext);
            if (!$ret) {
                throw new Exception($NsGlobal->getErrorMessage());
            }
            $hero['director_hero_pk'] = $params['director_hero_pk'];
            $hero['director_desc'] = $Troop->getHeroDesc($params['director_hero_pk']);
        } else {
            $hero['director_hero_pk'] = null;
            $hero['director_desc'] = null;
        }

        if (isset($params['staff_hero_pk'])) {
            $ret = $Hero->setCommand($base['src_posi_pk'], $params['staff_hero_pk'], 'C', $cmd_type_fulltext);
            if (!$ret) {
                throw new Exception($NsGlobal->getErrorMessage());
            }
            $hero['staff_hero_pk'] = $params['staff_hero_pk'];
            $hero['staff_desc'] = $Troop->getHeroDesc($params['staff_hero_pk']);
        } else {
            $hero['staff_hero_pk'] = null;
            $hero['staff_desc'] = null;
        }

        // 자원 차감
        $reso = [];
        $reso['gold'] = $params['reso_gold'];
        $reso['food'] = $params['reso_food'];
        $reso['horse'] = $params['reso_horse'];
        $reso['lumber'] = $params['reso_lumber'];
        $reso['iron'] = $params['reso_iron'];

        // 황금
        if ($reso['gold']) {
            $r = $GoldPop->decreaseGold($base['src_posi_pk'], $reso['gold'], null, 'troop_order');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_gold_lack')); // 금이 부족합니다
            }
        }

        // 자원
        if ($base['round_food'] || $base['presence_food'] || $reso['food'] || $reso['horse'] || $reso['lumber'] || $reso['iron']) {
            $food_backup = $reso['food']; // 전체 food 계산을 위해 순수 수송량 저장
            $reso['food'] += $base['round_food'] + $base['presence_food'];
            $r = $Resource->decrease($base['src_posi_pk'], $reso, null, 'troop_order');
            if (!$r) {
                throw new Exception($i18n->t('msg_resource_lack')); // 자원이 부족합니다
            }
            $reso['food'] = $food_backup; // 순수 수송량 복구
        }

        // 부대 등록
        $raid_troo_pk = 0;
        if (isset($params['raid_troo_pk'])) {
            $raid_troo_pk = $params['raid_troo_pk'];
        }

        $troop_info = $Troop->marchTroop($status, $cmd_type, $base, $hero, $reso, $army_arr, $move_time, $raid_troo_pk);
        if (! $troop_info) {
            throw new Exception('Error Occurred. [38005]'); // 부대 등록에 실패
        }

        $Army->calcArmyPoint();

        // 타이머 등록 및 갱신
        if ($cmd_type == 'A') {
            $Session->sqAppend('PUSH', ['PLAY_SOUND' => 'move'], null, $Session->lord['lord_pk']);
        }

        $timePks = $Troop->setTimer($troop_info['troo_pk'], $cmd_type, $base['src_lord_pk'], $base['dst_lord_pk'], $base['src_posi_pk'], $base['dst_posi_pk'], ['status' => $status, 'cmd_type' => $cmd_type, 'from_position' => $base['from_position'], 'to_position' => $base['to_position'], 'use_item' => $base['use_item_pk'], 'dst_type' => $base['dst_type']], $move_time, ['hero' => $hero, 'army' => $army_arr]);
        $Troop->setTimePk($troop_info['troo_pk'], $timePks['src_time_pk'], $timePks['dst_time_pk']);

        // $Session->sqAppend('MOVE_TROOP', $Troop->getTroop($troop_info['troo_pk']), null, $base['src_lord_pk'], $base['src_posi_pk']);

        // $Troop->setTroopMove($troop_info['troo_pk']);
        $Troop->getMoveTroop($troop_info['troo_pk']);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        $Army->get($base['src_posi_pk']);
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    // Log
    $Log->setTroop($base['src_lord_pk'], $base['src_posi_pk'], $status.'_'.$cmd_type, $base['dst_lord_pk'], null, $base['dst_posi_pk'], $base['to_position'], json_encode($hero), json_encode($army_arr), json_encode($reso), $troop_info['troo_pk']);

    return $Render->nsXhrReturn('success', null, ['move_time' => $move_time, 'alli' => $dst_posi['alli_status'] ?? false]);
}));

$app->post('/api/troopOrder/dstPosition', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $dst_posi = $Troop->getPositionRelation($params['dst_posi_x']. 'x'. $params['dst_posi_y']);

    return $Render->nsXhrReturn('success', null, $dst_posi);
}));

/* TODO 사용하지 않는 API
$app->post('/api/troopOrder/info/save', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');


    // 명령서 제목 체크
    if (!isset($params['troop_order_info_title']) || iconv_strlen($params['troop_order_info_title'], 'UTF-8') < 1) {
        throw new ErrorHandler('error', '작전 명령서 제목을 입력해주세요.');
    } else if (preg_match("/[^\x{AC00}-\x{D7A3}0-9a-zA-Z]/u", $params['troop_order_info_title'])) {
        throw new ErrorHandler('error', '작전 명령서 제목에 사용할 수 없는 글자가 있습니다.');
    } else if (iconv_strlen($params['troop_order_info_title'], 'UTF-8') > 10) {
        throw new ErrorHandler('error', '작전 명령서 제목은 최대 10자까지 입력할 수 있습니다.');
    }

    $r = [];
    // 자원값 체크
    $reso_type_array = ["food", "horse", "lumber", "iron", "gold"];
    foreach($reso_type_array as $v){
        $key = "reso_".$v;
        if (!in_array($key, $params)) {
            // POST로 보내진 정보 중에 해당하는 자원이 있으면
            if (preg_match("/[^0-9]/", $params[$key]) > 0) {
                throw new ErrorHandler('error', '입력 정보가 올바르지 않습니다.');
            } else if ($params[$key] > 0) {
                $r[$key] = $params[$key];
            }
        }
    }

    // 병력 체크
    global $NsGlobal, $_M_ARMY_C;
    $NsGlobal->requireMasterData(['army']);

    foreach($_M_ARMY_C as $k => $v) {
        $key = "army_".$k;
        if (!in_array($key, $params)) {
            if (preg_match("/[^0-9]/", $params[$key]) > 0) {
                throw new ErrorHandler('error', '입력 정보가 올바르지 않습니다.');
            } else if ($params[$key] > 0) {
                $r[$key] = $params[$key];
            }
        }
    }

    $result = $PgGame->query('INSERT INTO troop_order(lord_pk, troop_order_title, troop_order_sdata, troop_order_up_dt) VALUES ($1, $2, $3, now())', [$Session->lord['lord_pk'], $params['troop_order_info_title'], serialize($r)]);
    if (!$result) {
        throw new ErrorHandler('error', '작전명령서 저장 도중에 오류가 발생했습니다.');
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/troopOrder/info/load', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT troop_order_pk, troop_order_title, troop_order_sdata, date_part(\'epoch\', troop_order_up_dt)::integer FROM troop_order WHERE lord_pk = $1 ORDER BY troop_order_up_dt DESC', [$Session->lord['lord_pk']]);
    $r = [];
    if ($PgGame->fetchAll() > 0) {
        $r = $PgGame->rows;
        foreach($r as &$v) {
            $v['troop_order_sdata'] = unserialize($v['troop_order_sdata']);
        }
    }
    return $Render->nsXhrReturn('success', null, $r);
}));

$app->post('/api/troopOrder/info/delete', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    if (!isset($params['troop_order_pk']) || preg_match("/[^0-9]/", $params['troop_order_pk']) > 0) {
        throw new ErrorHandler('error', '입력 정보가 올바르지 않습니다.');
    }

    $result = $PgGame->query('DELETE FROM troop_order WHERE lord_pk = $1 AND troop_order_pk = $2', [$Session->lord['lord_pk'], $params['troop_order_pk']]);
    if (!$result) {
        throw new ErrorHandler('error', '작전 명령서 삭제 도중에 에러가 발생했습니다.');
    }

    return $Render->nsXhrReturn('success');
}));*/

$app->post('/api/troopOrder/info/effectTypes', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['captain_hero_pk']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
    $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);

    $lead_population = $params['lead_population'];
    $capacities = $Effect->getHeroCapacityEffects($params['captain_hero_pk']);
    $applies = $Effect->getHeroAppliedCommandEffects(PK_CMD_TROOP_CAPTAIN, $capacities);
    $ret = $Effect->getEffectedValue($params['posi_pk'], ['troop_leadership_increase'], $lead_population, $applies['all']);
    $lead_population = $ret['value'];

    return $Render->nsXhrReturn('success', null, $lead_population);
}));


$app->post('/api/troopOrder/preset', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT slot_number, order_data, preset_title FROM troop_preset WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $PgGame->fetchAll();
    foreach ($PgGame->rows as &$row) {
        $row['order_data'] = json_decode($row['order_data'], true);
    }
    $rows = $PgGame->rows;
    $Session->sqAppend('PUSH', ['PRESET' => $rows], null, $Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success');
}));


$app->post('/api/troopOrder/presetSave', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('INSERT INTO troop_preset (lord_pk, slot_number, order_data, preset_title) VALUES ($1, $2, $3, $4) ON CONFLICT (lord_pk, slot_number) DO UPDATE SET order_data = $3, preset_title = $4, create_dt = now()', [$Session->lord['lord_pk'], $params['slot_number'], $params['order_data'], $params['preset_title']]);

    $PgGame->query('SELECT slot_number, order_data, preset_title FROM troop_preset WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $PgGame->fetchAll();
    foreach ($PgGame->rows as &$row) {
        $row['order_data'] = json_decode($row['order_data'], true);
    }
    $rows = $PgGame->rows;
    $Session->sqAppend('PUSH', ['PRESET' => $rows], null, $Session->lord['lord_pk']);

    return $Render->nsXhrReturn('success');
}));






















