<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo 'if (ns_cs && ns_cs.m) { ns_cs.m.qbig_pack={};';

// m_qbig_pack
$PgGame->query('SELECT m_qbi_pac_pk, store_type, prod_id, disp_name, disp_price, qbig_total, qbig_buy, qbig_bonus, m_item_pk, item_cnt, price FROM m_qbig_pack WHERE visible = \'Y\' ORDER BY store_type ASC, m_qbi_pac_pk ASC');

$store_type = '';

while ($PgGame->fetch()) {
	$r = &$PgGame->row;

	if ($store_type != $r['store_type']) {
        echo "ns_cs.m.qbig_pack['{$r['store_type']}']=[];";
    }

	$json = json_encode($r);

	echo "ns_cs.m.qbig_pack['{$r['store_type']}'].push({$json});";

	$store_type = $r['store_type'];
}

echo '}';
