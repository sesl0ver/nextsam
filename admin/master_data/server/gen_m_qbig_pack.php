<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_QBIG_PACK'] = [];
\$_M_QBIG_PACK = &\$GLOBALS['_M_QBIG_PACK'];
\$_M['QBIG_PACK'] = &\$_M_QBIG_PACK;


EOF;

$note = 'm_qbig_pack';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_qbig_pack
$PgGame->query('SELECT store_type, pack_type, prod_id, qbig_total, m_item_pk, item_cnt, disp_price, qbig_buy, qbig_bonus, price FROM m_qbig_pack WHERE visible = \'Y\'');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_QBIG_PACK['{$r['store_type']}']['{$r['prod_id']}'] = ['pack_type' => {$r['pack_type']}, 'qbig_total' => {$r['qbig_total']}, 'm_item_pk' => '{$r['m_item_pk']}', 'item_cnt' => '{$r['item_cnt']}', 'price' => {$r['price']}, 'disp_price' => '{$r['disp_price']}', 'qbig_buy' => {$r['qbig_buy']}, 'qbig_bonus' => {$r['qbig_bonus']}];

EOF;
}