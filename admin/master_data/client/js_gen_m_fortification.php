<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.fort={};';

// m_fortification
$PgGame->query('SELECT m_fort_pk, m_cond_pk, code, title, spec_energy ,spec_attack ,spec_defence ,spec_target_range ,spec_attack_range ,spec_attack_efficiency ,need_vacancy ,description ,description_detail, category, attack_line, defence_line, weak_type, weaker_type, spec_attack_efficiency, weak_type_title, weaker_type_title FROM m_fortification');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_fort_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.fort['{$k}'] = {$json};";
	echo "ns_cs.m.fort['{$r['code']}'] = ns_cs.m.fort['{$k}'];";
}

echo '}';
