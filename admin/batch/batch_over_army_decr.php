<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');

$Resource = new Resource($Session, $PgGame);
$GoldPop = new GoldPop($Session, $PgGame);

$Army = new Army($Session, $PgGame, $Resource, $GoldPop);

$Report = new Report($Session, $PgGame);
$Log = new Log($Session, $PgGame);

global $_M, $_M_ARMY_C;
$NsGlobal = NsGlobal::getInstance();
$NsGlobal->requireMasterData(['army', 'building']);

$PgGame->query('SELECT
	t1.lord_pk, t1.posi_pk, t2.title, t3.level,
	t4.worker, t4.infantry, t4.pikeman, t4.scout,
	t4.spearman, t4.armed_infantry, t4.archer, t4.horseman,
	t4.armed_horseman, t4.transporter, t4.bowman, t4.battering_ram,
	t4.catapult, t4.adv_catapult
FROM
	position t1,
	territory t2,
	building_in_castle t3,
	army t4
WHERE
	t1.posi_pk = t2.posi_pk AND
	t1.posi_pk = t3.posi_pk AND
	t1.posi_pk = t4.posi_pk AND
	t3.m_buil_pk = 200100 AND
	t2.over_army_flag = \'Y\' AND
	t2.over_army_check_dt < now()'); // t2.over_army_check_dt < now()
$PgGame->fetchAll();

$rows = $PgGame->rows;

global $_M;
if (count($rows) > 0) {
    foreach($rows as $row) {
        $total_army = $row['worker'] + $row['infantry'] + $row['pikeman'] + $row['scout'] + $row['spearman'] + $row['armed_infantry'] + $row['archer'] + $row['horseman'] + $row['armed_horseman'] + $row['transporter'] + $row['bowman'] + $row['battering_ram'] + $row['catapult'] + $row['adv_catapult'];

        if ($total_army > $_M['BUIL']['200100']['level'][$row['level']]['variation_2']) {
            // 깎을 양 구하기
            $army_dic = ['worker', 'infantry', 'pikeman', 'scout', 'spearman', 'armed_infantry', 'archer', 'horseman', 'armed_horseman', 'transporter', 'bowman', 'battering_ram', 'catapult', 'adv_catapult'];
            $len = count($army_dic);

            $t_army = []; // 깎인 양
            $o_army = [];
            $log_description = '';

            $update_query = ''; // 업데이트할 쿼리
            $z = 0;
            for($i = 0; $i < $len; $i++) {
                if ($row[$army_dic[$i]] > 0) {
                    $t_army[$army_dic[$i]] = ceil($row[$army_dic[$i]] * OVER_ARMY_DESCREASE);
                    $o_army[$army_dic[$i]] = $row[$army_dic[$i]];

                    $log_description .= "{$_M_ARMY_C[$army_dic[$i]]['m_army_pk']}[curr[{$o_army[$army_dic[$i]]}];update[{$t_army[$army_dic[$i]]}];];";

                    if ($z == 0) {
                        $update_query .= $army_dic[$i].' = CASE WHEN '.$army_dic[$i].' - '.$t_army[$army_dic[$i]].' < 0 THEN 0 ELSE '.$army_dic[$i].' - '.$t_army[$army_dic[$i]].' END';
                        $z++;
                    } else {
                        $update_query .= ', '.$army_dic[$i].' = CASE WHEN '.$army_dic[$i].' - '.$t_army[$army_dic[$i]].' < 0 THEN 0 ELSE '.$army_dic[$i].' - '.$t_army[$army_dic[$i]].' END';
                    }
                }
            }

            $PgGame->query('UPDATE territory SET over_army_flag = $2 WHERE posi_pk = $1', [$row['posi_pk'], 'N']);

            // 병력 숫자 감소 업데이트
            $PgGame->query("UPDATE army SET {$update_query} WHERE posi_pk = $1", [$row['posi_pk']]);

            // 갱신
            $Army->get($row['posi_pk'], null, $row['lord_pk']);

            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$row['lord_pk']]);
            $lord_name = $PgGame->fetchOne();

            // 보고서 보내기
            $z_content = [];

            $z_content['loss_army_info'] = $t_army;
            $z_content['army_before_loss'] = $o_army;

            // from & to
            $z_from = ['posi_pk' => $row['posi_pk'], 'posi_name' => ($row['title'].' ('.$row['posi_pk'].')'), 'lord_name' => $lord_name];
            $z_to = ['posi_pk' => $row['posi_pk'], 'posi_name' => ($row['title'].' ('.$row['posi_pk'].')'), 'lord_name' => $lord_name];

            $Report->setReport($row['lord_pk'], 'misc', 'army_loss', $z_from, $z_to, '', '', json_encode($z_content));

            // 액션
            $Session->sqAppend('PUSH', ['TOAST' => ['type' => 'army_decr', 'posi_pk' => $row['posi_pk']]], null, $row['lord_pk'], $row['posi_pk']);

            // Log
            $Log->setArmy($row['lord_pk'], $row['posi_pk'], 'decrease_army_over', $log_description);
        }
    }
}
$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['over_army_decr']);