<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.hero_exp={};';

// m_hero_skill_exp
$PgGame->query('SELECT rare_type, level, need_exp, acquire_exp FROM m_hero_exp ORDER BY rare_type, level');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['rare_type'];
    $k2 = &$r['level'];

	$json = json_encode($r);
    if ($k2 == 1) {
        echo "ns_cs.m.hero_exp['$k'] = {};";
    }
    echo "ns_cs.m.hero_exp['$k']['$k2'] = { level: {$r['level']}, need_exp: {$r['need_exp']}, acquire_exp: {$r['acquire_exp']} };";
}

echo '}';
