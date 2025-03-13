<?php
global $app, $Render, $i18n;

// C
$app->get('/dispatcher/construct', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */

    global $NsGlobal, $_M;
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $BuildConstruction = new BuildConstruction($Session, $PgGame);
    $BuildConstruction->classHero();

    $NsGlobal->requireMasterData(['building', 'package']);

    $result_construct = $BuildConstruction->finish($result_timer['queue_pk']);
    if (! $result_construct) {
        // TODO 오류 로그
        return $Render->view();
    }
    if ($result_construct['position_type'] == 'I') {
        $Bd_c = new Bdic($Session, $PgGame, $Resource, $GoldPop);
        $PgGame->query('SELECT m_buil_pk, in_castle_pk, status, level FROM building_in_castle WHERE posi_pk = $1 AND in_castle_pk = $2', [$t['posi_pk'], $result_construct['position']]);
    } else {
        $Bd_c = new Bdoc($Session, $PgGame, $Resource, $GoldPop);
        $PgGame->query('SELECT m_buil_pk, out_castle_pk, status, level FROM building_out_castle WHERE posi_pk = $1 AND out_castle_pk = $2', [$t['posi_pk'], $result_construct['position']]);
    }
    $PgGame->fetch();
    $row = $PgGame->row;
    $m_buil_pk = $row['m_buil_pk'];
    $castle_pk = ($result_construct['position_type'] == 'I') ? $row['in_castle_pk'] : $row['out_castle_pk'];
    $next_level = $row['level'] + 1;


    // 수치 변경전 저장하기 - 자원/인구/황금
    if (in_array($m_buil_pk, [PK_BUILDING_STORAGE, PK_BUILDING_FOOD, PK_BUILDING_HORSE, PK_BUILDING_LUMBER])) {
        // 창고 , 자원 - 창고가 있는 이유는 자원이 창고의 저장공간을 넘을 수 있어서...
        $Resource->save($t['posi_pk']);
    } else if ($m_buil_pk == PK_BUILDING_COTTAGE) {
        // 민가
        $GoldPop->save($t['posi_pk']);
    } else if ($m_buil_pk == PK_BUILDING_TECHNIQUE) {
        $Technique = new Technique($Session, $PgGame);
        $Technique->updateTerritoryTechnique($Session->lord['lord_pk'], $t['posi_pk'], $next_level);
    }

    // 영향력 변동
    $Lord = new Lord($Session, $PgGame);
    if ($result_timer['queue_action'] == 'U') {
        $ret = $Bd_c->upgradePost($t['posi_pk'], $result_construct['position'], $result_timer['queue_pk'], $params['time_pk']);
        if ($ret) {
            $Lord->increasePower($Session->lord['lord_pk'], $_M['BUIL'][$m_buil_pk]['level'][$next_level]['increase_power'], $t['posi_pk']);
        }
    } else if ($result_timer['queue_action'] == 'D') {
        $ret = $Bd_c->demolishPost($t['posi_pk'], $result_construct['position'], false, $result_timer['queue_pk'], $params['time_pk']);
        if ($ret) {
            $Lord->decreasePower($Session->lord['lord_pk'], $_M['BUIL'][$m_buil_pk]['level'][$row['level']]['increase_power'], $t['posi_pk']);
        }
    }

    if ($m_buil_pk) {
        // 건물 효과
        $update_type = $_M['BUIL'][$m_buil_pk]['update_type'];
        if ($update_type != 'NULL') {
            $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
            $FigureReCalc->dispatcher($t['posi_pk'], $update_type, ['in_castle_pk' => $row['in_castle_pk'] ?? NULL, 'status' => $row['status'], 'level' => $row['level']]);
        }
    }

    //퀘스트 체크
    if ($result_timer['queue_action'] == 'U') {
        $Quest = new Quest($Session, $PgGame);
        $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'buil_upgrade','m_buil_pk' => $m_buil_pk, 'level' => $row['level'], 'position_type' => $result_construct['position_type'], 'posi_pk' => $t['posi_pk']]);
        if (in_array($m_buil_pk, [PK_BUILDING_FOOD, PK_BUILDING_HORSE, PK_BUILDING_LUMBER])) {
            $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'territory', 'posi_pk' => $t['posi_pk']]);
        }
    }

    // TODO 로그 안남기나?
    // $Log = new Log($Session, $PgGame);

    // 액션
    $Session->sqAppend('PUSH', ['PLAY_SOUND' => 'construct_complete'], null, $Session->lord['lord_pk'], $t['posi_pk']);
    $Session->sqAppend('QUEUE', [$result_timer['queue_pk'] => null], null, $Session->lord['lord_pk'], $t['posi_pk']);
    $Session->sqAppend('PUSH', [
        'TOAST' => [
            'type' => 'construction',
            'castle_type' => $result_construct['position_type'],
            'castle_pk' => $castle_pk,
            'pk' => $m_buil_pk
        ],
        'PLAY_EFFECT' => [
            'type' => 'construction',
            'castle_type' => $result_construct['position_type'],
            'castle_pk' => $castle_pk,
        ]
    ], null, $Session->lord['lord_pk'], $t['posi_pk']);

    // 패키지 구매 창
    $Lord->checkPackage($Session->lord['lord_pk'], 'construction', $m_buil_pk, $next_level);

    return $Render->view('[OK]');
}));

// T
$app->get('/dispatcher/technique', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['technique']);

    $Hero = new Hero($Session, $PgGame);
    $BuildTechnique = new BuildTechnique($Session, $PgGame, $Hero);

    $result_array = $BuildTechnique->finish($result_timer['queue_pk']);

    $code = $_M['TECH'][$result_array['m_tech_pk']]['code'];

    $Technique = new Technique($Session, $PgGame);
    $Technique->upgradePost($t['posi_pk'], $code, $result_timer['queue_pk'], $params['time_pk']);

    // 군주 태학 기술 레벨
    $Technique->updateLordTechnique($Session->lord['lord_pk'], $t['posi_pk'], $code);

    // TODO - 체크 - 수치 변경전 저장하기 - 자원/인구/황금

    // notification
    $Technique->get($t['posi_pk'], [$code]);

    $PgGame->query('SELECT '. $code. ' FROM technique WHERE posi_pk = $1', [$t['posi_pk']]);
    $level = $PgGame->fetchOne();

    //퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'devel_tech','m_tech_pk' => $result_array['m_tech_pk'], 'level' => $level]);

    // PN
    /*$info = $_M['TECH'][$result_array['m_tech_pk']]['title'];
    $info .= ' Lv.'. $level;

    $Push->send('tech', $info, $Session->lord['lord_pk'], $t['posi_pk']);*/

    // TODO 로그 안남기나?
    // $Log = new Log($Session, $PgGame);

    // 액션
    $Session->sqAppend('PUSH', ['PLAY_SOUND' => 'research_complete'], null, $Session->lord['lord_pk'], $t['posi_pk']);
    $Session->sqAppend('QUEUE', [$result_timer['queue_pk'] => null], null, $Session->lord['lord_pk'], $t['posi_pk']);
    $Session->sqAppend('PUSH', ['TOAST' => [
        'type' => 'technique',
        'castle_type' => 'I',
        'castle_pk' => $t['in_cast_pk'],
        'm_buil_pk' => PK_BUILDING_TECHNIQUE,
        'pk' => $result_array['m_tech_pk'],
        'level' => $level
    ]], null, $Session->lord['lord_pk'], $t['posi_pk']);

    return $Render->view('[OK]');
}));

// A
$app->get('/dispatcher/army', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['army']);

    $BuildArmy = new BuildArmy($Session, $PgGame, $Timer);
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Army = new Army($Session, $PgGame, $Resource, $GoldPop);

    $result_array = $BuildArmy->finish($result_timer['queue_pk']);

    $code = $_M['ARMY'][$result_array['m_army_pk']]['code'];

    $ret = $Army->upgradePost($t['posi_pk'], $code, $result_array['build_number'], $result_timer['queue_pk'], $params['time_pk']);

    // 수치 변경전 저장하기 - 자원/인구/황금
    // notification
    $Army->get($t['posi_pk'], [$code]);

    // 큐처리
    $BuildArmy->queue($result_array['buil_pk'], $t['posi_pk']);

    $Session->sqAppend('BUIL_IN_CAST', [$t['in_cast_pk'] => ['current' => $BuildArmy->getCurrent($t['posi_pk'], $t['in_cast_pk'])]], null, $Session->lord['lord_pk'], $t['posi_pk']);

    // 퀘스트 처리
    if ($ret) {
        $Quest = new Quest($Session, $PgGame);
        $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'army_recruit', 'army_code' => $code, 'posi_pk' => $t['posi_pk']]);
        $Quest->countCheckQuest($Session->lord['lord_pk'], 'EVENT_TRAINING', ['value' => $result_array['build_number']]);
    }

    // PN - TODO Push 구현 후 확인 필요.
    /*$info = $_M['ARMY'][$result_array['m_army_pk']]['title'];
    $info .= '('. $result_array['build_number']. ')';
    $Push->send('army', $info, $Session->lord['lord_pk'], $t['posi_pk']);*/

    // 액션
    $Session->sqAppend('PUSH', ['PLAY_SOUND' => 'training_complete'], null, $Session->lord['lord_pk'], $t['posi_pk']);
    $Session->sqAppend('QUEUE', [$result_timer['queue_pk'] => null], null, $Session->lord['lord_pk'], $t['posi_pk']);
    $Session->sqAppend('PUSH', ['TOAST' => [
        'type' => 'army',
        'castle_type' => 'I',
        'castle_pk' => $t['in_cast_pk'],
        'm_buil_pk' => PK_BUILDING_ARMY,
        'pk' => $result_array['m_army_pk']
    ]], null, $Session->lord['lord_pk'], $t['posi_pk']);

    // 세션에 있는 채널 정보가 필요함.
    $Log = new Log($Session, $PgGame);

    return $Render->view('[OK]');
}));

// F
$app->get('/dispatcher/fortification', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['fortification']);

    $BuildFortification = new BuildFortification($Session, $PgGame, $Timer);
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Terr = new Territory($Session, $PgGame);
    $Fortification = new Fortification($Session, $PgGame, $Resource, $GoldPop, $Terr);

    $result_array = $BuildFortification->finish($result_timer['queue_pk']);

    $code = $_M['FORT'][$result_array['m_fort_pk']]['code'];

    $ret = $Fortification->upgradePost($t['posi_pk'], $code, $result_array['build_number'], $result_timer['queue_pk'], $params['time_pk']);

    // 수치 변경전 저장하기 - 자원/인구/황금

    // notification
    $Fortification->get($t['posi_pk'], [$code]);

    // 퀘스트 처리
    if ($ret) {
        $Quest = new Quest($Session, $PgGame);
        $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'fortification', 'fort_code' => $code, 'posi_pk' => $t['posi_pk']]);
    }

    // 액션
    $Session->sqAppend('QUEUE', [$result_timer['queue_pk'] => null], null, $Session->lord['lord_pk'], $t['posi_pk']);
    $Session->sqAppend('PUSH', ['TOAST' => [
        'type' => 'fortification',
        'castle_type' => 'I',
        'castle_pk' => 2,
        'pk' => $result_array['m_fort_pk'],
        'm_buil_pk' => PK_BUILDING_WALL
    ]], null, $Session->lord['lord_pk'], $t['posi_pk']);


    // 세션에 있는 채널 정보가 필요함.
    $Log = new Log($Session, $PgGame);

    return $Render->view('[OK]');
}));

// W
$app->get('/dispatcher/fortificationValley', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['fortification']);

    $BuildFortificationValley = new BuildFortificationValley($Session, $PgGame, $Timer);
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Terr = new Territory($Session, $PgGame);
    $FortificationValley = new FortificationValley($Session, $PgGame, $Resource, $GoldPop, $Terr);

    $result_array = $BuildFortificationValley->finish($result_timer['queue_pk']);

    $code = $_M['FORT'][$result_array['m_fort_pk']]['code'];

    $valley_posi_pkArr = explode('-', $result_timer['description']);
    $valley_posi_pk = trim($valley_posi_pkArr[0]);

    $ret = $FortificationValley->upgradePost($valley_posi_pk, $code, $result_array['build_number'], $result_timer['queue_pk'], $params['time_pk']);

    // 액션
    $Session->sqAppend('QUEUE', [$result_timer['queue_pk'] => null], null, $Session->lord['lord_pk'], $t['posi_pk']);
    $Session->sqAppend('PUSH', ['TOAST' => [
        'type' => 'fortification_valley',
        'pk' => $result_array['m_fort_pk'],
        'posi_pk' => $valley_posi_pk
    ]], null, $Session->lord['lord_pk'], $t['posi_pk']);

    // 세션에 있는 채널 정보가 필요함.
    $Log = new Log($Session, $PgGame);

    return $Render->view('[OK]');
}));

// E
$app->get('/dispatcher/encounter', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */

    $Hero = new Hero($Session, $PgGame);

    // 탐색 영웅의 PK 및 등급
    $PgGame->query('SELECT cmd_hero_pk, m_item_pk, type FROM hero_encounter WHERE hero_enco_pk = (SELECT last_hero_enco_pk FROM territory WHERE posi_pk = $1)', [$t['posi_pk']]);
    $PgGame->fetch();
    $row = $PgGame->row;
    $cmd_hero_pk = $row['cmd_hero_pk'];

    $PgGame->query('SELECT level, (charm_basic + charm_enchant + charm_plusstat + charm_skill) charm FROM hero WHERE hero_pk = $1', [$cmd_hero_pk]);
    if (! $PgGame->fetch()) {
        // 실패하는 경우?... TODO 오류 로그 남기기
    }
    $hero_level = $PgGame->row['level'];
    $hero_charm = $PgGame->row['charm'];

    // 성공 확률
    global $_M, $NsGlobal;
    $SUCCESS_RATE = $_M['ENCOUNTER_SUCCESS_RATE'][$row['type']];

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
    $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);

    $capacities = $Effect->getHeroCapacityEffects($cmd_hero_pk);
    $applies = $Effect->getHeroAppliedCommandEffects(PK_CMD_ENCOU, $capacities);
    $ret = $Effect->getEffectedValue($t['posi_pk'], ['hero_encounter_increase'], $SUCCESS_RATE, $applies['all']); // 기본 30

    $encounter_type = 'none';
    $encounter_value = 0;
    $range_prev = 1;

    $yn_item = ($row['m_item_pk'] > 0) ? 'Y' : 'N';
    $SUCCESS_RATE = ($row['m_item_pk'] > 0) ? 100 : $ret['value'];

    $range_random_key = rand(1, 100000); // 십만

    $NsGlobal->requireMasterData(['encounter_range', 'hero_encounter_hero_level']);
    if ($range_random_key <= $SUCCESS_RATE * 1000) { // 성공 확률
        $range_random_key = rand(1, 100000); // 십만
        foreach ($_M['ENCO'][$row['type']][$yn_item] as $k => $v) {
            if ($v['recalc_rate'] == 0) {
                continue;
            }
            $next = $range_prev + $v['recalc_rate'];
            if ($range_random_key >= $range_prev && $range_random_key <= $next) {
                $encounter_type = $k;
                break;
            }
            $range_prev = $next;
        }

        if ($encounter_type == 'hero') {
            // 탐색이 성공하였고 탐색 보상이 영웅일때
            // 탐색하는 영웅의 매력 수치에 따라 얻을 수 있는 영웅의 레벨을 결정

            $_apply_table = false;
            ksort($_M['HERO_ENCOUNTER_HERO_LEVEL'][$yn_item]);

            foreach($_M['HERO_ENCOUNTER_HERO_LEVEL'][$yn_item] as $charm_range => &$_table) {
                if ($hero_charm <= $charm_range) {
                    $_apply_table =& $_table;
                    break;
                }
            }

            $_apply_table = (!$_apply_table) ? $_M['HERO_ENCOUNTER_HERO_LEVEL'][$yn_item]['19'] : $_apply_table;
            if ($_apply_table !== false) {
                ksort($_apply_table);
            }

            $rand_value = rand(1, 1000000);
            $prev = 0;
            $hero_level = 1;
            foreach($_apply_table as $_level => $rate) {
                if ($rand_value <= $prev + $rate) {
                    $hero_level = $_level;
                    break;
                } else {
                    $prev = $prev + $rate;
                }
            }

            // 군주 카드를 발급하지 않기 위해 지정된 전역 변수에 군주카드의 m_hero_base_pk를 적어놓음
            global $_not_m_hero_base_list;
            $_not_m_hero_base_list = [120000,120001,120002,120003,120004];
            // 탐색 강화?
            $_acquired_type = 'ENCOUNT';
            $_create_reason = 'encount';
            if ($row['m_item_pk'] > 0) {
                if($row['m_item_pk'] == 500248) {
                    $_acquired_type = 'ENCOUNT_SPECIAL';
                    $_create_reason = 'encount_special';
                } else {
                    $_acquired_type = 'ENCOUNT_ITEM';
                    $_create_reason = 'encount_item';
                }
            }
            $encounter_value = $Hero->getNewHero($_acquired_type, $hero_level, null, null, null, null, null, $_create_reason);
        } else if ($encounter_type == 'item') {
            $m_item_pk = rand(0, COUNT($_M['ENCOUNTER_ACQUIRED_ITEM']) - 1);
            $encounter_value = $_M['ENCOUNTER_ACQUIRED_ITEM'][$m_item_pk];
        } else if ($encounter_type == 'evt1') {
            $encounter_type = 'item';
            $encounter_value = 500113;
        } else {
            if ($row['m_item_pk'] > 0) {
                $encounter_value = rand($_M['ENCOUNTER_REWARD_ITEM_VALUE'][$encounter_type]['reward_min'], $_M['ENCOUNTER_REWARD_ITEM_VALUE'][$encounter_type]['reward_unit']*$hero_level);
            } else  {
                $encounter_value = rand($_M['ENCOUNTER_REWARD_VALUE'][$encounter_type]['reward_min'], $_M['ENCOUNTER_REWARD_VALUE'][$encounter_type]['reward_unit']*$hero_level);
            }
        }
    }

    // reward 입력
    $PgGame->query('UPDATE hero_encounter SET encounter_type = $1, encounter_value = $2, status = $3 WHERE hero_enco_pk = $4 AND status = $5', [$encounter_type, $encounter_value, 'F', $result_timer['queue_pk'], 'P']);

    // 수행영웅 명령해제
    $ret = $Hero->unsetCommand($cmd_hero_pk, true, $t['build_time']);
    if (!$ret) {
        throw new Exception('기존 명령 해제 불가');
    }

    // Log
    $PgGame->query('SELECT hero_enco_pk, start_dt, cmd_hero_pk, start_dt, end_dt, encounter_type, encounter_value, invitation_cnt, yn_invited, time_pk, type, m_item_pk  
FROM hero_encounter WHERE hero_enco_pk = (SELECT last_hero_enco_pk FROM territory WHERE posi_pk = $1)', [$t['posi_pk']]);
    $PgGame->fetch();
    $row = $PgGame->row;
    if ($row['encounter_type'] == 'none') {
        // $query_params = Array($row['hero_enco_pk']);
        // $PgGame->query('DELETE FROM hero_encounter WHERE hero_enco_pk = $1', $query_params);
    }

    // 세션에 있는 채널 정보가 필요함.
    $Log = new Log($Session, $PgGame);
    $Log->setHeroEncounter($Session->lord['lord_pk'], $t['posi_pk'], 'complete', $row['hero_enco_pk'], $cmd_hero_pk, $row['start_dt'], $row['end_dt'], $row['encounter_type'], $row['encounter_value'], $row['invitation_cnt'], $row['yn_invited'], $row['time_pk'], $row['type'], $row['m_item_pk']);

    // PN TODO Push가 필요하면 처리
    // $Push->send('enco', '', $Session->lord['lord_pk'], $t['posi_pk']);

    $Quest = new Quest($Session, $PgGame);
    $Quest->countCheckQuest($Session->lord['lord_pk'], 'EVENT_HERO_ENCOUNTER', ['value' => 1]);

    // 액션
    $Session->sqAppend('PUSH', ['TOAST' => [
        'type' => 'explorer',
        'castle_type' => 'I',
        'castle_pk' => $t['in_cast_pk'],
        'm_buil_pk' => PK_BUILDING_RECEPTIONHALL,
        'reward' => [
            'type' => $encounter_type,
            'value' => $encounter_value,
        ]
    ]], null, $Session->lord['lord_pk'], $t['posi_pk']);

    // 탐색 성공 유무에 상관없이 섬멸전 체크
    // $Troop->setRaidTroop($_M['ENCOUNTER_RAID_RATE'][$row['type']], $Session->lord['lord_pk'], 1, 'encounter');

    return $Render->view('[OK]');
}));

// I
$app->get('/dispatcher/invitation', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['hero', 'hero_base']);

    $Hero = new Hero($Session, $PgGame);

    // 초빙 정보 추출
    $PgGame->query('SELECT hero_enco_pk, send_gold, cmd_hero_pk, start_dt, end_dt, result_value, result_status, time_pk, m_item_pk FROM hero_invitation WHERE hero_invi_pk = $1', [$result_timer['queue_pk']]);
    $PgGame->fetch();
    $invi_info = $PgGame->row;

    // 탐색 정보 추출
    $PgGame->query('SELECT hero_enco_pk, encounter_value, invitation_cnt FROM hero_encounter WHERE hero_enco_pk = $1', [$invi_info['hero_enco_pk']]);
    $PgGame->fetch();
    $enco_info = $PgGame->row;

    // 최소 초빙 비용
    $PgGame->query('SELECT m_hero_pk, level, rare_type FROM hero WHERE hero_pk = $1', [$enco_info['encounter_value']]);
    $PgGame->fetch();
    $find_hero_info = $PgGame->row;

    //$PASS_SCORE = 50+(($find_hero_info['level']+$find_hero_info['rare_type']-2)*5);
    //$PASS_SCORE = 25+(($find_hero_info['level']-1)*3)+(($find_hero_info['rare_type']-1)*5);
    $PASS_SCORE = 44+(($find_hero_info['level']-1)*3)+(($find_hero_info['rare_type']-1)*3);
    $min_fee = 100*$find_hero_info['level']*$find_hero_info['rare_type'];
    $forces = $_M['HERO_BASE'][$_M['HERO'][$find_hero_info['m_hero_pk']]['m_hero_base_pk']]['forces'];
    $type = $_M['HERO_BASE'][$_M['HERO'][$find_hero_info['m_hero_pk']]['m_hero_base_pk']]['type'];

    // 수행정보
    $PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$invi_info['cmd_hero_pk']]);
    $cmd_m_hero_pk = $PgGame->fetchOne();
    $cmd_forces = $_M['HERO_BASE'][$_M['HERO'][$cmd_m_hero_pk]['m_hero_base_pk']]['forces'];
    $cmd_type = $_M['HERO_BASE'][$_M['HERO'][$cmd_m_hero_pk]['m_hero_base_pk']]['type'];

    // 우호도 계산
    $SCORE = 0;

    // 세력 우호도
    if ($forces == $cmd_forces) {
        $SCORE += FORCE_RELATION_SAME;
    } else if (in_array($cmd_forces, $_M['FORCE_RELATION'][$forces]['GOOD'])) {
        $SCORE += FORCE_RELATION_GOOD;
    } else if (in_array($cmd_forces, $_M['FORCE_RELATION'][$forces]['BAD'])) {
        $SCORE += FORCE_RELATION_BAD;
    } else {
        $SCORE += FORCE_RELATION_OTHER;
    }

    // 비용 우호도
    if ($invi_info['send_gold'] <= $min_fee) {
        $SCORE += -5;
    } else if($invi_info['send_gold'] < $min_fee*2) {
        $SCORE += 0;
    } else if($invi_info['send_gold'] < $min_fee*3) {
        $SCORE += 5;
    } else if($invi_info['send_gold'] < $min_fee*4) {
        $SCORE += 10;
    } else if($invi_info['send_gold'] < $min_fee*5) {
        $SCORE += 15;
    } else if($invi_info['send_gold'] >= $min_fee*5) {
        $SCORE += 20;
    } else {
        $SCORE += 0;
    }
    //echo "세력+비용 : $SCORE\n";

    // 수행영웅 우호도 계산준비
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
    $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);

    $capacities = $Effect->getHeroCapacityEffects($invi_info['cmd_hero_pk']);
    $applies = $Effect->getHeroAppliedCommandEffects(PK_CMD_INVIT, $capacities);

    // 영빈관 배속 영웅, 수행영웅 우호도 계산
    $ret = $Effect->getEffectedValue($t['posi_pk'], ['hero_invitation_increase'], $SCORE, $applies['all']);
    $SCORE = $ret['value'];
    //print_r($ret);

    // 인물유형 우호도
    if ($type == $cmd_type) {
        $SCORE *= $SCORE * 1.2;
    }

    // 아이템 효과
    if ($invi_info['m_item_pk']) {
        $SCORE = 1000; // 삼고초려
    }

    $SCORE = intval($SCORE);

    // 세션에 있는 채널 정보가 필요함.
    $Log = new Log($Session, $PgGame);

    $invite_result = false;
    if ($SCORE < $PASS_SCORE) {
        // 실패
        $PgGame->query('UPDATE hero_invitation SET status = $1, result_status = $3, result_value = $4 WHERE hero_invi_pk = $2', ['F', $result_timer['queue_pk'], 'N', $SCORE]);
        $PgGame->query('DELETE FROM hero_invitation WHERE hero_invi_pk = $1', [$result_timer['queue_pk']]);

        // Log
        $Log->setHeroInvitation($Session->lord['lord_pk'], $t['posi_pk'], 'failure', $invi_info['cmd_hero_pk'], $result_timer['queue_pk'], $invi_info['hero_enco_pk'], $invi_info['start_dt'], $invi_info['end_dt'], $invi_info['send_gold'], $invi_info['result_value'], $invi_info['result_status'], $params['time_pk'], $enco_info['invitation_cnt'], $enco_info['encounter_value']);
    } else {
        $invite_result = true;
        // 성공
        // $query_params = Array('F', $result_timer['queue_pk'], 'Y', $SCORE);
        // $PgGame->query('UPDATE hero_invitation SET status = $1, result_status = $3, result_value = $4 WHERE hero_invi_pk = $2', $query_params);

        // 탐색종결 하기
        $PgGame->query('UPDATE hero_encounter SET yn_invited = $2 WHERE hero_enco_pk = $1', [$enco_info['hero_enco_pk'], 'Y']);

        // $query_params = Array($t['posi_pk']);
        // $PgGame->query('UPDATE territory SET last_hero_enco_pk = NULL WHERE posi_pk = $1', $query_params);

        // $Hero->setMyHeroRegist($enco_info['encounter_value'], $Session->lord['lord_pk'], 'V');

        $PgGame->query('DELETE FROM hero_invitation WHERE hero_invi_pk = $1', [$result_timer['queue_pk']]);

        // Log
        $Log->setHeroInvitation($Session->lord['lord_pk'], $t['posi_pk'], 'success', $invi_info['cmd_hero_pk'], $result_timer['queue_pk'], $invi_info['hero_enco_pk'], $invi_info['start_dt'], $invi_info['end_dt'], $invi_info['send_gold'], $SCORE, 'Y', $params['time_pk'], $enco_info['invitation_cnt'], $enco_info['encounter_value']);
    }

    // 수행영웅 명령해제
    $Hero->unsetCommand($invi_info['cmd_hero_pk']);

    // 액션
    $Session->sqAppend('PUSH', ['TOAST' => [
        'type' => 'invite',
        'castle_type' => 'I',
        'castle_pk' => $t['in_cast_pk'],
        'm_buil_pk' => PK_BUILDING_RECEPTIONHALL,
        'result' => $invite_result
    ]], null, $Session->lord['lord_pk'], $t['posi_pk']);

    return $Render->view('[OK]');
}));

// P
$app->get('/dispatcher/enchant', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */

    $Hero = new Hero($Session, $PgGame);

    $hero_pk = $result_timer['queue_pk'];
    $m_item_pk = substr($result_timer['description'], 8);

    if ($m_item_pk == 500383) {
        $enchant_cnt = $Session->lord['lord_enchant'];
    } else {
        $PgGame->query('SELECT enchant FROM hero WHERE hero_pk = $1', [$hero_pk]);
        $enchant_cnt = $PgGame->fetchOne();
    }

    $Hero->setHeroEnchant($hero_pk, $enchant_cnt, $m_item_pk, $t['posi_pk']);

    // 세션에 있는 채널 정보가 필요함.
    $Log = new Log($Session, $PgGame);

    return $Render->view('[OK]');
}));

// B
$app->get('/dispatcher/buff', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */
    $PgGame->query('SELECT posi_pk, m_item_pk FROM territory_item_buff WHERE terr_item_buff_pk = $1', [$result_timer['queue_pk']]);
    $PgGame->fetch();
    $m_item_pk = $PgGame->row['m_item_pk'];
    $posi_pk = $PgGame->row['posi_pk'];

    $PgGame->query('DELETE FROM territory_item_buff WHERE terr_item_buff_pk = $1', [$result_timer['queue_pk']]);

    // 세션에 있는 채널 정보가 필요함.
    $Log = new Log($Session, $PgGame);
    $Log->setBuff(false, $posi_pk, $result_timer['queue_pk'], $m_item_pk, 'F');

    if ( ($m_item_pk == BUILD_QUEUE_INCREASE_ITEM) ||($m_item_pk == BUILD_QUEUE2_INCREASE_ITEM) ) {
        $PgGame->query('UPDATE build SET concurr_max = $3 WHERE posi_pk = $1 AND in_cast_pk = $2', [$posi_pk, 1, BUILD_QUEUE_DEFAULT_COUNT]);
    } else {
        $effects_for_update = [$m_item_pk];

        $Resource = new Resource($Session, $PgGame);
        $GoldPop = new GoldPop($Session, $PgGame);
        $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
        $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);
        $effect_types = $Effect->getEffectTypes($effects_for_update);
        if (COUNT($effect_types) > 0) {
            $Effect->setUpdateEffectTypes($t['posi_pk'], $effect_types);
        }
    }

    return $Render->view('[OK]');
}));

// D
$app->get('/dispatcher/truce', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */

    // $Item = new Item($Session, $PgGame);
    // $Item->finishTruceItem($t['posi_pk']);
    $m_item_pk = substr($result_timer['description'], 0, 6);
    $Territory = new Territory($Session, $PgGame);
    $Territory->finishTruceStatus($t['posi_pk'], $m_item_pk, $Session->lord['lord_pk']);

    // PN
    // $Push->send('newbie', '', $Session->lord['lord_pk'], $t['posi_pk']);

    // 세션에 있는 채널 정보가 필요함.
    $Log = new Log($Session, $PgGame);
    $Log->setBuff(false, $t['posi_pk'], $result_timer['queue_pk'], $m_item_pk, 'F');

    return $Render->view('[OK]');
}));

// S
$app->get('/dispatcher/delivery', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */

    $deli_pk = substr($result_timer['description'], 8);
    $PgGame->query('SELECT offe_posi_pk, bid_posi_pk, bid_lord_pk, reso_type, deal_amount, total_price FROM trade_delivery WHERE deli_pk = $1', [$deli_pk]);
    $PgGame->fetch();
    $row = $PgGame->row;
    if (!$row) {
        //dubug_mesg남기기
        Debug::debugMessage('ERROR', 'trade_delivery fail:deli_pk[' . $deli_pk.'];time_pk['.$params['time_pk'].'];');
        echo "[OK]";
        exit;
    }

    // 자원 배송하기
    $reso_type = match ($row['reso_type']) {
        'F' => 'food',
        'H' => 'horse',
        'L' => 'lumber',
        'I' => 'iron',
        default => null
    };

    if ($reso_type === null) {
        Debug::debugMessage('ERROR', 'trade_delivery fail:deli_pk[' . $deli_pk.'];time_pk['.$reso_type.'];');
        echo "[OK]";
        exit;
    }

    $Resource = new Resource($Session, $PgGame);
    $Troop = new Troop($Session, $PgGame);
    $Report = new Report($Session, $PgGame);
    $Resource->increase($row['bid_posi_pk'], [$reso_type => $row['deal_amount']], $Session->lord['lord_pk'], 'trade_delivery');

    // 배송정보 삭제
    $PgGame->query('DELETE FROM trade_delivery WHERE deli_pk = $1', [$deli_pk]);

    // 보고서
    $z_content = [];

    // reso
    $z_content['reso_type'] = $reso_type;
    $z_content['reso_amount'] = $row['deal_amount'];

    // from & to
    $z_from = ['posi_pk' => '-', 'posi_name' => '-:타 군주 영지:0'];
    $z_to = ['posi_pk' => $row['bid_posi_pk'], 'posi_name' => $Troop->getPositionName($row['bid_posi_pk'])];

    // title & summary
    $z_title = '';
    $z_summary = '';
    $Report->setReport($Session->lord['lord_pk'], 'move', 'shipping_finish', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));

    $Session->sqAppend('PUSH', ['TRADE_COMPLETE_DELIVERY' => true], null, $Session->lord['lord_pk'], $row['bid_posi_pk']);

    // 세션에 있는 채널 정보가 필요함.
    $Log = new Log($Session, $PgGame);
    $Log->setBuildingTradedept($row['bid_lord_pk'], $row['bid_posi_pk'], 'delivery', $reso_type, null, null, $row['deal_amount'], 'deli_pk['.$deli_pk.'];'.'total_price['.$row['total_price'].']');

    return $Render->view('[OK]');
}));

// M
$app->get('/dispatcher/medical', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $Quest = new Quest($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */

    // 의료원 치료 완료
    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['army']);

    $BuildMedical = new BuildMedical($Session, $PgGame, $Timer);
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Army = new Army($Session, $PgGame, $Resource, $GoldPop);

    $result_array = $BuildMedical->finish($result_timer['queue_pk']);

    $code = $_M['ARMY'][$result_array['m_army_pk']]['code'];

    $Medical = new Medical($Session, $PgGame);
    $ret = $Medical->upgradePost($t['posi_pk'], $code, $result_array['build_number'], $result_timer['queue_pk'], $params['time_pk']);

    // 수치 변경전 저장하기 - 자원/인구/황금
    // notification
    $Army->get($t['posi_pk'], [$code]);

    // 큐처리
    $BuildMedical->queue($result_array['buil_pk'], $t['posi_pk']);

    $Session->sqAppend('BUIL_IN_CAST', [$t['in_cast_pk'] => ['current' => $BuildMedical->getCurrent($t['posi_pk'], $t['in_cast_pk'])]], null, $Session->lord['lord_pk'], $t['posi_pk']);

    // PN
    // $info = $_M['ARMY'][$result_array['m_army_pk']]['title'];
    // $info .= '('. $result_array['build_number']. ')';
    // $Push->send('treat', $info, $Session->lord['lord_pk'], $t['posi_pk']);

    // 퀘스트 처리
    if ($ret) {
        // 치료 완료 퀘스트 추가 필요
        // $Quest->conditionCheckQuest($Session->lord['lord_pk'], Array('quest_type' => 'army_recruit', 'army_code' => $code, 'posi_pk' => $t['posi_pk']));
    }

    // 액션
    $Session->sqAppend('QUEUE', [$result_timer['queue_pk'] => null], null, $Session->lord['lord_pk'], $t['posi_pk']);
    $Session->sqAppend('PUSH', ['TOAST' => [
        'type' => 'medical',
        'castle_type' => 'I',
        'castle_pk' => $t['in_cast_pk'],
        'm_buil_pk' => PK_BUILDING_MEDICAL,
        'pk' => $result_array['m_army_pk']
    ]], null, $Session->lord['lord_pk'], $t['posi_pk']);


    $Quest->countCheckQuest($Session->lord['lord_pk'], 'EVENT_ARMY_CURE', ['value' => $result_array['build_number']]);

    // 세션에 있는 채널 정보가 필요함.
    $Log = new Log($Session, $PgGame);

    return $Render->view('[OK]');
}));

// O
$app->get('/dispatcher/occupation', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */
    if ($t['queue_action'] !== 'Y') {
        echo '[OK]';
        exit;
    }

    // 이미지 갱신을 위한 position update
    $PgGame->query('SELECT att_posi_pk, def_posi_pk, att_lord_pk, def_lord_pk FROM occupation_inform WHERE occu_pk = $1', [$result_timer['queue_pk']]);
    $PgGame->fetch();
    $row = $PgGame->row;

    $PgGame->query('UPDATE position SET last_update_dt = now() WHERE posi_pk IN ($1, $2)', [$row['att_posi_pk'], $row['def_posi_pk']]);

    // 전쟁 선포 삭제
    $PgGame->query('DELETE FROM occupation_inform WHERE occu_pk = $1', [$result_timer['queue_pk']]);

    // 사운드 출력
    $Session->sqAppend('PUSH', ['PLAY_SOUND' => 'alert'], null, $row['def_lord_pk']);

    // 세션에 있는 채널 정보가 필요함.
    $Log = new Log($Session, $PgGame);
    $Log->setTerritory($row['att_lord_pk'], $row['att_posi_pk'], 'EndOccupationInform', 'def_info:posi_pk['.$row['def_posi_pk'].'];lord_pk['.$row['def_lord_pk'].'];');

    return $Render->view('[OK]');
}));

// R
$app->get('/dispatcher/finishOverRank', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['time_pk']);

    /* 타이머 공통 */
    $Session = new Session(false);
    $PgGame = new Pg('DEFAULT');
    $Timer = new Timer($Session, $PgGame);
    $t = $Timer->getRecord($params['time_pk']); // 타이머 확인
    if ($t === false || $t['status'] != 'P' || ! $Session->positionToLord($t['posi_pk'])) {
        // TODO 오류 로그
        return $Render->view();
    }
    $result_timer = $Timer->finish($params['time_pk']);
    /* 타이머 공통 끝 */

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['hero', 'hero_base']);

    // 영응 능력 무능화
    $PgGame->query('UPDATE hero SET leadership_basic = 1, mil_force_basic = 1, intellect_basic = 1, politics_basic = 1, charm_basic = 1 WHERE hero_pk = $1', [$result_timer['queue_pk']]);

    $PgGame->query('SELECT m_offi_pk, posi_pk, status, status_cmd, cmd_type FROM my_hero WHERE hero_pk = $1', [$result_timer['queue_pk']]);
    $PgGame->fetch();
    $m_offi_pk = $PgGame->row['m_offi_pk'];
    $posi_pk = $PgGame->row['posi_pk'];
    $my_hero_info = $PgGame->row;

    $Hero = new Hero($Session, $PgGame);
    $Hero->setNewStat($result_timer['queue_pk'], $m_offi_pk);

    // 영웅 배속 효과 재계산
    $PgGame->query('SELECT m_buil_pk, in_castle_pk FROM building_in_castle WHERE posi_pk = $1 AND assign_hero_pk = $2', [$posi_pk, $result_timer['queue_pk']]);
    $PgGame->fetch();
    $m_buil_pk = $PgGame->row['m_buil_pk'];
    $castle_pk = $PgGame->row['in_castle_pk'];

    // 효과삭제
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
    $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);
    $effects_for_update = $Effect->unsetTerritoryHeroEffects($posi_pk, $castle_pk);

    $effect_types = $Effect->getEffectTypes($effects_for_update);
    if (COUNT($effect_types) > 0)
        $Effect->setUpdateEffectTypes($posi_pk, $effect_types);

    // 효과 재계산
    $capacities = $Effect->getHeroCapacityEffects($result_timer['queue_pk']);
    $applies = $Effect->getHeroAppliedAssignEffects($m_buil_pk, $capacities);
    $set_cnt = $Effect->setTerritoryHeroEffects($posi_pk, $castle_pk, $result_timer['queue_pk'], $applies);

    $effects_for_update = $applies['all'];

    $effect_types = $Effect->getEffectTypes($effects_for_update);
    if (COUNT($effect_types) > 0) {
        $Effect->setUpdateEffectTypes($posi_pk, $effect_types);
    }

    // 보고서
    // from & to
    $PgGame->query('SELECT m_hero_pk FROM hero WHERE hero_pk = $1', [$result_timer['queue_pk']]);
    $m_hero_pk = $PgGame->fetchOne();
    $m_hero_base_pk = $_M['HERO'][$m_hero_pk]['m_hero_base_pk'];
    $level = $_M['HERO'][$m_hero_pk]['level'];
    $hero_name = $_M['HERO_BASE'][$m_hero_base_pk]['name'];

    if (isset($hero_name) && isset($level)) {
        if (isset($posi_pk)) {
            $Troop = new Troop($Session, $PgGame);
            $z_from = ['posi_pk' => $posi_pk, 'posi_name' => $Troop->getPositionName($posi_pk)];
        } else {
            $z_from = ['posi_pk' => '-', 'posi_name' => '-'];
        }
        $z_to = [$hero_name, 'posi_name' => $hero_name . ' Lv.'.$level];

        // title & summary
        $z_title = '';
        $z_summary = $hero_name . ':' . $level;

        $z_content['hero'] = ['pk' => $result_timer['queue_pk'], 'm_pk' => $m_hero_pk];

        $Report = new Report($Session, $PgGame);
        $Report->setReport($Session->lord['lord_pk'], 'misc', 'over_rank_hero_inca', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));
    }

    if (isset($posi_pk)) {
        $Session->sqAppend('HERO', [$result_timer['queue_pk'] => $Hero->getMyHeroInfo($result_timer['queue_pk'])], null, $Session->lord['lord_pk'], $posi_pk);
    }

    // 세션에 있는 채널 정보가 필요함.
    $Log = new Log($Session, $PgGame);
    $Log->setHero($Session->lord['lord_pk'], $posi_pk, 'Incapable', $result_timer['queue_pk'], $my_hero_info['status'], $my_hero_info['status_cmd'], $my_hero_info['cmd_type'], '최초 영입시간:'.$result_timer['start_dt']);

    return $Render->view('[OK]');
}));

// X, Y - 전투 코드는 방대하므로 따로 파일을 관리함.