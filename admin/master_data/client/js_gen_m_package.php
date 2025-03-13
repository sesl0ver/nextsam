<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.package={};';

// m_pickup
$PgGame->query('SELECT m_pack_pk, title, description, price, target_type, target_pk, target_value, reward_item, buy_limit, time_limit FROM m_package ORDER BY m_pack_pk');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_pack_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.package['{$k}'] = {$json};";
}

echo '}';
