<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.hero_assi={};';

// m_hero_assign
$PgGame->query('SELECT m_hero_assi_pk, type, title, description_effect, stat_type, stat_step FROM m_hero_assign ORDER BY m_hero_assi_pk');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_hero_assi_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.hero_assi['{$k}'] = {$json};";
}

echo '}';
