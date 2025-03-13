<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.tech_effe={};';

// m_technique_effect
$PgGame->query('SELECT m_tech_pk, level, effect_value, value_type, calc_type FROM m_technique_effect');

$already = [];
while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_tech_pk'];
	$json = json_encode($r);

	if (! array_key_exists($k, $already)) {
		echo "ns_cs.m.tech_effe['{$r['m_tech_pk']}'] = {$json};";
		echo "ns_cs.m.tech_effe['{$r['m_tech_pk']}'].level = {};";
		$already[$k] = $json;
	}

	echo "ns_cs.m.tech_effe['{$r['m_tech_pk']}'].level['{$r['level']}'] = {$json};";
}

echo '}';