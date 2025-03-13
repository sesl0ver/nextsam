<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.army = {};';

// m_army
$PgGame->query('SELECT m_army_pk, m_cond_pk, type, code, title, spec_energy,spec_attack,spec_defence,spec_capacity ,spec_speed ,spec_target_range ,spec_attack_range ,spec_attack_efficiency ,need_population ,need_food ,description ,description_detail, category, attack_line, defence_line, weak_type, weaker_type, spec_attack_efficiency, short_title, orderno, category_code, weak_type_title, weaker_type_title, m_medi_cond_pk FROM m_army ORDER BY orderno DESC');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_army_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.army['{$k}'] = {$json};";
	echo "ns_cs.m.army['{$r['code']}'] = ns_cs.m.army['{$k}'];";
}

echo '}';
