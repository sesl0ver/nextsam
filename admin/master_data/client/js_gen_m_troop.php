<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.troop={};';

// m_troop
$PgGame->query('SELECT type, cond_value, value FROM m_troop ORDER BY type, cond_value');

$already = [];

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['type'];

	if (! array_key_exists($k, $already)) {
		echo "ns_cs.m.troop['{$k}']={};";
		$already[$k] = true;
	}

	$json = json_encode($r);

	echo "ns_cs.m.troop['{$k}']['{$r['cond_value']}'] = {$json};";
}

echo '}';