<?php // 매시 10분 실행해줌.
set_time_limit(60);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$Session = new Session(false);
$PgGame = new Pg('DEFAULT');
$Hero = new Hero($Session, $PgGame);

$rate_1_arr = [];
$rate_2_arr = [];
$rate_3_arr = [];
$rate_4_arr = [];
$rate_5_arr = [];
$rate_6_arr = [];
$rate_7_arr = [];

$NsGlobal = NsGlobal::getInstance();
$NsGlobal->requireMasterData(['hero','hero_base', 'gachapon']);

// 7성 판매 제한 개수
$PgGame->query('SELECT new_gachapon_buy_limit_cnt FROM m_preference');
$new_gachapon_buy_limit_cnt = $PgGame->fetchOne();

// 7성 판매 여부
$PgGame->query('SELECT gach_event_buy_count FROM new_gachapon_event WHERE orderno = $1', [7]);
if ($PgGame->fetchOne() > 0) {
	$new_gachapon_buy_limit_cnt += 100;
	if ($new_gachapon_buy_limit_cnt > NEW_GACHAPON_BUY_LIMIT_MAX_CNT) {
		$new_gachapon_buy_limit_cnt = NEW_GACHAPON_BUY_LIMIT_MAX_CNT;
	}
} else {
	$new_gachapon_buy_limit_cnt -= 50;
	if ($new_gachapon_buy_limit_cnt < NEW_GACHAPON_BUY_LIMIT_MIN_CNT) {
		$new_gachapon_buy_limit_cnt = NEW_GACHAPON_BUY_LIMIT_MIN_CNT;
	}
}

// 7성 판매 제한 업데이트
$PgGame->query('UPDATE m_preference SET new_gachapon_buy_limit_cnt = $1', [$new_gachapon_buy_limit_cnt]);

$not_lord_base_pk_arr = [120000, 120001, 120002, 120003, 120004];
global $_M;
foreach ($_M['HERO_BASE'] AS $k => $v) {
	if ($v['over_type'] != 'Y')	{
		if ($v['yn_new_gacha'] == 'Y') {
			if ($v['yn_modifier'] == 'N') {
				if ($v['rare_type'] == 1) {
					$rate_1_arr[] = $k;
				} else if ($v['rare_type'] == 2) {
					$rate_2_arr[] = $k;
				} else if ($v['rare_type'] == 3) {
					$rate_3_arr[] = $k;
				} else if ($v['rare_type'] == 4) {
					$rate_4_arr[] = $k;
				} else if ($v['rare_type'] == 5) {
					$rate_5_arr[] = $k;
				} else if ($v['rare_type'] == 6) {
					$rate_6_arr[] = $k;
				} else if ($v['rare_type'] == 7) {
					if (in_array($v['m_hero_base_pk'], $not_lord_base_pk_arr)) {
                        continue;
                    }
					$rate_7_arr[] = $k;
				}
			}
		}
	}
}

$PgGame->query('TRUNCATE new_gachapon_event');

//shuffle($rate_1_arr); TODO 사용안함
//shuffle($rate_2_arr); TODO 사용안함
shuffle($rate_3_arr);
shuffle($rate_4_arr);
shuffle($rate_5_arr);
shuffle($rate_6_arr);
shuffle($rate_7_arr);

/*
// 1성
for ($i = 0; $i < 30; $i++)
{
	$level = $Hero->getRandomLevel('GACHAPON', null);

	$m_hero_pk = $Hero->getHeroPK($rate_1_arr[$i], $level);

	$query_params = Array($m_hero_pk, 12, 1);
	$sql = <<< EOF
INSERT INTO new_gachapon_event (m_hero_pk, gach_event_default_count, orderno)
VALUES ($1, $2, $3)
EOF;

	$Db->query($sql, $query_params);
}

// 2성
for ($i = 0; $i < 30; $i++)
{
	$level = $Hero->getRandomLevel('GACHAPON', null);

	$m_hero_pk = $Hero->getHeroPK($rate_2_arr[$i], $level);

	$query_params = Array($m_hero_pk, 12, 2);
	$sql = <<< EOF
INSERT INTO new_gachapon_event (m_hero_pk, gach_event_default_count, orderno)
VALUES ($1, $2, $3)
EOF;

	$Db->query($sql, $query_params);
}
*/
// 3성

for ($i = 0; $i < 3; $i++) {
	$level = $Hero->getRandomLevel('NEW_GACHAPON', null);

	$m_hero_pk = $Hero->getNewHeroPK($rate_3_arr[$i], $level);

    $PgGame->query('INSERT INTO new_gachapon_event (m_hero_pk, gach_event_default_count, orderno) VALUES ($1, $2, $3)', [$m_hero_pk, 90, 3]);
}

// 4성
for ($i = 0; $i < 2; $i++) {
    $level = $Hero->getRandomLevel('NEW_GACHAPON', null);

    $m_hero_pk = $Hero->getNewHeroPK($rate_4_arr[$i], $level);

    $PgGame->query('INSERT INTO new_gachapon_event (m_hero_pk, gach_event_default_count, orderno) VALUES ($1, $2, $3)', [$m_hero_pk, 95, 4]);
}

// 5성
for ($i = 0; $i < 2; $i++) {
    $level = $Hero->getRandomLevel('NEW_GACHAPON', null);

    $m_hero_pk = $Hero->getNewHeroPK($rate_5_arr[$i], $level);

    $PgGame->query('INSERT INTO new_gachapon_event (m_hero_pk, gach_event_default_count, orderno) VALUES ($1, $2, $3)', [$m_hero_pk, 20, 5]);
}

// 6성
for ($i = 0; $i < 2; $i++) {
    $level = $Hero->getRandomLevel('NEW_GACHAPON', null);

    $m_hero_pk = $Hero->getNewHeroPK($rate_6_arr[$i], $level);

    $PgGame->query('INSERT INTO new_gachapon_event (m_hero_pk, gach_event_default_count, orderno) VALUES ($1, $2, $3)', [$m_hero_pk, 2, 6]);
}

// 7성
for ($i = 0; $i < 1; $i++) {
    $level = $Hero->getRandomLevel('NEW_GACHAPON', null);

    $m_hero_pk = $Hero->getNewHeroPK($rate_7_arr[$i], $level);

    $PgGame->query('INSERT INTO new_gachapon_event (m_hero_pk, gach_event_default_count, orderno) VALUES ($1, $2, $3)', [$m_hero_pk, 1, 7]);
}

// 군주 가챠폰 구매 횟수 초기화.
$PgGame->query('UPDATE lord SET new_gachapon_buy_cnt = $1', [0]);