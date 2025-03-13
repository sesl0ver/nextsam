<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_ITEM'] = [];
\$_M_ITEM = &\$GLOBALS['_M_ITEM'];
\$_M['ITEM'] = &\$_M_ITEM;


EOF;

$note = 'm_item';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_item
$PgGame->query('SELECT use_type, m_item_pk, type, yn_sell, yn_use, yn_myitem_use, yn_use_duplication_type, yn_use_duplication_item, cooltime_type, cooltime_item, orderno, price, title, description, description_detail, description_quickuse, buff_time, buff_title, yn_market_sale, magiccube_rate, notice_magiccube, notice_common, display_type, supply_amount, limit_buy FROM m_item WHERE yn_use = \'Y\' order by m_item_pk');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_item_pk'];

	echo <<< EOF
\$_M_ITEM['{$k}'] = ['m_item_pk' => {$r['m_item_pk']}, 'type' => '{$r['type']}', 'use_type' => '{$r['use_type']}', 'yn_sell' => '{$r['yn_sell']}', 'yn_use' => '{$r['yn_use']}', 'yn_myitem_use' => '{$r['yn_myitem_use']}', 'yn_use_duplication_type' => '{$r['yn_use_duplication_type']}', 'yn_use_duplication_item' => '{$r['yn_use_duplication_item']}', 'cooltime_type' => {$r['cooltime_type']}, 'cooltime_item' => {$r['cooltime_item']}, 'orderno' => {$r['orderno']}, 'price' => {$r['price']}, 'title' => '{$r['title']}', 'description' => '{$r['description']}', 'description_detail' => '{$r['description_detail']}', 'description_quickuse' => '{$r['description_quickuse']}', 'buff_time' => '{$r['buff_time']}', 'buff_title' => '{$r['buff_title']}', 'yn_market_sale' => '{$r['yn_market_sale']}', 'magiccube_rate' => '{$r['magiccube_rate']}', 'notice_magiccube' => '{$r['notice_magiccube']}', 'notice_common' => '{$r['notice_common']}', 'display_type' => '{$r['display_type']}', 'supply_amount' => '{$r['supply_amount']}', 'limit_buy' => {$r['limit_buy']}];

EOF;
}

/*
 * m_item_effect
$PgGame->query('SELECT m_item_pk, effect_type, effect_value FROM m_item_effect');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_item_pk'];

	echo <<< EOF
\$_M_ITEM['{$k}']['effects'][]= ['effect_type' => '{$r['effect_type']}', 'effect_value' => '{$r['effect_value']}');

EOF;
}
  */

