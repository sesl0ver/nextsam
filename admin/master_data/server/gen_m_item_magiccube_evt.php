<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_ITEM_MAGICCUBE_EVT'] = [];
\$_M_ITEM_MAGICCUBE_EVT = &\$GLOBALS['_M_ITEM_MAGICCUBE_EVT'];
\$_M['ITEM_MAGICCUBE_EVT'] = &\$_M_ITEM_MAGICCUBE_EVT;


EOF;

$note = 'm_item_magiccube_evt';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_ITEM_MAGICCUBE_EVT
$PgGame->query('SELECT m_item_pk, orderno, price, title, magiccube_rate FROM m_item WHERE magiccube_rate > 0 order by m_item_pk ASC');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_item_pk'];

	echo <<< EOF
\$_M_ITEM_MAGICCUBE_EVT['{$k}'] = ['m_item_pk' => {$r['m_item_pk']}, 'orderno' => {$r['orderno']}, 'price' => {$r['price']}, 'title' => '{$r['title']}', 'magiccube_rate' => '{$r['magiccube_rate']}'];

EOF;
}



echo <<< EOF
\$_M_ITEM_MAGICCUBE_EVT['500126']= ['m_item_pk' => 500126, 'orderno' => 2020, 'price' => 10, 'title' => '큐빅패키지(5)', 'magiccube_rate' => '286'];
\$_M_ITEM_MAGICCUBE_EVT['500495']= ['m_item_pk' => 500495, 'orderno' => 1021, 'price' => 25, 'title' => '큐빅패키지(25)', 'magiccube_rate' => '100'];
\$_M_ITEM_MAGICCUBE_EVT['500127']= ['m_item_pk' => 500127, 'orderno' => 2030, 'price' => 100, 'title' => '큐빅패키지(50)', 'magiccube_rate' => '43'];

EOF;