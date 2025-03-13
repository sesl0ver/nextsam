<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.need_reso={};';

// m_cmd
$PgGame->query('SELECT * FROM m_need_resource ORDER BY need_type, rare_type, level');

$already = [];

while ($PgGame->fetch()) {
	$r = &$PgGame->row;

    if (! array_key_exists($r['need_type'], $already)) {
		echo "ns_cs.m.need_reso['{$r['need_type']}']={};";
		$already[$r['need_type']] = [];
	}

    if (! array_key_exists($r['rare_type'], $already[$r['need_type']])) {
		echo "ns_cs.m.need_reso['{$r['need_type']}']['{$r['rare_type']}']={};";
		$already[$r['need_type']][$r['rare_type']] = [];
	}

    if (! array_key_exists($r['level'], $already[$r['need_type']][$r['rare_type']])) {
		echo "ns_cs.m.need_reso['{$r['need_type']}']['{$r['rare_type']}']['{$r['level']}']={};";
		$already[$r['need_type']][$r['rare_type']][$r['level']] = [];
	}

	echo "ns_cs.m.need_reso['{$r['need_type']}']['{$r['rare_type']}']['{$r['level']}'] = {'need_item':'{$r['need_item']}', 'need_gold':'{$r['need_gold']}'};";
}

echo '}';
