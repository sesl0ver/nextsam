<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.hero_skil={};';

// m_hero_skill
$PgGame->query('SELECT m_hero_skil_pk, type, rare, title, condition, use_slot_count, exericised_rate, leadership, mil_force, intellect, politics, charm, description, description_detail, set_count, left_count, yn_trade, description_quickuse, effect_type, effect_value, value_type, calc_type, battle_type, yn_lord_skill, skill_type FROM m_hero_skill ORDER BY m_hero_skil_pk');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_hero_skil_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.hero_skil['{$k}'] = {$json};";
}

echo '}';
