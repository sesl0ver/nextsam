<?php
set_time_limit(360);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/constant.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');
$PgGame2 = new Pg('DEFAULT');
$NsGlobal = new NsGlobal();
$Session = new Session(false);
$i18n = new i18n();

try {
    $PgGame->begin();
    $PgGame2->begin();

    $PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', ['bulkload_m_npc_hero']);

    $Hero = new Hero($Session, $PgGame);

    /*
     * DROP CONSTRAINT
     */
    {
        // m_npc_hero
        $PgGame->query('ALTER TABLE m_npc_hero DROP CONSTRAINT m_npc_hero_hero_pk_fkey');
        // 황건적 영지
        $PgGame->query('ALTER TABLE position_npc DROP CONSTRAINT position_npc_captain_hero_pk_fkey');
        $PgGame->query('ALTER TABLE position_npc DROP CONSTRAINT position_npc_director_hero_pk_fkey');
        $PgGame->query('ALTER TABLE position_npc DROP CONSTRAINT position_npc_staff_hero_pk_fkey');
        // 토벌부대
        $PgGame->query('ALTER TABLE suppress_position DROP CONSTRAINT suppress_position_hero_pk_fkey');
        // 습격부대
        $PgGame->query('ALTER TABLE troop DROP CONSTRAINT troop_captain_hero_pk_fkey');
    }

    $PgGame->query('DELETE FROM hero WHERE hero_pk IN (SELECT hero_pk FROM m_npc_hero)');
    $PgGame->query('TRUNCATE m_npc_hero');

    /*
     * 자원지 황건장수 (고정 장수)
     */
    $_sql = "select name, rare_type, m_hero_base_pk  from m_hero_base where forces = 'PC' and rare_type in ('1', '2', '3') order by rare_type asc";
    $zArr = [];
    $PgGame->query($_sql);
    while ($PgGame->fetch())
    {
        $zArr[] = $PgGame->row;
    }

    $types = [];

    $types[] = 'F'; // Forest
    $types[] = 'G'; // Grass
    $types[] = 'L'; // Lake
    $types[] = 'M'; // Mine
    $types[] = 'R'; // Ranch

    foreach ($types AS $_type)
    {
        for ($i = 1; $i <= 10; $i++)
        {
            shuffle($zArr);

            $hero_pk = $Hero->getNewHero('FREE', $i, $zArr[0]['rare_type'], $zArr[0]['m_hero_base_pk'], null, null, null, 'npc_valley');

            $sql = <<< EOF
INSERT INTO m_npc_hero
( type, hero_pk, level, regist_dt )
VALUES
( $1, $2, $3, now() )

EOF;

            $query_params = ['resource_'. $_type, $hero_pk, $i];
            $PgGame->query($sql, $query_params);
        }
    }

//////////////////////////////////////////////////

    $types = [];

    /*
     * 토벌 황건장수
     */
    $types['suppress'] = "select name, rare_type, m_hero_base_pk  from m_hero_base where forces = 'PC' order by rare_type asc";

    /*
     * 습격부대 황건장수
     */
    $types['attack'] = "select name, rare_type, m_hero_base_pk  from m_hero_base where forces = 'PC' and rare_type in ('1', '2', '3') order by rare_type asc";

    /*
     * 성(castle) 황건장수
     */
    $types['territory'] = "select name, rare_type, m_hero_base_pk  from m_hero_base where forces = 'PC' order by rare_type asc";

    foreach ($types AS $_type => $_sql)
    {
        $zArr = [];
        $PgGame->query($_sql);
        while ($PgGame->fetch())
        {
            $zArr[] = $PgGame->row;
        }

        foreach ($zArr AS $row)
        {
            for ($i = 1; $i <= 10; $i++)
            {
                $hero_pk = $Hero->getNewHero('FREE', $i, $row['rare_type'], $row['m_hero_base_pk'], null, null, null, 'npc');

                $sql = <<< EOF
INSERT INTO m_npc_hero
( type, hero_pk, level, regist_dt )
VALUES
( $1, $2, $3, now() )

EOF;

                $query_params = [$_type, $hero_pk, $i];
                $PgGame->query($sql, $query_params);
            }
        }
    }

    $PgGame->query('UPDATE hero SET status = $1 WHERE hero_pk IN (select hero_pk from m_npc_hero)', Array('Y'));

    /*
     * 기등록 데이터에 황건장수 연결
     */

// 가상 Cache
    $PgGame->query('SELECT level, hero_pk FROM m_npc_hero WHERE type = $1', Array('territory'));
    while ($PgGame->fetch())
    {
        $r =& $PgGame->row;
        $_M[$r['level']][] = $r['hero_pk'];
    }

// 황건적 영지 장수
    $PgGame->query('SELECT t1.posi_pk, t1.level FROM position t1, position_npc t2 WHERE t1.posi_pk = t2.posi_pk');
    while ($PgGame->fetch())
    {
        $r =& $PgGame->row;

        $m_npc_hero =& $_M[$r['level']];

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

        $sql = 'UPDATE position_npc SET captain_hero_pk = $1, director_hero_pk = $2, staff_hero_pk = $3 WHERE posi_pk = $4';
        $PgGame2->query($sql, [$captain_hero_pk, $director_hero_pk, $staff_hero_pk, $r['posi_pk']]);
    }

// 토벌부대 장수
    $sql = <<< EOF
UPDATE suppress_position SET hero_pk =
(
  SELECT hero_pk
  FROM m_npc_hero
  WHERE type = 'suppress'
   AND level = (SELECT level FROM lord WHERE lord_pk = (SELECT lord_pk FROM suppress WHERE supp_pk = suppress_position.supp_pk))
  ORDER BY random() LIMIT 1
)

EOF;
    $PgGame->query($sql);

// 습격부대 장수
    $sql = <<< EOF
UPDATE troop SET captain_hero_pk =
(
  SELECT hero_pk
  FROM m_npc_hero
  WHERE type = 'attack'
   AND level = (SELECT level FROM lord WHERE lord_pk = troop.dst_lord_pk)
  ORDER BY random() LIMIT 1
)
WHERE troop_type = 'N'

EOF;
    $PgGame->query($sql);

    /*
     * RECREATE CONSTRAINT
     */
    {
        $PgGame->query('ALTER TABLE m_npc_hero ADD CONSTRAINT m_npc_hero_hero_pk_fkey FOREIGN KEY (hero_pk) REFERENCES hero(hero_pk)');

        $PgGame->query('ALTER TABLE position_npc ADD CONSTRAINT position_npc_captain_hero_pk_fkey FOREIGN KEY (captain_hero_pk) REFERENCES hero(hero_pk)');
        $PgGame->query('ALTER TABLE position_npc ADD CONSTRAINT position_npc_director_hero_pk_fkey FOREIGN KEY (director_hero_pk) REFERENCES hero(hero_pk)');
        $PgGame->query('ALTER TABLE position_npc ADD CONSTRAINT position_npc_staff_hero_pk_fkey FOREIGN KEY (staff_hero_pk) REFERENCES hero(hero_pk)');

        $PgGame->query('ALTER TABLE suppress_position ADD CONSTRAINT suppress_position_hero_pk_fkey FOREIGN KEY (hero_pk) REFERENCES hero(hero_pk)');

        $PgGame->query('ALTER TABLE troop ADD CONSTRAINT troop_captain_hero_pk_fkey FOREIGN KEY (captain_hero_pk) REFERENCES hero(hero_pk)');
    }

    echo 'do make m_npc_hero masterdata cache file'. "\n";

    $PgGame->commit();
    $PgGame2->commit();
} catch (Throwable $e) {
    $PgGame->rollback();
    $PgGame2->rollback();
    print_r($e);
}