<?php
global $app, $Render, $i18n;

$app->post('/api/worldFavorite/list', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    if ($params['type'] == 'favorite') {
        $PgGame->query('SELECT a.posi_favo_pk, a.posi_pk, b.type, b.level, a.memo, b.lord_pk
FROM position_favorite a, position b where a.lord_pk = $1 AND a.type = $2 AND a.posi_pk = b.posi_pk ORDER BY posi_favo_pk DESC', [$Session->lord['lord_pk'], 'F']);
        $arr = [];

        while($PgGame->fetch()) {
            $arr[$PgGame->row['posi_pk']] = $PgGame->row;
        }

        foreach ($arr AS $k => $v) {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
            $arr[$k]['lord_name'] = $PgGame->fetchOne();
        }

        return $Render->nsXhrReturn('success', null, $arr);
    } else if ($params['type'] == 'recent') {
        $PgGame->query('SELECT a.posi_favo_pk, a.posi_pk, b.type, b.level, a.memo FROM position_favorite a, position b  where a.lord_pk = $1 AND a.type = $2
AND a.posi_pk = b.posi_pk ORDER BY posi_favo_pk DESC LIMIT 30', [$Session->lord['lord_pk'], 'R']);
        $arr = [];
        while($PgGame->fetch()) {
            $arr[$PgGame->row['posi_pk']] = $PgGame->row;
        }

        foreach ($arr AS $k => $v) {
            $PgGame->query('SELECT lord_name FROM lord WHERE lord_pk = $1', [$Session->lord['lord_pk']]);
            $arr[$k]['lord_name'] = $PgGame->fetchOne();
        }

        return $Render->nsXhrReturn('success', null, $arr);
    } else if ($params['type'] == 'suppress') {
        $PgGame->query("SELECT b.posi_pk, 'NPC_SUPP' AS type FROM lord a, suppress_position b WHERE a.lord_pk = $1 AND a.last_supp_pk = b.supp_pk AND b.status = $2", [$Session->lord['lord_pk'], 'N']);
        $arr = [];

        while($PgGame->fetch()) {
            $arr[$PgGame->row['posi_pk']] = $PgGame->row;
        }

        $PgGame->query('SELECT b.target_cnt FROM lord a, suppress b WHERE a.lord_pk = $1 AND a.last_supp_pk = b.supp_pk', [$Session->lord['lord_pk']]);
        $PgGame->fetchOne();

        return $Render->nsXhrReturn('success', null, $arr);
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/worldFavorite/add', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // Validation 검사를 위해...
    $params['posi_pk'] = $params['target_posi_pk'];

    $PgGame->query('SELECT posi_favo_pk, memo FROM position_favorite WHERE lord_pk = $1 AND posi_pk = $2 AND type = $3', [$Session->lord['lord_pk'], $params['posi_pk'], 'F']);
    $PgGame->fetch();

    $memo = '';
    if ($PgGame->row['posi_favo_pk']) {
        $add_type = 'modify';
        $memo = $PgGame->row['memo'];
    } else {
        $PgGame->query('SELECT COUNT(posi_favo_pk) FROM position_favorite WHERE lord_pk = $1 AND type = $2', [$Session->lord['lord_pk'], 'F']);
        if ($PgGame->fetchOne() >= 30) {
            throw new ErrorHandler('error', $i18n->t('msg_world_favorite_max')); // 최대 수량을 초과하여<br />더 이상 바로가기 등록할 수 없습니다.<br />(최대 30개)
        }
        $add_type = 'add';
    }

    return $Render->nsXhrReturn('success', null, ['add_type' =>$add_type, 'memo' => $memo]);
}));

$app->post('/api/worldFavorite/addSave', $Render->wrap(function (array $params) use ($Render, $i18n) {

    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    // Validation 검사를 위해...
    $params['posi_pk'] = $params['target_posi_pk'];

    $PgGame->query('SELECT posi_favo_pk FROM position_favorite WHERE lord_pk = $1 AND posi_pk = $2 AND type = $3', [$Session->lord['lord_pk'], $params['posi_pk'], 'F']);
    if ($PgGame->fetchOne()) {
        $PgGame->query('UPDATE position_favorite SET memo = $1 WHERE lord_pk = $2 AND posi_pk = $3 AND type = $4', [$params['memo'], $Session->lord['lord_pk'], $params['posi_pk'], 'F']);
    } else {
        $PgGame->query('SELECT COUNT(posi_favo_pk) FROM position_favorite WHERE lord_pk = $1 AND type = $2', [$Session->lord['lord_pk'], 'F']);
        if ($PgGame->fetchOne() >= 30) {
            throw new ErrorHandler('error', $i18n->t('msg_world_favorite_max')); // 최대 수량을 초과하여<br />더 이상 바로가기 등록할 수 없습니다.<br />(최대 30개)
        }

        $PgGame->query('INSERT INTO position_favorite (lord_pk, posi_pk, memo, regist_dt, type) VALUES ($1, $2, $3, now(), $4)', [$Session->lord['lord_pk'], $params['posi_pk'], $params['memo'], 'F']);
    }

    return $Render->nsXhrReturn('success');
}));

$app->post('/api/worldFavorite/delete', $Render->wrap(function (array $params) use ($Render, $i18n) {
    $Session = new Session();
    $PgGame = new Pg('DEFAULT');

    $str = $params['delete_position'];
    $PgGame->query("DELETE FROM position_favorite WHERE posi_favo_pk IN ($str)");

    return $Render->nsXhrReturn('success');
}));
