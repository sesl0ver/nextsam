<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_NPC_ANN_REWARD'] = [];
\$_M_NPC_ANN_REWARD = &\$GLOBALS['_M_NPC_ANN_REWARD'];
\$_M['NPC_ANN_REWARD'] = &\$_M_NPC_ANN_REWARD;


EOF;

$note = 'm_npc_ann_reward';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_npc_ann_reward
$PgGame->query('SELECT type, level, reward_item_pk, reward_item_cnt FROM m_npc_ann_reward ORDER BY level, type');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;

	if (!$r['reward_item_pk'])
		$r['reward_item_pk'] = 'none';

	echo <<< EOF
\$_M_NPC_ANN_REWARD['{$r['level']}']['{$r['type']}'] = ['reward_item_pk' => {$r['reward_item_pk']}, 'cnt' => {$r['reward_item_cnt']}];

EOF;
}