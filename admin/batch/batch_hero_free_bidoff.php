<?php // 매시 1분
set_time_limit(300);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/constant.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$NsGlobal = NsGlobal::getInstance();
$NsGlobal->requireMasterData(['hero', 'hero_base']);

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');
$Lord = new Lord($Session, $PgGame);
$Hero = new Hero($Session, $PgGame);
$GoldPop = new GoldPop($Session, $PgGame);
$i18n = new i18n();

$Report = new Report($Session, $PgGame);
$Log = new Log($Session, $PgGame);

$PgGame->query('SELECT t1.hero_free_pk, t1.hero_pk FROM hero_free t1, hero_free_bid t2 WHERE t1.hero_free_pk = t2.hero_free_pk GROUP BY t1.hero_free_pk, t1.hero_pk HAVING COUNT(t1.hero_free_pk) > 0');
$heroes_cnt = $PgGame->fetchAll();

$position_array = [];

global $_M;
if ($heroes_cnt > 0) {
	$heroes_free = $PgGame->rows;
	foreach ($heroes_free AS $hero_free) {
		$query_params = [$hero_free['hero_free_pk']];
		$PgGame->query('SELECT posi_pk, lord_pk, gold FROM hero_free_bid WHERE hero_free_pk = $1 ORDER BY gold DESC, bid_dt', $query_params);
		$bids_cnt = $PgGame->fetchAll();
		if ($bids_cnt > 0) {
			$bids = $PgGame->rows;
			$already_bidoff = false;
			// 한번 처리한 군주에 대해서는 처리하면 안되므로 추가함.
			// gold DESC, bid_dt ASC 순 이므로 최고가를 먼저 입력하여 체크하여 제외함.
			$report_checker = [];

			// 영웅 정보
			$PgGame->query('SELECT m_hero_pk, level FROM hero WHERE hero_pk = $1', [$hero_free['hero_pk']]);
			$PgGame->fetch();
			$m_hero_pk = $PgGame->row['m_hero_pk'];
			$level = $PgGame->row['level'];
			$m_hero_base_pk = $_M['HERO'][$m_hero_pk]['m_hero_base_pk'];

			// $sold_hero_name = $_M['HERO_BASE'][$m_hero_base_pk]['name']. ' (Lv.'. $level. ')';
            $sold_hero_name = $i18n->t("hero_name_$m_hero_base_pk"). ' (Lv.'. $level. ')';

            $sold_gold = 0;
			foreach ($bids AS $bid) {
				if ($already_bidoff === false) {
					$already_bidoff = true;
					$report_checker[] = $bid['lord_pk'].$bid['posi_pk']; // 보고서 체크용
					// 낙찰자 및 낙찰금액
					$PgGame->query('SELECT lord_name, level FROM lord WHERE lord_pk = $1', [$bid['lord_pk']]);
					$PgGame->fetch();

					$sold_lord_name = $PgGame->row['lord_name']. ' (Lv.'. $PgGame->row['level']. ')';
					$sold_gold = $bid['gold'];

					// 낙찰
					$Hero->setMyHeroCreate($hero_free['hero_pk'], $bid['lord_pk'], 'V', null, null, 'N', 'bid');

					// 보고서
					$z_content = [];
					$z_content['sold_gold'] = $sold_gold;
					$z_content['bid_gold'] = $bid['gold'];
					$z_content['result'] = $i18n->t('report_hero_bid_successful'); // 화면 하단의 영웅관리 메뉴를 통해서 낙찰 영웅을 등용할 수 있습니다.
					$z_content['hero'] = ['hero_pk' => $hero_free['hero_pk'], 'm_pk' => $m_hero_pk];
                    $z_content['success'] = true;
					// from & to
					$z_from = ['lord_name' => $sold_lord_name];
					$z_to = ['lord_name' => $sold_hero_name];

					// title & summary
					$z_title = ''; // 데이터가 없는 경우 비워두기
					$z_summary = $sold_hero_name;

                    $report_type = 'misc';
                    $repo_pk = $Report->setReport($bid['lord_pk'], $report_type, 'hero_bid_success', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));
                    $Session->sqAppend('PUSH', ['TOAST' => [
                        'type' => 'hero_bid_success',
                        'result' => $report_type,
                        'name' => $sold_hero_name,
                        'pk' => $repo_pk
                    ]], null, $bid['lord_pk']);

					// Log
                    if (! isset($position_array[$bid['lord_pk']])) {
                        $position_array[$bid['lord_pk']] = $Lord->getMainPosiPk($bid['lord_pk']);
                    }
                    $Log->setBuildingReceptionhall($bid['lord_pk'], $position_array[$bid['lord_pk']], 'bid_suc', $hero_free['hero_pk'], $bid['gold']);
				} else {
					if (!in_array($bid['lord_pk'].$bid['posi_pk'], $report_checker)) {
						$report_checker[] = $bid['lord_pk'].$bid['posi_pk']; // 보고서 및 환원 체크용
						// 입찰 금괴 환원
						$commision = (INT)($bid['gold'] / 10);
						$remain_gold = $bid['gold'] - $commision;
						$GoldPop->increaseGold($bid['posi_pk'], $remain_gold, $bid['lord_pk'], 'bid_fail');
						// $sold_gold = $bid['gold'];

						// 보고서
						$z_content = [];

						$z_content['sold_gold'] = $sold_gold;
						$z_content['bid_gold'] = $bid['gold'];
						$z_content['result'] = $i18n->t('report_hero_bid_unsuccessful', [$commision, $remain_gold]); // 입찰 수수료 황금 {{1}}냥을 제외한 입찰 참여금액 {{2}}냥은 반납되었습니다.
						// $z_content['result'] = $Lang_tool_batch_hero_free_bidoff_return1.$commision.$Lang_tool_batch_hero_free_bidoff_return2.$remain_gold.$Lang_tool_batch_hero_free_bidoff_return3;
						$z_content['hero'] = ['hero_pk' => $hero_free['hero_pk'], 'm_pk' => $m_hero_pk];
                        $z_content['success'] = false;

						$PgGame->query('SELECT lord_name, level FROM lord WHERE lord_pk = $1', [$bid['lord_pk']]);
						$PgGame->fetch();

						$sold_lord_name = $PgGame->row['lord_name']. ' (Lv.'. $PgGame->row['level']. ')';

						// from & to
						$z_from = ['lord_name' => $sold_lord_name];
						$z_to = ['lord_name' => $sold_hero_name];

						// title & summary
						$z_title = ''; // 데이터가 없는 경우 비워두기
						$z_summary = $sold_hero_name;

                        $report_type = 'misc';
						$repo_pk = $Report->setReport($bid['lord_pk'], $report_type, 'hero_bid_fail', $z_from, $z_to, $z_title, $z_summary, json_encode($z_content));
                        $Session->sqAppend('PUSH', ['TOAST' => [
                            'type' => 'hero_bid_fail',
                            'result' => $report_type,
                            'name' => $sold_hero_name,
                            'pk' => $repo_pk
                        ]], null, $bid['lord_pk']);
						// Log
						$Log->setHero($bid['lord_pk'], null, 'bid_fal', $hero_free['hero_pk'], null, null, null, 'gold['.$bid['gold'].']');

                        if (! isset($position_array[$bid['lord_pk']])) {
                            $position_array[$bid['lord_pk']] = $Lord->getMainPosiPk($bid['lord_pk']);
                        }
                        $Log->setBuildingReceptionhall($bid['lord_pk'], $position_array[$bid['lord_pk']], 'bid_fal', $hero_free['hero_pk'], $bid['gold']);
					}
				}
			}
		}
	}
}

// 백업 해 두기
$PgGame->query('INSERT INTO hero_free_backup SELECT * FROM hero_free');
$PgGame->query('INSERT INTO hero_free_bid_backup SELECT * FROM hero_free_bid');

// DROP CONSTRAINT
$PgGame->query('ALTER TABLE hero_free DROP CONSTRAINT hero_free_hero_pk_fkey');

$PgGame->query('UPDATE hero SET yn_del = $3 WHERE hero_pk IN (SELECT hero_pk FROM hero_free WHERE bid_cnt = $1) AND status = $2', [0, 'N', 'Y']);
$PgGame->query('TRUNCATE hero_free_bid, hero_free');

// RECREATE CONSTRAINT
$PgGame->query('ALTER TABLE hero_free ADD CONSTRAINT hero_free_hero_pk_fkey FOREIGN KEY (hero_pk) REFERENCES hero(hero_pk)');

$PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['hero_free_bidoff']);

// hero_free_random.php 재 실행
include('batch_hero_free_random.php');