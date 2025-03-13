<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_ACQUIRED_SKILL'] = [];
\$_M_HERO_ACQUIRED_SKILL = &\$GLOBALS['_M_HERO_ACQUIRED_SKILL'];
\$_M['HERO_ACQUIRED_SKILL'] = &\$_M_HERO_ACQUIRED_SKILL;


EOF;

$note = 'm_hero_acquired_skill';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_HERO_ACQUIRED_SKILL
$PgGame->query('SELECT type, rare, 1000*rate AS recalc_rate, rate::numeric AS rate FROM m_hero_acquired_skill ORDER BY type, rare, rate DESC');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_HERO_ACQUIRED_SKILL['{$r['type']}'][{$r['rare']}] = ['recalc_rate' => '{$r['recalc_rate']}', 'rate' => '{$r['rate']}'];

EOF;
}


