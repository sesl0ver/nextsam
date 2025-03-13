<?php // 매시30분
set_time_limit(600);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');
$Hero = new Hero($Session, $PgGame);
$HeroTrade = new HeroTrade($Session, $PgGame);
$GoldPop = new GoldPop($Session, $PgGame);

$Report = new Report($Session, $PgGame);
$Log = new Log($Session, $PgGame);

$now_dt = mktime(date('G'), date('i'), 0);

// 거래될 영웅 추출
$PgGame->query("UPDATE hero_trade SET yn_sale = 'Y', end_dt = ($now_dt + sale_period * 3600)::abstime WHERE yn_sale = 'N'");

// 거래 성사 처리
$PgGame->query('SELECT hero_trad_pk, hero_trad_bid_pk, lord_pk, lord_name, hero_pk, now_value, max_value, min_value, sale_period, password, 
hero_name, level, m_hero_pk, sell_ip, end_dt, yn_sale, trade_complete, enchant, leadership, mil_force, intellect, politics, charm, 
m_hero_skil_pk_1, m_hero_skil_pk_2, m_hero_skil_pk_3, m_hero_skil_pk_4, m_hero_skil_pk_5, m_hero_skil_pk_6, skill_exp
FROM hero_trade
WHERE yn_sale = \'Y\' AND (end_dt <= now() + interval \'5 minute\' OR trade_complete = \'Y\')
ORDER BY end_dt, hero_trad_pk LIMIT 5000');
$heroes_cnt = $PgGame->fetchAll();

if ($heroes_cnt > 0) {
	$heroes_trade = $PgGame->rows;
	foreach ($heroes_trade AS $hero_trade) {
		// 해당 거래 입찰 성공한 유저 처리(hero_trad_bid_pk가 성공한 유저)
		// trade_complete가 'Y'인 경우는 실패 처리만...
		$row = false;
		$yn_sale = 'N';
		if ($hero_trade['trade_complete'] == 'N') {
			if ($hero_trade['hero_trad_bid_pk']) {
				$PgGame->query('SELECT lord_pk, bid_value FROM hero_trade_bid WHERE hero_trad_bid_pk = $1', [$hero_trade['hero_trad_bid_pk']]);
				$PgGame->fetch();
				$row = $PgGame->row;
			}

			if ($row) { // 판매성공
				$yn_sale = 'Y';
				$HeroTrade->setHeroTradeBidSuccess($row['lord_pk'], $hero_trade['hero_trad_pk'], $row['bid_value'], $hero_trade);
			} else { // 판매 실패
				$HeroTrade->setHeroTradeSellFailure($hero_trade['hero_trad_pk'], $hero_trade);
			}
		}

		// 해당 거래 입찰 실패한 유저 처리
		$PgGame->query('SELECT lord_pk FROM hero_trade_bid WHERE hero_trad_pk = $1', [$hero_trade['hero_trad_pk']]);
		$PgGame->fetchAll();
		$rows = $PgGame->rows;
		if ($rows) {
			foreach ($rows AS $k => $v) {
				$HeroTrade->setHeroTradeBidFailure($v['lord_pk'], $hero_trade['hero_trad_pk'], $yn_sale);
			}
		}

		// 모두 처리 후 hero_trade_bid 테이블 삭제
		$PgGame->query('DELETE FROM hero_trade_bid WHERE hero_trad_pk = $1', [$hero_trade['hero_trad_pk']]);

		// 성공 실패 처리후 hero_trade 테이블 삭제
		$PgGame->query('DELETE FROM hero_trade WHERE hero_trad_pk = $1', [$hero_trade['hero_trad_pk']]);
	}
}