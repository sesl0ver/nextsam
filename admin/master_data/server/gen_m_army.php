<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_ARMY'] = [];
\$_M_ARMY = &\$GLOBALS['_M_ARMY'];
\$_M['ARMY'] = &\$_M_ARMY;

\$GLOBALS['_M_ARMY_C'] = [];
\$_M_ARMY_C = &\$GLOBALS['_M_ARMY_C'];
\$_M['ARMY_C'] = &\$_M_ARMY_C;


EOF;

$note = 'm_army';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_army
$PgGame->query('SELECT * FROM m_army ORDER BY orderno DESC');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_army_pk'];

	echo <<< EOF
\$_M_ARMY['{$k}'] = ['m_army_pk' => {$r['m_army_pk']}, 'm_cond_pk' => {$r['m_cond_pk']}, 'type' => '{$r['type']}', 'update_type' => '{$r['update_type']}', 'code' => '{$r['code']}', 'title' => '{$r['title']}', 'spec_energy' => {$r['spec_energy']}, 'spec_attack' => {$r['spec_attack']}, 'spec_defence' => {$r['spec_defence']}, 'spec_capacity' => {$r['spec_capacity']}, 'spec_speed' => {$r['spec_speed']}, 'spec_target_range' => {$r['spec_target_range']}, 'spec_attack_range' => {$r['spec_attack_range']}, 'spec_attack_efficiency' => '{$r['spec_attack_efficiency']}', 'need_population' => {$r['need_population']}, 'need_food' => {$r['need_food']}, 'weak_type' => '{$r['weak_type']}', 'weaker_type' => '{$r['weaker_type']}', 'treatment_costs' => {$r['treatment_costs']}, 'orderno' => {$r['orderno']}, 'attack_effect_type' => '{$r['attack_effect_type']}', 'defence_effect_type' => '{$r['defence_effect_type']}', 'category_code' => '{$r['category_code']}', 'weak_type_title' => '{$r['weak_type_title']}', 'weaker_type_title' => '{$r['weaker_type_title']}', 'm_medi_cond_pk' => '{$r['m_medi_cond_pk']}', 'priority' => {$r['priority']}];
\$_M_ARMY_C['{$r['code']}'] = &\$_M_ARMY['{$k}'];

EOF;
}


