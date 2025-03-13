<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/serv_info_hero_give', $Render->wrap(function (array $params) use ($Render, $i18n) {
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
	$PgGame->query("SELECT lord_pk, lord_name FROM lord WHERE lord_name LIKE '%{$params['search']}%' LIMIT $1 OFFSET $2", [$limit, $offset_start]);

	$response = new stdClass();
	$response->page = $page;
	$response->total = $total_page;
	$response->records = $count;
	$response->rows = [];

	$i = 0;
	while ($PgGame->fetch())
	{
		$response->rows[$i] = [];
		$response->rows[$i]['id'] = $PgGame->row['lord_pk'];
		$response->rows[$i]['cell'] = [$_SESSION['selected_server']['server_name'], $PgGame->row['lord_name']];
		$i++;
	}

	return $Render->view(json_encode($response));
}));

$app->post('/admin/gm/api/heroGive', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $checkPermit = $Gm->checkGMPermission(['EDIT']);
    if (is_string($checkPermit)) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => "{$checkPermit} 권한이 없습니다."]));
    }
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');
    $Session = new Session(false);

    $PgGame->query('SELECT m_hero_base_pk, rare_type, over_type, yn_new_gacha, yn_modifier FROM m_hero_base WHERE m_hero_base_pk = $1', [$params['hero_base_pk']]);
    if (! $PgGame->fetch()) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '존재하지 않는 장수를 지급할 수 없습니다.']));
    }
    $m_hero_base_pk = $PgGame->row['m_hero_base_pk'];
    $rare_type = $PgGame->row['rare_type'];
    $over_type = $PgGame->row['over_type'];
    $yn_new_gacha = $PgGame->row['yn_new_gacha'];
    $yn_modifier = $PgGame->row['yn_modifier'];

    $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$params['lord_pk']]);
    if (!$PgGame->fetch()) {
        return $Render->view(json_encode(['result' => 'fail', 'msg' => '존재하지 않는 유저에게 장수를 지급할 수 없습니다.']));
    }

    $lord_name = $PgGame->row['lord_name'];

    // $PgGame->query('UPDATE m_hero SET left_count = left_count + 1 WHERE m_hero_base_pk = $1 AND level = $2', [$m_hero_base_pk, $params['level']]);

    $Hero = new Hero($Session, $PgGame);

    $hero_pk = $Hero->getNewHero('FREE', $params['level'], $rare_type, $m_hero_base_pk, null, null, null, 'gm_hero_give', $over_type, $yn_new_gacha, $yn_modifier);
    $Hero->setMyHeroCreate($hero_pk, $params['lord_pk'], 'V', null, null, 'N', "gm_hero_give[{$params['cause']}];");

    // 히스토리 기록
    $PgGm = new Pg('GM');
    $description = ['action' => 'gm_give_hero', 'selected_server' => $_SESSION['selected_server'], 'lord' => ['lord_pk' => $params['lord_pk'], 'lord_name' => $lord_name], 'cause' => $params['cause'], 'm_hero_base_pk' => $params['hero_base_pk'], 'level' => $params['level']];
    $PgGm->query('INSERT INTO gm_log(gm_pk, gm_id, regist_dt, "type", description) VALUES ($1, $2, now(), $3, $4)', [$_SESSION['gm_pk'], $_SESSION['gm_id'], 'I', serialize($description)]);



    return $Render->view(json_encode(['result' => 'ok']));
}));