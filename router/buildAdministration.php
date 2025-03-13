<?php
global $app, $Render, $i18n;

$app->post('/api/administration/prod', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session(true, $params['posi_pk']);
    $PgGame = new Pg('DEFAULT');

    $PgGame->query("SELECT food_providence, horse_providence, lumber_providence, iron_providence, food_labor_force_curr, food_production_territory, food_production_valley, food_pct_plus_tech, food_pct_plus_hero_assign, food_pct_plus_hero_skill, food_pct_plus_item,
horse_labor_force_curr, horse_production_territory, horse_production_valley, horse_pct_plus_tech, horse_pct_plus_hero_assign, horse_pct_plus_hero_skill, horse_pct_plus_item, 
lumber_labor_force_curr, lumber_production_territory, lumber_production_valley, lumber_pct_plus_tech, lumber_pct_plus_hero_assign, lumber_pct_plus_hero_skill, lumber_pct_plus_item,
iron_labor_force_curr, iron_production_territory, iron_production_valley, iron_pct_plus_tech, iron_pct_plus_hero_assign, iron_pct_plus_hero_skill, iron_pct_plus_item
FROM production
WHERE posi_pk = \$1", [$params['posi_pk']]);
    $PgGame->fetch();

    return $Render->nsXhrReturn('success', null, $PgGame->row);
}));

$app->post('/api/administration/valley', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session(true, $params['posi_pk']);
    $PgGame = new Pg('DEFAULT');

    $r = [];
    $r_map = [];

    $PgGame->query('select b.type, a.valley_posi_pk, b.level from territory_valley a, position b where a.valley_posi_pk = b.posi_pk and a.posi_pk = $1 order by a.regist_dt desc', [$params['posi_pk']]);

    $valley_posi_pks = [];
    while($PgGame->fetch()) {
        $k = $PgGame->row['valley_posi_pk'];
        $valley_posi_pks[] = $k;
        $r[] = $PgGame->row;
        $r_map[$k] = COUNT($r) - 1;
    }

    if (count($valley_posi_pks) > 0) {
        $PgGame->query('SELECT t1.troo_pk, t1.dst_posi_pk, t1.captain_desc, date_part(\'epoch\', t1.withdrawal_dt)::integer as withdrawal_dt, t2.m_hero_pk
FROM troop t1, hero t2 WHERE t1.src_lord_pk = $1 AND t1.dst_posi_pk = ANY($2) and t1.status = $3 AND t1.captain_hero_pk = t2.hero_pk', [$Session->lord['lord_pk'], '{'. implode(',', $valley_posi_pks). '}', 'C']);

        while($PgGame->fetch()) {
            $k = $PgGame->row['dst_posi_pk'];
            $r[$r_map[$k]]['m_hero_pk'] = $PgGame->row['m_hero_pk'];
            $r[$r_map[$k]]['captain_desc'] = $PgGame->row['captain_desc'];
            $r[$r_map[$k]]['withdrawal_dt'] = $PgGame->row['withdrawal_dt'];
            $r[$r_map[$k]]['troo_pk'] = $PgGame->row['troo_pk'];
        }
    }

    return $Render->nsXhrReturn('success', null, $r);
}));

$app->post('/api/administration/terr', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session(true, $params['posi_pk']);
    $PgGame = new Pg('DEFAULT');

    $PgGame->query('SELECT t2.title, t2.posi_pk, t2.lord_hero_pk, t2.population_curr, t2.loyalty FROM position AS t1, territory AS t2
WHERE t1.lord_pk = $1 AND t1.posi_pk = t2.posi_pk ORDER BY title::BYTEA, t2.posi_pk', [$Session->lord['lord_pk']]);
    $PgGame->fetchAll();
    $rows = $PgGame->rows;

    $r = [];
    $r_map = [];

    $GoldPop = new GoldPop($Session, $PgGame);

    $lord_hero_pks = [];
    foreach($rows AS $k => $v) {
        $pk = $v['lord_hero_pk'];
        $lord_hero_pks[] = $pk;

        // 인구
        if ($params['posi_pk'] != $v['posi_pk']) {
            $z = $GoldPop->get($v['posi_pk'], 99999999); // 데이터만 필요해서...
            if ($z) {
                $v['population_curr'] = intval($z['population_curr']);
            }
        }

        $r[] = $v;
        $r_map[$pk] = COUNT($r) - 1;
    }

    if (count($lord_hero_pks) > 0) {
        $PgGame->query('select hero_pk, m_hero_pk, level from hero where hero_pk = ANY($1)', ['{'. implode(',', $lord_hero_pks). '}']);

        while($PgGame->fetch()) {
            $k = $PgGame->row['hero_pk'];
            $r[$r_map[$k]]['m_hero_pk'] = $PgGame->row['m_hero_pk'];
            $r[$r_map[$k]]['level'] = $PgGame->row['level'];
        }
    }

    return $Render->nsXhrReturn('success', null, $r);
}));

$app->post('/api/administration/adjustment', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session(true, $params['posi_pk']);
    $PgGame = new Pg('DEFAULT');

    if ($params['tax_rate'] > 50 || $params['tax_rate'] < 0) {
        throw new ErrorHandler('error', $i18n->t('msg_administration_tax_rate_set_limit')); // 세율은 최소 0%에서 최대 50%까지 가능합니다.
    }

    if(! $PgGame->query('UPDATE territory SET tax_rate = $1 WHERE posi_pk = $2', [$params['tax_rate'], $params['posi_pk']])) {
        throw new ErrorHandler('error', $i18n->t('Update failed')); // 업데이트 실패
    }

    $Session->sqAppend( 'TERR', ['tax_rate' => $params['tax_rate']]);

    $GoldPop = new GoldPop($Session, $PgGame);
    $GoldPop->get($params['posi_pk']);

    if ($params['tax_rate'] == 20) {
        //퀘스트 체크
        $Quest = new Quest($Session, $PgGame);
        $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'administration','quest_kind' => 'tax_rate']);
    }

    // Log
    $Log = new Log($Session, $PgGame);
    $Log->setBuildingAdministration($Session->lord['lord_pk'], $params['posi_pk'], 'tax_rate', $params['tax_rate']);

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/administration/comforting', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session(true, $params['posi_pk']);
    $PgGame = new Pg('DEFAULT');

    //제한시간 안 지났을경우 error
    $PgGame->query('SELECT population_curr, loyalty, date_part(\'epoch\', comforting_up_dt)::integer as last_up_dt, date_part(\'epoch\', now())::integer - date_part(\'epoch\', comforting_up_dt)::integer as remain_dt FROM territory WHERE posi_pk = $1', [$params['posi_pk']]);
    $PgGame->fetch();

    $remain_dt = $PgGame->row['remain_dt'];

    if($remain_dt && $remain_dt < 900 ) {
        return $Render->nsXhrReturn('success', null, ['remain_dt' => $remain_dt]);
    }

    $selected_type = $params['selected'];
    $need_gold = 0;
    $need_food = 0;
    $need_lumber = 0;
    $population_curr = $PgGame->row['population_curr'];

    $loyalty = 0;
    if ($selected_type == 'redress') { // 구휼
        $need_food = $population_curr * 2.5;
        $loyalty = $PgGame->row['loyalty'] + 5;
    } else if ($selected_type == 'ritual') { // 천제
        $need_gold = $population_curr * 2;
        $need_food = $population_curr * 1.5;
        $loyalty = $PgGame->row['loyalty'] + 10;
    } else if ($selected_type == 'prevention_disasters') { // 재해예방
        $need_food = $population_curr * 2.3;
        $need_lumber = $population_curr * 3;
        $loyalty = $PgGame->row['loyalty'] + 15;
    }

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);

    global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
    try {
        $PgGame->begin();

        $_NS_SQ_REFRESH_FLAG = true;

        if ($need_gold > 0) {
            $r = $GoldPop->decreaseGold($params['posi_pk'], $need_gold, null, 'comforting');
            if ($r === false) {
                throw new Exception($i18n->t('msg_resource_gold_lack')); // 황금이 부족합니다.
            }
        }

        if ($need_food > 0 || $need_lumber > 0) {
            $res = [];
            $res['food'] = $need_food;
            $res['lumber'] = $need_lumber;

            $r = $Resource->decrease($params['posi_pk'], $res, null, 'comforting');
            if ($r === false) {
                throw new Exception($i18n->t('msg_resource_lack')); // 자원이 부족합니다.
            }
        }

        if ($loyalty > 100) {
            $loyalty = 100;
        }

        $PgGame->query('UPDATE territory SET loyalty = $1, comforting_up_dt = now() WHERE posi_pk = $2', [$loyalty, $params['posi_pk']]);

        $PgGame->commit();
    } catch (Exception $e) {
        // 실패, sq 무시
        $PgGame->rollback();
        throw new ErrorHandler('error', $e->getMessage(), true);
    }

    // 처리 완료후 호출해야 할 함수와 sq 처리 작업
    $_NS_SQ_REFRESH_FLAG = false;
    $NsGlobal->commitComplete();

    $Session->sqAppend( 'TERR', ['loyalty' => $loyalty, 'comforting_up_dt' => time()]);

    // Log
    $Log = new Log($Session, $PgGame);
    $Log->setBuildingAdministration($Session->lord['lord_pk'], $params['posi_pk'], 'comforting', $selected_type.':food['.$res['food'].'];lumber['.$res['lumber'].']');

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'administration','quest_kind' => $selected_type]);

    return $Render->nsXhrReturn('success', null, ['remain_dt' => 0]);
}));

$app->post('/api/administration/requisition', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session(true, $params['posi_pk']);
    $PgGame = new Pg('DEFAULT');

    //제한시간 안 지났을경우 error
    $PgGame->query('SELECT population_curr, loyalty, date_part(\'epoch\', requisition_up_dt)::integer as last_up_dt, date_part(\'epoch\', now())::integer - date_part(\'epoch\', requisition_up_dt)::integer as remain_dt FROM territory WHERE posi_pk = $1', [$params['posi_pk']]);
    $PgGame->fetch();
    $row = $PgGame->row;

    $remain_dt = $PgGame->row['remain_dt'];

    if($remain_dt && $remain_dt < 900) {
        return $Render->nsXhrReturn('success', 'Time limit left.', ['remain_dt' => $remain_dt]);
    }

    if ($row['loyalty'] <= 30) {
        throw new ErrorHandler('error', $i18n->t('msg_low_public_sentiment_requisition_error')); // 민심이 낮아 징발을 할 수 없습니다. (30이하는 징발 불가능)
    }

    $res = [];

    $Resource = new Resource($Session, $PgGame);
    $GoldPop = new GoldPop($Session, $PgGame);

    if ($params['code'] == 'gold') {
        $r = $GoldPop->increaseGold($params['posi_pk'], $row['population_curr'], null, 'requisition');
        if ($r === false) {
            throw new ErrorHandler('error', 'a failure to increase gold');
        }
    } else if ($params['code'] == 'food') {
        $res['food'] = $row['population_curr'] * 2;
    } else if ($params['code'] == 'lumber') {
        $res['lumber'] = $row['population_curr'] * 1.5;
    } else if ($params['code'] == 'horse') {
        $res['horse'] = $row['population_curr'] * 1.3;
    } else if ($params['code'] == 'iron') {
        $res['iron'] = $row['population_curr'] * 0.5;
    }

    $quest_kind = $params['code'];
    $population_curr = $row['population_curr'];

    if ($params['code'] != 'gold') {
        $r = $Resource->increase($params['posi_pk'], $res, null, 'requisition');
        if (! $r) {
            throw new ErrorHandler('error', 'a failure to increase resource');
        }
    }

    $loyalty = $row['loyalty'] - 20;
    if (!$PgGame->query('UPDATE territory SET loyalty = $1, requisition_up_dt = now() WHERE posi_pk = $2', [$loyalty, $params['posi_pk']])) {
        throw new ErrorHandler('error', 'Update failed');
    }

    $Session->sqAppend( 'TERR', ['loyalty' => $loyalty, 'requisition_up_dt' => time()]);

    // Log
    $Log = new Log($Session, $PgGame);
    $Log->setBuildingAdministration($Session->lord['lord_pk'], $params['posi_pk'], 'requisition', $params['code'].'['.$population_curr.']');

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'administration','quest_kind' => $quest_kind]);

    return $Render->nsXhrReturn('success', null, ['remain_dt' => 0]);
}));

$app->post('/api/administration/terrTitle', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session(true, $params['posi_pk']);
    $PgGame = new Pg('DEFAULT');

    //제한시간 안 지났을경우 error
    $PgGame->query('SELECT title, date_part(\'epoch\', title_change_up_dt)::integer as title_change_up_dt, date_part(\'epoch\', now())::integer - date_part(\'epoch\', title_change_up_dt)::integer as remain_dt  FROM territory WHERE posi_pk = $1', [$params['posi_pk']]);
    if (!$PgGame->fetch()) {
        throw new ErrorHandler('error', $i18n->t('msg_not_have_any_territory_error')); // 해당 좌표에는 영지가 없습니다.
    }

    $prev_terr_name = $PgGame->row['title'];
    $last_up_dt = $PgGame->row['title_change_up_dt'];

    $remain_dt = $PgGame->row['remain_dt'];
    if(isset($remain_dt) && $remain_dt < 86400 ) {
        throw new ErrorHandler('error', $i18n->t('msg_change_territory_name_remain_time')); // 이전 영지명 변경 이후 24시간이 지나야 다시 변경 가능합니다.
    }

    // 문자열 입력 검사
    if (preg_match("/[^\x{AC00}-\x{D7A3}0-9a-zA-Z]/u", $params['terr_title']) > 0) {
        throw new ErrorHandler('error', $i18n->t('msg_territory_title_confine_language')); // 영지명은 한글, 영문, 숫자만 사용 가능합니다.
    }

    if (iconv_strlen($params['terr_title'], 'UTF-8') < 1) {
        throw new ErrorHandler('error', $i18n->t('msg_change_territory_title_empty_error')); // 변경할 영지명을 입력해 주십시오.
    } else if (iconv_strlen($params['terr_title'], 'UTF-8') < 2) {
        throw new ErrorHandler('error', $i18n->t('msg_change_territory_title_min_error')); // 영지명은 최소 2글자를 사용해야합니다.
    } else if (iconv_strlen($params['terr_title'], 'UTF-8') > 4) {
        throw new ErrorHandler('error', $i18n->t('msg_change_territory_title_max_error')); // 영지명은 최대 4글자까지 사용할 수 있습니다.
    }

    // 금지어 검사
    $ret = Useful::forbiddenWord($params['terr_title']);
    if (! $ret['ret']) {
        throw new ErrorHandler('error', $i18n->t('msg_alliance_name_unavailable_1', [$ret['str']])); // 입력하신 영지명의 ['.$ret['str'].']은(는) 사용할 수 없습니다.
    }

    // 예약어 검사
    if(! Useful::reservedWord($params['terr_title'])) {
        throw new ErrorHandler('error', $i18n->t('msg_alliance_name_unavailable_2', [$params['terr_title']])); // 입력하신 영지명 ['.$params['terr_title'].']은(는) 사용할 수 없습니다.
    }

    // 중복 검사
    $PgGame->query('SELECT count(posi_pk) FROM territory WHERE title_lower = lower($1)', [$params['terr_title']]);
    if ($PgGame->fetchOne() > 0) {
        throw new ErrorHandler('error', $i18n->t('msg_same_territory_title_error')); // 이미 사용중인 영지명입니다.<br />다른 영지명을 입력해 주십시오.
    }

    if (!$PgGame->query('UPDATE territory SET title = $1, title_lower = lower($2), title_change_up_dt = now() WHERE posi_pk = $3', [$params['terr_title'], $params['terr_title'], $params['posi_pk']])) {
        throw new ErrorHandler('error', 'Update failed');
    }

    $Session->sqAppend( 'TERR', ['title' => $params['terr_title'], 'title_change_up_dt' => time()]);

    // 퀘스트 체크
    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'administration','quest_kind' => 'terr_title']);

    $Log = new Log($Session, $PgGame);
    $Log->setBuildingAdministration($Session->lord['lord_pk'], $params['posi_pk'], 'change_terr_name', $prev_terr_name.';'.$params['terr_title'].';');

    return $Render->nsXhrReturn('success');
}));