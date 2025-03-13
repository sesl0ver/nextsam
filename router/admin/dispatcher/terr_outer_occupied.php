<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/terr_outer_occupied', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    function getValleyName($code)
    {
        $codeSet = Array(
            'L' => '저수지',
            'M' => '광산',
            'F' => '산림',
            'G' => '초원',
            'A' => '평지',
            'E' => '평지',
            'R' => '농경지'
        );
        return (isset($codeSet[$code]) ? $codeSet[$code] : $code);
    }

    global $_M, $NsGlobal;
    $NsGlobal->requireMasterData(['productivity_valley']);

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');


    $posi_pk = Array($_SESSION['selected_terr']['posi_pk']);
    $sql = "SELECT territory_valley.valley_posi_pk, date_part('epoch', territory_valley.regist_dt)::integer as regist_dt, position.level, position.type
            FROM territory_valley, position
            WHERE territory_valley.valley_posi_pk = position.posi_pk AND
                ( position.type = 'F' OR position.type = 'G' OR position.type = 'M' OR position.type = 'L' OR position.type = 'R' OR position.type = 'A' OR position.type = 'E') AND
                territory_valley.posi_pk = $1;";

    $PgGame->query($sql, $posi_pk);
    $PgGame->fetchAll();
    $result = $PgGame->rows;

    foreach ($result as $k => &$v)
    {
        if ( isset($v['regist_dt'])){
            $v['regist_dt'] = date('Y-m-d H:i:s', $v['regist_dt']);
        }

        if ( isset($v['type'])) {
            $valley_name = getValleyName($v['type']);
            $v['valley_name'] = $valley_name;

            $_level = $v['level'];
            $_resource_level_array['level'] = $_level;
            $_resource_level_array['food'] = $_M['PROD_VALL'][$v['type']][$_level]['food'];
            $_resource_level_array['horse'] = $_M['PROD_VALL'][$v['type']][$_level]['horse'];
            $_resource_level_array['lumber'] = $_M['PROD_VALL'][$v['type']][$_level]['lumber'];
            $_resource_level_array['iron'] = $_M['PROD_VALL'][$v['type']][$_level]['iron'];

            $v['level'] = $_resource_level_array;
        }
    }

    return $Render->view(json_encode($result));
}));