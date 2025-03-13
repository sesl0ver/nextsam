<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/terr_info', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    function getPopulationTrend($str): string
    {
        $population_trand = ['U' => '증가', 'S' => '안정', 'D' => '감소'];

        return $population_trand[$str] ?? $str;
    }

    function getQueueType($str): string
    {
        $queue_type = [
            'C' => '건설',
            'I' => '초빙',
            'F' => '방어시설',
            'P' => '강화',
            'T' => '기술개발',
            'E' => '탐색',
            'A' => '훈련',
            'S' => '배달',
            'X' => '전투'
        ];

        return $queue_type[$str] ?? $str;
    }

    function getTimeFromSeconds($seconds)
    {
        $h = sprintf("%02d", intval($seconds) / 3600);
        $tmp = $seconds % 3600;
        $m = sprintf("%02d", $tmp / 60);
        $s = sprintf("%02d", $tmp % 60);

        return $h.'시간 '.$m.'분 '.$s.'초';
    }

    function getBuffType ($str):string
    {
        $BUFF_TYPE = [
            'gold' => '세금',
            'food' => '식량',
            'horse' => '우마',
            'lumber' => '목재',
            'iron' => '철강',
            'population' => '인구',
            'truce' => '영지보호',
            'leadership' => '부대 통솔',
            'attack' => '부대 공격력',
            'defence' => '부대 방어력',
            'queue' => '건설허가서',
            '500052' => '초목개병',
            '500053' => '허장성세',
            '500054' => '공성계',
            'informatics' => '첩보자금',
            'cure' => '부대 치료'];

        return $BUFF_TYPE[$str] ?? $str;
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['hero', 'hero_base', 'army', 'building']);

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');


    $posi_pk = $_SESSION['selected_terr']['posi_pk'];

    if ( empty($posi_pk) )
    {
        return $Render->view(json_encode(['result' => false, 'message'=>'Is exists an error in [selected_terr][posi_pk]', 'data' => null]));
    }

    $sql = "SELECT food_providence, horse_providence, lumber_providence, iron_providence,
	food_labor_force_curr, food_production_territory, food_production_valley,
	food_pct_plus_tech, food_pct_plus_hero_assign, food_pct_plus_hero_skill, food_pct_plus_item,
	horse_labor_force_curr, horse_production_territory, horse_production_valley, horse_pct_plus_tech, horse_pct_plus_hero_assign, horse_pct_plus_hero_skill, horse_pct_plus_item, 
	lumber_labor_force_curr, lumber_production_territory, lumber_production_valley, lumber_pct_plus_tech, lumber_pct_plus_hero_assign, lumber_pct_plus_hero_skill, lumber_pct_plus_item,
	iron_labor_force_curr, iron_production_territory, iron_production_valley, iron_pct_plus_tech, iron_pct_plus_hero_assign, iron_pct_plus_hero_skill, iron_pct_plus_item
FROM production
WHERE posi_pk = $1";

    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetch();

    $terr_info_detail = $PgGame->row;

    $sql = "SELECT
	territory.loyalty, territory.tax_rate, territory.lord_hero_pk, hero.m_hero_pk, territory.population_curr,
	territory.yn_alliance_camp, territory.status_gate,
	territory.storage_max, territory.storage_food_pct, territory.storage_horse_pct, territory.storage_lumber_pct, territory.storage_iron_pct,
	territory.tax_rate_plus_tech, territory.tax_rate_plus_hero_assign, territory.tax_rate_plus_hero_skill, territory.tax_rate_plus_item, territory.tax_rate_plus_amount
FROM territory, hero
WHERE hero.hero_pk = territory.lord_hero_pk AND territory.posi_pk = $1";

    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetch();

    $terr_info_base = $PgGame->row;

    $hero_name = $_M['HERO_BASE'][$_M['HERO'][$terr_info_base['m_hero_pk']]['m_hero_base_pk']]['name'] . ' Lv.' . $_M['HERO'][$terr_info_base['m_hero_pk']]['level'];

    $_data_str = Array('food', 'horse', 'lumber', 'iron');

    $production_per_hour = [];
    foreach($_data_str as $value){
        $production_per_hour += [$value => 0];
        $production_per_hour[$value] = ($terr_info_detail[$value. '_providence'] + $terr_info_detail[$value. '_production_territory'] + $terr_info_detail[$value. '_production_valley']);
        $production_per_hour[$value] *= 1 + (($terr_info_detail[$value. '_pct_plus_tech'] + $terr_info_detail[$value. '_pct_plus_hero_assign'] + $terr_info_detail[$value. '_pct_plus_hero_skill'] + $terr_info_detail[$value. '_pct_plus_item']) / 100);
        $production_per_hour[$value] = floor($production_per_hour[$value]);
    }

    $sql = "select getcurrentresource($1);";
    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetch();
    $current_resource_info = implode($PgGame->row);
    $current_resource_info = explode(',', $current_resource_info, 6);


    $sql = "SELECT gold_curr, gold_production, gold_salary, gold_max FROM gold WHERE posi_pk = $1;";
    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetch();
    $gold_info = $PgGame->row;

    $sql = " SELECT population_max, 
               population_curr,
               population_labor_force,
               loyalty,
               population_trend,
               population_upward_plus_tech,
               population_upward_plus_hero_assign, 
               population_upward_plus_hero_skill,
               population_upward_plus_item, 
               population_upward_plus_amount
        FROM territory
        WHERE posi_pk = $1";

    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetch();
    $population_info = $PgGame->row;

    $population_trend_amount = 500;
    foreach( $population_info as $key => $value ) {
        if ( $key === 'population_trend') {
            $population_info[$key] = getPopulationTrend($value);
            switch($population_info[$key]){
                case 'U':
                    $population_trend_amount = 500 + $population_info['population_upward_plus_tech'] + $population_info['population_upward_plus_hero_assign'] + $population_info['population_upward_plus_hero_skill'] + $population_info['population_upward_plus_item'];
                    break;
                case 'D':
                    $population_trend_amount = -500;
                    break;
                case 'S':
                    $population_trend_amount = 0;
                    break;
            }
        }
    }

    $population_info['population_trend_amount'] = $population_trend_amount;

    $sql = "SELECT time_pk, queue_type, description, build_time, date_part('epoch', start_dt)::integer as start_dt, date_part('epoch', end_dt)::integer as end_dt FROM timer WHERE queue_type <> 'B' AND queue_type <> 'D' AND queue_type <> 'Y' AND status = 'P' AND posi_pk = $1 ORDER BY start_dt DESC";

    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetchAll();
    $timer = $PgGame->rows;

    foreach ($timer as &$row)
    {
        foreach ($row as $key => $value)
        {
            if ($key === 'queue_type') {
                $row[$key] = getQueueType($value);
            }

            if ($key === 'build_time') {
                $row[$key] = getTimeFromSeconds($value);
            }

            if ($key === 'start_dt' || $key === 'end_dt') {
                //$row[$key] = date("Y-m-d H:i:s", $value);
                $row[$key] = date("Y-m-d H:i:s", $value);
            }
        }

        // echo 'console.log("'. explode('', $row) .'");';
    }

    $sql = "SELECT time_pk, queue_type, description, build_time, date_part('epoch', start_dt)::integer as start_dt, date_part('epoch', end_dt)::integer as end_dt FROM timer WHERE (queue_type = 'B' OR queue_type = 'D') AND status = 'P' AND posi_pk = $1 ORDER BY start_dt DESC";
    $PgGame->rows = [];
    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetchAll();
    $buff_timer = $PgGame->rows;

    foreach ($buff_timer as &$_row)
    {
        if (isset($_row['description']))
        {
            $buff = explode(":", $_row['description']);
            $_row['buff'] = getBuffType($buff[1]);
        }
        if ( isset($_row['end_dt'])) {
            $left_time = getTimeFromSeconds($_row['end_dt'] - time());
            $_row['left_time'] = $left_time;
            $_row['end_dt'] = date('Y-m-d H:i:s', $_row['end_dt']);
        }

        if ( isset($_row['start_dt'])){
            $_row['start_dt'] = date('Y-m-d H:i:s', $_row['start_dt']);
        }
    }

    return $Render->view(json_encode(['terr_info_detail'=>$terr_info_detail, 'terr_info_base'=> $terr_info_base, 'production_per_hour'=> $production_per_hour, 'current_resource_info' => $current_resource_info, 'gold_info' => $gold_info, 'population_info' => $population_info, 'timer' => $timer, 'buff_timer' => $buff_timer, 'lord_hero_name' => $hero_name]));
}));


$app->post('/admin/gm/api/territoryNameChange', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $server_pk = $params['server_pk'];

    $Gm = new Gm();
    $Gm->selectPgGame($server_pk);
    $Session = new Session(false);
    $PgGame = new Pg('SELECT');

    if (!isset($_SESSION['selected_terr'])) {
        return $Render->view(json_encode(['result' => false, 'msg' => '선택한 영지없이 진행할 수 없습니다.']));
    }

    if (preg_match("/[^\x{AC00}-\x{D7A3}0-9a-zA-Z]/u", $params['terr_name']) > 0) {
        return $Render->view(json_encode(['result' => false, 'msg' => '영지명은 오로지 한글, 영문, 숫자만 사용해야합니다.']));
    }

    if (iconv_strlen($params['terr_name'], 'UTF-8') < 1) {
        return $Render->view(json_encode(['result' => false, 'msg' => '변경할 영지명을 입력해 주십시오.']));
    } else if (iconv_strlen($params['terr_name'], 'UTF-8') < 2) {
        return $Render->view(json_encode(['result' => false, 'msg' => '영지명은 최소 2글자를 사용해야합니다.']));
    } else if (iconv_strlen($params['terr_name'], 'UTF-8') > 4) {
        return $Render->view(json_encode(['result' => false, 'msg' => '영지명은 최대 4글자까지 사용할 수 있습니다.']));
    }

    if (iconv_strlen($params['change_cause']) < 1) {
        return $Render->view(json_encode(['result' => false, 'msg' => '변경 사유를 입력하여 주십시오.']));
    }

    // 금지어 검사
    $ret = Useful::forbiddenWord($params['terr_name']);
    if (!$ret['ret']) {
        return $Render->view(json_encode(['result' => false, 'msg' => '입력하신 영지명의 ['.$ret['str'].']은(는) 사용할 수 없습니다.']));
    }

    // 예약어 검사
    if(!Useful::reservedWord($params['terr_name'])) {
        return $Render->view(json_encode(['result' => false, 'msg' => '입력하신 영지명은 사용할 수 없습니다.['.$params['terr_name'].']']));
    }

    // 중복 검사
    $PgGame->query('SELECT count(posi_pk) FROM territory WHERE title_lower = lower($1)', [$params['terr_name']]);
    if ($PgGame->fetchOne() > 0) {
        return $Render->view(json_encode(['result' => false, 'msg' => '이미 사용중인 영지명입니다. 다른 영지명을 입력해 주십시오.']));
    }

    $origin_terr_name = $_SESSION['selected_terr']['title'];
    if (!$PgGame->query('UPDATE territory SET title = $1, title_lower = lower($2) WHERE posi_pk = $3', [$params['terr_name'], $params['terr_name'], $_SESSION['selected_terr']['posi_pk']])) {
        return $Render->view(json_encode(['result' => false, 'msg' => '영지명을 변경하는 도중 에러가 발생하였습니다.']));
    }

    $Log = new Log($Session, $PgGame);
    $Log->SetBuildingAdministration($_SESSION['selected_lord']['lord_pk'], $_SESSION['selected_terr']['posi_pk'], 'gm_change_terr_name', "prev[$origin_terr_name];change[{$params['terr_name']}];");


    $_SESSION['selected_terr']['title'] = $params['terr_name'];

    $Session->sqAppend( 'TERR', ['title' => $params['terr_name'], 'title_change_up_dt' => time()], null, $_SESSION['selected_lord']['lord_pk'], $_SESSION['selected_terr']['posi_pk']);

    // 히스토리 기록
    $PgGm = new Pg('GM');
    $description = ['action' => 'change_terr_name', 'selected_server' => $_SESSION['selected_server'], 'lord' => $_SESSION['selected_lord'], 'terr' => $_SESSION['selected_terr'], 'prev_name' => $origin_terr_name, 'cause' => $params['change_cause']];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'M', serialize($description)]);

    return $Render->view(json_encode(['result' => true, 'd' => []]));
}));
