<?php
global $app, $Render, $i18n;

// TODO 사용하지 않는 API

$app->post('/api/heroSalary/info', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $today = mktime(0, 5, 1);
    $now_hour = date('G');
    $now_minutes = date('i');

    if ($now_hour == 0 && $now_minutes <= 10) {
        throw new ErrorHandler('error', '자정(0시0분)부터 0시10분까지 10분간<br /><br />급여정산 중인 관계로 지급이 불가능 합니다.');
    }

    $PgGame->query('SELECT date_part(\'epoch\', last_salary_dt)::integer AS last_salary_dt_ut FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $last_salary_dt_ut = $PgGame->fetchOne();

    // 이미 지급 했음.
    if ($last_salary_dt_ut >= $today) {
        throw new ErrorHandler('error', '이미 금일 영웅급여를 지급 하셨습니다.<br /><br />급여지급은 자정을 기준으로 매일 1회만 가능 합니다.');
    }

    /*
    $sql = <<< EOF
    SELECT SUM(200-(t1.m_offi_pk-110001)/8*20)+SUM(10+((t2.level+t2.rare_type::smallint-2)*10))
    FROM my_hero t1, hero t2
    WHERE t1.hero_pk = t2.hero_pk AND t1.lord_pk = $1 AND t1.status = 'A' AND t2.loyalty BETWEEN 1 AND 99
    EOF;
    */
    /*$sql = <<< EOF
    SELECT SUM(tb1.employment_fee+tb2.level_fee)
    FROM m_officer tb1,
    (
      SELECT t1.m_offi_pk, (10+((t2.level+t2.rare_type::smallint-2)*10)) AS level_fee
      FROM my_hero t1, hero t2
      WHERE t1.hero_pk = t2.hero_pk AND t1.lord_pk = $1 AND t1.status = 'A' AND t2.loyalty BETWEEN 1 AND 99
    ) tb2
    WHERE tb1.m_offi_pk = tb2.m_offi_pk
    EOF;
    SELECT SUM(tb1.employment_fee+tb2.level_fee)
    FROM m_officer tb1,
    (
      SELECT t1.m_offi_pk, ((pow(2,(t2.rare_type::smallint-1)) * 10) + ((t2.level-1) * 20)) AS level_fee
      FROM my_hero t1, hero t2
      WHERE t1.hero_pk = t2.hero_pk AND t1.lord_pk = $1 AND t1.status = 'A' AND t2.loyalty BETWEEN 1 AND 99
    ) tb2
    WHERE tb1.m_offi_pk = tb2.m_offi_pk
    EOF;
    */

    $PgGame->query("SELECT SUM(tb1.employment_fee) FROM m_officer tb1,
(SELECT t1.m_offi_pk FROM my_hero t1, hero t2 WHERE t1.hero_pk = t2.hero_pk AND t1.lord_pk = $1 AND t1.status = 'A' AND t2.loyalty BETWEEN 1 AND 99) tb2
WHERE tb1.m_offi_pk = tb2.m_offi_pk", [$Session->lord['lord_pk']]);
    $salary = $PgGame->fetchOne();
    $salary += 10; // TODO ?

    return $Render->nsXhrReturn('success', null, ['salary' => $salary]);
}));

$app->post('/api/heroSalary/pay', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $today = mktime(0, 5, 1);
    $now_hour = date('G');
    $now_minutes = date('i');

    if ($now_hour == 0 && $now_minutes <= 10) {
        throw new ErrorHandler('error', '자정(0시0분)부터 0시10분까지 10분간<br /><br />급여정산 중인 관계로 지급이 불가능 합니다.');
    }

    $PgGame->query('SELECT date_part(\'epoch\', last_salary_dt)::integer AS last_salary_dt_ut FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
    $last_salary_dt_ut = $PgGame->fetchOne();

    // 이미 지급 했음.
    if ($last_salary_dt_ut >= $today) {
        throw new ErrorHandler('error', '이미 금일 영웅급여를 지급 하셨습니다.<br /><br />급여지급은 자정을 기준으로 매일 1회만 가능 합니다.');
    }

    /*
    $sql = <<< EOF
    SELECT SUM(200-(t1.m_offi_pk-110001)/8*20)+SUM(10+((t2.level+t2.rare_type::smallint-2)*10))
    FROM my_hero t1, hero t2
    WHERE t1.hero_pk = t2.hero_pk AND t1.lord_pk = $1 AND t1.status = 'A' AND t2.loyalty BETWEEN 1 AND 99
    EOF;
    */
    /*$sql = <<< EOF
    SELECT SUM(tb1.employment_fee+tb2.level_fee)
    FROM m_officer tb1,
    (
      SELECT t1.m_offi_pk, (10+((t2.level+t2.rare_type::smallint-2)*10)) AS level_fee
      FROM my_hero t1, hero t2
      WHERE t1.hero_pk = t2.hero_pk AND t1.lord_pk = $1 AND t1.status = 'A' AND t2.loyalty BETWEEN 1 AND 99
    ) tb2
    WHERE tb1.m_offi_pk = tb2.m_offi_pk
    EOF;
    SELECT SUM(tb1.employment_fee+tb2.level_fee)
    FROM m_officer tb1,
    (
      SELECT t1.m_offi_pk, ((pow(2,(t2.rare_type::smallint-1)) * 10) + ((t2.level-1) * 20)) AS level_fee
      FROM my_hero t1, hero t2
      WHERE t1.hero_pk = t2.hero_pk AND t1.lord_pk = $1 AND t1.status = 'A' AND t2.loyalty BETWEEN 1 AND 99
    ) tb2
    WHERE tb1.m_offi_pk = tb2.m_offi_pk
    EOF;
    */

    $PgGame->query("SELECT SUM(tb1.employment_fee) FROM m_officer tb1,
(SELECT t1.m_offi_pk FROM my_hero t1, hero t2 WHERE t1.hero_pk = t2.hero_pk AND t1.lord_pk = $1 AND t1.status = 'A' AND t2.loyalty BETWEEN 1 AND 99) tb2
WHERE tb1.m_offi_pk = tb2.m_offi_pk", [$Session->lord['lord_pk']]);
    $salary = $PgGame->fetchOne();
    $salary += 10; // TODO ?


    $GoldPop = new GoldPop($Session, $PgGame);
    // 차감 오류
    if (!$GoldPop->decreaseGold($params['posi_pk'], $salary, null, 'hero_salary')) {
        throw new ErrorHandler('error', '급여지급 중 오류가 발생했습니다.');
    }

    $PgGame->query('UPDATE lord SET last_salary_dt = now() WHERE lord_pk = $1', [$Session->lord['lord_pk']]);


    // Log
    $Log = new Log($Session, $PgGame);
    $Log->setHeroSalary($Session->lord['lord_pk'], $params['posi_pk'], 'hero_salary', $salary);

    $Quest = new Quest($Session, $PgGame);
    $Quest->conditionCheckQuest($Session->lord['lord_pk'], ['quest_type' => 'hero_salary', 'm_ques_pk' => '600101']);

    $HeroSkill = new HeroSkill($Session, $PgGame);
    $HeroSkill->updateSalarySkillExp($Session->lord['lord_pk']);

    // 타임 이벤트 시작
    /*
    $query_params = Array($Session->lord['lord_pk']);
    $PgGame->query('SELECT time_buff_type FROM my_event WHERE lord_pk = $1', $query_params);
    if ($PgGame->fetchOne()) {
        $query_params = Array('Y', $Session->lord['lord_pk']);
        $PgGame->query('UPDATE my_event SET last_event_dt = now(), time_buff_type = $1 WHERE lord_pk = $2', $query_params);
    } else {
        $query_params = Array('Y', $Session->lord['lord_pk']);
        $PgGame->query('INSERT INTO my_event (lord_pk, last_event_dt, time_buff_type) VALUES ($2, now(), $1)', $query_params);
    }

    $Session->sqAppend('PUSH', Array('TIME_EVENT_STATR' => true), null, $Session->lord['lord_pk'], $params['posi_pk']);
    */

    return $Render->nsXhrReturn('success');
}));
