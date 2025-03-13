<?php
/**
 * TODO 오버랭크 영웅을 덜어내어 더 이상 사용안함.
 */

set_time_limit(360);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');
$NsGlobal = new NsGlobal();
$Session = new Session(false);

$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', ['bulkload_m_npc_over_rank_hero']);

$Hero = new Hero($Session, $PgGame);

try {
    $PgGame->begin();

    /*
     * 오버랭크 영웅 생성
     */
    for ($level = 1; $level <= 3; $level++)
    {

        $PgGame->query('SELECT m_hero_base_pk, rare_type FROM m_hero_base WHERE point_level = $1', [$level]);
        $PgGame->fetchAll();
        $hero_list = $PgGame->rows;

        for ($i = 1; $i <= 243; $i++)
        {
            shuffle($hero_list);
            $hero_pk = $Hero->getNewHero('POINT', null, $hero_list[0]['rare_type'], $hero_list[0]['m_hero_base_pk'], null, null, null, 'npc_point', 'Y');

            $sql = <<< EOF
INSERT INTO m_npc_hero
( type, hero_pk, level, regist_dt )
VALUES
( $1, $2, $3, now() )

EOF;

            $query_params = ['point', $hero_pk, $level];
            $PgGame->query($sql, $query_params);
        }
    }
//////////////////////////////////////////////////

    echo 'do make m_npc_hero masterdata cache file'. "\n";

    $PgGame->commit();
} catch (Throwable $e) {
    $PgGame->rollback();
    print_r($e);
}