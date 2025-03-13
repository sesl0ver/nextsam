<?php
global $app, $Render, $i18n;

use Shuchkin\SimpleXLSXGen;

$app->post('/admin/gm/api/statistics/dau', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $date = $params['date'];

    // DAU
    $PgGame->query("select lord_pk, uuid, count(uuid) as count from lord_login where login_dt >= '$date 00:00:00 +0900' and login_dt <='$date 23:59:59 +0900' group by uuid, lord_pk;");
    $PgGame->fetchAll();

    // 컬럼명
    $data[] = ['lord_pk', 'uuid', 'count'];
    foreach ($PgGame->rows as $row) {
        $data[] = [$row['lord_pk'], $row['uuid'], $row['count']];
    }

    SimpleXLSXGen::fromArray($data)->downloadAs("dau.xlsx");

    return $Render->view();
}));

$app->post('/admin/gm/api/statistics/nru', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $date = $params['date'];

    // DAU
    $PgGame->query("select lord_pk, lord_name, level, regist_dt from lord where lord_pk > 2 and regist_dt >= '$date 00:00:00 +0900' and regist_dt <='$date 23:59:59 +0900' order by lord_pk");
    $PgGame->fetchAll();

    // 컬럼명
    $data[] = ['lord_pk', 'lord_name', 'level', 'regist_dt'];
    foreach ($PgGame->rows as $row) {
        $data[] = [$row['lord_pk'], $row['lord_name'], $row['level'], $row['regist_dt']];
    }

    SimpleXLSXGen::fromArray($data)->downloadAs("nru.xlsx");

    return $Render->view();
}));

$app->post('/admin/gm/api/statistics/paid', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $date = $params['date'];

    // DAU
    $PgGame->query("select lord_pk, store_type, pack_type, count(pack_type) as count, (pack_type * count(pack_type)) as sum from qbig_pack where buy_dt >= '$date 00:00:00 +0900' and buy_dt <='$date 23:59:59 +0900' group by lord_pk, store_type, pack_type order by lord_pk");
    $PgGame->fetchAll();

    // 컬럼명
    $data[] = ['lord_pk', 'store_type', 'pack_type', 'count', 'sum'];
    foreach ($PgGame->rows as $row) {
        $data[] = [$row['lord_pk'], $row['store_type'], $row['pack_type'], $row['count'], $row['sum']];
    }

    SimpleXLSXGen::fromArray($data)->downloadAs("paid.xlsx");

    return $Render->view();
}));

$app->post('/admin/gm/api/statistics/lord', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $date = $params['date'];

    // DAU
    $PgGame->query("select count(lord_pk) as count from lord where lord_pk > 2");
    $PgGame->fetchAll();

    // 컬럼명
    $data[] = ['전체 군주 수 (누적)'];
    foreach ($PgGame->rows as $row) {
        $data[] = [$row['count']];
    }

    SimpleXLSXGen::fromArray($data)->downloadAs("lord.xlsx");

    return $Render->view();
}));

$app->post('/admin/gm/api/statistics/level', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $date = $params['date'];

    // DAU
    $PgGame->query("select level, count(level) as count from lord group by level order by level;");
    $PgGame->fetchAll();

    // 컬럼명
    $data[] = ['level', 'count'];
    foreach ($PgGame->rows as $row) {
        $data[] = [$row['level'], $row['count']];
    }

    SimpleXLSXGen::fromArray($data)->downloadAs("level.xlsx");

    return $Render->view();
}));

$app->post('/admin/gm/api/statistics/build', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $date = $params['date'];

    // DAU
    $PgGame->query("select level, count(level) as count from building_in_castle where in_castle_pk = 1 group by level order by level;");
    $PgGame->fetchAll();

    // 컬럼명
    $data[] = ['level', 'count'];
    foreach ($PgGame->rows as $row) {
        $data[] = [$row['level'], $row['count']];
    }

    SimpleXLSXGen::fromArray($data)->downloadAs("build.xlsx");

    return $Render->view();
}));

