<?php
global $app, $Render, $i18n;

$app->post('/api/world/getFort', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 방어시설이 존재하는지 체크
    $PgGame->query('SELECT trap, abatis, tower FROM fortification_valley WHERE posi_pk = $1', [$params['target_posi_pk']]);
    $r = $PgGame->fetch();
    $fort = (! $r) ? ['trap' => 0, 'abatis' => 0, 'tower' => 0] : $PgGame->row;

    return $Render->nsXhrReturn('success', null, ['fort' => $fort]);
}));

$app->post('/api/world/upgrade', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['build_number']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    if ($params['build_number'] < 0) {
        throw new ErrorHandler('error', $i18n->t('msg_need_amount')); // 개수를 입력 하세요.
    }

    // 소유중인 자원지가 맞는지 체크
    $PgGame->query('SELECT posi_pk FROM territory_valley WHERE posi_pk = $1 AND valley_posi_pk = $2', [$params['posi_pk'], $params['target_posi_pk']]);
    $posi_pk = $PgGame->fetchOne();
    if (! $posi_pk) {
        throw new ErrorHandler('error', 'Error Occurred. [39001]'); // 해당 영지에서 소유 중인 자원지가 아닙니다.
    }

    global $NsGlobal, $_M;
    $NsGlobal->requireMasterData(['fortification']);

    // 설치 진행
    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Terr = new Territory($Session, $PgGame);
    $Queue = new Queue($Session, $PgGame);

    $FortificationValley = new FortificationValley($Session, $PgGame, $Resource, $GoldPop, $Terr);
    $result_arr = $FortificationValley->upgradePre($params['posi_pk'], $params['target_posi_pk'], $params['code'], $params['build_number']);
    if (!$result_arr) {
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    // 단축 효과 적용
    $FigureReCalc = new FigureReCalc($Session, $PgGame, $Resource, $GoldPop);
    $Effect = new Effect($Session, $PgGame, $Resource, $GoldPop, $FigureReCalc);
    $ret = $Effect->getEffectedValue($params['target_posi_pk'], ['fort_build_time_decrease'], $result_arr['build_time']);

    $result_arr['build_time'] = intval($ret['value']);

    // TODO description 앞에 posi_pk 는 타이머 종료시 dispatcher 에서 체크 할 수 있도록 추가해줌. (더 좋은 방법은 없나?)
    $description = $params['target_posi_pk']. ' - ' .$_M['FORT'][$result_arr['m_fort_pk']]['title'] . ' (' . $result_arr['build_number'] . ')';

    $Timer = new Timer($Session, $PgGame);
    $BuildFortificationValley = new BuildFortificationValley($Session, $PgGame, $Timer);
    $queue_pk = $BuildFortificationValley->set($result_arr['buil_pk'], $result_arr['m_fort_pk'], $result_arr['build_number'], $result_arr['build_time']);
    $Session->sqAppend('QUEUE', [$queue_pk => $Queue->getData('fortification_valley', $queue_pk)]);

    $Timer->set($params['posi_pk'], 'W', $queue_pk, 'U', $description, $result_arr['build_time'], 0);

    //Log
    $log_description = $_M['FORT'][$result_arr['m_fort_pk']]['code'] . '[curr['.$result_arr['current_number'].'];build['. $result_arr['build_number'].'];];';
    $time_pk = $PgGame->currSeq('timer_time_pk_seq');
    $Log = new Log($Session, $PgGame);
    $Log->setFortification($Session->lord['lord_pk'], $params['target_posi_pk'], 'upgrade', $log_description, $queue_pk, $result_arr['buil_pk'], $result_arr['m_fort_pk'], null, null, $result_arr['build_time'], null, $result_arr['build_number'], $time_pk);

    return $Render->nsXhrReturn('success', null, $result_arr);
}));

$app->post('/api/world/disperse', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Render->existParams(['disperse_number']);
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    if ($params['disperse_number'] < 0) {
        throw new ErrorHandler('error', $i18n->t('msg_need_amount')); // 수량을 입력 하세요.
    }

    global $NsGlobal;

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);
    $Terr = new Territory($Session, $PgGame);
    $FortificationValley = new FortificationValley($Session, $PgGame, $Resource, $GoldPop, $Terr);

    $ret = $FortificationValley->disperse($params['posi_pk'], $params['target_posi_pk'], $params['code'], $params['disperse_number']);
    if (!$ret) {
        throw new ErrorHandler('error', $NsGlobal->getErrorMessage());
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/world/coords', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $procedure_name = ($params['near'] == 'Y') ? 'getnearpositions' : 'getpositions';

    $lud = 0;
    if (isset($params['lud_max']) && $params['lud_max'] > 0) {
        $lud = 	$params['lud_max'] + 1;
    }

    $current_posi_pk = (isset($params['current_posi_pk'])) ? $params['current_posi_pk'] : $params['posi_pk'];
    $current_posi_pk = $current_posi_pk . ':';

    $PgGame->query('SELECT \''. $params['detail'].'\' as detail_val, posi_pk, type, level, lord_pk, state, date_part(\'epoch\', last_update_dt)::integer as last_update_dt, current_point, date_part(\'epoch\', update_point_dt)::integer as update_point_dt, flag, status_truce, alli_pk, alli_title FROM '. $procedure_name. '(\''. $current_posi_pk. '\',' . $lud .',' . $params['xcount'] .',' . $params['ycount'] .')');
    $arr = [];

    while($PgGame->fetch()) {
        $row = null;
        if ($lud > 0) {
            if (isset($PgGame->row['type'])) {
                $row = $PgGame->row;
            }
        } else {
            $p = explode('x', $PgGame->row['posi_pk']);
            $x = $p[0];
            $y = $p[1];
            if ($x < 1 || $y < 1 || $x > 486 || $y > 486) {
                continue;
            }
            $row = $PgGame->row;
        }
        if ($row !== null) {
            if ($row['type'] === 'P') { // 요충지라면
                $row['alli_pk'] = null;
                $row['alli_title'] = null;
                $row['lord_pk'] = null;
            }
            $arr['xy_'. $row['posi_pk']] = $row;
        }
    }

    $procedure_name = ($params['detail'] == 'Y' && $params['near'] == 'Y') ? 'getnearpositionsdetail' : 'getpositionsdetail';

    //상세 정보 요청하기
    $PgGame->query('SELECT posi_pk, lord_name, power, fame, type, current_point, date_part(\'epoch\', update_point_dt)::integer as update_point_dt, title FROM '. $procedure_name. '(\''. $current_posi_pk. '\',' . $lud .',' . $params['xcount'] .',' . $params['ycount'] .')');
    while($PgGame->fetch()) {
        if (isset($arr['xy_'. $PgGame->row['posi_pk']]) && $arr['xy_'. $PgGame->row['posi_pk']]['type'] !== 'P') { // 요충지는 제외
            $arr['xy_'. $PgGame->row['posi_pk']]['detail'] = $PgGame->row;
        }
    }

    // 동맹 정보
    /* $query_params = Array($Session->lord['lord_pk'], 'T');
    $PgGame->query('select b.posi_pk from alliance_member a, position b where a.lord_pk = $1 AND a.memb_lord_pk = b.lord_pk AND b.type = $2', $query_params);
    while($PgGame->fetch())
    {
        if ($arr['xy_'. $PgGame->row['posi_pk']])
        {
            echo $PgGame->row['posi_pk'];
            $arr['xy_'. $PgGame->row['posi_pk']]['alli'] = 'Y';
        }
    } */


    // 이동 중인 부대 정보 - TODO 일단 전체 정보이나 차후 상황에 따라 본인 또는 동맹의 부대 이동만 보여주게 될 수도?
    /*$Redis = new RedisCache();
    $list = $Redis->hGetAll('world:troop:move');
    $troops = [];
    foreach ($list as $troop) {
        $troop = json_decode($troop, true);
        // 정찰인 경우 본인에게만 보여야함.
        if ($troop['cmd_type'] == 'S' && ($troop['src_posi_pk'] != $Session->lord['main_posi_pk'])) {
            continue;
        }
        $troops[$troop['troo_pk']] = $troop;
    }
    if (count($troops) > 0) {
        $Session->sqAppend('TROOP', $troops);
    }*/

    return $Render->nsXhrReturn('success', null, ['WORLD' => $arr]);
}));

$app->post('/api/world/update', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();

    /*$Redis = new RedisCache();
    $list = $Redis->hGetAll('world:troop:move');
    $troops = [];
    foreach ($list as $troop) {
        $troop = json_decode($troop, true);
        $troops[$troop['troo_pk']] = $troop;
    }
    if (count($troops) > 0) {
        $Session->sqAppend('TROOP', $troops);
    }*/

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/world/detail', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // Validation 검사를 위해...
    $params['posi_pk'] = $params['target_posi_pk'];

    // dst_lord_info
    $Troop = new Troop($Session, $PgGame);
    $dst_posi = $Troop->getPositionRelation($params['posi_pk']);
    $result_arr = [];
    // 꼭 필요한 정보만
    $result_arr['dst_posi_info'] = [
        'type' => $dst_posi['type'],
        'name' => $dst_posi['name'],
        'level' => $dst_posi['level'],
        'relation' => $dst_posi['relation'],
        'my_camp_troop' => $dst_posi['my_camp_troop'],
        'my_troo_pk' => $dst_posi['my_troo_pk'],
        'truce' => $dst_posi['truce'],
    ];

    // lord_detail
    if (isset($dst_posi['lord_pk']) && $dst_posi['type'] !== 'P') {
        $PgGame->query('SELECT lord_name, power, level FROM lord WHERE lord_pk = $1', [$dst_posi['lord_pk']]);
        if ($PgGame->fetch()) {
            $result_arr['lord_detail'] = $PgGame->row;
        }
    }

    $Hero = new Hero($Session, $PgGame);

    // terr_hero
    $hero_info = null;
    if ($dst_posi['type'] == 'T') {
        // 성주정보
        $PgGame->query('SELECT lord_hero_pk, title FROM territory WHERE posi_pk = $1', [$params['posi_pk']]);
        if ($PgGame->fetch()) {
            $row = $PgGame->row;
            $hero_info = $Hero->getFreeHeroInfo($row['lord_hero_pk']);
            $result_arr['dst_posi_info']['title'] = $row['title'];
        }
    } else if ($dst_posi['type'] == 'N') {
        // 황건장수 정보
        $PgGame->query('SELECT captain_hero_pk FROM position_npc WHERE posi_pk = $1', [$params['posi_pk']]);
        $captain_hero_pk = $PgGame->FetchOne();
        $hero_info = $Hero->getFreeHeroInfo($captain_hero_pk);
    } else if ($dst_posi['type'] == 'D') {
        // 황건적 거점 장수 정보
        $PgGame->query('SELECT hero_pk FROM suppress a, suppress_position b WHERE a.supp_pk = b.supp_pk AND b.posi_pk = $1 AND a.lord_pk = $2 AND b.status = $3', [$params['posi_pk'], $Session->lord['lord_pk'], 'N']);
        $hero_pk = $PgGame->FetchOne();
        $hero_info = $Hero->getFreeHeroInfo($hero_pk);
    } else if ($dst_posi['type'] == 'P') {
        $PgGame->query('SELECT status, lord_pk, troo_pk, captain_hero_pk, director_hero_pk, staff_hero_pk, date_part(\'epoch\', occu_dt)::integer as occu_dt FROM position_point WHERE posi_pk = $1', [$params['posi_pk']]);
        $PgGame->fetch();
        $row = $PgGame->row;

        $result_arr['occupation_date'] = $row['occu_dt'];

        if ($dst_posi['lord_pk'] > 1) {
            $PgGame->query('SELECT captain_hero_pk, director_hero_pk, staff_hero_pk, arrival_dt FROM troop WHERE src_lord_pk = $1 AND dst_posi_pk = $2 AND status = $3 ORDER BY troo_pk LIMIT 1', [$dst_posi['lord_pk'], $params['posi_pk'], 'C']);
            $PgGame->fetch();
        }
        // $hero_info = $Hero->getFreeHeroInfo($PgGame->row['captain_hero_pk']); 요충지라면 정보 블라인드
    } else {
        if (isset($result_arr['dst_posi_info']['posi_pk'])) {
            $PgGame->query('select lord_hero_pk, title from territory where posi_pk = $1', [$result_arr['dst_posi_info']['posi_pk']]);
            if ($PgGame->fetch()) {
                $hero_info = $Hero->getFreeHeroInfo($PgGame->row['lord_hero_pk']);
                $result_arr['dst_posi_info']['title'] = $PgGame->row['title'];
            }
        }
    }

    if ($hero_info != null) {
        $result_arr['terr_hero'] = [
            'm_hero_pk' => $hero_info['m_hero_pk'],
        ];
    }

    return $Render->nsXhrReturn('success', null, $result_arr);
}));


// TODO 선전포고 사용 안함.
/*$app->post('/api/world/occupationInformation', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 선전포고 할 수 있는 영지인지 검사
	// 1. 이미 선전포고한 영지 인지 검사
	// 2. type이 'T'인지 확인
	// 3. 적대 동맹이거나 관계없는지 확인

    $PgGame->query('SELECT def_posi_pk FROM occupation_inform WHERE att_posi_pk = $1 AND def_posi_pk = $2', [$params['posi_pk'], $params['def_posi_pk']]);
    if ($PgGame->fetchOne() == $params['def_posi_pk']) {
        throw new ErrorHandler('error', '이미 선전포고한 영지입니다.');
    }

    $PgGame->query('SELECT lord_pk, type FROM position WHERE posi_pk = $1', [$params['def_posi_pk']]);
    $PgGame->fetch();
    $row = $PgGame->row;
    if ($row['type'] != 'T') {
        throw new ErrorHandler('error', '선전포고는 영지만 가능합니다.');
    }

    // 해당 영지 동맹
    $PgGame->query('SELECT alli_pk FROM lord WHERE lord_pk = $1', [$row['lord_pk']]);
    $alli_pk = $PgGame->fetchOne();

    // 내 동맹
    $PgGame->query('SELECT alli_pk FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $my_alli_pk = $PgGame->fetchOne();

    // 동맹 여부 체크
    if ($alli_pk) {
        if ($alli_pk == $my_alli_pk) {
            throw new ErrorHandler('error', '동맹원에게는 선전포고를 할 수 없습니다.');
        } else {
            $PgGame->query('SELECT rel_type FROM alliance_relation WHERE alli_pk = $1 AND rel_alli_pk = $2', Array($my_alli_pk, $alli_pk));
            if ($PgGame->getNumRows() == 1) {
                // 관계 설정 되어 있음.
                $relation = $PgGame->fetchOne();
                if ($relation == 'F' || $relation == 'N') {
                    throw new ErrorHandler('error', '중립이나 우호 동맹에는 선전포고를 할 수 없습니다.');
                }
            }
        }
    }

    // 선전포고
    $PgGame->query('INSERT INTO occupation_inform (att_posi_pk, def_posi_pk, att_lord_pk, def_lord_pk, regist_dt) VALUES ($1, $2, $3, $4, now())', [$params['posi_pk'], $params['def_posi_pk'], $Session->lord['lord_pk'], $row['lord_pk']]);
    $occu_pk = $PgGame->currSeq('occupation_inform_occu_pk_seq');

    // 타이머 등록(3일후 삭제됨)
    $Timer = new Timer($Session, $PgGame);

    // 공격측
    $Timer->set($params['posi_pk'], 'O', $occu_pk, 'Y', 'attack:'.$params['def_posi_pk'], OCCUPATION_INFORM_PERIOD + OCCUPATION_INFORM_READY);
    $att_time_pk = $Timer->getTimePk();

    // 방어측
    $Timer->set($params['def_posi_pk'], 'O', $occu_pk, 'N', 'defence:'.$params['posi_pk'], OCCUPATION_INFORM_PERIOD + OCCUPATION_INFORM_READY, null, $row['lord_pk']);
    $def_time_pk = $Timer->getTimePk();

    $PgGame->query('UPDATE occupation_inform SET att_time_pk = $2, def_time_pk = $3 WHERE occu_pk = $1', [$occu_pk, $att_time_pk, $def_time_pk]);

    // 이미지 갱신을 위한 position update
    $PgGame->query('UPDATE position SET last_update_dt = now() WHERE posi_pk IN ($1, $2)', [$params['posi_pk'], $params['def_posi_pk']]);

    // 외교서신 전달
    // 공격측
    $letter = [];
    $letter['type'] = 'S';
    $letter['title'] = '타 군주의 영지에 점령선포를 하였습니다!';
    $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$row['lord_pk']]);
    $lord_name = $PgGame->fetchOne();
    $str = '<strong>['.$lord_name.'] 군주의 ' . $params['def_posi_name'] . '</strong>영지에 점령선포를 하였습니다.<br/ ><br />점령선포 이후 12시간이 지나면 점령 가능 상태로 전환됩니다.<br />점령 가능 상태에선 상대의 영지를 뺏을 수 있습니다.<br />점령 가능 상태는 72시간동안 지속됩니다.<br /><br />';

    $PgGame->query('SELECT regist_dt + interval \'12 hours\' as regist_dt, regist_dt + interval \'84 hours\' as end_dt FROM occupation_inform WHERE occu_pk = $1', [$occu_pk]);
    $PgGame->fetch();
    $start_dt = substr($PgGame->row['regist_dt'], 0, 19);
    $end_dt = substr($PgGame->row['end_dt'], 0, 19);
    $str .= '점령 가능 시간 : ' . $start_dt . ' ~ ' . $end_dt . '<br /><br />귀하의 승리를 기원합니다.';
    $letter['content'] = $str;

    $Letter = new Letter($Session, $PgGame);
    $Letter->sendLetter(ADMIN_LORD_PK, [$Session->lord['lord_pk']], $letter, true, 'Y');

    // 방어측
    $letter = [];
    $letter['type'] = 'S';
    $letter['title'] = '귀하의 영지가 점령선포를 받았습니다!';
    $str = <<< EOF
<strong>{$params['def_posi_name']}</strong>영지가  [<strong>{$params['att_lord_name']}</strong>]({$params['posi_pk']}) 군주에게 점령선포를 당했습니다.

점령선포 이후 12시간이 지나면 점령 가능 상태로 전환됩니다.
점령 가능 상태에선 상대에게 영지를 뺏길 수 있습니다.
점령 가능 상태는 72시간동안 지속됩니다.

점령 가능 시간 : {$start_dt} ~ {$end_dt}

전투만이 최선은 아닙니다. 서신을 보내 화친을 하거나 평화서약, 영지이동을 통해
평화를 지속할 수도 있습니다.
귀하의 선전을 기원합니다.
EOF;

    $letter['content'] = $str;
    $Letter->sendLetter(ADMIN_LORD_PK, [$row['lord_pk']], $letter, true, 'Y');

    $Session->sqAppend('PUSH', ['OCCUPATION_INFORM_TOAST' => true], null, $row['lord_pk']);

    $Log = new Log($Session, $PgGame);
    $Log->setTerritory($Session->lord['lord_pk'], $params['posi_pk'], 'OccupationInform', 'time_info:start_dt['.$start_dt.';end_dt['.$end_dt.'];def_info:posi_pk['.$params['def_posi_pk'].'];lord_pk['.$row['lord_pk'].';');

    return $Render->nsXhrReturn('success');
}));*/


$app->post('/api/world/occupationValley', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT tv.valley_posi_pk, p.type, p.level, p.current_point, date_part(\'epoch\', p.update_point_dt), t.troo_pk, t.captain_hero_pk, h.m_hero_pk FROM territory_valley as tv
	                            LEFT JOIN position AS p ON tv.valley_posi_pk = p.posi_pk LEFT OUTER JOIN troop AS t ON tv.valley_posi_pk = t.dst_posi_pk LEFT OUTER JOIN hero AS h ON h.hero_pk = t.captain_hero_pk WHERE p.lord_pk = $1', [$Session->lord['lord_pk']]);
    $PgGame->fetchAll();
    $valleys = $PgGame->rows;

    return $Render->nsXhrReturn('success', null, $valleys);
}));


$app->post('/api/world/valleyDetail', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $current_posi_pk = $params['target_posi_pk'] . ':';
    //상세 정보 요청하기
    $PgGame->query("SELECT posi_pk, lord_name, power, fame, type, current_point, date_part('epoch', update_point_dt)::integer as update_point_dt, title FROM getpositionsdetail('$current_posi_pk', 0, 1, 1)");
    $PgGame->fetch();

    return $Render->nsXhrReturn('success', null, $PgGame->row);
}));