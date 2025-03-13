<?php
global $app, $Render, $i18n;

$app->post('/admin/gm/api/terr_camp_army', $Render->wrap(function (array $params) use ($Render, $i18n) {
    if (! isset($_SESSION) || ! isset($_SESSION['gm_active'])) {
        // 세션이 설정되지 않은 경우 이 파일을 볼 수없음.
        header("HTTP/1.0 404 Not Found");
        return $Render->view();
    }

    $Gm = new Gm();
    $Gm->selectPgGame($_SESSION['selected_server']['server_pk']);
    $PgGame = new Pg('SELECT');

    $PgGame->query('SELECT troop.dst_posi_pk, troop.status, troop.cmd_type, troop.troop_type, troop.to_position, position.level, position.type,
	troop.captain_hero_pk, troop.captain_desc, troop.director_hero_pk, troop.director_desc, troop.staff_hero_pk, troop.staff_desc,
	troop.reso_gold, troop.reso_food, troop.reso_horse, troop.reso_lumber, troop.reso_iron, 
	troop.army_worker, troop.army_infantry, troop.army_pikeman, troop.army_spearman, troop.army_scout, troop.army_armed_infantry,
	troop.army_armed_horseman, troop.army_archer, troop.army_horseman, troop.army_transporter, troop.army_bowman,
	troop.army_battering_ram, troop.army_catapult, troop.army_adv_catapult
FROM position, troop WHERE troop.dst_posi_pk = position.posi_pk AND troop.src_posi_pk = $1', [$_SESSION['selected_terr']['posi_pk']]);
    $PgGame->fetchAll();

    return $Render->view(json_encode(['result' => 'ok', 'rows' => $PgGame->rows]));
}));



