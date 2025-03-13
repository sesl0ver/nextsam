<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.hero_base={};';

// m_hero_base
$PgGame->query('SELECT m_hero_base_pk, name, rare_type, type, forces, mil_aptitude_infantry, mil_aptitude_spearman, mil_aptitude_pikeman, mil_aptitude_archer, mil_aptitude_horseman, mil_aptitude_siege, description, description_detail, over_type, point_level, over_hero_duration, yn_new_gacha, yn_modifier FROM m_hero_base');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_hero_base_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.hero_base['{$k}'] = {$json};";
}

echo '}';
