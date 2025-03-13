<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.hero_coll_comb={};';

// m_hero_collection_combi
$PgGame->query('SELECT * FROM m_hero_collection_combi ORDER BY m_hero_comb_coll_pk ASC');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_hero_comb_coll_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.hero_coll_comb['{$k}'] = {$json};";
	echo "ns_cs.m.hero_coll_comb['{$r['m_hero_base_pk']}'] =ns_cs.m.hero_coll_comb['{$k}'];";
}

echo '}';
