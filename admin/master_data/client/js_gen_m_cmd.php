<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.cmd = {};';

// m_cmd
$PgGame->query('SELECT m_cmd_pk, type, title, description, code, sort_hero_stat_type FROM m_cmd ORDER BY m_cmd_pk');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_cmd_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.cmd['{$k}'] = {$json};";
	echo "ns_cs.m.cmd['{$r['code']}'] = ns_cs.m.cmd['{$k}'];";
}

echo '}';
