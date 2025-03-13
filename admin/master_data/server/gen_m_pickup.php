<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_PICK'] = [];
\$_M_PICK = &\$GLOBALS['_M_PICK'];
\$_M['PICK'] = &\$_M_PICK;


EOF;

$note = 'm_pickup';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";


// m_pickup
$PgGame->query('SELECT pickup_type, acquired_type, m_item_pk, item_count, need_qbig, pity_limit, pity_level, pity_rare, pity_hero, upper_rare, start_date, end_date FROM m_pickup WHERE visible = \'Y\' order by pickup_type');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['pickup_type'];

	echo <<< EOF
\$_M_PICK['{$k}'] = ['pickup_type' => {$r['pickup_type']}, 'acquired_type' => '{$r['acquired_type']}', 'm_item_pk' => '{$r['m_item_pk']}', 'item_count' => {$r['item_count']}, 'need_qbig' => {$r['need_qbig']}, 'pity_limit' => {$r['pity_limit']}, 'pity_level' => {$r['pity_level']}, 'pity_rare' => {$r['pity_rare']}, 'pity_hero' => '{$r['pity_hero']}', 'upper_rare' => '{$r['upper_rare']}', 'start_date' => '{$r['start_date']}', 'end_date' => '{$r['end_date']}'];

EOF;
}

