<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_FORT'] = [];
\$_M_FORT = &\$GLOBALS['_M_FORT'];
\$_M['FORT'] = &\$_M_FORT;

\$GLOBALS['_M_FORT_C'] = [];
\$_M_FORT_C = &\$GLOBALS['_M_FORT_C'];
\$_M['FORT_C'] = &\$_M_FORT_C;


EOF;

$note = 'm_fort';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_fortification
$PgGame->query('SELECT * FROM m_fortification');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_fort_pk'];

	echo <<< EOF
\$_M_FORT['{$k}']= ['m_fort_pk' => {$r['m_fort_pk']}, 'm_cond_pk' => {$r['m_cond_pk']}, 'type' => '{$r['type']}', 'update_type' => '{$r['update_type']}', 'code' => '{$r['code']}', 'title' => '{$r['title']}', 'spec_energy' => {$r['spec_energy']}, 'spec_attack' => {$r['spec_attack']}, 'spec_defence' => {$r['spec_defence']}, 'spec_target_range' => {$r['spec_target_range']}, 'spec_attack_range' => {$r['spec_attack_range']}, 'spec_attack_efficiency' => '{$r['spec_attack_efficiency']}', 'need_vacancy' => {$r['need_vacancy']}, 'weak_type' => '{$r['weak_type']}', 'weaker_type' => '{$r['weaker_type']}', 'attack_effect_type' => '{$r['attack_effect_type']}', 'defence_effect_type' => '{$r['defence_effect_type']}', 'weak_type_title' => '{$r['weak_type_title']}', 'weaker_type_title' => '{$r['weaker_type_title']}']; 
\$_M_FORT_C['{$r['code']}'] = &\$_M_FORT['{$k}'];

EOF;
}



?>
