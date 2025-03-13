<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_SOCIAL_GIFT'] = [];
\$_M_SOCIAL_GIFT = &\$GLOBALS['_M_SOCIAL_GIFT'];
\$_M['SOCIAL_GIFT'] = &\$_M_SOCIAL_GIFT;


EOF;

$note = 'm_social_gift';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_SOCIAL_GIFT
$PgGame->query('SELECT level, m_item_pk, item_cnt FROM m_social_gift ORDER BY level, order_num');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M_SOCIAL_GIFT['{$r['level']}'][] = ['m_item_pk' => {$r['m_item_pk']}, 'item_cnt' => {$r['item_cnt']}];

EOF;
}