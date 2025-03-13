<?php
global $app, $Render, $i18n;

$app->post('/api/territoryManage/list', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $type_arr = ['terr', 'reso', 'prod', 'army', 'fort', 'troo'];
    $type = 'terr';
    if (in_array($params['type'], $type_arr)) {
        $type = $params['type'];
    }

    $data = [];
    if ($type == 'terr') {
        // 자원정보지만 영지 인구 정보에 자원정보가 연관되어있어 갱신되어야 해서 추가. 19.07.27
        $PgGame->query("SELECT t2.title, t2.posi_pk, getresourcedetail(t2.posi_pk)::text col, getcurrentgold(t2.posi_pk)::integer gold_curr
FROM position t1, territory t2 WHERE t1.lord_pk = $1 AND t1.type = 'T' AND t1.posi_pk = t2.posi_pk ORDER BY t2.title::BYTEA", [$Session->lord['lord_pk']]);
        $PgGame->fetch();

        // TODO 이건 아닌데 - 고치자 - 시작
        $PgGame->query("SELECT t2.title, t2.population_curr, t2.population_labor_force, t1.posi_pk, t2.tax_rate, t2.loyalty, t3.hero_enco_pk encounter
FROM position t1, territory t2 LEFT OUTER JOIN hero_encounter t3 ON t3.posi_pk = t2.posi_pk AND t3.status = 'P'
WHERE t1.lord_pk = $1 AND t1.type = 'T' AND t1.posi_pk = t2.posi_pk ORDER BY t2.title::BYTEA", [$Session->lord['lord_pk']]);
        while($PgGame->fetch()) {
            $data[$PgGame->row['posi_pk']] = $PgGame->row;
        }

        $PgGame->query("SELECT posi_pk, type, SUM(concurr_curr) as concurr_curr, SUM(concurr_max) as concurr_max
FROM build WHERE posi_pk IN (SELECT posi_pk FROM position WHERE lord_pk = $1 AND type = 'T') GROUP BY posi_pk, type", [$Session->lord['lord_pk']]);
        while($PgGame->fetch()) {
            if ($data[$PgGame->row['posi_pk']]) {
                $data[$PgGame->row['posi_pk']]['concurr'][$PgGame->row['type']] = $PgGame->row;
            }
        }
        // TODO 이건 아닌데 - 고치자 - 끝
    } else if ($params['type'] == 'reso') {
        // 영지 정보 // 자원
        $PgGame->query("
SELECT t2.title, t2.posi_pk, getresourcedetail(t2.posi_pk)::text col, getcurrentgold(t2.posi_pk)::integer gold_curr
FROM position t1, territory t2 WHERE t1.lord_pk = $1 AND t1.type = 'T' AND t1.posi_pk = t2.posi_pk ORDER BY t2.title::BYTEA", [$Session->lord['lord_pk']]);
        while($PgGame->fetch()) {
            $data[$PgGame->row['posi_pk']] = $PgGame->row;
            $spl = explode(',', $PgGame->row['col']);
            $data[$PgGame->row['posi_pk']]['food_curr'] = $spl[1];
            $data[$PgGame->row['posi_pk']]['food_max'] = $spl[2];
            $data[$PgGame->row['posi_pk']]['horse_curr'] = $spl[5];
            $data[$PgGame->row['posi_pk']]['horse_max'] = $spl[6];
            $data[$PgGame->row['posi_pk']]['lumber_curr'] = $spl[8];
            $data[$PgGame->row['posi_pk']]['lumber_max'] = $spl[9];
            $data[$PgGame->row['posi_pk']]['iron_curr'] = $spl[11];
            $data[$PgGame->row['posi_pk']]['iron_max'] = $spl[12];
        }
    } else if ($params['type'] == 'prod') {
        // 영지 정보 // 생산량
        $PgGame->query("SELECT t3.posi_pk, t3.title, t1.food_production, t1.horse_production, t1.lumber_production, t1.iron_production, t2.gold_curr, t2.gold_production, t2.gold_salary, t2.gold_max
FROM resource t1, gold t2, territory t3
WHERE t3.posi_pk = t1.posi_pk AND t3.posi_pk = t2.posi_pk AND t3.posi_pk IN (SELECT posi_pk FROM position WHERE type = 'T' AND lord_pk = $1) ORDER BY t3.title::BYTEA", [$Session->lord['lord_pk']]);
        while($PgGame->fetch()) {
            $data[$PgGame->row['posi_pk']] = [
                'title' => $PgGame->row['title'],
                'posi_pk' => $PgGame->row['posi_pk'],
                'gold_production' => floor($PgGame->row['gold_production']),
                'food_production' => $PgGame->row['food_production'],
                'horse_production' => $PgGame->row['horse_production'],
                'lumber_production' => $PgGame->row['lumber_production'],
                'iron_production' => $PgGame->row['iron_production']
            ];
        }
    } else if ($params['type'] == 'army') {
        // 영지 정보 // 부대
        $PgGame->query("SELECT t1.posi_pk, t1.title, t2.worker, t2.infantry, t2.pikeman, t2.spearman, t2.scout, t2.archer,
t2.horseman, t2.transporter, t2.armed_infantry, t2.armed_horseman, t2.bowman, t2.battering_ram, t2.catapult, t2.adv_catapult
FROM territory t1, army t2
WHERE t1.posi_pk = t2.posi_pk AND t1.posi_pk IN (SELECT posi_pk FROM position WHERE type = 'T' AND lord_pk = $1)
ORDER BY t1.title::BYTEA", [$Session->lord['lord_pk']]);
        while($PgGame->fetch()) {
            $data[$PgGame->row['posi_pk']] = $PgGame->row;
        }

        global $NsGlobal, $_M;
        $NsGlobal->requireMasterData(['army']);

        foreach($data AS $k => $v) {
            $my = 0;
            $alli = 0;

            // 내 병력
            foreach ($_M['ARMY_C'] AS $k2 => $v2) {
                $my += $v[$k2];
            }

            // 지원 병력
            $PgGame->query("SELECT sum(army_worker) as worker, sum(army_infantry) as infantry, sum(army_pikeman) as pikeman, sum(army_scout) as scout, sum(army_spearman) as spearman,
 sum(army_armed_infantry) as armed_infantry, sum(army_archer) as archer, sum(army_horseman) as horseman, sum(army_armed_horseman) as armed_horseman,
 sum(army_transporter) as transporter, sum(army_bowman) as bowman, sum(army_battering_ram) as battering_ram, sum(army_catapult) as catapult,
 sum(army_adv_catapult) as adv_catapult FROM troop WHERE dst_posi_pk = $1 AND status = $2", [$v['posi_pk'], 'C']);
            $PgGame->fetch();
            $z_arr =  $PgGame->row;

            foreach ($_M['ARMY_C'] AS $k3 => $v3) {
                $alli += $z_arr[$k3];
                $data[$k]['alli_'.$k3] = (!$z_arr[$k3]) ? 0 : $z_arr[$k3];
            }

            $data[$k]['total_army'] = ($my+$alli); // 총 병력
            $data[$k]['my_army'] = $my; // 내 병력
            $data[$k]['alli_army'] = $alli; // 지원 병력
        }
    } else if ($params['type'] == 'fort') {
        // 영지 정보 // 방어시설
        $PgGame->query("SELECT t1.posi_pk, t1.title, t1.wall_vacancy_max, t1.wall_vacancy_curr, t3.level, t2.trap, t2.abatis, t2.tower
FROM territory t1, fortification t2, building_in_castle t3
WHERE t1.posi_pk = t2.posi_pk AND t1.posi_pk = t3.posi_pk AND t3.m_buil_pk = 201600 AND t1.posi_pk IN (SELECT posi_pk FROM position WHERE type = 'T' AND lord_pk = $1)
ORDER BY t1.title::BYTEA", [$Session->lord['lord_pk']]);
        while($PgGame->fetch()) {
            $data[$PgGame->row['posi_pk']] = $PgGame->row;
        }
    } else if ($params['type'] == 'troo') {
        // 영지 정보 // 부대 이동
        $PgGame->query("SELECT COUNT(t1.troo_pk) FROM troop t1 WHERE (src_lord_pk = $1 OR (dst_lord_pk = $1 AND (cmd_type = 'T' OR cmd_type = 'P' OR cmd_type = 'R' OR (cmd_type = 'A' AND status <> 'R')))) AND status <> 'C'", [$Session->lord['lord_pk']]);
        $total_count = $PgGame->fetchOne();
        $total_page = (INT)($total_count / REPORT_LETTER_PAGE_NUM);
        $total_page += ($total_count % REPORT_LETTER_PAGE_NUM > 0)? 1 : 0;

        $page_num = $params['page_num'];

        if ($page_num < 1) {
            $page_num = 1;
        } else if ($page_num > $total_page) {
            $page_num = $total_page;
        }

        $order_arr = ['title', 'cmd_type', 'arrival'];
        $order = (!in_array($params['order_by'], $order_arr)) ? 'title' : strtolower($params['order_by']);

        if ($page_num > 0) {
            $order_str = '';
            if ($order == 'title') {
                $order_str = 't2.title::BYTEA ASC, t1.arrival_dt ASC';
            } else if ($order == 'cmd_type') {
                $order_str = "CASE
    WHEN t1.cmd_type = 'A' AND t1.status = 'M' THEN 1
    WHEN t1.cmd_type = 'A' AND t1.status = 'R' THEN 2
    WHEN t1.cmd_type = 'S' AND t1.status = 'M' THEN 3
    WHEN t1.cmd_type = 'S' AND t1.status = 'R' THEN 4
    WHEN t1.cmd_type = 'R' AND t1.status = 'M' THEN 5
    WHEN t1.cmd_type = 'R' AND t1.status = 'R' THEN 6
    WHEN t1.cmd_type = 'P' AND t1.status = 'M' THEN 7
    WHEN t1.cmd_type = 'P' AND t1.status = 'R' THEN 8
    WHEN t1.cmd_type = 'T' AND t1.status = 'M' THEN 9
    WHEN t1.cmd_type = 'T' AND t1.status = 'R' THEN 10
    ELSE 11
  END ASC,
  t2.title::BYTEA ASC,
  t1.arrival_dt ASC";
            } else if ($order == 'arrival') {
                $order_str = 't1.arrival_dt ASC, t2.title::BYTEA ASC';
            }

            $offset_start = ($page_num - 1) * HERO_LIST_PAGE_NUM;
            $limit = HERO_LIST_PAGE_NUM;

            $PgGame->query("SELECT t2.title, t1.troo_pk, t1.src_lord_pk, t1.dst_lord_pk, t1.from_position, t1.to_position, t1.src_posi_pk,
t1.status, t1.cmd_type, t1.troop_type, t1.captain_desc, date_part('epoch', t1.arrival_dt)::integer
FROM troop t1 LEFT OUTER JOIN territory AS t2 ON t1.src_posi_pk = t2.posi_pk
WHERE (src_lord_pk = $1 OR (dst_lord_pk = $1 AND (cmd_type = 'T' OR cmd_type = 'P' OR cmd_type = 'R' OR (cmd_type = 'A' AND status <> 'R')))) AND status <> 'C'
ORDER BY {$order_str} LIMIT {$limit} OFFSET {$offset_start}", [$Session->lord['lord_pk']]);
            $data['list'] = [];
            while($PgGame->fetch()) {
                //$data['list'][$PgGame->row['troo_pk']] = $PgGame->row;
                $data['list'][$PgGame->row['troo_pk']]['title'] = $PgGame->row['title'];
                $data['list'][$PgGame->row['troo_pk']]['troo_pk'] = $PgGame->row['troo_pk'];
                $data['list'][$PgGame->row['troo_pk']]['src_lord_pk'] = $PgGame->row['src_lord_pk'];
                $data['list'][$PgGame->row['troo_pk']]['dst_lord_pk'] = $PgGame->row['dst_lord_pk'];
                $data['list'][$PgGame->row['troo_pk']]['from_position'] = $PgGame->row['from_position'];
                $data['list'][$PgGame->row['troo_pk']]['to_position'] = $PgGame->row['to_position'];
                $data['list'][$PgGame->row['troo_pk']]['src_posi_pk'] = $PgGame->row['src_posi_pk'];
                $data['list'][$PgGame->row['troo_pk']]['status'] = $PgGame->row['status'];
                $data['list'][$PgGame->row['troo_pk']]['cmd_type'] = $PgGame->row['cmd_type'];
                $data['list'][$PgGame->row['troo_pk']]['troop_type'] = $PgGame->row['troop_type'];
                $data['list'][$PgGame->row['troo_pk']]['captain_desc'] = $PgGame->row['captain_desc'];
                $data['list'][$PgGame->row['troo_pk']]['arrival_dt'] = $PgGame->row['arrival_dt'];
                // TODO 차후 요충지 개발시
                if (str_contains($PgGame->row['from_position'], '요충지')) {
                    if ($Session->lord['lord_pk'] != $PgGame->row['src_lord_pk']) {
                        $data['list'][$PgGame->row['troo_pk']]['title'] = '타군주';
                        $data['list'][$PgGame->row['troo_pk']]['to_position'] = '타군주';
                    }
                } else if (str_contains($PgGame->row['to_position'], '요충지')) {
                    if ($Session->lord['lord_pk'] != $PgGame->row['src_lord_pk']) {
                        $data['list'][$PgGame->row['troo_pk']]['title'] = '타군주';
                        $data['list'][$PgGame->row['troo_pk']]['from_position'] = '타군주';
                    }
                }
            }
            $data['curr_page'] = $page_num;
            $data['total_page'] = $total_page;
            $data['order_by'] = $order;
            $data['total_count'] = $total_count;
        } else {
            $data['list'] = [];
            $data['curr_page'] = 1;
            $data['total_page'] = 1;
            $data['order_by'] = $order;
            $data['total_count'] = 0;
        }
    }

    return $Render->nsXhrReturn('success', null, $data);
}));
