<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

$PgGame = new Pg('DEFAULT');

echo <<< EOF
<?php
if (!isset(\$_M)) \$_M = &\$GLOBALS['_M'];
\$GLOBALS['_M_OCCUPATION_REWARD'] = [];
\$_M_OCCUPATION_REWARD = &\$GLOBALS['_M_OCCUPATION_REWARD'];
\$_M['OCCUPATION_REWARD'] = &\$_M_OCCUPATION_REWARD;


EOF;

$note = 'm_occupation_reward';
$PgGame->query('INSERT INTO md_update_history (note) VALUES ($1)', [$note]);
echo "\$_M['VERSION']['$note'] = '". CONF_CDN_VERSION. "';\n\n";

// m_productivity_valley
$PgGame->query('SELECT reward_type, rank, need_point, reward_item FROM m_occupation_reward');

while ($PgGame->fetch())
{
	$r = &$PgGame->row;
	$k = &$r['reward_type'];

	echo <<< EOF
\$_M_OCCUPATION_REWARD['{$k}']['{$r['rank']}'] = ['reward_type' => '{$r['reward_type']}', 'rank' => {$r['rank']}, 'need_point' => {$r['need_point']}, 'reward_item' => '{$r['reward_item']}'];

EOF;
}