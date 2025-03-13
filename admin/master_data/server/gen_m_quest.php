<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_QUES'] = [];
\$_M_QUES = &\$GLOBALS['_M_QUES'];
\$_M['QUES'] = &\$_M_QUES;


EOF;

$note = 'm_ques';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_quest
$PgGame->query('SELECT m_ques_pk, type, main_title, sub_title, description, description_goal, description_tip, description_reward, sub_precondition, lord_upgrade, power, item, population, food, horse, lumber, iron, gold, army, fortification, attack_npc, orderno, goal_type, condition_1, condition_2, condition_3, condition_4, condition_5, condition_count, yn_repeat FROM m_quest order by m_ques_pk');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_ques_pk'];

	echo <<< EOF
\$_M_QUES['{$k}'] = ['m_ques_pk' => {$r['m_ques_pk']}, 'type' => '{$r['type']}', 'main_title' => '{$r['main_title']}', 'sub_title' => '{$r['sub_title']}', 'description' => '{$r['description']}', 'description_goal' => '{$r['description_goal']}', 'description_tip' => '{$r['description_tip']}', 'description_reward' => '{$r['description_reward']}', 'sub_precondition' => '{$r['sub_precondition']}', 'lord_upgrade' => '{$r['lord_upgrade']}', 'power' => '{$r['power']}', 'item' => '{$r['item']}', 'population' => '{$r['population']}', 'food' => '{$r['food']}', 'horse' => '{$r['horse']}', 'lumber' => '{$r['lumber']}', 'iron' => '{$r['iron']}', 'gold' => '{$r['gold']}', 'army' => '{$r['army']}', 'fortification' => '{$r['fortification']}', 'attack_npc' => '{$r['attack_npc']}', 'orderno' => '{$r['orderno']}', 'goal_type' => '{$r['goal_type']}', 'condition_1' => '{$r['condition_1']}', 'condition_2' => '{$r['condition_2']}', 'condition_3' => '{$r['condition_3']}', 'condition_4' => '{$r['condition_4']}', 'condition_5' => '{$r['condition_5']}', 'condition_count' => {$r['condition_count']}, 'yn_repeat' => '{$r['yn_repeat']}'];

EOF;
}