<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/constant.php';
require_once __DIR__ . '/../../vendor/autoload.php';
$i18n = i18n::getInstance();
require_once __DIR__ . '/../../config/master_data.php';

$PgGame = new Pg('DEFAULT');
$Redis = new RedisCache();

try {
    // lord minus power revise
    $PgGame->query('UPDATE lord SET power = 0 WHERE power < 0');
    $PgGame->query('TRUNCATE TABLE ranking_lord');

    /*
    $PgGame->query('INSERT INTO ranking_lord(lord_pk, lord_name, alli_pk, lord_level, position_cnt, rank_power, power, rank_attack_point, attack_point, rank_defence_point, defence_point, rank_army_point, army_point)
    SELECT
        lord.lord_pk, lord.lord_name, lord.alli_pk, lord.level, lord.position_cnt,
        ROW_NUMBER() OVER(ORDER BY lord.power DESC) rank_power, lord.power,
        ROW_NUMBER() OVER(ORDER BY lord_point.attack_point DESC) rank_attack_point, lord_point.attack_point,
        ROW_NUMBER() OVER(ORDER BY lord_point.defence_point DESC) rank_defence_point, lord_point.defence_point,
        ROW_NUMBER() OVER(ORDER BY lord_point.army_point DESC) rank_army_point, lord_point.army_point
    FROM lord_point, lord
    WHERE lord.lord_pk = lord_point.lord_pk');
     */

    $PgGame->query('SELECT lord.lord_pk, lord.lord_name, lord.alli_pk, lord.level, lord.position_cnt,
	ROW_NUMBER() OVER(ORDER BY lord.power DESC) rank_power, lord.power,
	ROW_NUMBER() OVER(ORDER BY lord_point.attack_point DESC) rank_attack_point, lord_point.attack_point,
	ROW_NUMBER() OVER(ORDER BY lord_point.defence_point DESC) rank_defence_point, lord_point.defence_point,
	ROW_NUMBER() OVER(ORDER BY lord_point.army_point DESC) rank_army_point, lord_point.army_point
FROM lord_point, lord WHERE lord.lord_pk = lord_point.lord_pk');

    $PgGame->fetchAll();

    $alliance_titles = [];
    $Session = new Session(false);
    $Alliance = new Alliance($Session, $PgGame);

    function findAllianceTitle ($_alli_pk): string
    {
        global $alliance_titles, $Alliance;
        if ($_alli_pk < 1) {
            return '-';
        }
        if (isset($alliance_titles[$_alli_pk])) {
            return $alliance_titles[$_alli_pk];
        }
        $alliance_titles[$_alli_pk] = $Alliance->getAllianceTitle($_alli_pk);
        return $alliance_titles[$_alli_pk];
    }


    // 군주 랭킹
    $Redis->del('ranking:lord:power');
    $insert_query = '';
    foreach ($PgGame->rows as $row) {
        if ($insert_query !== '') {
            $insert_query .= ', ';
        }
        $cache_row = $row;
        $cache_row['alliance_title'] = findAllianceTitle($cache_row['alli_pk']);
        $Redis->zAdd('ranking:lord:power', $cache_row['rank_power'], $cache_row);
        $insert_query .= '(' . implode(',', array_map(function ($k) { return (is_null($k)) ? 'null' : ((! is_numeric($k)) ? '\'' . $k . '\'' : $k); }, $row)) . ')';
    }

    $PgGame->query("INSERT INTO ranking_lord(lord_pk, lord_name, alli_pk, lord_level, position_cnt, rank_power, power, rank_attack_point, attack_point, rank_defence_point, defence_point, rank_army_point, army_point) VALUES $insert_query");

    // lord_point main_rank update - 군주의 메인랭킹을 위해 남겨둠.
    $PgGame->query('UPDATE lord_point SET main_rank = rank_power FROM ranking_lord WHERE ranking_lord.lord_pk = lord_point.lord_pk');

    $Redis->del('ranking:lord:attack_point');

    foreach ($PgGame->rows as $row) {
        $row['alliance_title'] = findAllianceTitle($row['alli_pk']);
        $Redis->zAdd('ranking:lord:attack_point', $row['rank_attack_point'], $row);
    }

    $Redis->del('ranking:lord:defence_point');
    foreach ($PgGame->rows as $row) {
        $row['alliance_title'] = findAllianceTitle($row['alli_pk']);
        $Redis->zAdd('ranking:lord:defence_point', $row['rank_defence_point'], $row);
    }

    $Redis->del('ranking:lord:army_point');
    foreach ($PgGame->rows as $row) {
        $row['alliance_title'] = findAllianceTitle($row['alli_pk']);
        $Redis->zAdd('ranking:lord:army_point', $row['rank_army_point'], $row);
    }

    // 동맹 랭킹
    $PgGame->query('UPDATE alliance SET (power, attack_point, defence_point) = (t1.power, t1.attack_point, t1.defence_point) FROM
(SELECT alliance.alli_pk, SUM(lord.power) AS power, SUM(lord_point.attack_point) AS attack_point, SUM(lord_point.defence_point) AS defence_point
	FROM alliance, lord, lord_point WHERE alliance.alli_pk = lord.alli_pk AND lord.lord_pk = lord_point.lord_pk GROUP BY alliance.alli_pk) AS t1
WHERE alliance.alli_pk = t1.alli_pk');

    $PgGame->query('TRUNCATE TABLE ranking_alliance RESTART IDENTITY');

    /*
    $PgGame->query('INSERT INTO ranking_alliance (alli_pk, title, power_rank, power, attack_point_rank, attack_point, defence_point_rank, defence_point)
    SELECT
        alliance.alli_pk, alliance.title,
        ROW_NUMBER() OVER(ORDER BY alliance.power DESC) power_rank, alliance.power,
        ROW_NUMBER() OVER(ORDER BY alliance.attack_point DESC) attack_point_rank, alliance.attack_point,
        ROW_NUMBER() OVER(ORDER BY alliance.defence_point DESC) defence_point_rank, alliance.defence_point
    FROM alliance');
     */

    $PgGame->query('SELECT alliance.alli_pk, alliance.title, (SELECT lord_name FROM lord WHERE lord_pk = alliance.master_lord_pk) as lord_name,
	ROW_NUMBER() OVER(ORDER BY alliance.power DESC) power_rank, alliance.power,
	ROW_NUMBER() OVER(ORDER BY alliance.attack_point DESC) attack_point_rank, alliance.attack_point,
	ROW_NUMBER() OVER(ORDER BY alliance.defence_point DESC) defence_point_rank, alliance.defence_point
FROM alliance');
    $PgGame->fetchAll();

    $Redis->del('ranking:alliance:power');
    foreach ($PgGame->rows as $row) {
        $Redis->zAdd('ranking:alliance:power', $row['power_rank'], $row);
    }

    $Redis->del('ranking:alliance:attack_point');
    foreach ($PgGame->rows as $row) {
        $Redis->zAdd('ranking:alliance:attack_point', $row['attack_point_rank'], $row);
    }

    $Redis->del('ranking:alliance:defence_point');
    foreach ($PgGame->rows as $row) {
        $Redis->zAdd('ranking:alliance:defence_point', $row['defence_point_rank'], $row);
    }

    // hero_ranking
    $PgGame->query('TRUNCATE TABLE ranking_hero');

    $PgGame->query('SELECT \'L\' AS rank_type, t2.hero_pk, t3.m_hero_pk, t2.lord_pk, t1.lord_name, t2.rank, t3.level
, t3.leadership_basic + t3.leadership_enchant + t3.leadership_plusstat + t3.leadership_skill as leadership
, t3.mil_force_basic + t3.mil_force_enchant + t3.mil_force_plusstat + t3.mil_force_skill as mil_force
, t3.intellect_basic + t3.intellect_enchant + t3.intellect_plusstat + t3.intellect_skill as intellect
, t3.politics_basic + t3.politics_enchant + t3.politics_plusstat + t3.politics_skill as politics
, t3.charm_basic + t3.charm_enchant + t3.charm_plusstat + t3.charm_skill as charm
, t3.leadership_basic, t3.mil_force_basic, t3.intellect_basic, t3.politics_basic, t3.charm_basic
, t3.leadership_enchant, t3.mil_force_enchant, t3.intellect_enchant, t3.politics_enchant, t3.charm_enchant
, t3.leadership_plusstat, t3.mil_force_plusstat, t3.intellect_plusstat, t3.politics_plusstat, t3.charm_plusstat
, t3.leadership_skill, t3.mil_force_skill, t3.intellect_skill, t3.politics_skill, t3.charm_skill
FROM lord t1, (SELECT my_hero.lord_pk, my_hero.hero_pk,
ROW_NUMBER() OVER(ORDER BY my_hero.leadership DESC) AS rank,
my_hero.leadership, my_hero.mil_force, my_hero.intellect, my_hero.politics, my_hero.charm
FROM my_hero, hero, m_hero WHERE my_hero.hero_pk = hero.hero_pk AND hero.m_hero_pk = m_hero.m_hero_pk AND my_hero.status = \'A\' AND m_hero.over_type = \'N\'
ORDER BY my_hero.leadership DESC LIMIT 1000) t2, hero t3 WHERE t1.lord_pk = t2.lord_pk AND t2.hero_pk = t3.hero_pk');
    $PgGame->fetchAll();

    $Redis->del('ranking:hero:leadership');
    foreach ($PgGame->rows as $row) {
        $Redis->zAdd('ranking:hero:leadership', $row['rank'], $row);
    }

    $PgGame->query('SELECT \'M\' AS rank_type, t2.hero_pk, t3.m_hero_pk, t2.lord_pk, t1.lord_name, t2.rank, t3.level
, t3.leadership_basic + t3.leadership_enchant + t3.leadership_plusstat + t3.leadership_skill as leadership
, t3.mil_force_basic + t3.mil_force_enchant + t3.mil_force_plusstat + t3.mil_force_skill as mil_force
, t3.intellect_basic + t3.intellect_enchant + t3.intellect_plusstat + t3.intellect_skill as intellect
, t3.politics_basic + t3.politics_enchant + t3.politics_plusstat + t3.politics_skill as politics
, t3.charm_basic + t3.charm_enchant + t3.charm_plusstat + t3.charm_skill as charm
, t3.leadership_basic, t3.mil_force_basic, t3.intellect_basic, t3.politics_basic, t3.charm_basic
, t3.leadership_enchant, t3.mil_force_enchant, t3.intellect_enchant, t3.politics_enchant, t3.charm_enchant
, t3.leadership_plusstat, t3.mil_force_plusstat, t3.intellect_plusstat, t3.politics_plusstat, t3.charm_plusstat
, t3.leadership_skill, t3.mil_force_skill, t3.intellect_skill, t3.politics_skill, t3.charm_skill
FROM lord t1, (SELECT my_hero.lord_pk, my_hero.hero_pk,
ROW_NUMBER() OVER(ORDER BY my_hero.mil_force DESC) AS rank,
my_hero.leadership, my_hero.mil_force, my_hero.intellect, my_hero.politics, my_hero.charm
FROM my_hero, hero, m_hero WHERE my_hero.hero_pk = hero.hero_pk AND hero.m_hero_pk = m_hero.m_hero_pk AND my_hero.status = \'A\' AND m_hero.over_type = \'N\'
ORDER BY my_hero.mil_force DESC LIMIT 1000) t2, hero t3 WHERE t1.lord_pk = t2.lord_pk AND t2.hero_pk = t3.hero_pk');
    $PgGame->fetchAll();

    $Redis->del('ranking:hero:mil_force');
    foreach ($PgGame->rows as $row) {
        $Redis->zAdd('ranking:hero:mil_force', $row['rank'], $row);
    }

    $PgGame->query('SELECT \'I\' AS rank_type, t2.hero_pk, t3.m_hero_pk, t2.lord_pk, t1.lord_name, t2.rank, t3.level
, t3.leadership_basic + t3.leadership_enchant + t3.leadership_plusstat + t3.leadership_skill as leadership
, t3.mil_force_basic + t3.mil_force_enchant + t3.mil_force_plusstat + t3.mil_force_skill as mil_force
, t3.intellect_basic + t3.intellect_enchant + t3.intellect_plusstat + t3.intellect_skill as intellect
, t3.politics_basic + t3.politics_enchant + t3.politics_plusstat + t3.politics_skill as politics
, t3.charm_basic + t3.charm_enchant + t3.charm_plusstat + t3.charm_skill as charm
, t3.leadership_basic, t3.mil_force_basic, t3.intellect_basic, t3.politics_basic, t3.charm_basic
, t3.leadership_enchant, t3.mil_force_enchant, t3.intellect_enchant, t3.politics_enchant, t3.charm_enchant
, t3.leadership_plusstat, t3.mil_force_plusstat, t3.intellect_plusstat, t3.politics_plusstat, t3.charm_plusstat
, t3.leadership_skill, t3.mil_force_skill, t3.intellect_skill, t3.politics_skill, t3.charm_skill
FROM lord t1, (SELECT my_hero.lord_pk, my_hero.hero_pk,
ROW_NUMBER() OVER(ORDER BY my_hero.intellect DESC) AS rank,
my_hero.leadership, my_hero.mil_force, my_hero.intellect, my_hero.politics, my_hero.charm
FROM my_hero, hero, m_hero WHERE my_hero.hero_pk = hero.hero_pk AND hero.m_hero_pk = m_hero.m_hero_pk AND my_hero.status = \'A\' AND m_hero.over_type = \'N\'
ORDER BY my_hero.intellect DESC LIMIT 1000) t2, hero t3 WHERE t1.lord_pk = t2.lord_pk AND t2.hero_pk = t3.hero_pk');
    $PgGame->fetchAll();

    $Redis->del('ranking:hero:intellect');
    foreach ($PgGame->rows as $row) {
        $Redis->zAdd('ranking:hero:intellect', $row['rank'], $row);
    }

    $PgGame->query('SELECT \'P\' AS rank_type, t2.hero_pk, t3.m_hero_pk, t2.lord_pk, t1.lord_name, t2.rank, t3.level
, t3.leadership_basic + t3.leadership_enchant + t3.leadership_plusstat + t3.leadership_skill as leadership
, t3.mil_force_basic + t3.mil_force_enchant + t3.mil_force_plusstat + t3.mil_force_skill as mil_force
, t3.intellect_basic + t3.intellect_enchant + t3.intellect_plusstat + t3.intellect_skill as intellect
, t3.politics_basic + t3.politics_enchant + t3.politics_plusstat + t3.politics_skill as politics
, t3.charm_basic + t3.charm_enchant + t3.charm_plusstat + t3.charm_skill as charm
, t3.leadership_basic, t3.mil_force_basic, t3.intellect_basic, t3.politics_basic, t3.charm_basic
, t3.leadership_enchant, t3.mil_force_enchant, t3.intellect_enchant, t3.politics_enchant, t3.charm_enchant
, t3.leadership_plusstat, t3.mil_force_plusstat, t3.intellect_plusstat, t3.politics_plusstat, t3.charm_plusstat
, t3.leadership_skill, t3.mil_force_skill, t3.intellect_skill, t3.politics_skill, t3.charm_skill
FROM lord t1, (SELECT my_hero.lord_pk, my_hero.hero_pk,
ROW_NUMBER() OVER(ORDER BY my_hero.politics DESC) AS rank,
my_hero.leadership, my_hero.mil_force, my_hero.intellect, my_hero.politics, my_hero.charm
FROM my_hero, hero, m_hero WHERE my_hero.hero_pk = hero.hero_pk AND hero.m_hero_pk = m_hero.m_hero_pk AND my_hero.status = \'A\' AND m_hero.over_type = \'N\'
ORDER BY my_hero.politics DESC LIMIT 1000) t2, hero t3 WHERE t1.lord_pk = t2.lord_pk AND t2.hero_pk = t3.hero_pk');
    $PgGame->fetchAll();

    $Redis->del('ranking:hero:politics');
    foreach ($PgGame->rows as $row) {
        $Redis->zAdd('ranking:hero:politics', $row['rank'], $row);
    }

    $PgGame->query('SELECT \'C\' AS rank_type, t2.hero_pk, t3.m_hero_pk, t2.lord_pk, t1.lord_name, t2.rank, t3.level
, t3.leadership_basic + t3.leadership_enchant + t3.leadership_plusstat + t3.leadership_skill as leadership
, t3.mil_force_basic + t3.mil_force_enchant + t3.mil_force_plusstat + t3.mil_force_skill as mil_force
, t3.intellect_basic + t3.intellect_enchant + t3.intellect_plusstat + t3.intellect_skill as intellect
, t3.politics_basic + t3.politics_enchant + t3.politics_plusstat + t3.politics_skill as politics
, t3.charm_basic + t3.charm_enchant + t3.charm_plusstat + t3.charm_skill as charm
, t3.leadership_basic, t3.mil_force_basic, t3.intellect_basic, t3.politics_basic, t3.charm_basic
, t3.leadership_enchant, t3.mil_force_enchant, t3.intellect_enchant, t3.politics_enchant, t3.charm_enchant
, t3.leadership_plusstat, t3.mil_force_plusstat, t3.intellect_plusstat, t3.politics_plusstat, t3.charm_plusstat
, t3.leadership_skill, t3.mil_force_skill, t3.intellect_skill, t3.politics_skill, t3.charm_skill
FROM lord t1, (SELECT my_hero.lord_pk, my_hero.hero_pk,
ROW_NUMBER() OVER(ORDER BY my_hero.charm DESC) AS rank,
my_hero.leadership, my_hero.mil_force, my_hero.intellect, my_hero.politics, my_hero.charm
FROM my_hero, hero, m_hero WHERE my_hero.hero_pk = hero.hero_pk AND hero.m_hero_pk = m_hero.m_hero_pk AND my_hero.status = \'A\' AND m_hero.over_type = \'N\'
ORDER BY my_hero.charm DESC LIMIT 1000) t2, hero t3 WHERE t1.lord_pk = t2.lord_pk AND t2.hero_pk = t3.hero_pk');
    $PgGame->fetchAll();

    $Redis->del('ranking:hero:charm');
    foreach ($PgGame->rows as $row) {
        $Redis->zAdd('ranking:hero:charm', $row['rank'], $row);
    }

    $PgGame->query('INSERT INTO job_history VALUES($1, now()) ON CONFLICT (job_name) DO UPDATE SET last_job_dt = now()', ['ranking']);
} catch (Throwable $e) {
    print_r($e);
}

require_once './batch_occupation_point.php';

// hero_ranking
/*$PgGame->query('INSERT INTO ranking_hero
SELECT \'L\' AS rank_type, t2.hero_pk, t3.m_hero_pk, t2.lord_pk, t1.lord_name, t2.rank, t2.leadership, t2.mil_force, t2.intellect, t2.politics, t2.charm
FROM lord t1,
(
	SELECT
		my_hero.lord_pk, my_hero.hero_pk,
		ROW_NUMBER() OVER(ORDER BY my_hero.leadership DESC) AS rank,
		my_hero.leadership, my_hero.mil_force, my_hero.intellect, my_hero.politics, my_hero.charm
	FROM
		my_hero, hero, m_hero
	WHERE
		my_hero.hero_pk = hero.hero_pk AND
		hero.m_hero_pk = m_hero.m_hero_pk AND
		my_hero.status = \'A\' AND
		m_hero.over_type = \'N\'
	ORDER BY my_hero.leadership DESC
	LIMIT 1000
) t2, hero t3
WHERE t1.lord_pk = t2.lord_pk AND t2.hero_pk = t3.hero_pk');


$PgGame->query('INSERT INTO ranking_hero
SELECT \'M\' AS rank_type, t2.hero_pk, t3.m_hero_pk, t2.lord_pk, t1.lord_name, t2.rank, t2.leadership, t2.mil_force, t2.intellect, t2.politics, t2.charm
FROM lord t1,
(
	SELECT
		my_hero.lord_pk, my_hero.hero_pk,
		ROW_NUMBER() OVER(ORDER BY my_hero.mil_force DESC) AS rank,
		my_hero.leadership, my_hero.mil_force, my_hero.intellect, my_hero.politics, my_hero.charm
	FROM
		my_hero, hero, m_hero
	WHERE
		my_hero.hero_pk = hero.hero_pk AND
		hero.m_hero_pk = m_hero.m_hero_pk AND
		my_hero.status = \'A\' AND
		m_hero.over_type = \'N\'
	ORDER BY my_hero.mil_force DESC
	LIMIT 1000
) t2, hero t3
WHERE t1.lord_pk = t2.lord_pk AND t2.hero_pk = t3.hero_pk');

$PgGame->query('INSERT INTO ranking_hero
SELECT \'I\' AS rank_type, t2.hero_pk, t3.m_hero_pk, t2.lord_pk, t1.lord_name, t2.rank, t2.leadership, t2.mil_force, t2.intellect, t2.politics, t2.charm
FROM lord t1,
(
	SELECT
		my_hero.lord_pk, my_hero.hero_pk,
		ROW_NUMBER() OVER(ORDER BY my_hero.intellect DESC) AS rank,
		my_hero.leadership, my_hero.mil_force, my_hero.intellect, my_hero.politics, my_hero.charm
	FROM
		my_hero, hero, m_hero
	WHERE
		my_hero.hero_pk = hero.hero_pk AND
		hero.m_hero_pk = m_hero.m_hero_pk AND
		my_hero.status = \'A\' AND
		m_hero.over_type = \'N\'
	ORDER BY my_hero.intellect DESC
	LIMIT 1000
) t2, hero t3
WHERE t1.lord_pk = t2.lord_pk AND t2.hero_pk = t3.hero_pk');

$PgGame->query('INSERT INTO ranking_hero
SELECT \'P\' AS rank_type, t2.hero_pk, t3.m_hero_pk, t2.lord_pk, t1.lord_name, t2.rank, t2.leadership, t2.mil_force, t2.intellect, t2.politics, t2.charm
FROM lord t1,
(
	SELECT
		my_hero.lord_pk, my_hero.hero_pk,
		ROW_NUMBER() OVER(ORDER BY my_hero.politics DESC) AS rank,
		my_hero.leadership, my_hero.mil_force, my_hero.intellect, my_hero.politics, my_hero.charm
	FROM
		my_hero, hero, m_hero
	WHERE
		my_hero.hero_pk = hero.hero_pk AND
		hero.m_hero_pk = m_hero.m_hero_pk AND
		my_hero.status = \'A\' AND
		m_hero.over_type = \'N\'
	ORDER BY my_hero.politics DESC
	LIMIT 1000
) t2, hero t3
WHERE t1.lord_pk = t2.lord_pk AND t2.hero_pk = t3.hero_pk');

$PgGame->query('INSERT INTO ranking_hero
SELECT \'C\' AS rank_type, t2.hero_pk, t3.m_hero_pk, t2.lord_pk, t1.lord_name, t2.rank, t2.leadership, t2.mil_force, t2.intellect, t2.politics, t2.charm
FROM lord t1,
(
	SELECT
		my_hero.lord_pk, my_hero.hero_pk,
		ROW_NUMBER() OVER(ORDER BY my_hero.charm DESC) AS rank,
		my_hero.leadership, my_hero.mil_force, my_hero.intellect, my_hero.politics, my_hero.charm
	FROM
		my_hero, hero, m_hero
	WHERE
		my_hero.hero_pk = hero.hero_pk AND
		hero.m_hero_pk = m_hero.m_hero_pk AND
		my_hero.status = \'A\' AND
		m_hero.over_type = \'N\'
	ORDER BY my_hero.charm DESC
	LIMIT 1000
) t2, hero t3
WHERE t1.lord_pk = t2.lord_pk AND t2.hero_pk = t3.hero_pk');*/
