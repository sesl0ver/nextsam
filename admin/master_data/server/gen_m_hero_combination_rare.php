<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_COMBINATION_RARE'] = [];
\$_M_HERO_COMBINATION_RARE = &\$GLOBALS['_M_HERO_COMBINATION_RARE'];
\$_M['HERO_COMBINATION_RARE'] = &\$_M_HERO_COMBINATION_RARE;


EOF;

$note = 'm_hero_combination_rare';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_hero
$PgGame->query('SELECT * FROM m_hero_combination_rare ORDER BY combi_score, rare_type');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['combi_score'];

	echo <<< EOF
\$_M_HERO_COMBINATION_RARE['{$k}'][] = ['combi_score' => {$r['combi_score']}, 'rare_type' => {$r['rare_type']}, 'rate' => '{$r['rate']}'];

EOF;
}

