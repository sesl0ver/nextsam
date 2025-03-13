<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_ITEM_MAGICCUBE'] = [];
\$_M_ITEM_MAGICCUBE = &\$GLOBALS['_M_ITEM_MAGICCUBE'];
\$_M['ITEM_MAGICCUBE'] = &\$_M_ITEM_MAGICCUBE;


EOF;

$note = 'm_item_magiccube';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_ITEM_MAGICCUBE
$PgGame->query('SELECT m_item_pk, orderno, price, title, magiccube_rate FROM m_item WHERE magiccube_rate > 0 order by m_item_pk');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_item_pk'];

	echo <<< EOF
\$_M_ITEM_MAGICCUBE['{$k}'] = ['m_item_pk' => {$r['m_item_pk']}, 'orderno' => {$r['orderno']}, 'price' => {$r['price']}, 'title' => '{$r['title']}', 'magiccube_rate' => '{$r['magiccube_rate']}'];

EOF;
}
