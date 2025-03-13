<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_BUIL'] = [];
\$_M_BUIL = &\$GLOBALS['_M_BUIL'];
\$_M['BUIL'] = &\$_M_BUIL;


EOF;

$note = 'm_buil';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_building
$PgGame->query('SELECT m_buil_pk, title, type, update_type, alias, max_level, yn_demolish, yn_duplication, yn_hero_assign FROM m_building');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_buil_pk'];

	echo <<< EOF
\$_M_BUIL['{$k}']= ['m_buil_pk' => {$r['m_buil_pk']}, 'title' => '{$r['title']}', 'type' => '{$r['type']}', 'update_type' => '{$r['update_type']}', 'alias' => '{$r['alias']}', 'max_level' => {$r['max_level']}, 'yn_demolish' => '{$r['yn_demolish']}', 'yn_duplication' => '{$r['yn_duplication']}', 'yn_hero_assign' => '{$r['yn_hero_assign']}'];

EOF;
}

// m_building_level
$PgGame->query('SELECT m_buil_pk, level, m_cond_pk, variation_1, variation_2, power, increase_power FROM m_building_level');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_buil_pk'];

	echo <<< EOF
\$_M_BUIL['{$k}']['level']['{$r['level']}']= ['m_cond_pk' => {$r['m_cond_pk']}, 'variation_1' => {$r['variation_1']}, 'variation_2' => {$r['variation_2']}, 'power' => {$r['power']}, 'increase_power' => {$r['increase_power']}];

EOF;
}

