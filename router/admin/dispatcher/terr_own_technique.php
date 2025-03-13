<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/terr_own_technique', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['technique']);

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');


    $posi_pk = $_SESSION['selected_terr']['posi_pk'];
    $PgGame->query('SELECT lord_pk FROM position WHERE posi_pk = $1', [$posi_pk]);
    $lord_pk = $PgGame->fetchOne();

    if ( empty($posi_pk) )
    {
        return $Render->view(json_encode(['result' => false, 'message'=>'Is exists an error in [selected_terr][posi_pk]', 'data' => null]));
    }


    $sql = "SELECT  agriculture, stock_farming, lumbering, mining, storage, 
                    construction, astronomy, paper, medicine, smelting, casting, 
                    machinery, mil_fencing, mil_shield, mil_spear, mil_horse, mil_science, 
                    fortification, compass, logistics, informatics, mil_archery, 
                    mil_formation, mil_siege
        FROM technique where posi_pk = $1;";
    $PgGame->query($sql, [$posi_pk]);
    $PgGame->fetch();

    $tech_info = $PgGame->row;

    $sql = "SELECT  lord_pk, agriculture, stock_farming, lumbering, mining, storage,
construction, astronomy, paper, medicine, smelting, casting,
machinery, mil_fencing, mil_shield, mil_spear, mil_horse, mil_science,
fortification, compass, logistics, informatics, mil_archery, 
mil_formation, mil_siege
            FROM lord_technique where lord_pk = $1;";

    $PgGame->query($sql, [$lord_pk]);
    $PgGame->fetch();
    $lord_tech_info = $PgGame->row;

    $response = [];
    // 데이터 가공
    foreach($_M['TECH'] as $k => $v) {
        $response[] = ['title' => $v['title'], 'lord_tech_info' => $lord_tech_info[$v['code']], 'tech_info' =>$tech_info[$v['code']], 'tech_max' => 10];
    }

    return $Render->view(json_encode($response));
}));


