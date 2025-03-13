<?php
set_time_limit(120);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// TODO 모바일 푸시용도로 사용하였으나 현재는 사용안함.
$_MESG = [];
$_MESG['build'] = ['terr', 'Next 삼국지', '%s 완료되었습니다.'];
$_MESG['tech'] = ['terr', 'Next 삼국지', '%s 기술 연구가 완료되었습니다.'];
$_MESG['army'] = ['terr', 'Next 삼국지', '%s의 훈련이 완료되었습니다.'];
$_MESG['enco'] = ['terr', 'Next 삼국지', '영빈관에 영웅 탐색이 완료되었습니다.'];
$_MESG['treat'] = ['terr', 'Next 삼국지', '부상을 당한 %s의 치료가 완료되었습니다.'];
$_MESG['raid'] = ['terr', 'Next 삼국지', '황건적 요새가 발견되었습니다.'];

$_MESG['letter'] = ['advice', 'Next 삼국지', '다른 군주로부터 서신이 도착했습니다.'];
$_MESG['newbie'] = ['advice', 'Next 삼국지', '초보자 보호 쉴드가 해제되었습니다.'];
$_MESG['friend'] = ['advice', 'Next 삼국지', '추천코드를 입력한 군주가 있다고 합니다.'];
$_MESG['alliance'] = ['advice', 'Next 삼국지', '봉황의구슬이 동맹 선물로 도착했습니다.'];

$_MESG['detect'] = ['report', 'Next 삼국지', '[긴급] 공격해오는 적 부대가 있습니다.'];
$_MESG['scout'] = ['report', 'Next 삼국지', '출정 부대의 정찰 보고서가 도착했습니다.'];
$_MESG['scout2'] = ['report', 'Next 삼국지', '[긴급] 잠입한 적 정찰병이 발견되었습니다.'];
$_MESG['attack'] = ['report', 'Next 삼국지', '출정 부대의 전투 보고서가 도착했습니다.'];
$_MESG['defence'] = ['report', 'Next 삼국지', '방어 전투 결과를 확인해주십시오.'];

$PgGame = new Pg('DEFAULT');

while (true) {
	$PgGame->query('DELETE FROM pns_prepare WHERE pns_pre_pk IN (SELECT pns_pre_pk FROM pns_prepare ORDER BY pns_pre_pk ASC LIMIT 100) RETURNING lord_pk, type, info, posi_title');
	$c = $PgGame->fetchAll();
	if (!$c) {
		exit;
	}
	$rows = $PgGame->rows;

	$cnt = 0;
	$data = [];

	foreach ($rows AS $row) {
		$cnt++;
		$PgGame->query('SELECT t1.pn_token, t1.pn_report_t1, t1.pn_report_t2, t1.pn_advice_t1, t1.pn_advice_t2, t1.pn_terr_t1, t1.pn_terr_t2, t1.pn_event, t1.pn_night_srt, t1.pn_night_end, t2.platform
FROM game_option t1, lord t2 WHERE t1.lord_pk = t2.lord_pk AND t1.lord_pk = $1', [$row['lord_pk']]);
		if (!$PgGame->fetch()) {
			continue;
		}

		// pn_token
		if (!$PgGame->row['pn_token']) {
			continue;
		}

		$mesg = $_MESG[$row['type']];

		// enable check with night time term
		$ts = $PgGame->row['pn_night_srt'];
		$te = $PgGame->row['pn_night_end'];
		$hour = date('H');
		$col = 't1';

		if ($ts < $te) {
			if ($hour >= $ts && $hour < $te) {
				$col = 't2';
			}
		} else if ($ts > $te) {
			if ($hour >= $ts || $hour < $te) {
				$col = 't2';
			}
		}

		$col = 'pn_'. $mesg[0]. '_'. $col;

		if ($PgGame->row[$col] == 'N') {
			continue;
		}

		if (str_starts_with($PgGame->row['pn_token'], 'APA')) {
			// 안드로이드는 title에 영지명 추가
			$platform = 'A';
			$title = $mesg[1]. ' ['. GAME_SERVER_NAME. ']';
			if ($row['posi_title']) {
				$title .= '[' . $row['posi_title'] .']';
			}
			$message = sprintf($mesg[2], $row['info']);
		} else {
			if ($PgGame->row['platform'] == 'AB') {
				$platform = 'J';
			} else {
				$platform = 'I';
			}

			// 아이폰은 내용에 영지명 추가
			$title = '';
			if ($row['posi_title']) {
				$message = sprintf('['. GAME_SERVER_NAME. '][' . $row['posi_title'] .'] ' . $mesg[2], $row['info']);
			} else {
				$message = sprintf('['. GAME_SERVER_NAME. '] ' . $mesg[2], $row['info']);
			}
		}
		$data[] = [$platform, $PgGame->row['pn_token'], $row['lord_pk'], $title, $message];
	}

	if (COUNT($data) > 0) {
		$json = json_encode($data);

		$zArr = explode('//', CONF_PNS_GATEWAY_URL);
		list($ip, $dummy) = explode('/', $zArr[1], 2);

		$res = qbw_get_http($ip, 80, 'POST', '/pns_gateway.php', 'data='. $json);
		// print_r($res);
	}

	if ($cnt < 100) {
		exit;
	}
}
