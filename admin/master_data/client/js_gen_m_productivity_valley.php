<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.prod_vall={};';

/*
 * m_productivity_valley
 */
$PgGame->query('SELECT valley_type, level, food, horse, lumber, iron, description, occupation_point FROM m_productivity_valley');

$already = [];

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['valley_type'];

	if (! array_key_exists($k, $already)) {
		echo "ns_cs.m.prod_vall['{$k}']={};";
		$already[$k] = true;
	}

	$json = json_encode($r);

	echo "ns_cs.m.prod_vall['{$k}']['{$r['level']}'] = {$json};";
}

echo '}';
