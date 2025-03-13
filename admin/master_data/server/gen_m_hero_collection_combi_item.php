<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_COLL_COMB_ITEM'] = [];
\$_M_HERO_COLL_COMB_ITEM = &\$GLOBALS['_M_HERO_COLL_COMB_ITEM'];
\$_M['HERO_COLL_COMB_ITEM'] = &\$_M_HERO_COLL_COMB_ITEM;


EOF;

$note = 'm_hero_collection_combi_item';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_hero_coll_comb_item
$PgGame->query('SELECT m_item_pk, m_hero_base_pk, orderno, type, m_hero_comb_coll_pk FROM m_hero_collection_combi_item ORDER BY m_item_pk, orderno');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_HERO_COLL_COMB_ITEM['{$r['m_item_pk']}']['{$r['m_hero_base_pk']}'] = ['m_hero_base_pk' => '{$r['m_hero_base_pk']}', 'orderno' => '{$r['orderno']}', 'type' => '{$r['type']}', 'm_hero_comb_coll_pk' => '{$r['m_hero_comb_coll_pk']}'];

EOF;
}

