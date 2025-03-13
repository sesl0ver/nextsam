<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

// m_social_request

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.social_request={};';

$PgGame->query('SELECT level, m_item_pk FROM m_social_request ORDER BY level ASC, order_num ASC');
$PgGame->fetchAll();

$request = [];
foreach($PgGame->rows as $v) {
	if (! array_key_exists($v['level'], $request)) {
		$request[$v['level']] = [];
	}
	$request[$v['level']][] = $v['m_item_pk'];
}

$json = json_encode($request);
echo "ns_cs.m.social_request = {$json};";

echo '}';
