<?php // 매분
set_time_limit(30);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/constant.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');

$Troop = new Troop($Session, $PgGame);
$Report = new Report($Session, $PgGame);

$NsGlobal = NsGlobal::getInstance();

$PgGame->query('SELECT troo_pk, src_lord_pk, dst_posi_pk FROM troop WHERE status = $1 AND withdrawal_dt < now()', ['C']);
$PgGame->fetchAll();

$rows = $PgGame->rows;

$cnt = 0;
foreach ($rows AS $r) {
	$PgGame->query('SELECT lord_pk FROM position WHERE posi_pk = $1', [$r['dst_posi_pk']]);
	if ($PgGame->fetchOne() == $r['src_lord_pk']) {
		// 내 자원지 - 주둔부대의 철수가 소유 자원지의 포기는 아니어야 하므로 주석 처리함
		//$Troop->lossOwnershipValley($r['src_lord_pk'], $r['dst_posi_pk']);
	} else {
		// 동맹국의 영지
		$row = $Troop->getTroop($r['troo_pk']);

		// 보고서
		$z_content = [];

		// hero
		$z_content['hero'][] = $row['captain_desc'];
		if ($row['director_desc']) {
			$z_content['hero'][] = $row['director_desc'];
		}
		if ($row['staff_desc']) {
			$z_content['hero'][] = $row['staff_desc'];
		}

		// army
		foreach ($row AS $k => $v) {
			if (str_starts_with($k, 'army_')) {
				$z_content['army'][substr($k,5)] = $v;
			}
		}

		// from & to
		$z_from = ['posi_pk' => $row['src_posi_pk'], 'posi_name' => $row['from_position']];
		$z_to = ['posi_pk' => $row['dst_posi_pk'], 'posi_name' => $row['to_position']];

		// 보고서 발송!
		$Report->setReport($row['dst_lord_pk'], 'recall', 'ally_troop_recall', $z_from, $z_to, '', '', json_encode($z_content));
	}

	// 회군 (자동회군)
	$Troop->setStatusRecall($r['troo_pk'], null, true);

	$cnt++;
}

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['troop_withdrawal']);