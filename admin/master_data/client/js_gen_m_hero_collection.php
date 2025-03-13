<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.hero_collection={};';

// hero_collection
$PgGame->query('SELECT m_hero_coll_pk, title, type, type_value, collection_count, reward_item FROM m_hero_collection');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_hero_coll_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.hero_collection['{$k}'] = {$json};";
}

echo '}';
