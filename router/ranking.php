<?php
global $app, $Render, $i18n;

// TODO 차후 DB가 아니라 Redis 를 기준으로 랭킹을 기록하도록 변경 필요.
//  DB에는 누적 랭킹(지난주, 지난달 이라던가?) 기록이 필요한 경우 사용하면 좋을 듯?


// 군주 랭킹 리스트 가져오기
$app->post('/api/ranking/lordList', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    // $PgGame = new Pg('DEFAULT');
    $Redis = new RedisCache();

    // Redis 사용하므로 페이지는 사용하지 않음. (부하가 적을테니...)
    // TODO 군주가 많을때를 대비하여 1000명으로 제한하거나 하는 방안도 고려할 필요가 있을듯?
    $rankings = $Redis->zRange("ranking:lord:{$params['order']}");
    $total_count = count($rankings);

    $rankings = array_map(function ($r) { return json_decode($r, true); }, $rankings);
    $my_key = array_search($Session->lord['lord_pk'], array_column($rankings, 'lord_pk')); // 자신의 랭크 찾아오기
    $my_rank = ($my_key !== false) ? $rankings[$my_key]['rank_power'] : 0;

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'list' => $rankings, 'my_rank' => $my_rank]);
}));

$app->post('/api/ranking/allyList', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    // $PgGame = new Pg('DEFAULT');
    $Redis = new RedisCache();

    $rankings = $Redis->zRange("ranking:alliance:{$params['order']}");
    $total_count = count($rankings);

    $rankings = array_map(function ($r) { return json_decode($r, true); }, $rankings);

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'list' => $rankings]);
}));

// 영웅 랭킹 리스트 가져오기
$app->post('/api/ranking/heroList', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    // $PgGame = new Pg('DEFAULT');
    $Redis = new RedisCache();

    // Redis 사용하므로 페이지는 사용하지 않음. (부하가 적을테니...)
    $rankings = $Redis->zRange("ranking:hero:{$params['order']}");
    $total_count = count($rankings);

    $rankings = array_map(function ($r) { return json_decode($r, true); }, $rankings);

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'list' => $rankings]);
}));

$app->post('/api/ranking/areaList', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    // $PgGame = new Pg('DEFAULT');
    $Redis = new RedisCache();

    // TODO 준비 중

    return $Render->nsXhrReturn('success', null, ['total_count' => 0, 'list' => []]);
}));











/* TODO 랭킹 구조 변경으로 사용하지 않는 API
 * $app->post('/api/ranking/myLordRanking', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $max_list_count = 15;

    // 총 갯수 구하기
    $PgGame->query('SELECT COUNT(lord_pk) FROM ranking_lord');
    $total_count = $PgGame->fetchOne();

    // 컬럼명 정하기
    $rank_column = 'ranking_lord.rank_power';

    if ($params['order'] == 'power' || $params['order'] == 'army_point' || $params['order'] == 'attack_point' || $params['order'] == 'defence_point') {
        $rank_column = 'ranking_lord.rank_'.$params['order'];
    }

    // 지금 보고 있을 정렬 기준에서 현재 군주의 랭크 가져오기
    $PgGame->query("SELECT {$rank_column} FROM ranking_lord WHERE lord_pk = $1", [$Session->lord['lord_pk']]);
    $rank_num = $PgGame->fetchOne();

    if (!$rank_num) {
        throw new ErrorHandler('error', '랭킹 산정 대기 중 입니다.');
    }

    $list = null;
    if ($total_count > 0) {
        // 응답 - 총 페이지 수
        $total_page = (INT)($total_count / $max_list_count);
        $total_page += ($total_count % $max_list_count > 0)? 1 : 0;

        // 오프셋 구하기
        $offset_num = ($rank_num % $max_list_count == 0 ? (INT)($rank_num / $max_list_count) - 1 : (INT)($rank_num / $max_list_count)) * $max_list_count;

        // 현재 군주가 있는 페이지의 내용 가져오기
        $PgGame->query("SELECT ranking_lord.lord_pk, ranking_lord.lord_name, ranking_lord.alli_pk, ranking_lord.lord_level, ranking_lord.position_cnt, {$rank_column} AS rank, ranking_lord.power, ranking_lord.attack_point, ranking_lord.defence_point, ranking_lord.army_point, alliance.title
FROM ranking_lord LEFT OUTER JOIN alliance ON (ranking_lord.alli_pk = alliance.alli_pk) ORDER BY {$rank_column} LIMIT {$max_list_count} OFFSET $1", [$offset_num]);
        if ($PgGame->fetchAll()) {
            $list = $PgGame->rows;
        }

        $list = (!$list || !count($list)) ? [] : $list;

        // 페이지 번호 구하기
        $page_num = (INT)(($list[0]['rank'] - 1) / $max_list_count) + 1;

        // 응답 후 종료
        return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'total_page' => $total_page, 'curr_page' => $page_num, 'list' => $list]);
    }

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'list' => []]);
}));

$app->post('/api/ranking/myAllianceRanking', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $max_list_count = 15;

    // 내 동맹이 있는 랭킹 페이지 보기
    if ($Session->lord['alli_pk'] == null) {
        throw new ErrorHandler('error', '가입한 동맹이 없습니다.');
    }

    // 총 갯수 구하기
    $PgGame->query('SELECT COUNT(alli_pk) FROM ranking_alliance WHERE ranking_alliance.power_rank IS NOT NULL');
    $total_count = $PgGame->fetchOne();

    // 컬럼명 정하기
    $rank_column = 'ranking_alliance.power_rank';

    if ($params['order'] == 'power' || $params['order'] == 'attack_point' || $params['order'] == 'defence_point') {
        $rank_column = 'ranking_alliance.'.$params['order'].'_rank';
    }

    // 지금 보고 있을 기준에서 현재 군주가 속한 동맹의 랭크 가져오기
    $PgGame->query("select {$rank_column} from ranking_alliance where ranking_alliance.power_rank IS NOT NULL AND alli_pk = $1", [$Session->lord['alli_pk']]);
    $rank_num = $PgGame->fetchOne();

    if (!$rank_num) {
        throw new ErrorHandler('error', '랭킹 산정 대기 중인 동맹 입니다.');
    }

    $list = null;
    if ($total_count > 0) {
        // 응답 - 총 페이지 수
        $total_page = (INT)($total_count / $max_list_count);
        $total_page += ($total_count % $max_list_count > 0)? 1 : 0;

        // 오프셋 구하기
        $offset_num = ($rank_num % $max_list_count == 0 ? (INT)($rank_num / $max_list_count) - 1 : (INT)($rank_num / $max_list_count)) * $max_list_count;

        // 현재 군주가 속한 동맹이 있는 페이지의 내용 가져오기
        $PgGame->query("SELECT alliance.alli_pk, alliance.title, alliance.master_lord_pk, {$rank_column} AS rank, ranking_alliance.power, ranking_alliance.attack_point, ranking_alliance.defence_point, alliance.lord_name, alliance.now_member_count
FROM ranking_alliance, alliance WHERE ranking_alliance.alli_pk = alliance.alli_pk  ORDER BY {$rank_column} LIMIT {$max_list_count} OFFSET $1", [$offset_num]);
        if ($PgGame->fetchAll()) {
            $list = $PgGame->rows;
        }

        $list = (!$list || !count($list)) ? [] : $list;

        // 페이지 번호 구하기
        $page_num = (($list[0]['rank'] - 1) / $max_list_count) + 1;

        // 응답 후 종료
        return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'total_page' => $total_page, 'curr_page' => $page_num, 'list' => $list]);
    }

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'list' => []]);
}));

// 군주 찾기
$app->post('/api/ranking/lordSearch', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $max_list_count = 15;
    $like_search_limit = 50;

    // 컬럼명 구하기
    $order_by = 'rank_power';
    $rank_column = 'ranking_lord.rank_power';

    if ($params['order'] == 'power' || $params['order'] == 'army_point' || $params['order'] == 'attack_point' || $params['order'] == 'defence_point') {
        $order_by = 'rank_'.$params['order'];
        $rank_column = 'ranking_lord.rank_'.$params['order'];
    }

    $list = null;

    // 군주명을 보내진 문자열로 LIKE
    $PgGame->query("SELECT ranking_lord.lord_pk, ranking_lord.lord_name, ranking_lord.alli_pk, ranking_lord.lord_level, ranking_lord.position_cnt, $rank_column AS rank, ranking_lord.power, ranking_lord.attack_point, ranking_lord.defence_point, ranking_lord.army_point, alliance.title
FROM ranking_lord LEFT OUTER JOIN alliance ON (ranking_lord.alli_pk = alliance.alli_pk) WHERE ranking_lord.lord_name LIKE $1 ORDER BY $order_by LIMIT $like_search_limit", ["%{$params['search_word']}%"]);
    if ($PgGame->fetchAll()) {
        $list = $PgGame->rows;
    }

    if (is_array($list) && count($list) == 1 && $list[0]['lord_name'] == $params['search_word']) {
        // 결과가 하나면 -> 그 한개가 있는 페이지의 내용을 통째로 응답

        // 총 갯수 구하기
        $PgGame->query('SELECT COUNT(lord_pk) FROM ranking_lord');
        $total_count = $PgGame->fetchOne();

        if ($total_count > 0) {
            // 응답 - 총 페이지 수 구하기
            $total_page = (INT)($total_count / $max_list_count);
            $total_page += ($total_count % $max_list_count > 0)? 1 : 0;

            // 오프셋 구하기
            $offset_num = ((INT)($list[0]['rank'] / $max_list_count) - (($list[0]['rank'] % $max_list_count == 0) ? 1 : 0)) * $max_list_count;

            // 해당 페이지의 내용 가져오기
            $PgGame->query("SELECT ranking_lord.lord_pk, ranking_lord.lord_name, ranking_lord.alli_pk, ranking_lord.lord_level, ranking_lord.position_cnt, $rank_column AS rank, ranking_lord.power, ranking_lord.attack_point, ranking_lord.defence_point, ranking_lord.army_point, ranking_alliance.title
FROM ranking_lord LEFT OUTER JOIN ranking_alliance ON (ranking_lord.alli_pk = ranking_alliance.alli_pk) ORDER BY $rank_column LIMIT $max_list_count OFFSET $1", [$offset_num]);
            if ($PgGame->fetchAll()) {
                $list = $PgGame->rows;
            }

            $list = (!$list || !count($list)) ? [] : $list;

            // 페이지 번호 구하기
            $page_num = (INT)(($list[0]['rank'] - 1) / $max_list_count) + 1;

            // 응답 후 종료
            return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'total_page' => $total_page, 'curr_page' => $page_num, 'first_row_rank' => $list[0]['rank'],'list' => $list]);
        }
    } else {
        // 결과가 여러개면 -> LIKE로 찾아진 모든 것을 전부 반환
        return $Render->nsXhrReturn('success', null, ['total_count' => 0, 'total_page' => 0, 'curr_page' => 0, 'list' => $list]);
    }

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'list' => []]);
}));

// 동맹 찾기
$app->post('/api/ranking/allianceSearch', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $max_list_count = 15;
    $like_search_limit = 50;

    // 컬럼명 정하기
    $order_by = 'power_rank';
    $rank_column = 'ranking_alliance.power_rank';

    if ($params['order'] == 'power' || $params['order'] == 'attack_point' || $params['order'] == 'defence_point') {
        $order_by = $params['order'].'_rank';
        $rank_column = 'ranking_alliance.'.$params['order'].'_rank';
    }

    $list = null;

    // 동맹명을 보내진 문자열로 LIKE
    $PgGame->query("SELECT alliance.alli_pk, alliance.title, alliance.master_lord_pk, $rank_column AS rank, ranking_alliance.power, ranking_alliance.attack_point, ranking_alliance.defence_point, alliance.lord_name, alliance.now_member_count
FROM ranking_alliance, alliance WHERE ranking_alliance.alli_pk = alliance.alli_pk AND ranking_alliance.power_rank IS NOT NULL AND ranking_alliance.title LIKE $1 ORDER BY $order_by LIMIT {$like_search_limit}", ["%{$params['search_word']}%"]);
    if ($PgGame->fetchAll()) {
        $list = $PgGame->rows;
    }

    if (count($list) == 1 && $list[0]['title'] == $params['search_word']) {
        // 결과가 하나면 -> 그 한개가 있는 페이지의 내용을 통째로 응답

        // 총 갯수 구하기
        $PgGame->query('SELECT COUNT(alli_pk) FROM ranking_alliance');
        $total_count = $PgGame->fetchOne();

        if ($total_count > 0) {
            // 응답 - 총 페이지 수 구하기
            $total_page = (INT)($total_count / $max_list_count);
            $total_page += ($total_count % $max_list_count > 0)? 1 : 0;

            // 오프셋 구하기
            $offset_num = ((INT)($list[0]['rank'] / $max_list_count) - (($list[0]['rank'] % $max_list_count == 0) ? 1 : 0)) * $max_list_count;

            // 해당 페이지의 내용 가져오기
            $PgGame->query("SELECT alliance.alli_pk, alliance.title, alliance.master_lord_pk, {$rank_column} AS rank, ranking_alliance.power, ranking_alliance.attack_point, ranking_alliance.defence_point, alliance.lord_name, alliance.now_member_count
FROM ranking_alliance, alliance WHERE ranking_alliance.alli_pk = alliance.alli_pk ORDER BY {$rank_column} LIMIT {$max_list_count} OFFSET $1", [$offset_num]);
            if ($PgGame->fetchAll()) {
                $list = $PgGame->rows;
            }

            $list = (!$list || !count($list)) ? [] : $list;

            // 페이지 번호 구하기
            $page_num = (($list[0]['rank'] - 1) / $max_list_count) + 1;

            // 응답 후 종료
            return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'total_page' => $total_page, 'curr_page' => $page_num, 'list' => $list]);
        }
    } else {
        // 결과가 여러개면 -> LIKE로 찾아진 모든 것을 전부 반환
        return $Render->nsXhrReturn('success', null, ['total_count' => 0, 'total_page' => 0, 'curr_page' => 1, 'list' => $list]);
    }

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'list' => []]);
}));

$app->post('/api/ranking/heroSearch', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $max_list_count = 15;
    $like_search_limit = 50;

    // 영웅 찾기
    // 컬럼명 정하기
    $order_by = 'rank_leadership';
    $rank_type = 'L';

    if ($params['order'] == 'leadership' || $params['order'] == 'mil_force' || $params['order'] == 'intellect' || $params['order'] == 'politics' || $params['order'] == 'charm') {
        $order_by = 'rank_'.$params['order'];
        $rank_type = strtoupper(substr($params['order'], 0, 1));
    }

    $list = null;

    // 영웅명을 보내진 문자열로 LIKE
    $PgGame->query("
SELECT ranking_hero.rank_type, ranking_hero.hero_pk, ranking_hero.m_hero_pk, ranking_hero.lord_pk, ranking_hero.lord_name, ranking_hero.rank, ranking_hero.leadership,
ranking_hero.mil_force, ranking_hero.intellect, ranking_hero.politics, ranking_hero.charm, m_hero_base.name
FROM ranking_hero, m_hero, m_hero_base WHERE ranking_hero.rank_type = $2 AND ranking_hero.m_hero_pk = m_hero.m_hero_pk AND m_hero.m_hero_base_pk = m_hero_base.m_hero_base_pk
AND m_hero_base.name LIKE $1 ORDER BY rank LIMIT {$like_search_limit};
", ["%{$params['search_word']}%", $rank_type]);
    if ($PgGame->fetchAll()) {
        $list = $PgGame->rows;
    }

    if (is_array($list) && count($list) == 1 && $list[0]['name'] == $params['search_word']) {
        // 결과가 하나면 -> 그 한개가 있는 페이지의 내용을 통째로 응답

        // 총 갯수 구하기
        $PgGame->query('SELECT COUNT(hero_pk) FROM ranking_hero WHERE rank_type = $1', [$rank_type]);
        $total_count = $PgGame->fetchOne();

        if ($total_count > 0) {
            // 응답 - 총 페이지 수 구하기
            $total_page = (INT)($total_count / $max_list_count);
            $total_page += ($total_count % $max_list_count > 0)? 1 : 0;

            // 오프셋 구하기
            $offset_num = ((INT)($list[0]['rank'] / $max_list_count) - (($list[0]['rank'] % $max_list_count == 0) ? 1 : 0)) * $max_list_count;

            // 해당 페이지의 내용 가져오기
            $PgGame->query("SELECT rank_type, hero_pk, m_hero_pk, lord_pk, lord_name, rank, leadership, mil_force, intellect, politics, charm
FROM ranking_hero WHERE rank_type = $2 ORDER BY rank LIMIT {$max_list_count} OFFSET $1", [$offset_num, $rank_type]);
            if ($PgGame->fetchAll()) {
                $list = $PgGame->rows;
            }

            $list = (!$list || !count($list)) ? [] : $list;

            // 페이지 번호 구하기
            $page_num = (($list[0]['rank'] - 1) / $max_list_count) + 1;

            // 응답 후 종료
            return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'total_page' => $total_page, 'curr_page' => $page_num, 'list' => $list]);
        }
    } else {
        // 결과가 여러개면 -> LIKE로 찾아진 모든 것을 전부 반환
        $list = (!$list || !count($list)) ? [] : $list;
        return $Render->nsXhrReturn('success', null, ['total_count' => 0, 'total_page' => 0, 'curr_page' => 1, 'list' => $list]);
    }

    return $Render->nsXhrReturn('success', null, ['total_count' => $total_count, 'list' => []]);
}));

// 지역별 랭킹
$app->post('/api/ranking/region_ranking', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $list = [];

    // TODO $params['state']가 한글인데 괜찮나? 일단 동작은 함.
    $cache_list = $Session->Cache->get('region_ranking_list:'.$params['state']);
    $cache_time = $Session->Cache->get('region_ranking_time:'.$params['state']);

    $Alliance = new Alliance($Session, $PgGame);

    $alliance_data = [];

    if (! $cache_list || ! is_array($cache_list)) {
        $PgGame->query('SELECT position.state, sum(territory.power) as pow, lord.lord_pk, lord.lord_name, lord.alli_pk
FROM territory, lord, position WHERE position.state = $1 AND position.posi_pk = territory.posi_pk AND position.type = $2 AND position.lord_pk = lord.lord_pk
GROUP BY position.state, lord.lord_pk ORDER BY position.state, pow desc, lord.lord_pk LIMIT 10;', [$params['state'], 'T']);
        if ($PgGame->fetchAll()) {
            $list = $PgGame->rows;
            foreach ($list AS $k => $v) {
                if ($v['alli_pk'] > 0 && ! $alliance_data[$v['alli_pk']]) {
                    $alliance_data[$v['alli_pk']] = $Alliance->myAllianceInfo($v['alli_pk']);
                }
                $list[$k]['alliance'] = $alliance_data[$v['alli_pk']];
            }
        }

        $cache_time = time();
        if (is_array($list) && count($list) > 0) {
            $cache_list = $list;
            $Session->Cache->set('region_ranking_list:'.$params['state'], $list, 3600); // 1시간 마다 갱신
            $Session->Cache->set('region_ranking_time:'.$params['state'], $cache_time, 3600); // 1시간 마다 갱신
        }
    }

    return $Render->nsXhrReturn('success', null, ['total_count' => 0, 'total_page' => 0, 'curr_page' => 1,'list' => $cache_list, 'time' => date('Y-m-d h:i', $cache_time)]);
}));

$app->post('/api/ranking/regionAllianceRanking', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // 지역별 동맹 랭킹
    $list = [];

    $PgGame->query('SELECT position.state, alliance.power as pow, alliance.title, lord.lord_name, lord.alli_pk FROM alliance, lord, position
WHERE position.state = $1 AND alliance.master_lord_pk = position.lord_pk AND position.type = $2 AND position.lord_pk = lord.lord_pk
GROUP BY position.state, lord.lord_pk, alliance.power, alliance.title ORDER BY position.state, pow desc, lord.lord_pk LIMIT 10', [$params['state'], 'T']);
    if ($PgGame->fetchAll()) {
        $list = $PgGame->rows;
    }

    $cache_time = time();
    if (count($list) > 0) {
        $Session->Cache->set('region_alliance_ranking_list:'.$params['state'], $list, 3600); // 1시간 마다 갱신
        $Session->Cache->set('region_alliance_ranking_time:'.$params['state'], $cache_time, 3600); // 1시간 마다 갱신
    }

    return $Render->nsXhrReturn('success', null, ['total_count' => 0, 'total_page' => 0, 'curr_page' => 1,'list' => $list, 'time' => date('Y-m-d h:i', $cache_time)]);
}));*/
