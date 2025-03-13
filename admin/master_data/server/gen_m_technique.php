<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_TECH'] = [];
\$_M_TECH = &\$GLOBALS['_M_TECH'];
\$_M['TECH'] = &\$_M_TECH;

\$GLOBALS['_M_TECH_C'] = [];
\$_M_TECH_C = &\$GLOBALS['_M_TECH_C'];
\$_M['TECH_C'] = &\$_M_TECH_C;


EOF;

$note = 'm_tech';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_technique
$PgGame->query('SELECT m_tech_pk, title, type, update_type, code, max_level, yn_demolish, orderno FROM m_technique ORDER BY orderno DESC');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_tech_pk'];

	echo <<< EOF
\$_M_TECH['{$k}'] = ['m_tech_pk' => {$r['m_tech_pk']}, 'title' => '{$r['title']}', 'type' => '{$r['type']}', 'update_type' => '{$r['update_type']}', 'code' => '{$r['code']}', 'max_level' => {$r['max_level']}, 'yn_demolish' => '{$r['yn_demolish']}', 'orderno' => '{$r['orderno']}'];
\$_M_TECH_C['{$r['code']}'] = &\$_M_TECH['{$k}'];

EOF;
}

// m_technique_level
$PgGame->query('SELECT m_tech_pk, level, m_cond_pk, power, increase_power FROM m_technique_level');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_tech_pk'];

	echo <<< EOF
\$_M_TECH['{$k}']['level']['{$r['level']}'] = ['m_cond_pk' => {$r['m_cond_pk']}, 'power' => {$r['power']}, 'increase_power' => {$r['increase_power']}];

EOF;
}

/*
 * m_technique_effect
$PgGame->query('SELECT m_tech_pk, level, effect_type, effect_value FROM m_technique_effect');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_tech_pk'];

	echo <<< EOF
\$_M_TECH['{$k}']['level']['{$r['level']}']['effects'][]= ['effect_type' => '{$r['effect_type']}', 'effect_value' => '{$r['effect_value']}');

EOF;
}
  */