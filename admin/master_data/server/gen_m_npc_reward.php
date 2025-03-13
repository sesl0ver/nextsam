<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_NPC_REWARD'] = [];
\$_M_NPC_REWARD = &\$GLOBALS['_M_NPC_REWARD'];
\$_M['NPC_REWARD'] = &\$_M_NPC_REWARD;


EOF;

$note = 'm_npc_reward';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// M_NPC_REWARD
$PgGame->query('SELECT type, level, reward_item_pk, reward_item_cnt, reward_rate, 100*reward_rate AS recalc_rate FROM m_npc_reward ORDER BY type, level');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	if (!$r['reward_item_pk'])
		$r['reward_item_pk'] = 'none';

	echo <<< EOF
\$_M_NPC_REWARD['{$r['type']}']['{$r['level']}']['{$r['reward_item_pk']}'] = ['cnt' => {$r['reward_item_cnt']}, 'rate' => {$r['reward_rate']}, 'recalc_rate' => {$r['recalc_rate']}];

EOF;
}