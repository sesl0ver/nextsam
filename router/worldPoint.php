<?php
global $app, $Render, $i18n;

$app->post('/api/worldPoint/get', $Render->wrap(function (array $params) use ($Render, $i18n) {

    // TODO 굳이 분리할 필요가 없어보여 /api/world/detail 쪽에서 요충지 정보를 보여주게끔 소스 개선함. 하여 사용안함.
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 1. 요충지 할당된 영웅
    // 2. 점령 군주 정보
    // 3. 현재 내 순위
    // 4. 남은 시간

    $PgGame->query('SELECT status, lord_pk, troo_pk, captain_hero_pk, director_hero_pk, staff_hero_pk, date_part(\'epoch\', end_dt)::integer as end_dt, date_part(\'epoch\', now())::integer as now_dt FROM position_point WHERE posi_pk = $1', [$params['posi_pk']]);
    $PgGame->fetch();
    $dst_info = $PgGame->row;
    $result_arr['lord']['pk'] = 1;

    // 점령군주 정보
    $Hero = new Hero($Session, $PgGame);
    if ($dst_info['lord_pk'] > 1) {
        $PgGame->query('SELECT captain_hero_pk, director_hero_pk, staff_hero_pk, arrival_dt FROM troop WHERE src_lord_pk = $1 AND dst_posi_pk = $2 AND status = $3 ORDER BY troo_pk ASC LIMIT 1', [$dst_info['lord_pk'], $params['posi_pk'], 'C']);
        $PgGame->fetch();
        $row = $PgGame->row;

        $result_arr['hero'] = $Hero->getFreeHeroInfo($row['captain_hero_pk']);
        $result_arr['lord']['pk'] = ($dst_info['lord_pk'] == $Session->lord['lord_pk']) ? 3 : 4;
        $result_arr['lord']['arrival_dt'] = $row['arrival_dt'];
    } else {
        $result_arr['hero'] = $Hero->getFreeHeroInfo($dst_info['captain_hero_pk']);
    }

    // TODO 남은 시간(진행 안되는 시간엔 값 보내지 않음)
    $result_arr['remain_time'] = $dst_info['end_dt'] - $dst_info['now_dt'];

    return $Render->nsXhrReturn('success', null, $result_arr);
}));

// TODO my_rank 포함.
$app->post('/api/worldPoint/rank', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $cnt = 1;
    $PgGame->query('SELECT t1.rank, t2.sum_point, t2.regist_dt, t2.lord_pk FROM ranking_point_last_week t1 RIGHT JOIN 
(SELECT sum(occu_point + bonus_point) as sum_point, min(regist_dt) AS regist_dt, lord_pk
FROM ranking_point GROUP BY lord_pk HAVING sum(occu_point + bonus_point) > 0
ORDER BY sum_point DESC, regist_dt LIMIT 10) t2 ON t1.lord_pk = t2.lord_pk');
    $PgGame->fetchAll();
    $rows = $PgGame->rows;

    $result_arr = [];
    foreach ($rows AS $k => $v) {
        $result_arr['rank'][$cnt] = $v;
        unset($result_arr['rank'][$cnt]['lord_pk']);

        $PgGame->query('SELECT COUNT(posi_pk) as cnt FROM position_point WHERE lord_pk = $1', [$v['lord_pk']]);
        $result_arr['rank'][$cnt]['cnt'] = $PgGame->fetchOne();

        $cnt++;
    }

    // 내정보
    $my_point = null;
    $my_regist_dt = null;
    $PgGame->query('SELECT SUM(occu_point + bonus_point) AS sum_point, date_part(\'epoch\', min(regist_dt))::integer AS regist_dt FROM ranking_point WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    if ($PgGame->fetch()) {
        $my_point = $PgGame->row['sum_point'];
        $my_regist_dt = $PgGame->row['regist_dt'];
        $result_arr['my_point'] = $my_point;
    }

    if ($my_point) {
        $PgGame->query('SELECT sum(occu_point + bonus_point) as sum_point, min(regist_dt) AS regist_dt, lord_pk
FROM ranking_point GROUP BY lord_pk HAVING sum(occu_point + bonus_point) > $1 ORDER BY sum_point DESC, regist_dt', [$result_arr['my_point']]);
        // 나보다 랭킹 높은것 개수
        $up_ranker = $PgGame->fetchAll();

        $PgGame->query('SELECT sum(occu_point + bonus_point) as sum_point, date_part(\'epoch\', min(regist_dt))::integer AS regist_dt, lord_pk
FROM ranking_point GROUP BY lord_pk having sum(occu_point + bonus_point) = $1', [$result_arr['my_point']]);
        $PgGame->fetchAll();
        $rows = $PgGame->rows;

        if ($rows) {
            foreach($rows AS $k => $v){
                if ($v['lord_pk'] != $Session->lord['lord_pk'] && $my_regist_dt > $v['regist_dt']) {
                    $up_ranker++;
                }
            }
        }

        $result_arr['my_rank'] = $up_ranker + 1;
    } else {
        $result_arr['my_rank'] = '-';
        $result_arr['my_point'] = 0;
    }

    $PgGame->query('SELECT point_coin FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $result_arr['my_coin']= $PgGame->fetchOne();


    $PgGame->query('SELECT posi_pk, lord_pk, (date_part(\'epoch\', now())::integer - date_part(\'epoch\', last_batt_dt)::integer) as battle_dt FROM position_point');
    while($PgGame->fetch()) {
        $result_arr['point'][$PgGame->row['posi_pk']]['_type'] = 'P';
        $result_arr['point'][$PgGame->row['posi_pk']]['_posi_pk'] = $PgGame->row['posi_pk'];
        $result_arr['point'][$PgGame->row['posi_pk']]['_level'] = 1;
        $result_arr['point'][$PgGame->row['posi_pk']]['occu'] = 'N';
        $result_arr['point'][$PgGame->row['posi_pk']]['my_point'] = 'N';

        if ($PgGame->row['lord_pk'] > 1) {
            $result_arr['point'][$PgGame->row['posi_pk']]['occu'] = 'Y';
            if ($PgGame->row['lord_pk'] == $Session->lord['lord_pk']) {
                $result_arr['point'][$PgGame->row['posi_pk']]['my_point'] = 'Y';
            }
        }

        if ((INT)$PgGame->row['battle_dt'] > 0 && $PgGame->row['battle_dt'] <= POINT_BATTLE_TIME) {
            $result_arr['point'][$PgGame->row['posi_pk']]['battle'] = 'Y';
        }
    }

    return $Render->nsXhrReturn('success', null, $result_arr);
}));

$app->post('/api/worldPoint/troop', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $result_arr = [];
    $PgGame->query('SELECT COUNT(dst_posi_pk) FROM troop WHERE dst_posi_pk = $1 AND status = $2', [$params['posi_pk'], 'M']);
    $result_arr['troop_cnt'] = $PgGame->fetchOne();

    $cnt = 0;
    $PgGame->query("SELECT src_lord_pk, captain_desc, date_part('epoch', arrival_dt)::integer as end_dt
FROM troop WHERE dst_posi_pk = $1 AND status = 'M' ORDER BY arrival_dt LIMIT 10", [$params['posi_pk']]);
    while($PgGame->fetch()) {
        $result_arr['troop'][$cnt] = $PgGame->row;
        $result_arr['troop'][$cnt]['src_lord_pk'] = ($result_arr['troop'][$cnt]['src_lord_pk'] == $Session->lord['lord_pk']) ? 3 : 4;
        $cnt++;
    }

    return $Render->nsXhrReturn('success', null, $result_arr);
}));

$app->post('/api/worldPoint/getHero', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT lord_pk, captain_desc, director_desc, staff_desc FROM position_point WHERE posi_pk = $1', [$params['posi_pk']]);
    $PgGame->fetch();
    $result_arr = $PgGame->row;

    if ($result_arr['lord_pk'] > 1) {
        $PgGame->query('SELECT src_lord_pk as lord_pk, captain_desc, director_desc, staff_desc, arrival_dt FROM troop WHERE src_lord_pk = $1 AND dst_posi_pk = $2', [$result_arr['lord_pk'], $params['posi_pk']]);
        $PgGame->fetch();
        $result_arr = $PgGame->row;

        $PgGame->query('SELECT sum(occu_point + bonus_point) as sum_point FROM ranking_point WHERE lord_pk = $1 GROUP BY lord_pk', [$result_arr['lord_pk']]);
        $result_arr['sum_point'] = $PgGame->fetchOne();
        $result_arr['lord_pk'] = ($result_arr['lord_pk'] == $Session->lord['lord_pk']) ? 3 : 4;
    }

    return $Render->nsXhrReturn('success', null, $result_arr);
}));

$app->post('/api/worldPoint/giveup', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $r = $PgGame->query('SELECT lord_pk FROM position_point WHERE posi_pk = $1 AND lord_pk = $2', [$params['posi_pk'], $Session->lord['lord_pk']]);
    if (!$r || !$PgGame->fetchOne()) {
        throw new ErrorHandler('error', 'Error Occurred. [40001]'); // 해당 요충지 포기 실패
    }

    // 요충지 포기 처리
    $Troop = new Troop($Session, $PgGame);
    $Troop->lossOwnershipPoint($Session->lord['lord_pk'], $params['posi_pk'], 'Y');
    // NPC 생성
    $Troop->setNpcPoint($params['posi_pk']);
    // 부대 철수
    $PgGame->query('SELECT troo_pk FROM troop WHERE src_lord_pk = $1 AND dst_posi_pk = $2 AND status = $3', [$Session->lord['lord_pk'], $params['posi_pk'], 'C']);
    $PgGame->fetchAll();
    $rows = $PgGame->rows;
    foreach ($rows AS $k => $v) {
        $Troop->setStatusRecall($v['troo_pk']);
    }

    return $Render->nsXhrReturn('success');
}));
