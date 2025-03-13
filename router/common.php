<?php
global $app, $Render, $i18n;

// 따로 분류가 필요 없는 API 모음

$app->post('/api/attendanceCheck', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT attendance_cnt FROM my_event WHERE lord_pk = $1', [$Session->lord['lord_pk']]);

    return $Render->nsXhrReturn('success', null, ['attendance_cnt' => $PgGame->fetchOne()]);
}));

// TODO battleMock 동작확인 필요.
$app->post('/api/battleMock', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['wall_open', 'battle_type']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Troop = new Troop($Session, $PgGame);
    $Battle = new Battle($Session, $PgGame, $Troop);

    $battle_type = $params['battle_type'];
    $wall_open = $params['wall_open'] == 'Y';

    global $attack_position_line1, $attack_position_line2, $attack_position_line3;
    $attack_position = [$attack_position_line1, $attack_position_line2, $attack_position_line3];
    // 방어 진영 선택
    if ($battle_type == 'valley') {
        // 자원지
        global $defence_position_line1, $defence_position_line2, $defence_position_line3;
        $defence_position = [$defence_position_line1, $defence_position_line2, $defence_position_line3];
    } else {
        if ($wall_open) {
            // 영지 - 성문개방
            global $defence_position_wall_open_lineFort1, $defence_position_wall_open_lineFort2, $defence_position_wall_open_line1, $defence_position_wall_open_line2, $defence_position_wall_open_line3, $defence_position_wall_open_lineWall;
            $defence_position = [$defence_position_wall_open_lineFort1, $defence_position_wall_open_lineFort2, $defence_position_wall_open_line1, $defence_position_wall_open_line2, $defence_position_wall_open_line3, $defence_position_wall_open_lineWall];
        } else {
            // 영지 - 성문폐쇄
            global $defence_position_wall_close_lineFort1, $defence_position_wall_close_lineFort2, $defence_position_wall_close_lineWall;
            $defence_position = [$defence_position_wall_close_lineFort1, $defence_position_wall_close_lineFort2, $defence_position_wall_close_lineWall];
        }
    }

    // 공격측 병력
    $attack['position'] = $attack_position;

    foreach ($params AS $k => $v) {
        if (str_starts_with($k, 'att_unit_')) {
            if ($v > 0) {
                $unit_type = substr($k, 9);
                $Battle->addUnit($battle_type, $attack['army'], $unit_type, $v);
            }
        }
    }

    // 공격측 진영 조정
    $Battle->positionAdjust($attack['position'], $attack['army']);

    // 방어측 병력
    $defence['position'] = $defence_position;
    $Battle->addUnit($battle_type, $defence['army'], 'wall', $_POST['wall_level']); // level

    foreach ($params AS $k => $v) {
        if (str_starts_with($k, 'def_unit_')) {
            if ($v > 0) {
                $unit_type = substr($k, 9);
                $Battle->addUnit($battle_type, $defence['army'], $unit_type, $v);
            }
        }
    }

    // 방어측 진영 조정
    $Battle->positionAdjust($defence['position'], $defence['army']);

    $turn_description = '';

    $turn_result = [];
    $turn_count = 1;

    $att =& $attack['position'];
    $att_data =& $attack['army'];
    $def =& $defence['position'];
    $def_data =& $defence['army'];

    for (; $turn_count <= BATTLE_MAX_TURN; $turn_count++) {
        // unit_dead_last_turn 리셋
        foreach ($att_data AS $k => $v) {
            $att_data[$k]['unit_dead_last_turn'] = 0;
        }

        foreach ($def_data AS $k => $v) {
            $def_data[$k]['unit_dead_last_turn'] = 0;
        }

        // 공격턴을 위해 방어측 포지션 백업
        $backup_def = $def;

        $turn_description .= "<h2> $turn_count 턴</h2>";

        $turn_description .= "<h3 class=\"attack\"> 공격측 턴</h3>";
        $turn_description .= "<div class=\"attack\">";
        $att_retval = $Battle->doBattle($att, $att_data, $def, $def_data);
        $turn_description .= "\n</div>";

        $turn_description .= "<h3 class=\"defence\"> 방어측 턴</h3>";
        $turn_description .= "<div class=\"defence\">";
        $def_retval = $Battle->doBattle($backup_def, $def_data, $att, $att_data);
        $turn_description .= "\n</div>";

        // 만족시 승/패 가르기 위해 루틴 out
        //  - 공통 : 10합
        //  - 영지 : 성벽 무너지면
        //  - 자원지 : 공/방측 한 곳 이상에서 전체 병력 상실 시
        if ($battle_type == 'territory' && $def_data['wall']['unit_remain'] <= 0) {
            break;
        } else {
            if (!$att || !$def) {
                break;
            }
        }
    }

    $att_success = false;
    $def_success = false;

    // 영지전 결과
    if ($battle_type == 'territory') {
        // 성벽 무너짐 체크
        if ($def_data['wall']['unit_remain'] > 0) {
            $def_success = true;
        } else {
            if ($att) {
                $att_success = true;
            } else {
                // 성벽은 파괴 하였으나 전멸 당했을 경우 방어측 승리 (영지의 경우만)
                $def_success = true;
            }
        }
    } else {
        // 자원지전 결과
        if ($def) {
            $def_success = true;
        } else {
            if ($att) {
                $att_success = true;
            }
        }
    }

    $turn_description .= "\n\n";

    $response = [];

    // 전투 결과
    $turn_description .= "<span style='color:black;'>> 공격측 승? ($att_success)</span> \n";
    $turn_description .= "<span style='color:black;'>> 방어측 승? ($def_success)</font> \n\n";

    $response['turn'] = min($turn_count, BATTLE_MAX_TURN);
    $response['winner'] = !$att_success && !$def_success ? 'draw' : ($att_success ? 'attack' : 'defence');

    // 전투피해
    $turn_description .= "<h2> 전투피해 </h2>";
    $turn_description .= "<h3 class=\"attack\"> 공격측</h3>";
    $turn_description .= "<div class=\"attack\">\n";
    foreach ($att_data AS $k => $v) {
        $unit_dead = $v['unit_amount'] - $v['unit_remain'];
        $v['unit_injury'] = floor($unit_dead * 0.1);
        $turn_description .= " $k 병과 {$v['unit_amount']} 중 {$unit_dead} 사망 {$v['unit_remain']} 잔존, {$v['unit_injury']} 부상\n";

        $response['att'][$k] = $unit_dead;
    }
    $turn_description .= "\n</div>";

    $turn_description .= "<h3 class=\"attack\"> 방어측</h3>";
    $turn_description .= "<div class=\"attack\">\n";
    if ($def_data) {
        foreach ($def_data AS $k => $v) {
            $unit_dead = $v['unit_amount']-$v['unit_remain'];
            $v['unit_injury'] = floor($unit_dead*0.1);
            $turn_description .= " $k 병과 {$v['unit_amount']} 중 {$unit_dead} 사망 {$v['unit_remain']} 잔존, {$v['unit_injury']} 부상\n";

            $response['def'][$k] = $unit_dead;
        }
    }
    $turn_description .= "\n</div>";

    // TODO 전투결과 반영 - 데이터 업데이트

    // TODO 보고서 작성

    return $Render->nsXhrReturn('success', null, $response);
}));

$app->post('/api/production', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['lud']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $Production = new Production($Session, $PgGame);
    $r = $Production->get($params['posi_pk'], $params['lud']);

    return $Render->nsXhrReturn('success', null, $r);
}));


// 외부 사이트용 API
$app->get('/api/home/rank/{type}[/{order}]', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Redis = new RedisCache();

    $length = (!isset($params['length'])) ? 10 : (INT)$params['length'];
    $length = ($length > 100) ? 99 : $length; // 최대 100개

    try {
        switch ($params['type']) {
            case 'lord':
                $order = (!isset($params['order'])) ? 'power' : $params['order'];
                $rankings = $Redis->zRange("ranking:lord:$order", 0, $length);
                break;
            case 'alliance':
                $order = (!isset($params['order'])) ? 'power' : $params['order'];
                $rankings = $Redis->zRange("ranking:alliance:$order", 0, $length);
                break;
            case 'hero':
                $order = (!isset($params['order'])) ? 'leadership' : $params['order'];
                $rankings = $Redis->zRange("ranking:hero:$order", 0, $length);
                break;
            default:
                return $Render->nsXhrReturn('error', 'Invalid type value.');
        }

        $rankings = array_map(function ($o) {
            return json_decode($o, true);
        }, $rankings);

        if ($params['type'] === 'hero') {
            // 영웅 정보를 좀 더 자세히 정리하여 return
            global $NsGlobal, $_M;
            $NsGlobal->requireMasterData(['hero', 'hero_base']);

            $rankings = array_map(function ($o) use ($_M, $i18n) {
                $m_hero = $_M['HERO'][$o['m_hero_pk']];
                $m_hero_base = $_M['HERO_BASE'][$m_hero['m_hero_base_pk']];
                return [
                    'rank' => $o['rank'],
                    'lord_name' => $o['lord_name'],
                    'name' => $i18n->t('hero_name_' . $m_hero['m_hero_base_pk']), // 텍스트 파일 기준
                    'rare' => $m_hero_base['rare_type'],
                    'level' => $o['level'],
                    'leadership' => $o['leadership'],
                    'mil_force' => $o['mil_force'],
                    'intellect' => $o['intellect'],
                    'politics' => $o['politics'],
                    'charm' => $o['charm'],
                ];
            }, $rankings);
        }
    } catch (Throwable $e) {
        return $Render->nsXhrReturn('error', $e->getMessage());
    }

    return $Render->nsXhrReturn('success', null, [$rankings]);
}));

$app->get('/api/home/lord/{uuid}', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $PgCommon = new Pg('COMMON');
    $PgGame = new Pg('DEFAULT');

    try {
        $PgCommon->query('SELECT account_pk FROM account WHERE uid = $1', [$params['uuid']]);
        $account_pk = $PgCommon->fetchOne();
        if (! $account_pk) {
            throw new ErrorHandler('error', 'Not Found User Account.');
        }

        $PgGame->query('SELECT l.lord_name, l.level, l.power, l.main_posi_pk, a.title as alliance_title FROM lord_web as w left join lord as l on l.lord_pk = w.lord_pk left join alliance as a on a.alli_pk = l.alli_pk WHERE w.web_id = $1', [$account_pk]);
        $PgGame->fetch();
        $lord_info = $PgGame->row;
        if (! $lord_info) {
            throw new ErrorHandler('error', 'Not Found Lord.');
        }
    } catch (Throwable $e) {
        return $Render->nsXhrReturn('error', $e->getMessage());
    }

    return $Render->nsXhrReturn('success', null, $lord_info);
}));

$app->post('/api/home/terminate/{uuid}', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session(false);
    $PgCommon = new Pg('COMMON');
    $PgGame = new Pg('DEFAULT');
    $Troop = new Troop($Session, $PgGame);
    $Log = new Log($Session, $PgGame);

    $PgCommon->query('SELECT account_pk FROM account WHERE uid = $1', [$params['uuid']]);
    $account_pk = $PgCommon->fetchOne();
    if (! $account_pk) {
        throw new ErrorHandler('error', 'Not Found User Account.');
    }

    $PgGame->query('SELECT l.lord_pk, l.main_posi_pk, l.withdraw_dt FROM lord_web as w left join lord as l on l.lord_pk = w.lord_pk WHERE w.web_id = $1', [$account_pk]);
    $PgGame->fetch();
    $lord_info = $PgGame->row;
    if (! $lord_info) {
        throw new ErrorHandler('error', 'Not Found Lord.');
    }
    $lord_pk = $PgGame->row['lord_pk'];
    $main_posi_pk = $PgGame->row['main_posi_pk'];
    $withdraw_dt = $PgGame->row['withdraw_dt'];

    if ($withdraw_dt != null) {
        throw new ErrorHandler('error', 'an already departed');
    }

    try {
        $PgGame->begin();

        $power = 0; // 영지 생성시 재계산함.
        $Troop->removeAllTroop($lord_pk); // 부대관련 데이터 삭제

        // 방랑군주 처리 이용하여 처리
        $PgGame->query('SELECT title FROM territory WHERE posi_pk = $1', [$main_posi_pk]);
        $_last_territory_name = $PgGame->fetchOne() . ' (' . $main_posi_pk . ')';
        $PgGame->query('SELECT roamerlord('. $lord_pk .', \''. $main_posi_pk . '\', '.$power.', \''.$_last_territory_name .'\')');
        $Log->setTerritory($lord_pk, $main_posi_pk, 'terminate_lord');

        // 영웅 제거 hero, my_hero, my_hero_skill_slot
        $PgGame->query('SELECT hero_pk FROM my_hero WHERE lord_pk = $1', [$lord_pk]);
        $PgGame->fetchAll();
        $hero_rows = $PgGame->rows;
        $PgGame->query('DELETE FROM my_hero WHERE lord_pk = $1', [$lord_pk]);
        foreach ($hero_rows as $row) {
            $PgGame->query('DELETE FROM my_hero_skill_slot WHERE hero_pk = $1', [$row['hero_pk']]);
            $PgGame->query('DELETE FROM hero WHERE hero_pk = $1', [$row['hero_pk']]);
        }

        // 보유 아이템 제거 my_item, my_item_buy
        $PgGame->query('DELETE FROM my_item WHERE lord_pk = $1', [$lord_pk]);
        $PgGame->query('DELETE FROM my_item_buy WHERE lord_pk = $1', [$lord_pk]);

        // 보유 퀘스트 제거 my_quest
        $PgGame->query('DELETE FROM my_quest WHERE lord_pk = $1', [$lord_pk]);

        // 패키지 구매 기록 제거 my_package
        $PgGame->query('DELETE FROM my_package WHERE lord_pk = $1', [$lord_pk]);

        // 영웅 모집 기록 제거 my_pickup
        $PgGame->query('DELETE FROM my_pickup WHERE lord_pk = $1', [$lord_pk]);

        // 부대 프리셋 제거 troop_preset
        $PgGame->query('DELETE FROM troop_preset WHERE lord_pk = $1', [$lord_pk]);

        // 출석 기록 제거 my_event
        $PgGame->query('DELETE FROM my_event WHERE lord_pk = $1', [$lord_pk]);

        // 보유 스킬 제거 my_hero_skill, my_hero_skill_box
        $PgGame->query('DELETE FROM my_hero_skill_box WHERE lord_pk = $1', [$lord_pk]);
        $PgGame->query('DELETE FROM my_hero_skill WHERE lord_pk = $1', [$lord_pk]);

        // 점령치 이벤트 제거 occupation_point
        $PgGame->query('DELETE FROM occupation_point WHERE lord_pk = $1', [$lord_pk]);

        // 추가 확인 필요 game_option(게임 옵션), report(보고서)
        $PgGame->query('DELETE FROM game_option WHERE lord_pk = $1', [$lord_pk]);
        $PgGame->query('DELETE FROM report WHERE lord_pk = $1', [$lord_pk]);

        // qbig_pack 은 매출 기록과도 같으니 남겨둠.

        // lord 테이블 탈퇴 처리
        $PgGame->query('UPDATE lord SET withdraw_dt = now() WHERE lord_pk = $1', [$lord_pk]);

        $PgGame->commit();
    } catch (Throwable $e) {
        $PgGame->rollback();
        return $Render->nsXhrReturn('error', $e->getMessage());
    }

    return $Render->nsXhrReturn('success');
}));