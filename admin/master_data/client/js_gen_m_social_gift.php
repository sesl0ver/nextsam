<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

// m_social_gift

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.social_gift={};';

$PgGame->query('SELECT level, m_item_pk FROM m_social_gift ORDER BY level ASC, order_num ASC');
$PgGame->fetchAll();

$gift = [];
foreach($PgGame->rows as $v) {
	if (! array_key_exists($v['level'], $gift)) {
		$gift[$v['level']] = [];
	}
	$gift[$v['level']][] = $v['m_item_pk'];
}

$json = json_encode($gift);
echo "ns_cs.m.social_gift={$json};";

echo '}';
