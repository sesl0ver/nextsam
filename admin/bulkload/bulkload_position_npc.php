<?php
set_time_limit(360);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');
$PgGame2 = new Pg('DEFAULT');

try {
    $PgGame->begin();
    $PgGame2->begin();



    $PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', ['bulkload_position_npc']);

    $PgGame->query('TRUNCATE position_npc');

    /*
     * 마스터데이터 로딩
     */
// m_npc_territory
    $_M['NPC_TERR'] = [];
    $sql = "SELECT * FROM m_npc_territory";
    $PgGame->Query($sql);
    while($PgGame->fetch())
    {
        $r =& $PgGame->row;

        $_M['NPC_TERR'][$r['level']] = $PgGame->row;
    }

// m_npc_hero (territory)
    $_M['NPC_HERO']['territory'] = [];

    $sql = "SELECT level, hero_pk FROM m_npc_hero WHERE type = 'territory'";
    $PgGame->Query($sql);
    while($PgGame->fetch())
    {
        $r =& $PgGame->row;

        $_M['NPC_HERO']['territory'][$r['level']][] = $r['hero_pk'];
    }

    /*
     * 초기화
     */
//$sql = "SELECT posi_pk, level FROM position WHERE posi_pk IN ('245x160', '245x162', '246x163', '242x164', '244x164', '243x166')";
    $sql = "SELECT posi_pk, level FROM position WHERE type = 'N'";
    $PgGame->query($sql);
    while($PgGame->fetch())
    {
        $r =& $PgGame->row;

        $m_npc_terr =& $_M['NPC_TERR'][$r['level']];
        $m_npc_hero =& $_M['NPC_HERO']['territory'][$r['level']];

        /*
         * 황건적 장수 선택
         */
        $zArr = [];
        shuffle($m_npc_hero);
        $zArr[] = $m_npc_hero[0];
        $zArr[] = $m_npc_hero[1];
        $zArr[] = $m_npc_hero[2];

        $sql = 'SELECT hero_pk, mil_force_basic+mil_force_enchant+mil_force_plusstat AS mil_force FROM hero WHERE hero_pk = ANY ($1) ORDER BY mil_force DESC';
        $query_params = ['{'. implode(',', $zArr). '}'];
        $PgGame2->query($sql, $query_params);

        $zArr = [];
        while ($PgGame2->fetch())
        {
            $zArr[] = $PgGame2->row['hero_pk'];
        }

        $captain_hero_pk = $zArr[0];
        $director_hero_pk = $zArr[1];
        $staff_hero_pk = $zArr[2];

        $sql = <<< EOF
INSERT INTO position_npc
(
 posi_pk, status, captain_hero_pk, director_hero_pk, staff_hero_pk,
 reso_gold, reso_food, reso_horse, reso_lumber, reso_iron,
 army_worker, army_infantry, army_pikeman, army_scout, army_spearman,
 army_armed_infantry, army_archer, army_horseman, army_armed_horseman, army_transporter, army_bowman,
 army_battering_ram, army_catapult, army_adv_catapult,
 fort_trap, fort_abatis, fort_tower
) VALUES (
 $1, 'N', $2, $3, $4,
 $5, $6, $7, $8, $9,
 $10, $11, $12, $13, $14,
 $15, $16, $17, $18, $19, $20,
 $21, $22, $23,
 $24, $25, $26
)
EOF;

        $query_params =
            [
                $r['posi_pk'], $captain_hero_pk, $director_hero_pk, $staff_hero_pk,
                $m_npc_terr['reso_gold'], $m_npc_terr['reso_food'], $m_npc_terr['reso_horse'], $m_npc_terr['reso_lumber'], $m_npc_terr['reso_iron'],
                $m_npc_terr['army_worker'], $m_npc_terr['army_infantry'], $m_npc_terr['army_pikeman'], $m_npc_terr['army_scout'], $m_npc_terr['army_spearman'],
                $m_npc_terr['army_armed_infantry'], $m_npc_terr['army_archer'], $m_npc_terr['army_horseman'], $m_npc_terr['army_armed_horseman'], $m_npc_terr['army_transporter'], $m_npc_terr['army_bowman'],
                $m_npc_terr['army_battering_ram'], $m_npc_terr['army_catapult'], $m_npc_terr['army_adv_catapult'],
                $m_npc_terr['fort_trap'], $m_npc_terr['fort_abatis'], $m_npc_terr['fort_tower']
            ];

        $PgGame2->query($sql, $query_params);

        //print_r($m_npc_terr);
        //print_r($m_npc_hero);
    }

    $PgGame->commit();
    $PgGame2->commit();
} catch (Throwable $e) {
    $PgGame->rollback();
    $PgGame2->rollback();

}