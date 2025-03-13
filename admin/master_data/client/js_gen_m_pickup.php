<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.pick={};';

// m_pickup
$PgGame->query('SELECT pickup_type, title, m_item_pk, item_count, need_qbig, pity_limit, pity_level, pity_rare, pity_hero, start_date, end_date FROM m_pickup WHERE visible = \'Y\' ORDER BY pickup_type');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['pickup_type'];

	$json = json_encode($r);

	echo "ns_cs.m.pick['{$k}'] = {$json};";
}

echo '}';
