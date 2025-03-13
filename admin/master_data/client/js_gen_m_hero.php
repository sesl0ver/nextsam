<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.hero = new Object();';

// m_hero
$PgGame->query('SELECT * FROM m_hero');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_hero_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.hero['{$k}'] = {$json};";
}

echo '}';
