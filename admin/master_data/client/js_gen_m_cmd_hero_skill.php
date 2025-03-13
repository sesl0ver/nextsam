<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.cmd_hero_skil={};';

// m_cmd_hero_skill
$PgGame->query('SELECT m_cmd_pk, m_hero_skil_pk FROM m_cmd_hero_skill');

$already = [];

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_cmd_pk'];

	if (! array_key_exists($k, $already)) {
		echo "ns_cs.m.cmd_hero_skil['{$k}']={};";
		$already[$k] = true;
	}

	echo "ns_cs.m.cmd_hero_skil['{$k}']['{$r['m_hero_skil_pk']}']=true;";
}

echo '}';
