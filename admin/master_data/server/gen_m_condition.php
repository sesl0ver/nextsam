<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_COND'] = [];
\$_M_COND = &\$GLOBALS['_M_COND'];
\$_M['COND'] = &\$_M_COND;


EOF;

$note = 'm_cond';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_condition
$PgGame->query('SELECT * FROM m_condition');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_cond_pk'];

	echo <<< EOF
\$_M_COND['{$k}']= ['build_time' => '{$r['build_time']}', 'build_gold' => '{$r['build_gold']}', 'build_food' => '{$r['build_food']}', 'build_horse' => '{$r['build_horse']}', 'build_lumber' => '{$r['build_lumber']}', 'build_iron' => '{$r['build_iron']}', 'demolish_time' => '{$r['demolish_time']}', 'demolish_gold' => '{$r['demolish_gold']}', 'demolish_food' => '{$r['demolish_food']}', 'demolish_horse' => '{$r['demolish_horse']}', 'demolish_lumber' => '{$r['demolish_lumber']}', 'demolish_iron' => '{$r['demolish_iron']}', 'need_population' => '{$r['need_population']}', 'need_vacancy' => '{$r['need_vacancy']}', 'active_buil_level' => '{$r['active_buil_level']}', 'yn_hero_assign_required' => '{$r['yn_hero_assign_required']}', 'cmd_hero_stat_type' => '{$r['cmd_hero_stat_type']}', 'cmd_hero_stat_value' => '{$r['cmd_hero_stat_value']}', 'm_buil_pk' => '{$r['m_buil_pk']}', 'm_buil_level' => '{$r['m_buil_level']}', 'm_tech_pk' => '{$r['m_tech_pk']}', 'm_tech_level' => '{$r['m_tech_level']}', 'm_item_pk' => '{$r['m_item_pk']}', 'm_item_cnt' => '{$r['m_item_cnt']}'];

EOF;
}

