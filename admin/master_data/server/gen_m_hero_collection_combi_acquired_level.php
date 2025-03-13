<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_COLL_COMB_ACQU_LEVE'] = [];
\$_M_HERO_COLL_COMB_ACQU_LEVE = &\$GLOBALS['_M_HERO_COLL_COMB_ACQU_LEVE'];
\$_M['HERO_COLL_COMB_ACQU_LEVE'] = &\$_M_HERO_COLL_COMB_ACQU_LEVE;


EOF;

$note = 'm_hero_collection_combi_acquired_level';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_HERO_COLLECTION_COMBI_ACQUIRED_LEVEL
$PgGame->query('SELECT acquired_type, level_average,  level, 10000*rate AS recalc_rate, rate::numeric AS rate FROM m_hero_collection_combi_acquired_level ORDER BY acquired_type, level_average');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_HERO_COLL_COMB_ACQU_LEVE['{$r['acquired_type']}']['{$r['level_average']}']['{$r['level']}'] = ['recalc_rate' => '{$r['recalc_rate']}', 'rate' => '{$r['rate']}'];

EOF;
}

