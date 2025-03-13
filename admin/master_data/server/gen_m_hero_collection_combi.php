<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_COLL_COMB'] = [];
\$_M_HERO_COLL_COMB = &\$GLOBALS['_M_HERO_COLL_COMB'];
\$_M['HERO_COLL_COMB'] = &\$_M_HERO_COLL_COMB;


EOF;

$note = 'm_hero';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_hero
$PgGame->query('SELECT * FROM m_hero_collection_combi ORDER BY m_hero_comb_coll_pk');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_hero_comb_coll_pk'];

	echo <<< EOF
\$_M_HERO_COLL_COMB['{$k}'] = ['m_hero_comb_coll_pk' => {$r['m_hero_comb_coll_pk']}, 'm_hero_base_pk' => {$r['m_hero_base_pk']}, 'name' => '{$r['name']}', 'open_type' => '{$r['open_type']}', 'repate_count' => {$r['repate_count']}, 'm_hero_pk' => {$r['m_hero_pk']}, 'material_1' => {$r['material_1']}, 'material_2' => {$r['material_2']}, 'material_3' => {$r['material_3']}, 'material_4' => {$r['material_4']}, 'material_5' => {$r['material_5']}, 'material_6' => {$r['material_6']}, 'material_7' => {$r['material_7']}, 'material_8' => {$r['material_8']}, 'description' => '{$r['description']}', 'acquired_type' => '{$r['acquired_type']}', 'material_count' => '{$r['material_count']}'];

EOF;
}

