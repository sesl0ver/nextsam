<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_SOCIAL_INVITE_REWARD'] = [];
\$_M_SOCIAL_INVITE_REWARD = &\$GLOBALS['_M_SOCIAL_INVITE_REWARD'];
\$_M['SOCIAL_INVITE_REWARD'] = &\$_M_SOCIAL_INVITE_REWARD;


EOF;

$note = 'm_social_INVITE_REWARD';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_SOCIAL_INVITE_REWARD
$PgGame->query('SELECT m_item_pk, item_cnt, (rate * 1000) AS rate FROM m_social_INVITE_REWARD ORDER BY m_item_pk');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	echo <<< EOF
\$_M['SOCIAL_INVITE_REWARD']['{$r['m_item_pk']}'] = ['item_cnt' => {$r['item_cnt']}, 'rate' => {$r['rate']}];

EOF;
}