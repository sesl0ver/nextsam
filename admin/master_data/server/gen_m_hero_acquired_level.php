<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_ACQUIRED_LEVEL'] = [];
\$_M_HERO_ACQUIRED_LEVEL = &\$GLOBALS['_M_HERO_ACQUIRED_LEVEL'];
\$_M['HERO_ACQUIRED_LEVEL'] = &\$_M_HERO_ACQUIRED_LEVEL;


EOF;

$note = 'm_hero_acquired_level';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_HERO_ACQUIRED_LEVEL
$PgGame->query('SELECT acquired_type, level, 10000*rate AS recalc_rate, rate::numeric AS rate FROM m_hero_acquired_level ORDER BY acquired_type, level');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_HERO_ACQUIRED_LEVEL['{$r['acquired_type']}']['{$r['level']}'] = ['recalc_rate' => '{$r['recalc_rate']}', 'rate' => '{$r['rate']}'];

EOF;
}

