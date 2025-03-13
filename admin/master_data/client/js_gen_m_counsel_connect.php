<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.coun_conn={};';

// m_counsel_connect
$PgGame->query('SELECT * FROM m_counsel_connect ORDER BY m_coun_conn_pk');

$already = [];
while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_coun_conn_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.coun_conn['{$k}'] = {$json};";
}

echo '}';
