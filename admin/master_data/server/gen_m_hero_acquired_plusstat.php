<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_ACQUIRED_PLUSSTAT'] = [];
\$_M_HERO_ACQUIRED_PLUSSTAT = &\$GLOBALS['_M_HERO_ACQUIRED_PLUSSTAT'];
\$_M['HERO_ACQUIRED_PLUSSTAT'] = &\$_M_HERO_ACQUIRED_PLUSSTAT;


EOF;

$note = 'm_hero_acquired_plusstat';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_HERO_ACQUIRED_PLUSSTAT
$PgGame->query('SELECT type, 1000*rate AS recalc_rate, rate::numeric AS rate, plus1, plus2, plus3 FROM m_hero_acquired_plusstat ORDER BY type');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_HERO_ACQUIRED_PLUSSTAT['{$r['type']}'] = ['recalc_rate' => '{$r['recalc_rate']}', 'rate' => '{$r['rate']}', 'plus1' => '{$r['plus1']}', 'plus2' => '{$r['plus2']}', 'plus3' => '{$r['plus3']}'];

EOF;
}

