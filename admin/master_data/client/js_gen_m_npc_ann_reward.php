<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.npc_ann_rewa={};';

// m_npc_ann_reward
$PgGame->query('SELECT * FROM m_npc_ann_reward');

$already = [];

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

    if (! array_key_exists($r['level'], $already)) {
		echo "ns_cs.m.npc_ann_rewa['{$r['level']}']={};";
		$already[$r['level']] = [];
	}

    if (! array_key_exists($r['type'], $already[$r['level']])) {
		echo "ns_cs.m.npc_ann_rewa['{$r['level']}']['{$r['type']}']={};";
		$already[$r['level']][$r['type']] = [];
	}

	echo "ns_cs.m.npc_ann_rewa['{$r['level']}']['{$r['type']}'] = {'reward_item_pk':'{$r['reward_item_pk']}', 'cnt':'{$r['reward_item_cnt']}'};";
}

echo '}';
