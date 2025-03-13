<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_PACKAGE'] = [];
\$_M_PACKAGE = &\$GLOBALS['_M_PACKAGE'];
\$_M['PACKAGE'] = &\$_M_PACKAGE;


EOF;

$note = 'm_package';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";


// m_pickup
$PgGame->query('SELECT m_pack_pk, title, description, price, target_type, target_pk, target_value, reward_item, buy_limit, time_limit FROM m_package ORDER BY m_pack_pk');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['m_pack_pk'];

	echo <<< EOF
\$_M_PACKAGE['{$k}'] = ['m_pack_pk' => {$r['m_pack_pk']}, 'title' => '{$r['title']}', 'description' => '{$r['description']}', 'price' => {$r['price']}, 'target_type' => '{$r['target_type']}', 'target_pk' => '{$r['target_pk']}', 'target_value' => {$r['target_value']}, 'reward_item' => '{$r['reward_item']}', 'buy_limit' => {$r['buy_limit']}, 'time_limit' => {$r['time_limit']}];

EOF;
}

