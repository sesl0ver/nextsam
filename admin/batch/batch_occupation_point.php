<?php
set_time_limit(600);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

if (CONF_OCCUPATION_POINT_ENABLE !== true) {
    exit();
}

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');
$Event = new Event($PgGame);
$Log = new Log($Session, $PgGame);

if (! $Event->getTrigger('occupation_point')) {
    exit();
}

$Redis = new RedisCache();

$now_time = Useful::microTimeFloat();

$PgGame->query('SELECT posi_pk, lord_pk, type, level, current_point, now() as now, date_part(\'epoch\', update_point_dt) as update_point_dt FROM position WHERE lord_pk IS NOT NULL AND current_point > 0 AND type != \'T\'');
$PgGame->fetchAll();

global $_M;
$NsGlobal = NsGlobal::getInstance();
$NsGlobal->requireMasterData(['productivity_valley']);

$log_data = [];

$position_query = [];
$point_rows = []; // 포인트 최종값
foreach ($PgGame->rows as $row) {
    if (! isset($log_data[$row['lord_pk']])) {
        $log_data[$row['lord_pk']] = [];
    }

    // 시간에 따른 획득 포인트
    $need_point = bcdiv(bcmul(bcsub($now_time, $row['update_point_dt']), $_M['PROD_VALL'][$row['type']][$row['level']]['occupation_point']), 3600, 2);
    // 현재 남은 포인트 보다 높다면 현재 포인트만 획득
    if ($need_point > $row['current_point']) {
        $need_point = $row['current_point'];
    }

    $log_data[$row['lord_pk']][] = ['posi_pk' => $row['posi_pk'], 'earn_point' => $need_point];

    $position_query[] = "($need_point, '{$row['now']}', '{$row['posi_pk']}')";
    if (! isset($point_rows[$row['lord_pk']])) {
        $point_rows[$row['lord_pk']] = 0;
    }
    $point_rows[$row['lord_pk']] += $need_point;
}

// 1차 로그
foreach ($log_data as $_lord_pk => $_logs) {
    // [$_lord_pk, $_posi_pk, $_type, $_target_posi_pk, $_target_point, $_ranking, $this->web_channel, $this->server_index];
    foreach ($_logs as $_log) {
        $Log->setOccupationPoint($_lord_pk, null, 'regular_earn', $_log['posi_pk'], $_log['earn_point']);
    }
}

$log_point = [];

// 점령치 포인트 계산
if (count($position_query) > 0) {
    $position_query_string = implode(',', $position_query);
    $PgGame->query("UPDATE position AS t SET current_point = current_point - c.point, update_point_dt = c.now::timestamptz FROM (VALUES $position_query_string) as c(point, now, posi_pk) WHERE c.posi_pk = t.posi_pk and t.level BETWEEN 1 AND 3");
    $PgGame->query("UPDATE position AS t SET current_point = current_point - c.point, update_point_dt = c.now::timestamptz FROM (VALUES $position_query_string) as c(point, now, posi_pk) WHERE c.posi_pk = t.posi_pk and t.level BETWEEN 4 AND 6");
    $PgGame->query("UPDATE position AS t SET current_point = current_point - c.point, update_point_dt = c.now::timestamptz FROM (VALUES $position_query_string) as c(point, now, posi_pk) WHERE c.posi_pk = t.posi_pk and t.level BETWEEN 7 AND 9");
    $PgGame->query("UPDATE position AS t SET current_point = current_point - c.point, update_point_dt = c.now::timestamptz FROM (VALUES $position_query_string) as c(point, now, posi_pk) WHERE c.posi_pk = t.posi_pk and t.level = 10");

    $point_query = [];
    foreach ($point_rows as $lord_pk => $point) {
        $point_query[] = "($lord_pk, $point)";
    }
    $point_query_string = implode(',', $point_query);
    $PgGame->query("INSERT INTO occupation_point as op (lord_pk, point) VALUES $point_query_string ON CONFLICT (lord_pk) DO UPDATE SET point = op.point + EXCLUDED.point RETURNING lord_pk, point;");
    $PgGame->fetchAll();
    foreach ($PgGame->rows as $row) {
        $log_point[] = ['lord_pk' => $row['lord_pk'], 'target_point' => $row['point']];
    }
}

// 2차 로그 (최종 포인트)
foreach ($log_point as $_point) {
    $Log->setOccupationPoint($_point['lord_pk'], null, 'update_point', null, $_point['target_point']);
}

// 동맹 점령치 랭킹
$PgGame->query('select l.alli_pk, a.title, a.master_lord_pk, a.lord_name, a.now_member_count, a.power, sum(op.point) as ally_point, ROW_NUMBER() OVER(ORDER BY sum(op.point) DESC) as point_rank from occupation_point as op, lord as l left join alliance as a on l.alli_pk = a.alli_pk where l.alli_pk is not null and l.lord_pk = op.lord_pk group by l.alli_pk, a.title, a.master_lord_pk, a.lord_name, a.now_member_count, a.power order by ally_point desc limit 100;');
$PgGame->fetchAll();

$Redis->del('ranking:alliance:occupation_point');
foreach ($PgGame->rows as $row) {
    $Redis->zAdd('ranking:alliance:occupation_point', $row['point_rank'], $row);
}

// 개인 점령치 랭킹 (TODO 시즌용)
/*$PgGame->query('select op.lord_pk, l.lord_name, op.point, l.power, a.title, ROW_NUMBER() OVER(ORDER BY op.point DESC) as point_rank from occupation_point as op left join lord as l on op.lord_pk = l.lord_pk left outer join alliance as a on l.alli_pk = a.alli_pk order by op.point desc limit 100;');
$PgGame->fetchAll();

$Redis->del('ranking:lord:occupation_point');
foreach ($PgGame->rows as $row) {
    $Redis->zAdd('ranking:lord:occupation_point', $row['point_rank'], $row);
}*/

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['occupation_point']);