<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_ACQUIRED_RARE'] = [];
\$_M_HERO_ACQUIRED_RARE = &\$GLOBALS['_M_HERO_ACQUIRED_RARE'];
\$_M['HERO_ACQUIRED_RARE'] = &\$_M_HERO_ACQUIRED_RARE;


EOF;

$note = 'm_acquired_rare';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_HERO_ACQUIRED_RARE
$PgGame->query('SELECT acquired_type, level, rare_type, 1000000*rate AS recalc_rate, rate::numeric AS rate FROM m_hero_acquired_rare ORDER BY acquired_type, level DESC, rare_type');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_HERO_ACQUIRED_RARE['{$r['acquired_type']}']['{$r['level']}']['{$r['rare_type']}'] = ['recalc_rate' => '{$r['recalc_rate']}', 'rate' => '{$r['rate']}'];

EOF;
}

