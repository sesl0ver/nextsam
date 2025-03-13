<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_info_chat_block', $Render->wrap(function (array $params) use ($Render, $i18n) {
	if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
		// 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
		header("HTTP/1.0 404 Not Found");
		return $Render->view();
	}

	$page = $params['page']; // get the requested page
	$limit = $params['rows']; // get how many rows we want to have into the grid

	$Gm = new Gm();
	$Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
	$PgGame = new Pg('SELECT');

	$total_count_sql = "SELECT COUNT(lord_pk) FROM lord WHERE lord_name LIKE '%{$params['search']}%'";
	$PgGame->query($total_count_sql);

	$count = $PgGame->fetchOne();
	$total_page = ($count > 0) ? ceil($count/$limit) : 0;
	$page = ($page > $total_page) ? $total_page : $page;
	$offset_start = $limit * $page - $limit;
	$offset_start = ($offset_start < 0)? 0 : $offset_start;

	$offset_start = ($offset_start < 0) ? 0 : $offset_start;
	$PgGame->query("SELECT lord_pk, lord_name, regist_dt, last_login_dt, is_chat_blocked, chat_block_start_dt, chat_block_end_dt, is_logon FROM lord WHERE lord_name LIKE '%{$params['search']}%' ORDER BY lord_pk DESC LIMIT $1 OFFSET $2", [$limit, $offset_start]);

	$response = new stdClass();
	$response->page = $page;
	$response->total = $total_page;
	$response->records = $count;
	$response->rows = [];

	$i = 0;
	while ($PgGame->fetch()) {
		$response->rows[$i] = [];
		$response->rows[$i]['id'] = $PgGame->row['lord_pk'];
		$response->rows[$i]['cell'] = [$PgGame->row['lord_pk'], $PgGame->row['lord_name'], $PgGame->row['regist_dt'], $PgGame->row['last_login_dt'], (($PgGame->row['is_chat_blocked'] == 'N') ? '정상' : '블럭 중'), $PgGame->row['chat_block_start_dt'], $PgGame->row['chat_block_end_dt'], (($PgGame->row['is_logon'] == 'N') ? '로그아웃' : '로그인 중')];
		$i++;
	}

	return $Render->view(json_encode($response));
}));

$app->post('/admin/gm/api/chatBlock', $Render->wrap(function (array $params) use ($Render, $i18n) {
	if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
		// 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
		header("HTTP/1.0 404 Not Found");
		return $Render->view();
	}

	$Gm = new Gm();
	$checkPermit = $Gm->checkGMPermission(['BLOCK']);
	if (is_string($checkPermit)) {
		return $Render->view(json_encode(['result' => 'fail', 'msg' => "{$checkPermit} 권한이 없습니다."]));
	}
	$Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
	$PgGame = new Pg('SELECT');

	// 건네줘야되는것 lord_pk , block_cause // 어느 군주를 무슨 이유로
	$PgGame->query('SELECT lord_name, is_chat_blocked, blocked_cause FROM lord WHERE lord_pk = $1', [$params['lord_pk']]);
	if (!$PgGame->fetch()) {
		return $Render->view(json_encode(['result' => 'fail', 'msg' => '해당 유저가 존재하지 않습니다.']));
	}

	$block_info = $PgGame->row;

	if ($block_info['is_chat_blocked'] == 'Y') {
		return $Render->view(json_encode(['result' => 'fail', 'msg' => '다음과 같은 사유로 이미 채팅을 차단 중인 유저입니다.'."\n".$block_info['blocked_cause']]));
	}

	if (iconv_strlen($params['blocked_cause'], 'utf-8') < 1) {
		return $Render->view(json_encode(['result' => 'fail', 'msg' => '사유를 적지 않으면 유저를 차단할 수 없습니다.']));
	}
    $interval_time = $params['limit_block_date'] - time();
	$result = $PgGame->query("UPDATE lord SET is_chat_blocked = $1, chat_block_start_dt = now(), chat_block_end_dt = now() + interval '$interval_time seconds' WHERE lord_pk = $2", ['Y', $params['lord_pk']]);
	if (! $result) {
		return $Render->view(json_encode(['result' => 'fail', 'msg' => '유저의 차단하는 중에 오류가 발생하였습니다.']));
	}

    // 차단 후 세션 제거

	// 히스토리 기록
	$PgGm = new Pg('GM');
	$description = ['action' => 'chat_block', 'selected_server' => $_SESSION['selected_server'], 'lord' => ['lord_pk' => $params['lord_pk'], 'lord_name' => $block_info['lord_name'], 'block_cause' => $params['blocked_cause']]];
	$PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'K', serialize($description)]);

	return $Render->view(json_encode(['result' => 'ok']));
}));