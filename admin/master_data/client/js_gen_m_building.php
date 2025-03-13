<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.buil = {};';

// m_building
$PgGame->query('SELECT m_buil_pk, title, type, alias, max_level, yn_demolish, yn_duplication, yn_hero_assign, yn_hero_assign_required, sort_hero_stat_type, description, description_detail, description_assign, description_footnote, orderno FROM m_building ORDER BY orderno DESC');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_buil_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.buil['{$k}'] = {$json};";
	echo "ns_cs.m.buil['{$k}'].level = {};";
	echo "ns_cs.m.buil['{$r['alias']}'] = ns_cs.m.buil['{$k}'];";
}

// m_building_level
$PgGame->query('SELECT m_buil_pk, level, m_cond_pk, description, variation_1, variation_2, variation_description FROM m_building_level');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$json = json_encode($r);
	echo "ns_cs.m.buil['{$r['m_buil_pk']}'].level['{$r['level']}'] = {$json};";
}

echo '}';
