<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_HERO_COLLECTION'] = [];
\$_M_HERO_COLLECTION = &\$GLOBALS['_M_HERO_COLLECTION'];
\$_M['HERO_COLLECTION'] = &\$_M_HERO_COLLECTION;


EOF;

$note = 'm_hero_collection';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_HERO_COLLECTION
$PgGame->query('SELECT m_hero_coll_pk, title, type, type_value, collection_count, reward_item FROM m_hero_collection ORDER BY m_hero_coll_pk');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_hero_coll_pk'];

	echo <<< EOF
\$_M_HERO_COLLECTION['$k'] = ['m_hero_coll_pk' => {$r['m_hero_coll_pk']}, 'title' => '{$r['title']}', 'type' => '{$r['type']}', 'type_value' => '{$r['type_value']}', 'collection_count' => {$r['collection_count']}, 'reward_item' => '{$r['reward_item']}'];

EOF;
}


