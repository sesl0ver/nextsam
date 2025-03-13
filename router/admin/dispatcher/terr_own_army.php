<?php
global $app, $Render, $i18n;

// 로드 - 보유 병력 정보 조회
$app->post('/admin/gm/api/terr_own_army', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (!isset($_SESSION) || !isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['army', 'building']);

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $posi_pk = $_SESSION['selected_terr']['posi_pk'];

    $sql = "SELECT  worker, infantry, pikeman, scout, spearman,
                    armed_infantry, archer, horseman, armed_horseman, transporter,
                    bowman, battering_ram, catapult, adv_catapult
            FROM army
            WHERE posi_pk = $1;";

    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetch();
    $terr_in_army = $PgGame->row;


    $sql = "SELECT  worker, infantry, pikeman, scout, spearman,
	                armed_infantry, archer, horseman, armed_horseman, transporter,
	                bowman, battering_ram, catapult, adv_catapult
            FROM medical_army
            WHERE posi_pk = $1;";

    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetch();
    $medical_army = $PgGame->row;


    $sql =  "SELECT SUM(troop.army_worker) AS worker,
                    SUM(troop.army_infantry) AS infantry,
                    SUM(troop.army_pikeman) AS pikeman,
                    SUM(troop.army_spearman) AS spearman,
                    SUM(troop.army_scout) AS scout,
                    SUM(troop.army_armed_infantry) AS armed_infantry,
                    SUM(troop.army_armed_horseman) AS armed_horseman,
                    SUM(troop.army_archer) AS archer,
                    SUM(troop.army_horseman) AS horseman,
                    SUM(troop.army_transporter) AS transporter,
                    SUM(troop.army_bowman) AS bowman,
                    SUM(troop.army_battering_ram) AS battering_ram,
                    SUM(troop.army_catapult) AS catapult,
                    SUM(troop.army_adv_catapult) AS adv_catapult
            FROM troop
            WHERE src_posi_pk = $1";

    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetch();
    $order_army = $PgGame->row;


    $sql = "SELECT  trap, abatis, tower, wall_vacancy_max, wall_vacancy_curr
            FROM    fortification, territory
            WHERE   fortification.posi_pk = territory.posi_pk 
            AND     fortification.posi_pk = $1";

    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetch();

    $fort = $PgGame->row;
    $fort['wall_vacancy_remain'] = ($fort['wall_vacancy_max'] - $fort['wall_vacancy_curr']);
    
    // 데이터 종합
    $_total = 0;
    $_sum_of_terr_in_army = 0;
    $_sum_of_order_army = 0;
    $_sum_of_medical_army = 0;

    $response = [];
    $response['detail'] =[];
    
    // 병력 별 정보
    foreach($_M['ARMY_C'] as $k => $v){
        $sum_army = $terr_in_army[$k] + $order_army[$k] + $medical_army[$k];
        $_total += $sum_army;
        $_sum_of_terr_in_army += $terr_in_army[$k];
        $_sum_of_order_army += $order_army[$k];
        $_sum_of_medical_army += $medical_army[$k];

        $_medical_army = (!$medical_army[$k]) ? 0 : $medical_army[$k];
        $_order_army = (!$order_army[$k]) ? 0 : $order_army[$k];
        $_terr_in_army = (!$terr_in_army[$k]) ? 0 : $terr_in_army[$k];

        $_row = ['title' => $v['title'], 'sum' => $sum_army, 'terr' => $_terr_in_army, 'order' => $_order_army, 'medical' => $_medical_army];
        $response['detail'][] = $_row;
    }

    $totalAll = ['total' => $_total, 'terr' => $_sum_of_terr_in_army, 'order' => $_sum_of_order_army, 'medical' => $_sum_of_medical_army];
    $response['total'] = $totalAll;
    $response['fort'] = $fort;
    

    return $Render->view(json_encode($response));
}));