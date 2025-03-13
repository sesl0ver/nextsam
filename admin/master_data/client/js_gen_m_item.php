<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.item={};';

// m_item
$PgGame->query('SELECT use_type, m_item_pk, display_type, type, yn_sell, yn_use, yn_myitem_use, yn_use_duplication_type, yn_use_duplication_item, cooltime_type, cooltime_item, orderno, price, title, description, description_detail, description_quickuse, buff_time, buff_title, description_buff, popularity, sell_type, yn_use_again, supply_amount, limit_buy FROM m_item WHERE yn_use = \'Y\' ORDER BY orderno ASC');

while ($PgGame->fetch()) {
	$r = &$PgGame->row;
	$k = &$r['m_item_pk'];

	$json = json_encode($r);

	echo "ns_cs.m.item['{$k}'] = {$json};";
}

echo '}';
