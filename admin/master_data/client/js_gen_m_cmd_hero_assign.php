<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.cmd_hero_assi={};';

// m_cmd_hero_assign
$PgGame->query('SELECT m_cmd_pk, m_hero_assi_pk FROM m_cmd_hero_assign');

$already = [];

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_cmd_pk'];

	if (! array_key_exists($k, $already)) {
		echo "ns_cs.m.cmd_hero_assi['{$k}']={};";
		$already[$k] = true;
	}

	echo "ns_cs.m.cmd_hero_assi['{$k}']['{$r['m_hero_assi_pk']}']=true;";
}

echo '}';
