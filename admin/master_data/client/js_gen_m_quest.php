<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.ques={};';

// m_ques
$PgGame->query('SELECT m_ques_pk, type, main_title, sub_title, description, description_goal, description_tip, description_reward, sub_precondition, -(orderno) as orderno, goal_type, condition_1, condition_2, condition_3, condition_4, condition_5, condition_count, lord_upgrade, power, item, population, food, horse, lumber, iron, gold, army, fortification, sortno, main_dlg, btn_id, btn_name, next_ques_pk FROM m_quest ORDER BY m_ques_pk ASC');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_ques_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.ques['{$k}'] = {$json};";
}

echo '}';