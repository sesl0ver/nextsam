<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_COMBINATION_LEVEL'] = [];
\$_M_HERO_COMBINATION_LEVEL = &\$GLOBALS['_M_HERO_COMBINATION_LEVEL'];
\$_M['HERO_COMBINATION_LEVEL'] = &\$_M_HERO_COMBINATION_LEVEL;


EOF;

$note = 'm_hero_combination_level';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_hero
$PgGame->query('SELECT * FROM m_hero_combination_level ORDER BY rare_type, level');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['rare_type'];

	echo <<< EOF
\$_M_HERO_COMBINATION_LEVEL['{$k}'][] = ['rare_type' => {$r['rare_type']}, 'level' => {$r['level']}, 'score' => '{$r['score']}'];

EOF;
}

