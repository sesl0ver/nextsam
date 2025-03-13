<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.alli_memb = {};';

// m_hero_skill_exp
$PgGame->query('SELECT level, member FROM m_alliance_member ORDER BY level');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['level'];
	$json = json_encode($r);
	echo "ns_cs.m.alli_memb['{$k}'] = {$json};";
}
echo '}';
