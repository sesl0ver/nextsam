<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.cond={};';

// m_condition
$PgGame->query('SELECT m_cond_pk, build_time, build_gold, build_food, build_horse, build_lumber, build_iron, demolish_time, demolish_gold, demolish_food, demolish_horse, demolish_lumber, demolish_iron, need_population, need_vacancy, active_buil_level, yn_hero_assign_required, cmd_hero_stat_type, cmd_hero_stat_value, m_buil_pk, m_buil_level, m_tech_pk, m_tech_level, m_item_pk, m_item_cnt FROM m_condition');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_cond_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.cond['{$k}'] = {$json};";
}

echo '}';
