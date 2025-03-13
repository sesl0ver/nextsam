<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.occupation_reward={};';

/*
 * m_occupation_reward
 */
$PgGame->query('SELECT reward_type, rank, need_point, reward_item FROM m_occupation_reward');

$already = [];

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['reward_type'];

	if (! array_key_exists($k, $already)) {
		echo "ns_cs.m.occupation_reward['{$k}']={};";
		$already[$k] = true;
	}

	$json = json_encode($r);

	echo "ns_cs.m.occupation_reward['{$k}']['{$r['rank']}'] = {$json};";
}

echo '}';
