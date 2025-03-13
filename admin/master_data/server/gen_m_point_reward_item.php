<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_POIN_REWA_ITEM'] = [];
\$_M_POIN_REWA_ITEM = &\$GLOBALS['_M_POIN_REWA_ITEM'];
\$_M['POIN_REWA_ITEM'] = &\$_M_POIN_REWA_ITEM;


EOF;

$note = 'm_poin_rewa_item';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_poin_rewa_item
$PgGame->query('SELECT * FROM m_point_reward_item');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_POIN_REWA_ITEM['{$r['type']}']['{$r['type_value']}']['{$r['m_item_pk']}'] = ['m_item_pk' => {$r['m_item_pk']}, 'item_cnt' => {$r['item_cnt']}, 'reward_rate' => {$r['reward_rate']}];

EOF;
}